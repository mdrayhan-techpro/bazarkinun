<?php
// database/upgrade_entities.php
require_once __DIR__ . '/../config/db.php';

echo "--- Upgrading Schema for 3-Entity Flow (List, Order, Invoice) ---\n";

// 1. Add invoice_number column to orders if missing
$colCheck = $pdo->query("SHOW COLUMNS FROM orders LIKE 'invoice_number'")->fetch();
if (!$colCheck) {
    $pdo->exec("ALTER TABLE orders ADD COLUMN invoice_number VARCHAR(50) NULL AFTER order_number;");
    echo "[1/4] Added `invoice_number` column to `orders` table.\n";
} else {
    echo "[1/4] `invoice_number` column already exists in `orders`.\n";
}

// 2. Expand status column in orders table
$pdo->exec("ALTER TABLE orders MODIFY COLUMN `status` VARCHAR(50) DEFAULT 'pending';");
echo "[2/4] `status` column updated to VARCHAR(50) to support all workflow statuses including 'accepted'.\n";

// 3. Add list_code column to custom_lists if missing
$colCheck2 = $pdo->query("SHOW COLUMNS FROM custom_lists LIKE 'list_code'")->fetch();
if (!$colCheck2) {
    $pdo->exec("ALTER TABLE custom_lists ADD COLUMN list_code VARCHAR(50) NULL AFTER user_id;");
    echo "[3/4] Added `list_code` column to `custom_lists` table.\n";
} else {
    echo "[3/4] `list_code` column already exists in `custom_lists`.\n";
}

// 3.1 Add customer_address column to custom_lists if missing
$colCheckAddr = $pdo->query("SHOW COLUMNS FROM custom_lists LIKE 'customer_address'")->fetch();
if (!$colCheckAddr) {
    $pdo->exec("ALTER TABLE custom_lists ADD COLUMN customer_address TEXT NULL AFTER customer_phone;");
    echo "[3.1] Added `customer_address` column to `custom_lists` table.\n";
}

// 4. Update any existing custom_lists missing list_code
$stmt = $pdo->query("SELECT cl.id, cl.user_id, cl.customer_name, cl.customer_phone, u.name as u_name, u.phone as u_phone FROM custom_lists cl LEFT JOIN users u ON cl.user_id = u.id WHERE cl.list_code IS NULL OR cl.list_code = ''");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$seqMap = [];
foreach ($rows as $r) {
    $phone = $r['customer_phone'] ?: $r['u_phone'] ?: '01919668960';
    $name = $r['customer_name'] ?: $r['u_name'] ?: 'Daud';
    $cleanPhone = preg_replace('/\D/', '', $phone);
    $phone6 = strlen($cleanPhone) >= 6 ? substr($cleanPhone, -6) : str_pad($cleanPhone, 6, '0', STR_PAD_LEFT);
    $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
    $name2 = strlen($cleanName) >= 2 ? substr($cleanName, 0, 2) : str_pad($cleanName, 2, 'X');
    
    $key = "{$phone6}-{$name2}";
    $seqMap[$key] = ($seqMap[$key] ?? 0) + 1;
    $code = "LST-{$phone6}-{$name2}-{$seqMap[$key]}";
    
    $upd = $pdo->prepare("UPDATE custom_lists SET list_code = ? WHERE id = ?");
    $upd->execute([$code, $r['id']]);
}
echo "[4/4] Seeded list codes for existing custom lists.\n";

echo "--- Upgrade Completed Successfully ---\n";
