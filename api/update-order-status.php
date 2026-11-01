<?php
// api/update-order-status.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$action = $input['action'] ?? '';
$orderId = trim($input['order_id'] ?? $input['id'] ?? '');
$newStatus = trim($input['status'] ?? '');

// Enforce staff / rider / manager authorization
$authStaff = requirePortalAuth(['admin', 'author', 'manager', 'management', 'operation', 'rider', 'staff']);
$operatorName = $authStaff['name'] ?? ($_SESSION['manager_name'] ?? $_SESSION['user_name'] ?? 'System Manager');

try {
    // 1. ACTION: Assign Delivery Rider
    if ($action === 'assign_rider') {
        requirePermission('assign_riders');
        $riderId = intval($input['rider_id'] ?? 0);
        $riderName = trim($input['rider_name'] ?? '');
        $riderPhone = trim($input['rider_phone'] ?? '');

        if (empty($orderId)) {
            jsonResponse(['status' => 'error', 'message' => 'Order ID is required.'], 400);
        }

        // If rider_id given but no name/phone, fetch from users
        if ($riderId > 0 && (empty($riderName) || empty($riderPhone))) {
            $rStmt = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
            $rStmt->execute([$riderId]);
            $rider = $rStmt->fetch(PDO::FETCH_ASSOC);
            if ($rider) {
                $riderName = $riderName ?: $rider['name'];
                $riderPhone = $riderPhone ?: $rider['phone'];
            }
        }

        if (empty($riderName) || empty($riderPhone)) {
            jsonResponse(['status' => 'error', 'message' => 'Rider Name and Phone number are required.'], 400);
        }

        // Fetch Order
        $orderIdNum = is_numeric($orderId) ? intval($orderId) : 0;
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
        $stmt->execute([$orderIdNum, $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            jsonResponse(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        // Update rider assignment and transition status to delivering
        $upd = $pdo->prepare("
            UPDATE orders 
            SET rider_id = ?, 
                rider_name = ?, 
                rider_phone = ?, 
                assigned_at = NOW(), 
                status = 'delivering' 
            WHERE id = ?
        ");
        $upd->execute([$riderId ?: null, $riderName, $riderPhone, $order['id']]);

        // Trigger stock deduction if not already deducted
        deductOrderStock($pdo, $order, $operatorName, 'delivering');

        jsonResponse([
            'status' => 'success',
            'message' => "Rider '{$riderName}' assigned successfully! Order #{$order['order_number']} is now Out For Delivery.",
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'rider_name' => $riderName,
            'rider_phone' => $riderPhone,
            'status' => 'delivering'
        ]);
        exit;
    }

    // 1.5 ACTION: Admin Edit Order (Grocery List: Written or Uploaded Photo)
    if ($action === 'admin_edit_order_grocery_list') {
        requirePermission('edit_order_items');
        $orderId = intval($input['order_id'] ?? $input['id'] ?? 0);
        $orderNumber = trim($input['order_number'] ?? '');
        $customerName = trim($input['customer_name'] ?? '');
        $customerPhone = trim($input['customer_phone'] ?? '');
        $customerAddress = trim($input['customer_address'] ?? '');
        $status = trim($input['status'] ?? 'pending');
        $orderType = trim($input['order_type'] ?? 'text_list'); // 'text_list' or 'uploaded'
        $items = $input['items'] ?? [];
        $imagesRaw = $input['images'] ?? [];

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE (id > 0 AND id = ?) OR order_number = ? OR invoice_number = ?");
        $stmt->execute([$orderId, $orderNumber, $orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            jsonResponse(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        $targetCode = $order['order_number'] ?: ('ORD-' . $order['id']);
        $orderDate = !empty($order['created_at']) ? date('M d, Y, h:i A', strtotime($order['created_at'])) : date('M d, Y, h:i A');

        $savedImageUrls = [];
        $listsDir = __DIR__ . '/../uploads/lists/';
        if (!is_dir($listsDir)) {
            mkdir($listsDir, 0777, true);
        }

        if ($orderType === 'uploaded') {
            if (!empty($imagesRaw)) {
                $imagesArr = is_array($imagesRaw) ? $imagesRaw : json_decode($imagesRaw, true);
                if (!is_array($imagesArr) && is_string($imagesRaw)) {
                    $imagesArr = [$imagesRaw];
                }
                if (is_array($imagesArr)) {
                    foreach ($imagesArr as $img) {
                        if (is_string($img) && preg_match('/^data:image\/(\w+);base64,/', $img, $m)) {
                            $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                            $raw = base64_decode(substr($img, strpos($img, ',') + 1));
                            if ($raw !== false) {
                                $filename = 'order_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.' . $ext;
                                if (file_put_contents($listsDir . $filename, $raw)) {
                                    $savedImageUrls[] = 'uploads/lists/' . $filename;
                                }
                            }
                        } elseif (!empty($img) && is_string($img)) {
                            $savedImageUrls[] = $img;
                        }
                    }
                }
            }

            // Generate clean A4 Photo pages for uploaded order
            $photoPagesHtml = '';
            $totalPages = count($savedImageUrls) ?: 1;
            foreach ($savedImageUrls as $pIdx => $pUrl) {
                $isFirst = ($pIdx === 0);
                $maxHeight = $isFirst ? '640px' : '820px';

                $headerBlock = '';
                if ($isFirst) {
                    $headerBlock = "
                        <div style='text-align: center; margin-bottom: 12px;'>
                            <img src='assets/bazarkinundotcom_logo.png' alt='BazarKinun.com Logo' style='width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;'>
                            <div style='display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;'>
                                <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 75px; width: auto; object-fit: contain; display: inline-block;'>
                            </div>
                            <p style='margin: -4px 0 0 0; font-size: 14px; color: #475569; font-weight: 700; font-family: Outfit, sans-serif;'>বাজারে নয়, বাজার আসবে আপনার ঘরে।</p>
                        </div>
                        <hr style='border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;'>
                        <div style='display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;'>
                            <div style='max-width: 58%; text-align: left;'>
                                <div><strong style='color: #0f172a;'>Name:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerName) . "</span></div>
                                <div><strong style='color: #0f172a;'>Phone:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerPhone) . "</span></div>
                                <div><strong style='color: #0f172a;'>Location:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerAddress) . "</span></div>
                            </div>
                            <div style='text-align: right;'>
                                <div><strong style='color: #0f172a;'>Order Type:</strong> <span style='color: #00c675; font-weight: 800;'>Uploaded Photo Order</span></div>
                                <div><strong style='color: #0f172a;'>Order No:</strong> <span style='color: #00c675; font-weight: 800;'>{$targetCode}</span></div>
                                <div><strong style='color: #0f172a;'>Date & Time:</strong> <span style='color: #334155; font-weight: 600;'>{$orderDate}</span></div>
                                <div><strong style='color: #0f172a;'>Page:</strong> <span style='color: #334155; font-weight: 600;'>" . ($pIdx + 1) . " of {$totalPages}</span></div>
                            </div>
                        </div>
                    ";
                } else {
                    $headerBlock = "
                        <div style='display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 22px;'>
                            <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 52px; width: auto; object-fit: contain;'>
                            <div style='font-size: 13px; color: #0f172a; text-align: right;'>
                                <strong>Order No:</strong> <span style='font-weight: 800; color: #00c675;'>{$targetCode}</span> &nbsp;|&nbsp;
                                <strong>Page:</strong> <span style='font-weight: 700; color: #334155;'>" . ($pIdx + 1) . " of {$totalPages}</span>
                            </div>
                        </div>
                    ";
                }

                $photoPagesHtml .= "
                    <div class='a4-page' style='background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 0 auto 24px auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: Outfit, sans-serif; overflow: hidden;'>
                        <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;'>
                            <img src='assets/bazarkinundotcom_logo.png' alt='Watermark' style='width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;'>
                            <img src='assets/bazarkinundotcom_logo2.png' alt='Watermark Logo 2' style='width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;'>
                        </div>
                        <div style='position: relative; z-index: 2; flex: 1;'>
                            {$headerBlock}
                            <div style='display: flex; justify-content: center; align-items: center; margin: 15px 0; position: relative; z-index: 2; width: 100%; flex: 1;'>
                                <div style='border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fafafa; width: 100%; display: flex; justify-content: center; align-items: center; padding: 12px; box-sizing: border-box; box-shadow: 0 2px 10px rgba(0,0,0,0.02);'>
                                    <img src='{$pUrl}' alt='Grocery Photo' style='max-width: 100%; max-height: {$maxHeight}; width: auto; height: auto; object-fit: contain; display: block; border-radius: 6px;'>
                                </div>
                            </div>
                        </div>
                        <div style='border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;'>
                            <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 55px; width: auto;'>
                            <div style='text-align: right;'>
                                <span style='font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;'>24/7 Support Center</span>
                                <strong style='font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;'>01919-668960</strong>
                            </div>
                        </div>
                    </div>
                ";
            }

            $newHtml = $photoPagesHtml;

            // Delete old order_items and update order
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order['id']]);

            $upd = $pdo->prepare("
                UPDATE orders 
                SET customer_name = ?, 
                    customer_phone = ?, 
                    customer_address = ?, 
                    status = ?, 
                    order_type = 'uploaded', 
                    html_content = ? 
                WHERE id = ?
            ");
            $upd->execute([
                $customerName,
                $customerPhone,
                $customerAddress,
                $status,
                $newHtml,
                $order['id']
            ]);

            $formattedItems = [];
        } else {
            // Written / text grocery list order
            $orderItemsData = [];
            $rowsHtml = '';
            foreach ($items as $idx => $it) {
                $name = trim($it['item'] ?? $it['name'] ?? 'Item');
                $spec = trim($it['spec'] ?? $it['poriman'] ?? '');
                $qty = trim($it['qty'] ?? $it['quantity'] ?? '1');
                if (empty($name)) continue;

                $orderItemsData[] = [
                    'name' => $name,
                    'poriman' => $spec,
                    'quantity' => intval($qty) ?: 1,
                    'unit_price' => 0,
                    'subtotal' => 0
                ];

                $specHtml = $spec ? " <span style='color: #64748b; font-weight: 500;'>(" . htmlspecialchars($spec) . ")</span>" : "";
                $rowsHtml .= "
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700; text-align: center;'>" . ($idx + 1) . "</td>
                        <td style='padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700;'>" . htmlspecialchars($name) . "{$specHtml}</td>
                        <td style='padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13.5px; font-weight: 800;'>" . htmlspecialchars($qty) . "</td>
                    </tr>
                ";
            }

            // Build clean 3-column A4 HTML
            $tableHtml = "
                <table style='width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2;'>
                    <thead>
                        <tr style='background: #f8fafc; border-top: 1.5px solid #e2e8f0; border-bottom: 1.5px solid #e2e8f0;'>
                            <th style='padding: 11px 8px; color: #475569; font-size: 12.5px; font-weight: 800; width: 45px; text-align: center;'>SL</th>
                            <th style='padding: 11px 8px; color: #475569; font-size: 12.5px; font-weight: 800;'>Item Description</th>
                            <th style='padding: 11px 8px; color: #475569; text-align: center; font-size: 12.5px; font-weight: 800; width: 120px;'>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>{$rowsHtml}</tbody>
                </table>
            ";

            $newHtml = "
                <div class='a4-page' style='background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: Outfit, sans-serif; overflow: hidden;'>
                    <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;'>
                        <img src='assets/bazarkinundotcom_logo.png' alt='Watermark' style='width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;'>
                        <img src='assets/bazarkinundotcom_logo2.png' alt='Watermark Logo 2' style='width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;'>
                    </div>
                    <div style='position: relative; z-index: 2; flex: 1;'>
                        <div style='text-align: center; margin-bottom: 12px;'>
                            <img src='assets/bazarkinundotcom_logo.png' alt='BazarKinun.com Logo' style='width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;'>
                            <div style='display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;'>
                                <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 75px; width: auto; object-fit: contain; display: inline-block;'>
                            </div>
                            <p style='margin: -4px 0 0 0; font-size: 14px; color: #475569; font-weight: 700;'>বাজারে নয়, বাজার আসবে আপনার ঘরে।</p>
                        </div>
                        <hr style='border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;'>
                        <div style='display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;'>
                            <div style='max-width: 58%; text-align: left;'>
                                <div><strong style='color: #0f172a;'>Name:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerName) . "</span></div>
                                <div><strong style='color: #0f172a;'>Phone:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerPhone) . "</span></div>
                                <div><strong style='color: #0f172a;'>Location:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerAddress) . "</span></div>
                            </div>
                            <div style='text-align: right;'>
                                <div><strong style='color: #0f172a;'>Order Type:</strong> <span style='color: #00c675; font-weight: 800;'>Grocery List Order</span></div>
                                <div><strong style='color: #0f172a;'>Order No:</strong> <span style='color: #00c675; font-weight: 800;'>{$targetCode}</span></div>
                                <div><strong style='color: #0f172a;'>Date & Time:</strong> <span style='color: #334155; font-weight: 600;'>{$orderDate}</span></div>
                                <div><strong style='color: #0f172a;'>Page:</strong> <span style='color: #334155; font-weight: 600;'>1 of 1</span></div>
                            </div>
                        </div>
                        <div style='position: relative; margin-bottom: 20px; z-index: 2;'>
                            {$tableHtml}
                        </div>
                    </div>
                    <div style='border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;'>
                        <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 55px; width: auto;'>
                        <div style='text-align: right;'>
                            <span style='font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;'>24/7 Support Center</span>
                            <strong style='font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;'>01919-668960</strong>
                        </div>
                    </div>
                </div>
            ";

            // Update database records
            $upd = $pdo->prepare("
                UPDATE orders 
                SET customer_name = ?, 
                    customer_phone = ?, 
                    customer_address = ?, 
                    status = ?, 
                    order_type = 'text_list', 
                    html_content = ? 
                WHERE id = ?
            ");
            $upd->execute([
                $customerName,
                $customerPhone,
                $customerAddress,
                $status,
                $newHtml,
                $order['id']
            ]);

            // Replace order items
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order['id']]);
            $insItem = $pdo->prepare("INSERT INTO order_items (order_id, product_name, product_poriman, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($orderItemsData as $oi) {
                $insItem->execute([$order['id'], $oi['name'], $oi['poriman'], $oi['quantity'], 0, 0]);
            }

            $formattedItems = $orderItemsData;
        }

        $adminName = $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? 'Admin';
        $adminRole = !empty($_SESSION['admin_id']) ? 'admin' : 'manager';
        logAuditActivity($pdo, 'Order Edited by Management', "{$adminName} ({$adminRole}) updated grocery list order #{$targetCode}", $adminName, $adminRole);

        jsonResponse([
            'status' => 'success',
            'message' => "Order #{$targetCode} updated successfully!",
            'order' => [
                'id' => $targetCode,
                'order_number' => $targetCode,
                'db_id' => $order['id'],
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'status' => ucfirst($status),
                'order_type' => $orderType,
                'list_type' => $orderType === 'uploaded' ? 'uploaded' : 'written',
                'htmlContent' => $newHtml,
                'items' => $formattedItems,
                'images' => $savedImageUrls,
                'image_url' => $savedImageUrls[0] ?? null,
                'image_urls' => $savedImageUrls,
                'date' => $orderDate
            ]
        ]);
        exit;
    }

    // 2. ACTION: Admin Edit Order / Invoice
    if ($action === 'admin_edit_order_invoice') {
        requirePermission('manage_invoices');
        $orderId = intval($input['order_id'] ?? $input['id'] ?? 0);
        $orderNumber = trim($input['order_number'] ?? '');
        $status = trim($input['status'] ?? 'pending');
        $customerName = trim($input['customer_name'] ?? '');
        $customerPhone = trim($input['customer_phone'] ?? '');
        $customerAddress = trim($input['customer_address'] ?? '');
        $deliveryCharge = floatval($input['delivery_charge'] ?? 0);
        $discountAmount = floatval($input['discount_amount'] ?? 0);
        $items = $input['items'] ?? [];

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE (id > 0 AND id = ?) OR order_number = ? OR invoice_number = ?");
        $stmt->execute([$orderId, $orderNumber, $orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            jsonResponse(['status' => 'error', 'message' => 'Order/Invoice not found.'], 404);
        }

        // Calculate total amount from items
        $subtotal = 0;
        $orderItemsData = [];
        $invoiceRowsHtml = '';

        $couponCode = trim($input['coupon_code'] ?? '');

        foreach ($items as $idx => $it) {
            $name = trim($it['name'] ?? $it['item'] ?? 'Item');
            $spec = trim($it['spec'] ?? $it['poriman'] ?? '');
            $qty = intval($it['quantity'] ?? $it['qty'] ?? 1);
            $unitPrice = floatval($it['price'] ?? 0);
            $rowTotal = $qty * $unitPrice;
            $subtotal += $rowTotal;

            $displayName = $name . ($spec ? " ({$spec})" : '');
            $orderItemsData[] = [
                'name' => $displayName,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $rowTotal
            ];

            $isFreeGift = ($unitPrice == 0) || (strpos($name, 'Free Gift') !== false) || (strpos($name, 'ফ্রি গিফট') !== false) || (strpos($name, '🎁') !== false);

            $priceFormatted = floor($unitPrice) == $unitPrice ? number_format($unitPrice, 0) : number_format($unitPrice, 2);
            $rowTotalFormatted = floor($rowTotal) == $rowTotal ? number_format($rowTotal, 0) : number_format($rowTotal, 2);

            if ($isFreeGift) {
                // Ensure name has the clean gift badge
                $giftNameClean = preg_replace('/^\[.*?\]\s*/', '', $name);
                $fullGiftTitle = "[ 🎁 🎁 কম্বো ফ্রি গিফট] {$giftNameClean}" . ($spec ? " ({$spec})" : '');

                $invoiceRowsHtml .= "
                    <tr style='border-bottom: 1px solid #f1f5f9; background: #f0fdf4;'>
                        <td style='padding: 10px 8px; color: #64748b; font-size: 13px; text-align: center;'>" . ($idx + 1) . "</td>
                        <td style='padding: 10px 8px; color: #059669; font-weight: 700; font-size: 13.5px;'>" . htmlspecialchars($fullGiftTitle) . "</td>
                        <td style='padding: 10px 8px; color: #059669; text-align: center; font-weight: 800; font-size: 13.5px;'>FREE</td>
                        <td style='padding: 10px 8px; color: #1e293b; text-align: center; font-size: 13.5px;'>{$qty}</td>
                        <td style='padding: 10px 8px; color: #059669; text-align: right; font-weight: 800; font-size: 13.5px;'>৳0</td>
                    </tr>
                ";
            } else {
                $itemTitleHtml = htmlspecialchars($name) . ($spec ? " <span style='font-size: 11.5px; color: #64748b; font-weight: 600;'>(" . htmlspecialchars($spec) . ")</span>" : '');

                $invoiceRowsHtml .= "
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 10px 8px; color: #64748b; font-size: 13px; text-align: center;'>" . ($idx + 1) . "</td>
                        <td style='padding: 10px 8px; color: #0f172a; font-weight: 700; font-size: 13.5px;'>{$itemTitleHtml}</td>
                        <td style='padding: 10px 8px; color: #1e293b; text-align: center; font-size: 13.5px;'>৳{$priceFormatted}</td>
                        <td style='padding: 10px 8px; color: #1e293b; text-align: center; font-size: 13.5px;'>{$qty}</td>
                        <td style='padding: 10px 8px; color: #0f172a; text-align: right; font-weight: 800; font-size: 13.5px;'>৳{$rowTotalFormatted}</td>
                    </tr>
                ";
            }
        }

        $totalAmount = max(0, $subtotal - $discountAmount) + $deliveryCharge;

        $invCode = $order['invoice_number'] ?: $order['order_number'];
        $orderDateFormatted = !empty($order['created_at']) ? date('M d, Y, h:i A', strtotime($order['created_at'])) : date('M d, Y, h:i A');

        $subtotalFormatted = floor($subtotal) == $subtotal ? number_format($subtotal, 0) : number_format($subtotal, 2);
        $deliveryFeeFormatted = ($deliveryCharge == 0) ? 'FREE' : ('৳' . (floor($deliveryCharge) == $deliveryCharge ? number_format($deliveryCharge, 0) : number_format($deliveryCharge, 2)));
        $discountFormatted = floor($discountAmount) == $discountAmount ? number_format($discountAmount, 0) : number_format($discountAmount, 2);
        $totalFormatted = floor($totalAmount) == $totalAmount ? number_format($totalAmount, 0) : number_format($totalAmount, 2);

        $discountRowHtml = '';
        if ($discountAmount > 0) {
            $discountLabel = $couponCode ? "Coupon ({$couponCode}):" : "Discount:";
            $discountRowHtml = "
                <div style='display: flex; justify-content: space-between; padding: 4px 0; color: #059669; font-weight: 700;'>
                    <span>{$discountLabel}</span>
                    <span>-৳{$discountFormatted}</span>
                </div>
            ";
        }

        $uploadedImages = [];
        if (!empty($order['id'])) {
            $colStmt = $pdo->prepare("SELECT * FROM custom_order_lists WHERE order_id = ?");
            $colStmt->execute([$order['id']]);
            $colData = $colStmt->fetch();
            if ($colData && !empty($colData['image_urls'])) {
                $imgs = json_decode($colData['image_urls'], true);
                if (is_array($imgs)) {
                    $uploadedImages = array_merge($uploadedImages, $imgs);
                }
            }
        }
        if (!empty($order['custom_list_id'])) {
            $clStmt = $pdo->prepare("SELECT * FROM custom_lists WHERE id = ?");
            $clStmt->execute([$order['custom_list_id']]);
            $clData = $clStmt->fetch();
            if ($clData && !empty($clData['images'])) {
                $imgs = is_string($clData['images']) ? (json_decode($clData['images'], true) ?: explode(',', $clData['images'])) : $clData['images'];
                if (is_array($imgs)) {
                    $uploadedImages = array_merge($uploadedImages, array_map('trim', $imgs));
                }
            }
        }
        if (!empty($order['html_content'])) {
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $order['html_content'], $matches)) {
                foreach ($matches[1] as $src) {
                    if ((strpos($src, 'uploads/') !== false || strpos($src, 'data:image') !== false) && strpos($src, 'logo') === false && strpos($src, 'Watermark') === false) {
                        if (!in_array($src, $uploadedImages)) {
                            $uploadedImages[] = $src;
                        }
                    }
                }
            }
        }
        $uploadedImages = array_values(array_unique(array_filter($uploadedImages)));

        $totalPages = !empty($uploadedImages) ? (1 + count($uploadedImages)) : 1;
        $page1Str = ($totalPages > 1) ? "1 of {$totalPages}" : "1 of 1";

        // Build authentic modern A4 Invoice HTML matching Image 1
        $newHtml = "
        <div class='a4-page' style='background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: Outfit, sans-serif; overflow: hidden;'>
            <!-- Watermark -->
            <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;'>
                <img src='assets/bazarkinundotcom_logo.png' alt='Watermark' style='width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;'>
                <img src='assets/bazarkinundotcom_logo2.png' alt='Watermark Logo 2' style='width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;'>
            </div>

            <div style='position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column;'>
                <!-- Header -->
                <div style='text-align: center; margin-bottom: 12px;'>
                    <img src='assets/bazarkinundotcom_logo.png' alt='BazarKinun.com Logo' style='width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;'>
                    <div style='display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;'>
                        <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 75px; width: auto; object-fit: contain; display: inline-block;'>
                    </div>
                    <p style='margin: -4px 0 0 0; font-size: 14.5px; color: #475569; font-weight: 700; font-family: Outfit, sans-serif;'> বাজারে নয়, বাজার আসবে আপনার ঘরে। </p>
                </div>

                <hr style='border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;'>

                <!-- Customer Details & Order Metadata Row -->
                <div style='display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;'>
                    <div style='max-width: 58%; text-align: left;'>
                        <div><strong style='color: #0f172a;'>Name:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerName) . "</span></div>
                        <div><strong style='color: #0f172a;'>Phone:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerPhone) . "</span></div>
                        <div><strong style='color: #0f172a;'>Location:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerAddress) . "</span></div>
                    </div>
                    <div style='text-align: right;'>
                        <div><strong style='color: #0f172a;'>Order Type:</strong> <span style='color: #00c675; font-weight: 800;'>Online Order</span></div>
                        <div><strong style='color: #0f172a;'>Invoice No:</strong> <span style='color: #00c675; font-weight: 800;'>{$invCode}</span></div>
                        <div><strong style='color: #0f172a;'>Date & Time:</strong> <span style='color: #334155; font-weight: 600;'>{$orderDateFormatted}</span></div>
                        <div><strong style='color: #0f172a;'>Page:</strong> <span style='color: #334155; font-weight: 600;'>{$page1Str}</span></div>
                    </div>
                </div>

                <!-- Items Table -->
                <div style='position: relative; margin-bottom: 20px; z-index: 2;'>
                    <table style='width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2; margin-bottom: 16px;'>
                        <thead>
                            <tr style='border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a;'>
                                <th style='padding: 10px 8px; color: #0f172a; font-size: 13px; font-weight: 800; width: 45px; text-align: center;'>SL</th>
                                <th style='padding: 10px 8px; color: #0f172a; font-size: 13px; font-weight: 800;'>Item Description</th>
                                <th style='padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13px; font-weight: 800; width: 85px;'>Price</th>
                                <th style='padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13px; font-weight: 800; width: 65px;'>Qty</th>
                                <th style='padding: 10px 8px; color: #0f172a; text-align: right; font-weight: 800; font-size: 13px;'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$invoiceRowsHtml}
                        </tbody>
                    </table>

                    <!-- Summary Block aligned to Right -->
                    <div style='display: flex; justify-content: flex-end; position: relative; z-index: 2; margin-top: 10px;'>
                        <div style='width: 260px; font-size: 13.5px;'>
                            <div style='display: flex; justify-content: space-between; padding: 4px 0; color: #64748b;'>
                                <span>Subtotal:</span>
                                <span style='color: #0f172a; font-weight: 700;'>৳{$subtotalFormatted}</span>
                            </div>
                            {$discountRowHtml}
                            <div style='display: flex; justify-content: space-between; padding: 4px 0; color: #64748b;'>
                                <span>Delivery Fee:</span>
                                <span style='color: #0f172a; font-weight: 700;'>{$deliveryFeeFormatted}</span>
                            </div>
                            <div style='display: flex; justify-content: space-between; padding: 10px 0; border-top: 1.5px solid #e2e8f0; font-size: 17px; font-weight: 900; color: #00c675;'>
                                <span>Total to Pay:</span>
                                <span>৳{$totalFormatted}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer with Dashed Line -->
            <div style='border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; width: 100%;'>
                <div style='display: flex; align-items: center; gap: 8px;'>
                    <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 66px; width: auto; object-fit: contain;'>
                </div>
                <div style='text-align: right;'>
                    <span style='font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;'>24/7 Support Center</span>
                    <strong style='font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;'>01919-668960</strong>
                </div>
            </div>
        </div>
        ";

        if (!empty($uploadedImages)) {
            foreach ($uploadedImages as $imgIdx => $imgUrl) {
                $currPage = 2 + $imgIdx;
                $cleanImgUrl = htmlspecialchars($imgUrl);
                $newHtml .= "
                <div class='a4-page invoice-attachment-page' style='background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 30px auto 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: Outfit, sans-serif; overflow: hidden; page-break-before: always;'>
                    <!-- Watermark -->
                    <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;'>
                        <img src='assets/bazarkinundotcom_logo.png' alt='Watermark' style='width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;'>
                        <img src='assets/bazarkinundotcom_logo2.png' alt='Watermark Logo 2' style='width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;'>
                    </div>

                    <div style='position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column;'>
                        <!-- Header -->
                        <div style='text-align: center; margin-bottom: 12px;'>
                            <img src='assets/bazarkinundotcom_logo.png' alt='BazarKinun.com Logo' style='width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;'>
                            <div style='display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;'>
                                <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 75px; width: auto; object-fit: contain; display: inline-block;'>
                            </div>
                            <p style='margin: -4px 0 0 0; font-size: 14.5px; color: #475569; font-weight: 700; font-family: Outfit, sans-serif;'> বাজারে নয়, বাজার আসবে আপনার ঘরে। </p>
                        </div>

                        <hr style='border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;'>

                        <!-- Details Row -->
                        <div style='display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;'>
                            <div style='max-width: 58%; text-align: left;'>
                                <div><strong style='color: #0f172a;'>Name:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerName) . "</span></div>
                                <div><strong style='color: #0f172a;'>Phone:</strong> <span style='color: #334155; font-weight: 600;'>" . htmlspecialchars($customerPhone) . "</span></div>
                                <div><strong style='color: #0f172a;'>Document:</strong> <span style='color: #00c675; font-weight: 800;'><i class='fa-solid fa-paperclip' style='margin-right: 4px;'></i> Customer's Original Uploaded List</span></div>
                            </div>
                            <div style='text-align: right;'>
                                <div><strong style='color: #0f172a;'>Invoice No:</strong> <span style='color: #00c675; font-weight: 800;'>{$invCode}</span></div>
                                <div><strong style='color: #0f172a;'>Date & Time:</strong> <span style='color: #334155; font-weight: 600;'>{$orderDateFormatted}</span></div>
                                <div><strong style='color: #0f172a;'>Page:</strong> <span style='color: #334155; font-weight: 600;'>{$currPage} of {$totalPages}</span></div>
                            </div>
                        </div>

                        <!-- Image Box -->
                        <div style='flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 14px; margin-bottom: 15px; min-height: 620px;'>
                            <div style='margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 4px;'>
                                <span style='font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;'>
                                    <i class='fa-solid fa-camera' style='color: #00c675;'></i> কাস্টমারের আপলোড করা মূল ফর্দের ছবি (Photo #" . ($imgIdx + 1) . ")
                                </span>
                                <a href='{$cleanImgUrl}' target='_blank' style='display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #0284c7; text-decoration: none; background: #e0f2fe; padding: 5px 12px; border-radius: 6px; border: 1px solid #bae6fd;'>
                                    <i class='fa-solid fa-up-right-from-square'></i> সম্পূর্ণ সাইজ দেখুন
                                </a>
                            </div>
                            <div style='width: 100%; flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; max-height: 720px; border-radius: 8px; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 6px;'>
                                <img src='{$cleanImgUrl}' alt='Customer Original List Photo' style='max-width: 100%; max-height: 700px; object-fit: contain; border-radius: 6px; cursor: zoom-in;' onclick=\"window.open('{$cleanImgUrl}', '_blank')\">
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style='border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; width: 100%;'>
                        <div style='display: flex; align-items: center; gap: 8px;'>
                            <img src='assets/bazarkinundotcom_logo2.png' alt='BazarKinun.com' style='height: 66px; width: auto; object-fit: contain;'>
                        </div>
                        <div style='text-align: right;'>
                            <span style='font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;'>24/7 Support Center</span>
                            <strong style='font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;'>01919-668960</strong>
                        </div>
                    </div>
                </div>
                ";
            }
        }

        $couponCode = trim($input['coupon_code'] ?? '');

        // Update database record
        $upd = $pdo->prepare("
            UPDATE orders 
            SET customer_name = ?, 
                customer_phone = ?, 
                customer_address = ?, 
                status = ?, 
                delivery_charge = ?, 
                coupon_code = ?, 
                discount_amount = ?, 
                total_amount = ?, 
                html_content = ? 
            WHERE id = ?
        ");
        $upd->execute([
            $customerName,
            $customerPhone,
            $customerAddress,
            $status,
            $deliveryCharge,
            $couponCode,
            $discountAmount,
            $totalAmount,
            $newHtml,
            $order['id']
        ]);

        // Replace order items
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order['id']]);
        $insItem = $pdo->prepare("INSERT INTO order_items (order_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($orderItemsData as $oi) {
            $insItem->execute([$order['id'], $oi['name'], $oi['quantity'], $oi['unit_price'], $oi['subtotal']]);
        }

        $adminName = $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? 'Admin';
        $adminRole = !empty($_SESSION['admin_id']) ? 'admin' : 'manager';
        logAuditActivity($pdo, 'Order Edited by Management', "{$adminName} ({$adminRole}) updated order #{$invCode} (Total: ৳{$totalAmount})", $adminName, $adminRole);

        jsonResponse([
            'status' => 'success',
            'message' => "Order #{$invCode} updated successfully!",
            'order' => [
                'id' => $invCode,
                'db_id' => $order['id'],
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'status' => ucfirst($status),
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'delivery_charge' => number_format($deliveryCharge, 2, '.', ''),
                'discount_amount' => number_format($discountAmount, 2, '.', ''),
                'coupon_code' => $couponCode,
                'items' => $orderItemsData,
                'htmlContent' => $newHtml,
                'date' => date('M d, Y, h:i A')
            ]
        ]);
        exit;
    }

    // 3. ACTION: Admin Edit Custom List
    if ($action === 'admin_edit_custom_list') {
        requirePermission('edit_order_items');
        $listId = intval($input['list_id'] ?? $input['id'] ?? 0);
        $listCode = trim($input['list_code'] ?? '');
        $customerName = trim($input['customer_name'] ?? '');
        $customerPhone = trim($input['customer_phone'] ?? '');
        $customerAddress = trim($input['customer_address'] ?? '');
        $status = trim($input['status'] ?? 'draft');
        $listType = trim($input['list_type'] ?? 'written');
        $contentText = trim($input['content_text'] ?? '');
        $imagesRaw = $input['images'] ?? null;

        $stmt = $pdo->prepare("SELECT * FROM custom_lists WHERE id = ? OR list_code = ?");
        $stmt->execute([$listId, $listCode]);
        $list = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$list) {
            jsonResponse(['status' => 'error', 'message' => 'Custom List not found.'], 404);
        }

        $targetCode = $list['list_code'] ?: ($listCode ?: ('LST-' . $list['id']));

        // Process images
        $savedImageUrls = [];
        $listsDir = __DIR__ . '/../uploads/lists/';
        if (!is_dir($listsDir)) {
            mkdir($listsDir, 0777, true);
        }

        if ($listType === 'uploaded') {
            if (!empty($imagesRaw)) {
                $imagesArr = is_array($imagesRaw) ? $imagesRaw : json_decode($imagesRaw, true);
                if (!is_array($imagesArr) && is_string($imagesRaw)) {
                    $imagesArr = [$imagesRaw];
                }
                if (is_array($imagesArr)) {
                    foreach ($imagesArr as $img) {
                        if (is_string($img) && preg_match('/^data:image\/(\w+);base64,/', $img, $m)) {
                            $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                            $raw = base64_decode(substr($img, strpos($img, ',') + 1));
                            if ($raw !== false) {
                                $filename = 'list_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.' . $ext;
                                if (file_put_contents($listsDir . $filename, $raw)) {
                                    $savedImageUrls[] = 'uploads/lists/' . $filename;
                                }
                            }
                        } elseif (!empty($img) && is_string($img)) {
                            $savedImageUrls[] = $img;
                        }
                    }
                }
            }
            $imagesJson = !empty($savedImageUrls) ? json_encode($savedImageUrls) : null;
            if (empty($contentText) || $contentText === 'Uploaded Photo List') {
                $contentText = 'Uploaded Photo List';
            }
        } else {
            // Written list
            $imagesJson = null;
        }

        $upd = $pdo->prepare("
            UPDATE custom_lists 
            SET customer_name = ?, 
                customer_phone = ?, 
                customer_address = ?, 
                status = ?, 
                list_type = ?, 
                content_text = ?, 
                images = ?
            WHERE id = ?
        ");
        $upd->execute([
            $customerName,
            $customerPhone,
            $customerAddress,
            $status,
            $listType,
            $contentText,
            $imagesJson,
            $list['id']
        ]);

        $adminName = $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? 'Admin';
        $adminRole = !empty($_SESSION['admin_id']) ? 'admin' : 'manager';
        logAuditActivity($pdo, 'Custom List Edited by Management', "{$adminName} ({$adminRole}) updated custom list #{$targetCode}", $adminName, $adminRole);

        jsonResponse([
            'status' => 'success',
            'message' => "Custom List #{$targetCode} updated successfully!",
            'list' => [
                'id' => $targetCode,
                'list_code' => $targetCode,
                'db_id' => $list['id'],
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'status' => ucfirst($status),
                'list_type' => $listType,
                'content_text' => $contentText,
                'htmlContent' => $contentText,
                'images' => $savedImageUrls,
                'image_url' => $savedImageUrls[0] ?? null,
                'image_urls' => $savedImageUrls,
                'date' => date('M d, Y, h:i A')
            ]
        ]);
        exit;
    }

    // 3.8 ACTION: Update Payment Status
    if ($action === 'update_payment_status') {
        requirePermission('update_order_status');
        $paymentStatus = trim($input['payment_status'] ?? '');
        $allowedPaymentStatuses = ['pending', 'paid', 'refunded', 'failed'];
        if (empty($orderId) || !in_array($paymentStatus, $allowedPaymentStatuses)) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid order ID or payment status.'], 400);
        }

        $orderIdNum = is_numeric($orderId) ? intval($orderId) : 0;
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
        $stmt->execute([$orderIdNum, $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            jsonResponse(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        $upd = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $upd->execute([$paymentStatus, $order['id']]);

        logAuditActivity($pdo, 'Order Payment Status Updated', "{$operatorName} updated order #{$order['order_number']} payment status to '{$paymentStatus}'", $operatorName, $authStaff['role'] ?? 'staff');

        jsonResponse([
            'status' => 'success',
            'message' => "Order #{$order['order_number']} payment status updated to '{$paymentStatus}'!",
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'payment_status' => $paymentStatus
        ]);
        exit;
    }

    // 4. ACTION: Update Order Status
    $allowedStatuses = ['pending', 'processing', 'delivering', 'completed', 'cancelled', 'customer_confirmed'];

    if (empty($orderId) || empty($newStatus) || !in_array($newStatus, $allowedStatuses)) {
        jsonResponse(['status' => 'error', 'message' => 'Invalid order ID or status value'], 400);
    }

    if ($newStatus === 'cancelled') {
        requirePermission(['cancel_orders', 'orders.cancel']);
    } else {
        requirePermission(['update_order_status', 'orders.status_update', 'rider_trip_status', 'rider.trip_panel']);
    }

    // Find order
    $orderIdNum = is_numeric($orderId) ? intval($orderId) : 0;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
    $stmt->execute([$orderIdNum, $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonResponse(['status' => 'error', 'message' => 'Order not found in database'], 404);
    }

    $oldStatus = $order['status'];

    // Update status
    $update = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update->execute([$newStatus, $order['id']]);

    // Handle automated stock deduction
    if (in_array($newStatus, ['delivering', 'completed']) && intval($order['stock_deducted'] ?? 0) === 0) {
        deductOrderStock($pdo, $order, $operatorName, $newStatus);
    }

    // Handle automated stock restoration on cancellation
    if ($newStatus === 'cancelled' && intval($order['stock_deducted'] ?? 0) === 1) {
        restoreOrderStock($pdo, $order, $operatorName);
    }

    jsonResponse([
        'status' => 'success',
        'message' => "Order #{$order['order_number']} status updated from '{$oldStatus}' to '{$newStatus}'!",
        'order_number' => $order['order_number'],
        'order_id' => $order['id'],
        'old_status' => $oldStatus,
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}

// === HELPER FUNCTIONS FOR AUTOMATED INVENTORY ===

function deductOrderStock(PDO $pdo, array $order, string $operatorName, string $targetStatus): void {
    // Check if already deducted
    $chk = $pdo->prepare("SELECT stock_deducted FROM orders WHERE id = ?");
    $chk->execute([$order['id']]);
    if ($chk->fetchColumn() == 1) return;

    // Fetch order items
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$order['id']]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $pName = trim($item['product_name']);
        $qty = floatval($item['quantity'] ?? 1);
        if ($qty <= 0) continue;

        // Find product
        $pStmt = $pdo->prepare("SELECT id, title, stock FROM products WHERE id = ? OR title = ? OR title_bn = ? LIMIT 1");
        $pStmt->execute([$item['product_id'] ?? 0, $pName, $pName]);
        $prod = $pStmt->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $pId = $prod['id'];

            // Find active batch and stock record
            $stockStmt = $pdo->prepare("
                SELECT s.id as stock_id, s.batch_id, s.zone_id, s.current_stock 
                FROM product_stocks s 
                WHERE s.product_id = ? 
                ORDER BY s.id DESC 
                LIMIT 1
            ");
            $stockStmt->execute([$pId]);
            $stockRec = $stockStmt->fetch(PDO::FETCH_ASSOC);

            if ($stockRec) {
                $prevStock = floatval($stockRec['current_stock']);
                $newStock = max(0, $prevStock - $qty);

                // Update product_stocks
                $updStock = $pdo->prepare("UPDATE product_stocks SET current_stock = ? WHERE id = ?");
                $updStock->execute([$newStock, $stockRec['stock_id']]);

                // Update products table stock
                $updProd = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $updProd->execute([$newStock, $pId]);

                // Write stock audit log
                $insLog = $pdo->prepare("
                    INSERT INTO stock_logs 
                    (product_id, batch_id, zone_id, log_type, change_qty, previous_stock, new_stock, reference_id, operator_name, notes, created_at) 
                    VALUES (?, ?, ?, 'order_sale', ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insLog->execute([
                    $pId,
                    $stockRec['batch_id'],
                    $stockRec['zone_id'] ?? 1,
                    -$qty,
                    $prevStock,
                    $newStock,
                    $order['id'],
                    $operatorName,
                    "Auto-deducted for Order #{$order['order_number']} ({$targetStatus})"
                ]);
            }
        }
    }

    // Mark order as stock_deducted = 1
    $pdo->prepare("UPDATE orders SET stock_deducted = 1 WHERE id = ?")->execute([$order['id']]);
}

function restoreOrderStock(PDO $pdo, array $order, string $operatorName): void {
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$order['id']]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $pName = trim($item['product_name']);
        $qty = floatval($item['quantity'] ?? 1);
        if ($qty <= 0) continue;

        $pStmt = $pdo->prepare("SELECT id, title, stock FROM products WHERE id = ? OR title = ? OR title_bn = ? LIMIT 1");
        $pStmt->execute([$item['product_id'] ?? 0, $pName, $pName]);
        $prod = $pStmt->fetch(PDO::FETCH_ASSOC);

        if ($prod) {
            $pId = $prod['id'];

            $stockStmt = $pdo->prepare("
                SELECT s.id as stock_id, s.batch_id, s.zone_id, s.current_stock 
                FROM product_stocks s 
                WHERE s.product_id = ? 
                ORDER BY s.id DESC 
                LIMIT 1
            ");
            $stockStmt->execute([$pId]);
            $stockRec = $stockStmt->fetch(PDO::FETCH_ASSOC);

            if ($stockRec) {
                $prevStock = floatval($stockRec['current_stock']);
                $newStock = $prevStock + $qty;

                $updStock = $pdo->prepare("UPDATE product_stocks SET current_stock = ? WHERE id = ?");
                $updStock->execute([$newStock, $stockRec['stock_id']]);

                $updProd = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $updProd->execute([$newStock, $pId]);

                $insLog = $pdo->prepare("
                    INSERT INTO stock_logs 
                    (product_id, batch_id, zone_id, log_type, change_qty, previous_stock, new_stock, reference_id, operator_name, notes, created_at) 
                    VALUES (?, ?, ?, 'order_cancellation_refund', ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insLog->execute([
                    $pId,
                    $stockRec['batch_id'],
                    $stockRec['zone_id'] ?? 1,
                    +$qty,
                    $prevStock,
                    $newStock,
                    $order['id'],
                    $operatorName,
                    "Auto-restored from Cancelled Order #{$order['order_number']}"
                ]);
            }
        }
    }

    $pdo->prepare("UPDATE orders SET stock_deducted = 0 WHERE id = ?")->execute([$order['id']]);
}
