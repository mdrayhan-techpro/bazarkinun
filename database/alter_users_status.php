<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `status` ENUM('active', 'inactive', 'locked') DEFAULT 'active'");
    echo "users.status enum altered successfully to include 'locked'!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
