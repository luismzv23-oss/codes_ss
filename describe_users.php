<?php
define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
require __DIR__ . '/system/bootstrap.php';

$db = \Config\Database::connect();
$fields = $db->getFieldData('users');
foreach ($fields as $field) {
    echo "Field: {$field->name} | Type: {$field->type} | Max Length: {$field->max_length} | Nullable: " . ($field->nullable ? 'YES' : 'NO') . "\n";
}
