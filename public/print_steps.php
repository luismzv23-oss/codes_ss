<?php
$logFile = 'C:\\Users\\luism\\.gemini\\antigravity-ide\\brain\\91453e3f-4a02-4987-b78a-063bd7807882\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logFile)) {
    echo "File not found!";
    exit;
}

$handle = fopen($logFile, 'r');
if (!$handle) {
    echo "Cannot open file!";
    exit;
}

while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if ($data) {
        echo "Step {$data['step_index']}: {$data['type']} (source: {$data['source']})\n";
    }
}
fclose($handle);
