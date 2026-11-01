<?php
// api/customer-data.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? 'update_profile';

    $currentCust = getLoggedInCustomer($pdo);
    $isPortalStaff = !empty($_SESSION['admin_id']) || !empty($_SESSION['manager_id']) || !empty($_SESSION['staff_id']);

    if (!$currentCust && !$isPortalStaff) {
        jsonResponse(['status' => 'error', 'message' => 'Please log in to perform this action.'], 401);
    }

    $customerPhone = $currentCust['phone'] ?? '';
    $customerUserId = $currentCust['id'] ?? 0;

    if ($action === 'delete_list') {
        $listId = $input['list_id'] ?? $input['id'] ?? null;
        if (!$listId) {
            jsonResponse(['status' => 'error', 'message' => 'List ID is required.'], 400);
        }
        try {
            if ($isPortalStaff) {
                $delStmt = $pdo->prepare("DELETE FROM custom_lists WHERE id = ? OR list_code = ? OR title = ?");
                $delStmt->execute([$listId, $listId, $listId]);
            } else {
                $delStmt = $pdo->prepare("DELETE FROM custom_lists WHERE (id = ? OR list_code = ? OR title = ?) AND (customer_phone = ? OR (user_id = ? AND user_id > 0))");
                $delStmt->execute([$listId, $listId, $listId, $customerPhone, $customerUserId]);
            }
            jsonResponse(['status' => 'success', 'message' => 'Grocery list deleted successfully!']);
        } catch (Exception $e) {
            jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    if ($action === 'delete_order') {
        $orderId = $input['order_id'] ?? $input['id'] ?? null;
        if (!$orderId) {
            jsonResponse(['status' => 'error', 'message' => 'Order ID is required.'], 400);
        }
        try {
            $orderIdNum = is_numeric($orderId) ? intval($orderId) : 0;
            if ($isPortalStaff) {
                $chk = $pdo->prepare("SELECT id, order_number, invoice_number, order_type FROM orders WHERE id = ? OR order_number = ? OR invoice_number = ?");
                $chk->execute([$orderIdNum, $orderId, $orderId]);
            } else {
                $chk = $pdo->prepare("SELECT id, order_number, invoice_number, order_type FROM orders WHERE (id = ? OR order_number = ? OR invoice_number = ?) AND (customer_phone = ? OR (user_id = ? AND user_id > 0))");
                $chk->execute([$orderIdNum, $orderId, $orderId, $customerPhone, $customerUserId]);
            }
            $ordRow = $chk->fetch();

            if (!$ordRow) {
                jsonResponse(['status' => 'error', 'message' => 'অর্ডারটি পাওয়া যায়নি বা মুছে ফেলার অনুমতি নেই।'], 404);
            }

            if ($ordRow['order_type'] === 'website' || !empty($ordRow['invoice_number']) || strpos($ordRow['order_number'], 'INV-') === 0) {
                jsonResponse(['status' => 'error', 'message' => 'ইনভয়েস বা অফিসিয়াল অর্ডার মুছে ফেলা সম্ভব নয়।'], 403);
            }

            $pdo->prepare("DELETE FROM orders WHERE id = ? AND invoice_number IS NULL AND order_type != 'website'")->execute([$ordRow['id']]);
            $pdo->prepare("DELETE FROM custom_order_lists WHERE order_id = ?")->execute([$ordRow['id']]);
            jsonResponse(['status' => 'success', 'message' => 'Order deleted successfully!']);
        } catch (Exception $e) {
            jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    if ($action === 'confirm_invoice') {
        $invId = $input['invoice_number'] ?? $input['order_id'] ?? $input['id'] ?? null;
        if (!$invId) {
            jsonResponse(['status' => 'error', 'message' => 'Invoice identifier is required.'], 400);
        }
        try {
            $invIdNum = is_numeric($invId) ? intval($invId) : 0;
            if ($isPortalStaff) {
                $pdo->prepare("UPDATE orders SET status = 'customer_confirmed' WHERE id = ? OR order_number = ? OR invoice_number = ?")->execute([$invIdNum, $invId, $invId]);
            } else {
                $upd = $pdo->prepare("UPDATE orders SET status = 'customer_confirmed' WHERE (id = ? OR order_number = ? OR invoice_number = ?) AND (customer_phone = ? OR (user_id = ? AND user_id > 0))");
                $upd->execute([$invIdNum, $invId, $invId, $customerPhone, $customerUserId]);
                if ($upd->rowCount() === 0) {
                    jsonResponse(['status' => 'error', 'message' => 'ইনভয়েসটি পাওয়া যায়নি বা কনফার্মের অনুমতি নেই।'], 404);
                }
            }
            jsonResponse(['status' => 'success', 'message' => 'ইনভয়েস কনফার্ম করা হয়েছে! রাইডার ডেলিভারির কাজ শুরু করবেন।']);
        } catch (Exception $e) {
            jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    if ($action === 'cancel_order' || $action === 'cancel_invoice') {
        $invId = $input['invoice_number'] ?? $input['order_id'] ?? $input['id'] ?? null;
        $cancelReason = trim($input['reason'] ?? $input['cancel_reason'] ?? 'Customer cancelled the order');

        if (!$invId) {
            jsonResponse(['status' => 'error', 'message' => 'Invoice/Order identifier is required.'], 400);
        }
        try {
            $invIdNum = is_numeric($invId) ? intval($invId) : 0;
            if ($isPortalStaff) {
                $chkStmt = $pdo->prepare("SELECT id, order_number, invoice_number, status, customer_phone FROM orders WHERE id = ? OR order_number = ? OR invoice_number = ?");
                $chkStmt->execute([$invIdNum, $invId, $invId]);
            } else {
                $chkStmt = $pdo->prepare("SELECT id, order_number, invoice_number, status, customer_phone FROM orders WHERE (id = ? OR order_number = ? OR invoice_number = ?) AND (customer_phone = ? OR (user_id = ? AND user_id > 0))");
                $chkStmt->execute([$invIdNum, $invId, $invId, $customerPhone, $customerUserId]);
            }
            $order = $chkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                jsonResponse(['status' => 'error', 'message' => 'অর্ডারটি খুঁজে পাওয়া যায়নি বা বাতিলের অনুমতি নেই।'], 404);
            }

            $allowedCancelStatuses = ['pending', 'invoice_created', 'draft', 'accepted'];
            if (!in_array($order['status'], $allowedCancelStatuses)) {
                jsonResponse([
                    'status' => 'error',
                    'message' => 'ডেলিভারিম্যান কেনাকাটা বা ডেলিভারি শুরু করেছেন বিধায় সরাসরি বাতিল করা সম্ভব নয়। অনুগ্রহ করে হেল্পলাইনে (01919-668960) যোগাযোগ করুন।'
                ], 403);
            }

            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order['id']]);
            logAuditActivity($pdo, 'Customer Cancelled Order', "Customer cancelled order #{$invId}. Reason: {$cancelReason}", $customerPhone, 'customer');
            jsonResponse(['status' => 'success', 'message' => 'অর্ডারটি সফলভাবে বাতিল করা হয়েছে।']);
        } catch (Exception $e) {
            jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    if ($action === 'sync_user_state') {
        $syncPhone = $isPortalStaff ? normalizeBdPhone(trim($input['phone'] ?? '')) : $customerPhone;
        if (empty($syncPhone)) {
            jsonResponse(['status' => 'error', 'message' => 'Phone is required for syncing state.'], 400);
        }

        try {
            $currStmt = $pdo->prepare("SELECT saved_addresses, cart_data, wishlist_data, default_location FROM users WHERE phone = ?");
            $currStmt->execute([$syncPhone]);
            $currUser = $currStmt->fetch(PDO::FETCH_ASSOC);

            $fields = [];
            $params = [];

            if (isset($input['saved_addresses'])) {
                $rawAddresses = is_string($input['saved_addresses']) ? json_decode($input['saved_addresses'], true) : $input['saved_addresses'];
                $cleanAddresses = [];
                if (is_array($rawAddresses)) {
                    foreach ($rawAddresses as $addr) {
                        $t = $addr['title'] ?? '';
                        $f = $addr['fullAddress'] ?? '';
                        if (isValidLocationAddress($t) || isValidLocationAddress($f)) {
                            $cleanAddresses[] = $addr;
                        }
                    }
                }
                $hasDbAddrs = !empty($currUser['saved_addresses']) && $currUser['saved_addresses'] !== '[]';
                if (!empty($cleanAddresses) || !$hasDbAddrs || !empty($input['force_empty_addresses'])) {
                    $fields[] = "`saved_addresses` = ?";
                    $params[] = json_encode($cleanAddresses, JSON_UNESCAPED_UNICODE);
                }
            }
            if (isset($input['active_address_id'])) {
                if (!empty($input['active_address_id']) || empty($currUser['active_address_id'])) {
                    $fields[] = "`active_address_id` = ?";
                    $params[] = $input['active_address_id'];
                }
            }
            if (isset($input['cart_data'])) {
                $rawCart = is_string($input['cart_data']) ? json_decode($input['cart_data'], true) : $input['cart_data'];
                $hasDbCart = !empty($currUser['cart_data']) && $currUser['cart_data'] !== '[]';
                if (!empty($rawCart) || !$hasDbCart || !empty($input['force_empty_cart'])) {
                    $fields[] = "`cart_data` = ?";
                    $params[] = is_string($input['cart_data']) ? $input['cart_data'] : json_encode($input['cart_data'], JSON_UNESCAPED_UNICODE);
                }
            }
            if (isset($input['wishlist_data'])) {
                $rawWishlist = is_string($input['wishlist_data']) ? json_decode($input['wishlist_data'], true) : $input['wishlist_data'];
                $hasDbWishlist = !empty($currUser['wishlist_data']) && $currUser['wishlist_data'] !== '[]';
                if (!empty($rawWishlist) || !$hasDbWishlist || !empty($input['force_empty_wishlist'])) {
                    $fields[] = "`wishlist_data` = ?";
                    $params[] = is_string($input['wishlist_data']) ? $input['wishlist_data'] : json_encode($input['wishlist_data'], JSON_UNESCAPED_UNICODE);
                }
            }
            if (isset($input['location']) && !empty($input['location']) && isValidLocationAddress($input['location'])) {
                $fields[] = "`default_location` = ?";
                $params[] = $input['location'];
            }

            if (!empty($fields)) {
                $params[] = $syncPhone;
                $sql = "UPDATE `users` SET " . implode(", ", $fields) . " WHERE `phone` = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            jsonResponse(['status' => 'success', 'message' => 'User state synced successfully!']);
        } catch (Exception $e) {
            jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // If not portal staff, enforce logged-in customer's phone
    if (!$isPortalStaff) {
        $phone = $customerPhone;
        $cleanPhone = preg_replace('/\D/', '', $phone);
    } else {
        $rawPhone = trim($input['phone'] ?? ($input['phone_number'] ?? ''));
        $phone = normalizeBdPhone($rawPhone);
        $cleanPhone = preg_replace('/\D/', '', $phone);
    }

    $name = trim($input['name'] ?? ($input['full_name'] ?? ''));
    $email = trim($input['email'] ?? '');
    $address = trim($input['address'] ?? '');
    $photo = $input['profile_photo'] ?? null;

    if (empty($phone) || empty($name)) {
        jsonResponse(['status' => 'error', 'message' => 'Name and phone are required.']);
    }

    try {
        $sql = "UPDATE users SET name = ?, email = ?, default_location = ?";
        $params = [$name, $email, $address];

        $savedPhotoUrl = $photo;
        if ($photo !== null && !empty($photo)) {
            if (is_string($photo) && preg_match('/^data:image\/(\w+);base64,/', $photo, $m)) {
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                $allowedAvatarExts = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowedAvatarExts)) {
                    $raw = base64_decode(substr($photo, strpos($photo, ',') + 1));
                    if ($raw !== false) {
                        $avatarsDir = __DIR__ . '/../uploads/avatars/';
                        if (!is_dir($avatarsDir)) {
                            mkdir($avatarsDir, 0777, true);
                        }
                        $cleanPhone = preg_replace('/\D/', '', $phone);
                        $filename = 'avatar_' . $cleanPhone . '_' . date('Ymd_His') . '_' . substr(uniqid(), -4) . '.' . $ext;
                        if (file_put_contents($avatarsDir . $filename, $raw)) {
                            $savedPhotoUrl = 'uploads/avatars/' . $filename;
                        }
                    }
                }
            }
            $sql .= ", profile_photo = ?";
            $params[] = $savedPhotoUrl;

            $_SESSION['customer_photo'] = $savedPhotoUrl;
            $_SESSION['user_photo'] = $savedPhotoUrl;
        }

        $sql .= " WHERE phone = ?";
        $params[] = $phone;

        $_SESSION['customer_name'] = $name;
        $_SESSION['user_name'] = $name;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        jsonResponse([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'user' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'profile_photo' => $savedPhotoUrl
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// Check for single order / track code search (Accessible by guests with order/inv number & logged in customers with phone)
if (!empty($_GET['track_code']) || !empty($_GET['track_search'])) {
    $searchKey = trim($_GET['track_code'] ?? $_GET['track_search']);
    $cleanKey = normalizeBdPhone($searchKey);
    $sessionPhone = normalizeBdPhone($_SESSION['customer_phone'] ?? '');
    $isStaffOrAdmin = !empty($_SESSION['admin_id']) || !empty($_SESSION['manager_id']) || !empty($_SESSION['staff_id']);

    // If searching by customer phone number, customer must be logged in with that phone number (or be staff/admin)
    if (isValidBdPhone($cleanKey) && !$isStaffOrAdmin && $sessionPhone !== $cleanKey) {
        jsonResponse(['status' => 'error', 'message' => 'ফোন নম্বর দিয়ে সকল অর্ডার দেখতে অনুগ্রহ করে প্রথমে লগইন করুন, অথবা নির্দিষ্ট অর্ডার নম্বর (যেমন: ORD-...) দিয়ে সার্চ করুন।'], 403);
    }

    try {
        $st = $pdo->prepare("
            SELECT 
                o.id as db_id,
                COALESCE(o.invoice_number, o.order_number) as id,
                o.order_number,
                o.invoice_number,
                o.customer_name,
                o.customer_phone,
                o.customer_address,
                o.police_station,
                o.order_type,
                CAST(o.total_amount AS FLOAT) as total_amount,
                CAST(o.delivery_charge AS FLOAT) as delivery_charge,
                o.rider_id,
                o.rider_name,
                o.rider_phone,
                o.assigned_at,
                o.status,
                o.html_content as items_summary,
                col.image_urls,
                DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as date
            FROM orders o
            LEFT JOIN custom_order_lists col ON o.id = col.order_id
            WHERE o.order_number = ? 
               OR o.invoice_number = ? 
               OR o.id = ? 
               OR o.customer_phone = ?
               OR (o.customer_phone = ? AND ? != '')
            ORDER BY o.id DESC
            LIMIT 10
        ");
        $st->execute([$searchKey, $searchKey, $searchKey, $searchKey, $cleanKey, $cleanKey]);
        $foundOrders = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($foundOrders)) {
            $foundDbIds = array_column($foundOrders, 'db_id');
            $inClause = implode(',', array_map('intval', $foundDbIds));
            $itemRows = $pdo->query("SELECT order_id, product_name, product_poriman, quantity, unit_price, subtotal FROM order_items WHERE order_id IN ($inClause)")->fetchAll(PDO::FETCH_ASSOC);
            $itemsByOrderId = [];
            foreach ($itemRows as $ir) {
                $itemsByOrderId[$ir['order_id']][] = $ir;
            }
            foreach ($foundOrders as &$fo) {
                $fo['items'] = $itemsByOrderId[$fo['db_id']] ?? [];
                $fo['image_urls'] = $fo['image_urls'] ? (is_string($fo['image_urls']) ? json_decode($fo['image_urls'], true) : $fo['image_urls']) : null;
            }
            jsonResponse(['status' => 'success', 'orders' => $foundOrders]);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'অর্ডার পাওয়া যায়নি। অনুগ্রহ করে সঠিক অর্ডার বা ইনভয়েস নম্বর দিন।'], 404);
        }
    } catch (Exception $e) {
        jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

// GET Request (Customer Profile View)
$isStaffOrAdmin = !empty($_SESSION['admin_id']) || !empty($_SESSION['manager_id']) || !empty($_SESSION['staff_id']);
$sessionCustPhone = normalizeBdPhone($_SESSION['customer_phone'] ?? '');

if (!$isStaffOrAdmin) {
    if (empty($sessionCustPhone)) {
        jsonResponse(['status' => 'error', 'message' => 'Please log in to view customer profile.'], 401);
    }
    // Strictly restrict customers to viewing only their own account
    $phone = $sessionCustPhone;
    $rawPhone = $sessionCustPhone;
    $cleanPhone = preg_replace('/\D/', '', $phone);
} else {
    // Staff/Admin can view any customer profile
    $rawPhone = trim($_GET['phone'] ?? '');
    $phone = normalizeBdPhone($rawPhone);
    $cleanPhone = preg_replace('/\D/', '', $phone ?: $rawPhone);
    if (empty($phone)) {
        jsonResponse(['status' => 'error', 'message' => 'Customer phone is required.'], 400);
    }
}

try {
    // 1. Fetch User
    $userStmt = $pdo->prepare("SELECT id, name, phone, email, default_location, profile_photo, district, police_station, address, saved_addresses, active_address_id, cart_data, wishlist_data FROM users WHERE phone = ? OR phone = ? LIMIT 1");
    $userStmt->execute([$phone, $rawPhone]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $ins = $pdo->prepare("INSERT INTO users (name, phone, role, status, created_at) VALUES (?, ?, 'customer', 'active', NOW())");
        $ins->execute(['Customer', $phone]);
        $user = [
            'id' => $pdo->lastInsertId(),
            'name' => 'Customer',
            'phone' => $cleanPhone ?: $phone,
            'email' => '',
            'default_location' => '',
            'profile_photo' => null,
            'district' => '',
            'police_station' => '',
            'address' => '',
            'saved_addresses' => '[]',
            'active_address_id' => null,
            'cart_data' => '[]',
            'wishlist_data' => '[]'
        ];
    }

    // 2. Fetch Custom Draft Lists (LST-...)
    $userLocArr = array_filter([$user['address'] ?? '', $user['police_station'] ?? '', $user['district'] ?? '']);
    $defaultUserLoc = !empty($user['default_location']) ? $user['default_location'] : implode(', ', $userLocArr);

    $cListStmt = $pdo->prepare("
        SELECT 
            id,
            list_code,
            customer_address,
            list_type,
            title,
            content_text as raw_text,
            images as image_urls,
            status,
            DATE_FORMAT(created_at, '%b %d, %Y %h:%i %p') as date,
            created_at
        FROM custom_lists 
        WHERE user_id = ? OR customer_phone = ? OR customer_phone = ?
        ORDER BY id DESC
    ");
    $cListStmt->execute([$user['id'], $phone, $cleanPhone]);
    $rawLists = $cListStmt->fetchAll(PDO::FETCH_ASSOC);

    $customLists = [];
    foreach ($rawLists as $cl) {
        $customLists[] = [
            'id' => $cl['id'],
            'list_code' => $cl['list_code'] ?: ($cl['title'] ?: "LST-{$cl['id']}"),
            'list_type' => $cl['list_type'] === 'uploaded' ? 'uploaded' : 'written',
            'raw_text' => $cl['raw_text'],
            'image_urls' => $cl['image_urls'] ? (is_string($cl['image_urls']) ? json_decode($cl['image_urls'], true) : $cl['image_urls']) : null,
            'status' => $cl['status'] ?: 'draft',
            'customer_address' => !empty($cl['customer_address']) ? $cl['customer_address'] : $defaultUserLoc,
            'date' => $cl['date']
        ];
    }

    // 3. Fetch Active Grocery List Orders (ORD-... awaiting rider pricing / before invoice)
    $orderStmt = $pdo->prepare("
        SELECT 
            o.id as db_id,
            o.order_number as id,
            o.invoice_number,
            o.customer_name,
            o.customer_phone,
            o.customer_address,
            o.police_station,
            o.order_type,
            CAST(o.total_amount AS FLOAT) as total_amount,
            CAST(o.delivery_charge AS FLOAT) as delivery_charge,
            o.rider_id,
            o.rider_name,
            o.rider_phone,
            o.assigned_at,
            o.status,
            o.payment_method,
            o.payment_status,
            o.transaction_id,
            o.payment_sender_phone,
            o.html_content as items_summary,
            col.image_urls,
            DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as date
        FROM orders o
        LEFT JOIN custom_order_lists col ON o.id = col.order_id
        WHERE (o.user_id = ? OR o.customer_phone = ? OR o.customer_phone = ?)
          AND o.order_number NOT LIKE 'INV-%'
          AND (o.invoice_number IS NULL OR o.invoice_number = '')
          AND (o.order_type != 'website' OR o.order_type IS NULL)
        ORDER BY o.id DESC
    ");
    $orderStmt->execute([$user['id'], $phone, $cleanPhone]);
    $rawOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($rawOrders as $ro) {
        $orders[] = [
            'db_id' => $ro['db_id'],
            'id' => $ro['id'],
            'order_number' => $ro['id'],
            'invoice_number' => $ro['invoice_number'],
            'customer_name' => $ro['customer_name'],
            'customer_phone' => $ro['customer_phone'],
            'customer_address' => $ro['customer_address'],
            'police_station' => $ro['police_station'],
            'order_type' => $ro['order_type'] ?: 'custom_list',
            'total_amount' => $ro['total_amount'],
            'delivery_charge' => $ro['delivery_charge'],
            'payment_method' => $ro['payment_method'] ?: 'cod',
            'payment_status' => $ro['payment_status'] ?: 'pending',
            'transaction_id' => $ro['transaction_id'] ?? null,
            'payment_sender_phone' => $ro['payment_sender_phone'] ?? null,
            'rider_name' => $ro['rider_name'] ?? null,
            'rider_phone' => $ro['rider_phone'] ?? null,
            'assigned_at' => $ro['assigned_at'] ?? null,
            'status' => $ro['status'] ?: 'pending',
            'items_summary' => $ro['items_summary'],
            'image_urls' => $ro['image_urls'] ? (is_string($ro['image_urls']) ? json_decode($ro['image_urls'], true) : $ro['image_urls']) : null,
            'date' => $ro['date']
        ];
    }

    // 4. Fetch Invoices (INV-... and all orders where invoice is generated or cart checkout)
    $invoiceStmt = $pdo->prepare("
        SELECT 
            o.id as db_id,
            COALESCE(o.invoice_number, o.order_number) as id,
            o.order_number,
            o.invoice_number,
            o.customer_name,
            o.customer_phone,
            o.customer_address,
            o.police_station,
            o.order_type,
            CAST(o.total_amount AS FLOAT) as total_amount,
            CAST(o.delivery_charge AS FLOAT) as delivery_charge,
            o.payment_method,
            o.payment_status,
            o.transaction_id,
            o.payment_sender_phone,
            o.rider_id,
            o.rider_name,
            o.rider_phone,
            o.assigned_at,
            o.status,
            o.html_content as items_summary,
            col.image_urls,
            DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as date
        FROM orders o
        LEFT JOIN custom_order_lists col ON o.id = col.order_id
        WHERE (o.user_id = ? OR o.customer_phone = ? OR o.customer_phone = ?) 
          AND (o.order_number LIKE 'INV-%' OR (o.invoice_number IS NOT NULL AND o.invoice_number != '') OR o.order_type = 'website')
        ORDER BY o.id DESC
    ");
    $invoiceStmt->execute([$user['id'], $phone, $cleanPhone]);
    $rawInvoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

    $invoices = [];
    foreach ($rawInvoices as $ri) {
        $invoices[] = [
            'db_id' => $ri['db_id'],
            'id' => $ri['id'],
            'order_number' => $ri['order_number'],
            'invoice_number' => $ri['invoice_number'],
            'customer_name' => $ri['customer_name'],
            'customer_phone' => $ri['customer_phone'],
            'customer_address' => $ri['customer_address'],
            'police_station' => $ri['police_station'],
            'order_type' => $ri['order_type'] ?: 'website',
            'total_amount' => $ri['total_amount'],
            'delivery_charge' => $ri['delivery_charge'],
            'payment_method' => $ri['payment_method'] ?: 'cod',
            'payment_status' => $ri['payment_status'] ?: 'pending',
            'transaction_id' => $ri['transaction_id'] ?? null,
            'payment_sender_phone' => $ri['payment_sender_phone'] ?? null,
            'rider_name' => $ri['rider_name'] ?? null,
            'rider_phone' => $ri['rider_phone'] ?? null,
            'assigned_at' => $ri['assigned_at'] ?? null,
            'status' => $ri['status'] ?: 'pending',
            'items_summary' => $ri['items_summary'],
            'image_urls' => $ri['image_urls'] ? (is_string($ri['image_urls']) ? json_decode($ri['image_urls'], true) : $ri['image_urls']) : null,
            'date' => $ri['date']
        ];
    }

    $allOrderDbIds = array_unique(array_filter(array_merge(
        array_column($orders, 'db_id'),
        array_column($invoices, 'db_id')
    )));
    $itemsByOrderId = [];
    if (!empty($allOrderDbIds)) {
        $inClause = implode(',', array_map('intval', $allOrderDbIds));
        $itemRows = $pdo->query("SELECT order_id, product_name, product_poriman, quantity, unit_price, subtotal FROM order_items WHERE order_id IN ($inClause)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemRows as $ir) {
            $itemsByOrderId[$ir['order_id']][] = $ir;
        }
    }

    foreach ($orders as &$ro) {
        $ro['items'] = $itemsByOrderId[$ro['db_id']] ?? [];
    }
    unset($ro);

    foreach ($invoices as &$ri) {
        $ri['items'] = $itemsByOrderId[$ri['db_id']] ?? [];
    }
    unset($ri);

    $savedAddresses = !empty($user['saved_addresses']) ? json_decode($user['saved_addresses'], true) : [];
    $activeAddressId = $user['active_address_id'] ?? null;
    $cartData = !empty($user['cart_data']) ? json_decode($user['cart_data'], true) : [];
    $wishlistData = !empty($user['wishlist_data']) ? json_decode($user['wishlist_data'], true) : [];

    $customerPayload = [
        'id' => $user['id'],
        'name' => $user['name'] ?: 'Customer',
        'phone' => $user['phone'],
        'email' => $user['email'] ?? '',
        'location' => $user['default_location'] ?? $user['police_station'] ?? '',
        'address' => $user['address'] ?? $user['default_location'] ?? '',
        'district' => $user['district'] ?? '',
        'police_station' => $user['police_station'] ?? '',
        'profile_photo' => $user['profile_photo'] ?? '',
        'saved_addresses' => is_array($savedAddresses) ? $savedAddresses : [],
        'active_address_id' => $activeAddressId,
        'cart_data' => is_array($cartData) ? $cartData : [],
        'wishlist_data' => is_array($wishlistData) ? $wishlistData : []
    ];

    jsonResponse([
        'status' => 'success',
        'customer' => $customerPayload,
        'user' => $customerPayload,
        'custom_lists' => $customLists,
        'orders' => $orders,
        'invoices' => $invoices
    ]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
