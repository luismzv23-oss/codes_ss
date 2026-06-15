<?php
header('Content-Type: text/plain; charset=utf-8');

function normalizeTeamName(string $team): string
{
    // Convert accents to plain characters first
    $team = str_ireplace(
        ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'],
        $team
    );
    // Then do iconv translit to catch any others
    $team = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $team) ?: $team;
    $team = strtolower($team);
    // Clean up iconv artifacts
    $team = str_replace(["'", '`', '~', '^'], '', $team);
    $team = preg_replace('/\b(seleccion de futbol de|seleccion de|deportiva|fc|cf|sc|club|de|la|el|the)\b/i', ' ', $team) ?? $team;
    $team = preg_replace('/[^a-z0-9]+/', ' ', $team) ?? $team;
    return trim(preg_replace('/\s+/', ' ', $team) ?? $team);
}

echo "Irán: '" . normalizeTeamName("Irán") . "'\n";
echo "Malí: '" . normalizeTeamName("Malí") . "'\n";
echo "Afganistán: '" . normalizeTeamName("Afganistán") . "'\n";
echo "España: '" . normalizeTeamName("España") . "'\n";
echo "Japón: '" . normalizeTeamName("Japón") . "'\n";
