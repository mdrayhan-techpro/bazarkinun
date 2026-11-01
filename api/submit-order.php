<?php
// api/submit-order.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Invalid request method'], 405);
}

$inputData = json_decode(file_get_contents('php://input'), true);
if (!$inputData) {
    $inputData = $_POST;
}

$action = $inputData['action'] ?? '';
$customerName = trim($inputData['customer_name'] ?? 'Guest Customer');
$rawCustomerPhone = trim($inputData['customer_phone'] ?? '');
$customerPhone = normalizeBdPhone($rawCustomerPhone);
$customerAddress = trim($inputData['customer_address'] ?? $inputData['police_station'] ?? '');
$policeStation = mb_substr(trim($inputData['police_station'] ?? ''), 0, 450);
$totalAmount = floatval($inputData['total_amount'] ?? 0);
$couponCode = strtoupper(trim($inputData['coupon_code'] ?? ''));
$discountAmount = floatval($inputData['discount_amount'] ?? 0);
$htmlContent = sanitizeOrderHtml($inputData['html_content'] ?? $inputData['content_text'] ?? '');
$items = $inputData['items'] ?? [];
$images = $inputData['images'] ?? [];
$orderType = $inputData['order_type'] ?? '';
$listId = $inputData['list_id'] ?? null;
$listCode = $inputData['list_code'] ?? null;
$orderId = $inputData['order_id'] ?? null;
$paymentMethod = strtolower(trim($inputData['payment_method'] ?? 'cod'));
if (!in_array($paymentMethod, ['cod', 'bkash', 'nagad', 'rocket'])) {
    $paymentMethod = 'cod';
}
$transactionId = trim($inputData['transaction_id'] ?? $inputData['trx_id'] ?? '');
$paymentSenderPhone = normalizeBdPhone(trim($inputData['payment_sender_phone'] ?? $inputData['sender_phone'] ?? ''));

// Validate manual mobile banking fields
if (in_array($paymentMethod, ['bkash', 'nagad', 'rocket'])) {
    if (empty($transactionId)) {
        jsonResponse(['status' => 'error', 'message' => 'অনুগ্রহ করে পেমেন্টের TrxID (ট্রানজেকশন আইডি) লিখুন।'], 400);
    }
    if (empty($paymentSenderPhone)) {
        jsonResponse(['status' => 'error', 'message' => 'যে মোবাইল নম্বর থেকে টাকা পাঠিয়েছেন সেই নম্বরটি লিখুন।'], 400);
    }
}
$paymentStatus = 'pending';

if (empty($customerPhone)) {
    if (!empty($_SESSION['customer_phone'])) {
        $customerPhone = normalizeBdPhone($_SESSION['customer_phone']);
        $customerName = $_SESSION['customer_name'] ?? $customerName;
    }
}

if (empty($customerPhone)) {
    jsonResponse(['status' => 'error', 'message' => 'অর্ডার সম্পন্ন করতে অনুগ্রহ করে প্রথমে লগইন করুন।'], 401);
}

try {
    // 0. Customer Confirm Invoice Action (Protected against IDOR)
    if ($action === 'confirm_invoice') {
        $targetId = $orderId ?? $inputData['id'] ?? $inputData['order_number'] ?? null;
        if (!$targetId) {
            jsonResponse(['status' => 'error', 'message' => 'Order/Invoice identifier is required.'], 400);
        }
        $targetNumId = is_numeric($targetId) ? intval($targetId) : 0;
        $custUserId = $_SESSION['customer_id'] ?? 0;
        $upd = $pdo->prepare("
            UPDATE `orders` 
            SET `status` = 'customer_confirmed' 
            WHERE (id = ? OR order_number = ? OR invoice_number = ?)
              AND (customer_phone = ? OR (user_id = ? AND user_id > 0))
        ");
        $upd->execute([$targetNumId, $targetId, $targetId, $customerPhone, $custUserId]);

        if ($upd->rowCount() === 0) {
            jsonResponse(['status' => 'error', 'message' => 'অর্ডারটি পাওয়া যায়নি অথবা আপনার এই অর্ডারে অনুমতি নেই।'], 403);
        }

        logAuditActivity($pdo, 'Customer Confirmed Invoice', "Customer confirmed invoice for order #{$targetId}", $customerName, 'customer');

        jsonResponse([
            'status' => 'success',
            'message' => 'ইনভয়েসটি সফলভাবে কনফার্ম করা হয়েছে! রাইডার শীঘ্রই ডেলিভারি নিয়ে আসছেন।'
        ]);
        exit;
    }

    // 0.1 Customer Cancel Order / Invoice Action (Protected against IDOR)
    if ($action === 'cancel_order' || $action === 'cancel_invoice') {
        $targetId = $orderId ?? $inputData['id'] ?? $inputData['order_number'] ?? $inputData['invoice_number'] ?? null;
        $cancelReason = trim($inputData['reason'] ?? $inputData['cancel_reason'] ?? 'Customer cancelled the order');

        if (!$targetId) {
            jsonResponse(['status' => 'error', 'message' => 'Order/Invoice identifier is required.'], 400);
        }

        $targetNumId = is_numeric($targetId) ? intval($targetId) : 0;
        $custUserId = $_SESSION['customer_id'] ?? 0;
        // Fetch current order strictly verifying customer ownership
        $chkStmt = $pdo->prepare("
            SELECT id, order_number, invoice_number, status, customer_phone, stock_deducted 
            FROM orders 
            WHERE (id = ? OR order_number = ? OR invoice_number = ?)
              AND (customer_phone = ? OR (user_id = ? AND user_id > 0))
        ");
        $chkStmt->execute([$targetNumId, $targetId, $targetId, $customerPhone, $custUserId]);
        $order = $chkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            jsonResponse(['status' => 'error', 'message' => 'অর্ডারটি খুঁজে পাওয়া যায়নি অথবা আপনার এই অর্ডারে অনুমতি নেই।'], 404);
        }

        $allowedCancelStatuses = ['pending', 'invoice_created', 'draft', 'accepted'];
        if (!in_array($order['status'], $allowedCancelStatuses)) {
            jsonResponse([
                'status' => 'error',
                'message' => 'ডেলিভারিম্যান কেনাকাটা বা ডেলিভারি শুরু করেছেন বিধায় সরাসরি বাতিল করা সম্ভব নয়। অনুগ্রহ করে হেল্পলাইনে (01919-668960) যোগাযোগ করুন।'
            ], 403);
        }

        $upd = $pdo->prepare("
            UPDATE `orders` 
            SET `status` = 'cancelled' 
            WHERE id = ?
        ");
        $upd->execute([$order['id']]);

        logAuditActivity($pdo, 'Customer Cancelled Order', "Customer cancelled order #{$targetId}. Reason: {$cancelReason}", $customerName, 'customer');

        jsonResponse([
            'status' => 'success',
            'message' => 'অর্ডারটি সফলভাবে বাতিল করা হয়েছে।'
        ]);
        exit;
    }

    // Validate delivery address strictly for all order/invoice creations
    if ($action !== 'save_list') {
        if (!isValidLocationAddress($customerAddress) && !isValidLocationAddress($policeStation)) {
            jsonResponse([
                'status' => 'error',
                'message' => 'অর্ডার সম্পন্ন করতে অনুগ্রহ করে ম্যাপ বা সার্চ থেকে একটি সুনির্দিষ্ট ডেলিভারি ঠিকানা নির্বাচন করুন।'
            ], 422);
        }
    }

    // 1. Find or create user
    $userStmt = $pdo->prepare("SELECT id, name FROM users WHERE phone = ?");
    $userStmt->execute([$customerPhone]);
    $user = $userStmt->fetch();

    if ($user) {
        $userId = $user['id'];
        if (isValidLocationAddress($customerAddress)) {
            $pdo->prepare("UPDATE users SET default_location = ? WHERE id = ? AND (default_location IS NULL OR default_location = '')")->execute([$customerAddress, $userId]);
        }
    } else {
        $defaultLoc = isValidLocationAddress($customerAddress) ? $customerAddress : null;
        $insUser = $pdo->prepare("INSERT INTO users (name, phone, default_location) VALUES (?, ?, ?)");
        $insUser->execute([$customerName, $customerPhone, $defaultLoc]);
        $userId = $pdo->lastInsertId();
    }

    // CASE 0.5: Update existing website invoice
    if ($action === 'update_website_invoice' || !empty($inputData['edit_invoice_number']) || !empty($inputData['edit_order_id'])) {
        $editInvoiceNumber = $inputData['edit_invoice_number'] ?? $inputData['invoice_number'] ?? '';
        $editOrderId = $inputData['edit_order_id'] ?? $inputData['order_id'] ?? null;
        $editOrderIdNum = is_numeric($editOrderId) ? intval($editOrderId) : 0;

        $checkStmt = $pdo->prepare("
            SELECT * FROM `orders` 
            WHERE (id = ? OR invoice_number = ? OR order_number = ?) 
            AND `status` = 'pending' 
            LIMIT 1
        ");
        $checkStmt->execute([$editOrderIdNum, $editInvoiceNumber, $editInvoiceNumber]);
        $existingOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingOrder) {
            $currentInvNo = $existingOrder['invoice_number'] ?: $existingOrder['order_number'];
            $htmlContent = str_replace('{{INVOICE_NO}}', $currentInvNo, $htmlContent);

            // Calculate delivery charge for updated website order
            $ruleQuery = $pdo->prepare("SELECT * FROM `delivery_charge_rules` WHERE `target_type` = 'website' AND `is_active` = 1 ORDER BY `priority` ASC");
            $ruleQuery->execute();
            $rules = $ruleQuery->fetchAll();
            $deliveryCharge = 60.00;
            foreach ($rules as $r) {
                if ($r['based_on'] === 'order_amount') {
                    $minVal = floatval($r['min_value']);
                    $maxVal = floatval($r['max_value']);
                    if ($r['operator'] === 'greater_than_or_equal' && $totalAmount >= $minVal) {
                        $deliveryCharge = ($r['calculation_method'] === 'free') ? 0.00 : floatval($r['fixed_amount'] ?: $r['charge']);
                        break;
                    }
                }
            }
            if (empty($rules)) {
                $deliveryCharge = ($totalAmount >= 1000) ? 0.00 : 60.00;
            }

            $updOrder = $pdo->prepare("
                UPDATE `orders` 
                SET `total_amount` = ?, 
                    `delivery_charge` = ?, 
                    `coupon_code` = ?,
                    `discount_amount` = ?,
                    `html_content` = ?, 
                    `customer_address` = ? 
                WHERE `id` = ?
            ");
            $updOrder->execute([
                $totalAmount,
                $deliveryCharge,
                $couponCode ?: null,
                $discountAmount,
                $htmlContent,
                $customerAddress,
                $existingOrder['id']
            ]);

            // Replace order items
            $delItems = $pdo->prepare("DELETE FROM `order_items` WHERE `order_id` = ?");
            $delItems->execute([$existingOrder['id']]);

            if (!empty($items) && is_array($items)) {
                $itemStmt = $pdo->prepare("
                    INSERT INTO `order_items` (`order_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($items as $item) {
                    $name = $item['name'] ?? 'Product';
                    $qty = intval($item['quantity'] ?? 1);
                    $price = floatval($item['price'] ?? 0);
                    $subtotal = $qty * $price;
                    $itemStmt->execute([$existingOrder['id'], $name, $qty, $price, $subtotal]);
                }
            }

            logAuditActivity($pdo, 'Update Invoice', "Customer updated items for pending invoice #{$currentInvNo}", $customerName, 'customer');

            jsonResponse([
                'status' => 'success',
                'type' => 'invoice',
                'invoice_number' => $currentInvNo,
                'order_number' => $currentInvNo,
                'order_id' => $existingOrder['id'],
                'message' => "আপনার ইনভয়েস সফলভাবে আপডেট করা হয়েছে (ইনভয়েস নং: {$currentInvNo})!"
            ]);
            exit;
        }
    }

    // Handle base64 images upload and sanitize htmlContent to prevent MySQL max_allowed_packet overflow
    $savedImageUrls = [];
    $listsDir = __DIR__ . '/../uploads/lists/';
    if (!is_dir($listsDir)) {
        mkdir($listsDir, 0777, true);
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!empty($images) && is_array($images)) {
        foreach ($images as $img) {
            if (is_string($img) && preg_match('/^data:image\/(\w+);base64,/', $img, $m)) {
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                if (!in_array($ext, $allowedExts)) continue;
                $raw = base64_decode(substr($img, strpos($img, ',') + 1));
                if ($raw !== false) {
                    $filename = 'list_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.' . $ext;
                    if (file_put_contents($listsDir . $filename, $raw)) {
                        $savedUrl = 'uploads/lists/' . $filename;
                        $savedImageUrls[] = $savedUrl;
                        if (!empty($htmlContent)) {
                            $htmlContent = str_replace($img, $savedUrl, $htmlContent);
                        }
                    }
                }
            } elseif (!empty($img) && is_string($img)) {
                $cleanUrl = filter_var($img, FILTER_SANITIZE_URL);
                if ($cleanUrl) $savedImageUrls[] = $cleanUrl;
            }
        }
    }

    // Extract and replace any remaining embedded base64 images inside $htmlContent
    if (!empty($htmlContent) && strpos($htmlContent, 'data:image/') !== false) {
        $htmlContent = preg_replace_callback('/src=["\'](data:image\/(\w+);base64,([^"\']+))["\']/i', function($m) use ($listsDir, &$savedImageUrls, $allowedExts) {
            $ext = strtolower($m[2]) === 'jpeg' ? 'jpg' : strtolower($m[2]);
            if (!in_array($ext, $allowedExts)) return '';
            $raw = base64_decode($m[3]);
            if ($raw !== false) {
                $filename = 'list_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.' . $ext;
                if (file_put_contents($listsDir . $filename, $raw)) {
                    $savedUrl = 'uploads/lists/' . $filename;
                    if (!in_array($savedUrl, $savedImageUrls)) {
                        $savedImageUrls[] = $savedUrl;
                    }
                    return 'src="' . $savedUrl . '"';
                }
            }
            return $m[0];
        }, $htmlContent);
    }

    // CASE 1: Save Custom List ONLY (No Order, No Invoice)
    if ($action === 'save_list' || ($inputData['status'] ?? '') === 'Draft') {
        $typeStr = (!empty($savedImageUrls) || $orderType === 'uploaded') ? 'uploaded' : 'written';
        $imagesJson = !empty($savedImageUrls) ? json_encode($savedImageUrls) : null;

        if (!empty($listId) || !empty($listCode)) {
            $listIdNum = is_numeric($listId) ? intval($listId) : 0;
            $chkList = $pdo->prepare("SELECT * FROM `custom_lists` WHERE (id = ? OR list_code = ? OR title = ?) AND (user_id = ? OR customer_phone = ?) LIMIT 1");
            $chkList->execute([$listIdNum, $listCode, $listCode, $userId, $customerPhone]);
            $existingList = $chkList->fetch();
            if ($existingList) {
                $updList = $pdo->prepare("
                    UPDATE `custom_lists` 
                    SET `customer_name` = ?, 
                        `customer_phone` = ?, 
                        `customer_address` = ?,
                        `list_type` = ?, 
                        `content_text` = ?, 
                        `images` = ? 
                    WHERE `id` = ?
                ");
                $updList->execute([
                    $customerName,
                    $customerPhone,
                    $customerAddress,
                    $typeStr,
                    $htmlContent,
                    $imagesJson,
                    $existingList['id']
                ]);

                jsonResponse([
                    'status' => 'success',
                    'type' => 'list',
                    'list_code' => $existingList['list_code'] ?: $existingList['title'],
                    'list_id' => $existingList['id'],
                    'message' => 'বাজারের ফর্দটি সফলভাবে আপডেট করা হয়েছে!'
                ]);
                exit;
            }
        }

        $generatedListCode = generateSequentialCode($pdo, 'LST', $customerPhone, $customerName, $userId);

        if (!empty($htmlContent)) {
            $htmlContent = preg_replace('/LST-\d+-[A-Z0-9]+-\d+/i', $generatedListCode, $htmlContent);
            $htmlContent = str_ireplace('List Code: LST-Saved', "List Code: {$generatedListCode}", $htmlContent);
        }

        $insList = $pdo->prepare("
            INSERT INTO `custom_lists` 
            (`user_id`, `list_code`, `customer_name`, `customer_phone`, `customer_address`, `list_type`, `title`, `content_text`, `images`, `status`, `created_at`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())
        ");
        $insList->execute([
            $userId,
            $generatedListCode,
            $customerName,
            $customerPhone,
            $customerAddress,
            $typeStr,
            $generatedListCode,
            $htmlContent,
            $imagesJson
        ]);
        $newListId = $pdo->lastInsertId();

        jsonResponse([
            'status' => 'success',
            'type' => 'list',
            'list_code' => $generatedListCode,
            'list_id' => $newListId,
            'message' => 'বাজারের ফর্দটি সফলভাবে ড্রাফট হিসেবে সংরক্ষণ করা হয়েছে!'
        ]);
        exit;
    }

    // CASE 2: Order from Written / Uploaded List (Order Created, No Invoice Yet)
    if ($action === 'order_list' || $orderType === 'text_list' || $orderType === 'uploaded') {
        $editOrderId = $inputData['edit_order_id'] ?? null;
        $editOrderNumber = $inputData['edit_order_number'] ?? null;
        $typeStr = ($orderType === 'uploaded' || !empty($savedImageUrls)) ? 'uploaded' : 'text_list';

        if ($editOrderId || $editOrderNumber) {
            $editOrderIdNum = is_numeric($editOrderId) ? intval($editOrderId) : 0;
            $chkOrd = $pdo->prepare("SELECT * FROM `orders` WHERE (id = ? OR order_number = ?) AND (user_id = ? OR customer_phone = ?) AND `status` = 'pending' LIMIT 1");
            $chkOrd->execute([$editOrderIdNum, $editOrderNumber, $userId, $customerPhone]);
            $existingOrder = $chkOrd->fetch();
            $targetOrderNum = $existingOrder['order_number'];
            if (!empty($htmlContent)) {
                if (!empty($listCode)) {
                    $htmlContent = str_replace($listCode, $targetOrderNum, $htmlContent);
                }
                $htmlContent = preg_replace('/LST-\d+-[A-Z0-9]+-\d+/i', $targetOrderNum, $htmlContent);
                $htmlContent = str_ireplace('List Code:', 'Order No:', $htmlContent);
                $htmlContent = str_ireplace('Custom Grocery List', 'Grocery List Order', $htmlContent);
                $htmlContent = str_ireplace('Document:', 'Order Type:', $htmlContent);
            }

            if ($existingOrder) {
                $updOrd = $pdo->prepare("
                    UPDATE `orders` 
                    SET `customer_name` = ?, 
                        `customer_phone` = ?, 
                        `customer_address` = ?, 
                        `html_content` = ?, 
                        `order_type` = ? 
                    WHERE `id` = ?
                ");
                $updOrd->execute([
                    $customerName,
                    $customerPhone,
                    $customerAddress,
                    $htmlContent,
                    $typeStr,
                    $existingOrder['id']
                ]);

                // Update custom_order_lists if exists
                $updCol = $pdo->prepare("UPDATE `custom_order_lists` SET `raw_text` = ?, `image_urls` = ? WHERE `order_id` = ?");
                $updCol->execute([
                    $htmlContent,
                    !empty($savedImageUrls) ? json_encode($savedImageUrls) : null,
                    $existingOrder['id']
                ]);

                jsonResponse([
                    'status' => 'success',
                    'type' => 'order',
                    'order_number' => $existingOrder['order_number'],
                    'order_id' => $existingOrder['id'],
                    'message' => "আপনার ফর্দ অর্ডারটি সফলভাবে আপডেট করা হয়েছে (অর্ডার নং: {$existingOrder['order_number']})!"
                ]);
                exit;
            }
        }

        // If ordered from an existing draft list, fetch content and remove from custom_lists
        if ($listId || $listCode) {
            $listIdNum = is_numeric($listId) ? intval($listId) : 0;
            $fetchList = $pdo->prepare("SELECT * FROM `custom_lists` WHERE (id = ? OR list_code = ? OR title = ?) AND (user_id = ? OR customer_phone = ?)");
            $fetchList->execute([$listIdNum, $listCode, $listCode, $userId, $customerPhone]);
            $existingList = $fetchList->fetch();
            if ($existingList) {
                if (empty($htmlContent)) {
                    $htmlContent = $existingList['content_text'];
                }
                if (empty($savedImageUrls) && !empty($existingList['images'])) {
                    $savedImageUrls = is_string($existingList['images']) ? json_decode($existingList['images'], true) : $existingList['images'];
                }
                if (empty($orderType)) {
                    $orderType = $existingList['list_type'];
                }
            }
            $delStmt = $pdo->prepare("DELETE FROM `custom_lists` WHERE (id = ? OR list_code = ? OR title = ?) AND (user_id = ? OR customer_phone = ?)");
            $delStmt->execute([$listIdNum, $listCode, $listCode, $userId, $customerPhone]);
        }

        $generatedOrderNumber = generateSequentialCode($pdo, 'ORD', $customerPhone, $customerName, $userId);
        $typeStr = ($orderType === 'uploaded' || !empty($savedImageUrls)) ? 'uploaded' : 'text_list';

        if (!empty($htmlContent)) {
            if (!empty($listCode)) {
                $htmlContent = str_replace($listCode, $generatedOrderNumber, $htmlContent);
            }
            $htmlContent = preg_replace('/LST-\d+-[A-Z0-9]+-\d+/i', $generatedOrderNumber, $htmlContent);
            $htmlContent = str_ireplace('List Code:', 'Order No:', $htmlContent);
            $htmlContent = str_ireplace('Custom Grocery List', 'Grocery List Order', $htmlContent);
            $htmlContent = str_ireplace('Document:', 'Order Type:', $htmlContent);
        }

        // Calculate delivery charge rule for custom list
        $ruleQuery = $pdo->prepare("SELECT * FROM `delivery_charge_rules` WHERE `target_type` = 'custom_list' AND `is_active` = 1 ORDER BY `priority` ASC LIMIT 1");
        $ruleQuery->execute();
        $rule = $ruleQuery->fetch();
        $deliveryCharge = $rule ? floatval($rule['charge'] ?? $rule['fixed_amount'] ?? 60.00) : 60.00;

        // Resolve destination location for custom list
        $resolvedLoc = resolveLocationInfo($pdo, $customerAddress, $inputData['district'] ?? '', $policeStation ?: ($inputData['thana'] ?? ''));
        $destDistrict = $resolvedLoc['district'];
        $destThana = (!empty($resolvedLoc['police_station']) && $resolvedLoc['police_station'] !== 'All Police Stations') ? $resolvedLoc['police_station'] : $policeStation;
        $destThana = mb_substr($destThana, 0, 450);

        $insOrder = $pdo->prepare("
            INSERT INTO `orders` 
            (`order_number`, `invoice_number`, `user_id`, `customer_name`, `customer_phone`, `customer_address`, `police_station`, `district`, `order_type`, `total_amount`, `delivery_charge`, `payment_method`, `payment_status`, `transaction_id`, `payment_sender_phone`, `status`, `html_content`, `created_at`) 
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $insOrder->execute([
            $generatedOrderNumber,
            $userId,
            $customerName,
            $customerPhone,
            $customerAddress,
            $destThana,
            $destDistrict,
            $typeStr,
            $deliveryCharge,
            $paymentMethod,
            $paymentStatus,
            $transactionId ?: null,
            $paymentSenderPhone ?: null,
            $htmlContent
        ]);
        $newOrderId = $pdo->lastInsertId();

        // Also record in custom_order_lists for backward compatibility
        $insCol = $pdo->prepare("INSERT INTO `custom_order_lists` (`order_id`, `list_type`, `raw_text`, `image_urls`) VALUES (?, ?, ?, ?)");
        $insCol->execute([
            $newOrderId,
            $typeStr === 'uploaded' ? 'image' : 'text',
            $htmlContent,
            !empty($savedImageUrls) ? json_encode($savedImageUrls) : null
        ]);

        jsonResponse([
            'status' => 'success',
            'type' => 'order',
            'order_number' => $generatedOrderNumber,
            'order_id' => $newOrderId,
            'message' => "আপনার ফর্দটি অর্ডারে জমা হয়েছে (অর্ডার নং: {$generatedOrderNumber})! ডেলিভারিম্যান দরদাম করে শীঘ্রই ইনভয়েস তৈরি করবেন।"
        ]);
        exit;
    }

    // CASE 3: Direct Website Cart Order (Direct Invoice Created: INV-...)
    $generatedInvoiceNumber = generateSequentialCode($pdo, 'INV', $customerPhone, $customerName, $userId);

    // Resolve destination delivery location for location pricing
    $resolvedLoc = resolveLocationInfo($pdo, $customerAddress, $inputData['district'] ?? '', $policeStation ?: ($inputData['thana'] ?? ''));
    $destDistrict = $resolvedLoc['district'];
    $destThana = mb_substr($resolvedLoc['police_station'] ?? '', 0, 450);
    $destZoneId = $resolvedLoc['zone_id'];

    // Verify and calculate genuine total amount from database prices for the destination location
    $serverTotalBasePrice = 0;
    $verifiedItems = [];

    if (!empty($items) && is_array($items)) {
        $prodMapStmt = $pdo->query("SELECT id, title, title_bn, price, in_stock FROM products");
        $allProds = $prodMapStmt->fetchAll(PDO::FETCH_ASSOC);
        $prodByTitle = [];
        $prodById = [];
        foreach ($allProds as $p) {
            $prodById[$p['id']] = $p;
            $prodByTitle[trim(strtolower($p['title']))] = $p;
            if (!empty($p['title_bn'])) {
                $prodByTitle[trim($p['title_bn'])] = $p;
            }
        }

        foreach ($items as $item) {
            $rawName = $item['name'] ?? 'Product';
            $qty = max(1, intval($item['quantity'] ?? 1));
            $isGift = !empty($item['is_free_gift']);

            if ($isGift) {
                $verifiedItems[] = [
                    'name' => $rawName,
                    'quantity' => $qty,
                    'unit_price' => 0.00,
                    'subtotal' => 0.00
                ];
                continue;
            }

            // Find matching product in DB
            $matchedP = null;
            if (!empty($item['id']) && isset($prodById[$item['id']])) {
                $matchedP = $prodById[$item['id']];
            } else {
                $cleanTitle = trim(strtolower($rawName));
                if (isset($prodByTitle[$cleanTitle])) {
                    $matchedP = $prodByTitle[$cleanTitle];
                }
            }

            $actualUnitPrice = floatval($item['price'] ?? 0);
            if ($matchedP) {
                $locPricing = getProductPriceForLocation($pdo, $matchedP['id'], $destDistrict, $destThana, $destZoneId);
                if ($locPricing && isset($locPricing['selling_price'])) {
                    $actualUnitPrice = floatval($locPricing['selling_price']);
                }
            }

            $itemSubtotal = $qty * $actualUnitPrice;
            $serverTotalBasePrice += $itemSubtotal;

            $verifiedItems[] = [
                'name' => $rawName,
                'quantity' => $qty,
                'unit_price' => $actualUnitPrice,
                'subtotal' => $itemSubtotal
            ];
        }
    }

    // Server-side genuine coupon calculation
    $verifiedDiscountAmount = 0.00;
    if (!empty($couponCode)) {
        try {
            $cpStmt = $pdo->prepare("
                SELECT * FROM discount_rules 
                WHERE discount_type = 'coupon' AND coupon_code = ? AND is_active = 1 
                  AND (start_datetime IS NULL OR start_datetime <= NOW()) 
                  AND (end_datetime IS NULL OR end_datetime >= NOW())
                LIMIT 1
            ");
            $cpStmt->execute([$couponCode]);
            $cRule = $cpStmt->fetch(PDO::FETCH_ASSOC);
            if ($cRule) {
                $minCart = floatval($cRule['min_cart_amount'] ?? 0);
                if ($serverTotalBasePrice >= $minCart) {
                    if ($cRule['reward_type'] === 'percentage' || ($cRule['discount_value'] > 0 && strpos((string)($cRule['badge_text'] ?? ''), '%') !== false)) {
                        $verifiedDiscountAmount = round(($serverTotalBasePrice * floatval($cRule['discount_value'])) / 100, 2);
                        if (!empty($cRule['max_reward_limit']) && $verifiedDiscountAmount > floatval($cRule['max_reward_limit'])) {
                            $verifiedDiscountAmount = floatval($cRule['max_reward_limit']);
                        }
                    } else {
                        $verifiedDiscountAmount = floatval($cRule['discount_value']);
                    }
                }
            }
        } catch (Exception $e) {}
    }
    // Never trust client discount_amount over verified calculation
    $discountAmount = min($verifiedDiscountAmount, $serverTotalBasePrice);

    // Calculate delivery charge for website order
    $ruleQuery = $pdo->prepare("SELECT * FROM `delivery_charge_rules` WHERE `target_type` = 'website' AND `is_active` = 1 ORDER BY `priority` ASC");
    $ruleQuery->execute();
    $rules = $ruleQuery->fetchAll();
    $deliveryCharge = 60.00;

    $baseForDelivery = ($serverTotalBasePrice > 0) ? max(0, $serverTotalBasePrice - $discountAmount) : $totalAmount;

    foreach ($rules as $r) {
        if ($r['based_on'] === 'order_amount') {
            $minVal = floatval($r['min_value']);
            $maxVal = floatval($r['max_value']);
            if ($r['operator'] === 'greater_than_or_equal' && $baseForDelivery >= $minVal) {
                $deliveryCharge = ($r['calculation_method'] === 'free') ? 0.00 : floatval($r['fixed_amount'] ?: $r['charge']);
                break;
            }
        }
    }
    if (empty($rules)) {
        $deliveryCharge = ($baseForDelivery >= 1000) ? 0.00 : 60.00;
    }

    if ($serverTotalBasePrice > 0) {
        $totalAmount = max(0, $serverTotalBasePrice - $discountAmount) + $deliveryCharge;
    }

    $htmlContent = str_replace('{{INVOICE_NO}}', $generatedInvoiceNumber, $htmlContent);

    $insOrder = $pdo->prepare("
        INSERT INTO `orders` 
        (`order_number`, `invoice_number`, `user_id`, `customer_name`, `customer_phone`, `customer_address`, `police_station`, `district`, `order_type`, `total_amount`, `delivery_charge`, `coupon_code`, `discount_amount`, `payment_method`, `payment_status`, `transaction_id`, `payment_sender_phone`, `status`, `html_content`, `created_at`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'website', ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $insOrder->execute([
        $generatedInvoiceNumber,
        $generatedInvoiceNumber,
        $userId,
        $customerName,
        $customerPhone,
        $customerAddress,
        $destThana,
        $destDistrict,
        $totalAmount,
        $deliveryCharge,
        $couponCode ?: null,
        $discountAmount,
        $paymentMethod,
        $paymentStatus,
        $transactionId ?: null,
        $paymentSenderPhone ?: null,
        $htmlContent
    ]);
    $newOrderId = $pdo->lastInsertId();

    // Insert order items with location-verified prices
    $itemsToInsert = !empty($verifiedItems) ? $verifiedItems : $items;
    if (!empty($itemsToInsert) && is_array($itemsToInsert)) {
        $itemStmt = $pdo->prepare("
            INSERT INTO `order_items` (`order_id`, `product_name`, `quantity`, `unit_price`, `subtotal`) 
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($itemsToInsert as $item) {
            $name = $item['name'] ?? 'Product';
            $qty = intval($item['quantity'] ?? 1);
            $price = floatval($item['unit_price'] ?? $item['price'] ?? 0);
            $subtotal = floatval($item['subtotal'] ?? ($qty * $price));
            $itemStmt->execute([$newOrderId, $name, $qty, $price, $subtotal]);
        }
    }

    jsonResponse([
        'status' => 'success',
        'type' => 'invoice',
        'invoice_number' => $generatedInvoiceNumber,
        'order_number' => $generatedInvoiceNumber,
        'order_id' => $newOrderId,
        'message' => "আপনার ইনভয়েস সফলভাবে তৈরি হয়েছে (ইনভয়েস নং: {$generatedInvoiceNumber})!"
    ]);

} catch (Exception $e) {
    jsonResponse([
        'status' => 'error',
        'message' => 'Failed to process request: ' . $e->getMessage()
    ], 500);
}
