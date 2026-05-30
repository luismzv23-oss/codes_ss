<?php
$files = [
    'app/Views/sportsbook/index.php',
    'app/Views/sportsbook/event.php'
];

foreach ($files as $relPath) {
    $fullPath = __DIR__ . '/../' . $relPath;
    if (!file_exists($fullPath)) {
        echo "File not found: $relPath\n";
        continue;
    }
    
    echo "=== Searching in $relPath ===\n";
    $lines = file($fullPath);
    foreach ($lines as $i => $line) {
        $lineNum = $i + 1;
        // Search for bet validation terms
        if (preg_match('/(stake|bet|m[ií]n|m[aá]x|importe|limit|alert|valida)/i', $line)) {
            // Print line if it has JS or stake input references
            if (preg_match('/(input|val|id=|class=|function|const|let|var|stake|alert|error|swal|min|max)/i', $line)) {
                echo "$lineNum: " . trim($line) . "\n";
            }
        }
    }
}
