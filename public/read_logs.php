<?php
header('Content-Type: text/plain; charset=utf-8');
$logPath = 'C:\\Users\\luism\\.gemini\\antigravity-ide\\brain\\91453e3f-4a02-4987-b78a-063bd7807882\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logPath)) {
    echo "Log file does not exist at: $logPath\n";
    exit;
}

$handle = fopen($logPath, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, 'check_triggers.php') !== false || strpos($line, 'check_last_tx.php') !== false) {
            echo "--- Match ---\n";
            echo substr($line, 0, 1000) . "...\n";
            // If the line contains tool call or file contents, let's print it or parse it
            $data = json_decode($line, true);
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    if (isset($tc['args']['TargetFile']) && (strpos($tc['args']['TargetFile'], 'check_triggers.php') !== false || strpos($tc['args']['TargetFile'], 'check_last_tx.php') !== false)) {
                        echo "TOOL CALL ARGS:\n";
                        print_r($tc['args']);
                    }
                }
            }
            if (isset($data['content']) && (strpos($data['content'], 'check_triggers.php') !== false || strpos($data['content'], 'check_last_tx.php') !== false)) {
                echo "CONTENT SNIPPET:\n";
                echo substr($data['content'], 0, 2000) . "\n";
            }
        }
    }
    fclose($handle);
} else {
    echo "Could not open log file.\n";
}
