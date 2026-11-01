<?php
// database/upgrade_to_admin.php
// Standardize all 'author' and 'management' roles to 'admin' in database

require_once __DIR__ . '/../config/db.php';

try {
    echo "=== Starting Role Standardization to 'admin' ===\n";

    // 1. Update all users with role 'author' or 'management' or 'superadmin' to 'admin'
    $upd = $pdo->prepare("
        UPDATE `users` 
        SET `role` = 'admin' 
        WHERE `role` IN ('author', 'management', 'superadmin', 'super_author', 'admin_author')
    ");
    $upd->execute();
    echo "[Users] Updated " . $upd->rowCount() . " user accounts to role 'admin'.\n";

    // 2. Ensure Super Admin account exists
    $chkAdmin = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' OR phone = '01919668960' OR role = 'admin' LIMIT 1");
    $chkAdmin->execute();
    $adminUser = $chkAdmin->fetch();

    $defaultPass = password_hash('123456', PASSWORD_DEFAULT);

    if ($adminUser) {
        $pdo->prepare("
            UPDATE `users` 
            SET `role` = 'admin', 
                `name` = IF(name = '' OR name = 'Author' OR name = 'Super Author', 'Super Admin', name),
                `username` = IF(username = 'author', 'admin', username),
                `status` = 'active' 
            WHERE `id` = ?
        ")->execute([$adminUser['id']]);
        echo "[Admin] Updated primary admin account (ID: {$adminUser['id']}).\n";
    } else {
        $ins = $pdo->prepare("
            INSERT INTO `users` (`name`, `username`, `phone`, `email`, `password`, `role`, `status`, `default_location`) 
            VALUES ('Super Admin', 'admin', '01919668960', 'admin@bazarkinun.com', ?, 'admin', 'active', 'Dhaka Head Office')
        ");
        $ins->execute([$defaultPass]);
        echo "[Admin] Created default Super Admin account.\n";
    }

    echo "=== Database Role Standardization Complete ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
