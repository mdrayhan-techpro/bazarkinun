<?php
// mng_customer_detail.php
// Super Admin & Management Portal - Customer Details View
require_once __DIR__ . '/config/db.php';

// Enforce Portal user authorization
$isAuthorizedPortalUser = !empty($_SESSION['admin_id']) || !empty($_SESSION['manager_id']) || (!empty($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'super_admin', 'author', 'manager', 'management', 'operation']));
if (!$isAuthorizedPortalUser) {
    header('Location: portal_login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php?tab=customer'));
    exit;
}

// Enforce customer directory access privilege
requirePermission(['customers.view', 'view_customers']);

// Check customer ID
$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    // Default to first customer if no id specified
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'customer' ORDER BY id ASC LIMIT 1");
    $customerId = (int)$stmt->fetchColumn();
    if (!$customerId) {
        $customerId = 1;
    }
}

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("<div style='font-family:sans-serif; padding:40px; text-align:center;'><h2>Customer not found</h2><a href='dashboard.php'>Return to Dashboard</a></div>");
}

// Check Portal Context (Management or Admin)
$portal = $_GET['portal'] ?? '';
$isAdminPortal = ($portal === 'admin') || (!empty($_SESSION['admin_id'])) || (!empty($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'super_admin', 'author']));
$isAuthorPortal = $isAdminPortal;
$portalName = $isAdminPortal ? 'Admin Panel' : 'Management Portal';
$backUrl = 'dashboard.php?tab=customer' . ($portal ? "&portal=" . urlencode($portal) : '');

// Helper JSON response
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Handle AJAX actions (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_GET['action'] ?? '';
    $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if ($action === 'edit_profile') {
        $name = trim($inputData['full_name'] ?? '');
        $phone = trim($inputData['phone_number'] ?? '');
        $email = trim($inputData['email'] ?? '');
        $district = trim($inputData['district'] ?? '');
        $ps = trim($inputData['police_station'] ?? '');
        $address = trim($inputData['address'] ?? '');
        $photo = $inputData['profile_photo'] ?? null;

        if (!$name || !$phone) {
            jsonResponse(['status' => 'error', 'message' => 'Name and phone are required.']);
        }

        $sql = "UPDATE users SET name = ?, phone = ?, email = ?, district = ?, police_station = ?, address = ?, default_location = ?";
        $params = [$name, $phone, $email, $district, $ps, $address, ($address ?: ($ps ? "$ps, $district" : ''))];

        if ($photo !== null && !empty($photo)) {
            $savedPhotoUrl = $photo;
            if (is_string($photo) && preg_match('/^data:image\/(\w+);base64,/', $photo, $m)) {
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                $raw = base64_decode(substr($photo, strpos($photo, ',') + 1));
                if ($raw !== false) {
                    $avatarsDir = __DIR__ . '/uploads/avatars/';
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
            $sql .= ", profile_photo = ?";
            $params[] = $savedPhotoUrl;
        }
        $sql .= " WHERE id = ?";
        $params[] = $customerId;

        $up = $pdo->prepare($sql);
        $up->execute($params);

        jsonResponse(['status' => 'success', 'message' => 'Customer profile updated successfully!']);
    }

    if ($action === 'add_address') {
        $type = strtolower($inputData['address_type'] ?? 'home');
        $keyword = trim($inputData['keyword'] ?? 'Home');
        $district = trim($inputData['district'] ?? '');
        $ps = trim($inputData['police_station'] ?? '');
        $street = trim($inputData['street'] ?? '');
        $lat = trim($inputData['latitude'] ?? '');
        $lng = trim($inputData['longitude'] ?? '');

        // 1. Insert into customer_addresses table
        $ins = $pdo->prepare("INSERT INTO customer_addresses (user_id, address_type, keyword, district, police_station, street, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$customerId, $type, $keyword, $district, $ps, $street, $lat, $lng]);
        $newDbId = $pdo->lastInsertId();

        // 2. Also sync to users.saved_addresses JSON
        $stmtU = $pdo->prepare("SELECT saved_addresses, default_location, district, police_station, address FROM users WHERE id = ?");
        $stmtU->execute([$customerId]);
        $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);

        $savedList = json_decode($uRow['saved_addresses'] ?? '[]', true) ?: [];
        $savedList[] = [
            'id' => 'loc_' . time() . '_' . $newDbId,
            'db_id' => (int)$newDbId,
            'kw' => $keyword,
            'title' => $ps ?: $district ?: $keyword,
            'fullAddress' => $street ?: ($ps ? "$ps, $district" : $district),
            'district' => $district,
            'police_station' => $ps,
            'lat' => $lat ? (float)$lat : null,
            'lng' => $lng ? (float)$lng : null,
        ];

        $upU = $pdo->prepare("UPDATE users SET saved_addresses = ?, default_location = COALESCE(NULLIF(default_location, ''), ?), district = COALESCE(NULLIF(district, ''), ?), police_station = COALESCE(NULLIF(police_station, ''), ?), address = COALESCE(NULLIF(address, ''), ?) WHERE id = ?");
        $upU->execute([json_encode($savedList, JSON_UNESCAPED_UNICODE), ($ps ?: $street), $district, $ps, $street, $customerId]);

        jsonResponse(['status' => 'success', 'message' => 'New address added successfully!']);
    }

    if ($action === 'edit_address') {
        $addrId = $inputData['addr_id'] ?? $_GET['addr_id'] ?? '';
        $type = strtolower($inputData['address_type'] ?? 'home');
        $keyword = trim($inputData['keyword'] ?? 'Home');
        $district = trim($inputData['district'] ?? '');
        $ps = trim($inputData['police_station'] ?? '');
        $street = trim($inputData['street'] ?? '');
        $lat = trim($inputData['latitude'] ?? '');
        $lng = trim($inputData['longitude'] ?? '');

        // Update customer_addresses table if numeric ID
        if (is_numeric($addrId) && (int)$addrId > 0) {
            $up = $pdo->prepare("UPDATE customer_addresses SET address_type = ?, keyword = ?, district = ?, police_station = ?, street = ?, latitude = ?, longitude = ? WHERE id = ? AND user_id = ?");
            $up->execute([$type, $keyword, $district, $ps, $street, $lat, $lng, (int)$addrId, $customerId]);
        }

        // Also update in users.saved_addresses JSON
        $stmtU = $pdo->prepare("SELECT saved_addresses FROM users WHERE id = ?");
        $stmtU->execute([$customerId]);
        $savedList = json_decode($stmtU->fetchColumn() ?? '[]', true) ?: [];

        $found = false;
        foreach ($savedList as &$sa) {
            if (($sa['id'] ?? '') == $addrId || ($sa['db_id'] ?? '') == $addrId) {
                $sa['kw'] = $keyword;
                $sa['title'] = $ps ?: $district ?: $keyword;
                $sa['fullAddress'] = $street;
                $sa['district'] = $district;
                $sa['police_station'] = $ps;
                $sa['lat'] = $lat ? (float)$lat : null;
                $sa['lng'] = $lng ? (float)$lng : null;
                $found = true;
                break;
            }
        }
        unset($sa);

        if (!$found && is_numeric($addrId)) {
            $savedList[] = [
                'id' => 'loc_' . time() . '_' . $addrId,
                'db_id' => (int)$addrId,
                'kw' => $keyword,
                'title' => $ps ?: $district ?: $keyword,
                'fullAddress' => $street,
                'district' => $district,
                'police_station' => $ps,
                'lat' => $lat ? (float)$lat : null,
                'lng' => $lng ? (float)$lng : null,
            ];
        }

        $upU = $pdo->prepare("UPDATE users SET saved_addresses = ?, district = ?, police_station = ?, address = ? WHERE id = ?");
        $upU->execute([json_encode($savedList, JSON_UNESCAPED_UNICODE), $district, $ps, $street, $customerId]);

        jsonResponse(['status' => 'success', 'message' => 'Address updated successfully!']);
    }

    if ($action === 'delete_address') {
        $addrId = $inputData['addr_id'] ?? $_GET['addr_id'] ?? '';

        if (is_numeric($addrId) && (int)$addrId > 0) {
            $del = $pdo->prepare("DELETE FROM customer_addresses WHERE id = ? AND user_id = ?");
            $del->execute([(int)$addrId, $customerId]);
        }

        // Also remove from users.saved_addresses JSON
        $stmtU = $pdo->prepare("SELECT saved_addresses FROM users WHERE id = ?");
        $stmtU->execute([$customerId]);
        $savedList = json_decode($stmtU->fetchColumn() ?? '[]', true) ?: [];

        $newList = [];
        foreach ($savedList as $sa) {
            if (($sa['id'] ?? '') != $addrId && ($sa['db_id'] ?? '') != $addrId) {
                $newList[] = $sa;
            }
        }

        $upU = $pdo->prepare("UPDATE users SET saved_addresses = ? WHERE id = ?");
        $upU->execute([json_encode(array_values($newList), JSON_UNESCAPED_UNICODE), $customerId]);

        jsonResponse(['status' => 'success', 'message' => 'Address deleted successfully!']);
    }

    if ($action === 'delete_order') {
        $orderId = (int)($inputData['order_id'] ?? $_GET['order_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM orders WHERE id = ? AND (user_id = ? OR customer_phone = ?)");
        $del->execute([$orderId, $customerId, $customer['phone']]);

        jsonResponse(['status' => 'success', 'message' => 'Order deleted successfully!']);
    }

    if ($action === 'delete_list') {
        $listId = (int)($inputData['list_id'] ?? $_GET['list_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM custom_lists WHERE id = ? AND (user_id = ? OR customer_phone = ?)");
        $del->execute([$listId, $customerId, $customer['phone']]);

        // Also delete from orders if it was stored as an order
        $delOrder = $pdo->prepare("DELETE FROM orders WHERE id = ? AND (user_id = ? OR customer_phone = ?) AND order_type IN ('text_list', 'uploaded')");
        $delOrder->execute([$listId, $customerId, $customer['phone']]);

        jsonResponse(['status' => 'success', 'message' => 'List deleted successfully!']);
    }

    exit;
}

// Current Active Tab
$tab = $_GET['tab'] ?? 'profile';

// -------------------------------------------------------------
// 1. Fetch Unified Customer Addresses (Database + JSON Sync)
// -------------------------------------------------------------
$addrStmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY id DESC");
$addrStmt->execute([$customerId]);
$dbAddresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);

$rawSavedAddr = json_decode($customer['saved_addresses'] ?? '[]', true) ?: [];
$addresses = [];
$seenAddressKeys = [];

// Populate from DB table
foreach ($dbAddresses as $da) {
    $street = trim($da['street'] ?? '');
    $ps = trim($da['police_station'] ?? '');
    $dist = trim($da['district'] ?? '');
    $key = strtolower($street . '|' . $ps . '|' . $dist);

    $addresses[] = [
        'id' => (int)$da['id'],
        'db_id' => (int)$da['id'],
        'is_json' => false,
        'address_type' => strtolower($da['address_type'] ?: 'home'),
        'keyword' => $da['keyword'] ?: 'Home',
        'district' => $dist ?: 'Dhaka',
        'police_station' => $ps ?: 'Uttara',
        'street' => $street ?: ($da['keyword'] ?: 'Home'),
        'latitude' => (string)($da['latitude'] ?? ''),
        'longitude' => (string)($da['longitude'] ?? ''),
    ];
    if ($key) $seenAddressKeys[$key] = true;
}

// Populate from users.saved_addresses JSON
foreach ($rawSavedAddr as $idx => $sa) {
    $street = trim($sa['fullAddress'] ?? $sa['address'] ?? $sa['street'] ?? $sa['title'] ?? '');
    $ps = trim($sa['police_station'] ?? $sa['thana'] ?? '');
    $dist = trim($sa['district'] ?? '');

    if (!$ps && strpos($street, 'Uttara') !== false) {
        $ps = 'Uttara';
    }
    if (!$dist && strpos($street, 'Dhaka') !== false) {
        $dist = 'Dhaka';
    }

    $key = strtolower($street . '|' . $ps . '|' . $dist);
    if ($key && isset($seenAddressKeys[$key])) {
        continue;
    }

    $id = $sa['id'] ?? ('loc_' . ($idx + 1));
    $addresses[] = [
        'id' => $id,
        'db_id' => (int)($sa['db_id'] ?? 0),
        'is_json' => true,
        'address_type' => strtolower($sa['kw'] ?? $sa['type'] ?? 'home'),
        'keyword' => $sa['kw'] ?? $sa['title'] ?? 'Home',
        'district' => $dist ?: ($customer['district'] ?: 'Dhaka'),
        'police_station' => $ps ?: ($sa['title'] ?? ($customer['police_station'] ?: 'Uttara')),
        'street' => $street ?: ($customer['default_location'] ?: 'Uttara'),
        'latitude' => (string)($sa['lat'] ?? $customer['latitude'] ?? '23.870455'),
        'longitude' => (string)($sa['lng'] ?? $customer['longitude'] ?? '90.398812'),
    ];
    if ($key) $seenAddressKeys[$key] = true;
}

// Fallback if empty but user has default_location or address
if (empty($addresses) && (!empty($customer['address']) || !empty($customer['default_location']))) {
    $street = $customer['address'] ?: $customer['default_location'];
    $ps = $customer['police_station'] ?: ($customer['default_location'] ?: 'Uttara');
    $dist = $customer['district'] ?: 'Dhaka';
    $addresses[] = [
        'id' => 'loc_default_1',
        'db_id' => 0,
        'is_json' => true,
        'address_type' => 'home',
        'keyword' => 'Home',
        'district' => $dist,
        'police_station' => $ps,
        'street' => $street,
        'latitude' => (string)($customer['latitude'] ?? '23.870455'),
        'longitude' => (string)($customer['longitude'] ?? '90.398812'),
    ];
}

// Preferred customer display address for profile
$customerAddressSummary = !empty($addresses) ? $addresses[0]['street'] : ($customer['address'] ?: $customer['default_location']);
$customerDistrictDefault = !empty($addresses) ? $addresses[0]['district'] : ($customer['district'] ?: 'Dhaka');
$customerThanaDefault = !empty($addresses) ? $addresses[0]['police_station'] : ($customer['police_station'] ?: ($customer['default_location'] ?: 'Uttara'));

// -------------------------------------------------------------
// 2. Fetch Customer Orders, Invoices & Lists
// -------------------------------------------------------------
$ordStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? OR customer_phone = ? ORDER BY id DESC");
$ordStmt->execute([$customerId, $customer['phone']]);
$customerOrders = $ordStmt->fetchAll(PDO::FETCH_ASSOC);

$ordersFormatted = [];
$invoicesFormatted = [];
$listsFormatted = [];

$orderIds = array_column($customerOrders, 'id');
$orderItemsByOrderId = [];
if (!empty($orderIds)) {
    $inClause = implode(',', array_map('intval', $orderIds));
    $itemsStmt = $pdo->query("SELECT * FROM order_items WHERE order_id IN ($inClause) ORDER BY id ASC");
    $allOrderItems = $itemsStmt ? $itemsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($allOrderItems as $oi) {
        $orderItemsByOrderId[$oi['order_id']][] = [
            'name' => $oi['product_name'],
            'poriman' => $oi['product_poriman'] ?? '',
            'quantity' => (int)$oi['quantity'],
            'price' => (float)$oi['unit_price'],
            'subtotal' => (float)$oi['subtotal']
        ];
    }
}

foreach ($customerOrders as $o) {
    $dateFormatted = date('M d, Y, h:i A', strtotime($o['created_at']));
    
    // Extract images if uploaded photo order
    $extractedImages = [];
    if (!empty($o['html_content'])) {
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $o['html_content'], $imgMatches)) {
            foreach ($imgMatches[1] as $src) {
                if (!str_contains($src, 'bazarkinun') && !str_contains($src, 'logo')) {
                    $extractedImages[] = $src;
                }
            }
        }
    }

    $isUploadedPhoto = ($o['order_type'] === 'uploaded') || (!empty($extractedImages));
    $orderTypeVal = $isUploadedPhoto ? 'uploaded' : ($o['order_type'] ?: 'text_list');

    $itemObj = [
        'id' => $o['order_number'] ?: ('ORD-' . $o['id']),
        'order_number' => $o['order_number'],
        'invoice_number' => $o['invoice_number'] ?? '',
        'db_id' => (int)$o['id'],
        'date' => $dateFormatted,
        'status' => ucfirst($o['status'] ?: 'Pending'),
        'total_amount' => number_format((float)$o['total_amount'], 2, '.', ''),
        'delivery_charge' => number_format((float)($o['delivery_charge'] ?? 0), 2, '.', ''),
        'discount_amount' => number_format((float)($o['discount_amount'] ?? 0), 2, '.', ''),
        'customer_name' => $o['customer_name'] ?: $customer['name'],
        'customer_phone' => $o['customer_phone'] ?: $customer['phone'],
        'customer_address' => $o['customer_address'] ?: $customer['default_location'],
        'police_station' => $o['police_station'],
        'district' => $o['district'] ?: 'Dhaka',
        'order_type' => $orderTypeVal,
        'list_type' => $isUploadedPhoto ? 'uploaded' : 'written',
        'images' => $extractedImages,
        'image_url' => $extractedImages[0] ?? null,
        'image_urls' => $extractedImages,
        'htmlContent' => $o['html_content'] ?: '',
        'items' => $orderItemsByOrderId[$o['id']] ?? [],
        'items_summary' => "Total Price: ৳" . number_format((float)$o['total_amount'] - (float)$o['delivery_charge'], 2) . "\nDelivery Charge: ৳" . number_format((float)$o['delivery_charge'], 2) . "\nTotal to Pay: ৳" . number_format((float)$o['total_amount'], 2),
    ];

    $isInvoice = (strpos($o['order_number'], 'INV-') === 0) || !empty($o['invoice_number']) || $o['order_type'] === 'website';

    if ($isInvoice) {
        $invObj = $itemObj;
        $invObj['id'] = $o['invoice_number'] ?: (strpos($itemObj['id'], 'INV-') === 0 ? $itemObj['id'] : str_replace('ORD-', 'INV-', $itemObj['id']));
        $invoicesFormatted[] = $invObj;
    } else {
        // List-based / active orders go into Orders tab
        $ordersFormatted[] = $itemObj;
    }
}

// Fetch Custom Lists from custom_lists table
$cListStmt = $pdo->prepare("SELECT * FROM custom_lists WHERE user_id = ? OR customer_phone = ? ORDER BY id DESC");
$cListStmt->execute([$customerId, $customer['phone']]);
$customLists = $cListStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($customLists as $cl) {
    $dateFormatted = date('M d, Y, h:i A', strtotime($cl['created_at']));
    $imgUrl = null;
    $imgUrls = [];
    if (!empty($cl['images'])) {
        $decoded = json_decode($cl['images'], true);
        if (is_array($decoded)) {
            $imgUrls = $decoded;
            $imgUrl = $decoded[0] ?? null;
        } else {
            $imgUrls = [$cl['images']];
            $imgUrl = $cl['images'];
        }
    }
    $listCode = $cl['list_code'] ?: ($cl['title'] ?: ('LST-' . $cl['id']));
    $listsFormatted[] = [
        'id' => $listCode,
        'list_code' => $listCode,
        'db_id' => (int)$cl['id'],
        'date' => $dateFormatted,
        'status' => ucfirst($cl['status'] ?: 'Draft'),
        'is_processed' => (bool)$cl['is_processed'],
        'is_accepted' => (bool)$cl['is_accepted'],
        'list_type' => $cl['list_type'] ?: 'written',
        'customer_name' => $cl['customer_name'] ?: $customer['name'],
        'customer_phone' => $cl['customer_phone'] ?: $customer['phone'],
        'customer_address' => $cl['customer_address'] ?: ($customer['address'] ?: $customer['default_location']),
        'content_text' => $cl['content_text'] ?: '',
        'htmlContent' => $cl['content_text'] ?: '',
        'image_url' => $imgUrl,
        'image_urls' => $imgUrls,
    ];
}

// Fetch Active Catalog Products & Categories for Invoice / Order Catalog Picker
$catProdStmt = $pdo->query("
    SELECT p.id, p.product_code, p.category_id, p.title, p.title_bn, p.brand, p.poriman, p.poriman_bn, p.price, p.old_price, p.image, p.stock, 
           c.name as category_name, c.name_bn as category_name_bn
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE (p.is_active = 1 OR p.status = 'active')
    ORDER BY p.id ASC
");
$catalogProducts = $catProdStmt ? $catProdStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$categoriesStmt = $pdo->query("SELECT id, name, name_bn FROM categories ORDER BY id ASC");
$catalogCategories = $categoriesStmt ? $categoriesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Fetch Active Discount Rules & Delivery Rules for Live Calculation Engine
$discountRulesStmt = $pdo->query("SELECT * FROM discount_rules WHERE is_active = 1 ORDER BY priority ASC, id ASC");
$discountRules = $discountRulesStmt ? $discountRulesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$deliveryRulesStmt = $pdo->query("SELECT * FROM delivery_charge_rules WHERE is_active = 1 ORDER BY priority ASC, id ASC");
$deliveryRules = $deliveryRulesStmt ? $deliveryRulesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// 3. Real Active Cart & Wishlist from customer record
// -------------------------------------------------------------
$rawCart = json_decode($customer['cart_data'] ?? '[]', true) ?: [];
$cartItems = [];
$cartTotal = 0;
foreach ($rawCart as $p) {
    $qty = (int)($p['quantity'] ?? $p['qty'] ?? 1);
    $price = (float)($p['price'] ?? 0);
    $sub = $price * $qty;
    $cartTotal += $sub;
    $cartItems[] = [
        'name' => $p['title'] ?? $p['name'] ?? 'Product',
        'name_bn' => $p['name_bn'] ?? '',
        'price' => number_format($price, 2, '.', ''),
        'old_price' => number_format((float)($p['oldPrice'] ?? $p['old_price'] ?? $price), 2, '.', ''),
        'quantity' => $qty,
        'subtotal' => number_format($sub, 2, '.', ''),
        'img' => $p['image'] ?? $p['img'] ?? 'assets/bazarkinundotcom_logo.png',
        'poriman' => $p['poriman'] ?? $p['unit'] ?? '',
    ];
}
$cartCount = count($cartItems);

$rawWishlist = json_decode($customer['wishlist_data'] ?? '[]', true) ?: [];
$wishlistItems = [];
foreach ($rawWishlist as $p) {
    $price = (float)($p['price'] ?? 0);
    $wishlistItems[] = [
        'name' => $p['title'] ?? $p['name'] ?? 'Product',
        'name_bn' => $p['name_bn'] ?? '',
        'price' => number_format($price, 2, '.', ''),
        'old_price' => number_format((float)($p['oldPrice'] ?? $p['old_price'] ?? $price), 2, '.', ''),
        'img' => $p['image'] ?? $p['img'] ?? 'assets/bazarkinundotcom_logo.png',
        'poriman' => $p['poriman'] ?? $p['unit'] ?? '',
        'category' => $p['category'] ?? $p['category_name'] ?? 'General',
    ];
}
$wishlistCount = count($wishlistItems);

$listsCount = count($listsFormatted);
$ordersCount = count($ordersFormatted);
$invoicesCount = count($invoicesFormatted);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($customer['name']) ?> - <?= $portalName ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/bazarkinundotcom_logo.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="js/bd_locations.js"></script>

    <style>
        :root {
            --primary-color: <?= $isAdminPortal ? '#8b5cf6' : '#2563eb' ?>;
            --secondary-color: <?= $isAdminPortal ? '#7c3aed' : '#1d4ed8' ?>;
            --text-dark: #111827;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        /* Top Bar */
        .top-navbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 24px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .dashboard-header h2 { margin: 0; font-size: 24px; color: #111827; font-weight: 800; }
        
        .tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        .tabs-nav a {
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            color: #6b7280;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .tabs-nav a.active {
            background: var(--primary-color);
            color: white;
        }
        .tabs-nav a:hover:not(.active) {
            background: #e2e8f0;
            color: #111827;
        }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; padding: 15px; text-align: left; font-size: 13px; color: #6b7280; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        .data-table td { padding: 15px; border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #374151; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #fefce8; }
        
        .btn-primary { background: var(--primary-color); color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; font-size: 14px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn-outline { background: white; color: #4b5563; border: 1px solid #d1d5db; padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-outline:hover { background: #f3f4f6; color: #111827; border-color: #9ca3af; }
        .btn-danger { background: white; color: #ef4444; border: 1px solid #fca5a5; padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-danger:hover { background: #fee2e2; border-color: #ef4444; }

        .custom-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 2000; }
        .modal-content { background: white; border-radius: 16px; padding: 30px; width: 100%; max-width: 550px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; }
        .modal-header h3 { margin: 0; font-size: 20px; color: #111827; font-weight: 800; }
        .modal-close { background: none; border: none; font-size: 24px; color: #9ca3af; cursor: pointer; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 6px; color: #374151; font-size: 13px; }
        .form-input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-danger { background: #fee2e2; color: #ef4444; }

        /* Profile UI */
        .profile-header { display: flex; align-items: center; gap: 25px; margin-bottom: 30px; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e5e7eb; }
        .large-avatar-container { width: 100px; height: 100px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); position: relative; }
        .avatar-edit-overlay { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: white; font-size: 11px; font-weight: bold; text-align: center; padding: 4px 0; cursor: pointer; display: none; }
        .profile-title h2 { margin: 0 0 5px 0; font-size: 26px; color: #111827; font-weight: 900; }
        .profile-title p { margin: 0; color: #6b7280; font-size: 15px; }
        .profile-form { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e5e7eb; max-width: 650px; margin: 0 auto; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; transition: 0.3s; box-sizing: border-box; color: #111827; background: white; }
        .form-control:disabled { background: #f9fafb; color: #6b7280; border-color: #e5e7eb; cursor: not-allowed; }
        .form-control:focus:not(:disabled) { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .location-hint { font-size: 12px; color: #ef4444; margin-top: 6px; font-weight: 600; display: none; }
        .action-buttons { display: none; gap: 15px; margin-top: 30px; }
        .action-buttons .btn { flex: 1; justify-content: center; }

        /* Stacked List Layout */
        .list-layout { display: flex; flex-direction: column; gap: 15px; width: 100%; margin-top: 20px; }
        .list-item-card { display: flex; align-items: center; background: white; padding: 18px 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e5e7eb; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .list-item-card:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.06); border-color: #cbd5e1; }
        .list-item-thumb { width: 75px; height: 75px; background: #f9fafb; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 25px; overflow: hidden; flex-shrink: 0; border: 1px solid #f1f5f9; }
        .list-item-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .list-item-thumb i { font-size: 32px; color: #94a3b8; }
        .list-item-body { flex: 1; }
        .list-item-id { font-size: 18px; font-weight: 900; color: #111827; margin: 0 0 4px 0; }
        .list-item-date { color: #6b7280; font-size: 13px; margin: 0; }
        .list-item-action { color: var(--primary-color); font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        
        .preview-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); display: none; flex-direction: column; align-items: center; overflow-y: auto; z-index: 3000; padding: 40px 20px; box-sizing: border-box; }
        .modal-close-btn { position: absolute; top: -16px; right: -16px; background: #ffffff; border: 2px solid #e2e8f0; width: 42px; height: 42px; border-radius: 50%; font-size: 18px; color: #1e293b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); z-index: 3100; box-shadow: 0 4px 16px rgba(0,0,0,0.18); }
        .modal-close-btn:hover { background: #fee2e2; color: #ef4444; border-color: #fca5a5; transform: scale(1.08); }

        @media (max-width: 768px) {
            .profile-header { flex-direction: column !important; text-align: center !important; }
            .profile-form { padding: 20px !important; }
            .data-table { display: block; width: 100%; overflow-x: auto; }
            .list-item-card { padding: 14px !important; flex-wrap: wrap !important; }
            .list-item-action { width: 100% !important; justify-content: flex-end !important; margin-top: 8px; }
            .top-navbar { flex-wrap: wrap !important; gap: 10px !important; }
        }

        @media (max-width: 480px) {
            .dashboard-header { padding: 18px 14px !important; }
            .profile-form { padding: 16px 12px !important; }
            .list-item-thumb { width: 55px !important; height: 55px !important; margin-right: 12px !important; }
            .preview-modal { padding: 20px 10px !important; }
        }

        @media (max-width: 374px) {
            .dashboard-header { padding: 14px 10px !important; }
            .list-item-id { font-size: 15px !important; }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="top-navbar">
        <div style="display: flex; align-items: center; gap: 14px;">
            <a href="<?= htmlspecialchars($backUrl) ?>" style="color: #4b5563; text-decoration: none; font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 8px; background: #f1f5f9; transition: all 0.2s;">
                <i class="fa-solid fa-arrow-left"></i> Customer Directory
            </a>
            <span style="font-size: 18px; font-weight: 900; color: #111827;">Customer Details</span>
        </div>
        <div>
            <span style="font-size: 12px; font-weight: 800; background: <?= $isAdminPortal ? '#ede9fe' : '#dbeafe' ?>; color: <?= $isAdminPortal ? '#6d28d9' : '#1d4ed8' ?>; padding: 4px 12px; border-radius: 20px;">
                <?= strtoupper($portalName) ?>
            </span>
        </div>
    </header>

    <main class="container">
        <!-- Customer Banner Header -->
        <div class="dashboard-header">
            <div>
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <?= htmlspecialchars($customer['name']) ?>
                </h2>
                <p style="color: #6b7280; margin: 5px 0 0 0; font-size: 14px;">
                    <i class="fa-solid fa-phone" style="font-size: 12px;"></i> <?= htmlspecialchars($customer['phone']) ?> &nbsp;|&nbsp; 
                    <i class="fa-regular fa-envelope" style="font-size: 12px;"></i> <?= htmlspecialchars($customer['email'] ?: 'No email') ?> &nbsp;|&nbsp;
                    Joined <?= date('M d, Y', strtotime($customer['created_at'])) ?>
                </p>
            </div>
        </div>

        <!-- Multi Tabs Navigation -->
        <div class="tabs-nav">
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=profile" class="<?= $tab === 'profile' ? 'active' : '' ?>">
                <i class="fa-solid fa-id-card"></i> Profile
            </a>
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=location" class="<?= $tab === 'location' ? 'active' : '' ?>">
                <i class="fa-solid fa-map-location-dot"></i> Addresses (<?= count($addresses) ?>)
            </a>
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=cart" class="<?= $tab === 'cart' ? 'active' : '' ?>">
                <i class="fa-solid fa-cart-shopping"></i> Cart <?php if ($cartCount > 0): ?><span class="badge badge-success" style="padding: 2px 7px; border-radius: 10px; font-size: 11px;"><?= $cartCount ?></span><?php endif; ?>
            </a>
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=wishlist" class="<?= $tab === 'wishlist' ? 'active' : '' ?>">
                <i class="fa-solid fa-heart" style="color: #ef4444;"></i> Wishlist <?php if ($wishlistCount > 0): ?><span class="badge" style="background: #fee2e2; color: #ef4444; padding: 2px 7px; border-radius: 10px; font-size: 11px;"><?= $wishlistCount ?></span><?php endif; ?>
            </a>
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=list" class="<?= $tab === 'list' ? 'active' : '' ?>">
                <i class="fa-solid fa-list-check"></i> List <?php if ($listsCount > 0): ?><span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 2px 7px; border-radius: 10px; font-size: 11px;"><?= $listsCount ?></span><?php endif; ?>
            </a>
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=order" class="<?= $tab === 'order' ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-packing"></i> Order <?php if ($ordersCount > 0): ?><span class="badge" style="background: #dcfce7; color: #16a34a; padding: 2px 7px; border-radius: 10px; font-size: 11px;"><?= $ordersCount ?></span><?php endif; ?>
            </a>
            <a href="?id=<?= $customerId ?>&portal=<?= $isAdminPortal ? 'admin' : 'mng' ?>&tab=invoice" class="<?= $tab === 'invoice' ? 'active' : '' ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> Invoice <?php if ($invoicesCount > 0): ?><span class="badge" style="background: #fef3c7; color: #d97706; padding: 2px 7px; border-radius: 10px; font-size: 11px;"><?= $invoicesCount ?></span><?php endif; ?>
            </a>
        </div>

        <!-- 1. TAB: PROFILE -->
        <?php if ($tab === 'profile'): ?>
            <div class="profile-header">
                <div class="avatar-section">
                    <div class="large-avatar-container" id="large-avatar-container">
                        <?php if (!empty($customer['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($customer['profile_photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fa-solid fa-user" style="color: #9ca3af; font-size: 40px;" id="default-avatar-icon"></i>
                        <?php endif; ?>
                        <div class="avatar-edit-overlay" id="avatar-edit-overlay" onclick="triggerPhotoUpload()">Change</div>
                    </div>
                    <input type="file" id="photo-input" accept="image/*" style="display: none;" onchange="handlePhotoChange(event)">
                </div>
                <div class="profile-title">
                    <h2 id="display-name"><?= htmlspecialchars($customer['name']) ?></h2>
                    <p><i class="fa-solid fa-phone" style="font-size: 14px; margin-right: 5px;"></i> <span id="display-phone"><?= htmlspecialchars($customer['phone']) ?></span></p>
                </div>
            </div>

            <form class="profile-form" id="profile-form" onsubmit="submitCustomerProfile(event)">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" id="prof_name" value="<?= htmlspecialchars($customer['name']) ?>" disabled required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" class="form-control" id="prof_phone" value="<?= htmlspecialchars($customer['phone']) ?>" disabled required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" id="prof_email" value="<?= htmlspecialchars($customer['email'] ?: '') ?>" disabled>
                </div>
                <div class="form-group">
                    <label>District</label>
                    <select class="form-control" id="prof_district" onchange="fetchMngUpazilas(this.value, 'prof_ps')" disabled data-selected="<?= htmlspecialchars($customerDistrictDefault) ?>">
                        <option value="" disabled selected>Select District...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Police Station (Thana)</label>
                    <select class="form-control" id="prof_ps" disabled data-selected="<?= htmlspecialchars($customerThanaDefault) ?>">
                        <option value="" disabled selected>Select Station...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Full Location / Street Address</label>
                    <input type="text" class="form-control" id="prof_address" value="<?= htmlspecialchars($customerAddressSummary ?: '') ?>" disabled>
                    <p class="location-hint" id="location-hint">Please provide customer's complete delivery address (e.g. House 12, Road 5, Block C, Uttara, Dhaka).</p>
                </div>
                
                <button type="button" class="btn-primary" id="edit-btn" onclick="toggleEditMode()" style="width: 100%; margin-top: 20px; justify-content: center;">
                    <i class="fa-solid fa-pen"></i> Edit Profile
                </button>
                
                <div class="action-buttons" id="action-buttons">
                    <button type="button" class="btn-outline" onclick="cancelEdit()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- 2. TAB: ADDRESSES (LOCATION) -->
        <?php if ($tab === 'location'): ?>
            <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
                <button class="btn-primary" onclick="openAddAddressModal()"><i class="fa-solid fa-plus"></i> Add New Address</button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Label (Keyword)</th>
                        <th>District</th>
                        <th>Police Station</th>
                        <th>Street Address</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($addresses)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 25px; color: #6b7280;">No addresses found for this customer.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($addresses as $addr): ?>
                            <tr>
                                <td><span style="font-weight: 700; text-transform: capitalize; background: #f1f5f9; padding: 4px 10px; border-radius: 6px;"><?= htmlspecialchars($addr['address_type']) ?></span></td>
                                <td><strong><?= htmlspecialchars($addr['keyword']) ?></strong></td>
                                <td><?= htmlspecialchars($addr['district']) ?></td>
                                <td><?= htmlspecialchars($addr['police_station']) ?></td>
                                <td><?= htmlspecialchars($addr['street']) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button class="btn-outline" style="color: #2563eb; border-color: #2563eb;" onclick="showAddressInMap('<?= $addr['latitude'] ?>', '<?= $addr['longitude'] ?>', '<?= addslashes($addr['street']) ?>', '<?= addslashes($addr['police_station']) ?>', '<?= addslashes($addr['district']) ?>')">
                                        <i class="fa-solid fa-map-location-dot"></i> Map
                                    </button>
                                    <button class="btn-outline" onclick="openEditAddressModal('<?= $addr['id'] ?>', '<?= addslashes($addr['address_type']) ?>', '<?= addslashes($addr['keyword']) ?>', '<?= addslashes($addr['district']) ?>', '<?= addslashes($addr['police_station']) ?>', '<?= addslashes($addr['street']) ?>', '<?= $addr['latitude'] ?>', '<?= $addr['longitude'] ?>')">
                                        <i class="fa-solid fa-pen" style="color: #f59e0b;"></i> Edit
                                    </button>
                                    <button class="btn-danger" onclick="deleteAddress('<?= $addr['id'] ?>')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Add/Edit Address Modal -->
            <div class="custom-modal" id="addressModal">
                <div class="modal-content" style="max-width: 480px;">
                    <div class="modal-header">
                        <h3 id="addr_modal_title">Add New Address</h3>
                        <button class="modal-close" onclick="closeModal('addressModal')">&times;</button>
                    </div>
                    <form onsubmit="submitAddress(event)">
                        <input type="hidden" id="addr_id">
                        <div class="form-group">
                            <label>Address Type</label>
                            <select id="addr_type" class="form-input" required>
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Keyword / Label</label>
                            <input type="text" id="addr_keyword" class="form-input" placeholder="Home, Office" required>
                        </div>
                        <div class="form-group">
                            <label>District</label>
                            <select id="addr_district" class="form-input" onchange="fetchMngUpazilas(this.value, 'addr_ps')" required>
                                <option value="" disabled selected>Select District...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Police Station (Thana)</label>
                            <select id="addr_ps" class="form-input" required>
                                <option value="" disabled selected>Select Station...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Street Address</label>
                            <input type="text" id="addr_street" class="form-input" placeholder="Road, House, Flat number" required>
                        </div>
                        <div class="form-group">
                            <label>Pin Location on Map</label>
                            <div id="edit-address-map" style="height: 220px; width: 100%; border-radius: 8px; border: 1px solid #d1d5db; position: relative;"></div>
                            <div style="text-align: center; font-size: 11px; color: #6b7280; margin-top: 5px;">Drag map to adjust pin position</div>
                            <input type="hidden" id="addr_lat" value="">
                            <input type="hidden" id="addr_lng" value="">
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">Save Address</button>
                    </form>
                </div>
            </div>

            <!-- View Map Modal -->
            <div class="custom-modal" id="mapViewModal">
                <div class="modal-content" style="max-width: 600px; padding: 0; overflow: hidden;">
                    <div class="modal-header" style="padding: 15px 20px; border-bottom: 1px solid #e5e7eb; margin: 0;">
                        <h3 style="margin: 0; font-size: 18px; color: #111827;">Address Location</h3>
                        <button class="modal-close" onclick="closeModal('mapViewModal')">&times;</button>
                    </div>
                    <div id="view-map" style="height: 320px; width: 100%; z-index: 1;"></div>
                    
                    <div style="padding: 20px;">
                        <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px;">
                            <div style="width: 36px; height: 36px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-location-dot" style="color: #2563eb; font-size: 16px;"></i>
                            </div>
                            <div>
                                <div id="view-map-address-primary" style="font-weight: 800; color: #111827; font-size: 15px; margin-bottom: 3px;">-</div>
                                <div id="view-map-address-secondary" style="color: #6b7280; font-size: 13px;">-</div>
                            </div>
                        </div>

                        <div style="background: #f9fafb; border-radius: 8px; padding: 12px 16px; display: flex; gap: 20px;">
                            <div>
                                <div style="color: #6b7280; font-size: 11px; margin-bottom: 2px;">Latitude</div>
                                <div id="view-map-lat" style="color: #111827; font-size: 13px; font-weight: 600;">-</div>
                            </div>
                            <div style="width: 1px; background: #e5e7eb;"></div>
                            <div>
                                <div style="color: #6b7280; font-size: 11px; margin-bottom: 2px;">Longitude</div>
                                <div id="view-map-lng" style="color: #111827; font-size: 13px; font-weight: 600;">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3. TAB: CART -->
        <?php if ($tab === 'cart'): ?>
            <?php if (!empty($cartItems)): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="background: white; border-radius: 12px; padding: 20px 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 style="margin: 0 0 4px 0; font-size: 18px; color: #111827; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-cart-shopping" style="color: var(--primary-color);"></i> Active Shopping Cart
                            </h3>
                            <p style="margin: 0; font-size: 13px; color: #6b7280;">Live items in this customer's shopping cart.</p>
                        </div>
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div style="text-align: right;">
                                <div style="font-size: 12px; color: #6b7280; font-weight: 600;">Total Items</div>
                                <div style="font-size: 18px; font-weight: 800; color: #111827;"><?= $cartCount ?> pcs</div>
                            </div>
                            <div style="height: 35px; width: 1px; background: #e5e7eb;"></div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; color: #6b7280; font-weight: 600;">Estimated Value</div>
                                <div style="font-size: 20px; font-weight: 900; color: #059669;">৳<?= number_format($cartTotal, 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <table class="data-table" style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Item</th>
                                <th>Product Title</th>
                                <th style="text-align: center;">Unit Price</th>
                                <th style="text-align: center;">Quantity</th>
                                <th style="text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div style="width: 55px; height: 55px; background: #f9fafb; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e5e7eb;">
                                            <img src="<?= htmlspecialchars($item['img']) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        <?php if (!empty($item['name_bn'])): ?>
                                            <div style="font-size: 12px; color: #6b7280;"><?= htmlspecialchars($item['name_bn']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['poriman'])): ?>
                                            <div style="font-size: 11.5px; color: #059669; font-weight: 600;"><?= htmlspecialchars($item['poriman']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">৳<?= $item['price'] ?></td>
                                    <td style="text-align: center;"><span class="badge badge-success"><?= $item['quantity'] ?></span></td>
                                    <td style="text-align: right; font-weight: 800; color: #059669;">৳<?= $item['subtotal'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f9fafb; font-weight: 800;">
                                <td colspan="4" style="text-align: right; font-size: 15px;">Total Cart Value:</td>
                                <td style="text-align: right; font-size: 17px; color: #059669;">৳<?= number_format($cartTotal, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background: white; border-radius: 12px; padding: 50px 20px; text-align: center; border: 1px dashed #d1d5db;">
                    <i class="fa-solid fa-cart-arrow-down" style="font-size: 36px; color: #9ca3af; margin-bottom: 12px;"></i>
                    <h3 style="margin: 0 0 6px 0;">Cart is Empty</h3>
                    <p style="margin: 0; color: #6b7280; font-size: 14px;">This customer does not have any items in their shopping cart right now.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 4. TAB: WISHLIST -->
        <?php if ($tab === 'wishlist'): ?>
            <?php if (!empty($wishlistItems)): ?>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="background: white; border-radius: 12px; padding: 20px 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 18px; color: #111827; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-heart" style="color: #ef4444;"></i> Customer's Wishlist
                        </h3>
                        <span class="badge" style="background: #fee2e2; color: #ef4444; font-size: 13px; padding: 6px 14px; border-radius: 20px;">
                            <?= $wishlistCount ?> Saved Items
                        </span>
                    </div>

                    <table class="data-table" style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Item</th>
                                <th>Product Title</th>
                                <th>Category</th>
                                <th style="text-align: center;">Price</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wishlistItems as $item): ?>
                                <tr>
                                    <td>
                                        <div style="width: 55px; height: 55px; background: #f9fafb; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e5e7eb;">
                                            <img src="<?= htmlspecialchars($item['img']) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        <?php if (!empty($item['name_bn'])): ?>
                                            <div style="font-size: 12px; color: #6b7280;"><?= htmlspecialchars($item['name_bn']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['poriman'])): ?>
                                            <div style="font-size: 11.5px; color: #059669; font-weight: 600;"><?= htmlspecialchars($item['poriman']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span style="font-weight: 600; text-transform: capitalize; color: #6b7280;"><?= htmlspecialchars($item['category']) ?></span></td>
                                    <td style="text-align: center; font-weight: 800; color: #059669;">৳<?= $item['price'] ?></td>
                                    <td style="text-align: center;"><span class="badge badge-success">Saved</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background: white; border-radius: 12px; padding: 50px 20px; text-align: center; border: 1px dashed #d1d5db;">
                    <i class="fa-regular fa-heart" style="font-size: 36px; color: #ef4444; margin-bottom: 12px;"></i>
                    <h3 style="margin: 0 0 6px 0;">Wishlist is Empty</h3>
                    <p style="margin: 0; color: #6b7280; font-size: 14px;">No products saved to wishlist yet.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 5. TAB: LISTS -->
        <?php if ($tab === 'list'): ?>
            <div id="list-grid" class="list-layout"></div>
        <?php endif; ?>

        <!-- 6. TAB: ORDERS -->
        <?php if ($tab === 'order'): ?>
            <div id="list-grid" class="list-layout"></div>
        <?php endif; ?>

        <!-- 7. TAB: INVOICES -->
        <?php if ($tab === 'invoice'): ?>
            <div id="list-grid" class="list-layout"></div>
        <?php endif; ?>
    </main>

    <!-- Document & Invoice Preview Modal -->
    <div class="preview-modal" id="preview-modal" onclick="if(event.target===this)closePreviewModal()">
        <div style="width: 100%; max-width: 800px; position: relative;" id="preview-wrapper">
            <button class="modal-close-btn" onclick="closePreviewModal()" title="Close Preview"><i class="fa-solid fa-xmark"></i></button>
            <div id="preview-content">
                <!-- Document content injected here -->
            </div>
        </div>
    </div>

    <!-- Admin Management Edit Modal for Lists, Orders, and Invoices -->
    <div class="preview-modal" id="admin-edit-modal" style="display: none; align-items: center; justify-content: center; z-index: 10000;" onclick="if(event.target===this)closeAdminEditModal()">
        <div style="background: white; width: 100%; max-width: 850px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; position: relative;">
            <!-- Modal Header -->
            <div style="background: #0f172a; color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: rgba(0, 198, 117, 0.2); width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-pen-to-square" style="color: #00c675; font-size: 17px;"></i>
                    </div>
                    <div>
                        <h3 id="edit-modal-title" style="margin: 0; font-size: 17px; font-weight: 800; color: white; display: flex; align-items: center; gap: 8px;">Edit Document</h3>
                        <p id="edit-modal-subtitle" style="margin: 2px 0 0 0; font-size: 12px; color: #94a3b8;">Update customer details, items, prices, and status</p>
                    </div>
                </div>
                <button type="button" onclick="closeAdminEditModal()" style="background: #334155; border: none; color: #cbd5e1; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: all 0.2s;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div style="padding: 24px; overflow-y: auto; flex: 1;" id="edit-modal-body">
                <input type="hidden" id="edit-item-id">
                <input type="hidden" id="edit-item-db-id">
                <input type="hidden" id="edit-item-type">

                <!-- Customer Details Form Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 18px;">
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Customer Name</label>
                        <input type="text" id="edit-cust-name" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Phone Number</label>
                        <input type="text" id="edit-cust-phone" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Status</label>
                        <select id="edit-doc-status" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box; background: white; font-weight: 600;">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="delivering">Out for Delivery</option>
                            <option value="completed">Completed / Delivered</option>
                            <option value="draft">Draft</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Delivery Location / Address</label>
                    <input type="text" id="edit-cust-address" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
                </div>

                <!-- ORDER / INVOICE SECTION (Items Table) -->
                <div id="edit-order-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <h4 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-basket-shopping" style="color: #00c675;"></i> Invoice / Order Items
                        </h4>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" onclick="openProductCatalogModal()" style="background: #00c675; border: none; color: white; font-weight: 700; font-size: 12.5px; padding: 7px 16px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(0, 198, 117, 0.25); transition: 0.2s;">
                                <i class="fa-solid fa-cart-plus"></i> Add Products from Catalog
                            </button>
                            <button type="button" onclick="addEditItemRow('', '', 0, 1)" style="background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #16a34a; font-weight: 700; font-size: 12.5px; padding: 6px 14px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <i class="fa-solid fa-plus"></i> Custom Item
                            </button>
                        </div>
                    </div>

                    <div style="border: 1.5px solid #e2e8f0; border-radius: 10px; overflow-x: auto; margin-bottom: 18px; background: white;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;" id="edit-items-table">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; font-size: 12px; color: #64748b; text-transform: uppercase;">
                                    <th style="padding: 10px 8px; width: 40px; text-align: center;">#</th>
                                    <th style="padding: 10px 10px;">Item Title / Name</th>
                                    <th style="padding: 10px 10px; width: 140px;">Spec / Weight</th>
                                    <th style="padding: 10px 10px; width: 110px; text-align: right;">Unit Price (৳)</th>
                                    <th style="padding: 10px 10px; width: 85px; text-align: center;">Qty</th>
                                    <th style="padding: 10px 12px; width: 110px; text-align: right;">Total (৳)</th>
                                    <th style="padding: 10px 8px; width: 45px; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody id="edit-items-tbody">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary and Totals Box -->
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                        <!-- Left side: Coupon / Promo Code Input -->
                        <div style="max-width: 320px; width: 100%;">
                            <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                                <i class="fa-solid fa-tag" style="color: #00c675;"></i> Apply Promo / Coupon Code
                            </label>
                            <div style="display: flex; gap: 6px;">
                                <input type="text" id="edit-coupon-code" placeholder="e.g. BAZAR5" style="flex: 1; padding: 7px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 700; text-transform: uppercase; font-family: inherit;">
                                <button type="button" onclick="applyEditCoupon()" style="background: #0f172a; color: white; border: none; padding: 7px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;">
                                    Apply
                                </button>
                            </div>
                            <span style="display: block; font-size: 11px; color: #64748b; margin-top: 4px;">Enter a coupon code to apply automatic discount</span>
                            
                            <!-- Active Offers & Free Gifts Container -->
                            <div id="edit-active-offers-container" style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;"></div>
                        </div>

                        <!-- Right side: Calculation Breakdown -->
                        <div style="width: 100%; max-width: 330px; display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13.5px; color: #475569;">
                                <span>Subtotal:</span>
                                <strong id="edit-summary-subtotal" style="color: #0f172a;">৳0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: #475569;">
                                <span style="display: flex; align-items: center; gap: 5px;">
                                    Delivery Charge (৳):
                                    <button type="button" onclick="autoSetDeliveryCharge()" title="Recalculate based on store rules" style="background: #e0f2fe; color: #0284c7; border: none; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 700; cursor: pointer;">Auto</button>
                                </span>
                                <input type="number" id="edit-delivery-charge" value="0" min="0" step="1" oninput="recalcEditTotal(false)" style="width: 90px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 700;">
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: #475569;">
                                <span>Discount (৳):</span>
                                <input type="number" id="edit-discount-amount" value="0" min="0" step="1" oninput="recalcEditTotal(false)" style="width: 90px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 700; color: #ef4444;">
                            </div>
                            <hr style="border: none; border-top: 1.5px solid #cbd5e1; margin: 4px 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 15px; font-weight: 800; color: #0f172a;">Grand Total:</span>
                                <span id="edit-summary-grandtotal" style="font-size: 20px; font-weight: 900; color: #059669;">৳0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CUSTOM LIST / GROCERY LIST ORDER SECTION -->
                <div id="edit-list-section" style="display: none;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;" id="edit-list-type-label">Format / Type</label>
                        <select id="edit-list-type" onchange="handleListTypeChange()" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-family: inherit; box-sizing: border-box; background: white; font-weight: 600;">
                            <option value="written">Written / Typed Grocery List</option>
                            <option value="uploaded">Uploaded Photo List / Order</option>
                        </select>
                    </div>

                    <div id="edit-list-items-container" style="margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <h4 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-list-check" style="color: #3b82f6;"></i> Grocery Items List
                                </h4>
                                <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Type your grocery items one by one below. Enter product name, brand/details, and quantity.</p>
                            </div>
                            <button type="button" onclick="addEditCustomListRow('', '', '1')" style="background: #eff6ff; border: 1.5px solid #bfdbfe; color: #2563eb; font-weight: 700; font-size: 12.5px; padding: 6px 14px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <i class="fa-solid fa-plus"></i> Add Item
                            </button>
                        </div>

                        <div style="border: 1.5px solid #e2e8f0; border-radius: 10px; overflow-x: auto; background: white;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left;" id="edit-custom-list-table">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; font-size: 12px; color: #64748b; text-transform: uppercase;">
                                        <th style="padding: 10px 8px; width: 45px; text-align: center;">SL</th>
                                        <th style="padding: 10px 10px;">Item Name</th>
                                        <th style="padding: 10px 10px; width: 230px;">Specification / Brand</th>
                                        <th style="padding: 10px 10px; width: 140px; text-align: center;">Quantity / Unit</th>
                                        <th style="padding: 10px 8px; width: 45px; text-align: center;"></th>
                                    </tr>
                                </thead>
                                <tbody id="edit-custom-list-tbody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="edit-list-image-container" style="display: none; background: #f8fafc; padding: 18px; border-radius: 12px; border: 1.5px solid #e2e8f0; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <label style="font-size: 13.5px; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-images" style="color: #6366f1;"></i> Attached Grocery List Photos
                                    <span id="edit-list-photos-count-badge" style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 20px; font-size: 11.5px; font-weight: 800;">0 photos</span>
                                </label>
                                <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;">Manage customer grocery list photos or upload new ones.</p>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <input type="file" id="edit-custom-list-file-input" accept="image/*" multiple style="display: none;" onchange="handleAdminListImageUpload(event)">
                                <button type="button" onclick="document.getElementById('edit-custom-list-file-input').click()" style="background: #6366f1; border: none; color: white; padding: 7px 16px; border-radius: 8px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25); transition: 0.2s;">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload / Add Photos
                                </button>
                            </div>
                        </div>

                        <!-- Drop & Upload Zone -->
                        <div id="edit-list-drop-zone" onclick="document.getElementById('edit-custom-list-file-input').click()" style="border: 2px dashed #cbd5e1; background: white; border-radius: 10px; padding: 18px; text-align: center; cursor: pointer; transition: all 0.2s ease; margin-bottom: 14px;" onmouseover="this.style.borderColor='#6366f1'; this.style.background='#f5f3ff'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='white'">
                            <i class="fa-solid fa-camera" style="font-size: 24px; color: #818cf8; margin-bottom: 6px; display: block;"></i>
                            <span style="font-size: 13px; font-weight: 700; color: #334155; display: block;">Click to browse or drag & drop photo(s) here</span>
                            <span style="font-size: 11.5px; color: #94a3b8; display: block; margin-top: 2px;">Supports JPG, JPEG, PNG, WEBP (Multiple images allowed)</span>
                        </div>

                        <!-- Photo Gallery Grid -->
                        <div id="edit-list-photos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                            <!-- Dynamic Photo Cards -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeAdminEditModal()" style="background: white; border: 1.5px solid #cbd5e1; color: #475569; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; transition: all 0.2s;">
                    Cancel
                </button>
                <button type="button" id="btn-save-admin-edit" onclick="saveAdminEdit()" style="background: #00c675; border: none; color: white; padding: 10px 24px; border-radius: 8px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3);">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- ADD PRODUCTS TO ORDER MODAL (CATALOG PICKER) -->
    <div id="catalog-product-modal" style="display: none; position: fixed; inset: 0; z-index: 100000; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 16px;">
        <div style="background: white; border-radius: 16px; width: 100%; max-width: 960px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.3); animation: popModal 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
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

                <!-- Categories Scrollable Pills -->
                <div id="catalog-category-pills" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: thin;">
                    <!-- Category Pills rendered dynamically -->
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

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const userOrders = <?= json_encode($ordersFormatted, JSON_UNESCAPED_UNICODE) ?>;
        const userInvoices = <?= json_encode($invoicesFormatted, JSON_UNESCAPED_UNICODE) ?>;
        const userLists = <?= json_encode($listsFormatted, JSON_UNESCAPED_UNICODE) ?>;
        const catalogProducts = <?= json_encode($catalogProducts, JSON_UNESCAPED_UNICODE) ?>;
        const catalogCategories = <?= json_encode($catalogCategories, JSON_UNESCAPED_UNICODE) ?>;
        const storeDiscountRules = <?= json_encode($discountRules, JSON_UNESCAPED_UNICODE) ?>;
        const storeDeliveryRules = <?= json_encode($deliveryRules, JSON_UNESCAPED_UNICODE) ?>;
        const currentTab = "<?= $tab ?>";
        const customerId = <?= $customerId ?>;

        document.addEventListener("DOMContentLoaded", function() {
            if (currentTab === "list") renderCards('list-grid', userLists, 'list');
            if (currentTab === "order") renderCards('list-grid', userOrders, 'order');
            if (currentTab === "invoice") renderCards('list-grid', userInvoices, 'invoice');
            fetchMngDistricts();
        });

        function goBackToPreviousPage() {
            if (document.referrer && document.referrer.indexOf(window.location.host) !== -1 && !document.referrer.includes('mng_customer_detail.php')) {
                window.location.href = document.referrer;
            } else if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = 'dashboard.php';
            }
        }

        function findCustomerItem(id, type) {
            let list = (type === 'invoice') ? userInvoices : ((type === 'order') ? userOrders : userLists);
            return list.find(x => x.id === id || x.db_id === parseInt(id)) || null;
        }

        function renderCards(gridId, data, type) {
            const grid = document.getElementById(gridId);
            if (!grid) return;
            grid.innerHTML = '';
            
            if (!data || data.length === 0) {
                grid.innerHTML = `<div style="text-align: center; color: #9ca3af; padding: 40px; background: white; border-radius: 12px; border: 1px solid #e5e7eb;">No ${type}s found for this customer.</div>`;
                return;
            }
            
            data.forEach(item => {
                const card = document.createElement('div');
                card.className = 'list-item-card';
                
                let thumbHtml = '';
                if (item.list_type === 'uploaded' || (item.image_url)) {
                    thumbHtml = item.image_url ? `<img src="${item.image_url}" alt="Thumb">` : `<i class="fa-solid fa-image" style="color: #6366f1;"></i>`;
                } else if (item.list_type === 'written') {
                    thumbHtml = `<i class="fa-solid fa-file-lines" style="color: #3b82f6;"></i>`;
                } else {
                    thumbHtml = `<i class="fa-solid fa-box" style="color: #10b981;"></i>`;
                }
                
                let statusBadge = '';
                let statusText = item.status || 'Pending';
                let badgeColor = '#dbeafe; color: #1e40af;';
                if (['Delivered', 'Completed', 'Accepted', 'Paid'].includes(statusText)) {
                    badgeColor = '#d1fae5; color: #047857;';
                } else if (['Pending', 'Draft', 'Processing'].includes(statusText)) {
                    badgeColor = '#fef3c7; color: #b45309;';
                } else if (['Cancelled', 'Failed'].includes(statusText)) {
                    badgeColor = '#fee2e2; color: #b91c1c;';
                }
                statusBadge = `<span style="background: ${badgeColor}; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 800; margin-left: 8px;">${statusText}</span>`;
                
                let typeBadge = '';
                if (item.order_type) {
                    let typeName = item.order_type === 'website' ? 'Website' : (item.order_type === 'uploaded' ? 'Uploaded List' : 'Text List');
                    typeBadge = `<span style="background: #f1f5f9; color: #475569; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700; margin-left: 6px;">${typeName}</span>`;
                } else if (item.list_type) {
                    let typeName = item.list_type === 'uploaded' ? 'Upload' : 'Written';
                    typeBadge = `<span style="background: #f1f5f9; color: #475569; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 700; margin-left: 6px;">${typeName}</span>`;
                }

                let extraInfo = '';
                if (item.total_amount && parseFloat(item.total_amount) > 0) {
                    extraInfo = `<span style="color: #059669; font-weight: 800; font-size: 15px; margin-left: 10px;">৳${item.total_amount}</span>`;
                }
                
                card.innerHTML = `
                    <div class="list-item-thumb">
                        ${thumbHtml}
                    </div>
                    <div class="list-item-body">
                        <h3 class="list-item-id">${item.id} ${statusBadge} ${typeBadge} ${extraInfo}</h3>
                        <p class="list-item-date"><i class="fa-regular fa-clock"></i> ${item.date}</p>
                    </div>
                    <div class="list-item-action">
                        View Details <i class="fa-solid fa-chevron-right"></i>
                    </div>
                `;
                
                card.onclick = () => openPreviewModal(item, type);
                grid.appendChild(card);
            });
        }

        function extractListItemsFromContent(rawContent) {
            if (!rawContent) return [];
            const items = [];

            // 1. Try JSON
            try {
                const parsed = JSON.parse(rawContent);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    parsed.forEach(p => {
                        if (typeof p === 'object' && p !== null) {
                            items.push({
                                item: p.item || p.name || p.product || p.title || '',
                                spec: p.spec || p.brand || p.poriman || '',
                                qty: p.qty || p.quantity || '1',
                                price: p.price || '',
                                total: p.total || ''
                            });
                        } else if (typeof p === 'string' && p.trim()) {
                            let itemText = p.trim();
                            let spec = '';
                            const m = itemText.match(/^(.*?)\s*\((.*?)\)$/);
                            if (m) { itemText = m[1].trim(); spec = m[2].trim(); }
                            items.push({ item: itemText, spec: spec, qty: '1', price: '', total: '' });
                        }
                    });
                    if (items.length > 0) return items;
                }
            } catch(e) {}

            // 2. Try HTML table
            if (rawContent.includes('<table') || rawContent.includes('<tr')) {
                const temp = document.createElement('div');
                temp.innerHTML = rawContent;
                let trs = temp.querySelectorAll('tbody tr');
                if (trs.length === 0) trs = temp.querySelectorAll('tr');

                trs.forEach(tr => {
                    const tds = tr.querySelectorAll('td');
                    if (tds.length >= 2) {
                        let itemText = '';
                        let spec = '';
                        let qty = '1';
                        let price = '';
                        let total = '';

                        if (tds.length >= 5) {
                            const span = tds[1].querySelector('span');
                            if (span) {
                                spec = span.innerText.replace(/^\(|\)$/g, '').trim();
                                const clone = tds[1].cloneNode(true);
                                const sc = clone.querySelector('span');
                                if (sc) sc.remove();
                                itemText = clone.innerText.trim();
                            } else {
                                const raw = tds[1].innerText.trim();
                                const m = raw.match(/^(.*?)\s*\((.*?)\)$/);
                                if (m) { itemText = m[1].trim(); spec = m[2].trim(); }
                                else { itemText = raw; }
                            }
                            price = tds[2].innerText.replace(/[^\d\.]/g, '').trim();
                            qty = tds[3].innerText.trim() || '1';
                            total = tds[4].innerText.replace(/[^\d\.]/g, '').trim();
                        } else if (tds.length === 4) {
                            itemText = tds[1].innerText.trim();
                            spec = tds[2].innerText.trim();
                            qty = tds[3].innerText.trim() || '1';
                        } else if (tds.length === 3) {
                            const span = tds[1].querySelector('span');
                            if (span) {
                                spec = span.innerText.replace(/^\(|\)$/g, '').trim();
                                const clone = tds[1].cloneNode(true);
                                const sc = clone.querySelector('span');
                                if (sc) sc.remove();
                                itemText = clone.innerText.trim();
                            } else {
                                const raw = tds[1].innerText.trim();
                                const m = raw.match(/^(.*?)\s*\((.*?)\)$/);
                                if (m) { itemText = m[1].trim(); spec = m[2].trim(); }
                                else { itemText = raw; }
                            }
                            qty = tds[2].innerText.trim() || '1';
                        } else if (tds.length === 2) {
                            const raw = tds[0].innerText.trim();
                            const m = raw.match(/^(.*?)\s*\((.*?)\)$/);
                            if (m) { itemText = m[1].trim(); spec = m[2].trim(); }
                            else { itemText = raw; }
                            qty = tds[1].innerText.trim() || '1';
                        }

                        const lowerItem = itemText.toLowerCase();
                        if (itemText && !lowerItem.includes('item description') && !lowerItem.includes('পণ্যের নাম') && !lowerItem.includes('subtotal') && !lowerItem.includes('total') && !lowerItem.includes('delivery')) {
                            items.push({ item: itemText, spec: spec, qty: qty, price: price, total: total });
                        }
                    }
                });

                if (items.length > 0) return items;
                // If this is an A4 wrapper without real table items, do NOT fall through to text extraction
                if (rawContent.includes('a4-page') || rawContent.includes('bazarkinundotcom_logo') || rawContent.includes('Uploaded Photo List')) {
                    return [];
                }
            }

            // If it contains A4 HTML or upload placeholder, return empty
            if (rawContent.includes('a4-page') || rawContent.includes('bazarkinundotcom_logo') || rawContent.includes('Uploaded Photo List')) {
                return [];
            }

            // 3. Try plain text line by line
            const skipKeywords = ['bazarkinun', 'বাজারে নয়', 'support center', 'order type:', 'document:', 'list code:', 'order no:', 'invoice no:', 'date & time:', 'page:', 'name:', 'phone:', 'location:', 'uploaded photo list', 'total:'];
            const lines = rawContent.split('\n').map(l => l.trim()).filter(l => l.length > 0 && !l.includes('<div') && !l.includes('</div') && !l.includes('<p') && !l.includes('</p') && !l.includes('<table') && !l.includes('</table>') && !l.includes('<img'));
            
            lines.forEach(line => {
                const lineLower = line.toLowerCase();
                if (skipKeywords.some(kw => lineLower.includes(kw))) {
                    return;
                }
                const clean = line.replace(/^\d+[\.\-\)]\s*/, '').trim();
                if (clean && !clean.startsWith('<')) {
                    let itemText = clean;
                    let spec = '';
                    let qty = '1';

                    // Check for parentheses: "Item (Spec)" or "Item (Spec) - Qty"
                    const parenMatch = clean.match(/^(.*?)\s*\((.*?)\)(.*)$/);
                    if (parenMatch) {
                        itemText = parenMatch[1].trim();
                        spec = parenMatch[2].trim();
                        const rest = parenMatch[3].trim();
                        if (rest) {
                            const qtyMatch = rest.match(/^[-–:]\s*(.+)$/);
                            if (qtyMatch) {
                                qty = qtyMatch[1].trim();
                            }
                        }
                    } else if (clean.includes(' - ') || clean.includes(' – ') || clean.includes(' : ') || clean.includes(' | ')) {
                        const parts = clean.split(/\s*[-–:|]\s*/);
                        if (parts.length === 2) {
                            itemText = parts[0].trim();
                            qty = parts[1].trim();
                        } else if (parts.length >= 3) {
                            itemText = parts[0].trim();
                            spec = parts[1].trim();
                            qty = parts.slice(2).join(' - ').trim();
                        }
                    }

                    if (itemText && !skipKeywords.some(kw => itemText.toLowerCase().includes(kw))) {
                        items.push({ item: itemText, spec: spec, qty: qty, price: '', total: '' });
                    }
                }
            });

            return items;
        }

        function formatListItemsToText(rawContent) {
            if (!rawContent) return '';
            // If already clean plain text without any HTML tags
            if (!rawContent.includes('<div') && !rawContent.includes('<table') && !rawContent.includes('<p') && !rawContent.includes('<tr') && !rawContent.includes('<br')) {
                return rawContent.trim();
            }

            const items = extractListItemsFromContent(rawContent);
            if (items.length === 0) {
                const temp = document.createElement('div');
                temp.innerHTML = rawContent;
                return (temp.innerText || temp.textContent || '').trim();
            }

            return items.map((it, idx) => {
                let line = `${idx + 1}. ${it.item || 'Item'}`;
                if (it.spec) line += ` (${it.spec})`;
                if (it.qty) line += ` - ${it.qty}`;
                if (it.price) line += ` - ৳${it.price}`;
                return line;
            }).join('\n');
        }

        function parseListItems(rawText, imageUrls) {
            if (imageUrls && Array.isArray(imageUrls) && imageUrls.length > 0) {
                return { type: 'images', items: imageUrls };
            }

            if (!rawText) {
                return { type: 'rows', items: [] };
            }

            const items = extractListItemsFromContent(rawText);
            const mapped = items.map((it, idx) => ({
                sl: idx + 1,
                item: it.item || 'Item',
                spec: it.spec || '',
                qty: it.qty || '1',
                price: it.price || '',
                total: it.total || (it.price && it.qty ? (parseFloat(it.price) * parseInt(it.qty)) : '')
            }));

            return { type: 'rows', items: mapped };
        }

        function generateSingleA4PageHtml({ name, phone, location, orderNo, dateTime, contentHtml, pageIndex = 0, totalPages = 1, footerExtra = '' }) {
            const isFirstPage = (pageIndex === 0);
            const isLastPage = (pageIndex === totalPages - 1);

            let headerBlock = '';
            if (isFirstPage) {
                headerBlock = `
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

                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;">
                        <div style="max-width: 58%; text-align: left;">
                            <div><strong style="color: #0f172a;">Name:</strong> <span style="color: #334155; font-weight: 600;">${name}</span></div>
                            <div><strong style="color: #0f172a;">Phone:</strong> <span style="color: #334155; font-weight: 600;">${phone}</span></div>
                            <div><strong style="color: #0f172a;">Location:</strong> <span style="color: #334155; font-weight: 600;">${location}</span></div>
                        </div>
                        <div style="text-align: right;">
                            <div><strong style="color: #0f172a;">${orderNo.startsWith('LST-') ? 'Document:' : 'Order Type:'}</strong> <span style="color: #00c675; font-weight: 800;">${orderNo.startsWith('LST-') ? 'Custom Grocery List' : (orderNo.startsWith('ORD-') ? 'Grocery List Order' : 'Official Invoice')}</span></div>
                            <div><strong style="color: #0f172a;">${orderNo.startsWith('LST-') ? 'List Code:' : (orderNo.startsWith('ORD-') ? 'Order No:' : 'Invoice No:')}</strong> <span style="color: #00c675; font-weight: 800;">${orderNo}</span></div>
                            <div><strong style="color: #0f172a;">Date & Time:</strong> <span style="color: #334155; font-weight: 600;">${dateTime}</span></div>
                            <div><strong style="color: #0f172a;">Page:</strong> <span style="color: #334155; font-weight: 600;">${pageIndex + 1} of ${totalPages}</span></div>
                        </div>
                    </div>
                `;
            } else {
                const labelStr = orderNo.startsWith('LST-') ? 'List Code:' : (orderNo.startsWith('ORD-') ? 'Order No:' : 'Invoice No:');
                headerBlock = `
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 22px;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 52px; width: auto; object-fit: contain;">
                        <div style="font-size: 13px; color: #0f172a; text-align: right;">
                            <strong>${labelStr}</strong> <span style="font-weight: 800; color: #00c675;">${orderNo}</span> &nbsp;|&nbsp; 
                            <strong>Page:</strong> <span style="font-weight: 700; color: #334155;">${pageIndex + 1} of ${totalPages}</span>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="a4-page" style="background: white; width: 100%; max-width: 800px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: 'Outfit', sans-serif; overflow: hidden; align-self: stretch;">
                    
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;">
                        <img src="assets/bazarkinundotcom_logo.png" alt="Watermark" style="width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="Watermark Logo 2" style="width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;">
                    </div>

                    <div style="position: relative; z-index: 2; flex: 1;">
                        ${headerBlock}

                        <div style="position: relative; margin-bottom: 20px; z-index: 2;">
                            ${contentHtml}
                        </div>
                    </div>

                    <div style="border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 55px; width: auto; object-fit: contain;">
                        </div>
                        ${(isLastPage && footerExtra) ? footerExtra : `
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;">24/7 Support Center</span>
                            <strong style="font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;">01919-668960</strong>
                        </div>`}
                    </div>
                </div>
            `;
        }

        function generateA4PagesHtml({ name, phone, location, orderNo, dateTime, parsedData, footerExtra = '' }) {
            if (!parsedData || !parsedData.items || parsedData.items.length === 0) {
                return generateSingleA4PageHtml({
                    name, phone, location, orderNo, dateTime,
                    contentHtml: '<p style="text-align: center; color: #64748b; padding: 40px; font-size: 14px; background: #f8fafc; border-radius: 8px;">No items found in this list.</p>',
                    pageIndex: 0,
                    totalPages: 1,
                    footerExtra
                });
            }

            if (parsedData.type === 'images') {
                const images = parsedData.items;
                const totalPages = images.length || 1;
                let fullHtml = '<div style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">';

                for (let p = 0; p < totalPages; p++) {
                    const img = images[p];
                    const isFirst = (p === 0);
                    const maxImgHeight = isFirst ? '640px' : '820px';
                    let grid = `
                        <div style="display: flex; justify-content: center; align-items: center; margin: 15px 0; position: relative; z-index: 2; width: 100%; flex: 1;">
                            <div style="border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fafafa; width: 100%; display: flex; justify-content: center; align-items: center; padding: 12px; box-sizing: border-box; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                <img src="${img}" alt="Grocery List Photo ${p + 1}" style="max-width: 100%; max-height: ${maxImgHeight}; width: auto; height: auto; object-fit: contain; display: block; border-radius: 6px;">
                            </div>
                        </div>
                    `;

                    fullHtml += generateSingleA4PageHtml({
                        name, phone, location, orderNo, dateTime,
                        contentHtml: grid,
                        pageIndex: p,
                        totalPages,
                        footerExtra: (p === totalPages - 1) ? footerExtra : ''
                    });
                }

                fullHtml += '</div>';
                return fullHtml;
            }

            const items = parsedData.items;
            const pageChunks = [];
            let idx = 0;

            while (idx < items.length) {
                const limit = (pageChunks.length === 0) ? 14 : 20;
                pageChunks.push(items.slice(idx, idx + limit));
                idx += limit;
            }

            const totalPages = pageChunks.length;
            const hasPriceCol = items.some(it => it.price || it.total);

            let fullHtml = '<div style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">';

            pageChunks.forEach((chunk, pageIndex) => {
                let rowsHtml = '';
                chunk.forEach(row => {
                    const specHtml = row.spec ? ` <span style="color: #64748b; font-weight: 500;">(${row.spec})</span>` : '';
                    rowsHtml += `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700; text-align: center;">${row.sl}</td>
                            <td style="padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700;">${row.item}${specHtml}</td>
                            ${hasPriceCol ? `<td style="padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13.5px; font-weight: 700;">${row.price ? '৳' + row.price : '-'}</td>` : ''}
                            <td style="padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13.5px; font-weight: 800;">${row.qty || '1'}</td>
                            ${hasPriceCol ? `<td style="padding: 10px 8px; color: #059669; text-align: right; font-size: 13.5px; font-weight: 800;">${row.total ? '৳' + row.total : '-'}</td>` : ''}
                        </tr>
                    `;
                });

                const tableHtml = `
                    <table style="width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2;">
                        <thead>
                            <tr style="background: #f8fafc; border-top: 1.5px solid #e2e8f0; border-bottom: 1.5px solid #e2e8f0;">
                                <th style="padding: 11px 8px; color: #475569; font-size: 12.5px; font-weight: 800; width: 45px; text-align: center;">SL</th>
                                <th style="padding: 11px 8px; color: #475569; font-size: 12.5px; font-weight: 800;">Item Description</th>
                                ${hasPriceCol ? '<th style="padding: 11px 8px; color: #475569; text-align: center; font-size: 12.5px; font-weight: 800; width: 85px;">Price</th>' : ''}
                                <th style="padding: 11px 8px; color: #475569; text-align: center; font-size: 12.5px; font-weight: 800; width: 95px;">Quantity</th>
                                ${hasPriceCol ? '<th style="padding: 11px 8px; color: #475569; text-align: right; font-size: 12.5px; font-weight: 800; width: 95px;">Total</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                `;

                fullHtml += generateSingleA4PageHtml({
                    name, phone, location, orderNo, dateTime,
                    contentHtml: tableHtml,
                    pageIndex,
                    totalPages,
                    footerExtra: (pageIndex === totalPages - 1) ? footerExtra : ''
                });
            });

            fullHtml += '</div>';
            return fullHtml;
        }

        function openPreviewModal(item, type) {
            document.getElementById('preview-modal').style.display = 'flex';
            const contentArea = document.getElementById('preview-content');
            let contentHtml = '';

            const itemCode = item.id || item.list_code || item.order_number || item.invoice_number || 'DOC-1';
            const custName = item.customer_name || 'Customer';
            const custPhone = item.customer_phone || '';
            const custAddress = item.customer_address || 'Uttara, Dhaka';
            const custDate = item.date || 'Today';

            // Check if uploaded photo list or order
            const hasImages = (item.image_urls && item.image_urls.length > 0) || !!item.image_url;
            const isUploaded = (item.list_type === 'uploaded' || item.order_type === 'uploaded') || (hasImages && item.list_type !== 'written' && item.order_type !== 'text_list');

            if (isUploaded) {
                let imgArr = [];
                if (item.image_urls && Array.isArray(item.image_urls) && item.image_urls.length > 0) {
                    imgArr = item.image_urls;
                } else if (item.image_url) {
                    imgArr = [item.image_url];
                }
                
                const parsedData = { type: 'images', items: imgArr };
                contentHtml = generateA4PagesHtml({
                    name: custName,
                    phone: custPhone,
                    location: custAddress,
                    orderNo: itemCode,
                    dateTime: custDate,
                    parsedData: parsedData
                });
            } else if (type === 'list') {
                // Written custom list
                const rawText = item.content_text || item.htmlContent || item.items_summary || '';
                const parsedData = parseListItems(rawText, null);
                
                contentHtml = generateA4PagesHtml({
                    name: custName,
                    phone: custPhone,
                    location: custAddress,
                    orderNo: itemCode,
                    dateTime: custDate,
                    parsedData: parsedData
                });
            } else {
                // Order / Invoice
                let rawHtml = item.htmlContent || item.items_summary || '';
                if (rawHtml && (rawHtml.includes('class="a4-page"') || rawHtml.includes('a4-page') || rawHtml.includes('Watermark') || rawHtml.includes('bazarkinundotcom_logo'))) {
                    if (itemCode) {
                        rawHtml = rawHtml.replace(/LST-\d+-[A-Z0-9]+-\d+/gi, itemCode);
                        rawHtml = rawHtml.replace(/ORD-\d+-[A-Z0-9]+-\d+/gi, itemCode);
                        rawHtml = rawHtml.replace(/INV-\d+-[A-Z0-9]+-\d+/gi, itemCode);
                    }
                    contentHtml = rawHtml;
                } else {
                    const rawText = item.content_text || item.htmlContent || item.items_summary || '';
                    const parsedData = parseListItems(rawText, null);
                    
                    let footerExtra = '';
                    if (item.total_amount && parseFloat(item.total_amount) > 0) {
                        footerExtra = `
                            <div style="text-align: right;">
                                <span style="font-size: 13px; color: #64748b; font-weight: 700;">Total: </span>
                                <strong style="font-size: 20px; font-weight: 900; color: #00c675;">৳${item.total_amount}</strong>
                            </div>
                        `;
                    }

                    contentHtml = generateA4PagesHtml({
                        name: custName,
                        phone: custPhone,
                        location: custAddress,
                        orderNo: itemCode,
                        dateTime: custDate,
                        parsedData: parsedData,
                        footerExtra: footerExtra
                    });
                }
            }

            let actionBtnHtml = `
            <div class="no-print" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); width: 100%; max-width: 800px; box-sizing: border-box; margin: 12px auto 0 auto;">
                <button type="button" onclick="closePreviewModal(); openAdminEditModal(findCustomerItem('${item.id}', '${type}'), '${type}');" style="background: #ffffff; border: 1.5px solid #10b981; color: #059669; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1); transition: all 0.2s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#ffffff'">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>
                <button type="button" onclick="closePreviewModal()" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'">
                    Close
                </button>
                <button type="button" onclick="window.print()" style="background: #00c675; border: none; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;" onmouseover="this.style.background='#00b368'" onmouseout="this.style.background='#00c675'">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>`;

            contentArea.innerHTML = contentHtml + actionBtnHtml;
        }

        function closePreviewModal() {
            document.getElementById('preview-modal').style.display = 'none';
        }

        function extractItemsFromHtmlOrObject(item) {
            if (item.items && Array.isArray(item.items) && item.items.length > 0) {
                return item.items.map(it => ({
                    name: it.name || it.product_name || 'Item',
                    spec: it.poriman || it.product_poriman || it.spec || '',
                    price: parseFloat(it.price || it.unit_price || 0),
                    qty: parseInt(it.quantity || it.qty || 1)
                }));
            }

            const items = [];
            if (item.htmlContent) {
                try {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(item.htmlContent, 'text/html');
                    const rows = doc.querySelectorAll('tbody tr');
                    rows.forEach(tr => {
                        const cols = tr.querySelectorAll('td');
                        if (cols.length >= 4) {
                            let rawName = cols[1].innerText.trim();
                            let spec = '';
                            const match = rawName.match(/^(.*?)\s*\((.*?)\)$/);
                            if (match) {
                                rawName = match[1].trim();
                                spec = match[2].trim();
                            }
                            let priceText = cols[2].innerText.replace(/[^0-9.]/g, '');
                            let price = parseFloat(priceText) || 0;

                            let qtyText = cols[3].innerText.replace(/[^0-9]/g, '');
                            let qty = parseInt(qtyText) || 1;

                            items.push({ name: rawName, spec, price, qty });
                        }
                    });
                } catch(e) {
                    console.error("Error parsing items from htmlContent:", e);
                }
            }

            if (items.length === 0 && item.total_amount && parseFloat(item.total_amount) > 0) {
                items.push({
                    name: 'Order Item',
                    spec: '',
                    price: parseFloat(item.total_amount),
                    qty: 1
                });
            }

            return items;
        }

        let currentEditListImages = [];

        function renderEditListPhotosGrid() {
            const grid = document.getElementById('edit-list-photos-grid');
            const countBadge = document.getElementById('edit-list-photos-count-badge');
            if (countBadge) {
                countBadge.innerText = `${currentEditListImages.length} photo${currentEditListImages.length === 1 ? '' : 's'}`;
            }
            if (!grid) return;
            grid.innerHTML = '';

            if (currentEditListImages.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; padding: 20px 16px; border: 2px dashed #cbd5e1; border-radius: 10px; text-align: center; background: white; color: #64748b;">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 26px; color: #94a3b8; margin-bottom: 6px; display: block;"></i>
                        <span style="font-size: 13px; font-weight: 700; color: #475569; display: block;">No photos attached yet</span>
                        <span style="font-size: 11.5px; color: #94a3b8; margin-top: 2px; display: block;">Click "Upload / Add Photos" above or browse to add grocery list pictures.</span>
                    </div>
                `;
                return;
            }

            currentEditListImages.forEach((imgUrl, idx) => {
                const card = document.createElement('div');
                card.style.cssText = `
                    background: white;
                    border: 1.5px solid #e2e8f0;
                    border-radius: 10px;
                    overflow: hidden;
                    position: relative;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                    display: flex;
                    flex-direction: column;
                    transition: all 0.2s ease;
                `;
                card.innerHTML = `
                    <div style="position: relative; width: 100%; height: 135px; background: #0f172a; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <img src="${imgUrl}" alt="Photo ${idx + 1}" style="width: 100%; height: 100%; object-fit: contain; cursor: pointer;" onclick="window.open('${imgUrl}', '_blank')" title="Click to view full size">
                        <span style="position: absolute; top: 6px; left: 6px; background: rgba(15, 23, 42, 0.75); color: white; padding: 2px 7px; border-radius: 6px; font-size: 10.5px; font-weight: 700; backdrop-filter: blur(4px);">
                            #${idx + 1}
                        </span>
                    </div>
                    <div style="padding: 8px 10px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <button type="button" onclick="window.open('${imgUrl}', '_blank')" style="background: white; border: 1px solid #cbd5e1; color: #475569; padding: 4px 8px; border-radius: 5px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-up-right-from-square" style="font-size: 10px;"></i> View
                        </button>
                        <button type="button" onclick="removeAdminListPhoto(${idx})" style="background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626; padding: 4px 8px; border-radius: 5px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Remove this photo">
                            <i class="fa-solid fa-trash-can" style="font-size: 10px;"></i> Delete
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function removeAdminListPhoto(idx) {
            if (idx >= 0 && idx < currentEditListImages.length) {
                currentEditListImages.splice(idx, 1);
                renderEditListPhotosGrid();
                showToast('Photo removed from list.', 'warning', 2000);
            }
        }

        function handleAdminListImageUpload(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            let loadedCount = 0;
            const validFiles = Array.from(files).filter(f => f.type.startsWith('image/'));

            if (validFiles.length === 0) {
                showToast('Please select valid image files (JPG, PNG, WEBP).', 'warning');
                return;
            }

            validFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentEditListImages.push(e.target.result);
                    loadedCount++;
                    if (loadedCount === validFiles.length) {
                        renderEditListPhotosGrid();
                        showToast(`${validFiles.length} photo(s) added successfully!`, 'success', 2200);
                    }
                };
                reader.readAsDataURL(file);
            });

            event.target.value = '';
        }

        function handleListTypeChange() {
            const listType = document.getElementById('edit-list-type').value;
            const itemsContainer = document.getElementById('edit-list-items-container');
            const imgContainer = document.getElementById('edit-list-image-container');
            if (listType === 'uploaded') {
                if (itemsContainer) itemsContainer.style.display = 'none';
                if (imgContainer) imgContainer.style.display = 'block';
                renderEditListPhotosGrid();
            } else {
                if (itemsContainer) itemsContainer.style.display = 'block';
                if (imgContainer) imgContainer.style.display = 'none';
                
                const tbody = document.getElementById('edit-custom-list-tbody');
                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                    addEditCustomListRow('', '', '1');
                }
            }
        }

        function addEditCustomListRow(item = '', spec = '', qty = '1') {
            const tbody = document.getElementById('edit-custom-list-tbody');
            if (!tbody) return;
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #e2e8f0';

            tr.innerHTML = `
                <td style="padding: 10px 8px; text-align: center; color: #94a3b8; font-weight: 700; font-size: 12.5px;" class="custom-list-sl">1</td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="cl-item-name" value="${escapeHtml(item)}" oninput="checkCustomListRow(this)" placeholder="Miniket Rice / আলু" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: inherit; box-sizing: border-box;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="cl-item-spec" value="${escapeHtml(spec)}" placeholder="5kg Bag or ACI Pure" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: inherit; box-sizing: border-box;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="cl-item-qty" value="${escapeHtml(qty || '1')}" placeholder="1 Bag / 2 kg" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: inherit; text-align: center; box-sizing: border-box; font-weight: 600;">
                </td>
                <td style="padding: 8px 6px; text-align: center;">
                    <button type="button" onclick="removeEditCustomListRow(this)" style="background: #fee2e2; border: none; color: #ef4444; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s;" title="Remove row">
                        <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            updateCustomListSl();
        }

        function checkCustomListRow(input) {
            const row = input.closest('tr');
            const tbody = document.getElementById('edit-custom-list-tbody');
            if (row === tbody.lastElementChild && input.value.trim() !== '') {
                addEditCustomListRow('', '', '1');
            }
        }

        function removeEditCustomListRow(btn) {
            const tbody = document.getElementById('edit-custom-list-tbody');
            const rows = tbody.querySelectorAll('tr');
            if (rows.length <= 1) {
                const tr = rows[0];
                tr.querySelector('.cl-item-name').value = '';
                tr.querySelector('.cl-item-spec').value = '';
                tr.querySelector('.cl-item-qty').value = '1';
                return;
            }
            btn.closest('tr').remove();
            updateCustomListSl();
        }

        function updateCustomListSl() {
            const rows = document.querySelectorAll('#edit-custom-list-tbody tr');
            rows.forEach((tr, idx) => {
                const sl = tr.querySelector('.custom-list-sl');
                if (sl) sl.innerText = idx + 1;
            });
        }

        function openAdminEditModal(item, type) {
            if (!item) return;
            document.getElementById('admin-edit-modal').style.display = 'flex';
            
            document.getElementById('edit-item-id').value = item.id || '';
            document.getElementById('edit-item-db-id').value = item.db_id || 0;
            document.getElementById('edit-item-type').value = type;

            document.getElementById('edit-cust-name').value = item.customer_name || '';
            document.getElementById('edit-cust-phone').value = item.customer_phone || '';
            document.getElementById('edit-cust-address').value = item.customer_address || '';

            const statusSelect = document.getElementById('edit-doc-status');
            const currentStatus = (item.status || 'Pending').toLowerCase();
            statusSelect.value = ['pending', 'processing', 'delivering', 'completed', 'cancelled', 'draft', 'paid', 'accepted'].includes(currentStatus) ? currentStatus : 'pending';

            const title = document.getElementById('edit-modal-title');
            const subtitle = document.getElementById('edit-modal-subtitle');
            const orderSection = document.getElementById('edit-order-section');
            const listSection = document.getElementById('edit-list-section');
            const typeLabel = document.getElementById('edit-list-type-label');

            if (type === 'list' || type === 'order') {
                const isOrder = (type === 'order');
                if (typeLabel) typeLabel.innerText = isOrder ? 'Order Type' : 'List Type';

                title.innerHTML = isOrder 
                    ? `<i class="fa-solid fa-cart-shopping" style="color: #00c675;"></i> Edit Order #${item.id}`
                    : `<i class="fa-solid fa-list-check" style="color: #3b82f6;"></i> Edit Custom List #${item.id}`;
                subtitle.innerText = isOrder 
                    ? 'Update grocery items, customer details, photos, and order status'
                    : 'Update list content, customer details, photos, and processing status';

                orderSection.style.display = 'none';
                listSection.style.display = 'block';

                // Collect attached images
                currentEditListImages = [];
                if (item.image_urls && Array.isArray(item.image_urls) && item.image_urls.length > 0) {
                    currentEditListImages = [...item.image_urls];
                } else if (item.image_url) {
                    currentEditListImages = [item.image_url];
                } else if (item.images) {
                    let parsed = item.images;
                    if (typeof item.images === 'string') {
                        try { parsed = JSON.parse(item.images); } catch(e) { parsed = [item.images]; }
                    }
                    if (Array.isArray(parsed)) currentEditListImages = [...parsed];
                    else if (typeof parsed === 'string' && parsed) currentEditListImages = [parsed];
                }

                // If images still empty but htmlContent has <img> tags (excluding logos)
                if (currentEditListImages.length === 0 && item.htmlContent) {
                    const temp = document.createElement('div');
                    temp.innerHTML = item.htmlContent;
                    const imgs = temp.querySelectorAll('img');
                    imgs.forEach(img => {
                        const src = img.getAttribute('src');
                        if (src && !src.includes('bazarkinun') && !src.includes('logo')) {
                            currentEditListImages.push(src);
                        }
                    });
                }

                renderEditListPhotosGrid();

                const isUploaded = (item.list_type === 'uploaded' || item.order_type === 'uploaded') || (currentEditListImages.length > 0 && item.list_type !== 'written' && item.order_type !== 'text_list');
                document.getElementById('edit-list-type').value = isUploaded ? 'uploaded' : 'written';
                handleListTypeChange();

                const tbody = document.getElementById('edit-custom-list-tbody');
                tbody.innerHTML = '';

                if (isUploaded) {
                    // For uploaded items, start written items table with a clean fresh row
                    addEditCustomListRow('', '', '1');
                } else {
                    let itemsLoaded = false;
                    // Check if item.items has array from order_items
                    if (item.items && Array.isArray(item.items) && item.items.length > 0) {
                        item.items.forEach(it => {
                            let name = (it.name || it.product_name || it.item || '').trim();
                            let spec = (it.poriman || it.product_poriman || it.spec || '').trim();
                            let qty = (it.quantity || it.qty || '1').toString();
                            
                            if (!spec && name) {
                                const m = name.match(/^(.*?)\s*\((.*?)\)$/);
                                if (m) {
                                    name = m[1].trim();
                                    spec = m[2].trim();
                                }
                            }
                            if (name) {
                                addEditCustomListRow(name, spec, qty);
                                itemsLoaded = true;
                            }
                        });
                    }

                    if (!itemsLoaded) {
                        const rawContent = item.content_text || item.htmlContent || item.items_summary || '';
                        const items = extractListItemsFromContent(rawContent);

                        if (items && items.length > 0) {
                            items.forEach(it => {
                                addEditCustomListRow(it.item, it.spec, it.qty || '1');
                            });
                        } else {
                            addEditCustomListRow('', '', '1');
                        }
                    }
                }
            } else {
                // Invoice edit mode
                const isInvoice = type === 'invoice';
                title.innerHTML = `<i class="fa-solid fa-file-invoice-dollar" style="color: #00c675;"></i> Edit ${isInvoice ? 'Invoice' : 'Order'} #${item.id}`;
                subtitle.innerText = `Edit customer information, items, pricing, delivery charge, and status`;
                orderSection.style.display = 'block';
                listSection.style.display = 'none';

                const tbody = document.getElementById('edit-items-tbody');
                tbody.innerHTML = '';

                const items = extractItemsFromHtmlOrObject(item);
                if (items.length === 0) {
                    addEditItemRow('', '', 0, 1);
                } else {
                    items.forEach(it => {
                        addEditItemRow(it.name, it.spec, it.price, it.qty);
                    });
                }

                const couponInput = document.getElementById('edit-coupon-code');
                if (couponInput) couponInput.value = item.coupon_code || '';

                document.getElementById('edit-delivery-charge').value = parseFloat(item.delivery_charge || 0);
                document.getElementById('edit-discount-amount').value = parseFloat(item.discount_amount || 0);
                recalcEditTotal(true);
            }
        }

        function closeAdminEditModal() {
            document.getElementById('admin-edit-modal').style.display = 'none';
        }

        // --- CATALOG PRODUCT PICKER MODAL FUNCTIONS ---
        let currentCatalogCategory = 'all';

        function openProductCatalogModal() {
            const modal = document.getElementById('catalog-product-modal');
            if (!modal) return;
            modal.style.display = 'flex';
            currentCatalogCategory = 'all';
            const searchInput = document.getElementById('catalog-search-input');
            if (searchInput) {
                const isBn = (localStorage.getItem('appLanguage') === 'bn');
                searchInput.placeholder = isBn ? 'পণ্য খুঁজুন' : 'Search product';
                searchInput.value = '';
            }
            renderCatalogCategories();
            renderCatalogProducts(catalogProducts);
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

            catalogCategories.forEach(c => {
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
            let filtered = catalogProducts;

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
                const spec = p.poriman || p.poriman_bn || p.brand || '';
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
                            <img src="${img}" alt="${escapeHtml(title)}" style="max-width: 100%; max-height: 100px; object-fit: contain;">
                        </div>
                        <h4 style="margin: 0 0 4px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; line-height: 1.3; min-height: 36px;">${escapeHtml(title)}</h4>
                        <p style="margin: 0 0 10px 0; font-size: 12px; color: #64748b; font-weight: 600;">${escapeHtml(spec || 'Standard')}</p>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                        <div style="display: flex; align-items: baseline; gap: 6px;">
                            <span style="font-size: 16px; font-weight: 900; color: #00c675;">৳${price.toFixed(0)}</span>
                            ${oldPrice > price ? `<span style="font-size: 12px; color: #94a3b8; text-decoration: line-through;">৳${oldPrice.toFixed(0)}</span>` : ''}
                        </div>
                        <button type="button" onclick="addCatalogProductToOrder(${p.id}, this)" style="background: white; border: 1.5px solid #00c675; color: #00c675; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;" onmouseover="this.style.background='#00c675'; this.style.color='#fff'" onmouseout="this.style.background='white'; this.style.color='#00c675'">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function addCatalogProductToOrder(prodId, btn = null) {
            const prod = catalogProducts.find(p => p.id === prodId);
            if (!prod) return;

            const name = prod.title || prod.title_bn || 'Item';
            const spec = prod.poriman || prod.poriman_bn || prod.brand || '';
            const price = parseFloat(prod.price || 0);

            const tbody = document.getElementById('edit-items-tbody');
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
                    if (!firstName) {
                        existingRows[0].remove();
                    }
                }
                addEditItemRow(name, spec, price, 1);
            }

            recalcEditTotal(true);
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
            showToast(`Added '${name}' to order!`, 'success', 1600);
        }

        function updateCatalogItemCount() {
            const rows = document.querySelectorAll('#edit-items-tbody tr');
            let count = 0;
            rows.forEach(tr => {
                const name = tr.querySelector('.item-name-input')?.value.trim();
                const qty = parseInt(tr.querySelector('.item-qty-input')?.value) || 1;
                if (name) count += qty;
            });
            const badge = document.getElementById('catalog-items-count-badge');
            if (badge) badge.innerText = `Total items in order: ${count}`;
        }

        function matchItemToCatalog(name, spec = '') {
            if (!name || !catalogProducts || catalogProducts.length === 0) return null;
            const cleanName = name.trim().toLowerCase();
            let match = catalogProducts.find(p => (p.title && p.title.toLowerCase() === cleanName) || (p.title_bn && p.title_bn.toLowerCase() === cleanName));
            if (match) return match;

            match = catalogProducts.find(p => {
                const t = (p.title || '').toLowerCase();
                const tb = (p.title_bn || '').toLowerCase();
                return (t && (cleanName.includes(t) || t.includes(cleanName))) || (tb && (cleanName.includes(tb) || tb.includes(cleanName)));
            });
            return match || null;
        }

        function addFreeGiftItemToOrder(giftProdId, giftTitle, giftQty = 1) {
            const prod = catalogProducts.find(p => p.id == giftProdId);
            let name = giftTitle || (prod ? (prod.title || prod.title_bn) : 'Green Chilli (Kacha Morich)');
            name = name.replace(/^\[.*?\]\s*/, '').replace(/\s*🎁.*$/, '').trim();
            const spec = prod ? (prod.poriman || prod.poriman_bn || '') : '';
            
            const tbody = document.getElementById('edit-items-tbody');
            let exists = false;
            tbody.querySelectorAll('tr').forEach(tr => {
                const nameInp = tr.querySelector('.item-name-input');
                const priceInp = tr.querySelector('.item-price-input');
                if (nameInp && priceInp && parseFloat(priceInp.value) === 0 && nameInp.value.toLowerCase().includes(name.toLowerCase())) {
                    const qtyInp = tr.querySelector('.item-qty-input');
                    if (qtyInp) qtyInp.value = giftQty;
                    exists = true;
                }
            });

            if (!exists) {
                addEditItemRow(`[ 🎁 🎁 কম্বো ফ্রি গিফট] ${name}`, spec, 0, giftQty);
            }
            recalcEditTotal(true);
            showToast(`Free Gift '${name}' added to invoice items!`, 'success');
        }

        function applyEditCoupon() {
            const codeInput = document.getElementById('edit-coupon-code');
            const code = (codeInput ? codeInput.value : '').trim().toUpperCase();
            if (!code) {
                showToast('Please enter a coupon code.', 'warning');
                return;
            }
            recalcEditTotal(true);
            showToast(`Coupon code '${code}' evaluated!`, 'success', 2000);
        }

        function autoSetDeliveryCharge() {
            recalcEditTotal(true);
            const delVal = document.getElementById('edit-delivery-charge')?.value || '0';
            showToast(`Delivery charge recalculated: ৳${delVal}`, 'success', 2500);
        }

        function addEditItemRow(name = '', spec = '', price = 0, qty = 1) {
            const tbody = document.getElementById('edit-items-tbody');
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #e2e8f0';

            tr.innerHTML = `
                <td style="padding: 10px 8px; text-align: center; color: #94a3b8; font-weight: 700; font-size: 12.5px;" class="row-sl">1</td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="item-name-input" value="${escapeHtml(name)}" oninput="recalcEditTotal(true)" placeholder="Item title (e.g. Potato / আলু)" style="width: 100%; padding: 7px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: inherit; box-sizing: border-box;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="text" class="item-spec-input" value="${escapeHtml(spec)}" placeholder="1 kg" style="width: 100%; padding: 7px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-family: inherit; box-sizing: border-box;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="number" class="item-price-input" value="${price}" min="0" step="0.5" oninput="recalcEditTotal(true)" style="width: 100%; padding: 7px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: inherit; text-align: right; box-sizing: border-box; font-weight: 600;">
                </td>
                <td style="padding: 8px 10px;">
                    <input type="number" class="item-qty-input" value="${qty}" min="1" step="1" oninput="recalcEditTotal(true)" style="width: 100%; padding: 7px 8px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: inherit; text-align: center; box-sizing: border-box; font-weight: 700;">
                </td>
                <td style="padding: 8px 12px; text-align: right; font-weight: 800; color: #059669; font-size: 13.5px;" class="item-row-total">
                    ৳${(price * qty).toFixed(2)}
                </td>
                <td style="padding: 8px 6px; text-align: center;">
                    <button type="button" onclick="removeEditRow(this)" style="background: #fee2e2; border: none; color: #ef4444; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            recalcEditTotal(true);
        }

        function removeEditRow(btn) {
            const tbody = document.getElementById('edit-items-tbody');
            if (tbody.querySelectorAll('tr').length <= 1) {
                showToast('An order must have at least one item.', 'warning');
                return;
            }
            btn.closest('tr').remove();
            recalcEditTotal(true);
        }

        function evaluateOrderOffersAndDelivery(autoDel = true) {
            const rows = document.querySelectorAll('#edit-items-tbody tr');
            let subtotal = 0;
            const cartItemsMeta = [];

            rows.forEach((tr, idx) => {
                const slTd = tr.querySelector('.row-sl');
                if (slTd) slTd.innerText = idx + 1;

                const nameInput = tr.querySelector('.item-name-input');
                const specInput = tr.querySelector('.item-spec-input');
                const priceInput = tr.querySelector('.item-price-input');
                const qtyInput = tr.querySelector('.item-qty-input');
                const rowTotalTd = tr.querySelector('.item-row-total');

                const name = (nameInput ? nameInput.value : '').trim();
                const spec = (specInput ? specInput.value : '').trim();
                const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                const rowTotal = price * qty;
                subtotal += rowTotal;

                if (rowTotalTd) {
                    rowTotalTd.innerText = '৳' + rowTotal.toFixed(2);
                }

                if (name) {
                    const matchedProd = matchItemToCatalog(name, spec);
                    cartItemsMeta.push({
                        prodId: matchedProd ? matchedProd.id : null,
                        catId: matchedProd ? matchedProd.category_id : null,
                        name: name,
                        spec: spec,
                        price: price,
                        qty: qty,
                        is_free_gift: (price === 0)
                    });
                }
            });

            // 1. Delivery Charge Calculation (Based on Store Delivery Rules & Subtotal)
            let calcDelivery = subtotal >= 1000 ? 0 : (subtotal > 0 ? 60 : 0);
            if (storeDeliveryRules && storeDeliveryRules.length > 0) {
                for (const rule of storeDeliveryRules) {
                    if (rule.is_active == 1 && (rule.target_type === 'website' || rule.target_type === 'all')) {
                        if (rule.based_on === 'order_amount') {
                            const minVal = parseFloat(rule.min_value) || 0;
                            const maxVal = parseFloat(rule.max_value) || 0;
                            if (rule.operator === 'greater_than_or_equal' && subtotal >= minVal) {
                                calcDelivery = (rule.calculation_method === 'free') ? 0 : (parseFloat(rule.fixed_amount || rule.charge) || 0);
                                break;
                            } else if (rule.operator === 'greater_than' && subtotal > minVal) {
                                calcDelivery = (rule.calculation_method === 'free') ? 0 : (parseFloat(rule.fixed_amount || rule.charge) || 0);
                                break;
                            } else if (rule.operator === 'less_than' && maxVal > 0 && subtotal < maxVal) {
                                calcDelivery = parseFloat(rule.fixed_amount || rule.charge || 60);
                                break;
                            }
                        }
                    }
                }
            }

            if (autoDel) {
                const delInput = document.getElementById('edit-delivery-charge');
                if (delInput) delInput.value = calcDelivery;
            }

            // 2. Active Discounts & Combo Offers Evaluation
            const activeOffers = [];
            let calculatedDiscounts = 0;
            const appliedCoupon = (document.getElementById('edit-coupon-code')?.value || '').trim().toUpperCase();

            if (storeDiscountRules && storeDiscountRules.length > 0) {
                storeDiscountRules.forEach(rule => {
                    if (!rule.is_active || rule.is_active == 0) return;
                    const minCart = parseFloat(rule.min_cart_amount) || 0;

                    // A. Combo Bundle Offers (e.g. Potato + Onion -> Free Gift / Discount)
                    if (rule.discount_type === 'combo_bundle' || rule.target_type === 'multi_products') {
                        let requiredIds = [];
                        if (rule.target_ids_json) {
                            try {
                                requiredIds = typeof rule.target_ids_json === 'string' ? JSON.parse(rule.target_ids_json) : rule.target_ids_json;
                            } catch(e) { requiredIds = []; }
                        }
                        if ((!requiredIds || requiredIds.length === 0) && rule.target_id) {
                            requiredIds = [parseInt(rule.target_id)];
                        }

                        if (Array.isArray(requiredIds) && requiredIds.length > 0) {
                            let maxBundles = Infinity;
                            let allPresent = true;

                            requiredIds.forEach(reqItem => {
                                const reqId = typeof reqItem === 'object' ? reqItem.id : reqItem;
                                const reqQty = (typeof reqItem === 'object' && reqItem.qty) ? parseInt(reqItem.qty) : (parseInt(rule.min_qty_required) || 1);

                                const matchItem = cartItemsMeta.find(c => (c.prodId == reqId));
                                const cartQty = matchItem ? parseInt(matchItem.qty) : 0;

                                if (cartQty < reqQty) {
                                    allPresent = false;
                                } else {
                                    const bundlesForThisItem = Math.floor(cartQty / reqQty);
                                    if (bundlesForThisItem < maxBundles) {
                                        maxBundles = bundlesForThisItem;
                                    }
                                }
                            });

                            const bundleMultiplier = allPresent && maxBundles > 0 && isFinite(maxBundles) ? maxBundles : 0;

                            if (bundleMultiplier > 0) {
                                const rewardProd = catalogProducts.find(p => p.id == rule.reward_product_id);
                                const rewardTitle = (rewardProd ? (rewardProd.title || rewardProd.title_bn) : (rule.reward_product_title || 'Free Gift'));
                                const totalGiftQty = bundleMultiplier * (parseInt(rule.reward_qty) || 1);

                                if (rule.reward_type === 'free_gift' || rule.reward_product_id) {
                                    activeOffers.push({
                                        type: 'combo_gift',
                                        title: rule.title || rule.title_bn || 'Potato + Onion Combo Offer',
                                        badge: rule.badge_text || '🎁 কম্বো ফ্রি গিফট',
                                        giftProdId: rule.reward_product_id,
                                        giftName: rewardTitle,
                                        giftQty: totalGiftQty
                                    });
                                } else if (rule.reward_type === 'percentage') {
                                    const discVal = Math.round((subtotal * (parseFloat(rule.reward_value || rule.discount_value) || 0)) / 100);
                                    if (discVal > 0) {
                                        calculatedDiscounts += discVal;
                                        activeOffers.push({
                                            type: 'combo_disc',
                                            title: rule.title || 'Combo Discount',
                                            badge: rule.badge_text || '🎁 Combo Discount',
                                            amount: discVal
                                        });
                                    }
                                } else if (rule.reward_type === 'fixed_amount' || rule.discount_type === 'combo_bundle') {
                                    const discVal = (parseFloat(rule.reward_value || rule.discount_value) || 0) * bundleMultiplier;
                                    if (discVal > 0) {
                                        calculatedDiscounts += discVal;
                                        activeOffers.push({
                                            type: 'combo_disc',
                                            title: rule.title || 'Combo Discount',
                                            badge: rule.badge_text || '🎁 Combo Discount',
                                            amount: discVal
                                        });
                                    }
                                }
                            }
                        }
                    }

                    // B. Cart Spend Discounts (e.g. Spend ৳2000+ -> ৳100 Cashback)
                    else if (rule.discount_type === 'cart_spend') {
                        if (subtotal >= minCart) {
                            const discVal = parseFloat(rule.discount_value || 0);
                            if (discVal > 0) {
                                calculatedDiscounts += discVal;
                                activeOffers.push({
                                    type: 'cart_spend',
                                    title: rule.title || 'Cart Spend Offer',
                                    badge: rule.badge_text || '⚡ Spend Cashback',
                                    amount: discVal
                                });
                            }
                        }
                    }

                    // C. Coupon Code Rules (e.g. BAZAR5 -> 5% off)
                    else if (rule.discount_type === 'coupon' || (rule.coupon_code && rule.coupon_code.trim())) {
                        if (appliedCoupon && rule.coupon_code && rule.coupon_code.toUpperCase() === appliedCoupon) {
                            if (subtotal >= minCart) {
                                let cDisc = 0;
                                if (rule.discount_type === 'percentage' || rule.reward_type === 'discount_percent') {
                                    cDisc = (subtotal * (parseFloat(rule.discount_value || rule.reward_value) || 0)) / 100;
                                } else {
                                    cDisc = parseFloat(rule.discount_value || rule.reward_value || 0);
                                }
                                if (cDisc > 0) {
                                    calculatedDiscounts += cDisc;
                                    activeOffers.push({
                                        type: 'coupon',
                                        title: `Coupon '${appliedCoupon}'`,
                                        badge: `🏷️ ${rule.discount_value}% Off`,
                                        amount: cDisc
                                    });
                                }
                            } else {
                                activeOffers.push({
                                    type: 'coupon_min',
                                    title: `Coupon '${appliedCoupon}' requires min order ৳${minCart}`,
                                    badge: '⚠️ Min Spend Required'
                                });
                            }
                        }
                    }
                });
            }

            // Fallback for default BAZAR5 coupon if not in rules
            if (appliedCoupon === 'BAZAR5' && !activeOffers.some(o => o.type === 'coupon')) {
                if (subtotal >= 500) {
                    const cDisc = (subtotal * 5) / 100;
                    calculatedDiscounts += cDisc;
                    activeOffers.push({
                        type: 'coupon',
                        title: "Coupon 'BAZAR5'",
                        badge: "🏷️ 5% Off",
                        amount: cDisc
                    });
                } else {
                    activeOffers.push({
                        type: 'coupon_min',
                        title: "Coupon 'BAZAR5' requires min order ৳500",
                        badge: "⚠️ Min ৳500 Required"
                    });
                }
            }

            // Set discount input
            const discInput = document.getElementById('edit-discount-amount');
            if (discInput) {
                discInput.value = calculatedDiscounts.toFixed(2);
            }

            // Recalculate totals
            const delivery = parseFloat(document.getElementById('edit-delivery-charge')?.value || 0);
            const discount = parseFloat(document.getElementById('edit-discount-amount')?.value || 0);
            const grandTotal = Math.max(0, subtotal - discount) + delivery;

            document.getElementById('edit-summary-subtotal').innerText = '৳' + subtotal.toFixed(2);
            document.getElementById('edit-summary-grandtotal').innerText = '৳' + grandTotal.toFixed(2);
            updateCatalogItemCount();

            // 3. Render Active Offer Badges in Summary Box
            renderActiveOfferBadges(activeOffers, subtotal, delivery);
        }

        function renderActiveOfferBadges(activeOffers, subtotal, delivery) {
            const container = document.getElementById('edit-active-offers-container');
            if (!container) return;
            container.innerHTML = '';

            // Check if free delivery is unlocked
            if (subtotal >= 1000 && delivery === 0) {
                const fdDiv = document.createElement('div');
                fdDiv.style.cssText = `
                    background: #ecfdf5;
                    border: 1px solid #6ee7b7;
                    padding: 6px 10px;
                    border-radius: 6px;
                    font-size: 11.5px;
                    color: #065f46;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-weight: 700;
                `;
                fdDiv.innerHTML = `<i class="fa-solid fa-truck-fast" style="color: #059669;"></i> <span>Free Delivery Unlocked! (Order ৳${subtotal.toFixed(0)} >= ৳1000)</span>`;
                container.appendChild(fdDiv);
            }

            if (!activeOffers || activeOffers.length === 0) return;

            activeOffers.forEach(offer => {
                const card = document.createElement('div');

                if (offer.type === 'combo_gift') {
                    // Check if gift already in table
                    const tbody = document.getElementById('edit-items-tbody');
                    let isGiftInTable = false;
                    tbody.querySelectorAll('tr').forEach(tr => {
                        const nameInp = tr.querySelector('.item-name-input');
                        const priceInp = tr.querySelector('.item-price-input');
                        if (nameInp && priceInp && parseFloat(priceInp.value) === 0 && nameInp.value.toLowerCase().includes(offer.giftName.toLowerCase())) {
                            isGiftInTable = true;
                        }
                    });

                    card.style.cssText = `
                        background: #f0fdf4;
                        border: 1.5px solid #86efac;
                        padding: 8px 10px;
                        border-radius: 8px;
                        font-size: 12px;
                        color: #14532d;
                        display: flex;
                        flex-direction: column;
                        gap: 5px;
                    `;
                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 800; color: #15803d; display: flex; align-items: center; gap: 5px;">
                                <i class="fa-solid fa-gift"></i> ${escapeHtml(offer.title)}
                            </span>
                            <span style="background: #22c55e; color: white; padding: 1px 7px; border-radius: 10px; font-size: 10px; font-weight: 800;">
                                ${escapeHtml(offer.badge)}
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2px;">
                            <span style="font-size: 11.5px; color: #334155; font-weight: 700;">
                                Free Gift: <strong>${escapeHtml(offer.giftName)} (${offer.giftQty} Pcs)</strong>
                            </span>
                            ${isGiftInTable 
                                ? `<span style="color: #059669; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 3px;"><i class="fa-solid fa-circle-check"></i> Added</span>`
                                : `<button type="button" onclick="addFreeGiftItemToOrder(${offer.giftProdId}, '${escapeHtml(offer.giftName)}', ${offer.giftQty})" style="background: #059669; color: white; border: none; padding: 3px 8px; border-radius: 5px; font-size: 11px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-plus"></i> Add Free Gift
                                   </button>`
                            }
                        </div>
                    `;
                } else if (offer.type === 'coupon') {
                    card.style.cssText = `
                        background: #eff6ff;
                        border: 1px solid #93c5fd;
                        padding: 6px 10px;
                        border-radius: 6px;
                        font-size: 11.5px;
                        color: #1e40af;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        font-weight: 700;
                    `;
                    card.innerHTML = `
                        <span style="display: flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-tag" style="color: #3b82f6;"></i> ${escapeHtml(offer.title)}: <strong>-৳${offer.amount.toFixed(2)}</strong>
                        </span>
                        <span style="background: #3b82f6; color: white; padding: 1px 6px; border-radius: 10px; font-size: 10px; font-weight: 800;">${escapeHtml(offer.badge)}</span>
                    `;
                } else if (offer.type === 'cart_spend' || offer.type === 'combo_disc') {
                    card.style.cssText = `
                        background: #fdf4ff;
                        border: 1px solid #f0abfc;
                        padding: 6px 10px;
                        border-radius: 6px;
                        font-size: 11.5px;
                        color: #86198f;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        font-weight: 700;
                    `;
                    card.innerHTML = `
                        <span style="display: flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-bolt" style="color: #d946ef;"></i> ${escapeHtml(offer.title)}: <strong>-৳${offer.amount.toFixed(2)}</strong>
                        </span>
                        <span style="background: #d946ef; color: white; padding: 1px 6px; border-radius: 10px; font-size: 10px; font-weight: 800;">${escapeHtml(offer.badge)}</span>
                    `;
                } else if (offer.type === 'coupon_min') {
                    card.style.cssText = `
                        background: #fffbeb;
                        border: 1px solid #fde68a;
                        padding: 6px 10px;
                        border-radius: 6px;
                        font-size: 11px;
                        color: #92400e;
                        display: flex;
                        align-items: center;
                        gap: 5px;
                        font-weight: 600;
                    `;
                    card.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="color: #d97706;"></i> <span>${escapeHtml(offer.title)}</span>`;
                }

                container.appendChild(card);
            });
        }

        function recalcEditTotal(autoDel = true) {
            evaluateOrderOffersAndDelivery(autoDel);
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // Modern Floating Toast Notification (Auto-Dismissing)
        function showToast(message, type = 'success', duration = 3200) {
            let container = document.getElementById('management-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'management-toast-container';
                container.style.cssText = `
                    position: fixed;
                    top: 24px;
                    right: 24px;
                    z-index: 999999;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    pointer-events: none;
                    max-width: 440px;
                    width: calc(100vw - 48px);
                `;
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            const isWarning = type === 'warning';
            const bgGrad = isSuccess 
                ? 'linear-gradient(135deg, #059669, #10b981)' 
                : (isWarning ? 'linear-gradient(135deg, #d97706, #f59e0b)' : 'linear-gradient(135deg, #dc2626, #ef4444)');
            const borderCol = isSuccess ? '#34d399' : (isWarning ? '#fcd34d' : '#f87171');
            const iconClass = isSuccess ? 'fa-circle-check' : (isWarning ? 'fa-triangle-exclamation' : 'fa-circle-xmark');

            toast.style.cssText = `
                pointer-events: auto;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 18px;
                border-radius: 10px;
                color: #ffffff;
                font-family: 'Outfit', sans-serif;
                font-size: 14px;
                font-weight: 700;
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22), 0 4px 10px rgba(0, 0, 0, 0.12);
                background: ${bgGrad};
                border: 1px solid ${borderCol};
                transform: translateY(-20px) scale(0.96);
                opacity: 0;
                transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
                position: relative;
                overflow: hidden;
            `;

            toast.innerHTML = `
                <i class="fa-solid ${iconClass}" style="font-size: 19px; flex-shrink: 0;"></i>
                <div style="flex: 1; line-height: 1.4; word-break: break-word;">${escapeHtml(message)}</div>
                <button type="button" onclick="this.parentElement.remove()" style="background: transparent; border: none; color: rgba(255,255,255,0.75); cursor: pointer; padding: 4px; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.75)'">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="toast-prog-bar" style="position: absolute; bottom: 0; left: 0; height: 3px; background: rgba(255,255,255,0.45); width: 100%; transition: width ${duration}ms linear;"></div>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0) scale(1)';
                toast.style.opacity = '1';
                const prog = toast.querySelector('.toast-prog-bar');
                if (prog) {
                    requestAnimationFrame(() => { prog.style.width = '0%'; });
                }
            });

            setTimeout(() => {
                toast.style.transform = 'translateY(-20px) scale(0.95)';
                toast.style.opacity = '0';
                setTimeout(() => {
                    if (toast.parentElement) toast.remove();
                }, 350);
            }, duration);
        }

        async function saveAdminEdit() {
            const btn = document.getElementById('btn-save-admin-edit');
            const originalBtnHtml = btn.innerHTML;

            const itemId = document.getElementById('edit-item-id').value;
            const dbId = parseInt(document.getElementById('edit-item-db-id').value);
            const type = document.getElementById('edit-item-type').value;

            const custName = document.getElementById('edit-cust-name').value.trim();
            const custPhone = document.getElementById('edit-cust-phone').value.trim();
            const custAddress = document.getElementById('edit-cust-address').value.trim();
            const status = document.getElementById('edit-doc-status').value;

            if (!custName || !custPhone) {
                showToast('Customer Name and Phone are required.', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Saving...`;

            try {
                let payload = {};
                if (type === 'list') {
                    const listType = document.getElementById('edit-list-type').value;
                    let contentText = '';

                    if (listType === 'written') {
                        const rows = document.querySelectorAll('#edit-custom-list-tbody tr');
                        const listItems = [];
                        rows.forEach(tr => {
                            const name = tr.querySelector('.cl-item-name')?.value.trim() || '';
                            const spec = tr.querySelector('.cl-item-spec')?.value.trim() || '';
                            const qty = tr.querySelector('.cl-item-qty')?.value.trim() || '1';
                            if (name) {
                                listItems.push({ item: name, spec: spec, qty: qty });
                            }
                        });

                        if (listItems.length === 0) {
                            showToast('Please add at least one item name to the list.', 'warning');
                            btn.disabled = false;
                            btn.innerHTML = originalBtnHtml;
                            return;
                        }

                        contentText = listItems.map((it, idx) => {
                            let line = `${idx + 1}. ${it.item}`;
                            if (it.spec) line += ` (${it.spec})`;
                            if (it.qty) line += ` - ${it.qty}`;
                            return line;
                        }).join('\n');

                        payload = {
                            action: 'admin_edit_custom_list',
                            list_id: dbId,
                            list_code: itemId,
                            customer_name: custName,
                            customer_phone: custPhone,
                            customer_address: custAddress,
                            status: status,
                            list_type: 'written',
                            content_text: contentText,
                            images: []
                        };
                    } else {
                        // Uploaded photo list
                        if (currentEditListImages.length === 0) {
                            showToast('Please upload at least one photo for this list.', 'warning');
                            btn.disabled = false;
                            btn.innerHTML = originalBtnHtml;
                            return;
                        }

                        contentText = 'Uploaded Photo List';
                        payload = {
                            action: 'admin_edit_custom_list',
                            list_id: dbId,
                            list_code: itemId,
                            customer_name: custName,
                            customer_phone: custPhone,
                            customer_address: custAddress,
                            status: status,
                            list_type: 'uploaded',
                            content_text: contentText,
                            images: currentEditListImages
                        };
                    }
                } else if (type === 'order') {
                    const listType = document.getElementById('edit-list-type').value;

                    if (listType === 'written') {
                        const rows = document.querySelectorAll('#edit-custom-list-tbody tr');
                        const listItems = [];
                        rows.forEach(tr => {
                            const name = tr.querySelector('.cl-item-name')?.value.trim() || '';
                            const spec = tr.querySelector('.cl-item-spec')?.value.trim() || '';
                            const qty = tr.querySelector('.cl-item-qty')?.value.trim() || '1';
                            if (name) {
                                listItems.push({ item: name, spec: spec, qty: qty });
                            }
                        });

                        if (listItems.length === 0) {
                            showToast('Please add at least one item to the order.', 'warning');
                            btn.disabled = false;
                            btn.innerHTML = originalBtnHtml;
                            return;
                        }

                        payload = {
                            action: 'admin_edit_order_grocery_list',
                            order_id: dbId,
                            order_number: itemId,
                            customer_name: custName,
                            customer_phone: custPhone,
                            customer_address: custAddress,
                            status: status,
                            order_type: 'text_list',
                            items: listItems,
                            images: []
                        };
                    } else {
                        // Uploaded photo order
                        if (currentEditListImages.length === 0) {
                            showToast('Please upload at least one photo for this order.', 'warning');
                            btn.disabled = false;
                            btn.innerHTML = originalBtnHtml;
                            return;
                        }

                        payload = {
                            action: 'admin_edit_order_grocery_list',
                            order_id: dbId,
                            order_number: itemId,
                            customer_name: custName,
                            customer_phone: custPhone,
                            customer_address: custAddress,
                            status: status,
                            order_type: 'uploaded',
                            items: [],
                            images: currentEditListImages
                        };
                    }
                } else {
                    const rows = document.querySelectorAll('#edit-items-tbody tr');
                    const items = [];
                    rows.forEach(tr => {
                        const name = tr.querySelector('.item-name-input').value.trim();
                        const spec = tr.querySelector('.item-spec-input').value.trim();
                        const price = parseFloat(tr.querySelector('.item-price-input').value) || 0;
                        const qty = parseInt(tr.querySelector('.item-qty-input').value) || 1;
                        if (name) {
                            items.push({ name, spec, price, qty });
                        }
                    });

                    if (items.length === 0) {
                        showToast('Please add at least one item with a name.', 'warning');
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml;
                        return;
                    }

                    const deliveryCharge = parseFloat(document.getElementById('edit-delivery-charge').value) || 0;
                    const discountAmount = parseFloat(document.getElementById('edit-discount-amount').value) || 0;
                    const couponCode = document.getElementById('edit-coupon-code')?.value.trim() || '';

                    payload = {
                        action: 'admin_edit_order_invoice',
                        order_id: dbId,
                        order_number: itemId,
                        customer_name: custName,
                        customer_phone: custPhone,
                        customer_address: custAddress,
                        status: status,
                        delivery_charge: deliveryCharge,
                        discount_amount: discountAmount,
                        coupon_code: couponCode,
                        items: items
                    };
                }

                const response = await fetch('api/update-order-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const res = await response.json();
                if (res.status === 'success') {
                    // Update in-memory array
                    if (type === 'list' && res.list) {
                        const idx = userLists.findIndex(x => x.db_id === dbId || x.id === itemId);
                        if (idx !== -1) {
                            Object.assign(userLists[idx], res.list);
                        }
                    } else if (res.order) {
                        if (type === 'invoice') {
                            const idx = userInvoices.findIndex(x => x.db_id === dbId || x.id === itemId);
                            if (idx !== -1) {
                                Object.assign(userInvoices[idx], res.order);
                            }
                        } else {
                            const idx = userOrders.findIndex(x => x.db_id === dbId || x.id === itemId);
                            if (idx !== -1) {
                                Object.assign(userOrders[idx], res.order);
                            }
                        }
                    }

                    // Re-render current tab
                    if (currentTab === 'list') renderCards('list-grid', userLists, 'list');
                    if (currentTab === 'order') renderCards('list-grid', userOrders, 'order');
                    if (currentTab === 'invoice') renderCards('list-grid', userInvoices, 'invoice');

                    closeAdminEditModal();
                    showToast(res.message || 'Saved successfully!', 'success');
                } else {
                    showToast(res.message || 'Failed to update record.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Network error while saving changes. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }

        function closePreviewModal() {
            document.getElementById('preview-modal').style.display = 'none';
        }

        function deleteItemRecord(id, type) {
            if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
            let actionName = type === 'order' ? 'delete_order' : (type === 'list' ? 'delete_list' : 'delete_order');
            let payload = type === 'list' ? { list_id: id } : { order_id: id };

            fetch(`mng_customer_detail.php?id=${customerId}&action=${actionName}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(res => {
                showToast(res.message, res.status === 'success' ? 'success' : 'error');
                if (res.status === 'success') setTimeout(() => location.reload(), 900);
            }).catch(() => {
                showToast('Network error while deleting item.', 'error');
            });
        }

        // Map and Profile Edit Scripts
        let viewMap = null;
        let editAddrMap = null;
        let editMarker = null;

        function showAddressInMap(lat, lng, street, ps, district) {
            lat = parseFloat(lat) || 23.870455;
            lng = parseFloat(lng) || 90.398812;

            document.getElementById('view-map-address-primary').innerText = street || "Street not specified";
            document.getElementById('view-map-address-secondary').innerText = (ps ? ps + ', ' : '') + (district || 'Dhaka');
            document.getElementById('view-map-lat').innerText = lat.toFixed(6);
            document.getElementById('view-map-lng').innerText = lng.toFixed(6);

            document.getElementById('mapViewModal').style.display = 'flex';
            setTimeout(() => {
                if (!viewMap) {
                    viewMap = L.map('view-map').setView([lat, lng], 15);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(viewMap);
                    L.marker([lat, lng]).addTo(viewMap);
                } else {
                    viewMap.setView([lat, lng], 15);
                    viewMap.invalidateSize();
                }
            }, 300);
        }

        function initEditAddressMap(lat, lng) {
            lat = parseFloat(lat) || 23.870455;
            lng = parseFloat(lng) || 90.398812;
            document.getElementById('addr_lat').value = lat;
            document.getElementById('addr_lng').value = lng;

            setTimeout(() => {
                if (!editAddrMap) {
                    editAddrMap = L.map('edit-address-map').setView([lat, lng], 14);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(editAddrMap);
                    editMarker = L.marker([lat, lng], { draggable: true }).addTo(editAddrMap);
                    
                    editMarker.on('dragend', function(e) {
                        const pos = editMarker.getLatLng();
                        document.getElementById('addr_lat').value = pos.lat.toFixed(6);
                        document.getElementById('addr_lng').value = pos.lng.toFixed(6);
                    });

                    editAddrMap.on('move', function () {
                        const center = editAddrMap.getCenter();
                        if (editMarker) editMarker.setLatLng(center);
                        document.getElementById('addr_lat').value = center.lat.toFixed(6);
                        document.getElementById('addr_lng').value = center.lng.toFixed(6);
                    });
                } else {
                    editAddrMap.setView([lat, lng], 14);
                    if (editMarker) editMarker.setLatLng([lat, lng]);
                    editAddrMap.invalidateSize();
                }
            }, 300);
        }

        function openAddAddressModal() {
            document.getElementById('addr_modal_title').innerText = "Add New Address";
            document.getElementById('addr_id').value = '';
            document.getElementById('addr_type').value = 'home';
            document.getElementById('addr_keyword').value = 'Home';
            
            if (window.populateDistrictSelect) {
                window.populateDistrictSelect('addr_district', 'Dhaka');
                fetchMngUpazilas('Dhaka', 'addr_ps', 'Uttara');
            }
            document.getElementById('addr_street').value = '';
            document.getElementById('addressModal').style.display = 'flex';
            initEditAddressMap(23.870455, 90.398812);
        }

        function openEditAddressModal(id, type, keyword, district, ps, street, lat, lng) {
            document.getElementById('addr_modal_title').innerText = "Edit Address";
            document.getElementById('addr_id').value = id;
            document.getElementById('addr_type').value = type || 'home';
            document.getElementById('addr_keyword').value = keyword || 'Home';
            
            if (window.populateDistrictSelect) {
                window.populateDistrictSelect('addr_district', district || 'Dhaka');
            }
            if (district || ps) {
                fetchMngUpazilas(district || 'Dhaka', 'addr_ps', ps || 'Uttara');
            }
            document.getElementById('addr_street').value = street || '';
            document.getElementById('addressModal').style.display = 'flex';
            initEditAddressMap(lat || 23.870455, lng || 90.398812);
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function submitAddress(event) {
            event.preventDefault();
            const addrId = document.getElementById('addr_id').value;
            const data = {
                addr_id: addrId,
                address_type: document.getElementById('addr_type').value,
                keyword: document.getElementById('addr_keyword').value,
                district: document.getElementById('addr_district').value,
                police_station: document.getElementById('addr_ps').value,
                street: document.getElementById('addr_street').value,
                latitude: document.getElementById('addr_lat').value,
                longitude: document.getElementById('addr_lng').value
            };
            const action = addrId ? 'edit_address' : 'add_address';
            fetch(`mng_customer_detail.php?id=${customerId}&action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).then(r => r.json()).then(res => {
                showToast(res.message, res.status === 'success' ? 'success' : 'error');
                if (res.status === 'success') setTimeout(() => location.reload(), 900);
            }).catch(() => {
                showToast('Network error while saving address.', 'error');
            });
        }

        function deleteAddress(addrId) {
            if (!confirm("Are you sure you want to delete this address?")) return;
            fetch(`mng_customer_detail.php?id=${customerId}&action=delete_address`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ addr_id: addrId })
            }).then(r => r.json()).then(res => {
                showToast(res.message, res.status === 'success' ? 'success' : 'error');
                if (res.status === 'success') setTimeout(() => location.reload(), 900);
            }).catch(() => {
                showToast('Network error while deleting address.', 'error');
            });
        }

        // Profile Edit
        let profilePhotoData = "<?= htmlspecialchars($customer['profile_photo'] ?? '') ?>";

        function toggleEditMode() {
            document.querySelectorAll('#profile-form .form-control').forEach(el => el.disabled = false);
            document.getElementById('edit-btn').style.display = 'none';
            document.getElementById('action-buttons').style.display = 'flex';
            document.getElementById('location-hint').style.display = 'block';
            document.getElementById('avatar-edit-overlay').style.display = 'block';
        }

        function cancelEdit() {
            location.reload();
        }

        function triggerPhotoUpload() {
            document.getElementById('photo-input').click();
        }

        function handlePhotoChange(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePhotoData = e.target.result;
                    document.getElementById('large-avatar-container').innerHTML = `
                        <img src="${profilePhotoData}" style="width: 100%; height: 100%; object-fit: cover;">
                        <div class="avatar-edit-overlay" id="avatar-edit-overlay" onclick="triggerPhotoUpload()" style="display: block;">Change</div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }

        function submitCustomerProfile(event) {
            event.preventDefault();
            const data = {
                full_name: document.getElementById('prof_name').value,
                phone_number: document.getElementById('prof_phone').value,
                email: document.getElementById('prof_email').value,
                district: document.getElementById('prof_district').value,
                police_station: document.getElementById('prof_ps').value,
                address: document.getElementById('prof_address').value,
                profile_photo: profilePhotoData
            };
            fetch(`mng_customer_detail.php?id=${customerId}&action=edit_profile`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).then(r => r.json()).then(res => {
                showToast(res.message, res.status === 'success' ? 'success' : 'error');
                if (res.status === 'success') setTimeout(() => location.reload(), 900);
            }).catch(() => {
                showToast('Network error while updating profile.', 'error');
            });
        }

        function fetchMngDistricts() {
            if (window.populateDistrictSelect) {
                const profDist = document.getElementById('prof_district');
                if (profDist) {
                    const defaultProf = profDist.getAttribute('data-selected') || 'Dhaka';
                    window.populateDistrictSelect(profDist, defaultProf);
                    const defaultPs = document.getElementById('prof_ps')?.getAttribute('data-selected') || 'Uttara';
                    fetchMngUpazilas(defaultProf, 'prof_ps', defaultPs);
                }
            }
        }

        function fetchMngUpazilas(dist, targetId, selected = null) {
            const select = document.getElementById(targetId);
            if (!select) return;
            if (window.populateUpazilaSelect) {
                window.populateUpazilaSelect(dist, select, selected);
            }
        }
    </script>
</body>
</html>
