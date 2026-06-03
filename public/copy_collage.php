<?php
$src = "C:\\Users\\luism\\.gemini\\antigravity-ide\\brain\\cf4d8f7a-6493-419f-9dee-8a70a0d87b23\\worldcup2026_collage_1780453523921.png";
$dest = __DIR__ . "/assets/images/worldcup_collage.png";

$dir = dirname($dest);
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

if (!file_exists($src)) {
    die("Source file not found at: $src\n");
}

if (copy($src, $dest)) {
    echo "SUCCESS: Copied $src to $dest\n";
} else {
    echo "ERROR: Failed to copy\n";
}
