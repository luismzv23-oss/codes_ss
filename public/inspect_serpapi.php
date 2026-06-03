<?php
$file = __DIR__ . '/serpapi_result.json';
if (!file_exists($file)) {
    die("File not found\n");
}
$data = json_decode(file_get_contents($file), true);

echo "Keys in root:\n";
print_r(array_keys($data));

if (isset($data['sports_results'])) {
    echo "\nKeys in sports_results:\n";
    print_r(array_keys($data['sports_results']));
    
    echo "\nsports_results content (partial or key values):\n";
    if (isset($data['sports_results']['game_spotlight'])) {
        echo "game_spotlight keys:\n";
        print_r(array_keys($data['sports_results']['game_spotlight']));
        echo "game_spotlight teams:\n";
        print_r($data['sports_results']['game_spotlight']['teams']);
    } else {
        echo "No game_spotlight found. sports_results keys are: " . implode(', ', array_keys($data['sports_results'])) . "\n";
    }
} else {
    echo "\nNo sports_results found in root. Trying to search key 'sports_results' in whole JSON.\n";
}
