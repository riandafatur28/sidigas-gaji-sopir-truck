<?php

namespace App\Http\Controllers;

use App\Services\RitaseParserService;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RitaseParserController extends Controller
{
    public function __construct(
        protected RitaseParserService $parser
    ) {}

    /**
     * Parse text and return structured preview (no DB writes).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:50000',
            'periode_id' => 'nullable|exists:periodes,id',
        ]);

        $parsed = $this->parser->parse($request->input('text'));
        
        // Add matching previews
        $driverNames = collect($parsed['packages'])->pluck('drivers')->flatten()->unique()->values()->all();
        $routeNames = collect($parsed['packages'])->pluck('route_name')->unique()->values()->all();

        $driverMatches = $this->parser->matchDrivers($driverNames);
        $routeMatches = $this->parser->matchRoutes($routeNames);

        return response()->json([
            'parsed' => $parsed,
            'driver_matches' => $driverMatches,
            'route_matches' => $routeMatches,
            'summary' => [
                'date' => $parsed['date'],
                'total_packages' => count($parsed['packages']),
                'total_drivers' => count($driverNames),
                'drivers_matched' => collect($driverMatches)->where('matched', true)->count(),
                'routes_matched' => collect($routeMatches)->where('matched', true)->count(),
            ],
        ]);
    }

    /**
     * Parse text and create Ritase records.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:50000',
            'periode_id' => 'required|exists:periodes,id',
            'confirm' => 'required|boolean',
        ]);

        if (!$request->boolean('confirm')) {
            return response()->json([
                'message' => 'Confirmation required. Set confirm=true to proceed.',
            ], 422);
        }

        $parsed = $this->parser->parse($request->input('text'));
        $results = $this->parser->createRitases($parsed, $request->integer('periode_id'));

        return response()->json([
            'message' => "Parsed and created {$results['created']} ritase records",
            'results' => $results,
        ]);
    }

    /**
     * Test endpoint with sample data.
     */
    public function test(): JsonResponse
    {
        $sampleText = "22 07 26 rabu
Bondan patching pare kota
Paket cmm blitar kota
1. Riki
2. Kola
3. Firsa
4. Wahyu
5. Ginem
6. Mbah POR 
7. Didik 
8. Yuri
9. Agung
Paket watualang ngawi
1. Gun
2. Anjar
3. Wilujeng
4. Yanto
5. Soim
6. Kuwat
7. Toni
8. Aripin
9. Avit
10. Radib
11. Topik
12. Narji
13. Eka bence
14. Prapto
15. Berok
16. Manto
17. Eko Wilangan
18. Torik
19. Adib
20. Wakub";

        $parsed = $this->parser->parse($sampleText);
        
        $driverNames = collect($parsed['packages'])->pluck('drivers')->flatten()->unique()->values()->all();
        $routeNames = collect($parsed['packages'])->pluck('route_name')->unique()->values()->all();

        $driverMatches = $this->parser->matchDrivers($driverNames);
        $routeMatches = $this->parser->matchRoutes($routeNames);

        return response()->json([
            'parsed' => $parsed,
            'driver_matches' => $driverMatches,
            'route_matches' => $routeMatches,
        ]);
    }
}