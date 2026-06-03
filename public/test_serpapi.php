<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = Config\Services::codeigniter();
// 3. FORZAR LA CARGA DEL ARCHIVO .ENV
// Buscamos el archivo .env una carpeta arriba de /public
if (file_exists(FCPATH . '../.env')) {
    $dotenv = new \CodeIgniter\Config\Dotenv(FCPATH . '../');
    $dotenv->load();
}
define('CodeIgniter\ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'development');
$app->initialize();


//$serpApiKey = getenv('SERPAPI_KEY');
// Intentamos con la función global de CI4, y si no, buscamos en el array $_ENV
$serpApiKey = function_exists('env') ? env('SERPAPI_KEY') : ($_ENV['SERPAPI_KEY'] ?? $_SERVER['SERPAPI_KEY'] ?? null);

if (!$serpApiKey) {
    die("No SERPAPI_KEY found in .env\n");
}

$query = urlencode("Marruecos vs Madagascar resultado");
$url = "https://serpapi.com/search.json?engine=google&q={$query}&api_key={$serpApiKey}";

$client = \Config\Services::curlrequest();
$response = $client->request('GET', $url, [
    'http_errors' => false,
    'timeout' => 10
]);

file_put_contents(FCPATH . 'serpapi_result.json', $response->getBody());
echo "Saved to serpapi_result.json";
