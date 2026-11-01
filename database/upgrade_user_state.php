<?php
// database/upgrade_user_state.php
require_once __DIR__ . '/../config/db.php';

try {
    echo "Checking and adding user state columns to `users` table...\n";
    
    $cols = $pdo->query("DESCRIBE `users`")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('saved_addresses', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `saved_addresses` LONGTEXT NULL AFTER `default_location`");
        echo "Added `saved_addresses` column.\n";
    }
    
    if (!in_array('active_address_id', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `active_address_id` VARCHAR(100) NULL AFTER `saved_addresses`");
        echo "Added `active_address_id` column.\n";
    }
    
    if (!in_array('cart_data', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `cart_data` LONGTEXT NULL AFTER `active_address_id`");
        echo "Added `cart_data` column.\n";
    }
    
    if (!in_array('wishlist_data', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `wishlist_data` LONGTEXT NULL AFTER `cart_data`");
        echo "Added `wishlist_data` column.\n";
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
