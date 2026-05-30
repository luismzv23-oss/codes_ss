<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();
if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}
$app = Config\Services::codeigniter();
$app->initialize();

$userModel = new \App\Models\UserModel();

// Clean up any test users first
$userModel->where('username', 'testuser18')->delete(null, true);
$userModel->where('username', 'testuserunder18')->delete(null, true);

// Helper to clear a single shared service from CodeIgniter's service registry
function clearSharedService($name) {
    try {
        $ref = new \ReflectionClass('CodeIgniter\Config\BaseService');
        $prop = $ref->getProperty('instances');
        $prop->setAccessible(true);
        $instances = $prop->getValue();
        if (isset($instances[$name])) {
            unset($instances[$name]);
        }
        $prop->setValue(null, $instances);
    } catch (\Throwable $t) {
        // Fallback
    }
}

// Helper function to mock POST and run registerAction
function runRegister($postData) {
    $_POST = $postData;
    $_REQUEST = $postData;
    
    // Clear request and validation shared instances
    clearSharedService('request');
    clearSharedService('validation');
    
    // Create new Request object
    $request = new \CodeIgniter\HTTP\IncomingRequest(
        config('App'),
        new \CodeIgniter\HTTP\URI('http://localhost:8080/auth/registerAction'),
        'PHP://input',
        new \CodeIgniter\HTTP\UserAgent()
    );
    $request->setGlobal('post', $postData);
    $request->setHeader('HX-Request', 'true');
    $request->setHeader('X-Requested-With', 'XMLHttpRequest');
    
    \Config\Services::injectMock('request', $request);
    
    $controller = new \App\Controllers\Auth();
    $controller->initController(
        $request,
        \Config\Services::response(),
        \Config\Services::logger()
    );
    
    // Run registerAction
    $res = $controller->registerAction();
    return $res;
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST 1: Under 18 years old (Should fail) ===\n";
$dob_under18 = date('Y-m-d', strtotime('-17 years')); // 17 years ago
$post_under18 = [
    'username' => 'testuserunder18',
    'email' => 'under18@example.com',
    'phone_country' => '54',
    'phone_area' => '11',
    'phone_number' => '12345678',
    'country' => 'AR',
    'document_number' => '35123456',
    'birthdate' => $dob_under18,
    'password' => 'securePassword123',
    'password_confirm' => 'securePassword123',
    'privacy_policy' => '1'
];

$res1 = runRegister($post_under18);
// Read validation errors
$validation = \Config\Services::validation();
if ($validation->hasError('birthdate')) {
    echo "SUCCESS: Under 18 validation failed as expected. Error: " . $validation->getError('birthdate') . "\n";
} else {
    echo "FAILED: Under 18 validation did not trigger. Errors:\n";
    print_r($validation->getErrors());
}

echo "\n=== TEST 2: Missing privacy policy (Should fail) ===\n";
$dob_over18 = date('Y-m-d', strtotime('-25 years')); // 25 years ago
$post_no_policy = [
    'username' => 'testuser18',
    'email' => 'over18@example.com',
    'phone_country' => '54',
    'phone_area' => '11',
    'phone_number' => '12345678',
    'country' => 'AR',
    'document_number' => '35123456',
    'birthdate' => $dob_over18,
    'password' => 'securePassword123',
    'password_confirm' => 'securePassword123',
    // 'privacy_policy' is missing
];

$res2 = runRegister($post_no_policy);
$validation = \Config\Services::validation();
if ($validation->hasError('privacy_policy')) {
    echo "SUCCESS: Privacy policy validation failed as expected. Error: " . $validation->getError('privacy_policy') . "\n";
} else {
    echo "FAILED: Privacy policy validation did not trigger. Errors:\n";
    print_r($validation->getErrors());
}

echo "\n=== TEST 3: Successful Registration DNI (Should succeed) ===\n";
$post_success_dni = [
    'username' => 'testuser18',
    'email' => 'over18@example.com',
    'phone_country' => '54',
    'phone_area' => '11',
    'phone_number' => '12345678',
    'country' => 'AR',
    'document_number' => '35123456', // 8 digits -> DNI
    'birthdate' => $dob_over18,
    'password' => 'securePassword123',
    'password_confirm' => 'securePassword123',
    'privacy_policy' => '1'
];

$res3 = runRegister($post_success_dni);
$registeredUser = $userModel->where('username', 'testuser18')->first();
if ($registeredUser) {
    echo "SUCCESS: User registered successfully in DB.\n";
    echo "Phone combined: " . $registeredUser['phone'] . " (Expected: +54 11 12345678)\n";
    echo "Document Type: " . $registeredUser['document_type'] . " (Expected: DNI)\n";
    echo "Document Number: " . $registeredUser['document_number'] . " (Expected: 35123456)\n";
    echo "Country: " . $registeredUser['country'] . " (Expected: AR)\n";
    echo "Birthdate: " . $registeredUser['birthdate'] . " (Expected: " . $dob_over18 . ")\n";
    echo "KYC Status: " . $registeredUser['kyc_status'] . " (Expected: approved)\n";
} else {
    echo "FAILED: User was not inserted in database. Errors:\n";
    $validation = \Config\Services::validation();
    print_r($validation->getErrors());
}

echo "\n=== TEST 4: Successful Registration CUIT (Should succeed) ===\n";
// Clean previous
$userModel->where('username', 'testuser18')->delete(null, true);

$post_success_cuit = [
    'username' => 'testuser18',
    'email' => 'over18@example.com',
    'phone_country' => '54',
    'phone_area' => '11',
    'phone_number' => '12345678',
    'country' => 'UY', // Uruguay
    'document_number' => '20351234569', // 11 digits -> CUIT
    'birthdate' => $dob_over18,
    'password' => 'securePassword123',
    'password_confirm' => 'securePassword123',
    'privacy_policy' => '1'
];

$res4 = runRegister($post_success_cuit);
$registeredUser2 = $userModel->where('username', 'testuser18')->first();
if ($registeredUser2) {
    echo "SUCCESS: CUIT User registered successfully in DB.\n";
    echo "Phone combined: " . $registeredUser2['phone'] . " (Expected: +54 11 12345678)\n";
    echo "Document Type: " . $registeredUser2['document_type'] . " (Expected: CUIT)\n";
    echo "Document Number: " . $registeredUser2['document_number'] . " (Expected: 20351234569)\n";
    echo "Country: " . $registeredUser2['country'] . " (Expected: UY)\n";
} else {
    echo "FAILED: CUIT User was not inserted in database. Errors:\n";
    $validation = \Config\Services::validation();
    print_r($validation->getErrors());
}

// Clean up
$userModel->where('username', 'testuser18')->delete(null, true);
$userModel->where('username', 'testuserunder18')->delete(null, true);
