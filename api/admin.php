<?php
// api/admin.php
// Super Admin panel backend operations
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// Enforce authorized portal staff session
$authAdmin = requirePortalAuth(['admin', 'author', 'super_admin', 'management', 'operation', 'manager', 'staff', 'rider']);

try {
    // Unified Add User (Full name, Username, Password, Phone, Access Level, Status)
    if ($action === 'add_user') {
        requirePermission('create_staff');
        $name = trim($input['name'] ?? $input['full_name'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '123456');
        $phone = normalizeBdPhone(trim($input['phone'] ?? ''));
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? $input['access_level'] ?? 'rider');
        $status = strtolower(trim($input['status'] ?? 'active'));
        $location = trim($input['location'] ?? 'Dhaka');

        // Role Hierarchy Guard: Only Super Admin can create Super Admin or Management Board accounts
        if (($role === 'admin' || $role === 'management') && !isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => "Permission Denied: Only Super Admin can create accounts with '{$role}' access."], 403);
        }

        if (!in_array($status, ['active', 'inactive', 'locked'])) {
            $status = 'active';
        }

        if (empty($name)) {
            jsonResponse(['status' => 'error', 'message' => 'Full Name is required.'], 400);
        }
        if (empty($username)) {
            $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $name));
        }
        if (empty($phone)) {
            jsonResponse(['status' => 'error', 'message' => 'Phone number is required.'], 400);
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR username = ?");
        $check->execute([$phone, $username]);
        if ($check->fetchColumn()) {
            jsonResponse(['status' => 'error', 'message' => "An account with Phone '{$phone}' or Username '{$username}' already exists."], 400);
        }

        // Custom permissions guard: non-admins without manage_roles_permissions cannot assign custom permissions
        $userPerms = null;
        if (!empty($input['permissions']) && hasPermission('manage_roles_permissions')) {
            $userPerms = is_array($input['permissions']) ? json_encode($input['permissions'], JSON_UNESCAPED_UNICODE) : strval($input['permissions']);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, phone, email, password, role, status, default_location, permissions) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $username, $phone, $email, $hash, $role, $status, $location, $userPerms]);
        $newId = $pdo->lastInsertId();

        try {
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, created_at) VALUES (?, 'admin', 'User Created', ?, ?, NOW())");
            $logStmt->execute([
                $_SESSION['admin_name'] ?? 'Super Admin',
                "Created user '{$name}' (@{$username}, Role: {$role}, Status: {$status})",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {}

        jsonResponse([
            'status' => 'success',
            'message' => "User account '{$name}' created successfully!",
            'user_id' => $newId
        ]);
    }

    // Unified Edit User
    if ($action === 'edit_user') {
        requirePermission('edit_staff');
        $userId = intval($input['id'] ?? $input['user_id'] ?? 0);
        $name = trim($input['name'] ?? $input['full_name'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $phone = normalizeBdPhone(trim($input['phone'] ?? ''));
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? $input['access_level'] ?? 'rider');
        $status = strtolower(trim($input['status'] ?? 'active'));
        $location = trim($input['location'] ?? 'Dhaka');

        if ($userId <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid user ID'], 400);
        }

        // Fetch existing target user to enforce role hierarchy
        $targetStmt = $pdo->prepare("SELECT id, role, username, permissions FROM users WHERE id = ?");
        $targetStmt->execute([$userId]);
        $targetUser = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$targetUser) {
            jsonResponse(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Role Hierarchy Guard: Non-admins cannot edit Super Admin or Management accounts
        if (($targetUser['role'] === 'admin' || $targetUser['role'] === 'management') && !isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => "Permission Denied: Only Super Admin can modify '{$targetUser['role']}' accounts."], 403);
        }

        // Role Hierarchy Guard: Non-admins cannot promote anyone to admin or management
        if (($role === 'admin' || $role === 'management') && !isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => "Permission Denied: Only Super Admin can assign the '{$role}' role."], 403);
        }

        if (!in_array($status, ['active', 'inactive', 'locked'])) {
            $status = 'active';
        }
        if (empty($name) || empty($phone)) {
            jsonResponse(['status' => 'error', 'message' => 'Full Name and Phone are required.'], 400);
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE (phone = ? OR username = ?) AND id != ?");
        $check->execute([$phone, $username, $userId]);
        if ($check->fetchColumn()) {
            jsonResponse(['status' => 'error', 'message' => "Another user with Phone '{$phone}' or Username '{$username}' already exists."], 400);
        }

        // Custom permissions guard: only callers with manage_roles_permissions can alter custom privileges
        if (hasPermission('manage_roles_permissions')) {
            $userPerms = isset($input['permissions']) ? (is_array($input['permissions']) ? json_encode($input['permissions'], JSON_UNESCAPED_UNICODE) : strval($input['permissions'])) : null;
        } else {
            $userPerms = $targetUser['permissions'];
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, phone = ?, email = ?, password = ?, role = ?, status = ?, default_location = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$name, $username, $phone, $email, $hash, $role, $status, $location, $userPerms, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, phone = ?, email = ?, role = ?, status = ?, default_location = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$name, $username, $phone, $email, $role, $status, $location, $userPerms, $userId]);
        }

        jsonResponse([
            'status' => 'success',
            'message' => "User account '{$name}' updated successfully!"
        ]);
    }

    // Unified Delete User (Admin, Management, Operation, Manager, Rider)
    if ($action === 'delete_user') {
        requirePermission('delete_staff');
        $userId = intval($input['id'] ?? $input['user_id'] ?? 0);
        if ($userId <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid user ID.'], 400);
        }
        if ($userId === 1) {
            jsonResponse(['status' => 'error', 'message' => 'Primary Super Admin account (#1) cannot be deleted.'], 400);
        }

        $userStmt = $pdo->prepare("SELECT name, username, role FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $uData = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$uData) {
            jsonResponse(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Role Hierarchy Guard: Non-admins cannot delete Super Admin or Management accounts
        if (($uData['role'] === 'admin' || $uData['role'] === 'management') && !isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => "Permission Denied: Only Super Admin can delete '{$uData['role']}' accounts."], 403);
        }

        // Clean up staff locations & disassociate rider orders
        try {
            $pdo->prepare("DELETE FROM staff_locations WHERE staff_id = ?")->execute([$userId]);
            $pdo->prepare("UPDATE orders SET rider_id = NULL WHERE rider_id = ?")->execute([$userId]);
        } catch (Exception $e) {}

        // Delete user
        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$userId]);

        try {
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, created_at) VALUES (?, 'admin', 'User Deleted', ?, ?, NOW())");
            $logStmt->execute([
                $_SESSION['admin_name'] ?? 'Super Admin',
                "Deleted user '{$uData['name']}' (@{$uData['username']}, Role: {$uData['role']})",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {}

        jsonResponse([
            'status' => 'success',
            'message' => "User '{$uData['name']}' deleted successfully!"
        ]);
    }

    // 1. Add Staff (Legacy Fallback)
    if ($action === 'add_staff') {
        requirePermission('create_staff');
        $username = trim($input['username'] ?? '');
        $name = trim($input['name'] ?? $input['full_name'] ?? $username);
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? 'rider');
        $password = trim($input['password'] ?? '123456');
        $location = trim($input['location'] ?? 'Dhaka');

        if (empty($name) && !empty($username)) $name = $username;
        if (empty($username) && !empty($name)) $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $name));
        if (empty($phone)) {
            $phone = !empty($username) ? $username : '017' . rand(10000000, 99999999);
        }

        if (empty($username) && empty($name)) {
            jsonResponse(['status' => 'error', 'message' => 'Username or Full Name is required.'], 400);
        }

        if (!in_array($role, ['rider', 'manager', 'admin', 'management'])) {
            $role = 'rider';
        }

        // Check unique phone / username
        $check = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $check->execute([$phone]);
        if ($check->fetchColumn()) {
            jsonResponse(['status' => 'error', 'message' => "An account with identifier {$phone} already exists."], 400);
        }

        $permissions = !empty($input['permissions']) ? (is_string($input['permissions']) ? $input['permissions'] : json_encode($input['permissions'], JSON_UNESCAPED_UNICODE)) : null;

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, username, phone, email, password, role, status, default_location, permissions) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ");
        $stmt->execute([$name, $username, $phone, $email, $hash, $role, $location, $permissions]);
        $newId = $pdo->lastInsertId();

        jsonResponse([
            'status' => 'success',
            'message' => "Staff account '{$name}' created successfully!",
            'staff_id' => $newId
        ]);
    }

    // 1.1 Add Management Account
    if ($action === 'add_management') {
        requirePermission('create_staff');
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? 'management');
        $password = trim($input['password'] ?? '123456');
        $location = trim($input['location'] ?? 'Corporate Head Office, Dhaka');

        if (empty($name)) {
            jsonResponse(['status' => 'error', 'message' => 'Management member name is required.'], 400);
        }
        if (empty($phone)) {
            jsonResponse(['status' => 'error', 'message' => 'Mobile number is required.'], 400);
        }

        if (!in_array($role, ['management', 'admin'])) {
            $role = 'management';
        }

        // Check unique phone
        $check = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $check->execute([$phone]);
        if ($check->fetchColumn()) {
            jsonResponse(['status' => 'error', 'message' => "An account with mobile number {$phone} already exists."], 400);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, phone, email, password, role, status, default_location) 
            VALUES (?, ?, ?, ?, ?, 'active', ?)
        ");
        $stmt->execute([$name, $phone, $email, $hash, $role, $location]);
        $newId = $pdo->lastInsertId();

        jsonResponse([
            'status' => 'success',
            'message' => "Management account '{$name}' created successfully!",
            'management_id' => $newId
        ]);
    }

    // 1.2 Edit Management Account
    if ($action === 'edit_management') {
        requirePermission('edit_staff');
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? 'management');
        $status = trim($input['status'] ?? 'active');
        $location = trim($input['location'] ?? 'Corporate Head Office, Dhaka');
        $password = trim($input['password'] ?? '');

        if ($id <= 0 || empty($name) || empty($phone)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid ID, Name and Phone are required.'], 400);
        }

        // Check if phone taken by another user
        $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        $check->execute([$phone, $id]);
        if ($check->fetchColumn()) {
            jsonResponse(['status' => 'error', 'message' => "Phone number {$phone} is already in use by another user."], 400);
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, email = ?, role = ?, status = ?, default_location = ?, password = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $email, $role, $status, $location, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, email = ?, role = ?, status = ?, default_location = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $email, $role, $status, $location, $id]);
        }

        jsonResponse(['status' => 'success', 'message' => 'Management account updated successfully!']);
    }

    // 1.3 Delete Management Account
    if ($action === 'delete_management') {
        requirePermission('delete_staff');
        if (!isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => 'Permission Denied: Only Super Admin can delete Management accounts.'], 403);
        }
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid account ID'], 400);

        if ($id === 1) {
            jsonResponse(['status' => 'error', 'message' => 'Primary Super Admin account cannot be deleted.'], 400);
        }

        $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('management', 'admin')")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Management account deleted successfully!']);
    }

    // 2. Edit Staff
    if ($action === 'edit_staff') {
        requirePermission('edit_staff');
        $id = intval($input['id'] ?? 0);
        $username = trim($input['username'] ?? '');
        $name = trim($input['name'] ?? $input['full_name'] ?? $username);
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? 'rider');
        $status = trim($input['status'] ?? 'active');
        $password = trim($input['password'] ?? '');

        if ($id <= 0 || (empty($name) && empty($username))) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid account ID or name.'], 400);
        }

        if (empty($name)) $name = $username;
        if (empty($username)) $username = $name;

        // Check if phone taken by another user
        if (!empty($phone)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $check->execute([$phone, $id]);
            if ($check->fetchColumn()) {
                jsonResponse(['status' => 'error', 'message' => "Phone number/Identifier {$phone} is already in use."], 400);
            }
        }

        $permissions = !empty($input['permissions']) ? (is_string($input['permissions']) ? $input['permissions'] : json_encode($input['permissions'], JSON_UNESCAPED_UNICODE)) : null;

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, username = ?, phone = ?, email = ?, role = ?, status = ?, password = ?, permissions = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $username, $phone, $email, $role, $status, $hash, $permissions, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, username = ?, phone = ?, email = ?, role = ?, status = ?, permissions = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $username, $phone, $email, $role, $status, $permissions, $id]);
        }

        jsonResponse(['status' => 'success', 'message' => 'Account profile updated successfully!']);
    }

    // 3. Toggle Staff Status
    if ($action === 'toggle_staff_status') {
        requirePermission('toggle_staff_status');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid staff ID'], 400);

        $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) jsonResponse(['status' => 'error', 'message' => 'User not found.'], 404);

        // Role Hierarchy Guard: Only Super Admin can alter Super Admin or Management status
        if (($target['role'] === 'admin' || $target['role'] === 'management') && !isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => "Permission Denied: Only Super Admin can alter '{$target['role']}' account status."], 403);
        }

        $current = $target['status'];
        $newStatus = ($current === 'active') ? 'inactive' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);

        jsonResponse([
            'status' => 'success',
            'message' => "Staff status switched to {$newStatus}.",
            'new_status' => $newStatus
        ]);
    }

    // 4. Delete Staff
    if ($action === 'delete_staff') {
        requirePermission('delete_staff');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid staff ID'], 400);

        // Disassociate orders if rider
        $pdo->prepare("UPDATE orders SET rider_id = NULL WHERE rider_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('rider', 'manager')")->execute([$id]);

        jsonResponse(['status' => 'success', 'message' => 'Staff member deleted successfully!']);
    }

    // 5. Save Portal Settings
    if ($action === 'save_portal_settings') {
        // Enforce fine-grained permissions depending on what is being updated
        $submittedKeys = array_keys($input);
        foreach ($submittedKeys as $sk) {
            if (strpos($sk, 'sms_gateway_') === 0) {
                requirePermission('manage_sms_gateway');
            } elseif (strpos($sk, 'payment_') === 0) {
                requirePermission('manage_payment_methods');
            } elseif (strpos($sk, 'broadcast_') === 0) {
                requirePermission('manage_broadcast');
            } elseif (in_array($sk, ['about_us_en', 'about_us_bn', 'privacy_policy_en', 'privacy_policy_bn', 'terms_conditions_en', 'terms_conditions_bn', 'refund_policy_en', 'refund_policy_bn'])) {
                requirePermission('manage_policies');
            } elseif (in_array($sk, ['hotline_phone', 'alt_phone', 'support_email', 'whatsapp_number', 'facebook_url', 'office_address', 'office_address_en', 'office_address_bn', 'tagline_en', 'tagline_bn', 'support_hours_en', 'support_hours_bn'])) {
                requirePermission('manage_contacts');
            }
        }

        $allowedKeys = [
            'site_name', 'hotline_phone', 'alt_phone', 'support_email', 'whatsapp_number', 'facebook_url',
            'support_hours_en', 'support_hours_bn', 'order_hours_en', 'order_hours_bn',
            'delivery_time_estimate_en', 'delivery_time_estimate_bn',
            'sms_gateway_enabled', 'sms_gateway_provider', 'sms_gateway_api_key', 'sms_gateway_sender_id', 'sms_gateway_custom_url',
            'payment_cod_enabled', 'payment_bkash_enabled', 'payment_bkash_number', 'payment_bkash_type',
            'payment_nagad_enabled', 'payment_nagad_number', 'payment_nagad_type',
            'payment_rocket_enabled', 'payment_rocket_number',
            'office_address', 'office_address_en', 'office_address_bn',
            'tagline_en', 'tagline_bn',
            'about_us_en', 'about_us_bn',
            'privacy_policy_en', 'privacy_policy_bn',
            'terms_conditions_en', 'terms_conditions_bn',
            'refund_policy_en', 'refund_policy_bn',
            'happy_customers_count', 'satisfaction_rate', 'delivery_success_rate',
            'broadcast_notice_en', 'broadcast_notice_bn', 'broadcast_active'
        ];

        $stmt = $pdo->prepare("
            INSERT INTO portal_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($allowedKeys as $k) {
            if (isset($input[$k])) {
                $val = trim(strval($input[$k]));
                $stmt->execute([$k, $val]);
            }
        }

        jsonResponse(['status' => 'success', 'message' => 'Portal settings updated successfully!']);
    }

    // 6. Save Delivery Rules / CRUD Actions
    if ($action === 'add_delivery_rule' || $action === 'edit_delivery_rule') {
        requirePermission('manage_delivery_rules');
        $id = intval($input['id'] ?? 0);
        $priority = intval($input['priority'] ?? 1);
        $ruleName = trim($input['rule_name'] ?? 'Delivery Rule');
        $targetType = trim($input['target_type'] ?? 'custom_list');
        $basedOn = trim($input['based_on'] ?? 'weight');
        $operator = trim($input['operator'] ?? 'between');
        $minValue = floatval($input['min_value'] ?? 0);
        $maxValue = floatval($input['max_value'] ?? 0);
        $calcMethod = trim($input['calculation_method'] ?? 'fixed');
        $baseCharge = floatval($input['base_charge'] ?? ($input['fixed_amount'] ?? 0));
        $perUnitRate = floatval($input['per_unit_rate'] ?? 0);
        $fixedAmount = floatval($input['fixed_amount'] ?? ($calcMethod === 'base_per_kg' || $calcMethod === 'base_per_km' ? $baseCharge : 25));
        $charge = $calcMethod === 'free' ? 0.00 : ($calcMethod === 'base_per_kg' || $calcMethod === 'base_per_km' ? $baseCharge : $fixedAmount);
        $isActive = intval($input['is_active'] ?? 1);
        $minChargeLimit = !empty($input['min_charge_limit']) ? floatval($input['min_charge_limit']) : null;
        $maxChargeLimit = !empty($input['max_charge_limit']) ? floatval($input['max_charge_limit']) : null;
        $effectiveFrom = !empty($input['effective_from']) ? trim($input['effective_from']) : null;
        $effectiveTo = !empty($input['effective_to']) ? trim($input['effective_to']) : null;

        if ($action === 'edit_delivery_rule' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE `delivery_charge_rules` SET `priority` = ?, `rule_name` = ?, `target_type` = ?, `based_on` = ?, `operator` = ?, `min_value` = ?, `max_value` = ?, `calculation_method` = ?, `fixed_amount` = ?, `base_charge` = ?, `per_unit_rate` = ?, `charge` = ?, `is_active` = ?, `min_charge_limit` = ?, `max_charge_limit` = ?, `effective_from` = ?, `effective_to` = ? WHERE `id` = ?");
            $stmt->execute([$priority, $ruleName, $targetType, $basedOn, $operator, $minValue, $maxValue, $calcMethod, $fixedAmount, $baseCharge, $perUnitRate, $charge, $isActive, $minChargeLimit, $maxChargeLimit, $effectiveFrom, $effectiveTo, $id]);
            jsonResponse(['status' => 'success', 'message' => 'Delivery rule updated successfully!']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `delivery_charge_rules` (`priority`, `rule_name`, `target_type`, `based_on`, `operator`, `min_value`, `max_value`, `calculation_method`, `fixed_amount`, `base_charge`, `per_unit_rate`, `charge`, `is_active`, `min_charge_limit`, `max_charge_limit`, `effective_from`, `effective_to`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$priority, $ruleName, $targetType, $basedOn, $operator, $minValue, $maxValue, $calcMethod, $fixedAmount, $baseCharge, $perUnitRate, $charge, $isActive, $minChargeLimit, $maxChargeLimit, $effectiveFrom, $effectiveTo]);
            jsonResponse(['status' => 'success', 'message' => 'New delivery rule created!']);
        }
    }

    if ($action === 'toggle_delivery_rule') {
        requirePermission('manage_delivery_rules');
        $id = intval($input['id'] ?? 0);
        $pdo->prepare("UPDATE `delivery_charge_rules` SET `is_active` = IF(`is_active` = 1, 0, 1) WHERE `id` = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Rule status updated!']);
    }

    if ($action === 'duplicate_delivery_rule') {
        requirePermission('manage_delivery_rules');
        $id = intval($input['id'] ?? 0);
        $rule = $pdo->prepare("SELECT * FROM `delivery_charge_rules` WHERE `id` = ?");
        $rule->execute([$id]);
        $r = $rule->fetch();
        if ($r) {
            $stmt = $pdo->prepare("INSERT INTO `delivery_charge_rules` (`priority`, `rule_name`, `target_type`, `based_on`, `operator`, `min_value`, `max_value`, `calculation_method`, `fixed_amount`, `base_charge`, `per_unit_rate`, `charge`, `is_active`, `min_charge_limit`, `max_charge_limit`, `effective_from`, `effective_to`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$r['priority'], $r['rule_name'] . ' (Copy)', $r['target_type'], $r['based_on'], $r['operator'], $r['min_value'], $r['max_value'], $r['calculation_method'], $r['fixed_amount'], $r['base_charge'] ?? 0, $r['per_unit_rate'] ?? 0, $r['charge'], $r['is_active'], $r['min_charge_limit'] ?? null, $r['max_charge_limit'] ?? null, $r['effective_from'] ?? null, $r['effective_to'] ?? null]);
            jsonResponse(['status' => 'success', 'message' => 'Rule duplicated successfully!']);
        }
        jsonResponse(['status' => 'error', 'message' => 'Rule not found.'], 404);
    }

    if ($action === 'delete_delivery_rule') {
        requirePermission('manage_delivery_rules');
        $id = intval($input['id'] ?? 0);
        $pdo->prepare("DELETE FROM `delivery_charge_rules` WHERE `id` = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Delivery rule deleted!']);
    }

    if ($action === 'save_delivery_rules') {
        requirePermission('manage_delivery_rules');
        $baseCharge = floatval($input['base_charge'] ?? 60.00);
        $minFreeAmount = floatval($input['min_free_amount'] ?? 1000.00);

        // Update threshold rule
        $pdo->prepare("UPDATE delivery_charge_rules SET min_order_amount = ?, charge = 0.00 WHERE charge = 0.00")
            ->execute([$minFreeAmount]);

        // Update base rule
        $pdo->prepare("UPDATE delivery_charge_rules SET charge = ? WHERE charge > 0")
            ->execute([$baseCharge]);

        jsonResponse(['status' => 'success', 'message' => 'Delivery charge configuration updated!']);
    }

    // 7. Add Category
    if ($action === 'add_category') {
        requirePermission('manage_categories');
        $name = trim($input['name'] ?? '');
        $nameBn = trim($input['name_bn'] ?? $name);
        $slug = trim($input['slug'] ?? strtolower(str_replace(' ', '-', $name)));
        $img = trim($input['img'] ?? 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300');

        if (empty($name)) {
            jsonResponse(['status' => 'error', 'message' => 'Category English Name is required.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO categories (name, name_bn, slug, img) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $nameBn, $slug, $img]);

        jsonResponse([
            'status' => 'success',
            'message' => "Category '{$name}' created successfully!",
            'category_id' => $pdo->lastInsertId()
        ]);
    }

    // 8. Edit Category
    if ($action === 'edit_category') {
        requirePermission('manage_categories');
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $nameBn = trim($input['name_bn'] ?? $name);
        $slug = trim($input['slug'] ?? '');
        $img = trim($input['img'] ?? '');

        if ($id <= 0 || empty($name)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid Category ID and name are required.'], 400);
        }

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, name_bn = ?, slug = ?, img = IF(? != '', ?, img) WHERE id = ?");
        $stmt->execute([$name, $nameBn, $slug, $img, $img, $id]);

        jsonResponse(['status' => 'success', 'message' => 'Category updated successfully!']);
    }

    // 9. Delete Category
    if ($action === 'delete_category') {
        requirePermission('manage_categories');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid Category ID'], 400);

        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Category deleted successfully!']);
    }

    // 10. Assign Rider to Order
    if ($action === 'assign_rider') {
        requirePermission('assign_riders');
        $orderId = intval($input['order_id'] ?? 0);
        $riderId = !empty($input['rider_id']) ? intval($input['rider_id']) : null;

        if ($orderId <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid Order ID'], 400);
        }

        if ($riderId) {
            // Check if rider is active
            $rCheck = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'rider'");
            $rCheck->execute([$riderId]);
            $riderName = $rCheck->fetchColumn();
            if (!$riderName) {
                jsonResponse(['status' => 'error', 'message' => 'Selected rider not found.'], 404);
            }

            // If order was pending, progress it to processing
            $pdo->prepare("
                UPDATE orders 
                SET rider_id = ?, status = IF(status = 'pending', 'processing', status) 
                WHERE id = ?
            ")->execute([$riderId, $orderId]);

            jsonResponse(['status' => 'success', 'message' => "Order assigned to Rider {$riderName}!"]);
        } else {
            $pdo->prepare("UPDATE orders SET rider_id = NULL WHERE id = ?")->execute([$orderId]);
            jsonResponse(['status' => 'success', 'message' => 'Rider unassigned from order.']);
        }
    }

    // 11. Customer Reviews (OHC)
    if ($action === 'add_ohc') {
        requirePermission('manage_happy_customers');
        $name = trim($input['name'] ?? '');
        $nameBn = trim($input['name_bn'] ?? '');
        $rating = intval($input['rating'] ?? 5);
        $comment = trim($input['comment'] ?? '');
        $commentBn = trim($input['comment_bn'] ?? '');
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (empty($name) || empty($comment)) {
            jsonResponse(['status' => 'error', 'message' => 'Customer Name and English comment are required.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO `happy_customers` (`name`, `name_bn`, `rating`, `comment`, `comment_bn`, `is_active`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $nameBn, $rating, $comment, $commentBn, $isActive]);

        jsonResponse([
            'status' => 'success',
            'message' => 'Customer review added successfully!',
            'review_id' => $pdo->lastInsertId()
        ]);
    }

    if ($action === 'edit_ohc') {
        requirePermission('manage_happy_customers');
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $nameBn = trim($input['name_bn'] ?? '');
        $rating = intval($input['rating'] ?? 5);
        $comment = trim($input['comment'] ?? '');
        $commentBn = trim($input['comment_bn'] ?? '');
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if ($id <= 0 || empty($name) || empty($comment)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid Review ID, Name, and comment are required.'], 400);
        }

        $stmt = $pdo->prepare("UPDATE `happy_customers` SET `name` = ?, `name_bn` = ?, `rating` = ?, `comment` = ?, `comment_bn` = ?, `is_active` = ? WHERE `id` = ?");
        $stmt->execute([$name, $nameBn, $rating, $comment, $commentBn, $isActive, $id]);

        jsonResponse(['status' => 'success', 'message' => 'Customer review updated successfully!']);
    }

    if ($action === 'toggle_ohc_status') {
        requirePermission('manage_happy_customers');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid Review ID'], 400);

        $pdo->prepare("UPDATE `happy_customers` SET `is_active` = IF(`is_active` = 1, 0, 1) WHERE `id` = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Review visibility updated!']);
    }

    if ($action === 'delete_ohc') {
        requirePermission('manage_happy_customers');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid Review ID'], 400);

        $pdo->prepare("DELETE FROM `happy_customers` WHERE `id` = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Customer review deleted!']);
    }

    // 8. Broadcast Notices
    if ($action === 'create_broadcast_notice') {
        requirePermission('manage_broadcast');
        $title = trim($input['title'] ?? '');
        $message = trim($input['message'] ?? '');
        $targetAudience = trim($input['target_audience'] ?? 'all');
        $customNumbers = trim($input['custom_numbers'] ?? '');
        $noticeType = trim($input['notice_type'] ?? 'info');
        $sendSms = isset($input['send_sms']) && intval($input['send_sms']) === 1 ? 1 : 0;
        $smsStatus = $sendSms ? 'Dispatched' : 'In-App Only';
        $adminName = $_SESSION['admin_name'] ?? $_SESSION['user']['name'] ?? 'Super Admin';

        if (empty($title) || empty($message)) {
            jsonResponse(['status' => 'error', 'message' => 'Title and Announcement message body are required.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO `broadcast_notices` (`title`, `message`, `target_audience`, `custom_numbers`, `notice_type`, `send_sms`, `sms_status`, `is_active`, `created_by`) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
        $stmt->execute([$title, $message, $targetAudience, $customNumbers, $noticeType, $sendSms, $smsStatus, $adminName]);

        // Dispatch SMS to target recipients if enabled
        if ($sendSms) {
            $recipients = [];
            if ($targetAudience === 'custom' && !empty($customNumbers)) {
                $rawList = explode(',', $customNumbers);
                foreach ($rawList as $num) {
                    $c = trim($num);
                    if (!empty($c)) $recipients[] = $c;
                }
            } elseif ($targetAudience === 'staff') {
                $recipients = $pdo->query("SELECT phone FROM users WHERE role IN ('admin', 'manager', 'rider', 'staff') AND phone IS NOT NULL AND phone != ''")->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($targetAudience === 'customer') {
                $recipients = $pdo->query("SELECT phone FROM users WHERE role = 'customer' AND phone IS NOT NULL AND phone != ''")->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($targetAudience === 'all') {
                $recipients = $pdo->query("SELECT DISTINCT phone FROM users WHERE phone IS NOT NULL AND phone != ''")->fetchAll(PDO::FETCH_COLUMN);
            }

            $smsText = "BazarKinun.com Notice: {$title} - {$message}";
            foreach ($recipients as $recPhone) {
                sendSmsNotification($pdo, $recPhone, $smsText);
            }
        }

        jsonResponse([
            'status' => 'success',
            'message' => 'Broadcast announcement published successfully!' . ($sendSms ? ' (SMS dispatched to ' . count($recipients ?? []) . ' recipients)' : '')
        ]);
    }

    // 8.5 Test SMS Action
    if ($action === 'test_sms') {
        requirePermission('manage_sms_gateway');
        $testPhone = trim($input['phone'] ?? '');
        if (empty($testPhone)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid phone number is required.'], 400);
        }
        $testMsg = "BazarKinun.com: Your SMS Gateway is configured and working successfully!";
        $res = sendSmsNotification($pdo, $testPhone, $testMsg);
        if ($res['success']) {
            jsonResponse([
                'status' => 'success',
                'message' => 'Test SMS dispatched successfully to ' . $testPhone . (!empty($res['simulated']) ? ' (Simulation Mode: Save real API Key to send real SMS)' : '')
            ]);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Failed to send SMS: ' . ($res['error'] ?? 'Check API Key / Provider settings.')], 500);
        }
    }

    if ($action === 'toggle_broadcast_notice') {
        requirePermission('manage_broadcast');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid Notice ID'], 400);

        $pdo->prepare("UPDATE `broadcast_notices` SET `is_active` = IF(`is_active` = 1, 0, 1) WHERE `id` = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Broadcast display status updated!']);
    }

    if ($action === 'delete_broadcast_notice') {
        requirePermission('manage_broadcast');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid Notice ID'], 400);

        $pdo->prepare("DELETE FROM `broadcast_notices` WHERE `id` = ?")->execute([$id]);
        jsonResponse(['status' => 'success', 'message' => 'Broadcast announcement deleted!']);
    }

    // 9. Staff Locations
    if ($action === 'add_staff_location') {
        requirePermission('edit_staff');
        $staffId = intval($input['staff_id'] ?? 0);
        $district = trim($input['district'] ?? 'Dhaka');
        $policeStation = trim($input['police_station'] ?? '');

        if ($staffId <= 0 || empty($policeStation)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid Staff ID and Police Station are required.'], 400);
        }

        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM staff_locations WHERE staff_id = ? AND district = ? AND police_station = ?");
        $check->execute([$staffId, $district, $policeStation]);
        if ($check->fetchColumn()) {
            jsonResponse(['status' => 'error', 'message' => 'This location is already assigned to this staff member.'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO `staff_locations` (`staff_id`, `district`, `police_station`) VALUES (?, ?, ?)");
        $stmt->execute([$staffId, $district, $policeStation]);

        $locs = $pdo->prepare("SELECT * FROM `staff_locations` WHERE `staff_id` = ? ORDER BY `id` ASC");
        $locs->execute([$staffId]);

        jsonResponse([
            'status' => 'success',
            'message' => "Location '{$policeStation}, {$district}' assigned successfully!",
            'locations' => $locs->fetchAll()
        ]);
    }

    if ($action === 'delete_staff_location') {
        requirePermission('edit_staff');
        $locId = intval($input['location_id'] ?? 0);
        $staffId = intval($input['staff_id'] ?? 0);
        if ($locId <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid location ID'], 400);

        $pdo->prepare("DELETE FROM `staff_locations` WHERE `id` = ?")->execute([$locId]);

        $locs = [];
        if ($staffId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM `staff_locations` WHERE `staff_id` = ? ORDER BY `id` ASC");
            $stmt->execute([$staffId]);
            $locs = $stmt->fetchAll();
        }

        jsonResponse([
            'status' => 'success',
            'message' => 'Assigned location removed!',
            'locations' => $locs
        ]);
    }

    if ($action === 'get_staff_locations') {
        requirePermission('view_staff');
        $staffId = intval($_GET['staff_id'] ?? $input['staff_id'] ?? 0);
        if ($staffId <= 0) jsonResponse(['status' => 'error', 'message' => 'Invalid staff ID'], 400);

        $stmt = $pdo->prepare("SELECT * FROM `staff_locations` WHERE `staff_id` = ? ORDER BY `id` ASC");
        $stmt->execute([$staffId]);
        jsonResponse([
            'status' => 'success',
            'locations' => $stmt->fetchAll()
        ]);
    }

    if ($action === 'clear_audit_logs') {
        requirePermission('view_audit_logs');
        $pdo->exec("TRUNCATE TABLE `audit_logs`");
        logAuditActivity($pdo, 'Audit Logs Cleared', 'System administrator truncated audit logs history.');
        jsonResponse([
            'status' => 'success',
            'message' => 'All audit logs cleared successfully!'
        ]);
    }

    if ($action === 'save_role_permissions') {
        requirePermission('manage_roles_permissions');
        $roleKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', trim($input['role_key'] ?? '')));
        $roleTitle = trim($input['role_title'] ?? '');
        $description = trim($input['description'] ?? '');
        $permissions = $input['permissions'] ?? [];
        $permissionsJson = is_array($permissions) ? json_encode($permissions, JSON_UNESCAPED_UNICODE) : (string)$permissions;

        if (empty($roleKey) || empty($roleTitle)) {
            jsonResponse(['status' => 'error', 'message' => 'Role Key and Title are required.'], 400);
        }

        // 1. Update legacy role_permissions table
        $stmt = $pdo->prepare("
            INSERT INTO `role_permissions` (`role_key`, `role_title`, `description`, `permissions`, `is_system`)
            VALUES (?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE 
                `role_title` = VALUES(`role_title`),
                `description` = VALUES(`description`),
                `permissions` = VALUES(`permissions`)
        ");
        $stmt->execute([$roleKey, $roleTitle, $description, $permissionsJson]);

        // 2. Sync into centralized `roles` table
        $roleStmt = $pdo->prepare("
            INSERT INTO `roles` (`role_key`, `role_title`, `description`, `is_system`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                `role_title` = VALUES(`role_title`),
                `description` = VALUES(`description`),
                `updated_at` = NOW()
        ");
        $roleStmt->execute([$roleKey, $roleTitle, $description]);
        $roleId = $pdo->query("SELECT id FROM `roles` WHERE `role_key` = " . $pdo->quote($roleKey))->fetchColumn();

        // 3. Sync into `role_permission_map` pivot table
        if ($roleId) {
            $pdo->prepare("DELETE FROM `role_permission_map` WHERE `role_id` = ?")->execute([$roleId]);

            $mapInsert = $pdo->prepare("
                INSERT IGNORE INTO `role_permission_map` (`role_id`, `permission_id`, `created_at`)
                VALUES (?, ?, NOW())
            ");

            if ($roleKey === 'admin') {
                $allPermIds = $pdo->query("SELECT id FROM `permissions` WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($allPermIds as $pId) {
                    $mapInsert->execute([$roleId, $pId]);
                }
            } elseif (is_array($permissions) && !empty($permissions)) {
                $permStmt = $pdo->prepare("SELECT id FROM `permissions` WHERE (`slug` = ? OR `legacy_slug` = ?) AND status = 'active' LIMIT 1");
                foreach ($permissions as $pSlug) {
                    $permStmt->execute([$pSlug, $pSlug]);
                    $matchedId = $permStmt->fetchColumn();
                    if ($matchedId) {
                        $mapInsert->execute([$roleId, $matchedId]);
                    }
                }
            }
        }

        try {
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, created_at) VALUES (?, 'admin', 'Role Configuration', ?, ?, NOW())");
            $logStmt->execute([
                $_SESSION['admin_name'] ?? 'Super Admin',
                "Configured permissions for role: '{$roleTitle}' ({$roleKey})",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {}

        jsonResponse([
            'status' => 'success',
            'message' => "Permissions for role '{$roleTitle}' saved successfully!"
        ]);
    }

    if ($action === 'delete_role') {
        requirePermission('manage_roles_permissions');
        $roleKey = strtolower(trim($input['role_key'] ?? ''));
        if (in_array($roleKey, ['admin', 'manager', 'rider', 'management', 'operation', 'customer'])) {
            jsonResponse(['status' => 'error', 'message' => 'System default roles cannot be deleted.'], 400);
        }

        $roleId = $pdo->query("SELECT id FROM `roles` WHERE `role_key` = " . $pdo->quote($roleKey) . " AND `is_system` = 0")->fetchColumn();
        if ($roleId) {
            $pdo->prepare("DELETE FROM `role_permission_map` WHERE `role_id` = ?")->execute([$roleId]);
            $pdo->prepare("DELETE FROM `roles` WHERE `id` = ? AND `is_system` = 0")->execute([$roleId]);
        }

        $stmt = $pdo->prepare("DELETE FROM `role_permissions` WHERE `role_key` = ? AND `is_system` = 0");
        $stmt->execute([$roleKey]);

        jsonResponse([
            'status' => 'success',
            'message' => "Custom role '{$roleKey}' deleted successfully!"
        ]);
    }

    if ($action === 'assign_user_role') {
        requirePermission('manage_roles_permissions');
        $userId = intval($input['user_id'] ?? 0);
        $newRole = trim($input['role'] ?? '');

        if ($userId <= 0 || empty($newRole)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid User ID and Role are required.'], 400);
        }

        // Role Hierarchy Guard: Only Super Admin can assign admin or management roles
        if (($newRole === 'admin' || $newRole === 'management') && !isSuperAdmin()) {
            jsonResponse(['status' => 'error', 'message' => 'Permission Denied: Only Super Admin can assign administrative roles.'], 403);
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);

        try {
            $uName = $pdo->query("SELECT name FROM users WHERE id = {$userId}")->fetchColumn();
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_name, user_role, action, details, ip_address, created_at) VALUES (?, 'admin', 'User Role Assignment', ?, ?, NOW())");
            $logStmt->execute([
                $_SESSION['admin_name'] ?? 'Super Admin',
                "Assigned role '{$newRole}' to user '{$uName}' (ID: {$userId})",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e) {}

        jsonResponse([
            'status' => 'success',
            'message' => "User role updated to '{$newRole}' successfully!"
        ]);
    }

    jsonResponse(['status' => 'error', 'message' => "Unknown action: '{$action}'"], 400);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
