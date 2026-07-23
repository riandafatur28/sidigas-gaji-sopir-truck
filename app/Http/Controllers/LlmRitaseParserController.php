<?php

namespace App\Http\Controllers;

use App\Models\Periode;
use App\Services\LlmRitaseParserService;
use Illuminate\Http\Request;

class LlmRitaseParserController extends Controller
{
    /**
     * Show LLM parser form.
     */
    public function form()
    {
        $periodes = Periode::orderBy('id', 'desc')->get();
        return view('ritase.llm-parser', compact('periodes'));
    }

    /**
     * Process parsed text with LLM.
     */
    public function process(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'periode_id' => 'required|exists:periodes,id',
            'auto_create' => 'boolean',
        ]);

        $parser = app(LlmRitaseParserService::class);
        $parsed = $parser->parse($request->text);

        if (empty($parsed['date'])) {
            return back()->withErrors(['text' => 'Tanggal tidak terdeteksi. Format: DD MM YY hari'])
                ->withInput();
        }

        $driverMatches = $parser->matchDrivers(
            collect($parsed['packages'])->pluck('drivers')->flatten()->unique()->values()->all()
        );
        $routeMatches = $parser->matchRoutes(
            collect($parsed['packages'])->pluck('route_name')->unique()->values()->all()
        );

        $results = [
            'date' => $parsed['date'],
            'packages' => $parsed['packages'],
            'driver_matches' => $driverMatches,
            'route_matches' => $routeMatches,
            'confidence' => $parsed['confidence'] ?? 100,
            'hallucination_detected' => $parsed['hallucination_detected'] ?? false,
            'source' => $parsed['source'] ?? 'rule-based',
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
            'details' => [],
        ];

        if ($request->boolean('auto_create')) {
            $createResult = $parser->createRitases($parsed, $request->periode_id, $driverMatches, $routeMatches);
            $results['created'] = $createResult['created'];
            $results['skipped'] = $createResult['skipped'];
            $results['errors'] = array_merge($results['errors'], $createResult['errors']);
            $results['details'] = $createResult['details'];
        }

        return view('ritase.llm-parser-result', compact('results'));
    }
}
