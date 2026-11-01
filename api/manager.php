<?php
// api/manager.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// Enforce Manager / Admin authorization
$authManager = requirePortalAuth(['admin', 'author', 'manager', 'management', 'operation']);

try {
    // 1. Add New Product
    if ($action === 'add_product') {
        requirePermission('create_products');
        $categoryId = intval($input['category_id'] ?? 1);
        $title = trim($input['title'] ?? '');
        $titleBn = trim($input['title_bn'] ?? $title);
        $desc = trim($input['description'] ?? '');
        $descBn = trim($input['description_bn'] ?? $desc);
        $poriman = trim($input['poriman'] ?? '1 kg');
        $porimanBn = trim($input['poriman_bn'] ?? '১ কেজি');
        $price = floatval($input['price'] ?? 0);
        $oldPrice = floatval($input['old_price'] ?? 0);
        $stock = intval($input['stock'] ?? 0);
        $image = trim($input['image'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500');
        $inStock = isset($input['in_stock']) ? intval($input['in_stock']) : 1;
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (empty($title)) {
            jsonResponse(['status' => 'error', 'message' => 'Product title is required.'], 400);
        }

        $discountPercent = ($oldPrice > $price && $oldPrice > 0) ? round((($oldPrice - $price) / $oldPrice) * 100) : 0;

        $stmt = $pdo->prepare("
            INSERT INTO `products` 
            (`category_id`, `title`, `title_bn`, `description`, `description_bn`, `poriman`, `poriman_bn`, `price`, `old_price`, `discount_percent`, `image`, `stock`, `in_stock`, `is_active`, `is_featured`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $categoryId, $title, $titleBn, $desc, $descBn, $poriman, $porimanBn, $price, $oldPrice, $discountPercent, $image, $stock, $inStock, $isActive
        ]);
        $productId = $pdo->lastInsertId();
        $pCode = 'PRD-' . str_pad($productId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE `products` SET `product_code` = ? WHERE `id` = ?")->execute([$pCode, $productId]);

        logAuditActivity($pdo, 'Product Created', "New product #{$productId} ({$title}) created with Price: ৳{$price}, Stock: {$stock}");

        jsonResponse([
            'status' => 'success',
            'message' => 'Product added successfully!',
            'product_id' => $productId,
            'product_code' => $pCode
        ]);
    }

    // 2. Edit Existing Product
    if ($action === 'edit_product') {
        requirePermission('edit_products');
        $id = intval($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $titleBn = trim($input['title_bn'] ?? $title);
        $categoryId = intval($input['category_id'] ?? 1);
        $desc = trim($input['description'] ?? '');
        $descBn = trim($input['description_bn'] ?? $desc);
        $poriman = trim($input['poriman'] ?? '1 kg');
        $porimanBn = trim($input['poriman_bn'] ?? '১ কেজি');
        $image = trim($input['image'] ?? '');

        if ($id <= 0 || empty($title)) {
            jsonResponse(['status' => 'error', 'message' => 'Valid Product ID and title are required.'], 400);
        }

        $exist = $pdo->prepare("SELECT title, price, old_price, stock, in_stock, is_active FROM products WHERE id = ?");
        $exist->execute([$id]);
        $curr = $exist->fetch(PDO::FETCH_ASSOC) ?: ['title' => $title, 'price' => 0, 'old_price' => 0, 'stock' => 0, 'in_stock' => 1, 'is_active' => 1];

        $price = (isset($input['price']) && $input['price'] !== '') ? floatval($input['price']) : floatval($curr['price']);
        $oldPrice = (isset($input['old_price']) && $input['old_price'] !== '') ? floatval($input['old_price']) : floatval($curr['old_price']);
        $stock = (isset($input['stock']) && $input['stock'] !== '') ? intval($input['stock']) : intval($curr['stock']);
        $inStock = isset($input['in_stock']) ? intval($input['in_stock']) : intval($curr['in_stock']);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : intval($curr['is_active']);

        $discountPercent = ($oldPrice > $price && $oldPrice > 0) ? round((($oldPrice - $price) / $oldPrice) * 100) : 0;

        $stmt = $pdo->prepare("
            UPDATE `products` 
            SET `category_id` = ?, `title` = ?, `title_bn` = ?, `description` = ?, `description_bn` = ?, `poriman` = ?, `poriman_bn` = ?, `price` = ?, `old_price` = ?, `discount_percent` = ?, `stock` = ?, `in_stock` = ?, `is_active` = ?, `image` = IF(? != '', ?, `image`) 
            WHERE `id` = ?
        ");
        $stmt->execute([
            $categoryId, $title, $titleBn, $desc, $descBn, $poriman, $porimanBn, $price, $oldPrice, $discountPercent, $stock, $inStock, $isActive, $image, $image, $id
        ]);

        $changes = [];
        if (floatval($curr['price']) != $price) {
            $changes[] = "Price: ৳{$curr['price']} -> ৳{$price}";
        }
        if (floatval($curr['old_price']) != $oldPrice) {
            $changes[] = "Old Price: ৳{$curr['old_price']} -> ৳{$oldPrice}";
        }
        if (intval($curr['stock']) != $stock) {
            $changes[] = "Stock: {$curr['stock']} -> {$stock}";
        }
        if (intval($curr['in_stock']) != $inStock) {
            $changes[] = "In Stock: " . ($curr['in_stock'] ? 'Yes' : 'No') . " -> " . ($inStock ? 'Yes' : 'No');
        }
        if (intval($curr['is_active']) != $isActive) {
            $changes[] = "Status: " . ($curr['is_active'] ? 'Active' : 'Inactive') . " -> " . ($isActive ? 'Active' : 'Inactive');
        }
        $detailStr = !empty($changes) ? implode(', ', $changes) : "Product details updated (Title: {$title})";
        logAuditActivity($pdo, 'Product Updated', "Product #{$id} ({$title}): " . $detailStr);

        jsonResponse(['status' => 'success', 'message' => 'Product updated successfully!']);
    }

    // 3. Delete Product
    if ($action === 'delete_product') {
        requirePermission('delete_products');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid Product ID'], 400);
        }
        $delTitle = $pdo->query("SELECT title FROM products WHERE id = " . intval($id))->fetchColumn() ?: "ID #{$id}";
        $pdo->prepare("DELETE FROM `products` WHERE `id` = ?")->execute([$id]);
        logAuditActivity($pdo, 'Product Deleted', "Product #{$id} ({$delTitle}) was permanently deleted.");
        jsonResponse(['status' => 'success', 'message' => 'Product deleted successfully!']);
    }

    // 4. Toggle Product Active / Inactive Status
    if ($action === 'toggle_product_status') {
        requirePermission('edit_products');
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid Product ID'], 400);
        }

        $prod = $pdo->prepare("SELECT id, title, is_active, status FROM products WHERE id = ?");
        $prod->execute([$id]);
        $p = $prod->fetch(PDO::FETCH_ASSOC);

        if (!$p) {
            jsonResponse(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $currentActive = (isset($p['is_active']) && intval($p['is_active']) === 1) || (isset($p['status']) && $p['status'] === 'active');
        $newActive = $currentActive ? 0 : 1;
        $newStatus = $newActive ? 'active' : 'inactive';

        $pdo->prepare("UPDATE products SET is_active = ?, status = ? WHERE id = ?")->execute([$newActive, $newStatus, $id]);
        logAuditActivity($pdo, 'Product Status Toggled', "Product #{$id} ({$p['title']}) status changed to " . ($newActive ? 'Active' : 'Inactive'));

        jsonResponse([
            'status' => 'success',
            'message' => "Product '{$p['title']}' is now " . ($newActive ? 'Active' : 'Inactive') . '!',
            'is_active' => $newActive,
            'status_label' => $newStatus
        ]);
    }

    // 4. Delete Order
    if ($action === 'delete_order') {
        requirePermission('cancel_orders');
        $orderId = intval($input['order_id'] ?? $input['id'] ?? 0);
        if ($orderId <= 0) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid Order ID'], 400);
        }
        $pdo->prepare("DELETE FROM `orders` WHERE `id` = ?")->execute([$orderId]);
        logAuditActivity($pdo, 'Order Deleted', "Order #{$orderId} was deleted by staff/admin.");
        jsonResponse(['status' => 'success', 'message' => 'Order deleted successfully!']);
    }

    // 5. Add New Category
    if ($action === 'add_category') {
        requirePermission('manage_categories');
        $name = trim($input['name'] ?? '');
        $nameBn = trim($input['name_bn'] ?? $name);
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
        $img = trim($input['img'] ?? 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300');

        if (empty($name)) {
            jsonResponse(['status' => 'error', 'message' => 'Category name is required'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO `categories` (`name`, `name_bn`, `slug`, `img`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $nameBn, $slug, $img]);
        $newCatId = $pdo->lastInsertId();
        logAuditActivity($pdo, 'Category Created', "Category #{$newCatId} ({$name}) was created.");

        jsonResponse(['status' => 'success', 'message' => 'Category created successfully!']);
    }

    jsonResponse(['status' => 'error', 'message' => 'Invalid action requested.'], 400);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
