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
    if (strpos($line, 'check_triggers.php') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            echo "=== STEP {$data['step_index']} ({$data['type']}) ===\n";
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    echo "Tool: {$tc['name']}\n";
                    if (isset($tc['args']['CodeContent'])) {
                        echo "CodeContent:\n" . $tc['args']['CodeContent'] . "\n\n";
                    } else if (isset($tc['args']['ReplacementContent'])) {
                        echo "ReplacementContent:\n" . $tc['args']['ReplacementContent'] . "\n\n";
                    } else {
                        echo "Args: " . json_encode($tc['args'], JSON_PRETTY_PRINT) . "\n\n";
                    }
                }
            }
        }
    }
}
fclose($handle);
