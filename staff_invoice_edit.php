<?php
// staff_invoice_edit.php
require_once __DIR__ . '/config/db.php';

// Verify staff, manager, or admin session
$isStaffAuthorized = !empty($_SESSION['staff_id']) || !empty($_SESSION['admin_id']) || !empty($_SESSION['manager_id']) || (!empty($_SESSION['user_role']) && in_array(strtolower($_SESSION['user_role']), ['rider', 'staff', 'admin', 'manager', 'operation']));
if (!$isStaffAuthorized) {
    header('Location: portal_login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php?tab=staff_panel'));
    exit;
}

// Enforce granular invoice management permission
requirePermission(['invoices.manage', 'orders.edit_items', 'manage_invoices', 'edit_order_items']);

$id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$customListId = isset($_GET['custom_list_id']) ? intval($_GET['custom_list_id']) : 0;
$fromAdmin = !empty($_SESSION['admin_id']) || isset($_GET['from_admin']);
$order = null;
$customList = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
}

if (!$order && $customListId > 0) {
    // Check if custom list exists in custom_lists table
    $clStmt = $pdo->prepare("SELECT * FROM custom_lists WHERE id = ?");
    $clStmt->execute([$customListId]);
    $customList = $clStmt->fetch();

    if ($customList) {
        $listCode = $customList['list_code'] ?: $customList['title'];
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE custom_list_id = ? OR order_number = ? LIMIT 1");
        $stmt->execute([$customListId, $listCode]);
        $order = $stmt->fetch();

        if (!$order) {
            // Auto create pending order record for this custom list
            $ordNum = generateSequentialCode($pdo, 'ORD', $customList['customer_phone'] ?? '', $customList['customer_name'] ?? 'Customer', $customList['user_id'] ?? null);
            $insStmt = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, custom_list_id, customer_name, customer_phone, customer_address, police_station, order_type, total_amount, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'custom_list', 0, 'accepted', NOW())
            ");
            $insStmt->execute([
                $ordNum,
                $customList['user_id'] ?? null,
                $customList['id'],
                $customList['customer_name'] ?: 'Customer',
                $customList['customer_phone'] ?: '',
                $customList['customer_address'] ?: '',
                $customList['police_station'] ?? ''
            ]);
            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$newId]);
            $order = $stmt->fetch();
        }
    } else {
        // Check if customListId is an orders table ID
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$customListId]);
        $order = $stmt->fetch();
    }
}

// Fallback demo order if not found
if (!$order) {
    $order = [
        'id' => $id > 0 ? $id : 101,
        'order_number' => 'ORD-' . ($id > 0 ? $id : 101),
        'customer_name' => 'Md. Rayhan',
        'customer_phone' => '01919668960',
        'customer_address' => 'House 12, Road 5, Sector 4, Uttara, Dhaka',
        'police_station' => 'Uttara',
        'order_type' => 'grocery_list',
        'total_amount' => 450.00,
        'delivery_charge' => 25.00,
        'status' => 'pending',
        'html_content' => ''
    ];
}

// 1. Extract Uploaded Images if any
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

// 2. Extract Written List Items if any
$preloadedItems = [];
$rawContent = $order['html_content'] ?: ($customList['content_text'] ?? '');
if (!empty($rawContent) && strpos($rawContent, '<tr') !== false) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $rawContent);
    libxml_clear_errors();
    $rows = $dom->getElementsByTagName('tr');
    foreach ($rows as $r) {
        $tds = $r->getElementsByTagName('td');
        if ($tds->length >= 2) {
            $fullItem = '';
            $spec = '';
            $qty = '1';

            if ($tds->length >= 3) {
                $fullItem = trim($tds->item(1)->textContent);
                $qty = trim($tds->item(2)->textContent);
            } else {
                $fullItem = trim($tds->item(0)->textContent);
                $qty = trim($tds->item(1)->textContent);
            }

            // Exclude header or metadata text
            if (in_array(strtolower($fullItem), ['item description', 'item name', 'name', 'phone', 'location', 'page', 'subtotal', 'total'])) continue;
            if (strpos($fullItem, 'BazarKinun') !== false || strpos($fullItem, 'বাজারে নয়') !== false) continue;
            if (strpos($fullItem, 'Support Center') !== false || strpos($fullItem, 'Helpline') !== false) continue;

            if (preg_match('/^(.*?)\s*[\(\[\{](.*?)[\)\]\}]\s*$/', $fullItem, $m)) {
                $fullItem = trim($m[1]);
                $spec = trim($m[2]);
            }

            if (!empty($fullItem) && strlen($fullItem) > 1) {
                $preloadedItems[] = [
                    'name' => $fullItem,
                    'spec' => $spec,
                    'qty' => $qty ?: '1',
                    'price' => ''
                ];
            }
        }
    }
}

// 3. Fetch Products Catalog & Categories for Modal and Autocomplete
$catalogProducts = [];
$catalogCategories = [];
try {
    $pStmt = $pdo->query("
        SELECT p.id, p.product_code, p.category_id, p.title, p.title_bn, p.brand, p.poriman, p.poriman_bn, p.price, p.old_price, p.image, p.stock, 
               c.name as category_name, c.name_bn as category_name_bn
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE (p.is_active = 1 OR p.status = 'active')
        ORDER BY p.id ASC
    ");
    if ($pStmt) {
        $catalogProducts = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $cStmt = $pdo->query("SELECT id, name, name_bn FROM categories ORDER BY id ASC");
    if ($cStmt) {
        $catalogCategories = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
$prodCatalog = $catalogProducts;
$categoriesList = $catalogCategories;

// 4. Fetch Delivery Charge Rules
$deliveryRules = [];
try {
    $drStmt = $pdo->query("SELECT * FROM delivery_charge_rules WHERE is_active = 1 ORDER BY priority ASC");
    if ($drStmt) {
        $deliveryRules = $drStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

$initialDeliveryCharge = floatval($order['delivery_charge'] ?? 25.00);
if ($initialDeliveryCharge <= 0) {
    $initialDeliveryCharge = 25.00;
}

$successMsg = '';
$errorMsg = '';

// Handle Form POST Submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $itemsSubtotal = isset($_POST['items_subtotal']) ? floatval($_POST['items_subtotal']) : 0;
    $deliveryCharge = isset($_POST['delivery_charge']) ? floatval($_POST['delivery_charge']) : 25.00;
    $totalAmount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : ($itemsSubtotal + $deliveryCharge);
    $totalWeight = isset($_POST['total_weight']) ? trim($_POST['total_weight']) : '';
    $itemsDescription = isset($_POST['items_description']) ? trim($_POST['items_description']) : '';
    $rawItemsJson = isset($_POST['raw_items_json']) ? trim($_POST['raw_items_json']) : '';

    try {
        // Generate unique sequential Invoice Number
        $invoiceNumber = !empty($order['invoice_number']) ? $order['invoice_number'] : generateSequentialCode($pdo, 'INV', $order['customer_phone'] ?? '', $order['customer_name'] ?? 'Customer', $order['user_id'] ?? null);

        $parsedItems = !empty($rawItemsJson) ? json_decode($rawItemsJson, true) : [];
        $tableRowsHtml = '';

        if (!empty($parsedItems) && is_array($parsedItems)) {
            foreach ($parsedItems as $idx => $it) {
                $sl = $idx + 1;
                $itName = htmlspecialchars($it['name'] ?? '');
                $itSpec = !empty($it['spec']) ? ' <span style="color: #64748b; font-weight: 500;">(' . htmlspecialchars($it['spec']) . ')</span>' : '';
                $itQty = htmlspecialchars($it['qty'] ?? '1');
                $itPrice = number_format(floatval($it['price'] ?? 0), 2);

                $tableRowsHtml .= '
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px 10px; font-weight: 700; color: #475569; text-align: center; font-size: 13.5px;">' . $sl . '</td>
                        <td style="padding: 10px 10px; font-weight: 700; color: #0f172a; font-size: 13.5px;">' . $itName . $itSpec . '</td>
                        <td style="padding: 10px 10px; text-align: center; font-weight: 700; color: #334155; font-size: 13.5px;">' . $itQty . '</td>
                        <td style="padding: 10px 10px; text-align: right; font-weight: 800; color: #047857; font-size: 13.5px;">৳' . $itPrice . '</td>
                    </tr>
                ';
            }
        } else {
            $lines = explode("\n", $itemsDescription);
            foreach ($lines as $idx => $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $tableRowsHtml .= '
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 10px; font-weight: 700; color: #475569; text-align: center; font-size: 13.5px;">' . ($idx + 1) . '</td>
                            <td colspan="2" style="padding: 10px 10px; font-weight: 700; color: #0f172a; font-size: 13.5px;">' . htmlspecialchars($line) . '</td>
                            <td style="padding: 10px 10px; text-align: right; font-weight: 800; color: #047857; font-size: 13.5px;">--</td>
                        </tr>
                    ';
                }
            }
        }

        $totalPages = !empty($uploadedImages) ? (1 + count($uploadedImages)) : 1;
        $page1Str = ($totalPages > 1) ? "1 of {$totalPages}" : "1 of 1";

        // Standard Official BazarKinun A4 Invoice HTML
        $generatedHtml = '
        <div class="a4-page" style="background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: Outfit, \'Hind Siliguri\', sans-serif; overflow: hidden; align-self: stretch;">
            
            <!-- Faded Full Page Center Watermark -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;">
                <img src="assets/bazarkinundotcom_logo.png" alt="Watermark" style="width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                <img src="assets/bazarkinundotcom_logo2.png" alt="Watermark Logo 2" style="width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;">
            </div>

            <div style="position: relative; z-index: 2; flex: 1;">
                <!-- Top Center Header -->
                <div style="text-align: center; margin-bottom: 12px;">
                    <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;">
                    <div style="display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 75px; width: auto; object-fit: contain; display: inline-block;">
                    </div>
                    <p style="margin: -4px 0 0 0; font-size: 14px; color: #475569; font-weight: 700; font-family: Outfit, sans-serif; line-height: 1.2;">
                        বাজারে নয়, বাজার আসবে আপনার ঘরে।
                    </p>
                </div>

                <hr style="border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;">

                <!-- Customer Details & Invoice Metadata Row -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;">
                    <div style="max-width: 58%; text-align: left;">
                        <div><strong style="color: #0f172a;">Customer Name:</strong> <span style="color: #334155; font-weight: 600;">' . htmlspecialchars($order['customer_name']) . '</span></div>
                        <div><strong style="color: #0f172a;">Phone:</strong> <span style="color: #334155; font-weight: 600;">' . htmlspecialchars($order['customer_phone']) . '</span></div>
                        <div><strong style="color: #0f172a;">Delivery Location:</strong> <span style="color: #334155; font-weight: 600;">' . htmlspecialchars($order['customer_address'] ?: $order['police_station']) . '</span></div>
                        ' . ($totalWeight ? '<div><strong style="color: #0f172a;">Total Weight:</strong> <span style="color: #047857; font-weight: 800;"><i class="fa-solid fa-weight-hanging" style="color: #10b981; margin-right: 3px;"></i> ' . htmlspecialchars($totalWeight) . '</span></div>' : '') . '
                    </div>
                    <div style="text-align: right;">
                        <div><strong style="color: #0f172a;">Document:</strong> <span style="color: #00c675; font-weight: 800;">Official Grocery Invoice</span></div>
                        <div><strong style="color: #0f172a;">Invoice No:</strong> <span style="color: #00c675; font-weight: 900;">' . htmlspecialchars($invoiceNumber) . '</span></div>
                        <div><strong style="color: #0f172a;">Order Ref:</strong> <span style="color: #64748b; font-weight: 700;">' . htmlspecialchars($order['order_number']) . '</span></div>
                        <div><strong style="color: #0f172a;">Date & Time:</strong> <span style="color: #334155; font-weight: 600;">' . date('M d, Y, h:i A') . '</span></div>
                        <div><strong style="color: #0f172a;">Page:</strong> <span style="color: #334155; font-weight: 600;">' . $page1Str . '</span></div>
                    </div>
                </div>

                <!-- Priced Items Table -->
                <div style="position: relative; margin-bottom: 20px; z-index: 2;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2;">
                        <thead>
                            <tr style="border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a; background: #f8fafc;">
                                <th style="padding: 11px 10px; color: #0f172a; font-size: 13px; font-weight: 800; width: 45px; text-align: center;">SL</th>
                                <th style="padding: 11px 10px; color: #0f172a; font-size: 13px; font-weight: 800;">Item Description</th>
                                <th style="padding: 11px 10px; color: #0f172a; font-size: 13px; font-weight: 800; text-align: center; width: 95px;">Quantity</th>
                                <th style="padding: 11px 10px; color: #0f172a; text-align: right; font-size: 13px; font-weight: 800; width: 115px;">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $tableRowsHtml . '
                        </tbody>
                    </table>
                </div>

                <!-- Complete Bill Breakdown & Delivery Charge Section -->
                <div style="border-top: 2px solid #0f172a; padding-top: 14px; margin-top: 10px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <span style="display: inline-block; background: #ecfdf5; color: #047857; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 6px; border: 1px solid #a7f3d0; margin-bottom: 6px;">
                            <i class="fa-solid fa-check"></i> Priced by Delivery Staff (Waiting Customer Confirm)
                        </span>
                        <div style="font-size: 12.5px; color: #64748b; font-weight: 600;">
                            Payment Method: <strong style="color: #0f172a;">Cash on Delivery</strong>
                        </div>
                    </div>
                    <div style="min-width: 250px; text-align: right;">
                        <div style="display: flex; justify-content: space-between; font-size: 13.5px; color: #475569; margin-bottom: 4px;">
                            <span>Items Subtotal:</span>
                            <strong style="color: #0f172a;">৳' . number_format($itemsSubtotal, 2) . '</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13.5px; color: #475569; margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px;">
                            <span>Delivery Charge ' . ($totalWeight ? '(' . htmlspecialchars($totalWeight) . ')' : '') . ':</span>
                            <strong style="color: #047857;">+ ৳' . number_format($deliveryCharge, 2) . '</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 900; color: #047857; margin-top: 4px;">
                            <span style="color: #0f172a;">Grand Total:</span>
                            <span style="font-size: 21px; color: #047857; letter-spacing: -0.5px;">৳' . number_format($totalAmount, 2) . '</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer with Dashed Line -->
            <div style="border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 55px; width: auto; object-fit: contain;">
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;">24/7 Support Center</span>
                    <strong style="font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;">01919-668960</strong>
                </div>
            </div>
        </div>';

        // Append Customer's Uploaded Original List Photos as Page 2, Page 3 etc.
        if (!empty($uploadedImages)) {
            foreach ($uploadedImages as $imgIdx => $imgUrl) {
                $currPage = 2 + $imgIdx;
                $cleanImgUrl = htmlspecialchars($imgUrl);
                $generatedHtml .= '
                <div class="a4-page invoice-attachment-page" style="background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 30px auto 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: Outfit, \'Hind Siliguri\', sans-serif; overflow: hidden; align-self: stretch; page-break-before: always;">
                    
                    <!-- Faded Full Page Center Watermark -->
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;">
                        <img src="assets/bazarkinundotcom_logo.png" alt="Watermark" style="width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="Watermark Logo 2" style="width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;">
                    </div>

                    <div style="position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column;">
                        <!-- Top Center Header -->
                        <div style="text-align: center; margin-bottom: 12px;">
                            <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;">
                            <div style="display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;">
                                <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 75px; width: auto; object-fit: contain; display: inline-block;">
                            </div>
                            <p style="margin: -4px 0 0 0; font-size: 14px; color: #475569; font-weight: 700; font-family: Outfit, sans-serif; line-height: 1.2;">
                                বাজারে নয়, বাজার আসবে আপনার ঘরে।
                            </p>
                        </div>

                        <hr style="border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;">

                        <!-- Customer Details & Attachment Metadata Row -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;">
                            <div style="max-width: 58%; text-align: left;">
                                <div><strong style="color: #0f172a;">Customer Name:</strong> <span style="color: #334155; font-weight: 600;">' . htmlspecialchars($order['customer_name']) . '</span></div>
                                <div><strong style="color: #0f172a;">Phone:</strong> <span style="color: #334155; font-weight: 600;">' . htmlspecialchars($order['customer_phone']) . '</span></div>
                                <div><strong style="color: #0f172a;">Document:</strong> <span style="color: #00c675; font-weight: 800;"><i class="fa-solid fa-paperclip" style="margin-right: 4px;"></i> Customer\'s Original Uploaded List</span></div>
                            </div>
                            <div style="text-align: right;">
                                <div><strong style="color: #0f172a;">Invoice No:</strong> <span style="color: #00c675; font-weight: 900;">' . htmlspecialchars($invoiceNumber) . '</span></div>
                                <div><strong style="color: #0f172a;">Order Ref:</strong> <span style="color: #64748b; font-weight: 700;">' . htmlspecialchars($order['order_number']) . '</span></div>
                                <div><strong style="color: #0f172a;">Date & Time:</strong> <span style="color: #334155; font-weight: 600;">' . date('M d, Y, h:i A') . '</span></div>
                                <div><strong style="color: #0f172a;">Page:</strong> <span style="color: #334155; font-weight: 600;">' . $currPage . ' of ' . $totalPages . '</span></div>
                            </div>
                        </div>

                        <!-- Image Display Box -->
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 14px; margin-bottom: 15px; min-height: 620px;">
                            <div style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 4px;">
                                <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-camera" style="color: #00c675;"></i> কাস্টমারের আপলোড করা মূল ফর্দের ছবি (Photo #' . ($imgIdx + 1) . ')
                                </span>
                                <a href="' . $cleanImgUrl . '" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #0284c7; text-decoration: none; background: #e0f2fe; padding: 5px 12px; border-radius: 6px; border: 1px solid #bae6fd;">
                                    <i class="fa-solid fa-up-right-from-square"></i> সম্পূর্ণ সাইজ দেখুন
                                </a>
                            </div>
                            <div style="width: 100%; flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; max-height: 720px; border-radius: 8px; background: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 6px;">
                                <img src="' . $cleanImgUrl . '" alt="Customer Original List Photo" style="max-width: 100%; max-height: 700px; object-fit: contain; border-radius: 6px; cursor: zoom-in;" onclick="window.open(\'' . $cleanImgUrl . '\', \'_blank\')">
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Footer with Dashed Line -->
                    <div style="border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 55px; width: auto; object-fit: contain;">
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;">24/7 Support Center</span>
                            <strong style="font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;">01919-668960</strong>
                        </div>
                    </div>
                </div>';
            }
        }

        // Update Order in database
        $updateStmt = $pdo->prepare("
            UPDATE orders 
            SET invoice_number = ?,
                total_amount = ?, 
                delivery_charge = ?,
                status = 'invoice_created', 
                html_content = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$invoiceNumber, $totalAmount, $deliveryCharge, $generatedHtml, $order['id']]);

        if (!empty($order['custom_list_id'])) {
            $pdo->prepare("UPDATE custom_lists SET is_processed = 1, status = 'invoiced' WHERE id = ?")->execute([$order['custom_list_id']]);
        }

        $creatorName = $_SESSION['admin_name'] ?? $_SESSION['staff_name'] ?? 'Delivery Staff';
        $creatorRole = !empty($_SESSION['admin_id']) ? 'admin' : 'rider';
        logAuditActivity($pdo, 'Invoice Generated', "{$creatorName} generated invoice {$invoiceNumber} for order {$order['order_number']}", $creatorName, $creatorRole);

        $redirUrl = "dashboard.php?tab=staff_panel&subtab_staff=delivery&msg=invoice_saved&order_id=" . $order['id'];
        header("Location: " . $redirUrl);
        exit;
    } catch (Exception $e) {
        $errorMsg = "Error saving invoice: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Grocery List & Generate Invoice - BazarKinun.com</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/bazarkinundotcom_logo.png">
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #00c675;
            --primary-hover: #00b368;
            --primary-dark: #047857;
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }
        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .invoice-card {
            background: white;
            border-radius: 16px;
            padding: 28px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            max-height: 240px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        .autocomplete-item {
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .autocomplete-item:hover, .autocomplete-item.active {
            background: #ecfdf5;
        }
        .thumb-preview-img {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .thumb-preview-img:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        @media (max-width: 768px) {
            .invoice-card { padding: 18px 14px !important; }
            .invoice-weight-row { flex-direction: column !important; gap: 14px !important; }
            .table-responsive { overflow-x: auto !important; -webkit-overflow-scrolling: touch !important; width: 100% !important; }
            .invoice-table, table { min-width: 520px !important; }
        }
        @media (max-width: 480px) {
            .invoice-card { padding: 14px 10px !important; }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header style="background: white; border-bottom: 1px solid #e2e8f0; padding: 14px 0; margin-bottom: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); position: sticky; top: 0; z-index: 50;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 10px rgba(0, 198, 117, 0.3);">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 style="font-size: 19px; font-weight: 900; margin: 0; line-height: 1;">
                        <span style="color: #15803d;">Bazar</span><span style="color: #f97316;">Kinun</span><span style="color: #15803d;">.com</span>
                    </h2>
                    <span style="font-size: 11.5px; font-weight: 800; color: #64748b;">Staff Invoice &amp; Pricing Desk</span>
                </div>
            </div>
            <?php $backUrl = 'dashboard.php?tab=staff_panel&subtab_staff=receive'; ?>
            <a href="<?= $backUrl ?>" style="text-decoration: none; color: #334155; font-weight: 700; font-size: 13.5px; padding: 8px 16px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: white; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                <i class="fa-solid fa-arrow-left"></i> Back to Staff Panel
            </a>
        </div>
    </header>

    <main style="padding-bottom: 60px;">
        <div class="container">
            <!-- Page Title -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                <div>
                    <h1 style="font-size: 26px; color: var(--text-dark); margin: 0 0 4px 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-tags" style="color: var(--primary-color);"></i> Price Bazar &amp; Generate Invoice
                    </h1>
                    <p style="margin: 0; color: #64748b; font-size: 14px;">
                        Order Ref: <strong style="color: #0f172a;"><?= htmlspecialchars($order['order_number']) ?></strong> 
                        <?php if (!empty($uploadedImages)): ?>
                            <span style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 800; margin-left: 6px;"><i class="fa-solid fa-image"></i> Uploaded Photo List</span>
                        <?php else: ?>
                            <span style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 800; margin-left: 6px;"><i class="fa-solid fa-file-lines"></i> Written Grocery List</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($errorMsg)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <!-- Customer Details Card -->
            <div style="background: white; border-radius: 14px; padding: 18px 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1.5px solid #e2e8f0; margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; align-items: center;">
                <div>
                    <span style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Customer Name</span>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                        <i class="fa-solid fa-user" style="color: #9ca3af; margin-right: 4px;"></i> <?= htmlspecialchars($order['customer_name']) ?>
                    </div>
                </div>
                <div>
                    <span style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Phone Number</span>
                    <div style="margin-top: 2px;">
                        <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>" style="font-size: 15px; font-weight: 800; color: var(--primary-color); text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($order['customer_phone']) ?>
                        </a>
                    </div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <span style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Delivery Address</span>
                    <div style="font-size: 14px; font-weight: 700; color: #334155; margin-top: 2px;">
                        <i class="fa-solid fa-location-dot" style="color: #ef4444; margin-right: 4px;"></i> <?= htmlspecialchars($order['customer_address'] ?: $order['police_station'] ?: 'Location not specified') ?>
                    </div>
                </div>
            </div>

            <!-- CUSTOMER'S UPLOADED PHOTO LIST VIEWER (If images exist) -->
            <?php if (!empty($uploadedImages)): ?>
                <div style="background: #ffffff; border: 2px solid #6366f1; border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(99, 102, 241, 0.08);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #e0e7ff; color: #4338ca; width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">
                                <i class="fa-solid fa-camera"></i>
                            </span>
                            <div>
                                <h3 style="margin: 0; color: #1e1b4b; font-size: 17px; font-weight: 900;">Customer's Uploaded Grocery List (হাতের লেখা চিরকুট / ফর্দ)</h3>
                                <p style="margin: 0; color: #6366f1; font-size: 12.5px; font-weight: 600;">Click on any photo to open full-screen zoom preview.</p>
                            </div>
                        </div>
                        <span style="background: #e0e7ff; color: #4338ca; font-size: 12px; font-weight: 800; padding: 4px 12px; border-radius: 20px;">
                            <?= count($uploadedImages) ?> Photo(s) Attached
                        </span>
                    </div>

                    <div style="display: flex; gap: 16px; overflow-x: auto; padding: 6px 2px 12px 2px; align-items: flex-start;">
                        <?php foreach ($uploadedImages as $idx => $imgSrc): ?>
                            <div style="flex: 0 0 auto; width: 220px; border: 1.5px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #fafafa; padding: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align: center;">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Customer List Photo <?= $idx + 1 ?>" class="thumb-preview-img" onclick="openPhotoZoom('<?= htmlspecialchars($imgSrc) ?>')" style="width: 100%; height: 190px; object-fit: contain; border-radius: 6px; display: block; margin-bottom: 8px; background: white;">
                                <button type="button" onclick="openPhotoZoom('<?= htmlspecialchars($imgSrc) ?>')" style="background: #4f46e5; color: white; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; width: 100%; justify-content: center;">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i> Zoom Photo <?= $idx + 1 ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CUSTOMER'S WRITTEN LIST PRELOAD NOTIFICATION (If preloaded items exist) -->
            <?php if (!empty($preloadedItems)): ?>
                <div style="background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-check" style="color: #059669; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #065f46; font-size: 14.5px;">Customer's written grocery list items have been pre-filled below!</strong>
                            <div style="color: #047857; font-size: 13px;">Please enter the market price (৳) for each item purchased at the bazaar.</div>
                        </div>
                    </div>
                    <span style="background: #047857; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 800;">
                        <?= count($preloadedItems) ?> Items Pre-filled
                    </span>
                </div>
            <?php endif; ?>

            <!-- Invoice Details Form -->
            <div class="invoice-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; color: #0f172a; font-size: 20px; font-weight: 900; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-list-check" style="color: var(--primary-color);"></i> Invoice Items &amp; Pricing
                        </h3>
                        <p style="color: #64748b; font-size: 13.5px; margin: 0;">Type product name for live catalog suggestions. Enter quantity and actual purchased price.</p>
                    </div>
                    <button type="button" onclick="openProductCatalogModal()" style="background: linear-gradient(135deg, #00c675, #059669); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.35); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                        <i class="fa-solid fa-boxes-stacked"></i> Add Product from Catalog
                    </button>
                </div>

                <form method="POST" action="staff_invoice_edit.php?id=<?= $order['id'] ?>" onsubmit="return compileInvoiceSummary();">
                    <!-- Dynamic Items Table -->
                    <div style="overflow-x: auto; margin-bottom: 18px; border: 1.5px solid #e2e8f0; border-radius: 12px;">
                        <table id="invoice-items-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; min-width: 680px;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #334155; font-weight: 800;">
                                    <th style="padding: 12px 14px; width: 45px; text-align: center;">SL</th>
                                    <th style="padding: 12px 14px; min-width: 220px;">Item Description (Search Catalog)</th>
                                    <th style="padding: 12px 14px; min-width: 180px;">Specification (Brand, Size)</th>
                                    <th style="padding: 12px 14px; width: 120px;">Quantity</th>
                                    <th style="padding: 12px 14px; width: 140px;">Price (৳)</th>
                                    <th style="padding: 12px 14px; width: 60px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items-tbody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <button type="button" onclick="addInvoiceRow()" style="background: #f1f5f9; color: #334155; border: 1.5px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-plus" style="color: var(--primary-color);"></i> Add Custom / Manual Item Row
                        </button>
                    </div>

                    <!-- Weight and Complete Bill Breakdown Box -->
                    <div class="invoice-weight-row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 28px; background: #f8fafc; padding: 22px; border-radius: 14px; border: 1.5px solid #e2e8f0;">
                        <!-- Total Weight with Toggle -->
                        <div style="flex: 1; min-width: 260px;">
                            <label style="display: block; margin-bottom: 8px; color: #0f172a; font-weight: 800; font-size: 14px;">Total Estimated Weight</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="number" id="weight_val" step="0.01" min="0" placeholder="1.5" oninput="handleWeightInput()" style="flex: 1; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; box-sizing: border-box;">
                                <div style="display: flex; gap: 4px; background: #ffffff; padding: 4px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                                    <button type="button" id="unit-btn-kg" onclick="selectWeightUnit('kg')" style="padding: 7px 16px; border: none; border-radius: 6px; font-weight: 800; font-size: 13px; cursor: pointer; background: var(--primary-color); color: white;">kg</button>
                                    <button type="button" id="unit-btn-gram" onclick="selectWeightUnit('gram')" style="padding: 7px 16px; border: none; border-radius: 6px; font-weight: 800; font-size: 13px; cursor: pointer; background: transparent; color: #64748b;">gram</button>
                                </div>
                            </div>
                            <input type="hidden" name="total_weight" id="total_weight_input">
                            <span style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">
                                <i class="fa-solid fa-info-circle"></i> Delivery charge auto-calculates based on this weight.
                            </span>
                        </div>

                        <!-- Complete Financial Breakdown: Subtotal + Delivery Charge = Grand Total -->
                        <div style="flex: 1.2; min-width: 300px; background: white; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 14px; color: #475569;">
                                <span>Items Subtotal (পণ্যের মূল্য):</span>
                                <strong id="subtotal_display" style="color: #0f172a; font-size: 16px;">৳0.00</strong>
                            </div>
                            <input type="hidden" name="items_subtotal" id="items_subtotal_input" value="0.00">

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 14px; color: #475569; border-bottom: 1.5px dashed #cbd5e1; padding-bottom: 8px;">
                                <span>Delivery Charge (ডেলিভারি চার্জ):</span>
                                <div style="display: inline-flex; align-items: center; gap: 4px;">
                                    <span style="font-weight: 800; color: #047857;">+ ৳</span>
                                    <input type="number" id="delivery_charge_input" name="delivery_charge" step="0.01" min="0" value="<?= number_format($initialDeliveryCharge, 2, '.', '') ?>" oninput="recalculateInvoiceTotal()" style="width: 85px; padding: 5px 8px; border: 1.5px solid #10b981; border-radius: 6px; font-size: 14px; font-weight: 800; color: #047857; text-align: right; outline: none; background: #ecfdf5;">
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 900; color: #0f172a;">
                                <span>Grand Total (সর্বমোট বিল):</span>
                                <div style="position: relative;">
                                    <span style="font-size: 22px; color: #047857; font-weight: 900;" id="grand_total_display">৳0.00</span>
                                </div>
                            </div>
                            <input type="hidden" id="total_amount_input" name="total_amount" value="0.00">
                        </div>
                    </div>

                    <!-- Hidden inputs for compiled data -->
                    <textarea name="items_description" id="items_description_input" style="display: none;"></textarea>
                    <input type="hidden" name="raw_items_json" id="raw_items_json_input">

                    <button type="submit" style="width: 100%; background: linear-gradient(135deg, #00c675, #059669); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 16.5px; font-weight: 900; cursor: pointer; box-shadow: 0 4px 16px rgba(0, 198, 117, 0.35); display: flex; justify-content: center; align-items: center; gap: 10px; transition: all 0.2s;">
                        <i class="fa-solid fa-file-circle-check"></i> Generate Official Invoice &amp; Move to Delivery Queue
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- PHOTO ZOOM MODAL -->
    <div id="photo-zoom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box;" onclick="closePhotoZoom()">
        <div style="position: relative; max-width: 90vw; max-height: 90vh; background: white; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 10px 40px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
            <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 800; color: #0f172a; font-size: 15px;"><i class="fa-solid fa-image"></i> Customer's Grocery List Photo</span>
                <button type="button" onclick="closePhotoZoom()" style="background: #fee2e2; color: #ef4444; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-weight: 900; font-size: 16px;">&times;</button>
            </div>
            <img id="photo-zoom-img" src="" alt="Zoomed Photo" style="max-width: 100%; max-height: 75vh; object-fit: contain; border-radius: 8px;">
        </div>
    </div>

    <!-- ADD PRODUCTS TO ORDER MODAL (EXACT MATCH WITH IMAGE 1) -->
    <div id="catalog-product-modal" style="display: none; position: fixed; inset: 0; z-index: 100000; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 16px;">
        <div style="background: white; border-radius: 16px; width: 100%; max-width: 960px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.3); border: 1px solid #e2e8f0;">
            <!-- Header -->
            <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #ecfdf5; color: #00c675; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="fa-solid fa-cart-plus"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Add Products to Order</h3>
                        <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;">Search products from catalog to add directly to this order</p>
                    </div>
                </div>
                <button type="button" onclick="closeProductCatalogModal()" style="background: #f1f5f9; border: none; color: #64748b; width: 34px; height: 34px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Filter & Search Controls -->
            <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; background: #fafbfc;">
                <!-- Search Input -->
                <div style="position: relative; margin-bottom: 12px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px;"></i>
                    <input type="text" id="catalog-search-input" oninput="filterCatalogProducts()" placeholder="Search product" data-en-placeholder="Search product" data-bn-placeholder="পণ্য খুঁজুন" style="width: 100%; padding: 11px 14px 11px 42px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; font-family: inherit; box-sizing: border-box; outline: none; transition: 0.2s;" onfocus="this.style.borderColor='#00c675'" onblur="this.style.borderColor='#cbd5e1'">
                </div>

                <!-- Categories Scrollable Pills with Slider Arrows -->
                <div style="position: relative; display: flex; align-items: center;">
                    <button type="button" onclick="scrollCatPills(-180)" style="background: transparent; border: none; color: #64748b; cursor: pointer; padding: 4px 6px; font-size: 13px;"><i class="fa-solid fa-caret-left"></i></button>
                    <div id="catalog-category-pills" style="display: flex; gap: 8px; overflow-x: auto; scroll-behavior: smooth; padding: 2px 4px 4px 4px; scrollbar-width: none; flex: 1;">
                        <!-- Category Pills rendered dynamically -->
                    </div>
                    <button type="button" onclick="scrollCatPills(180)" style="background: transparent; border: none; color: #64748b; cursor: pointer; padding: 4px 6px; font-size: 13px;"><i class="fa-solid fa-caret-right"></i></button>
                </div>
            </div>

            <!-- Product Grid (Scrollable) -->
            <div style="padding: 20px 24px; overflow-y: auto; flex: 1; background: #f8fafc;" id="catalog-products-container">
                <div id="catalog-products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                    <!-- Dynamic Product Cards -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; background: white; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 700; color: #0f172a;">
                    <i class="fa-solid fa-bag-shopping" style="color: #00c675;"></i>
                    <span id="catalog-items-count-badge">Total items in order: 0</span>
                </div>
                <button type="button" onclick="closeProductCatalogModal()" style="background: #0f172a; border: none; color: white; padding: 10px 22px; border-radius: 8px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;">
                    <i class="fa-solid fa-check"></i> Done / Close
                </button>
            </div>
        </div>
    </div>

    <script>
        window.preloadedItems = <?= json_encode($preloadedItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
        window.catalogProducts = <?= json_encode($catalogProducts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
        window.catalogCategories = <?= json_encode($catalogCategories, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
        window.prodCatalog = window.catalogProducts;
        window.deliveryRules = <?= json_encode($deliveryRules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
        let currentWeightUnit = 'kg';
        let currentCatalogCategory = 'all';

        function scrollCatPills(delta) {
            const el = document.getElementById('catalog-category-pills');
            if (el) el.scrollLeft += delta;
        }

        function selectWeightUnit(unit) {
            currentWeightUnit = unit;
            const btnKg = document.getElementById('unit-btn-kg');
            const btnGram = document.getElementById('unit-btn-gram');
            if (btnKg && btnGram) {
                if (unit === 'kg') {
                    btnKg.style.background = 'var(--primary-color)';
                    btnKg.style.color = 'white';
                    btnGram.style.background = 'transparent';
                    btnGram.style.color = '#64748b';
                } else {
                    btnGram.style.background = 'var(--primary-color)';
                    btnGram.style.color = 'white';
                    btnKg.style.background = 'transparent';
                    btnKg.style.color = '#64748b';
                }
            }
            handleWeightInput();
        }

        function createRowElement(item = {}, rowIndex = 1) {
            const tr = document.createElement('tr');
            tr.className = 'invoice-item-row';
            tr.style.borderBottom = '1px solid #f1f5f9';

            const nameVal = item.name || '';
            const specVal = item.spec || '';
            const qtyVal = item.qty || '1';
            const priceVal = item.price || '';

            tr.innerHTML = `
                <td style="padding: 10px 14px; text-align: center; font-weight: 800; color: #94a3b8;" class="sl-number">${rowIndex}</td>
                <td style="padding: 10px 14px; position: relative;">
                    <input type="text" class="item-name-input" value="${escapeHtml(nameVal)}" placeholder="Type item name..." required oninput="handleItemInput(this)" onfocus="handleItemFocus(this)" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 700; outline: none; box-sizing: border-box;">
                    <div class="autocomplete-dropdown"></div>
                </td>
                <td style="padding: 10px 14px;">
                    <input type="text" class="item-spec-input" value="${escapeHtml(specVal)}" placeholder="Brand, Size (e.g. 5kg Bag)" oninput="checkAutoAddRow(this)" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
                </td>
                <td style="padding: 10px 14px;">
                    <input type="text" class="item-qty-input" value="${escapeHtml(qtyVal)}" placeholder="1" oninput="checkAutoAddRow(this)" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 700; outline: none; box-sizing: border-box; text-align: center;">
                </td>
                <td style="padding: 10px 14px;">
                    <input type="number" step="0.01" min="0" class="item-price-input" value="${escapeHtml(priceVal)}" placeholder="0.00" required oninput="checkAutoAddRow(this); recalculateInvoiceTotal();" style="width: 100%; padding: 9px 12px; border: 2px solid #10b981; border-radius: 8px; font-size: 15px; font-weight: 800; outline: none; box-sizing: border-box; color: #047857; text-align: right; background: #f0fdf4;">
                </td>
                <td style="padding: 10px 14px; text-align: center;">
                    <button type="button" onclick="deleteInvoiceRow(this)" style="background: #fee2e2; color: #ef4444; border: none; width: 34px; height: 34px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.15s;"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            `;
            return tr;
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function addInvoiceRow(item = {}) {
            const tbody = document.getElementById('invoice-items-tbody');
            const rowCount = tbody.getElementsByTagName('tr').length + 1;
            const tr = createRowElement(item, rowCount);
            tbody.appendChild(tr);
            updateSLNumbers();
            updateCatalogItemCount();
            return tr;
        }

        function checkAutoAddRow(element) {
            const row = element.closest('tr');
            const tbody = document.getElementById('invoice-items-tbody');
            const lastRow = tbody.lastElementChild;
            if (row === lastRow) {
                const itemName = row.querySelector('.item-name-input').value.trim();
                const itemPrice = row.querySelector('.item-price-input').value.trim();
                if (itemName || itemPrice) {
                    addInvoiceRow();
                }
            }
        }

        function deleteInvoiceRow(btn) {
            const tbody = document.getElementById('invoice-items-tbody');
            if (tbody.getElementsByTagName('tr').length <= 1) {
                alert("Invoice must have at least one item row.");
                return;
            }
            const row = btn.closest('tr');
            row.remove();
            updateSLNumbers();
            updateCatalogItemCount();
            recalculateInvoiceTotal();
        }

        function updateSLNumbers() {
            const rows = document.querySelectorAll('#invoice-items-tbody tr');
            rows.forEach((row, index) => {
                const slCell = row.querySelector('.sl-number');
                if (slCell) slCell.innerText = index + 1;
            });
        }

        function handleWeightInput() {
            const weightEl = document.getElementById('weight_val');
            const hiddenInput = document.getElementById('total_weight_input');
            const valStr = (weightEl ? weightEl.value : '').trim();
            const valNum = parseFloat(valStr) || 0;

            if (valStr) {
                hiddenInput.value = `${valStr} ${currentWeightUnit}`;
            } else {
                hiddenInput.value = '';
            }

            // Convert to kg for rule evaluation
            let weightInKg = valNum;
            if (currentWeightUnit === 'gram') {
                weightInKg = valNum / 1000.0;
            }

            // Calculate matching delivery charge rule
            let calculatedCharge = null;
            if (window.deliveryRules && window.deliveryRules.length > 0 && weightInKg > 0) {
                for (let r of window.deliveryRules) {
                    if (r.target_type === 'custom_list' && parseInt(r.is_active) === 1) {
                        if (r.based_on === 'weight') {
                            const minVal = parseFloat(r.min_value) || 0;
                            const maxVal = parseFloat(r.max_value) || 0;
                            const chargeVal = parseFloat(r.fixed_amount || r.charge) || 0;

                            if (r.operator === 'between' && weightInKg >= minVal && weightInKg <= maxVal) {
                                calculatedCharge = chargeVal;
                                break;
                            } else if (r.operator === 'less_than' && weightInKg <= maxVal) {
                                calculatedCharge = chargeVal;
                                break;
                            } else if (r.operator === 'greater_than' && weightInKg >= minVal) {
                                calculatedCharge = chargeVal;
                                break;
                            }
                        }
                    }
                }
            }

            if (calculatedCharge !== null) {
                const delInput = document.getElementById('delivery_charge_input');
                if (delInput) {
                    delInput.value = calculatedCharge.toFixed(2);
                }
            }

            recalculateInvoiceTotal();
        }

        function recalculateInvoiceTotal() {
            let subtotal = 0;
            const priceInputs = document.querySelectorAll('.item-price-input');
            priceInputs.forEach(input => {
                const val = parseFloat(input.value) || 0;
                subtotal += val;
            });

            const delInput = document.getElementById('delivery_charge_input');
            const deliveryCharge = parseFloat(delInput ? delInput.value : 0) || 0;
            const grandTotal = subtotal + deliveryCharge;

            const subtotalDisplay = document.getElementById('subtotal_display');
            const subtotalInput = document.getElementById('items_subtotal_input');
            const grandTotalDisplay = document.getElementById('grand_total_display');
            const grandTotalInput = document.getElementById('total_amount_input');

            if (subtotalDisplay) subtotalDisplay.innerText = '৳' + subtotal.toFixed(2);
            if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
            if (grandTotalDisplay) grandTotalDisplay.innerText = '৳' + grandTotal.toFixed(2);
            if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);
        }

        // ================= PRODUCT CATALOG MODAL SYSTEM (EXACT PARITY WITH IMAGE 1) ================= //
        function openProductCatalogModal() {
            const modal = document.getElementById('catalog-product-modal');
            if (!modal) return;
            modal.style.display = 'flex';
            renderCatalogCategories();
            currentCatalogCategory = 'all';
            const searchInput = document.getElementById('catalog-search-input');
            if (searchInput) {
                const isBn = (localStorage.getItem('appLanguage') === 'bn');
                searchInput.placeholder = isBn ? 'পণ্য খুঁজুন' : 'Search product';
                searchInput.value = '';
                searchInput.focus();
            }
            filterCatalogProducts();
            updateCatalogItemCount();
        }

        function closeProductCatalogModal() {
            const modal = document.getElementById('catalog-product-modal');
            if (modal) modal.style.display = 'none';
        }

        function renderCatalogCategories() {
            const pillsContainer = document.getElementById('catalog-category-pills');
            if (!pillsContainer) return;
            pillsContainer.innerHTML = '';

            const allBtn = document.createElement('button');
            allBtn.type = 'button';
            allBtn.className = 'cat-pill active-cat-pill';
            allBtn.innerText = 'All Products';
            allBtn.style.cssText = `
                background: #00c675;
                color: white;
                border: 1.5px solid #00c675;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 12.5px;
                font-weight: 700;
                cursor: pointer;
                white-space: nowrap;
                transition: all 0.2s;
            `;
            allBtn.onclick = () => selectCatalogCategory('all', allBtn);
            pillsContainer.appendChild(allBtn);

            const cats = window.catalogCategories || [];
            cats.forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cat-pill';
                btn.innerText = c.name || c.name_bn || 'Category';
                btn.style.cssText = `
                    background: white;
                    color: #475569;
                    border: 1.5px solid #cbd5e1;
                    padding: 6px 14px;
                    border-radius: 20px;
                    font-size: 12.5px;
                    font-weight: 600;
                    cursor: pointer;
                    white-space: nowrap;
                    transition: all 0.2s;
                `;
                btn.onclick = () => selectCatalogCategory(c.id, btn);
                pillsContainer.appendChild(btn);
            });
        }

        function selectCatalogCategory(catId, btn) {
            currentCatalogCategory = catId;
            const pills = document.querySelectorAll('#catalog-category-pills .cat-pill');
            pills.forEach(p => {
                p.style.background = 'white';
                p.style.color = '#475569';
                p.style.borderColor = '#cbd5e1';
                p.style.fontWeight = '600';
            });
            btn.style.background = '#00c675';
            btn.style.color = 'white';
            btn.style.borderColor = '#00c675';
            btn.style.fontWeight = '700';

            filterCatalogProducts();
        }

        function filterCatalogProducts() {
            const query = (document.getElementById('catalog-search-input')?.value || '').trim().toLowerCase();
            let filtered = window.catalogProducts || [];

            if (currentCatalogCategory !== 'all') {
                filtered = filtered.filter(p => parseInt(p.category_id) === parseInt(currentCatalogCategory));
            }

            if (query) {
                filtered = filtered.filter(p => {
                    const t = (p.title || '').toLowerCase();
                    const tb = (p.title_bn || '').toLowerCase();
                    const b = (p.brand || '').toLowerCase();
                    const spec = (p.poriman || '').toLowerCase();
                    const cat = (p.category_name || '').toLowerCase();
                    return t.includes(query) || tb.includes(query) || b.includes(query) || spec.includes(query) || cat.includes(query);
                });
            }

            renderCatalogProducts(filtered);
        }

        function renderCatalogProducts(products) {
            const grid = document.getElementById('catalog-products-grid');
            if (!grid) return;
            grid.innerHTML = '';

            if (!products || products.length === 0) {
                grid.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 40px; font-size: 14px; background: white; border-radius: 12px; border: 1.5px dashed #cbd5e1;">No products found matching your search.</div>`;
                return;
            }

            products.forEach(p => {
                const title = p.title || p.title_bn || 'Product';
                const spec = p.poriman || p.poriman_bn || p.brand || '1 kg';
                const price = parseFloat(p.price || 0);
                const oldPrice = parseFloat(p.old_price || 0);
                const img = p.image || 'assets/bazarkinundotcom_logo.png';

                const card = document.createElement('div');
                card.style.cssText = `
                    background: white;
                    border: 1.5px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 14px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
                `;
                card.onmouseover = () => { card.style.borderColor = '#00c675'; card.style.transform = 'translateY(-2px)'; card.style.boxShadow = '0 6px 16px rgba(0,198,117,0.12)'; };
                card.onmouseout = () => { card.style.borderColor = '#e2e8f0'; card.style.transform = 'translateY(0)'; card.style.boxShadow = '0 2px 6px rgba(0,0,0,0.02)'; };

                card.innerHTML = `
                    <div>
                        <div style="width: 100%; height: 110px; display: flex; align-items: center; justify-content: center; background: #fafbfc; border-radius: 8px; margin-bottom: 10px; overflow: hidden;">
                            <img src="${img}" alt="${escapeHtml(title)}" style="max-width: 100%; max-height: 100px; object-fit: contain;" onerror="this.src='assets/bazarkinundotcom_logo.png'">
                        </div>
                        <h4 style="margin: 0 0 4px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; line-height: 1.3; min-height: 36px;">${escapeHtml(title)}</h4>
                        <p style="margin: 0 0 10px 0; font-size: 12px; color: #64748b; font-weight: 600;">${escapeHtml(spec || 'Standard')}</p>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                        <div style="display: flex; align-items: baseline; gap: 6px;">
                            <span style="font-size: 16px; font-weight: 900; color: #00c675;">৳${price.toFixed(0)}</span>
                            ${oldPrice > price ? `<span style="font-size: 12px; color: #94a3b8; text-decoration: line-through;">৳${oldPrice.toFixed(0)}</span>` : ''}
                        </div>
                        <button type="button" onclick="addCatalogProductToInvoice(${p.id}, this)" style="background: white; border: 1.5px solid #00c675; color: #00c675; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;" onmouseover="this.style.background='#00c675'; this.style.color='#fff'" onmouseout="this.style.background='white'; this.style.color='#00c675'">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function addCatalogProductToInvoice(prodId, btn = null) {
            const prod = (window.catalogProducts || []).find(p => parseInt(p.id) === parseInt(prodId));
            if (!prod) return;

            const name = prod.title || prod.title_bn || 'Item';
            const spec = prod.poriman || prod.poriman_bn || prod.brand || '';
            const price = parseFloat(prod.price || 0);

            const tbody = document.getElementById('invoice-items-tbody');
            const existingRows = tbody.querySelectorAll('tr');
            let found = false;

            existingRows.forEach(tr => {
                const nameInput = tr.querySelector('.item-name-input');
                if (nameInput && nameInput.value.trim().toLowerCase() === name.toLowerCase()) {
                    const qtyInput = tr.querySelector('.item-qty-input');
                    if (qtyInput) {
                        qtyInput.value = (parseInt(qtyInput.value) || 1) + 1;
                        found = true;
                    }
                }
            });

            if (!found) {
                // If only 1 row exists and it's empty, replace it
                if (existingRows.length === 1) {
                    const firstName = existingRows[0].querySelector('.item-name-input')?.value.trim();
                    const firstPrice = existingRows[0].querySelector('.item-price-input')?.value.trim();
                    if (!firstName && (!firstPrice || parseFloat(firstPrice) === 0)) {
                        existingRows[0].remove();
                    }
                }
                addInvoiceRow({
                    name: name,
                    spec: spec,
                    qty: '1',
                    price: price.toFixed(2)
                });
            }

            recalculateInvoiceTotal();
            updateCatalogItemCount();

            if (btn) {
                const oldHtml = btn.innerHTML;
                btn.innerHTML = `<i class="fa-solid fa-check"></i> Added`;
                btn.style.background = '#00c675';
                btn.style.color = '#fff';
                setTimeout(() => {
                    btn.innerHTML = oldHtml;
                    btn.style.background = 'white';
                    btn.style.color = '#00c675';
                }, 1000);
            }
        }

        function updateCatalogItemCount() {
            const rows = document.querySelectorAll('#invoice-items-tbody tr');
            let count = 0;
            rows.forEach(tr => {
                const name = tr.querySelector('.item-name-input')?.value.trim();
                const qty = parseInt(tr.querySelector('.item-qty-input')?.value) || 1;
                if (name) count += qty;
            });
            const badge = document.getElementById('catalog-items-count-badge');
            if (badge) badge.innerText = `Total items in order: ${count}`;
        }

        // ================= AUTOCOMPLETE PRODUCT SUGGESTIONS ================= //
        function handleItemFocus(input) {
            const query = input.value.trim().toLowerCase();
            if (query.length >= 1) {
                showSuggestions(input, query);
            }
        }

        function handleItemInput(input) {
            checkAutoAddRow(input);
            const query = input.value.trim().toLowerCase();
            if (query.length >= 1) {
                showSuggestions(input, query);
            } else {
                hideSuggestions(input);
            }
        }

        function showSuggestions(input, query) {
            const container = input.parentElement;
            const dropdown = container.querySelector('.autocomplete-dropdown');
            if (!dropdown) return;

            const matches = (window.catalogProducts || []).filter(p => {
                const t = (p.title || '').toLowerCase();
                const tb = (p.title_bn || '').toLowerCase();
                return t.includes(query) || tb.includes(query);
            }).slice(0, 6);

            if (matches.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            dropdown.innerHTML = '';
            matches.forEach(p => {
                const div = document.createElement('div');
                div.className = 'autocomplete-item';
                div.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${p.image ? `<img src="${p.image}" style="width: 28px; height: 28px; object-fit: contain; border-radius: 4px; border: 1px solid #e2e8f0;" onerror="this.src='assets/bazarkinundotcom_logo.png'">` : '<i class="fa-solid fa-basket-shopping" style="color: var(--primary-color);"></i>'}
                        <div>
                            <strong style="color: #0f172a; font-size: 13.5px;">${p.title}</strong>
                            ${p.title_bn ? `<span style="color: #64748b; font-size: 12px;"> (${p.title_bn})</span>` : ''}
                            <div style="font-size: 11.5px; color: #94a3b8;">${p.poriman || '1 unit'}</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-weight: 800; color: #047857; font-size: 13.5px;">৳${parseFloat(p.price || 0).toFixed(2)}</span>
                    </div>
                `;
                div.onclick = () => selectProductSuggestion(input, p);
                dropdown.appendChild(div);
            });
            dropdown.style.display = 'block';
        }

        function hideSuggestions(input) {
            const container = input.parentElement;
            const dropdown = container.querySelector('.autocomplete-dropdown');
            if (dropdown) dropdown.style.display = 'none';
        }

        function selectProductSuggestion(input, prod) {
            const row = input.closest('tr');
            input.value = prod.title;
            const specInput = row.querySelector('.item-spec-input');
            const priceInput = row.querySelector('.item-price-input');
            
            if (specInput && !specInput.value.trim() && prod.poriman) {
                specInput.value = prod.poriman;
            }
            if (priceInput && (!priceInput.value.trim() || parseFloat(priceInput.value) === 0)) {
                priceInput.value = parseFloat(prod.price || 0).toFixed(2);
            }

            hideSuggestions(input);
            recalculateInvoiceTotal();
            checkAutoAddRow(input);
            if (priceInput) priceInput.focus();
        }

        // Close autocomplete when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.item-name-input') && !e.target.closest('.autocomplete-dropdown')) {
                document.querySelectorAll('.autocomplete-dropdown').forEach(d => d.style.display = 'none');
            }
        });

        // PHOTO ZOOM MODAL
        function openPhotoZoom(src) {
            const modal = document.getElementById('photo-zoom-modal');
            const img = document.getElementById('photo-zoom-img');
            img.src = src;
            modal.style.display = 'flex';
        }

        function closePhotoZoom() {
            document.getElementById('photo-zoom-modal').style.display = 'none';
        }

        function compileInvoiceSummary() {
            handleWeightInput();
            const rows = document.querySelectorAll('#invoice-items-tbody tr');
            let summaryText = [];
            let itemsData = [];
            
            rows.forEach((row, index) => {
                const itemName = (row.querySelector('.item-name-input')?.value || '').trim();
                const itemSpec = (row.querySelector('.item-spec-input')?.value || '').trim();
                const itemQty = (row.querySelector('.item-qty-input')?.value || '1').trim();
                const itemPrice = (row.querySelector('.item-price-input')?.value || '0.00').trim();
                
                if (itemName || parseFloat(itemPrice) > 0) {
                    let line = `${index + 1}. ${itemName}`;
                    if (itemSpec) line += ` (${itemSpec})`;
                    line += ` - Qty: ${itemQty} - ৳${itemPrice}`;
                    summaryText.push(line);

                    itemsData.push({
                        name: itemName,
                        spec: itemSpec,
                        qty: itemQty,
                        price: parseFloat(itemPrice) || 0
                    });
                }
            });

            if (itemsData.length === 0) {
                alert("Please add at least one item with a valid price.");
                return false;
            }
            
            document.getElementById('items_description_input').value = summaryText.join('\n');
            document.getElementById('raw_items_json_input').value = JSON.stringify(itemsData);
            return true;
        }

        // INITIALIZATION
        window.addEventListener('DOMContentLoaded', () => {
            selectWeightUnit('kg');
            const tbody = document.getElementById('invoice-items-tbody');
            tbody.innerHTML = '';

            if (window.preloadedItems && window.preloadedItems.length > 0) {
                window.preloadedItems.forEach((it, idx) => {
                    addInvoiceRow(it);
                });
            } else {
                addInvoiceRow();
            }

            recalculateInvoiceTotal();
            updateCatalogItemCount();
        });
    </script>
</body>
</html>
