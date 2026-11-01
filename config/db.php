<?php
// config/db.php
// Database Configuration and Connection Handler

// Load environment configuration from .env if present
(function() {
    $envFile = __DIR__ . '/../.env';
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
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
})();

$app_env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
if ($app_env === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Configure 30-Day Persistent Session for customers and staff
$sessionLifetime = 86400 * 30; // 30 Days in seconds
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
    ini_set('session.cookie_lifetime', (string)$sessionLifetime);
    $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$db_host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
$db_port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$db_name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'bazarkinun_db');
$db_user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? 'B@nR@y#257');

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Cloud & TiDB Serverless SSL Connection Support
$db_ssl = getenv('DB_SSL') ?: ($_ENV['DB_SSL'] ?? '');
if ($db_ssl === 'true' || strpos($db_host, 'tidbcloud.com') !== false || (int)$db_port === 4000) {
    $caBundle = '/etc/ssl/certs/ca-certificates.crt';
    if (file_exists($caBundle)) {
        $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $caBundle;
    }
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $pdoOptions);
    try {
        $pdo->exec("SET GLOBAL max_allowed_packet = 67108864;");
    } catch (Exception $exGlobal) {}
} catch (PDOException $e) {
    // If database doesn't exist yet, attempt connecting without dbname so setup can run
    try {
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, $pdoOptions);
    } catch (PDOException $ex) {
        error_log("Database connection failure: " . $ex->getMessage());
        if ($app_env === 'production' && php_sapi_name() !== 'cli') {
            http_response_code(500);
            die("We are experiencing temporary system maintenance. Please try again shortly.");
        } else {
            die("Database connection failure: " . $ex->getMessage());
        }
    }
}

// Helper JSON response function
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// CSRF Protection Helpers
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Authentication & Authorization Enforcement Helpers
function requirePortalAuth($allowedRoles = ['admin', 'manager', 'rider', 'staff', 'author', 'management', 'operation']) {
    $role = strtolower($_SESSION['admin_role'] ?? $_SESSION['manager_role'] ?? $_SESSION['staff_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
    $uid = $_SESSION['admin_id'] ?? $_SESSION['manager_id'] ?? $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? null;
    
    $allowedList = array_map('strtolower', (array)$allowedRoles);
    if (empty($uid) || empty($role) || !in_array($role, $allowedList)) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Unauthorized: Access denied. Active authorized staff or management session required.'
        ], 401);
    }
    return [
        'id' => $uid,
        'role' => $role,
        'name' => $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? $_SESSION['staff_name'] ?? $_SESSION['user_name'] ?? 'Staff User',
        'phone' => $_SESSION['admin_phone'] ?? $_SESSION['manager_phone'] ?? $_SESSION['staff_phone'] ?? $_SESSION['user_phone'] ?? ''
    ];
}

function requireCustomerAuth() {
    $cust = getLoggedInCustomer();
    if (!$cust || empty($cust['phone'])) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Unauthorized: Please log in to your customer account to perform this action.'
        ], 401);
    }
    return $cust;
}

// Granular Role-Based Access Control (RBAC) System
function isSuperAdmin(): bool {
    $role = strtolower($_SESSION['admin_role'] ?? $_SESSION['author_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
    return in_array($role, ['admin', 'super_admin', 'author']);
}

function getUserPermissions($pdoInstance = null, $forceRefresh = false): array {
    global $pdo;
    $db = $pdoInstance ?? $pdo;

    if (isSuperAdmin()) {
        return ['*'];
    }

    if (!$forceRefresh && isset($_SESSION['user_permissions']) && is_array($_SESSION['user_permissions'])) {
        return $_SESSION['user_permissions'];
    }

    $role = strtolower($_SESSION['admin_role'] ?? $_SESSION['manager_role'] ?? $_SESSION['staff_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? '');
    $uid = $_SESSION['admin_id'] ?? $_SESSION['manager_id'] ?? $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? null;

    if (empty($role) && empty($uid)) {
        return [];
    }

    $perms = [];
    if ($db) {
        try {
            // 1. Fetch from Centralized RBAC Tables (roles -> role_permission_map -> permissions)
            if (!empty($role)) {
                $stmt = $db->prepare("
                    SELECT p.slug, p.legacy_slug 
                    FROM role_permission_map rpm
                    JOIN roles r ON rpm.role_id = r.id
                    JOIN permissions p ON rpm.permission_id = p.id
                    WHERE r.role_key = ? AND p.status = 'active'
                ");
                $stmt->execute([$role]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    foreach ($rows as $r) {
                        $perms[] = $r['slug'];
                        if (!empty($r['legacy_slug'])) {
                            $perms[] = $r['legacy_slug'];
                        }
                    }
                } else {
                    // Fallback to legacy role_permissions table if rpm empty
                    $legacyStmt = $db->prepare("SELECT permissions FROM role_permissions WHERE role_key = ?");
                    $legacyStmt->execute([$role]);
                    $row = $legacyStmt->fetch();
                    if ($row && !empty($row['permissions'])) {
                        $decoded = json_decode($row['permissions'], true);
                        if (is_array($decoded)) {
                            $perms = array_merge($perms, $decoded);
                        }
                    }
                }
            }

            // 2. Fetch User-Specific Custom Permissions Overrides from `users.permissions`
            if ($uid) {
                $uStmt = $db->prepare("SELECT permissions FROM users WHERE id = ?");
                $uStmt->execute([$uid]);
                $uRow = $uStmt->fetch();
                if ($uRow && !empty($uRow['permissions'])) {
                    $uDecoded = json_decode($uRow['permissions'], true);
                    if (is_array($uDecoded)) {
                        foreach ($uDecoded as $up) {
                            $perms[] = $up;
                            // Check if up matches slug or legacy_slug in permissions
                            $mapStmt = $db->prepare("SELECT slug, legacy_slug FROM permissions WHERE slug = ? OR legacy_slug = ? LIMIT 1");
                            $mapStmt->execute([$up, $up]);
                            $mRow = $mapStmt->fetch(PDO::FETCH_ASSOC);
                            if ($mRow) {
                                if (!empty($mRow['slug'])) $perms[] = $mRow['slug'];
                                if (!empty($mRow['legacy_slug'])) $perms[] = $mRow['legacy_slug'];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {}
    }

    // 3. Composite Alias Expansion for 100% Backward Compatibility
    $expanded = $perms;
    if (in_array('manage_staff', $perms) || in_array('users.manage', $perms)) {
        $expanded = array_merge($expanded, ['view_staff', 'create_staff', 'edit_staff', 'toggle_staff_status', 'delete_staff', 'users.view', 'users.create', 'users.edit', 'users.status', 'users.delete']);
    }
    if (in_array('create_edit_products', $perms) || in_array('products.manage', $perms)) {
        $expanded = array_merge($expanded, ['create_products', 'edit_products', 'products.create', 'products.edit']);
    }
    if (in_array('broadcast_notices', $perms)) {
        $expanded = array_merge($expanded, ['manage_broadcast', 'portal.broadcast']);
    }

    $perms = array_values(array_unique(array_filter($expanded)));
    $_SESSION['user_permissions'] = $perms;
    return $perms;
}

function hasPermission($requiredPerm, $pdoInstance = null): bool {
    if (isSuperAdmin()) {
        return true;
    }
    $perms = getUserPermissions($pdoInstance);
    if (in_array('*', $perms, true)) {
        return true;
    }

    // If an array of acceptable permissions was passed, verify user has at least one
    if (is_array($requiredPerm)) {
        foreach ($requiredPerm as $rp) {
            if (in_array($rp, $perms, true)) return true;
        }
        return false;
    }

    return in_array($requiredPerm, $perms, true);
}

function requirePermission($requiredPerm, $pdoInstance = null) {
    if (!hasPermission($requiredPerm, $pdoInstance)) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
               || (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false)
               || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

        if ($isAjax) {
            jsonResponse([
                'status' => 'error',
                'message' => "Permission Denied: You do not have the required privilege [{$requiredPerm}]."
            ], 403);
        } else {
            header('Location: dashboard.php?tab=summary&error=permission_denied&perm=' . urlencode($requiredPerm));
            exit;
        }
    }
}

// Universal Order & Invoice HTML Sanitizer to prevent Stored XSS
function sanitizeOrderHtml($html) {
    if (empty($html) || !is_string($html)) return '';
    // Strip malicious tags
    $clean = preg_replace('/<(script|iframe|object|embed|applet|form|svg|link|meta|base)[^>]*>.*?<\/\1>/is', '', $html);
    $clean = preg_replace('/<(script|iframe|object|embed|applet|form|svg|link|meta|base)[^>]*>/is', '', $clean);
    // Remove all javascript event attributes like onclick, onerror, onload, onmouseover
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $clean);
    // Sanitize dangerous URI protocols
    $clean = preg_replace('/(href|action|src)\s*=\s*["\']?\s*javascript:[^"\'>]*/i', '$1="#"', $clean);
    return $clean;
}

// Helper to record system/admin/manager activities into audit_logs table
function logAuditActivity($pdo, $action, $details = '', $userName = null, $userRole = null) {
    try {
        if (!$userName) {
            $userName = $_SESSION['author_name'] ?? $_SESSION['manager_name'] ?? $_SESSION['staff_name'] ?? 'System / Admin';
        }
        if (!$userRole) {
            $userRole = $_SESSION['author_role'] ?? $_SESSION['role'] ?? 'admin';
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userName, $userRole, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently skip if table is missing or error
    }
}

// Universal Bangladeshi Phone Normalizer (Standard 11 digits: 01XXXXXXXXX)
function normalizeBdPhone($phone) {
    if ($phone === null || $phone === false) {
        return '';
    }
    $phone = trim((string)$phone);
    if ($phone === '') {
        return '';
    }

    // 1. Convert Bengali numerals (০-৯) to English numerals (0-9)
    $bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    $enDigits = ['0','1','2','3','4','5','6','7','8','9'];
    $phone = str_replace($bnDigits, $enDigits, $phone);

    // 2. Remove all non-numeric characters (dashes, spaces, plus, brackets)
    $digits = preg_replace('/\D/', '', $phone);
    if (empty($digits)) {
        return '';
    }

    // 3. Normalize international and local prefixes
    if (strpos($digits, '0088') === 0) {
        $digits = substr($digits, 4);
    }
    if (strlen($digits) === 13 && strpos($digits, '880') === 0) {
        $digits = substr($digits, 2);
    } elseif (strlen($digits) === 14 && strpos($digits, '0880') === 0) {
        $digits = substr($digits, 3);
    } elseif (strlen($digits) === 10 && preg_match('/^[1-9]/', $digits)) {
        $digits = '0' . $digits;
    }

    return $digits;
}

// Universal Bangladeshi Phone Validator (013-019, 11 digits)
function isValidBdPhone($phone) {
    $normalized = normalizeBdPhone($phone);
    return (bool)preg_match('/^01[3-9]\d{8}$/', $normalized);
}

// Universal Address Validator (Rejects dummy/placeholder map texts)
function isValidLocationAddress($address) {
    if ($address === null || $address === false) {
        return false;
    }
    $address = trim((string)$address);
    if (mb_strlen($address) < 3) {
        return false;
    }

    $lower = mb_strtolower($address);
    $invalidKeywords = [
        'not provided', 'unknown location', 'undefined', 'null', 
        'no address selected', 'please search or pinpoint', 'pinpoint your location',
        'কোনো ঠিকানা নির্বাচিত হয়নি', 'ঠিকানা নির্দিষ্ট নয়', 'address not specified',
        'ম্যাপে পিন করুন', 'লোকেশন সার্চ করুন', 'লোকেশন পিন', 'ঠিকানা নির্বাচন করুন',
        'নির্বাচিত হয়নি', 'ম্যাপে পিন', '??', '???'
    ];
    foreach ($invalidKeywords as $kw) {
        if (mb_strpos($lower, $kw) !== false) {
            return false;
        }
    }
    return true;
}

// Universal Dynamic SMS Gateway Dispatcher Helper
function sendSmsNotification($pdo, $phone, $message) {
    try {
        if (!$pdo || empty($phone) || empty($message)) {
            return ['success' => false, 'error' => 'Invalid phone or message'];
        }

        // Normalize Bangladeshi phone number to standard 11 digits
        $cleanPhone = normalizeBdPhone($phone);
        if (empty($cleanPhone)) {
            return ['success' => false, 'error' => 'Invalid phone format'];
        }

        // Fetch portal SMS settings
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM portal_settings WHERE setting_key LIKE 'sms_%'");
        $smsSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $enabled = ($smsSettings['sms_gateway_enabled'] ?? '0') === '1';
        $provider = $smsSettings['sms_gateway_provider'] ?? 'greenweb';
        $apiKey = trim($smsSettings['sms_gateway_api_key'] ?? '');
        $senderId = trim($smsSettings['sms_gateway_sender_id'] ?? '');

        // If gateway is disabled or no API key configured, run in safe simulation mode
        if (!$enabled || empty($apiKey)) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'SMS Gateway is in simulation mode (No API Key configured).',
                'phone' => $cleanPhone
            ];
        }

        // Standard 880 format for Bangladeshi SMS gateways
        $bdPhone = $cleanPhone;
        if (strlen($bdPhone) === 11 && substr($bdPhone, 0, 2) === '01') {
            $bdPhone = '88' . $bdPhone;
        }

        $resBody = '';
        $httpCode = 0;

        // 1. httpSMS (Android Phone Gateway - Free)
        if ($provider === 'httpsms') {
            $url = "https://api.httpsms.com/v1/messages/send";
            $fromClean = normalizeBdPhone($senderId);
            $fromPhone = !empty($fromClean) ? ('+88' . $fromClean) : trim($senderId);
            if (empty($fromPhone)) {
                return [
                    'success' => false,
                    'error' => 'httpSMS requires your Android phone SIM number in Sender ID (e.g. 017XXXXXXXX or +88017XXXXXXXX).',
                    'provider' => 'httpsms'
                ];
            }
            $toPhone = '+88' . $cleanPhone;
            $payload = json_encode([
                'content' => $message,
                'from'    => $fromPhone,
                'to'      => $toPhone
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            $verifySsl = (getenv('CURL_VERIFY_SSL') !== 'false');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $resBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $jsonRes = json_decode($resBody, true);
            $isSuccess = ($httpCode >= 200 && $httpCode < 300) && (!isset($jsonRes['status']) || $jsonRes['status'] !== 'error');
            $errMsg = null;
            if (!$isSuccess) {
                $errMsg = $jsonRes['message'] ?? $curlError ?: "HTTP {$httpCode} error from httpSMS";
            }

            return [
                'success' => $isSuccess,
                'response' => $resBody,
                'error' => $errMsg,
                'provider' => 'httpsms',
                'phone' => $toPhone
            ];
        }
        // 2. Greenweb BD Gateway
        elseif ($provider === 'greenweb') {
            $url = "http://api.greenweb.com.bd/api.php";
            $data = [
                'token' => $apiKey,
                'to' => $bdPhone,
                'message' => $message
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
        // 2. BulkSMSBD Gateway
        elseif ($provider === 'bulksmsbd') {
            $url = "http://bulksmsbd.net/api/smsapi";
            $data = [
                'api_key' => $apiKey,
                'type' => 'text',
                'number' => $bdPhone,
                'senderid' => $senderId ?: '8809612443880',
                'message' => $message
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
        // 3. SSL Wireless Gateway
        elseif ($provider === 'sslwireless') {
            $url = "https://smsplus.sslwireless.com/api/v3/send-sms";
            $data = [
                'api_token' => $apiKey,
                'sid' => $senderId,
                'msisdn' => $bdPhone,
                'sms' => $message,
                'csms_id' => uniqid('BK')
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $verifySsl = (getenv('CURL_VERIFY_SSL') !== 'false');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
        // 4. Custom / Generic HTTP GET/POST Webhook
        elseif ($provider === 'custom') {
            $customUrl = $smsSettings['sms_gateway_custom_url'] ?? '';
            if (!empty($customUrl)) {
                $targetUrl = str_replace(
                    ['{phone}', '{message}', '{api_key}', '{sender_id}'],
                    [urlencode($bdPhone), urlencode($message), urlencode($apiKey), urlencode($senderId)],
                    $customUrl
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $targetUrl);
                $verifySsl = (getenv('CURL_VERIFY_SSL') !== 'false');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $resBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }
        }

        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'response' => $resBody,
            'provider' => $provider,
            'phone' => $bdPhone
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Get logged in customer or null
function getLoggedInCustomer($pdo = null) {
    $uid = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['manager_id'] ?? $_SESSION['staff_id'] ?? null;
    if (!empty($uid)) {
        $photo = $_SESSION['customer_photo'] ?? $_SESSION['user_photo'] ?? '';
        if (empty($photo) && $pdo) {
            try {
                $phone = $_SESSION['customer_phone'] ?? $_SESSION['user_phone'] ?? '';
                $st = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ? OR phone = ? LIMIT 1");
                $st->execute([$uid, $phone]);
                $photo = $st->fetchColumn() ?: '';
                if ($photo) {
                    $_SESSION['customer_photo'] = $photo;
                }
            } catch (Exception $e) {}
        }
        return [
            'id' => $uid,
            'name' => $_SESSION['customer_name'] ?? $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? $_SESSION['staff_name'] ?? 'Customer',
            'phone' => $_SESSION['customer_phone'] ?? $_SESSION['user_phone'] ?? $_SESSION['admin_phone'] ?? $_SESSION['manager_phone'] ?? $_SESSION['staff_phone'] ?? '',
            'profile_photo' => $photo,
        ];
    }
    return null;
}

// Universal Sequential Code Generator (Format: PREFIX-phone6-name2-seq)
function generateSequentialCode($pdo, $type, $phone, $name = 'User', $userId = null) {
    $cleanPhone = preg_replace('/\D/', '', (string)$phone);
    $phone6 = strlen($cleanPhone) >= 6 ? substr($cleanPhone, -6) : str_pad($cleanPhone, 6, '0', STR_PAD_LEFT);
    $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)$name));
    $name2 = strlen($cleanName) >= 2 ? substr($cleanName, 0, 2) : str_pad($cleanName, 2, 'X');
    $userSuffix = "{$phone6}-{$name2}";
    $prefixKey = "{$type}-{$userSuffix}";

    $maxSeq = 0;

    if ($type === 'LST') {
        // ONLY check custom_lists for existing LST codes matching this customer
        $stmt1 = $pdo->prepare("
            SELECT list_code, title 
            FROM custom_lists 
            WHERE (user_id = ? OR customer_phone = ? OR customer_phone = ? OR list_code LIKE ? OR title LIKE ?) 
              AND (list_code LIKE 'LST-%' OR title LIKE 'LST-%')
        ");
        $stmt1->execute([$userId, $phone, $cleanPhone, "LST-{$userSuffix}-%", "LST-{$userSuffix}-%"]);
        $rows1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows1 as $r) {
            foreach ([$r['list_code'], $r['title']] as $val) {
                if (strpos((string)$val, 'LST-') === 0 && preg_match('/-(\d+)$/', (string)$val, $m)) {
                    $maxSeq = max($maxSeq, (int)$m[1]);
                }
            }
        }
        $nextSeq = $maxSeq + 1;
        return "{$prefixKey}-{$nextSeq}";
    } elseif ($type === 'ORD') {
        // ONLY check orders for existing ORD- order numbers matching this customer
        $stmt = $pdo->prepare("
            SELECT order_number 
            FROM orders 
            WHERE (user_id = ? OR customer_phone = ? OR customer_phone = ? OR order_number LIKE ?) 
              AND order_number LIKE 'ORD-%'
        ");
        $stmt->execute([$userId, $phone, $cleanPhone, "ORD-{$userSuffix}-%"]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if (strpos((string)$r['order_number'], 'ORD-') === 0 && preg_match('/-(\d+)$/', (string)$r['order_number'], $m)) {
                $maxSeq = max($maxSeq, (int)$m[1]);
            }
        }
        $nextSeq = $maxSeq + 1;
        return "{$prefixKey}-{$nextSeq}";
    } elseif ($type === 'INV') {
        // ONLY check orders for existing INV- invoice numbers matching this customer
        $stmt = $pdo->prepare("
            SELECT invoice_number, order_number 
            FROM orders 
            WHERE (user_id = ? OR customer_phone = ? OR customer_phone = ? OR invoice_number LIKE ? OR order_number LIKE ?) 
              AND (invoice_number LIKE 'INV-%' OR order_number LIKE 'INV-%')
        ");
        $stmt->execute([$userId, $phone, $cleanPhone, "INV-{$userSuffix}-%", "INV-{$userSuffix}-%"]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            foreach ([$r['invoice_number'], $r['order_number']] as $val) {
                if (strpos((string)$val, 'INV-') === 0 && preg_match('/-(\d+)$/', (string)$val, $m)) {
                    $maxSeq = max($maxSeq, (int)$m[1]);
                }
            }
        }
        $nextSeq = $maxSeq + 1;
        return "{$prefixKey}-{$nextSeq}";
    }

    return "{$prefixKey}-1";
}

// Location and Zone Resolution Helper
function resolveLocationInfo($pdo, $locationStr, $givenDistrict = '', $givenThana = '', $givenZoneId = null) {
    $district = trim((string)$givenDistrict);
    $thana = trim((string)$givenThana);

    // If given thana is actually a full address string (contains commas or is too long), reset it
    if (strpos($thana, ',') !== false || mb_strlen($thana) > 40) {
        if (empty($locationStr)) {
            $locationStr = $thana;
        }
        $thana = '';
    }

    $zoneId = $givenZoneId ? intval($givenZoneId) : null;
    $locClean = strtolower((string)$locationStr);

    // 0. Match from hub_sub_areas (Matches Sub-areas/Sectors like "Sector 4", "Bhatulia", "Kazipara", etc.)
    if (!$zoneId || empty($thana) || $thana === 'All Police Stations' || empty($district)) {
        try {
            static $cachedSubAreas = null;
            if ($cachedSubAreas === null) {
                $cachedSubAreas = $pdo->query("SELECT zone_id, district, police_station, sub_area FROM hub_sub_areas ORDER BY CHAR_LENGTH(sub_area) DESC")->fetchAll(PDO::FETCH_ASSOC);
            }
            foreach ($cachedSubAreas as $sa) {
                $sub = strtolower($sa['sub_area']);
                if (!empty($sub) && stripos($locClean, $sub) !== false) {
                    if (empty($thana) || $thana === 'All Police Stations') $thana = $sa['police_station'];
                    if (empty($district) || $district === 'All Bangladesh') $district = $sa['district'];
                    if (!$zoneId || $zoneId <= 1) $zoneId = (int)$sa['zone_id'];
                    break;
                }
            }
        } catch (Exception $e) {}
    }

    // 1. Match from pricing_zones
    if (!$zoneId || empty($thana) || $thana === 'All Police Stations' || empty($district)) {
        try {
            $zones = $pdo->query("SELECT id, zone_name, thana_list, district_list FROM pricing_zones WHERE status = 1")->fetchAll();
            foreach ($zones as $z) {
                $thList = array_map('trim', explode(',', strtolower($z['thana_list'] ?? '')));
                foreach ($thList as $th) {
                    if (!empty($th) && stripos($locClean, $th) !== false) {
                        if (!$zoneId || $zoneId <= 1) $zoneId = (int)$z['id'];
                        if (empty($thana) || $thana === 'All Police Stations') $thana = ucwords($th);
                        break;
                    }
                }
                $distList = array_map('trim', explode(',', strtolower($z['district_list'] ?? '')));
                foreach ($distList as $dist) {
                    if (!empty($dist) && stripos($locClean, $dist) !== false) {
                        if (empty($district) || $district === 'All Bangladesh') $district = ucwords($dist);
                        if (!$zoneId || $zoneId <= 1) $zoneId = (int)$z['id'];
                        break;
                    }
                }
            }
        } catch(Exception $e) {}
    }

    // 2. Match from product_pricing existing districts & thanas
    if (empty($district) || empty($thana)) {
        try {
            $pricingLocs = $pdo->query("SELECT DISTINCT district, police_station FROM product_pricing WHERE is_active = 1")->fetchAll();
            foreach ($pricingLocs as $pl) {
                if (empty($district) && !empty($pl['district']) && $pl['district'] !== 'All Bangladesh') {
                    if (stripos($locClean, strtolower($pl['district'])) !== false) {
                        $district = $pl['district'];
                    }
                }
                if (empty($thana) && !empty($pl['police_station']) && $pl['police_station'] !== 'All Police Stations') {
                    if (stripos($locClean, strtolower($pl['police_station'])) !== false) {
                        $thana = $pl['police_station'];
                    }
                }
            }
        } catch(Exception $e) {}
    }

    // 3. Match from standard 64 Bangladesh districts list
    if (empty($district) || $district === 'All Bangladesh') {
        $bdDistricts = [
            'Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail',
            'Bagerhat', 'Chuadanga', 'Jashore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura', 'Meherpur', 'Narail', 'Satkhira',
            'Bogura', 'Joypurhat', 'Naogaon', 'Natore', 'Chapai Nawabganj', 'Pabna', 'Rajshahi', 'Sirajganj',
            'Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon',
            'Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet',
            'Barguna', 'Barishal', 'Bhola', 'Jhalokathi', 'Patuakhali', 'Pirojpur',
            'Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', 'Cox\'s Bazar', 'Cumilla', 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati',
            'Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'
        ];
        foreach ($bdDistricts as $d) {
            if (stripos($locClean, strtolower($d)) !== false) {
                $district = $d;
                break;
            }
        }
    }

    return [
        'zone_id' => $zoneId ?: 1,
        'district' => !empty($district) ? $district : 'All Bangladesh',
        'police_station' => !empty($thana) ? $thana : 'All Police Stations'
    ];
}

// Authoritative Location-specific Product Pricing Helper
function getProductPriceForLocation($pdo, $productId, $district = 'All Bangladesh', $policeStation = 'All Police Stations', $zoneId = 1, $batchId = null) {
    try {
        $priceStmt = $pdo->prepare("
            SELECT cost_price, dp_price, mrp_price, selling_price, is_active 
            FROM product_pricing 
            WHERE product_id = ? 
              AND (
                  (district = ? AND police_station = ?) OR
                  (district = ? AND (police_station = 'All Police Stations' OR police_station = '' OR police_station IS NULL)) OR
                  (zone_id = ? AND zone_id > 1) OR
                  (district = 'All Bangladesh') OR
                  ((district = '' OR district IS NULL) AND (zone_id = 1 OR zone_id IS NULL))
              )
            ORDER BY 
                (CASE WHEN district = ? AND police_station = ? AND district != 'All Bangladesh' AND district != '' THEN 100 ELSE 0 END) DESC,
                (CASE WHEN district = ? AND district != 'All Bangladesh' AND district != '' THEN 80 ELSE 0 END) DESC,
                (CASE WHEN zone_id = ? AND zone_id > 1 THEN 60 ELSE 0 END) DESC,
                (CASE WHEN batch_id = ? AND batch_id IS NOT NULL THEN 40 ELSE 0 END) DESC,
                (CASE WHEN district = 'All Bangladesh' THEN 20 ELSE 0 END) DESC,
                id DESC 
            LIMIT 1
        ");
        $priceStmt->execute([
            $productId,
            $district, $policeStation,
            $district,
            $zoneId,
            $district, $policeStation,
            $district,
            $zoneId,
            $batchId
        ]);
        $pricing = $priceStmt->fetch();
        if ($pricing) {
            return [
                'selling_price' => floatval($pricing['selling_price']),
                'mrp_price' => floatval($pricing['mrp_price']),
                'is_active' => (isset($pricing['is_active']) && (string)$pricing['is_active'] === '0') ? 0 : 1
            ];
        }
    } catch (Exception $e) {}

    // Fallback to products table base price
    try {
        $pStmt = $pdo->prepare("SELECT price, old_price, in_stock FROM products WHERE id = ?");
        $pStmt->execute([$productId]);
        $p = $pStmt->fetch();
        if ($p) {
            return [
                'selling_price' => floatval($p['price']),
                'mrp_price' => $p['old_price'] ? floatval($p['old_price']) : floatval($p['price']),
                'is_active' => intval($p['in_stock'])
            ];
        }
    } catch (Exception $e) {}

    return null;
}

// Authoritative Location-specific Product Stock Helper
function getProductStockForLocation($pdo, $productId, $district = 'All Bangladesh', $policeStation = 'All Police Stations', $zoneId = 1, $batchId = null) {
    try {
        $stockStmt = $pdo->prepare("
            SELECT current_stock, low_stock_threshold, district, police_station 
            FROM product_stocks 
            WHERE product_id = ? 
              AND (
                  (district = ? AND police_station = ?) OR
                  (district = ? AND (police_station = 'All Police Stations' OR police_station = '' OR police_station IS NULL)) OR
                  (zone_id = ? AND zone_id > 1) OR
                  (district = 'All Bangladesh') OR
                  ((district = '' OR district IS NULL) AND (zone_id = 1 OR zone_id IS NULL))
              )
            ORDER BY 
                (CASE WHEN district = ? AND police_station = ? AND district != 'All Bangladesh' AND district != '' THEN 100 ELSE 0 END) DESC,
                (CASE WHEN district = ? AND district != 'All Bangladesh' AND district != '' THEN 80 ELSE 0 END) DESC,
                (CASE WHEN zone_id = ? AND zone_id > 1 THEN 60 ELSE 0 END) DESC,
                (CASE WHEN batch_id = ? AND batch_id IS NOT NULL THEN 40 ELSE 0 END) DESC,
                (CASE WHEN district = 'All Bangladesh' THEN 20 ELSE 0 END) DESC,
                id DESC 
            LIMIT 1
        ");
        $stockStmt->execute([
            $productId,
            $district, $policeStation,
            $district,
            $zoneId,
            $district, $policeStation,
            $district,
            $zoneId,
            $batchId
        ]);
        $stock = $stockStmt->fetch();
        if ($stock) {
            return [
                'current_stock' => floatval($stock['current_stock']),
                'low_stock_threshold' => floatval($stock['low_stock_threshold']),
                'district' => $stock['district'],
                'police_station' => $stock['police_station']
            ];
        }
    } catch (Exception $e) {}

    // Fallback to products table stock
    try {
        $pStmt = $pdo->prepare("SELECT stock, in_stock FROM products WHERE id = ?");
        $pStmt->execute([$productId]);
        $p = $pStmt->fetch();
        if ($p) {
            return [
                'current_stock' => floatval($p['stock']),
                'low_stock_threshold' => 5.0,
                'district' => 'All Bangladesh',
                'police_station' => 'All Police Stations'
            ];
        }
    } catch (Exception $e) {}

    return null;
}
