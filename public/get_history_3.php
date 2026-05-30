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

$targetSteps = [1109, 1111, 1113];
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if ($data && in_array((int)$data['step_index'], $targetSteps)) {
        echo "=== STEP {$data['step_index']} ({$data['type']}) ===\n";
        if (isset($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $tc) {
                echo "Tool: {$tc['name']}\n";
                if (isset($tc['args']['ReplacementChunks'])) {
                    $chunks = json_decode($tc['args']['ReplacementChunks'], true);
                    if (!$chunks) {
                        // Sometimes it is already an array in JSON or a string
                        $chunks = $tc['args']['ReplacementChunks'];
                    }
                    if (is_array($chunks)) {
                        foreach ($chunks as $idx => $chunk) {
                            echo "--- Chunk #{$idx} ---\n";
                            echo "StartLine: {$chunk['StartLine']}, EndLine: {$chunk['EndLine']}\n";
                            echo "TargetContent:\n{$chunk['TargetContent']}\n";
                            echo "ReplacementContent:\n{$chunk['ReplacementContent']}\n";
                        }
                    } else {
                        echo "Chunks (raw): " . print_r($chunks, true) . "\n";
                    }
                } else {
                    echo "Args: " . json_encode($tc['args'], JSON_PRETTY_PRINT) . "\n";
                }
            }
        }
    }
}
fclose($handle);
