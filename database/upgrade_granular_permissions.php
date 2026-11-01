<?php
// database/upgrade_granular_permissions.php
require_once __DIR__ . '/../config/db.php';

echo "Updating role_permissions table with granular permissions...\n";

$allPerms = [
    // Staff & Riders
    'view_staff', 'create_staff', 'edit_staff', 'toggle_staff_status', 'delete_staff',
    // Portal Info & Config
    'manage_contacts', 'manage_policies', 'manage_happy_customers', 'manage_broadcast', 'manage_sms_gateway', 'manage_payment_methods',
    // Catalog & Products
    'view_products', 'create_products', 'edit_products', 'delete_products', 'manage_pricing_stock', 'manage_discounts', 'manage_categories',
    // Orders & Sales
    'view_orders', 'update_order_status', 'assign_riders', 'edit_order_items', 'cancel_orders', 'access_pos', 'manage_invoices',
    // Customers & Logistics
    'view_customers', 'edit_customers', 'delete_customers', 'manage_delivery_rules', 'rider_trip_status',
    // Security, Finance & Roles
    'view_financial_analytics', 'view_cost_profit', 'view_audit_logs', 'manage_roles_permissions'
];

$roleMatrix = [
    'admin' => [
        'title' => 'Super Admin',
        'desc' => 'Full Master Privileges across entire platform with complete uninhibited control.',
        'perms' => $allPerms,
        'is_system' => 1
    ],
    'management' => [
        'title' => 'Management Board',
        'desc' => 'Executive level oversight, pricing, financials, audit logs and customer relations.',
        'perms' => [
            'view_staff', 'view_products', 'manage_pricing_stock', 'manage_discounts',
            'view_orders', 'access_pos', 'manage_invoices', 'view_customers',
            'view_financial_analytics', 'view_cost_profit', 'manage_broadcast', 'manage_happy_customers', 'view_audit_logs'
        ],
        'is_system' => 1
    ],
    'manager' => [
        'title' => 'Store Manager',
        'desc' => 'Full daily branch operations, products, stock batching, orders and customer billing.',
        'perms' => [
            'view_products', 'create_products', 'edit_products', 'manage_pricing_stock', 'manage_discounts', 'manage_categories',
            'view_orders', 'update_order_status', 'assign_riders', 'edit_order_items', 'access_pos', 'manage_invoices',
            'view_customers'
        ],
        'is_system' => 1
    ],
    'operation' => [
        'title' => 'Operation & Support',
        'desc' => 'Order processing, catalog management, customer reviews, and rider assignments.',
        'perms' => [
            'view_products', 'create_products', 'edit_products', 'manage_categories', 'manage_pricing_stock', 'manage_discounts',
            'view_orders', 'update_order_status', 'assign_riders', 'edit_order_items',
            'manage_happy_customers'
        ],
        'is_system' => 1
    ],
    'rider' => [
        'title' => 'Delivery Rider',
        'desc' => 'Dedicated delivery logistics panel, trip status dispatch, and invoice verification.',
        'perms' => [
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

foreach ($roleMatrix as $key => $data) {
    $json = json_encode($data['perms'], JSON_UNESCAPED_UNICODE);
    $stmt->execute([$key, $data['title'], $data['desc'], $json, $data['is_system']]);
    echo "Configured role: {$key} (" . count($data['perms']) . " permissions)\n";
}

echo "Granular permissions database upgrade completed successfully!\n";

