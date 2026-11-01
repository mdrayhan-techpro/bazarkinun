<?php
// api/dp-sales-manager.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Enforce Dealer Point Management authorization
$authDp = requirePortalAuth(['admin', 'author', 'manager', 'management', 'operation']);

try {
    // 1. Get List of DP Sales
    if ($action === 'get_dp_sales') {
        requirePermission(['dp_sales.view', 'orders.view', 'view_orders']);
        $stmt = $pdo->query("SELECT * FROM dp_sales ORDER BY id DESC LIMIT 200");
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['status' => 'success', 'sales' => $sales]);
    }

    // 2. Get Single DP Sale Detail
    if ($action === 'get_dp_sale_detail') {
        requirePermission(['dp_sales.view', 'orders.view', 'view_orders']);
        $id = intval($_GET['id'] ?? $input['id'] ?? 0);
        if (!$id) jsonResponse(['status' => 'error', 'message' => 'Sale ID is required'], 400);

        $saleStmt = $pdo->prepare("SELECT * FROM dp_sales WHERE id = ?");
        $saleStmt->execute([$id]);
        $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale) jsonResponse(['status' => 'error', 'message' => 'Invoice not found'], 404);

        $itemsStmt = $pdo->prepare("SELECT * FROM dp_sale_items WHERE sale_id = ?");
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['status' => 'success', 'sale' => $sale, 'items' => $items]);
    }

    // 3. Save New DP Sale (Wholesale / Dealer Order)
    if ($action === 'save_dp_sale') {
        requirePermission(['dp_sales.create', 'pos.access', 'access_pos']);
        $dealerName = trim($input['dealer_name'] ?? '');
        $dealerPhone = trim($input['dealer_phone'] ?? '');
        $dealerAddress = trim($input['dealer_address'] ?? '');
        $items = $input['items'] ?? [];
        $discount = floatval($input['discount'] ?? 0);
        $deliveryCharge = floatval($input['delivery_charge'] ?? 0);
        $paidAmount = floatval($input['paid_amount'] ?? 0);
        $paymentMethod = $input['payment_method'] ?? 'Cash';
        $notes = trim($input['notes'] ?? '');
        $createdBy = trim($input['created_by'] ?? 'Admin');

        if (empty($dealerName)) {
            jsonResponse(['status' => 'error', 'message' => 'Dealer / Client name is required!'], 400);
        }

        if (empty($items) || !is_array($items)) {
            jsonResponse(['status' => 'error', 'message' => 'Please add at least one product item!'], 400);
        }

        $subtotal = 0;
        $totalCost = 0;
        $totalProfit = 0;
        $processedItems = [];

        foreach ($items as $item) {
            $pId = intval($item['product_id'] ?? 0);
            $qty = max(1, floatval($item['quantity'] ?? 1));
            $dpPrice = floatval($item['dp_price'] ?? 0);
            $costPrice = floatval($item['cost_price'] ?? 0);
            $bId = !empty($item['batch_id']) ? intval($item['batch_id']) : null;
            $bNum = trim($item['batch_number'] ?? '');

            // Fetch product info if missing
            $prod = $pdo->query("SELECT title, product_code FROM products WHERE id = {$pId}")->fetch(PDO::FETCH_ASSOC);
            $pTitle = $prod['title'] ?? ($item['product_title'] ?? 'Product #' . $pId);
            $pCode = $prod['product_code'] ?? ($item['product_code'] ?? 'PRD-' . $pId);

            $lineTotal = round($dpPrice * $qty, 2);
            $lineCost = round($costPrice * $qty, 2);
            $lineProfit = round($lineTotal - $lineCost, 2);

            $subtotal += $lineTotal;
            $totalCost += $lineCost;
            $totalProfit += $lineProfit;

            $processedItems[] = [
                'product_id' => $pId,
                'batch_id' => $bId,
                'product_title' => $pTitle,
                'product_code' => $pCode,
                'batch_number' => $bNum,
                'cost_price' => $costPrice,
                'dp_price' => $dpPrice,
                'quantity' => $qty,
                'total_price' => $lineTotal,
                'total_cost' => $lineCost,
                'total_profit' => $lineProfit
            ];
        }

        $totalAmount = max(0, ($subtotal - $discount) + $deliveryCharge);
        $dueAmount = max(0, $totalAmount - $paidAmount);
        $paymentStatus = $dueAmount <= 0 ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Due');
        $invoiceNo = 'DP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO dp_sales (
                invoice_no, dealer_name, dealer_phone, dealer_address,
                subtotal, discount, delivery_charge, total_amount, paid_amount, due_amount,
                total_cost, total_profit, payment_method, payment_status, order_status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Delivered', ?, ?)
        ");
        $stmt->execute([
            $invoiceNo, $dealerName, $dealerPhone, $dealerAddress,
            $subtotal, $discount, $deliveryCharge, $totalAmount, $paidAmount, $dueAmount,
            $totalCost, $totalProfit, $paymentMethod, $paymentStatus, $notes, $createdBy
        ]);
        $saleId = $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("
            INSERT INTO dp_sale_items (
                sale_id, product_id, batch_id, product_title, product_code, batch_number,
                cost_price, dp_price, quantity, total_price, total_cost, total_profit
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($processedItems as $it) {
            $itemStmt->execute([
                $saleId, $it['product_id'], $it['batch_id'], $it['product_title'], $it['product_code'], $it['batch_number'],
                $it['cost_price'], $it['dp_price'], $it['quantity'], $it['total_price'], $it['total_cost'], $it['total_profit']
            ]);

            // Deduct stock from product_stocks and products table
            $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?")->execute([$it['quantity'], $it['product_id']]);
            if (!empty($it['batch_id'])) {
                $pdo->prepare("UPDATE product_stocks SET current_stock = GREATEST(0, current_stock - ?) WHERE product_id = ? AND batch_id = ?")->execute([$it['quantity'], $it['product_id'], $it['batch_id']]);
            } else {
                $pdo->prepare("UPDATE product_stocks SET current_stock = GREATEST(0, current_stock - ?) WHERE product_id = ? LIMIT 1")->execute([$it['quantity'], $it['product_id']]);
            }
        }

        $pdo->commit();

        jsonResponse([
            'status' => 'success',
            'message' => "DP Wholesale Sale created successfully! Invoice: {$invoiceNo}",
            'sale_id' => $saleId,
            'invoice_no' => $invoiceNo
        ]);
    }

    // 4. Delete DP Sale
    if ($action === 'delete_dp_sale') {
        requirePermission(['dp_sales.delete', 'orders.cancel', 'cancel_orders']);
        $id = intval($input['id'] ?? 0);
        if (!$id) jsonResponse(['status' => 'error', 'message' => 'Sale ID is required'], 400);

        $pdo->prepare("DELETE FROM dp_sale_items WHERE sale_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM dp_sales WHERE id = ?")->execute([$id]);

        jsonResponse(['status' => 'success', 'message' => 'DP Sale invoice deleted successfully']);
    }

    jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
