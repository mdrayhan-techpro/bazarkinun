<?php
// database/create_role_permissions.php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Create role_permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `role_permissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `role_key` VARCHAR(50) NOT NULL UNIQUE,
            `role_title` VARCHAR(100) NOT NULL,
            `description` VARCHAR(255) NULL,
            `permissions` LONGTEXT NOT NULL,
            `is_system` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $allPermissions = [
        'view_products', 'create_edit_products', 'manage_categories', 'manage_pricing_stock', 'manage_discounts',
        'view_orders', 'update_order_status', 'access_pos', 'manage_invoices',
        'manage_delivery_rules', 'assign_riders', 'rider_trip_status',
        'view_customers', 'manage_staff', 'manage_roles_permissions',
        'view_financial_analytics', 'broadcast_notices', 'view_audit_logs'
    ];

    $defaultRoles = [
        [
            'role_key' => 'admin',
            'role_title' => 'Super Admin',
            'description' => 'Full unrestricted master access across all platform modules, settings, and database.',
            'permissions' => $allPermissions,
            'is_system' => 1
        ],
        [
            'role_key' => 'management',
            'role_title' => 'Management Board',
            'description' => 'Executive level oversight on financials, batch pricing, discounts, staff, and audit trails.',
            'permissions' => [
                'view_products', 'manage_pricing_stock', 'manage_discounts',
                'view_orders', 'access_pos', 'manage_invoices',
                'view_customers', 'manage_staff',
                'view_financial_analytics', 'broadcast_notices', 'view_audit_logs'
            ],
            'is_system' => 1
        ],
        [
            'role_key' => 'manager',
            'role_title' => 'Store Manager',
            'description' => 'Store operations: product catalog, pricing, inventory stock, POS counter, and order dispatch.',
            'permissions' => [
                'view_products', 'create_edit_products', 'manage_categories', 'manage_pricing_stock', 'manage_discounts',
                'view_orders', 'update_order_status', 'access_pos', 'manage_invoices',
                'assign_riders', 'view_customers'
            ],
            'is_system' => 1
        ],
        [
            'role_key' => 'rider',
            'role_title' => 'Delivery Rider',
            'description' => 'Delivery staff: assigned order pickups, customer address navigation, and trip completion.',
            'permissions' => [
                'view_orders', 'update_order_status', 'rider_trip_status'
            ],
            'is_system' => 1
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `role_permissions` (`role_key`, `role_title`, `description`, `permissions`, `is_system`)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            `role_title` = VALUES(`role_title`),
            `description` = VALUES(`description`),
            `permissions` = VALUES(`permissions`),
            `is_system` = VALUES(`is_system`)
    ");

    foreach ($defaultRoles as $r) {
        $stmt->execute([
            $r['role_key'],
            $r['role_title'],
            $r['description'],
            json_encode($r['permissions'], JSON_UNESCAPED_UNICODE),
            $r['is_system']
        ]);
    }

    echo "Table role_permissions created and seeded successfully with default roles!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
