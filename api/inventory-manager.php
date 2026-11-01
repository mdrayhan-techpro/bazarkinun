<?php
// api/inventory-manager.php
// Full Management API for Product Batches, Multi-Zone Pricing (CP/DP/MRP/Sale), and Stock Ledger

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// Enforce Inventory Management authorization
$authInventory = requirePortalAuth(['admin', 'author', 'manager', 'management', 'operation']);

try {
    // 1. Get All Batches with product info
    if ($action === 'get_batches') {
        requirePermission('inventory.batches_view');
        $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $sql = "
            SELECT 
                b.*,
                p.product_code,
                p.title as product_title,
                p.title_bn as product_title_bn,
                (SELECT current_stock FROM product_stocks WHERE batch_id = b.id LIMIT 1) as current_stock,
                (SELECT selling_price FROM product_pricing WHERE batch_id = b.id LIMIT 1) as selling_price,
                (SELECT cost_price FROM product_pricing WHERE batch_id = b.id LIMIT 1) as cost_price
            FROM product_batches b
            JOIN products p ON b.product_id = p.id
        ";
        if ($productId > 0) {
            $sql .= " WHERE b.product_id = " . $productId;
        }
        $sql .= " ORDER BY b.id DESC";

        $batches = $pdo->query($sql)->fetchAll();
        jsonResponse(['status' => 'success', 'batches' => $batches]);
    }

    // 2. Save / Update Batch
    if ($action === 'save_batch') {
        requirePermission('inventory.batches_create');
        $batchId = !empty($input['id']) ? intval($input['id']) : null;
        $productId = intval($input['product_id'] ?? 0);
        $batchNumber = trim($input['batch_number'] ?? '');
        $mfgDate = !empty($input['mfg_date']) ? $input['mfg_date'] : null;
        $expDate = !empty($input['exp_date']) ? $input['exp_date'] : null;
        $supplierName = trim($input['supplier_name'] ?? '');
        $status = $input['status'] ?? 'active';
        $notes = trim($input['notes'] ?? '');

        if (!$productId || empty($batchNumber)) {
            jsonResponse(['status' => 'error', 'message' => 'Product and Batch Number are required!'], 400);
        }

        if ($batchId) {
            $stmt = $pdo->prepare("
                UPDATE product_batches 
                SET product_id = ?, batch_number = ?, mfg_date = ?, exp_date = ?, supplier_name = ?, status = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$productId, $batchNumber, $mfgDate, $expDate, $supplierName, $status, $notes, $batchId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO product_batches (product_id, batch_number, mfg_date, exp_date, supplier_name, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $batchNumber, $mfgDate, $expDate, $supplierName, $status, $notes]);
            $batchId = $pdo->lastInsertId();

            // Auto-create default pricing and stock entry for new batch
            $prod = $pdo->query("SELECT price, old_price FROM products WHERE id = {$productId}")->fetch();
            $pPrice = floatval($prod['price'] ?? 0);
            $pOldPrice = floatval($prod['old_price'] ?? ($pPrice * 1.15));

            $pdo->prepare("
                INSERT INTO product_pricing (product_id, batch_id, zone_id, cost_price, dp_price, mrp_price, selling_price, is_active)
                VALUES (?, ?, 1, ?, ?, ?, ?, 1)
            ")->execute([$productId, $batchId, round($pPrice * 0.8, 2), round($pPrice * 0.9, 2), $pOldPrice, $pPrice]);

            $pdo->prepare("
                INSERT INTO product_stocks (product_id, batch_id, zone_id, opening_stock, current_stock, low_stock_threshold)
                VALUES (?, ?, 1, 0, 0, 5)
            ")->execute([$productId, $batchId]);
        }

        jsonResponse(['status' => 'success', 'message' => 'Batch saved successfully!', 'batch_id' => $batchId]);
    }

    // 3. Delete Batch
    if ($action === 'delete_batch') {
        requirePermission('inventory.batches_delete');
        $batchId = intval($input['id'] ?? 0);
        if (!$batchId) {
            jsonResponse(['status' => 'error', 'message' => 'Batch ID required'], 400);
        }
        $pdo->prepare("DELETE FROM product_batches WHERE id = ?")->execute([$batchId]);
        jsonResponse(['status' => 'success', 'message' => 'Batch deleted successfully!']);
    }

    // 4. Get Pricing Table (All products & batches with CP, DP, MRP, Selling Price, Zone)
    if ($action === 'get_pricing') {
        requirePermission('inventory.pricing_view');
        $sql = "
            SELECT 
                pr.*,
                p.product_code,
                p.title as product_title,
                p.title_bn as product_title_bn,
                p.poriman,
                b.batch_number,
                b.exp_date,
                z.zone_name,
                z.zone_code
            FROM product_pricing pr
            JOIN products p ON pr.product_id = p.id
            LEFT JOIN product_batches b ON pr.batch_id = b.id
            LEFT JOIN pricing_zones z ON pr.zone_id = z.id
            ORDER BY p.id DESC, pr.id DESC
        ";
        $pricing = $pdo->query($sql)->fetchAll();
        jsonResponse(['status' => 'success', 'pricing' => $pricing]);
    }

    // 5. Save / Update Pricing
    if ($action === 'save_pricing') {
        requirePermission('inventory.pricing_save');
        $pricingId = !empty($input['id']) ? intval($input['id']) : null;
        $productId = intval($input['product_id'] ?? 0);
        $batchId = !empty($input['batch_id']) ? intval($input['batch_id']) : null;
        $zoneId = !empty($input['zone_id']) ? intval($input['zone_id']) : 1;
        $district = !empty($input['district']) ? trim($input['district']) : 'All Bangladesh';
        $policeStation = !empty($input['police_station']) ? trim($input['police_station']) : 'All Police Stations';
        $costPrice = floatval($input['cost_price'] ?? 0);
        $dpPrice = floatval($input['dp_price'] ?? 0);
        $mrpPrice = floatval($input['mrp_price'] ?? 0);
        $sellingPrice = floatval($input['selling_price'] ?? 0);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (!$productId || $sellingPrice <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Product and a valid Selling Price (> 0) are required!'], 400);
        }

        if ($pricingId) {
            $stmt = $pdo->prepare("
                UPDATE product_pricing
                SET product_id = ?, batch_id = ?, zone_id = ?, district = ?, police_station = ?, cost_price = ?, dp_price = ?, mrp_price = ?, selling_price = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$productId, $batchId, $zoneId, $district, $policeStation, $costPrice, $dpPrice, $mrpPrice, $sellingPrice, $isActive, $pricingId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO product_pricing (product_id, batch_id, zone_id, district, police_station, cost_price, dp_price, mrp_price, selling_price, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $batchId, $zoneId, $district, $policeStation, $costPrice, $dpPrice, $mrpPrice, $sellingPrice, $isActive]);
            $pricingId = $pdo->lastInsertId();
        }

        // Auto-sync product_stocks entry for this location if not exists
        $chkStock = $pdo->prepare("
            SELECT id FROM product_stocks 
            WHERE product_id = ? 
              AND (batch_id = ? OR (batch_id IS NULL AND ? IS NULL))
              AND district = ? 
              AND police_station = ?
            LIMIT 1
        ");
        $chkStock->execute([$productId, $batchId, $batchId, $district, $policeStation]);
        if (!$chkStock->fetch()) {
            $insStock = $pdo->prepare("
                INSERT INTO product_stocks (product_id, batch_id, zone_id, district, police_station, opening_stock, current_stock, low_stock_threshold)
                VALUES (?, ?, ?, ?, ?, 0.00, 0.00, 5.00)
            ");
            $insStock->execute([$productId, $batchId, $zoneId, $district, $policeStation]);
        }

        // Also sync main products table base price if zone is default (1) or All Bangladesh
        if (($zoneId == 1 || empty($zoneId)) && ($district === 'All Bangladesh' || empty($district))) {
            $pdo->prepare("UPDATE products SET price = ?, old_price = ? WHERE id = ?")->execute([$sellingPrice, $mrpPrice, $productId]);
        }

        jsonResponse(['status' => 'success', 'message' => 'Pricing & location inventory updated successfully!', 'pricing_id' => $pricingId]);
    }

    // 5.1 Delete Pricing Entry
    if ($action === 'delete_pricing') {
        requirePermission('inventory.pricing_delete');
        $pricingId = intval($input['id'] ?? 0);
        if (!$pricingId) {
            jsonResponse(['status' => 'error', 'message' => 'Pricing ID required'], 400);
        }
        $pdo->prepare("DELETE FROM product_pricing WHERE id = ?")->execute([$pricingId]);
        jsonResponse(['status' => 'success', 'message' => 'Pricing entry deleted successfully!']);
    }

    // 6. Get Stocks with Product, Batch, and Location/Zone Info
    if ($action === 'get_stocks') {
        requirePermission('inventory.stocks_view');
        $sql = "
            SELECT 
                s.*,
                p.product_code,
                p.title as product_title,
                p.title_bn as product_title_bn,
                p.poriman,
                p.image,
                b.batch_number,
                b.exp_date,
                b.status as batch_status,
                z.zone_name
            FROM product_stocks s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN product_batches b ON s.batch_id = b.id
            LEFT JOIN pricing_zones z ON s.zone_id = z.id
            ORDER BY (s.district = 'All Bangladesh') DESC, s.district ASC, s.current_stock ASC, p.id DESC
        ";
        $stocks = $pdo->query($sql)->fetchAll();
        jsonResponse(['status' => 'success', 'stocks' => $stocks]);
    }

    // 7. Stock Operation (Stock IN / Stock OUT / Damage / Adjustment)
    if ($action === 'update_stock_operation') {
        requirePermission('inventory.stock_adjust');
        $stockId = intval($input['stock_id'] ?? 0);
        $operation = $input['operation'] ?? 'stock_in'; // 'stock_in', 'stock_out', 'damage', 'adjustment'
        $qty = floatval($input['quantity'] ?? 0);
        $notes = trim($input['notes'] ?? '');
        $staffName = $_SESSION['manager_name'] ?? 'Manager';

        if (!$stockId || $qty <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Valid Stock item and Quantity (> 0) required!'], 400);
        }

        $stockRow = $pdo->query("SELECT * FROM product_stocks WHERE id = {$stockId}")->fetch();
        if (!$stockRow) {
            jsonResponse(['status' => 'error', 'message' => 'Stock record not found!'], 404);
        }

        $oldStock = floatval($stockRow['current_stock']);
        $newStock = $oldStock;
        $damagedStock = floatval($stockRow['damaged_stock']);
        $actionType = 'stock_in';

        if ($operation === 'stock_in') {
            $newStock = $oldStock + $qty;
            $actionType = 'stock_in';
        } else if ($operation === 'stock_out') {
            if ($oldStock < $qty) {
                jsonResponse(['status' => 'error', 'message' => 'Insufficient stock for stock out! Current stock: ' . $oldStock], 400);
            }
            $newStock = $oldStock - $qty;
            $actionType = 'stock_out';
        } else if ($operation === 'damage') {
            if ($oldStock < $qty) {
                jsonResponse(['status' => 'error', 'message' => 'Damaged quantity cannot exceed current stock!'], 400);
            }
            $newStock = $oldStock - $qty;
            $damagedStock += $qty;
            $actionType = 'damage';
        } else if ($operation === 'adjustment') {
            $newStock = $qty;
            $actionType = 'adjustment';
        }

        // Update Specific Location Stock
        $updStock = $pdo->prepare("UPDATE product_stocks SET current_stock = ?, damaged_stock = ? WHERE id = ?");
        $updStock->execute([$newStock, $damagedStock, $stockId]);

        // Also update products table overall stock (sum of all locations)
        $sumStock = $pdo->query("SELECT COALESCE(SUM(current_stock), 0) FROM product_stocks WHERE product_id = {$stockRow['product_id']}")->fetchColumn();
        $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([intval($sumStock), $stockRow['product_id']]);

        // Insert into Stock Movement Logs with location info
        $insLog = $pdo->prepare("
            INSERT INTO stock_logs (product_id, batch_id, stock_id, district, police_station, action_type, quantity, previous_stock, new_stock, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insLog->execute([
            $stockRow['product_id'],
            $stockRow['batch_id'],
            $stockId,
            $stockRow['district'] ?? 'All Bangladesh',
            $stockRow['police_station'] ?? 'All Police Stations',
            $actionType,
            $qty,
            $oldStock,
            $newStock,
            $notes ?: "Manual {$operation} performed by {$staffName}",
            $staffName
        ]);

        jsonResponse([
            'status' => 'success',
            'message' => "Stock updated successfully for {$stockRow['district']}! New Stock: {$newStock}",
            'new_stock' => $newStock
        ]);
    }

    // 8. Get Stock Logs (Audit Trail)
    if ($action === 'get_stock_logs') {
        requirePermission('inventory.stock_logs_view');
        $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $sql = "
            SELECT 
                l.*,
                p.product_code,
                p.title as product_title,
                p.title_bn as product_title_bn,
                b.batch_number,
                DATE_FORMAT(l.created_at, '%b %d, %Y %h:%i %p') as formatted_date
            FROM stock_logs l
            JOIN products p ON l.product_id = p.id
            LEFT JOIN product_batches b ON l.batch_id = b.id
        ";
        if ($productId > 0) {
            $sql .= " WHERE l.product_id = " . $productId;
        }
        $sql .= " ORDER BY l.id DESC LIMIT 100";

        $logs = $pdo->query($sql)->fetchAll();
        jsonResponse(['status' => 'success', 'logs' => $logs]);
    }

    // 9. Get / Save Pricing Zones
    if ($action === 'get_zones') {
        requirePermission('inventory.zones_manage');
        $zones = $pdo->query("SELECT * FROM pricing_zones ORDER BY is_default DESC, id ASC")->fetchAll();
        jsonResponse(['status' => 'success', 'zones' => $zones]);
    }

    if ($action === 'save_zone') {
        requirePermission('inventory.zones_manage');
        $zoneId = !empty($input['id']) ? intval($input['id']) : null;
        $name = trim($input['zone_name'] ?? '');
        $code = trim($input['zone_code'] ?? '');
        $thanas = trim($input['thana_list'] ?? '');
        $status = isset($input['status']) ? intval($input['status']) : 1;

        if (empty($name) || empty($code)) {
            jsonResponse(['status' => 'error', 'message' => 'Zone Name and Zone Code are required!'], 400);
        }

        if ($zoneId) {
            $stmt = $pdo->prepare("UPDATE pricing_zones SET zone_name = ?, zone_code = ?, thana_list = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $code, $thanas, $status, $zoneId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pricing_zones (zone_name, zone_code, thana_list, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $code, $thanas, $status]);
            $zoneId = $pdo->lastInsertId();
        }

        jsonResponse(['status' => 'success', 'message' => 'Zone saved successfully!', 'zone_id' => $zoneId]);
    }

    jsonResponse(['status' => 'error', 'message' => 'Unknown action'], 400);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
