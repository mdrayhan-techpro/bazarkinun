<?php
// database/upgrade_centralized_rbac.php
require_once __DIR__ . '/../config/db.php';

echo "=== CENTRALIZED RBAC MIGRATION SCRIPT ===\n";

try {
    // 1. Create `permissions` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `permissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `legacy_slug` VARCHAR(100) NULL,
            `module` VARCHAR(100) NOT NULL,
            `submodule` VARCHAR(100) NULL DEFAULT '',
            `description` VARCHAR(255) NULL,
            `category` ENUM('page', 'action', 'financial', 'config') NOT NULL DEFAULT 'action',
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_perm_slug` (`slug`),
            INDEX `idx_perm_legacy` (`legacy_slug`),
            INDEX `idx_perm_module` (`module`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[✓] Verified `permissions` table.\n";

    // 2. Create `roles` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `role_key` VARCHAR(50) NOT NULL UNIQUE,
            `role_title` VARCHAR(100) NOT NULL,
            `description` VARCHAR(255) NULL,
            `is_system` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_role_key` (`role_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[✓] Verified `roles` table.\n";

    // 3. Create `role_permission_map` pivot table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `role_permission_map` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `role_id` INT NOT NULL,
            `permission_id` INT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_role_perm` (`role_id`, `permission_id`),
            INDEX `idx_rpm_role` (`role_id`),
            INDEX `idx_rpm_perm` (`permission_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[✓] Verified `role_permission_map` pivot table.\n";

    $pdo->beginTransaction();

    // 4. Seed Permissions Table
    $allPermissions = [
        [
            'name' => 'View Dashboard & Analytics',
            'slug' => 'dashboard.view',
            'legacy_slug' => 'view_financial_analytics',
            'module' => 'Dashboard & Analytics',
            'submodule' => 'Summary',
            'description' => 'View summary dashboard, order stats, and delivery zone breakdown',
            'category' => 'page',
        ],
        [
            'name' => 'View Staff Directory',
            'slug' => 'users.view',
            'legacy_slug' => 'view_staff',
            'module' => 'Users & Staff',
            'submodule' => 'Staff Directory',
            'description' => 'View list of internal staff, riders, and managers',
            'category' => 'page',
        ],
        [
            'name' => 'Create Staff / User',
            'slug' => 'users.create',
            'legacy_slug' => 'create_staff',
            'module' => 'Users & Staff',
            'submodule' => 'Staff Directory',
            'description' => 'Add new staff, rider, or manager accounts',
            'category' => 'action',
        ],
        [
            'name' => 'Edit Staff / User',
            'slug' => 'users.edit',
            'legacy_slug' => 'edit_staff',
            'module' => 'Users & Staff',
            'submodule' => 'Staff Directory',
            'description' => 'Edit profile, password, and assigned locations of staff',
            'category' => 'action',
        ],
        [
            'name' => 'Toggle Staff Status',
            'slug' => 'users.status',
            'legacy_slug' => 'toggle_staff_status',
            'module' => 'Users & Staff',
            'submodule' => 'Staff Directory',
            'description' => 'Lock or unlock staff account access',
            'category' => 'action',
        ],
        [
            'name' => 'Delete Staff Account',
            'slug' => 'users.delete',
            'legacy_slug' => 'delete_staff',
            'module' => 'Users & Staff',
            'submodule' => 'Staff Directory',
            'description' => 'Permanently delete staff or rider account',
            'category' => 'action',
        ],
        [
            'name' => 'Assign Delivery Zones to Rider',
            'slug' => 'users.assign_location',
            'legacy_slug' => 'edit_staff',
            'module' => 'Users & Staff',
            'submodule' => 'Staff Directory',
            'description' => 'Assign specific districts and thanas to delivery riders',
            'category' => 'action',
        ],
        [
            'name' => 'Manage Roles & Permissions',
            'slug' => 'roles.manage',
            'legacy_slug' => 'manage_roles_permissions',
            'module' => 'RBAC & Roles',
            'submodule' => 'Role Hub',
            'description' => 'Create, edit roles, and assign permissions matrix',
            'category' => 'config',
        ],
        [
            'name' => 'View Customer Directory',
            'slug' => 'customers.view',
            'legacy_slug' => 'view_customers',
            'module' => 'Customers',
            'submodule' => 'Directory',
            'description' => 'View customer registered accounts and purchase histories',
            'category' => 'page',
        ],
        [
            'name' => 'Edit Customer Information',
            'slug' => 'customers.edit',
            'legacy_slug' => 'edit_customers',
            'module' => 'Customers',
            'submodule' => 'Directory',
            'description' => 'Modify customer contact details and delivery addresses',
            'category' => 'action',
        ],
        [
            'name' => 'Delete Customer Account',
            'slug' => 'customers.delete',
            'legacy_slug' => 'delete_customers',
            'module' => 'Customers',
            'submodule' => 'Directory',
            'description' => 'Remove or block customer accounts from portal',
            'category' => 'action',
        ],
        [
            'name' => 'View Categories',
            'slug' => 'categories.view',
            'legacy_slug' => 'manage_categories',
            'module' => 'Categories',
            'submodule' => 'Category List',
            'description' => 'View catalog categories',
            'category' => 'page',
        ],
        [
            'name' => 'Create Category',
            'slug' => 'categories.create',
            'legacy_slug' => 'manage_categories',
            'module' => 'Categories',
            'submodule' => 'Category List',
            'description' => 'Create new product category',
            'category' => 'action',
        ],
        [
            'name' => 'Edit Category',
            'slug' => 'categories.edit',
            'legacy_slug' => 'manage_categories',
            'module' => 'Categories',
            'submodule' => 'Category List',
            'description' => 'Modify category title, image, and slug',
            'category' => 'action',
        ],
        [
            'name' => 'Delete Category',
            'slug' => 'categories.delete',
            'legacy_slug' => 'manage_categories',
            'module' => 'Categories',
            'submodule' => 'Category List',
            'description' => 'Delete category from catalog',
            'category' => 'action',
        ],
        [
            'name' => 'View Products Catalog',
            'slug' => 'products.view',
            'legacy_slug' => 'view_products',
            'module' => 'Products & Catalog',
            'submodule' => 'Catalog',
            'description' => 'View products, prices, and stock counts',
            'category' => 'page',
        ],
        [
            'name' => 'Create Product',
            'slug' => 'products.create',
            'legacy_slug' => 'create_products',
            'module' => 'Products & Catalog',
            'submodule' => 'Catalog',
            'description' => 'Add new products with prices and specifications',
            'category' => 'action',
        ],
        [
            'name' => 'Edit Product',
            'slug' => 'products.edit',
            'legacy_slug' => 'edit_products',
            'module' => 'Products & Catalog',
            'submodule' => 'Catalog',
            'description' => 'Update product price, title, image, and status',
            'category' => 'action',
        ],
        [
            'name' => 'Delete Product',
            'slug' => 'products.delete',
            'legacy_slug' => 'delete_products',
            'module' => 'Products & Catalog',
            'submodule' => 'Catalog',
            'description' => 'Permanently delete product from system',
            'category' => 'action',
        ],
        [
            'name' => 'View Inventory Batches',
            'slug' => 'inventory.batches_view',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Batches',
            'description' => 'View stock batches, manufacturing & expiry dates',
            'category' => 'page',
        ],
        [
            'name' => 'Create Inventory Batch',
            'slug' => 'inventory.batches_create',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Batches',
            'description' => 'Stock in new batch with supplier & purchase rate',
            'category' => 'action',
        ],
        [
            'name' => 'Delete Inventory Batch',
            'slug' => 'inventory.batches_delete',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Batches',
            'description' => 'Remove erroneous batch record',
            'category' => 'action',
        ],
        [
            'name' => 'View Location Pricing',
            'slug' => 'inventory.pricing_view',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Pricing',
            'description' => 'View customized location/branch pricing and DP wholesale rates',
            'category' => 'page',
        ],
        [
            'name' => 'Save Location Pricing',
            'slug' => 'inventory.pricing_save',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Pricing',
            'description' => 'Configure MRP and Dealer/Wholesale rates per zone',
            'category' => 'action',
        ],
        [
            'name' => 'Delete Location Pricing',
            'slug' => 'inventory.pricing_delete',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Pricing',
            'description' => 'Remove custom location pricing rule',
            'category' => 'action',
        ],
        [
            'name' => 'View Stocks & Balances',
            'slug' => 'inventory.stocks_view',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Stocks',
            'description' => 'Monitor real-time warehouse stock balances',
            'category' => 'page',
        ],
        [
            'name' => 'Adjust Stock Balance',
            'slug' => 'inventory.stock_adjust',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Stocks',
            'description' => 'Perform stock adjustment (Addition, Deduction, Damage / Waste)',
            'category' => 'action',
        ],
        [
            'name' => 'View Stock Movement Audit Logs',
            'slug' => 'inventory.stock_logs_view',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Stocks',
            'description' => 'View complete ledger of all stock in/out operations',
            'category' => 'action',
        ],
        [
            'name' => 'Manage Inventory Zones',
            'slug' => 'inventory.zones_manage',
            'legacy_slug' => 'manage_pricing_stock',
            'module' => 'Inventory & Stocks',
            'submodule' => 'Stocks',
            'description' => 'Add and configure warehouse locations and hubs',
            'category' => 'config',
        ],
        [
            'name' => 'View Orders & Invoices',
            'slug' => 'orders.view',
            'legacy_slug' => 'view_orders',
            'module' => 'Orders & Sales',
            'submodule' => 'Orders List',
            'description' => 'View customer orders, details, and delivery statuses',
            'category' => 'page',
        ],
        [
            'name' => 'Update Order Status',
            'slug' => 'orders.status_update',
            'legacy_slug' => 'update_order_status',
            'module' => 'Orders & Sales',
            'submodule' => 'Orders List',
            'description' => 'Transition order between Pending, Processing, Delivering, Completed',
            'category' => 'action',
        ],
        [
            'name' => 'Assign Delivery Rider',
            'slug' => 'orders.assign_rider',
            'legacy_slug' => 'assign_riders',
            'module' => 'Orders & Sales',
            'submodule' => 'Orders List',
            'description' => 'Dispatch and assign delivery riders to orders',
            'category' => 'action',
        ],
        [
            'name' => 'Edit Order Items & Grocery Lists',
            'slug' => 'orders.edit_items',
            'legacy_slug' => 'edit_order_items',
            'module' => 'Orders & Sales',
            'submodule' => 'Orders List',
            'description' => 'Modify ordered products, handwritten list items, quantities, and prices',
            'category' => 'action',
        ],
        [
            'name' => 'Manage Invoices & Receipts',
            'slug' => 'invoices.manage',
            'legacy_slug' => 'manage_invoices',
            'module' => 'Orders & Sales',
            'submodule' => 'Invoices',
            'description' => 'Create, edit, finalize, and print official invoices',
            'category' => 'action',
        ],
        [
            'name' => 'Cancel / Reject Orders',
            'slug' => 'orders.cancel',
            'legacy_slug' => 'cancel_orders',
            'module' => 'Orders & Sales',
            'submodule' => 'Orders List',
            'description' => 'Cancel orders and automatically restore inventory stock',
            'category' => 'action',
        ],
        [
            'name' => 'POS Billing & Counter Sales',
            'slug' => 'pos.access',
            'legacy_slug' => 'access_pos',
            'module' => 'Orders & Sales',
            'submodule' => 'POS Counter',
            'description' => 'Access POS cash counter sales screen',
            'category' => 'action',
        ],
        [
            'name' => 'View DP Wholesale Sales',
            'slug' => 'dp_sales.view',
            'legacy_slug' => 'view_orders',
            'module' => 'DP Wholesale Sales',
            'submodule' => 'Dealer Invoices',
            'description' => 'View wholesale invoices and dealership customer accounts',
            'category' => 'page',
        ],
        [
            'name' => 'Create DP Wholesale Sale',
            'slug' => 'dp_sales.create',
            'legacy_slug' => 'access_pos',
            'module' => 'DP Wholesale Sales',
            'submodule' => 'Dealer Invoices',
            'description' => 'Create wholesale bulk sales at DP dealer rates',
            'category' => 'action',
        ],
        [
            'name' => 'Delete DP Wholesale Sale',
            'slug' => 'dp_sales.delete',
            'legacy_slug' => 'cancel_orders',
            'module' => 'DP Wholesale Sales',
            'submodule' => 'Dealer Invoices',
            'description' => 'Remove or void wholesale invoice',
            'category' => 'action',
        ],
        [
            'name' => 'Rider Delivery Trip Panel',
            'slug' => 'rider.trip_panel',
            'legacy_slug' => 'rider_trip_status',
            'module' => 'Rider Logistics',
            'submodule' => 'Staff Panel',
            'description' => 'Access rider trip operations, accept orders, and mark delivered',
            'category' => 'action',
        ],
        [
            'name' => 'Manage Delivery Charge Rules',
            'slug' => 'delivery_rules.manage',
            'legacy_slug' => 'manage_delivery_rules',
            'module' => 'Delivery & Discounts',
            'submodule' => 'Delivery Rules',
            'description' => 'Configure location-based delivery charges, min order amounts, and free delivery thresholds',
            'category' => 'config',
        ],
        [
            'name' => 'Manage Discounts & Coupons',
            'slug' => 'discounts.manage',
            'legacy_slug' => 'manage_discounts',
            'module' => 'Delivery & Discounts',
            'submodule' => 'Discount Rules',
            'description' => 'Create and configure coupon codes, BOGO offers, and percentage discounts',
            'category' => 'config',
        ],
        [
            'name' => 'Manage Contact & Social Links',
            'slug' => 'portal.contacts',
            'legacy_slug' => 'manage_contacts',
            'module' => 'Portal Information',
            'submodule' => 'Contact Settings',
            'description' => 'Update store phone, email, hotline, and social profiles',
            'category' => 'config',
        ],
        [
            'name' => 'Manage Legal Policies',
            'slug' => 'portal.policies',
            'legacy_slug' => 'manage_policies',
            'module' => 'Portal Information',
            'submodule' => 'Policies',
            'description' => 'Edit privacy policy, terms of service, and return policies',
            'category' => 'config',
        ],
        [
            'name' => 'Manage Happy Customers',
            'slug' => 'portal.happy_customers',
            'legacy_slug' => 'manage_happy_customers',
            'module' => 'Portal Information',
            'submodule' => 'Reviews',
            'description' => 'Add, approve, and manage customer review photos and testimonials',
            'category' => 'config',
        ],
        [
            'name' => 'Manage Broadcast Notices',
            'slug' => 'portal.broadcast',
            'legacy_slug' => 'manage_broadcast',
            'module' => 'Portal Information',
            'submodule' => 'Broadcast',
            'description' => 'Publish and manage alert banners and store announcements',
            'category' => 'config',
        ],
        [
            'name' => 'Manage SMS Gateway',
            'slug' => 'portal.sms_gateway',
            'legacy_slug' => 'manage_sms_gateway',
            'module' => 'Portal Information',
            'submodule' => 'SMS Gateway',
            'description' => 'Configure SMS provider, API credentials, and send test SMS',
            'category' => 'config',
        ],
        [
            'name' => 'Manage Payment Methods',
            'slug' => 'portal.payment_methods',
            'legacy_slug' => 'manage_payment_methods',
            'module' => 'Portal Information',
            'submodule' => 'Payment Gateways',
            'description' => 'Configure bKash, Nagad, Rocket merchant numbers and COD settings',
            'category' => 'config',
        ],
        [
            'name' => 'View System Security & Audit Logs',
            'slug' => 'audit_logs.view',
            'legacy_slug' => 'view_audit_logs',
            'module' => 'Security & Financial',
            'submodule' => 'Audit Trail',
            'description' => 'Review login logs, staff actions, and price modification history',
            'category' => 'action',
        ],
        [
            'name' => 'View Financial Analytics & Net Revenue',
            'slug' => 'financial.view_analytics',
            'legacy_slug' => 'view_financial_analytics',
            'module' => 'Security & Financial',
            'submodule' => 'Financial Reports',
            'description' => 'View total sales volume, net profits, and financial analytics',
            'category' => 'financial',
        ],
        [
            'name' => 'View Cost Price & Gross Profit Margins',
            'slug' => 'financial.view_cost_profit',
            'legacy_slug' => 'view_cost_profit',
            'module' => 'Security & Financial',
            'submodule' => 'Margins & Profit',
            'description' => 'Unmask confidential purchase cost rates and gross profit margins',
            'category' => 'financial',
        ],
    ];

    $permStmt = $pdo->prepare("
        INSERT INTO `permissions` (`name`, `slug`, `legacy_slug`, `module`, `submodule`, `description`, `category`, `status`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            `name` = VALUES(`name`),
            `legacy_slug` = VALUES(`legacy_slug`),
            `module` = VALUES(`module`),
            `submodule` = VALUES(`submodule`),
            `description` = VALUES(`description`),
            `category` = VALUES(`category`),
            `status` = VALUES(`status`),
            `updated_at` = NOW()
    ");

    foreach ($allPermissions as $p) {
        $permStmt->execute([
            $p['name'],
            $p['slug'],
            $p['legacy_slug'],
            $p['module'],
            $p['submodule'],
            $p['description'],
            $p['category'],
            'active'
        ]);
    }
    echo "[✓] Seeded " . count($allPermissions) . " permissions into `permissions` table.\n";

    // 5. Seed Roles from existing `role_permissions`
    $existingRoles = $pdo->query("SELECT * FROM `role_permissions`")->fetchAll(PDO::FETCH_ASSOC);
    $roleInsertStmt = $pdo->prepare("
        INSERT INTO `roles` (`role_key`, `role_title`, `description`, `is_system`, `created_at`, `updated_at`)
        VALUES (?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            `role_title` = VALUES(`role_title`),
            `description` = VALUES(`description`),
            `is_system` = VALUES(`is_system`),
            `updated_at` = NOW()
    ");

    foreach ($existingRoles as $er) {
        $roleInsertStmt->execute([
            $er['role_key'],
            $er['role_title'],
            $er['description'] ?? '',
            $er['is_system'] ?? 0
        ]);
    }
    echo "[✓] Synced roles into `roles` table.\n";

    // Build permission lookup cache: [slug => id, legacy_slug => id]
    $permRows = $pdo->query("SELECT id, slug, legacy_slug FROM `permissions`")->fetchAll(PDO::FETCH_ASSOC);
    $permIdBySlug = [];
    $permIdByLegacy = [];
    foreach ($permRows as $pr) {
        $permIdBySlug[$pr['slug']] = $pr['id'];
        if (!empty($pr['legacy_slug'])) {
            $permIdByLegacy[$pr['legacy_slug']][] = $pr['id'];
        }
    }

    // 6. Seed `role_permission_map`
    $roleRows = $pdo->query("SELECT id, role_key FROM `roles`")->fetchAll(PDO::FETCH_ASSOC);
    $roleIdByKey = [];
    foreach ($roleRows as $rr) {
        $roleIdByKey[$rr['role_key']] = $rr['id'];
    }

    $rpmStmt = $pdo->prepare("
        INSERT IGNORE INTO `role_permission_map` (`role_id`, `permission_id`, `created_at`)
        VALUES (?, ?, NOW())
    ");

    foreach ($existingRoles as $er) {
        $rKey = $er['role_key'];
        if (!isset($roleIdByKey[$rKey])) continue;
        $rId = $roleIdByKey[$rKey];

        $rawPerms = json_decode($er['permissions'] ?? '[]', true) ?: [];

        // If admin, grant all active permissions
        if ($rKey === 'admin') {
            foreach ($permRows as $pr) {
                $rpmStmt->execute([$rId, $pr['id']]);
            }
        } else {
            foreach ($rawPerms as $pSlug) {
                // If it's a direct standard slug
                if (isset($permIdBySlug[$pSlug])) {
                    $rpmStmt->execute([$rId, $permIdBySlug[$pSlug]]);
                }
                // If it matches a legacy slug
                if (isset($permIdByLegacy[$pSlug])) {
                    foreach ($permIdByLegacy[$pSlug] as $pId) {
                        $rpmStmt->execute([$rId, $pId]);
                    }
                }
            }
        }
    }
    echo "[✓] Populated `role_permission_map` pivot mappings.\n";

    $pdo->commit();
    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY WITH ZERO DATA LOSS! ===\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}
