<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');
foreach (['serpapi_today_amistosos.json', 'serpapi_si_result.json', 'serpapi_result.json'] as $filename) {
    $file = __DIR__ . '/' . $filename;
    echo "====================================\n";
    echo "FILE: {$filename}\n";
    if (!file_exists($file)) {
        echo "File not found!\n";
        continue;
    }
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    if ($data === null) {
        echo "JSON Decode Error: " . json_last_error_msg() . "\n";
        continue;
    }
    echo "Root keys: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['knowledge_graph'])) {
        echo "Knowledge Graph keys: " . implode(', ', array_keys($data['knowledge_graph'])) . "\n";
        if (isset($data['knowledge_graph']['tabs'])) {
            echo "Knowledge Graph Tabs:\n";
            foreach ($data['knowledge_graph']['tabs'] as $i => $tab) {
                echo "  Tab #{$i}: text='{$tab['text']}', link_exists=" . (isset($tab['link']) ? 'yes' : 'no') . ", serpapi_link_exists=" . (isset($tab['serpapi_link']) ? 'yes' : 'no') . ", si_exists=" . (isset($tab['si']) ? 'yes' : 'no') . "\n";
            }
        }
    }
    
    if (isset($data['sports_results'])) {
        echo "Sports Results keys: " . implode(', ', array_keys($data['sports_results'])) . "\n";
        if (isset($data['sports_results']['games'])) {
            echo "Games count: " . count($data['sports_results']['games']) . "\n";
            $first = $data['sports_results']['games'][0] ?? null;
            if ($first) {
                echo "First game keys: " . implode(', ', array_keys($first)) . "\n";
                echo "First game date: " . ($first['date'] ?? 'N/A') . " | time: " . ($first['time'] ?? 'N/A') . " | status: " . ($first['status'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "No sports_results found\n";
    }
}
