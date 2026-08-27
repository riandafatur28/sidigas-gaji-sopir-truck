<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Facade that delegates to focused services.
 * Kept for backward compatibility with existing callers.
 */
class RitaseParserService
{
    private RitaseTextParser $parser;
    private RitaseFuzzyMatcher $matcher;
    private RitaseCreator $creator;

    public function __construct()
    {
        $this->parser = new RitaseTextParser();
        $this->matcher = new RitaseFuzzyMatcher();
        $this->creator = new RitaseCreator();
    }

    public function parse(string $text): array
    {
        return $this->parser->parse($text);
    }

    public function matchDrivers(array $driverNames): array
    {
        return $this->matcher->matchDrivers($driverNames);
    }

    public function matchRoutes(array $routeNames): array
    {
        return $this->matcher->matchRoutes($routeNames);
    }

    public function createRitases(array $parsed, int $periodeId, array $driverMatches = [], array $routeMatches = []): array
    {
        return $this->creator->create($parsed, $periodeId, $driverMatches, $routeMatches);
    }

    public function cleanDriverName(string $name): string
    {
        return $this->parser->cleanDriverName($name);
    }

    public function looksLikeDriverName(string $line): bool
    {
        return $this->parser->looksLikeDriverName($line);
    }

    public function guessKabupaten(string $routeName): string
    {
        return $this->creator->guessKabupaten($routeName);
    }

    public function guessWaktu(string $routeName): string
    {
        return $this->creator->guessWaktu($routeName);
    }

    public function calculateStringSimilarity(string $str1, string $str2): float
    {
        return $this->matcher->calculateStringSimilarity($str1, $str2);
    }
}
