<?php
// api/auth.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// Helper functions for persistent OTP management
function getDbOtpRecord($pdo, $phone) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE phone = ? AND expires_at > NOW() AND attempts < 5 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function recordDbOtpAttempt($pdo, $recordId) {
    try {
        $stmt = $pdo->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$recordId]);
    } catch (Exception $e) {}
}

function clearDbOtp($pdo, $phone) {
    try {
        $stmt = $pdo->prepare("DELETE FROM otp_verifications WHERE phone = ?");
        $stmt->execute([$phone]);
    } catch (Exception $e) {}
}

// 1. Send OTP
if ($action === 'send_otp' || strpos($_SERVER['REQUEST_URI'], 'send_otp') !== false || strpos($_SERVER['REQUEST_URI'], 'send-otp') !== false) {
    $rawPhone = trim($input['phone'] ?? '');
    $phone = normalizeBdPhone($rawPhone);
    $type = trim($input['type'] ?? 'login'); // 'login' or 'register'

    if (empty($phone)) {
        jsonResponse(['status' => 'error', 'message' => 'Phone number is required'], 400);
    }
    if (!isValidBdPhone($phone)) {
        jsonResponse(['status' => 'error', 'message' => 'অনুগ্রহ করে সঠিক ১১-সংখ্যার বাংলাদেশি মোবাইল নম্বর দিন (যেমন: 017XXXXXXXX)।'], 400);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $userExists = $stmt->fetchColumn();

    if ($type === 'login' && !$userExists) {
        jsonResponse([
            'status' => 'error',
            'not_registered' => true,
            'message' => 'এই ফোন নম্বর দিয়ে কোনো অ্যাকাউন্ট নেই। অনুগ্রহ করে প্রথমে সাইন আপ করুন।'
        ], 404);
    }

    if ($type === 'register' && $userExists) {
        jsonResponse([
            'status' => 'error',
            'already_registered' => true,
            'message' => 'এই ফোন নম্বর দিয়ে ইতোমধ্যে অ্যাকাউন্ট রয়েছে। অনুগ্রহ করে লগইন করুন।'
        ], 400);
    }
    
    // Persistent Rate-limiting: prevent spamming SMS within 60 seconds and enforce hourly limits
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $now = time();
    $nowStr = date('Y-m-d H:i:s', $now);
    $sixtySecAgo = date('Y-m-d H:i:s', $now - 60);
    $oneHourAgo = date('Y-m-d H:i:s', $now - 3600);
    $fifteenMinsAgo = date('Y-m-d H:i:s', $now - 900);

    // 1. 60-Second Cooldown Check (Phone Level - Session + Database)
    $lastSentSession = $_SESSION['otp_sent_time_' . $phone] ?? 0;
    if (($now - $lastSentSession) < 60) {
        $waitSec = 60 - ($now - $lastSentSession);
        jsonResponse(['status' => 'error', 'message' => "অনুগ্রহ করে {$waitSec} সেকেন্ড পর পুনরায় চেষ্টা করুন।"], 429);
    }

    try {
        $cooldownStmt = $pdo->prepare("SELECT last_sent_at FROM otp_verifications WHERE phone = ? AND last_sent_at > ? ORDER BY id DESC LIMIT 1");
        $cooldownStmt->execute([$phone, $sixtySecAgo]);
        $recentRecord = $cooldownStmt->fetch(PDO::FETCH_ASSOC);
        if ($recentRecord) {
            $diff = $now - strtotime($recentRecord['last_sent_at']);
            $waitSec = max(1, 60 - $diff);
            jsonResponse(['status' => 'error', 'message' => "অনুগ্রহ করে {$waitSec} সেকেন্ড পর পুনরায় চেষ্টা করুন।"], 429);
        }

        // 2. Hourly Limit per Phone (Max 5 OTP requests per hour to protect SMS credits)
        $hourlyPhoneStmt = $pdo->prepare("SELECT COUNT(*) FROM otp_verifications WHERE phone = ? AND created_at > ?");
        $hourlyPhoneStmt->execute([$phone, $oneHourAgo]);
        if (intval($hourlyPhoneStmt->fetchColumn()) >= 5) {
            jsonResponse(['status' => 'error', 'message' => 'এই নম্বরে ১ ঘণ্টার ওটিপি অনুরোধের সীমা অতিক্রম করেছে। অনুগ্রহ করে পরে চেষ্টা করুন।'], 429);
        }

        // 3. IP-level Throttle (Max 15 OTP requests per 15 mins across all numbers)
        $ipLimitStmt = $pdo->prepare("SELECT COUNT(*) FROM otp_verifications WHERE ip_address = ? AND created_at > ?");
        $ipLimitStmt->execute([$clientIp, $fifteenMinsAgo]);
        if (intval($ipLimitStmt->fetchColumn()) >= 15) {
            jsonResponse(['status' => 'error', 'message' => 'আপনার নেটওয়ার্ক থেকে অস্বাভাবিক ওটিপি অনুরোধ করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ অপেক্ষা করুন।'], 429);
        }
    } catch (Exception $e) {}

    // Generate secure 4-digit OTP with 5-minute expiration
    $otp = strval(random_int(1000, 9999)); 
    $expiresAt = $now + 300;
    $expiresAtStr = date('Y-m-d H:i:s', $expiresAt);

    try {
        $insOtp = $pdo->prepare("INSERT INTO otp_verifications (phone, ip_address, otp_code, attempts, last_sent_at, expires_at, created_at) VALUES (?, ?, ?, 0, ?, ?, ?)");
        $insOtp->execute([$phone, $clientIp, $otp, $nowStr, $expiresAtStr, $nowStr]);
    } catch (Exception $e) {}

    $_SESSION['temp_otp_' . $phone] = [
        'code' => $otp,
        'expires_at' => $expiresAt,
        'attempts' => 0
    ];
    $_SESSION['otp_sent_time_' . $phone] = $now;

    $smsMsg = "Your BazarKinun.com verification code is: {$otp}. Please do not share this OTP with anyone.";
    $smsRes = sendSmsNotification($pdo, $phone, $smsMsg);

    $respData = [
        'status' => 'success',
        'message' => 'OTP sent successfully to ' . $phone,
    ];

    // Only expose OTP in response if running on local development machine
    $isLocalHost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000']);
    if (!empty($smsRes['simulated']) && $isLocalHost) {
        $respData['otp'] = $otp;
        $respData['dev_mode'] = true;
    }

    jsonResponse($respData);
}

// 2. Login Customer
if ($action === 'login' || strpos($_SERVER['REQUEST_URI'], 'login_customer') !== false || strpos($_SERVER['REQUEST_URI'], 'login') !== false) {
    $rawPhone = trim($input['phone'] ?? '');
    $phone = normalizeBdPhone($rawPhone);
    $otp = trim($input['otp'] ?? '');

    if (empty($phone) || empty($otp)) {
        jsonResponse(['status' => 'error', 'message' => 'Phone and OTP are required'], 400);
    }

    // Accept 4-digit OTP code
    if (!preg_match('/^\d{4}$/', $otp)) {
        jsonResponse(['status' => 'error', 'message' => 'সঠিক ৪-সংখ্যার ওটিপি কোড লিখুন!'], 400);
    }

    // Strict OTP Verification (Dual Check: Session + DB)
    $storedOtp = $_SESSION['temp_otp_' . $phone] ?? null;
    $dbOtp = getDbOtpRecord($pdo, $phone);

    if (!$storedOtp && !$dbOtp) {
        jsonResponse(['status' => 'error', 'message' => 'ওটিপি অনুরোধ করা হয়নি অথবা মেয়াদ শেষ হয়েছে। অনুগ্রহ করে আবার ওটিপি পাঠান।'], 400);
    }

    $storedCode = is_array($storedOtp) ? ($storedOtp['code'] ?? '') : ($storedOtp ? (string)$storedOtp : ($dbOtp['otp_code'] ?? ''));
    $expiresAt = is_array($storedOtp) ? ($storedOtp['expires_at'] ?? 0) : ($dbOtp ? strtotime($dbOtp['expires_at']) : 0);
    $attempts = is_array($storedOtp) ? ($storedOtp['attempts'] ?? 0) : ($dbOtp['attempts'] ?? 0);

    if ($expiresAt > 0 && time() > $expiresAt) {
        unset($_SESSION['temp_otp_' . $phone]);
        clearDbOtp($pdo, $phone);
        jsonResponse(['status' => 'error', 'message' => 'ওটিপির মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে আবার নতুন ওটিপি পাঠান।'], 400);
    }

    if ($attempts >= 5) {
        unset($_SESSION['temp_otp_' . $phone]);
        clearDbOtp($pdo, $phone);
        jsonResponse(['status' => 'error', 'message' => 'অতিরিক্ত ভুল ওটিপি দেওয়ার কারণে কোডটি বাতিল করা হয়েছে। নতুন ওটিপি চেয়ে চেষ্টা করুন।'], 429);
    }

    if ($otp !== $storedCode) {
        if (is_array($_SESSION['temp_otp_' . $phone])) {
            $_SESSION['temp_otp_' . $phone]['attempts']++;
        }
        if ($dbOtp) {
            recordDbOtpAttempt($pdo, $dbOtp['id']);
        }
        jsonResponse(['status' => 'error', 'message' => 'ভুল ওটিপি কোড! অনুগ্রহ করে আপনার মোবাইলে আসা সঠিক কোডটি দিন।'], 400);
    }

    // Clear verified OTP
    unset($_SESSION['temp_otp_' . $phone], $_SESSION['otp_sent_time_' . $phone]);
    clearDbOtp($pdo, $phone);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse([
            'status' => 'error',
            'not_registered' => true,
            'message' => 'অ্যাকাউন্ট পাওয়া যায়নি! অনুগ্রহ করে প্রথমে সাইন আপ করুন।'
        ], 404);
    }

    $_SESSION['customer_id'] = $user['id'];
    $_SESSION['customer_name'] = $user['name'] ?: 'Customer';
    $_SESSION['customer_phone'] = $user['phone'];
    $_SESSION['customer_photo'] = $user['profile_photo'] ?? '';

    $userLocation = $user['default_location'] ?? $user['police_station'] ?? $user['address'] ?? '';
    $savedAddresses = !empty($user['saved_addresses']) ? json_decode($user['saved_addresses'], true) : [];
    $activeAddressId = $user['active_address_id'] ?? null;
    $cartData = !empty($user['cart_data']) ? json_decode($user['cart_data'], true) : [];
    $wishlistData = !empty($user['wishlist_data']) ? json_decode($user['wishlist_data'], true) : [];

    jsonResponse([
        'status' => 'success',
        'message' => 'Logged in successfully',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'] ?: 'Customer',
            'phone' => $user['phone'],
            'email' => $user['email'] ?? '',
            'location' => $userLocation,
            'profile_photo' => $user['profile_photo'] ?? '',
            'saved_addresses' => is_array($savedAddresses) ? $savedAddresses : [],
            'active_address_id' => $activeAddressId,
            'cart_data' => is_array($cartData) ? $cartData : [],
            'wishlist_data' => is_array($wishlistData) ? $wishlistData : []
        ]
    ]);
}

// 3. Create / Register Customer
if ($action === 'register' || strpos($_SERVER['REQUEST_URI'], 'create_customer') !== false || strpos($_SERVER['REQUEST_URI'], 'register') !== false) {
    $name = trim($input['name'] ?? '');
    $rawPhone = trim($input['phone'] ?? '');
    $phone = normalizeBdPhone($rawPhone);
    $email = trim($input['email'] ?? '');
    $otp = trim($input['otp'] ?? '');

    if (empty($name) || empty($phone)) {
        jsonResponse(['status' => 'error', 'message' => 'Full Name and Phone number are required.'], 400);
    }

    if (!isValidBdPhone($phone)) {
        jsonResponse(['status' => 'error', 'message' => 'অনুগ্রহ করে সঠিক ১১-সংখ্যার বাংলাদেশি মোবাইল নম্বর দিন (যেমন: 017XXXXXXXX)।'], 400);
    }

    // Accept 4-digit OTP code
    if (empty($otp) || !preg_match('/^\d{4}$/', $otp)) {
        jsonResponse(['status' => 'error', 'message' => 'সঠিক ৪-সংখ্যার ওটিপি কোড লিখুন!'], 400);
    }

    // Strict OTP Verification for Registration (Dual Check: Session + DB)
    $storedOtp = $_SESSION['temp_otp_' . $phone] ?? null;
    $dbOtp = getDbOtpRecord($pdo, $phone);

    if (!$storedOtp && !$dbOtp) {
        jsonResponse(['status' => 'error', 'message' => 'ওটিপি অনুরোধ করা হয়নি অথবা মেয়াদ শেষ হয়েছে। অনুগ্রহ করে আবার ওটিপি পাঠান।'], 400);
    }

    $storedCode = is_array($storedOtp) ? ($storedOtp['code'] ?? '') : ($storedOtp ? (string)$storedOtp : ($dbOtp['otp_code'] ?? ''));
    $expiresAt = is_array($storedOtp) ? ($storedOtp['expires_at'] ?? 0) : ($dbOtp ? strtotime($dbOtp['expires_at']) : 0);
    $attempts = is_array($storedOtp) ? ($storedOtp['attempts'] ?? 0) : ($dbOtp['attempts'] ?? 0);

    if ($expiresAt > 0 && time() > $expiresAt) {
        unset($_SESSION['temp_otp_' . $phone]);
        clearDbOtp($pdo, $phone);
        jsonResponse(['status' => 'error', 'message' => 'ওটিপির মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে আবার নতুন ওটিপি পাঠান।'], 400);
    }

    if ($attempts >= 5) {
        unset($_SESSION['temp_otp_' . $phone]);
        clearDbOtp($pdo, $phone);
        jsonResponse(['status' => 'error', 'message' => 'অতিরিক্ত ভুল ওটিপি দেওয়ার কারণে কোডটি বাতিল করা হয়েছে। নতুন ওটিপি চেয়ে চেষ্টা করুন।'], 429);
    }

    if ($otp !== $storedCode) {
        if (is_array($_SESSION['temp_otp_' . $phone])) {
            $_SESSION['temp_otp_' . $phone]['attempts']++;
        }
        if ($dbOtp) {
            recordDbOtpAttempt($pdo, $dbOtp['id']);
        }
        jsonResponse(['status' => 'error', 'message' => 'ভুল ওটিপি কোড! অনুগ্রহ করে আপনার মোবাইলে আসা সঠিক কোডটি দিন।'], 400);
    }

    // Clear verified OTP
    unset($_SESSION['temp_otp_' . $phone], $_SESSION['otp_sent_time_' . $phone]);
    clearDbOtp($pdo, $phone);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $existingUser = $stmt->fetch();

    $savedAddresses = [];
    $activeAddressId = null;
    $cartData = [];
    $wishlistData = [];
    $userPhoto = '';

    if ($existingUser) {
        $upd = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $upd->execute([$name, $email, $existingUser['id']]);
        $userId = $existingUser['id'];
        $userLoc = $existingUser['default_location'] ?? $existingUser['police_station'] ?? $existingUser['address'] ?? '';
        $userPhoto = $existingUser['profile_photo'] ?? '';
        $savedAddresses = !empty($existingUser['saved_addresses']) ? json_decode($existingUser['saved_addresses'], true) : [];
        $activeAddressId = $existingUser['active_address_id'] ?? null;
        $cartData = !empty($existingUser['cart_data']) ? json_decode($existingUser['cart_data'], true) : [];
        $wishlistData = !empty($existingUser['wishlist_data']) ? json_decode($existingUser['wishlist_data'], true) : [];
    } else {
        $ins = $pdo->prepare("INSERT INTO users (name, phone, email, role, status, created_at) VALUES (?, ?, ?, 'customer', 'active', NOW())");
        $ins->execute([$name, $phone, $email]);
        $userId = $pdo->lastInsertId();
        $userLoc = '';
    }

    $_SESSION['customer_id'] = $userId;
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_phone'] = $phone;
    $_SESSION['customer_photo'] = $userPhoto;

    jsonResponse([
        'status' => 'success',
        'message' => 'Registration successful',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'location' => $userLoc,
            'profile_photo' => $userPhoto,
            'saved_addresses' => is_array($savedAddresses) ? $savedAddresses : [],
            'active_address_id' => $activeAddressId,
            'cart_data' => is_array($cartData) ? $cartData : [],
            'wishlist_data' => is_array($wishlistData) ? $wishlistData : []
        ]
    ]);
}

// 4. Logout
if ($action === 'logout') {
    session_destroy();
    jsonResponse(['status' => 'success', 'message' => 'Logged out successfully']);
}

jsonResponse(['status' => 'error', 'message' => 'Unknown action'], 400);
