<?php
// database/upgrade_automation_and_riders.php
// Migration for stock auto-deduction flags, rider assignment, and analytics

require_once __DIR__ . '/../config/db.php';

try {
    echo "=== Starting Automation & Rider Upgrade ===\n";

    // 1. Upgrade `orders` table
    $orderCols = [
        "stock_deducted" => "TINYINT(1) DEFAULT 0 AFTER `discount_amount`",
        "rider_id" => "INT NULL AFTER `stock_deducted`",
        "rider_name" => "VARCHAR(100) NULL AFTER `rider_id`",
        "rider_phone" => "VARCHAR(25) NULL AFTER `rider_name`",
        "assigned_at" => "DATETIME NULL AFTER `rider_phone`"
    ];

    foreach ($orderCols as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM `orders` LIKE '{$col}'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `{$col}` {$definition};");
            echo "[Orders] Added column `{$col}`.\n";
        }
    }

    // 2. Ensure sample Delivery Riders exist in users table
    $riders = [
        ['name' => 'Md. Rahim Mia (Rider 1)', 'username' => 'rider_rahim', 'phone' => '01711122334', 'role' => 'rider'],
        ['name' => 'Karim Hossain (Rider 2)', 'username' => 'rider_karim', 'phone' => '01822334455', 'role' => 'rider'],
        ['name' => 'Sajid Ahmed (Rider 3)', 'username' => 'rider_sajid', 'phone' => '01933445566', 'role' => 'rider']
    ];

    $checkUser = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $insUser = $pdo->prepare("
        INSERT INTO users (name, username, phone, password, role, status, default_location) 
        VALUES (?, ?, ?, ?, ?, 'active', 'Dhaka Delivery Hub')
    ");

    $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
    foreach ($riders as $r) {
        $checkUser->execute([$r['phone']]);
        if (!$checkUser->fetchColumn()) {
            $insUser->execute([$r['name'], $r['username'], $r['phone'], $defaultPass, $r['role']]);
            echo "[Users] Seeded delivery rider `{$r['name']}`.\n";
        }
    }

    echo "=== Upgrade Completed Successfully! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
