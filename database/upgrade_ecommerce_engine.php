<?php
// database/upgrade_ecommerce_engine.php
// Comprehensive database migration for Products, Batches, Pricing (CP/DP/MRP/Location), Stock & Logs, Multi-tier Discounts (BOGO, Tiered, Coupons), and Delivery Matrix.

require_once __DIR__ . '/../config/db.php';

try {
    echo "=== Starting E-Commerce Engine Database Migration ===\n";

    // 1. Upgrade `products` table
    $columnsToAdd = [
        "product_code" => "VARCHAR(50) NULL UNIQUE AFTER `id`",
        "sku" => "VARCHAR(100) NULL AFTER `product_code`",
        "brand" => "VARCHAR(100) NULL AFTER `title_bn`",
        "unit_type" => "VARCHAR(30) DEFAULT 'kg' AFTER `poriman_bn`",
        "is_taxable" => "TINYINT(1) DEFAULT 0 AFTER `is_hot`",
        "status" => "ENUM('active', 'inactive') DEFAULT 'active' AFTER `is_taxable`"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM `products` LIKE '{$col}'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `products` ADD COLUMN `{$col}` {$definition};");
            echo "[Products] Added column `{$col}`.\n";
        }
    }

    // Auto-generate product_code for existing products without one
    $prods = $pdo->query("SELECT id, product_code FROM products WHERE product_code IS NULL OR product_code = ''")->fetchAll();
    foreach ($prods as $p) {
        $pCode = 'PRD-' . str_pad($p['id'], 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE products SET product_code = ? WHERE id = ?")->execute([$pCode, $p['id']]);
    }

    // Upgrade `orders` table with coupon and discount fields
    $orderCols = [
        "coupon_code" => "VARCHAR(50) NULL AFTER `delivery_charge`",
        "discount_amount" => "DECIMAL(10,2) DEFAULT 0.00 AFTER `coupon_code`"
    ];
    foreach ($orderCols as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM `orders` LIKE '{$col}'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `{$col}` {$definition};");
            echo "[Orders] Added column `{$col}`.\n";
        }
    }

    // Upgrade `delivery_charge_rules` table
    $delCols = [
        "target_type" => "VARCHAR(30) DEFAULT 'website'",
        "based_on" => "VARCHAR(30) DEFAULT 'order_amount'",
        "min_value" => "DECIMAL(10,2) DEFAULT 0.00",
        "max_value" => "DECIMAL(10,2) DEFAULT 0.00",
        "fixed_amount" => "DECIMAL(10,2) DEFAULT 60.00",
        "calculation_method" => "VARCHAR(30) DEFAULT 'fixed'",
        "operator" => "VARCHAR(30) DEFAULT 'greater_than_or_equal'",
        "zone_id" => "INT NULL",
        "priority" => "INT DEFAULT 1"
    ];
    foreach ($delCols as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM `delivery_charge_rules` LIKE '{$col}'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `delivery_charge_rules` ADD COLUMN `{$col}` {$definition};");
            echo "[Delivery Rules] Added column `{$col}`.\n";
        }
    }

    // 2. Create `pricing_zones` table (for location-wise price & delivery rules)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pricing_zones` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `zone_name` VARCHAR(100) NOT NULL,
            `zone_code` VARCHAR(50) NOT NULL UNIQUE,
            `thana_list` TEXT NULL,
            `is_default` TINYINT(1) DEFAULT 0,
            `status` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[Pricing Zones] Verified table `pricing_zones`.\n";

    // Seed default pricing zones if empty
    $zoneCount = $pdo->query("SELECT COUNT(*) FROM `pricing_zones`")->fetchColumn();
    if ($zoneCount == 0) {
        $pdo->exec("
            INSERT INTO `pricing_zones` (`zone_name`, `zone_code`, `thana_list`, `is_default`, `status`) VALUES
            ('Default / All Bangladesh', 'DEFAULT_ALL', 'all, bangladesh', 1, 1),
            ('Dhaka North', 'DHK_NORTH', 'Uttara, Mirpur, Gulshan, Banani, Baridhara, Badda, Cantonment, Pallabi', 0, 1),
            ('Dhaka South', 'DHK_SOUTH', 'Dhanmondi, Motijheel, Lalbagh, Paltan, Ramna, Shahbagh, Jatrabari', 0, 1),
            ('Sub-Urban / Outside Dhaka', 'OUTSIDE_DHK', 'Savar, Gazipur, Narayanganj, Keraniganj, Chittagong, Sylhet', 0, 1);
        ");
        echo "[Pricing Zones] Seeded default zones.\n";
    }

    // 3. Create `product_batches` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_batches` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `batch_number` VARCHAR(100) NOT NULL,
            `mfg_date` DATE NULL,
            `exp_date` DATE NULL,
            `supplier_name` VARCHAR(150) NULL,
            `status` ENUM('active', 'expired', 'depleted', 'inactive') DEFAULT 'active',
            `notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (`product_id`),
            UNIQUE KEY `prod_batch_unique` (`product_id`, `batch_number`),
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[Product Batches] Verified table `product_batches`.\n";

    // 4. Create `product_pricing` table (CP, DP, MRP, Selling Price, Zone Price)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_pricing` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `batch_id` INT NULL,
            `zone_id` INT NULL DEFAULT 1,
            `district` VARCHAR(100) NULL DEFAULT 'All Bangladesh',
            `police_station` VARCHAR(100) NULL DEFAULT 'All Police Stations',
            `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `dp_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `mrp_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`product_id`),
            INDEX (`batch_id`),
            INDEX (`zone_id`),
            INDEX (`district`),
            INDEX (`police_station`),
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[Product Pricing] Verified table `product_pricing`.\n";

    // 5. Create `product_stocks` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_stocks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `batch_id` INT NULL,
            `zone_id` INT NULL DEFAULT 1,
            `opening_stock` DECIMAL(10,2) DEFAULT 0.00,
            `current_stock` DECIMAL(10,2) DEFAULT 0.00,
            `reserved_stock` DECIMAL(10,2) DEFAULT 0.00,
            `damaged_stock` DECIMAL(10,2) DEFAULT 0.00,
            `low_stock_threshold` DECIMAL(10,2) DEFAULT 5.00,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`product_id`),
            INDEX (`batch_id`),
            INDEX (`zone_id`),
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[Product Stocks] Verified table `product_stocks`.\n";

    // 6. Create `stock_logs` table (Inventory Movement Audit Ledger)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `stock_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `batch_id` INT NULL,
            `action_type` ENUM('stock_in', 'stock_out', 'sale', 'return', 'adjustment', 'damage') NOT NULL,
            `quantity` DECIMAL(10,2) NOT NULL,
            `previous_stock` DECIMAL(10,2) NOT NULL,
            `new_stock` DECIMAL(10,2) NOT NULL,
            `reference_id` VARCHAR(100) NULL,
            `notes` VARCHAR(255) NULL,
            `created_by` VARCHAR(100) NULL DEFAULT 'Admin',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (`product_id`),
            INDEX (`batch_id`),
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[Stock Logs] Verified table `stock_logs`.\n";

    // 7. Create `discount_rules` table (Fixed, %, BOGO, Tiered, Cart Spend, Coupon)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `discount_rules` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(200) NOT NULL,
            `title_bn` VARCHAR(250) NULL,
            `discount_type` ENUM('fixed_amount', 'percentage', 'bogo', 'tiered_qty', 'cart_spend', 'coupon') NOT NULL,
            `target_type` ENUM('single_product', 'category', 'batch', 'entire_cart', 'specific_products') NOT NULL DEFAULT 'single_product',
            `target_id` INT NULL,
            `target_ids_json` JSON NULL,
            `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `min_qty_required` DECIMAL(10,2) DEFAULT 1.00,
            `reward_type` ENUM('discount_amount', 'discount_percent', 'free_product', 'free_delivery') DEFAULT 'discount_amount',
            `reward_product_id` INT NULL,
            `reward_qty` DECIMAL(10,2) DEFAULT 1.00,
            `reward_discount_percent` DECIMAL(5,2) DEFAULT 100.00,
            `min_cart_amount` DECIMAL(10,2) DEFAULT 0.00,
            `coupon_code` VARCHAR(50) NULL UNIQUE,
            `max_uses_total` INT DEFAULT NULL,
            `max_uses_per_user` INT DEFAULT 1,
            `current_uses_count` INT DEFAULT 0,
            `start_datetime` DATETIME NULL,
            `end_datetime` DATETIME NULL,
            `priority` INT DEFAULT 1,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (`discount_type`),
            INDEX (`coupon_code`),
            INDEX (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[Discount Rules] Verified table `discount_rules`.\n";

    // Seed default batches, prices, and stock for all existing products if empty
    $allProducts = $pdo->query("SELECT id, title, price, old_price, stock FROM products")->fetchAll();
    foreach ($allProducts as $prod) {
        $pId = $prod['id'];
        $pPrice = floatval($prod['price']);
        $pOldPrice = floatval($prod['old_price'] ?: ($pPrice * 1.15));
        $pStock = intval($prod['stock'] ?: 50);

        // Check or create default batch
        $batchCheck = $pdo->prepare("SELECT id FROM product_batches WHERE product_id = ?");
        $batchCheck->execute([$pId]);
        $batchId = $batchCheck->fetchColumn();

        if (!$batchId) {
            $batchNum = 'BAT-' . date('Y') . '-' . str_pad($pId, 4, '0', STR_PAD_LEFT);
            $expDate = date('Y-m-d', strtotime('+1 year'));
            $insBatch = $pdo->prepare("
                INSERT INTO product_batches (product_id, batch_number, mfg_date, exp_date, supplier_name, status) 
                VALUES (?, ?, CURDATE(), ?, 'Direct Farm / Wholesale Mart', 'active')
            ");
            $insBatch->execute([$pId, $batchNum, $expDate]);
            $batchId = $pdo->lastInsertId();
        }

        // Check or create pricing (CP: 80% of selling price, DP: 90% of selling price, MRP: old_price, Selling: price)
        $priceCheck = $pdo->prepare("SELECT id FROM product_pricing WHERE product_id = ? AND batch_id = ?");
        $priceCheck->execute([$pId, $batchId]);
        if (!$priceCheck->fetch()) {
            $costPrice = round($pPrice * 0.80, 2);
            $dpPrice = round($pPrice * 0.90, 2);
            $mrpPrice = $pOldPrice > $pPrice ? $pOldPrice : round($pPrice * 1.10, 2);
            $insPrice = $pdo->prepare("
                INSERT INTO product_pricing (product_id, batch_id, zone_id, cost_price, dp_price, mrp_price, selling_price, is_active) 
                VALUES (?, ?, 1, ?, ?, ?, ?, 1)
            ");
            $insPrice->execute([$pId, $batchId, $costPrice, $dpPrice, $mrpPrice, $pPrice]);
        }

        // Check or create stock
        $stockCheck = $pdo->prepare("SELECT id FROM product_stocks WHERE product_id = ? AND batch_id = ?");
        $stockCheck->execute([$pId, $batchId]);
        if (!$stockCheck->fetch()) {
            $insStock = $pdo->prepare("
                INSERT INTO product_stocks (product_id, batch_id, zone_id, opening_stock, current_stock, low_stock_threshold) 
                VALUES (?, ?, 1, ?, ?, 5)
            ");
            $insStock->execute([$pId, $batchId, $pStock, $pStock]);
        }
    }

    echo "[Seed Data] Synchronized initial Batches, Pricing, and Stocks for " . count($allProducts) . " products.\n";

    // Seed demo discount rules if table is empty
    $discCount = $pdo->query("SELECT COUNT(*) FROM discount_rules")->fetchColumn();
    if ($discCount == 0 && count($allProducts) > 0) {
        $p1 = $allProducts[0]['id'];
        $p2 = isset($allProducts[1]) ? $allProducts[1]['id'] : $p1;

        $pdo->exec("
            INSERT INTO `discount_rules` 
            (`title`, `title_bn`, `discount_type`, `target_type`, `target_id`, `discount_value`, `min_qty_required`, `reward_type`, `reward_product_id`, `reward_qty`, `reward_discount_percent`, `min_cart_amount`, `coupon_code`, `is_active`) 
            VALUES
            ('Summer Flash Discount (৳20 Off)', 'গ্রীষ্মকালীন বিশেষ ছাড় (৳২০ ছাড়)', 'fixed_amount', 'single_product', {$p1}, 20.00, 1.00, 'discount_amount', NULL, 0, 0, 0.00, NULL, 1),
            ('Weekend 10% Off on Grocery', 'উইকেন্ড ১০% স্পেশাল ছাড়', 'percentage', 'category', 1, 10.00, 1.00, 'discount_percent', NULL, 0, 0, 0.00, NULL, 1),
            ('Buy 1 Get 1 Free Promo', 'বাই ১ গেট ১ ফ্রি অফার', 'bogo', 'single_product', {$p2}, 0.00, 1.00, 'free_product', {$p2}, 1.00, 100.00, 0.00, NULL, 1),
            ('Cart Spend ৳2000+ Get ৳100 Cashback', '২০০০ টাকার অর্ডারে ১০০ টাকা ক্যাশব্যাক', 'cart_spend', 'entire_cart', NULL, 100.00, 1.00, 'discount_amount', NULL, 0, 0, 2000.00, NULL, 1),
            ('Welcome Coupon 5% Off', 'প্রথম অর্ডারে ৫% ছাড় কুপন', 'coupon', 'entire_cart', NULL, 5.00, 1.00, 'discount_percent', NULL, 0, 0, 500.00, 'BAZAR5', 1);
        ");
        echo "[Discount Rules] Seeded sample multi-type discount rules.\n";
    }

    echo "=== Migration Finished Successfully! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
