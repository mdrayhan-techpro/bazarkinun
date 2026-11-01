<?php
// portal_login.php - Unified Portal Login for Admin, Manager & Staff
require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $identifier = trim($_POST['identifier'] ?? $_POST['username'] ?? $_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $fifteenMinsAgo = date('Y-m-d H:i:s', time() - 900);

    // Rate-limiting & Brute-force throttling check (5 failed attempts per 15 minutes)
    $throttleStmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ? AND is_successful = 0");
    $throttleStmt->execute([$ip, $fifteenMinsAgo]);
    $failedAttempts = (int)$throttleStmt->fetchColumn();

    if ($failedAttempts >= 5) {
        $error = 'Too many failed login attempts from this network. For security reasons, please wait 15 minutes before trying again.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired or invalid. Please refresh the page and try again.';
    } elseif (empty($identifier) || empty($password)) {
        $error = 'Please enter both your Username/Phone and Password.';
    } else {
        $normIdentifier = normalizeBdPhone($identifier);
        // Query authorized internal management and staff users
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE (LOWER(TRIM(username)) = LOWER(?) OR LOWER(TRIM(phone)) = LOWER(?) OR LOWER(TRIM(phone)) = LOWER(?) OR LOWER(TRIM(email)) = LOWER(?) OR LOWER(TRIM(name)) = LOWER(?)) 
            AND role IN ('admin', 'author', 'management', 'manager', 'operation', 'rider', 'staff')
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier, $normIdentifier, $identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            try {
                $failLog = $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier, attempted_at, is_successful) VALUES (?, ?, NOW(), 0)");
                $failLog->execute([$ip, $identifier]);
            } catch (Exception $e) {}
            $error = 'No authorized staff or management account found with this identifier.';
        } elseif ($user['status'] === 'inactive') {
            $error = 'Your account has been deactivated. Please contact the Super Admin.';
        } else {
            // Strictly verify password using secure hash verification
            $isValidPassword = (!empty($user['password']) && password_verify($password, $user['password']));

            if ($isValidPassword) {
                // Clear prior failed attempts for this IP
                try {
                    $succLog = $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier, attempted_at, is_successful) VALUES (?, ?, NOW(), 1)");
                    $succLog->execute([$ip, $identifier]);
                    $clrLog = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND is_successful = 0");
                    $clrLog->execute([$ip]);
                } catch (Exception $e) {}

                // Determine destination and set respective sessions
                $role = strtolower($user['role']);
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

                // Audit Log Login
                try {
                    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $logStmt->execute([
                        $user['name'] ?? $user['username'],
                        $role,
                        'Portal Login',
                        "User logged into {$role} portal successfully via unified login.",
                        $ip
                    ]);
                } catch (Exception $e) {}

                // Set unified session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'] ?: 'Portal User';
                $_SESSION['user_phone'] = $user['phone'] ?? '';
                $_SESSION['user_role'] = $role;
                $_SESSION['role'] = $role;
                $_SESSION['user'] = $user;

                if ($role === 'admin' || $role === 'author') {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['name'] ?: 'Super Admin';
                    $_SESSION['admin_phone'] = $user['phone'];
                    $_SESSION['admin_role'] = 'admin';
                    // Backward compatibility aliases
                    $_SESSION['author_id'] = $user['id'];
                    $_SESSION['author_name'] = $user['name'] ?: 'Super Admin';
                    $_SESSION['author_phone'] = $user['phone'];
                    $_SESSION['author_role'] = 'admin';
                    header('Location: dashboard.php?tab=summary');
                    exit;
                } elseif ($role === 'manager' || $role === 'management' || $role === 'operation') {
                    $_SESSION['manager_id'] = $user['id'];
                    $_SESSION['manager_name'] = $user['name'] ?: 'Store Manager';
                    $_SESSION['manager_phone'] = $user['phone'];
                    $_SESSION['manager_role'] = 'manager';
                    header('Location: dashboard.php?tab=summary');
                    exit;
                } elseif ($role === 'rider' || $role === 'staff') {
                    $_SESSION['staff_id'] = $user['id'];
                    $_SESSION['staff_name'] = $user['name'] ?: 'Staff Rider';
                    $_SESSION['staff_username'] = $user['username'] ?? $user['name'];
                    $_SESSION['staff_phone'] = $user['phone'];
                    $_SESSION['staff_role'] = 'rider';
                    $_SESSION['staff_location'] = $user['default_location'] ?? 'Uttara, Dhaka';
                    header('Location: dashboard.php?tab=staff_panel');
                    exit;
                } else {
                    $error = 'Access denied. Your account does not have internal portal permissions.';
                }
            } else {
                try {
                    $failLog = $pdo->prepare("INSERT INTO login_attempts (ip_address, identifier, attempted_at, is_successful) VALUES (?, ?, NOW(), 0)");
                    $failLog->execute([$ip, $identifier]);
                } catch (Exception $e) {}
                $error = 'Invalid password. Please check your credentials and try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Management Portal - BazarKinun.com</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/bazarkinundotcom_logo.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --emerald: #059669;
            --purple: #7c3aed;
            --blue: #2563eb;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fff1f2 0%, #f0fdf4 35%, #fdf2f8 70%, #fffbeb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #0f172a;
            position: relative;
            box-sizing: border-box;
        }

        /* Sweet ambient glow orbs */
        .bg-shape-1 {
            position: absolute;
            top: -120px;
            left: -100px;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(244, 114, 182, 0.18) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .bg-shape-2 {
            position: absolute;
            bottom: -120px;
            right: -100px;
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .bg-shape-3 {
            position: absolute;
            top: 25%;
            right: 15%;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.14) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .portal-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: 28px;
            box-shadow: 0 25px 60px -15px rgba(244, 63, 94, 0.08), 0 20px 45px -10px rgba(5, 150, 105, 0.08), 0 10px 25px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 440px;
            padding: 48px 36px;
            position: relative;
            z-index: 10;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .input-wrapper:focus-within {
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
        }

        .input-icon {
            padding: 0 14px;
            color: #94a3b8;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-input {
            width: 100%;
            padding: 13px 14px 13px 0;
            border: none;
            outline: none;
            font-size: 14.5px;
            font-weight: 600;
            color: #0f172a;
            background: transparent;
            font-family: inherit;
        }

        .toggle-pwd {
            padding: 0 16px;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
            background: none;
            border: none;
            font-size: 15px;
        }

        .toggle-pwd:hover {
            color: #475569;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 11px 15px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 9px;
            line-height: 1.35;
        }

        .btn-portal-submit {
            width: 100%;
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 15.5px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-portal-submit:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .portal-card {
                padding: 32px 20px;
                border-radius: 20px;
            }
        }

        @media (max-width: 374px) {
            .portal-card {
                padding: 24px 14px;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shape-1"></div>
    <div class="bg-shape-2"></div>
    <div class="bg-shape-3"></div>

    <div class="portal-card">
        <!-- Brand Logos & Title -->
        <div style="text-align: center; margin-bottom: 24px;">
            <a href="index.php" style="display: inline-flex; flex-direction: column; align-items: center; text-decoration: none; margin-bottom: 4px;">
                <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="height: 68px; width: auto; object-fit: contain; margin-bottom: -12px;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3081/3081840.png'">
                <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 85px; max-width: 95%; width: auto; object-fit: contain; display: block; margin-top: -6px; margin-bottom: -4px;">
            </a>
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 18.5px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.2px;">Sign In Here</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 16px;"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Portal Login Form -->
        <form method="POST" action="portal_login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
            <!-- 1. USERNAME -->
            <div class="form-group">
                <label class="form-label">User Name</label>
                <div class="input-wrapper">
                    <div class="input-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <input type="text" 
                           name="identifier" 
                           id="portal-identifier" 
                           class="form-input" 
                           placeholder="Enter your user name" 
                           value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" 
                           required 
                           autofocus>
                </div>
            </div>

            <!-- 2. PASSWORD -->
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <div class="input-icon">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input type="password" 
                           name="password" 
                           id="portal-password" 
                           class="form-input" 
                           placeholder="Enter your password" 
                           required>
                    <button type="button" class="toggle-pwd" onclick="togglePasswordVisibility()" title="Show/Hide Password">
                        <i class="fa-solid fa-eye" id="pwd-eye-icon"></i>
                    </button>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <button type="submit" class="btn-portal-submit">
                <span>Sign In</span>
            </button>
        </form>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('portal-password');
            const icon = document.getElementById('pwd-eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
