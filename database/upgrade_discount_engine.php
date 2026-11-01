<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Expand discount_type enum to include 'combo_bundle', 'free_gift', 'free_delivery' while keeping all existing values intact
    $pdo->exec("
        ALTER TABLE `discount_rules` 
        MODIFY COLUMN `discount_type` ENUM('fixed_amount', 'percentage', 'bogo', 'tiered_qty', 'cart_spend', 'coupon', 'combo_bundle', 'free_gift', 'free_delivery') NOT NULL,
        MODIFY COLUMN `target_type` ENUM('single_product', 'category', 'batch', 'entire_cart', 'specific_products', 'multi_products') NOT NULL DEFAULT 'single_product'
    ");

    // 2. Add customer_target & payment_method_target columns if not exist
    $cols = $pdo->query("SHOW COLUMNS FROM discount_rules")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('customer_target', $cols)) {
        $pdo->exec("ALTER TABLE `discount_rules` ADD COLUMN `customer_target` VARCHAR(50) NOT NULL DEFAULT 'all' AFTER `min_cart_amount`");
    }
    if (!in_array('payment_method_target', $cols)) {
        $pdo->exec("ALTER TABLE `discount_rules` ADD COLUMN `payment_method_target` VARCHAR(50) NOT NULL DEFAULT 'all' AFTER `customer_target`");
    }
    if (!in_array('badge_text', $cols)) {
        $pdo->exec("ALTER TABLE `discount_rules` ADD COLUMN `badge_text` VARCHAR(100) NULL AFTER `title_bn`");
    }
    if (!in_array('max_reward_limit', $cols)) {
        $pdo->exec("ALTER TABLE `discount_rules` ADD COLUMN `max_reward_limit` INT NULL DEFAULT NULL AFTER `reward_discount_percent`");
    }

    echo "Discount Rules schema upgraded successfully!\n";
} catch (Exception $e) {
    echo "Upgrade Error: " . $e->getMessage() . "\n";
}
