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
    if ($data && (int)$data['step_index'] === 5) {
        // Step 5 has the content of Checkout.php
        $content = $data['content'];
        $pos = strpos($content, 'chillerlan');
        if ($pos !== false) {
            echo "Found in step 5 content:\n";
            echo substr($content, $pos - 300, 800) . "\n";
        } else {
            echo "Not found in step 5 content.\n";
        }
        break;
    }
}
fclose($handle);
