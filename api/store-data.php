<?php
// api/store-data.php
require_once __DIR__ . '/../config/db.php';
session_write_close(); // Release PHP session lock immediately for non-blocking parallel requests

header('Content-Type: application/json; charset=utf-8');

try {
    $userZoneId = isset($_GET['zone_id']) ? intval($_GET['zone_id']) : null;
    $userLocation = isset($_GET['location']) ? trim($_GET['location']) : '';
    $userDistrict = isset($_GET['district']) ? trim($_GET['district']) : '';
    $userThana = isset($_GET['police_station']) ? trim($_GET['police_station']) : (isset($_GET['thana']) ? trim($_GET['thana']) : '');

    // Resolve District, Thana & Zone automatically from location string if not explicitly passed
    $resolvedLoc = resolveLocationInfo($pdo, $userLocation, $userDistrict, $userThana, $userZoneId);
    $userZoneId = $resolvedLoc['zone_id'];
    $userDistrict = $resolvedLoc['district'];
    $userThana = $resolvedLoc['police_station'];

    // 1. Fetch Categories
    $catStmt = $pdo->query("SELECT id, name, name_bn, slug, img FROM categories WHERE status = 1 ORDER BY id ASC");
    $categories = $catStmt->fetchAll();

    // 2. Fetch Active Discount Rules
    $discStmt = $pdo->query("
        SELECT 
            r.*,
            rp.title as reward_product_title,
            rp.title_bn as reward_product_title_bn,
            rp.image as reward_product_image,
            rp.poriman as reward_product_poriman,
            rp.poriman_bn as reward_product_poriman_bn,
            rp.price as reward_product_price
        FROM discount_rules r 
        LEFT JOIN products rp ON r.reward_product_id = rp.id
        WHERE r.is_active = 1 
          AND (r.start_datetime IS NULL OR r.start_datetime <= NOW())
          AND (r.end_datetime IS NULL OR r.end_datetime >= NOW())
        ORDER BY r.priority ASC, r.id DESC
    ");
    $discountRules = $discStmt->fetchAll();

    // 3. Fetch Products with Category, Active Batch, Zone Pricing, and Realtime Stock
    $prodStmt = $pdo->query("
        SELECT 
            p.id, 
            p.product_code,
            p.sku,
            p.brand,
            p.title, 
            p.title_bn, 
            p.description, 
            p.description_bn, 
            p.poriman, 
            p.poriman_bn, 
            p.unit_type,
            CAST(p.price AS FLOAT) as base_price, 
            CAST(p.old_price AS FLOAT) as base_old_price, 
            p.discount_percent, 
            p.image, 
            p.stock, 
            p.in_stock,
            p.is_active,
            p.is_featured, 
            p.is_hot,
            p.category_id,
            c.name as category_name,
            c.name_bn as category_name_bn,
            (SELECT b.id FROM product_batches b WHERE b.product_id = p.id AND b.status = 'active' ORDER BY b.id DESC LIMIT 1) as active_batch_id,
            (SELECT b.batch_number FROM product_batches b WHERE b.product_id = p.id AND b.status = 'active' ORDER BY b.id DESC LIMIT 1) as batch_number,
            (SELECT b.exp_date FROM product_batches b WHERE b.product_id = p.id AND b.status = 'active' ORDER BY b.id DESC LIMIT 1) as exp_date
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' AND (p.in_stock = 1 OR p.stock > 0)
        ORDER BY p.id DESC
    ");
    $rawProducts = $prodStmt->fetchAll();

    $products = [];
    foreach ($rawProducts as $p) {
        $pId = $p['id'];
        $bId = $p['active_batch_id'];

        // Fetch Specific Location / District / Zone / Batch / Default Pricing
        $priceStmt = $pdo->prepare("
            SELECT cost_price, dp_price, mrp_price, selling_price, batch_id, zone_id, district, police_station, is_active 
            FROM product_pricing 
            WHERE product_id = ? 
              AND (
                  (district = ? AND police_station = ?) OR
                  (district = ? AND (police_station = 'All Police Stations' OR police_station = '' OR police_station IS NULL)) OR
                  (zone_id = ? AND zone_id > 1) OR
                  (district = 'All Bangladesh') OR
                  ((district = '' OR district IS NULL) AND (zone_id = 1 OR zone_id IS NULL))
              )
            ORDER BY 
                (CASE WHEN district = ? AND police_station = ? AND district != 'All Bangladesh' AND district != '' THEN 100 ELSE 0 END) DESC,
                (CASE WHEN district = ? AND district != 'All Bangladesh' AND district != '' THEN 80 ELSE 0 END) DESC,
                (CASE WHEN zone_id = ? AND zone_id > 1 THEN 60 ELSE 0 END) DESC,
                (CASE WHEN batch_id = ? AND batch_id IS NOT NULL THEN 40 ELSE 0 END) DESC,
                (CASE WHEN district = 'All Bangladesh' THEN 20 ELSE 0 END) DESC,
                id DESC 
            LIMIT 1
        ");
        $priceStmt->execute([
            $pId, 
            $userDistrict, $userThana,
            $userDistrict,
            $userZoneId,
            $userDistrict, $userThana,
            $userDistrict,
            $userZoneId,
            $bId
        ]);
        $pricing = $priceStmt->fetch();

        $isAvailableInLocation = true;
        if ($pricing && (isset($pricing['is_active']) && $pricing['is_active'] == 0)) {
            $isAvailableInLocation = false;
        }

        $costPrice = $pricing ? floatval($pricing['cost_price']) : round(floatval($p['base_price']) * 0.8, 2);
        $dpPrice = $pricing ? floatval($pricing['dp_price']) : round(floatval($p['base_price']) * 0.9, 2);
        $sellingPrice = $pricing ? floatval($pricing['selling_price']) : floatval($p['base_price']);
        $mrpPrice = $pricing ? floatval($pricing['mrp_price']) : ($p['base_old_price'] ? floatval($p['base_old_price']) : round($sellingPrice * 1.15, 2));

        // Fetch Location-specific Stock
        $locStock = getProductStockForLocation($pdo, $pId, $userDistrict, $userThana, $userZoneId, $bId);
        $currStock = $locStock ? floatval($locStock['current_stock']) : floatval($p['stock']);
        if (!$isAvailableInLocation) {
            $currStock = 0;
        }

        // Check for Active Product Discounts (Fixed, %, BOGO)
        $applicableDiscounts = [];
        $isBogo = false;
        $bogoText = '';
        $bogoTextBn = '';
        $discountBadge = '';
        $discountBadgeBn = '';
        $effectiveDiscountAmount = 0;

        foreach ($discountRules as $dr) {
            $isMatch = false;
            if ($dr['target_type'] === 'single_product' && intval($dr['target_id']) === $pId) {
                $isMatch = true;
            } else if ($dr['target_type'] === 'category' && intval($dr['target_id']) === intval($p['category_id'])) {
                $isMatch = true;
            }

            if ($isMatch) {
                if ($dr['discount_type'] === 'bogo') {
                    $isBogo = true;
                    $bogoText = $dr['title'] ?: 'BUY 1 GET 1 FREE';
                    $bogoTextBn = $dr['title_bn'] ?: 'বাই ১ গেট ১ ফ্রি';
                    $applicableDiscounts[] = [
                        'type' => 'bogo',
                        'title' => $dr['title'],
                        'title_bn' => $dr['title_bn'],
                        'reward_product_id' => $dr['reward_product_id'] ?: $pId,
                        'reward_qty' => floatval($dr['reward_qty']),
                        'reward_discount_percent' => floatval($dr['reward_discount_percent'])
                    ];
                } else if ($dr['discount_type'] === 'fixed_amount') {
                    $discVal = floatval($dr['discount_value']);
                    $effectiveDiscountAmount = max($effectiveDiscountAmount, $discVal);
                    $discountBadge = "৳{$discVal} OFF";
                    $discountBadgeBn = "৳{$discVal} ছাড়";
                    $applicableDiscounts[] = [
                        'type' => 'fixed_amount',
                        'amount' => $discVal,
                        'title' => $dr['title'],
                        'title_bn' => $dr['title_bn']
                    ];
                } else if ($dr['discount_type'] === 'percentage') {
                    $pct = floatval($dr['discount_value']);
                    $discVal = round(($sellingPrice * $pct) / 100, 2);
                    $effectiveDiscountAmount = max($effectiveDiscountAmount, $discVal);
                    $discountBadge = "{$pct}% OFF";
                    $discountBadgeBn = "{$pct}% ছাড়";
                    $applicableDiscounts[] = [
                        'type' => 'percentage',
                        'percent' => $pct,
                        'amount' => $discVal,
                        'title' => $dr['title'],
                        'title_bn' => $dr['title_bn']
                    ];
                }
            }
        }

        // Final discounted price
        $finalPrice = max(0, $sellingPrice - $effectiveDiscountAmount);

        $products[] = [
            'id' => $pId,
            'product_code' => $p['product_code'] ?: ('PRD-' . str_pad($pId, 5, '0', STR_PAD_LEFT)),
            'sku' => $p['sku'] ?? '',
            'brand' => $p['brand'] ?? '',
            'title' => $p['title'],
            'title_bn' => $p['title_bn'],
            'description' => $p['description'],
            'description_bn' => $p['description_bn'],
            'poriman' => $p['poriman'],
            'poriman_bn' => $p['poriman_bn'],
            'unit_type' => $p['unit_type'] ?: 'kg',
            'category_id' => $p['category_id'],
            'category_name' => $p['category_name'],
            'category_name_bn' => $p['category_name_bn'],
            'image' => $p['image'],
            'cost_price' => $costPrice,
            'dp_price' => $dpPrice,
            'mrp_price' => $mrpPrice,
            'price' => $finalPrice,
            'old_price' => ($mrpPrice > $finalPrice) ? $mrpPrice : null,
            'stock' => intval($currStock),
            'in_stock' => $currStock > 0 ? 1 : 0,
            'is_available' => ($isAvailableInLocation && $currStock > 0),
            'is_available_in_location' => $isAvailableInLocation,
            'batch_number' => $p['batch_number'] ?? '',
            'exp_date' => $p['exp_date'] ?? '',
            'is_bogo' => $isBogo,
            'bogo_text' => $bogoText,
            'bogo_text_bn' => $bogoTextBn,
            'discount_badge' => $discountBadge,
            'discount_badge_bn' => $discountBadgeBn,
            'discounts' => $applicableDiscounts
        ];
    }

    // 4. Calculate Delivery Charge based on Active Delivery Rules
    $orderAmount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
    $deliveryCharge = 60; // default fallback

    $rulesStmt = $pdo->query("SELECT * FROM delivery_charge_rules WHERE is_active = 1 AND target_type = 'website' ORDER BY priority ASC");
    $deliveryRules = $rulesStmt->fetchAll();

    foreach ($deliveryRules as $r) {
        if ($r['based_on'] === 'order_amount') {
            $minVal = floatval($r['min_value']);
            $maxVal = floatval($r['max_value']);
            if ($orderAmount >= $minVal && ($maxVal <= 0 || $orderAmount <= $maxVal)) {
                $deliveryCharge = floatval($r['charge']);
                break;
            }
        }
    }

    jsonResponse([
        'status' => 'success',
        'categories' => $categories,
        'products' => $products,
        'delivery_charge' => $deliveryCharge,
        'discount_rules' => $discountRules,
        'resolved_location' => $resolvedLoc,
        'total_products' => count($products)
    ]);

} catch (Exception $e) {
    jsonResponse([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 500);
}
