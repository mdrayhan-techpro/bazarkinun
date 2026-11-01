<?php
// database/upgrade_author.php
require_once __DIR__ . '/../config/db.php';

echo "--- Starting Author & Portal Settings Upgrade ---\n";

// 1. Create portal_settings table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `portal_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "[1/4] portal_settings table ensured.\n";

// 2. Default portal settings values
$defaultSettings = [
    'site_name' => 'BazarKinun.com',
    'hotline_phone' => '01919-668960',
    'support_email' => 'support@bazarkinun.com',
    'office_address' => 'House 12, Road 4, Sector 3, Uttara, Dhaka-1230',
    'order_hours_en' => '07:00 AM - 11:00 PM (Daily)',
    'order_hours_bn' => 'সকাল ৭:০০ - রাত ১১:০০ (প্রতিদিন)',
    'broadcast_notice_en' => 'Welcome to BazarKinun.com! Free delivery on orders above ৳1000!',
    'broadcast_notice_bn' => 'স্বাগতম BazarKinun.com এ! ১০০০ টাকার উপরে অর্ডারে ফ্রি ডেলিভারি!',
    'broadcast_active' => '1',
    'facebook_url' => 'https://facebook.com',
    'whatsapp_number' => '8801919668960'
];

$setStmt = $pdo->prepare("
    INSERT INTO `portal_settings` (`setting_key`, `setting_value`) 
    VALUES (?, ?) 
    ON DUPLICATE KEY UPDATE `setting_key`=VALUES(`setting_key`);
");

foreach ($defaultSettings as $key => $val) {
    // Only insert if doesn't exist
    $check = $pdo->prepare("SELECT id FROM portal_settings WHERE setting_key = ?");
    $check->execute([$key]);
    if (!$check->fetchColumn()) {
        $setStmt->execute([$key, $val]);
    }
}
echo "[2/4] Default portal settings seeded.\n";

// 3. Add rider_id to orders table if not exists
$checkCol = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'rider_id'")->fetch();
if (!$checkCol) {
    $pdo->exec("
        ALTER TABLE `orders` 
        ADD COLUMN `rider_id` INT NULL AFTER `user_id`,
        ADD CONSTRAINT `fk_orders_rider` FOREIGN KEY (`rider_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
    ");
    echo "[3/4] Added rider_id column to orders table.\n";
} else {
    echo "[3/4] rider_id column already present.\n";
}

// 4. Ensure Admin / Author User exists
$adminPass = password_hash('123456', PASSWORD_DEFAULT);
$adminCheck = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR role = 'admin'");
$adminCheck->execute(['01900000000']);
$adminId = $adminCheck->fetchColumn();

if (!$adminId) {
    $insAdmin = $pdo->prepare("
        INSERT INTO `users` (`name`, `phone`, `email`, `password`, `role`, `status`, `default_location`) 
        VALUES (?, ?, ?, ?, 'admin', 'active', 'Uttara Sector 3, Dhaka')
    ");
    $insAdmin->execute(['BazarKinun Author Admin', '01900000000', 'admin@bazarkinun.com', $adminPass]);
    echo "[4/4] Author Admin user created (Phone: 01900000000, Pass: 123456).\n";
} else {
    // Ensure password and role are active
    $pdo->prepare("UPDATE users SET password = ?, role = 'admin', status = 'active' WHERE id = ?")->execute([$adminPass, $adminId]);
    echo "[4/4] Author Admin user already exists (ID: {$adminId}), credentials refreshed.\n";
}

// Also ensure Rider and Manager have password hashes
$pdo->prepare("UPDATE users SET password = ? WHERE phone = '01711223344'")->execute([$adminPass]);
$pdo->prepare("UPDATE users SET password = ? WHERE phone = '01811223344'")->execute([$adminPass]);

echo "--- Upgrade Completed Successfully ---\n";
