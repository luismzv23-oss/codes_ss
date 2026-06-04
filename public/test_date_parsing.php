<?php
header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/serpapi_si_result.json';
if (!file_exists($file)) {
    die("File serpapi_si_result.json not found!\n");
}
$data = json_decode(file_get_contents($file), true);

function serpApiText($value, string $fallback = ''): string
{
    if ($value === null) {
        return $fallback;
    }
    if (is_scalar($value)) {
        $text = trim((string) $value);
        return $text !== '' ? $text : $fallback;
    }
    if (is_array($value)) {
        foreach (['name', 'title', 'league', 'text', 'label', 'displayName'] as $key) {
            if (array_key_exists($key, $value)) {
                $text = serpApiText($value[$key], '');
                if ($text !== '') {
                    return $text;
                }
            }
        }
        $parts = [];
        foreach ($value as $item) {
            $text = serpApiText($item, '');
            if ($text !== '') {
                $parts[] = $text;
            }
        }
        $text = trim(implode(' ', array_unique($parts)));
        return $text !== '' ? $text : $fallback;
    }
    return $fallback;
}

function translateSerpApiDate(string $value): string
{
    $spanishMonths = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic', ' de '];
    $englishMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', ' '];
    $spanishDays = ['lun.', 'mar.', 'mie.', 'miÃ©.', 'jue.', 'vie.', 'sab.', 'sÃ¡b.', 'dom.', 'lun', 'mar', 'mie', 'miÃ©', 'jue', 'vie', 'sab', 'sÃ¡b', 'dom'];
    $englishDays = ['Mon', 'Tue', 'Wed', 'Wed', 'Thu', 'Fri', 'Sat', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Wed', 'Thu', 'Fri', 'Sat', 'Sat', 'Sun'];

    $value = str_ireplace($spanishMonths, $englishMonths, $value);
    $value = str_ireplace($spanishDays, $englishDays, $value);
    $value = str_ireplace(['Hoy', 'MaÃ±ana', 'Manana', 'Ayer'], ['Today', 'Tomorrow', 'Tomorrow', 'Yesterday'], $value);
    return str_ireplace(['a. m.', 'p. m.', 'a.m.', 'p.m.'], ['AM', 'PM', 'AM', 'PM'], $value);
}

function extractDateFromSearchQuery(string $query): string
{
    if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', $query, $matches)) {
        return $matches[1] . '/' . $matches[2] . '/' . $matches[3];
    }
    return date('Y-m-d');
}

function parseSerpApiStartTime(array $match, string $query = ''): string
{
    foreach (['start_time', 'startTime', 'datetime', 'date_time', 'utcDate'] as $key) {
        $candidate = serpApiText($match[$key] ?? null, '');
        if ($candidate !== '') {
            $timestamp = strtotime(translateSerpApiDate($candidate));
            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }
    }

    $dateStr = serpApiText($match['date'] ?? null, '');
    $timeStr = serpApiText($match['time'] ?? null, '');
    if ($timeStr === '' || preg_match('/en vivo|live|fin|final/i', $timeStr)) {
        $timeStr = '00:00';
    }

    // Preprocess: e.g. "Mar 2-6" -> "2026-6-2"
    if (preg_match('/\b(lun|mar|mié|mie|jue|vie|sáb|sab|dom)\s+(\d{1,2})[-|\/](\d{1,2})\b/i', $dateStr, $m)) {
        $day = (int)$m[2];
        $month = (int)$m[3];
        $year = date('Y');
        $dateStr = "{$year}-{$month}-{$day}";
    }

    $dateStr = $dateStr !== '' ? $dateStr : extractDateFromSearchQuery($query);
    $translated = translateSerpApiDate(trim($dateStr . ' ' . $timeStr));

    if (!preg_match('/\d{4}/', $translated)) {
        $translated .= ' ' . date('Y');
    }

    $timestamp = strtotime($translated);
    if ($timestamp === false) {
        $timestamp = strtotime(date('Y-m-d') . ' 00:00:00');
    }

    return date('Y-m-d H:i:s', $timestamp);
}

$query = 'Amistosos Internacionales Hoy';

if (isset($data['sports_results']['games'])) {
    echo "Total games found: " . count($data['sports_results']['games']) . "\n\n";
    $count = 0;
    foreach ($data['sports_results']['games'] as $match) {
        if ($count++ >= 10) break; // show first 10
        $home = $match['teams'][0]['name'] ?? 'Home';
        $away = $match['teams'][1]['name'] ?? 'Away';
        
        $startTime = parseSerpApiStartTime($match, $query);
        
        $timestamp = strtotime($startTime);
        $hasRealTime = substr($startTime, 11) !== '00:00:00';
        $matchDateLabel = $timestamp !== false
            ? ($hasRealTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp) . ' (A confirmar)')
            : 'Fecha no disponible';
            
        echo "Match: {$home} vs {$away}\n";
        echo "  - Raw Date: " . ($match['date'] ?? 'N/A') . "\n";
        echo "  - Raw Time: " . ($match['time'] ?? 'N/A') . "\n";
        echo "  - Raw Status: " . ($match['status'] ?? 'N/A') . "\n";
        echo "  - Parsed Start Time: {$startTime}\n";
        echo "  - Match Date Label: {$matchDateLabel}\n\n";
    }
} else {
    echo "No games key found under sports_results.\n";
}
