<?php
// api/health-check.php - Diagnostic probe for live deployment verification
header('Content-Type: application/json; charset=utf-8');

$expectedKey = 'bk_render_check_2026';
if (($_GET['key'] ?? '') !== $expectedKey) {
    http_response_code(403);
    echo json_encode(['status' => 'forbidden', 'message' => 'Invalid or missing diagnostic key.']);
    exit;
}

// 1. Load .env if present (same mechanism as config/db.php)
$envFile = __DIR__ . '/../.env';
$envLoaded = false;
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if ((strpos($val, '"') === 0 && substr($val, -1) === '"') || 
                (strpos($val, "'") === 0 && substr($val, -1) === "'")) {
                $val = substr($val, 1, -1);
            }
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            $envLoaded = true;
        }
    }
}

function getCfgVal($k, $default = '') {
    $v = getenv($k);
    if ($v !== false && $v !== '') return trim((string)$v);
    if (!empty($_ENV[$k])) return trim((string)$_ENV[$k]);
    if (!empty($_SERVER[$k])) return trim((string)$_SERVER[$k]);
    return $default;
}

$db_host = getCfgVal('DB_HOST', '127.0.0.1');
$db_port = getCfgVal('DB_PORT', '3306');
$db_name = getCfgVal('DB_NAME', 'bazarkinun_db');
$db_user = getCfgVal('DB_USER', 'root');
$db_pass = getCfgVal('DB_PASS', '');
$db_ssl  = getCfgVal('DB_SSL', '');

$possibleCAs = [
    '/etc/ssl/certs/ca-certificates.crt',
    '/etc/pki/tls/certs/ca-bundle.crt',
    'C:\\Program Files\\Git\\usr\\ssl\\certs\\ca-bundle.crt',
    'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt'
];
$caFound = null;
foreach ($possibleCAs as $ca) {
    if (file_exists($ca)) {
        $caFound = $ca;
        break;
    }
}

$diag = [
    'status' => 'probing',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'php_sapi' => php_sapi_name(),
    'env_file_exists' => file_exists($envFile),
    'env_vars_loaded_from_file' => $envLoaded,
    'detected_config' => [
        'DB_HOST' => $db_host,
        'DB_PORT' => $db_port,
        'DB_NAME' => $db_name,
        'DB_USER' => $db_user,
        'DB_PASS_CONFIGURED' => !empty($db_pass),
        'DB_PASS_LENGTH' => strlen($db_pass),
        'DB_SSL' => $db_ssl,
        'CA_FILE_FOUND' => $caFound,
    ],
    'db_connection' => null
];

try {
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ];
    if ($caFound && (strpos($db_host, 'tidbcloud.com') !== false || (int)$db_port === 4000 || $db_ssl === 'true')) {
        $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $caFound;
        $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    $testPdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $pdoOptions);
    $tables = $testPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $diag['status'] = 'healthy';
    $diag['db_connection'] = [
        'success' => true,
        'tables_count' => count($tables),
    ];
} catch (PDOException $e) {
    $diag['status'] = 'db_error';
    $diag['db_connection'] = [
        'success' => false,
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage()
    ];
}

echo json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
