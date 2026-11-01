<?php
// database/upgrade_multilocation_stocks.php
require_once __DIR__ . '/../config/db.php';

try {
    $stockCols = $pdo->query("SHOW COLUMNS FROM `product_stocks`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('district', $stockCols)) {
        $pdo->exec("ALTER TABLE `product_stocks` ADD COLUMN `district` VARCHAR(100) NOT NULL DEFAULT 'All Bangladesh' AFTER `zone_id`");
    }
    if (!in_array('police_station', $stockCols)) {
        $pdo->exec("ALTER TABLE `product_stocks` ADD COLUMN `police_station` VARCHAR(100) NOT NULL DEFAULT 'All Police Stations' AFTER `district`");
    }

    $logCols = $pdo->query("SHOW COLUMNS FROM `stock_logs`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stock_id', $logCols)) {
        $pdo->exec("ALTER TABLE `stock_logs` ADD COLUMN `stock_id` INT NULL AFTER `batch_id`");
    }
    if (!in_array('district', $logCols)) {
        $pdo->exec("ALTER TABLE `stock_logs` ADD COLUMN `district` VARCHAR(100) NOT NULL DEFAULT 'All Bangladesh' AFTER `stock_id`");
    }
    if (!in_array('police_station', $logCols)) {
        $pdo->exec("ALTER TABLE `stock_logs` ADD COLUMN `police_station` VARCHAR(100) NOT NULL DEFAULT 'All Police Stations' AFTER `district`");
    }

    // Ensure all existing product_pricing locations have a corresponding product_stocks row
    $pricingRows = $pdo->query("
        SELECT DISTINCT product_id, batch_id, zone_id, district, police_station 
        FROM product_pricing
    ")->fetchAll();

    foreach ($pricingRows as $pr) {
        $chk = $pdo->prepare("
            SELECT id FROM product_stocks 
            WHERE product_id = ? 
              AND (batch_id = ? OR (batch_id IS NULL AND ? IS NULL))
              AND district = ? 
              AND police_station = ?
            LIMIT 1
        ");
        $chk->execute([$pr['product_id'], $pr['batch_id'], $pr['batch_id'], $pr['district'], $pr['police_station']]);
        $exists = $chk->fetch();

        if (!$exists) {
            $ins = $pdo->prepare("
                INSERT INTO product_stocks 
                (product_id, batch_id, zone_id, district, police_station, opening_stock, current_stock, low_stock_threshold)
                VALUES (?, ?, ?, ?, ?, 0.00, 0.00, 5.00)
            ");
            $ins->execute([
                $pr['product_id'],
                $pr['batch_id'],
                $pr['zone_id'] ?: 1,
                $pr['district'],
                $pr['police_station']
            ]);
        }
    }

    echo "Multi-location stock schema upgraded & synchronized successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
