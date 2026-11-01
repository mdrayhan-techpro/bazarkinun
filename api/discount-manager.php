<?php
// api/discount-manager.php
// Full Management & Execution API for Multi-Tier Discounts, BOGO, Quantity Tiers & Coupons

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// Enforce Management authorization for administrative actions (validate_coupon remains public for checkout)
if ($action !== 'validate_coupon') {
    requirePortalAuth(['admin', 'author', 'manager', 'management']);
}

try {
    // 1. Get All Discount Rules
    if ($action === 'get_rules') {
        requirePermission('discounts.manage');
        $sql = "
            SELECT 
                r.*,
                p.title as target_product_title,
                p.title_bn as target_product_title_bn,
                p.product_code as target_product_code,
                c.name as target_category_name,
                rp.title as reward_product_title,
                rp.title_bn as reward_product_title_bn,
                rp.product_code as reward_product_code
            FROM discount_rules r
            LEFT JOIN products p ON r.target_type = 'single_product' AND r.target_id = p.id
            LEFT JOIN categories c ON r.target_type = 'category' AND r.target_id = c.id
            LEFT JOIN products rp ON r.reward_product_id = rp.id
            ORDER BY r.is_active DESC, r.priority ASC, r.id DESC
        ";
        $rules = $pdo->query($sql)->fetchAll();
        jsonResponse(['status' => 'success', 'rules' => $rules]);
    }

    // 2. Save / Update Discount Rule
    if ($action === 'save_rule') {
        requirePermission('discounts.manage');
        $ruleId = !empty($input['id']) ? intval($input['id']) : null;
        $title = trim($input['title'] ?? '');
        $titleBn = trim($input['title_bn'] ?? '');
        $badgeText = trim($input['badge_text'] ?? '');
        $discountType = $input['discount_type'] ?? 'fixed_amount'; // fixed_amount, percentage, bogo, tiered_qty, cart_spend, coupon, combo_bundle, free_gift, free_delivery
        $targetType = $input['target_type'] ?? 'single_product'; // single_product, category, batch, entire_cart, multi_products
        $targetId = !empty($input['target_id']) ? intval($input['target_id']) : null;
        
        $targetIds = $input['target_ids'] ?? [];
        if (is_string($targetIds)) {
            $targetIds = json_decode($targetIds, true) ?: [];
        }
        $formattedTargetIds = [];
        if (!empty($targetIds) && is_array($targetIds)) {
            foreach ($targetIds as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $formattedTargetIds[] = [
                        'id' => intval($t['id']),
                        'qty' => max(1, intval($t['qty'] ?? 1))
                    ];
                } elseif (is_numeric($t)) {
                    $formattedTargetIds[] = [
                        'id' => intval($t),
                        'qty' => 1
                    ];
                }
            }
        }
        $targetIdsJson = !empty($formattedTargetIds) ? json_encode($formattedTargetIds) : null;

        $discountValue = floatval($input['discount_value'] ?? 0);
        $minQtyRequired = floatval($input['min_qty_required'] ?? 1);
        $rewardType = $input['reward_type'] ?? 'discount_amount';
        $rewardProductId = !empty($input['reward_product_id']) ? intval($input['reward_product_id']) : null;
        $rewardQty = floatval($input['reward_qty'] ?? 1);
        $rewardDiscountPercent = floatval($input['reward_discount_percent'] ?? 100);
        $maxRewardLimit = (isset($input['max_reward_limit']) && intval($input['max_reward_limit']) > 0) ? intval($input['max_reward_limit']) : null;
        $minCartAmount = floatval($input['min_cart_amount'] ?? 0);
        $customerTarget = $input['customer_target'] ?? 'all';
        $paymentMethodTarget = $input['payment_method_target'] ?? 'all';
        $couponCode = !empty($input['coupon_code']) ? strtoupper(trim($input['coupon_code'])) : null;
        $maxUsesTotal = !empty($input['max_uses_total']) ? intval($input['max_uses_total']) : null;
        $maxUsesPerUser = !empty($input['max_uses_per_user']) ? intval($input['max_uses_per_user']) : 1;
        $startDatetime = !empty($input['start_datetime']) ? $input['start_datetime'] : null;
        $endDatetime = !empty($input['end_datetime']) ? $input['end_datetime'] : null;
        $priority = intval($input['priority'] ?? 1);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (empty($title)) {
            jsonResponse(['status' => 'error', 'message' => 'Rule Title is required!'], 400);
        }

        if ($discountType === 'coupon' && empty($couponCode)) {
            jsonResponse(['status' => 'error', 'message' => 'Coupon code is required for coupon discount type!'], 400);
        }

        if ($discountType === 'bogo' && empty($rewardProductId)) {
            $rewardProductId = $targetId; // Default BOGO to same product if not specified
        }

        if ($discountType === 'combo_bundle' || $discountType === 'free_gift') {
            $rewardType = 'free_product';
        } elseif ($discountType === 'free_delivery') {
            $rewardType = 'free_delivery';
        }

        if ($ruleId) {
            $stmt = $pdo->prepare("
                UPDATE discount_rules 
                SET title = ?, title_bn = ?, badge_text = ?, discount_type = ?, target_type = ?, target_id = ?, target_ids_json = ?, discount_value = ?, min_qty_required = ?, reward_type = ?, reward_product_id = ?, reward_qty = ?, reward_discount_percent = ?, max_reward_limit = ?, min_cart_amount = ?, customer_target = ?, payment_method_target = ?, coupon_code = ?, max_uses_total = ?, max_uses_per_user = ?, start_datetime = ?, end_datetime = ?, priority = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $title, $titleBn, $badgeText, $discountType, $targetType, $targetId, $targetIdsJson, $discountValue, $minQtyRequired,
                $rewardType, $rewardProductId, $rewardQty, $rewardDiscountPercent, $maxRewardLimit, $minCartAmount, $customerTarget, $paymentMethodTarget,
                $couponCode, $maxUsesTotal, $maxUsesPerUser, $startDatetime, $endDatetime, $priority, $isActive, $ruleId
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO discount_rules 
                (title, title_bn, badge_text, discount_type, target_type, target_id, target_ids_json, discount_value, min_qty_required, reward_type, reward_product_id, reward_qty, reward_discount_percent, max_reward_limit, min_cart_amount, customer_target, payment_method_target, coupon_code, max_uses_total, max_uses_per_user, start_datetime, end_datetime, priority, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, $titleBn, $badgeText, $discountType, $targetType, $targetId, $targetIdsJson, $discountValue, $minQtyRequired,
                $rewardType, $rewardProductId, $rewardQty, $rewardDiscountPercent, $maxRewardLimit, $minCartAmount, $customerTarget, $paymentMethodTarget,
                $couponCode, $maxUsesTotal, $maxUsesPerUser, $startDatetime, $endDatetime, $priority, $isActive
            ]);
            $ruleId = $pdo->lastInsertId();
        }

        jsonResponse(['status' => 'success', 'message' => 'Discount rule saved successfully!', 'rule_id' => $ruleId]);
    }

    // 3. Toggle Rule Status (Active / Inactive)
    if ($action === 'toggle_status') {
        requirePermission('discounts.manage');
        $ruleId = intval($input['id'] ?? 0);
        $status = intval($input['status'] ?? 0);
        $pdo->prepare("UPDATE discount_rules SET is_active = ? WHERE id = ?")->execute([$status, $ruleId]);
        jsonResponse(['status' => 'success', 'message' => 'Discount rule status updated!']);
    }

    // 4. Delete Discount Rule
    if ($action === 'delete_rule') {
        requirePermission('discounts.manage');
        $ruleId = intval($input['id'] ?? 0);
        $pdo->prepare("DELETE FROM discount_rules WHERE id = ?")->execute([$ruleId]);
        jsonResponse(['status' => 'success', 'message' => 'Discount rule deleted successfully!']);
    }

    // 5. Evaluate / Validate Coupon Code on Checkout Cart
    if ($action === 'validate_coupon') {
        $code = strtoupper(trim($_GET['code'] ?? $_GET['coupon_code'] ?? $input['coupon_code'] ?? $input['code'] ?? ''));
        $cartSubtotal = floatval($_GET['cart_total'] ?? $_GET['subtotal'] ?? $input['subtotal'] ?? $input['cart_total'] ?? 0);
        $customerPhone = trim($_GET['phone'] ?? $input['phone'] ?? '');

        if (empty($code)) {
            jsonResponse(['status' => 'error', 'message' => 'কুপন কোড লিখুন।'], 400);
        }

        $stmt = $pdo->prepare("
            SELECT * FROM discount_rules 
            WHERE discount_type = 'coupon' AND coupon_code = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $rule = $stmt->fetch();

        if (!$rule) {
            jsonResponse(['status' => 'error', 'message' => 'অবৈধ অথবা মেয়াদোত্তীর্ণ কুপন কোড!'], 404);
        }

        // Check date validity
        $now = date('Y-m-d H:i:s');
        if (!empty($rule['start_datetime']) && $now < $rule['start_datetime']) {
            jsonResponse(['status' => 'error', 'message' => 'কুপনটির মেয়াদ এখনো শুরু হয়নি।'], 400);
        }
        if (!empty($rule['end_datetime']) && $now > $rule['end_datetime']) {
            jsonResponse(['status' => 'error', 'message' => 'কুপনটির মেয়াদ শেষ হয়ে গেছে।'], 400);
        }

        // Check min cart amount
        if ($cartSubtotal < floatval($rule['min_cart_amount'])) {
            jsonResponse([
                'status' => 'error', 
                'message' => "এই কুপনটি ব্যবহার করতে সর্বনিম্ন ৳" . number_format($rule['min_cart_amount'], 2) . " এর অর্ডার প্রয়োজন।"
            ], 400);
        }

        // Calculate discount
        $discAmount = 0;
        $isPercent = ($rule['reward_type'] === 'discount_percent' || ($rule['discount_value'] > 0 && $rule['discount_value'] <= 100 && $rule['reward_type'] !== 'fixed_amount'));
        if ($isPercent) {
            $discAmount = round(($cartSubtotal * floatval($rule['discount_value'])) / 100, 2);
        } else {
            $discAmount = floatval($rule['discount_value']);
        }

        jsonResponse([
            'status' => 'success',
            'message' => "কুপন কোড '{$code}' সফলভাবে প্রযোজ্য হয়েছে! আপনি ৳{$discAmount} ছাড় পেয়েছেন।",
            'coupon_code' => $code,
            'discount_amount' => $discAmount,
            'discount_type' => $isPercent ? 'percentage' : 'fixed_amount',
            'min_cart_amount' => floatval($rule['min_cart_amount']),
            'rule' => $rule,
            'coupon' => [
                'code' => $code,
                'discount_amount' => $discAmount,
                'rule_id' => $rule['id'],
                'reward_type' => $rule['reward_type'],
                'discount_value' => $rule['discount_value'],
                'min_cart_amount' => $rule['min_cart_amount']
            ]
        ]);
    }

    // 6. Get Delivery Rules Matrix
    if ($action === 'get_delivery_rules') {
        requirePermission('delivery_rules.manage');
        $rules = $pdo->query("SELECT * FROM delivery_charge_rules ORDER BY priority ASC, id ASC")->fetchAll();
        jsonResponse(['status' => 'success', 'rules' => $rules]);
    }

    // 7. Save Delivery Rule
    if ($action === 'save_delivery_rule') {
        requirePermission('delivery_rules.manage');
        $ruleId = !empty($input['id']) ? intval($input['id']) : null;
        $basedOn = $input['based_on'] ?? 'order_amount'; // order_amount, weight, gps_distance, zone_location, delivery_speed
        $targetType = $input['target_type'] ?? 'website';
        $minValue = floatval($input['min_value'] ?? 0);
        $maxValue = floatval($input['max_value'] ?? 0);
        $charge = floatval($input['charge'] ?? 60);
        $zoneId = !empty($input['zone_id']) ? intval($input['zone_id']) : null;
        $priority = intval($input['priority'] ?? 1);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if ($ruleId) {
            $stmt = $pdo->prepare("
                UPDATE delivery_charge_rules 
                SET based_on = ?, target_type = ?, min_value = ?, max_value = ?, charge = ?, zone_id = ?, priority = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$basedOn, $targetType, $minValue, $maxValue, $charge, $zoneId, $priority, $isActive, $ruleId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO delivery_charge_rules (based_on, target_type, min_value, max_value, charge, zone_id, priority, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$basedOn, $targetType, $minValue, $maxValue, $charge, $zoneId, $priority, $isActive]);
            $ruleId = $pdo->lastInsertId();
        }

        jsonResponse(['status' => 'success', 'message' => 'Delivery rule saved successfully!', 'rule_id' => $ruleId]);
    }

    // 8. Delete Delivery Rule
    if ($action === 'delete_delivery_rule') {
        requirePermission('delivery_rules.manage');
        $ruleId = intval($input['id'] ?? 0);
        $pdo->prepare("DELETE FROM delivery_charge_rules WHERE id = ?")->execute([$ruleId]);
        jsonResponse(['status' => 'success', 'message' => 'Delivery rule deleted successfully!']);
    }

    jsonResponse(['status' => 'error', 'message' => 'Unknown action'], 400);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
