<?php

namespace App\Services;

use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\Periode;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class LlmRitaseParserService extends RitaseParserService
{
    /**
     * Parse raw text message into structured ritase data using LLM.
     * Falls back to rule-based parsing if LLM API unavailable.
     */
    public function parse(string $text): array
    {
        // Try LLM first
        $llmResult = $this->tryLlmParse($text);

        if ($llmResult !== null) {
            return $llmResult;
        }

        // Fallback ke rule-based parser (parent)
        return parent::parse($text);
    }

    /**
     * Try parsing with LLM API.
     */
    protected function tryLlmParse(string $text): ?array
    {
        $apiKey = config('services.llm.api_key', '');
        $endpoint = config('services.llm.endpoint', '');
        $model = config('services.llm.model', 'gpt-4');

        if (empty($apiKey) || empty($endpoint)) {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                        ['role' => 'user', 'content' => "Parse this text:\n{$text}"],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 2000,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return null;
            }

            return $this->parseLlmResponse($content, $text);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build system prompt for LLM.
     */
    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
Kamu adalah asisten yang mengekstrak jadwal sopir dump truck dari teks grup chat Indonesia.
Ekstrak data ke format JSON yang ketat.

CONTOH INPUT:
```
22 07 26 rabu
Bondan patching pare kota
Paket cmm blitar kota
1. Riki
2. Kola
Paket watualang ngawi
1. Gun
2. Anjar
```

CONTOH OUTPUT JSON:
{
  "date": "2026-07-26",
  "packages": [
    {
      "route_name": "Bondan patching pare kota",
      "drivers": ["Riki", "Kola"]
    },
    {
      "route_name": "Paket cmm blitar kota",
      "drivers": []
    },
    {
      "route_name": "Paket watualang ngawi",
      "drivers": ["Gun", "Anjar"]
    }
  ],
  "hallucination_check": true,
  "pattern_match_score": 0.95,
  "notes": "Parsing completed successfully"
}

ATURAN:
- Date: format Y-m-d. Baris pertama biasanya "DD MM YY hari".
- Packages: array of {route_name, drivers[]}.
  - Route: baris yang mengandung kata kunci (paket, bondan, patching, kota, rute, kabupaten).
  - Drivers: nama dari baris bernomor "1. Nama", "2. Nama", dst.
- Hallucination: true jika data terlihat valid dan konsisten.
- Pattern_match_score: 0.0-1.0, seberapa cocok dengan pola yang diharapkan.

HANYA KELUARKAN JSON. Tidak ada teks lain.
PROMPT;
    }

    /**
     * Parse LLM response content into structured data.
     */
    protected function parseLlmResponse(string $content, string $originalText): ?array
    {
        // Coba parse langsung
        $decoded = json_decode($content, true);

        // Jika gagal, coba extract dari ```json ... ```
        if (!$decoded || json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $m)) {
                $decoded = json_decode($m[1], true);
            }
        }

        // Jika masih gagal, coba ambil JSON object pertama
        if (!$decoded || json_last_error() !== JSON_ERROR_NONE) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
            }
        }

        if (!$decoded || !isset($decoded['date'], $decoded['packages'])) {
            return null;
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $decoded['date'])) {
            return null;
        }

        // Validate packages structure
        $packages = [];
        foreach ($decoded['packages'] as $pkg) {
            if (!isset($pkg['route_name']) || !isset($pkg['drivers'])) {
                continue;
            }
            $packages[] = [
                'route_name' => $pkg['route_name'],
                'drivers' => array_values(array_filter(array_map('trim', (array)$pkg['drivers']))),
            ];
        }

        if (empty($packages)) {
            return null;
        }

        // Hitung confidence score
        $confidence = $this->calculateConfidence($decoded, $originalText);

        return [
            'date' => $decoded['date'],
            'packages' => $packages,
            'confidence' => round($confidence, 2),
            'hallucination_detected' => $confidence < 70,
            'source' => 'llm',
        ];
    }

    /**
     * Calculate confidence score for LLM result.
     */
    protected function calculateConfidence(array $decoded, string $originalText): float
    {
        $score = 0;
        $totalDrivers = 0;
        $totalRoutes = count($decoded['packages'] ?? []);

        foreach ($decoded['packages'] as $pkg) {
            $totalDrivers += count($pkg['drivers'] ?? []);
        }

        // Base score: struktur valid
        if (isset($decoded['date'])) $score += 20;
        if (isset($decoded['packages']) && $totalRoutes > 0) $score += 20;

        // Hallucination check dari LLM
        if (!empty($decoded['hallucination_check'])) $score += 15;

        // Pattern match score
        if (isset($decoded['pattern_match_score'])) {
            $score += $decoded['pattern_match_score'] * 20;
        }

        // Setidaknya ada driver atau route
        if ($totalDrivers > 0) $score += 10;
        if ($totalRoutes > 0) $score += 10;

        // Verifikasi dengan original text
        $lines = preg_split('/\r\n|\n|\r/', $originalText);
        $numberedLines = 0;
        foreach ($lines as $line) {
            if (preg_match('/^\d+\./', trim($line))) $numberedLines++;
        }

        if ($numberedLines > 0 && $numberedLines <= $totalDrivers + 2) $score += 5;

        return min($score, 100);
    }
}
