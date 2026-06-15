<?php
header('Content-Type: text/plain; charset=utf-8');

function normalizeTeamName(string $team): string
{
    $team = str_ireplace(
        ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
        ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
        $team
    );
    $team = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $team) ?: $team;
    $team = strtolower($team);
    $team = str_replace(["'", '`', '~', '^'], '', $team);
    $team = preg_replace('/\b(seleccion de futbol de|seleccion de|deportiva|fc|cf|sc|club|de|la|el|the)\b/i', ' ', $team) ?? $team;
    $team = preg_replace('/[^a-z0-9]+/', ' ', $team) ?? $team;
    return trim(preg_replace('/\s+/', ' ', $team) ?? $team);
}

echo "Irán: " . normalizeTeamName("Irán") . "\n";
echo "Malí: " . normalizeTeamName("Malí") . "\n";
echo "Afganistán: " . normalizeTeamName("Afganistán") . "\n";
echo "España: " . normalizeTeamName("España") . "\n";
echo "Japón: " . normalizeTeamName("Japón") . "\n";
