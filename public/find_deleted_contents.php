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

$filesToFind = ['check_triggers.php', 'check_last_tx.php', 'test_deposit_commission.php'];

while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if ($data) {
        $found = false;
        foreach ($filesToFind as $f) {
            if (strpos($line, $f) !== false) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "=== STEP {$data['step_index']} ({$data['type']}) ===\n";
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    $targetFile = $tc['args']['TargetFile'] ?? '';
                    foreach ($filesToFind as $f) {
                        if (strpos($targetFile, $f) !== false) {
                            echo "Tool: {$tc['name']} -> {$f}\n";
                            if (isset($tc['args']['CodeContent'])) {
                                echo "CodeContent:\n" . $tc['args']['CodeContent'] . "\n\n";
                            }
                            if (isset($tc['args']['ReplacementContent'])) {
                                echo "ReplacementContent:\n" . $tc['args']['ReplacementContent'] . "\n\n";
                            }
                        }
                    }
                }
            }
            // Check if there was a VIEW_FILE or read content that shows the original file before edit!
            if ($data['type'] === 'VIEW_FILE' || $data['type'] === 'READ_FILE') {
                echo "File content viewed:\n" . $data['content'] . "\n\n";
            }
        }
    }
}
fclose($handle);
