<?php
// database/upgrade_production_readiness.php
// Production Readiness Database Optimization & Table Setup

require_once __DIR__ . '/../config/db.php';

echo "=== BazarKinun.com Production Database Optimization ===\n\n";

// Helper function to check if index exists on a table
function indexExists($pdo, $table, $indexName) {
    try {
        $stmt = $pdo->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = ?");
        $stmt->execute([$indexName]);
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// Helper to safely add an index
function safeAddIndex($pdo, $table, $indexName, $columnsSql) {
    if (!indexExists($pdo, $table, $indexName)) {
        echo "[+] Adding index '{$indexName}' on table '{$table}' ({$columnsSql})... ";
        try {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columnsSql})");
            echo "DONE\n";
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    } else {
        echo "[=] Index '{$indexName}' on table '{$table}' already exists. Skipping.\n";
    }
}

// 1. Add missing indexes on high-traffic tables
echo "--- 1. High-Traffic Indexes ---\n";
safeAddIndex($pdo, 'orders', 'idx_orders_status', '`status`');
safeAddIndex($pdo, 'orders', 'idx_orders_customer_phone', '`customer_phone`');
safeAddIndex($pdo, 'orders', 'idx_orders_invoice_number', '`invoice_number`');
safeAddIndex($pdo, 'orders', 'idx_orders_created_at', '`created_at`');

safeAddIndex($pdo, 'audit_logs', 'idx_audit_created_at', '`created_at`');
safeAddIndex($pdo, 'audit_logs', 'idx_audit_action', '`action`');
safeAddIndex($pdo, 'audit_logs', 'idx_audit_user_name', '`user_name`');

safeAddIndex($pdo, 'products', 'idx_products_is_active', '`is_active`');
safeAddIndex($pdo, 'products', 'idx_products_in_stock', '`in_stock`');

// 2. Create persistent OTP verification and rate-limit table
echo "\n--- 2. Creating persistent OTP Verification Table ---\n";
$createOtpTableSql = "
CREATE TABLE IF NOT EXISTS `otp_verifications` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `otp_code` VARCHAR(10) NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `last_sent_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_otp_phone_time` (`phone`, `created_at`),
    INDEX `idx_otp_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$pdo->exec($createOtpTableSql);
echo "[+] Table `otp_verifications` created or verified.\n";

// 3. Create persistent Login Attempts Table (Brute-Force Protection)
echo "\n--- 3. Creating Portal Login Attempts Table ---\n";
$createLoginAttemptsSql = "
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `identifier` VARCHAR(100) NOT NULL,
    `attempted_at` DATETIME NOT NULL,
    `is_successful` TINYINT(1) NOT NULL DEFAULT 0,
    INDEX `idx_login_ip_time` (`ip_address`, `attempted_at`),
    INDEX `idx_login_ident_time` (`identifier`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
$pdo->exec($createLoginAttemptsSql);
echo "[+] Table `login_attempts` created or verified.\n";

echo "\n=== Database Optimization Complete! ===\n";

