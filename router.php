<?php
// router.php - Router for PHP built-in development server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Security: Block access to sensitive files and internal directories
if (preg_match('/(^\.|\.(env|sql|log|bak|config|md)$)/i', basename($uri)) || preg_match('#^/(database|scratch|\.git)/#i', $uri)) {
    http_response_code(403);
    die('403 Forbidden: Access Denied');
}

// If static file exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Routes based on .htaccess
$routes = [
    '/website'           => 'shop.php',
    '/shop'              => 'shop.php',
    '/cart'              => 'cart.php',
    '/write-list'        => 'write_list.php',
    '/upload-list'       => 'upload_list.php',
    '/profile'           => 'profile.php',
    '/login'             => 'login.php',
    '/signup'            => 'signup.php',
    '/api/store-data'    => 'api/store-data.php',
    '/api/submit-order'  => 'api/submit-order.php',
    '/api/customer-data' => 'api/customer-data.php',
    '/api/send-otp'      => 'api/send-otp.php',
    '/api/login-customer'=> 'api/login.php',
    '/api/create-customer'=> 'api/create-customer.php',
    '/api/auth'          => 'api/auth.php',
];

$trimmed = rtrim($uri, '/');
if (isset($routes[$trimmed])) {
    require __DIR__ . '/' . $routes[$trimmed];
    exit;
}

// Check if file with .php extension exists
if (file_exists(__DIR__ . $uri . '.php')) {
    require __DIR__ . $uri . '.php';
    exit;
}

// Default entry
if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/index.php';
    exit;
}

return false;
