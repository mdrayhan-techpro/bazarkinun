<?php
// dashboard.php
// Super Admin Control Center for BazarKinun.com
require_once __DIR__ . '/config/db.php';

// User & Role Resolution for Unified Panel
$currentUserId = $_SESSION['admin_id'] ?? $_SESSION['manager_id'] ?? $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$currentUserName = $_SESSION['admin_name'] ?? $_SESSION['manager_name'] ?? $_SESSION['staff_name'] ?? $_SESSION['user_name'] ?? $_SESSION['user']['name'] ?? null;
$currentUserPhone = $_SESSION['admin_phone'] ?? $_SESSION['manager_phone'] ?? $_SESSION['staff_phone'] ?? $_SESSION['user_phone'] ?? $_SESSION['user']['phone'] ?? '';
$currentUserRole = strtolower($_SESSION['admin_role'] ?? $_SESSION['manager_role'] ?? $_SESSION['staff_role'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? $_SESSION['user']['role'] ?? '');

// Enforce valid management or staff session
$authorizedRoles = ['admin', 'super_admin', 'author', 'manager', 'management', 'operation', 'staff', 'rider'];
if (empty($currentUserId) || !in_array($currentUserRole, $authorizedRoles)) {
    header('Location: portal_login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php'));
    exit;
}

// Normalize role
if (in_array($currentUserRole, ['admin', 'super_admin', 'author'])) {
    $userRoleType = 'admin';
    $roleLabel = 'Super Admin';
    $roleBadge = '<span style="background: #ede9fe; color: #6d28d9; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-crown" style="color: #7c3aed;"></i> Super Admin</span>';
} elseif ($currentUserRole === 'management') {
    $userRoleType = 'management';
    $roleLabel = 'Management Board';
    $roleBadge = '<span style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-building-columns" style="color: #475569;"></i> Management Board</span>';
} elseif ($currentUserRole === 'operation') {
    $userRoleType = 'operation';
    $roleLabel = 'Operation';
    $roleBadge = '<span style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-gears" style="color: #d97706;"></i> Operation</span>';
} elseif ($currentUserRole === 'manager') {
    $userRoleType = 'manager';
    $roleLabel = 'Store Manager';
    $roleBadge = '<span style="background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-user-tie" style="color: #059669;"></i> Store Manager</span>';
} else {
    $userRoleType = 'staff';
    $roleLabel = 'Delivery Staff';
    $roleBadge = '<span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-motorcycle" style="color: #2563eb;"></i> Delivery Staff</span>';
}

$adminName = $currentUserName ?: 'Authorized User';
$authorName = $adminName;

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset(
        $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_phone'], $_SESSION['admin_role'],
        $_SESSION['author_id'], $_SESSION['author_name'], $_SESSION['author_phone'], $_SESSION['author_role'],
        $_SESSION['manager_id'], $_SESSION['manager_name'], $_SESSION['manager_phone'], $_SESSION['manager_role'],
        $_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_phone'], $_SESSION['staff_role'],
        $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_phone'], $_SESSION['user_role'],
        $_SESSION['role'], $_SESSION['user']
    );
    header('Location: portal_login.php');
    exit;
}

// Refresh active permissions from database for logged in user
$currentUserPermissions = getUserPermissions($pdo, true);

$tab = $_GET['tab'] ?? ($userRoleType === 'staff' ? 'staff_panel' : 'summary');

// Enforce granular role-based tab access
if ($userRoleType === 'staff') {
    $tab = 'staff_panel';
} elseif (!isSuperAdmin()) {
    $tabPermMap = [
        'summary' => null, // accessible to any logged in management staff
        'users' => 'view_staff',
        'management' => 'view_staff',
        'manager' => 'view_staff',
        'staff' => 'view_staff',
        'permission' => 'manage_roles_permissions',
        'customer' => 'view_customers',
        'category' => 'manage_categories',
        'product' => 'view_products',
        'order' => 'view_orders',
        'dp_sale' => 'view_products',
        'staff_panel' => 'rider_trip_status',
        'delivery_charge' => 'manage_delivery_rules',
        'audit_log' => 'view_audit_logs',
        'portal_info' => ['manage_contacts', 'manage_policies', 'manage_happy_customers', 'manage_broadcast', 'manage_sms_gateway', 'manage_payment_methods']
    ];

    if (isset($tabPermMap[$tab])) {
        $required = $tabPermMap[$tab];
        if (is_array($required)) {
            $hasAny = false;
            foreach ($required as $p) {
                if (hasPermission($p)) { $hasAny = true; break; }
            }
            if (!$hasAny) {
                $tab = 'summary';
            }
        } elseif ($required !== null && !hasPermission($required)) {
            $tab = 'summary';
        }
    }
}

// Staff Panel Action Triggers
if (isset($_GET['action'])) {
    $act = $_GET['action'];
    $actId = intval($_GET['id'] ?? 0);
    $subtabStaff = $_GET['subtab_staff'] ?? 'pending';
    $pendingType = $_GET['pending_type'] ?? 'cart';

    if ($act === 'staff_accept_order' && $actId > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'processing', rider_id = ?, rider_name = ?, rider_phone = ?, assigned_at = NOW() WHERE id = ?");
        $stmt->execute([$currentUserId ?: 1, $adminName, $currentUserPhone, $actId]);
        logAuditActivity($pdo, 'Order Accepted', "Staff {$adminName} accepted direct cart order #{$actId} via Staff Panel", $adminName, $userRoleType);
        header("Location: dashboard.php?tab=staff_panel&subtab_staff=delivery&msg=order_accepted");
        exit;
    }

    if ($act === 'staff_accept_custom_list' && $actId > 0) {
        $staffRiderId = $currentUserId ?: 1;
        $staffRiderName = $adminName ?: 'Delivery Staff';
        $staffRiderPhone = $currentUserPhone ?: '';

        // 1. Check custom_lists table
        $clStmt = $pdo->prepare("SELECT * FROM custom_lists WHERE id = ?");
        $clStmt->execute([$actId]);
        $foundCl = $clStmt->fetch();

        if ($foundCl) {
            $updCl = $pdo->prepare("UPDATE custom_lists SET is_accepted = 1, status = 'accepted' WHERE id = ?");
            $updCl->execute([$actId]);

            // Sync with orders table if matching order exists
            $code = $foundCl['list_code'] ?: $foundCl['title'];
            if (!empty($code)) {
                $pdo->prepare("UPDATE orders SET status = 'accepted', rider_id = ?, rider_name = ?, rider_phone = ?, assigned_at = NOW() WHERE (custom_list_id = ? OR order_number = ?)")
                    ->execute([$staffRiderId, $staffRiderName, $staffRiderPhone, $actId, $code]);
            }
        }

        // 2. Also check orders table (if $actId is an orders table ID)
        $ordStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $ordStmt->execute([$actId]);
        $foundOrd = $ordStmt->fetch();

        if ($foundOrd) {
            $updOrd = $pdo->prepare("UPDATE orders SET status = 'accepted', rider_id = ?, rider_name = ?, rider_phone = ?, assigned_at = NOW() WHERE id = ?");
            $updOrd->execute([$staffRiderId, $staffRiderName, $staffRiderPhone, $actId]);

            // Sync with custom_lists table if list exists
            $ordNum = $foundOrd['order_number'];
            if (!empty($ordNum)) {
                $pdo->prepare("UPDATE custom_lists SET is_accepted = 1, status = 'accepted' WHERE (list_code = ? OR title = ?)")
                    ->execute([$ordNum, $ordNum]);
            }
        }

        logAuditActivity($pdo, 'Custom Order Accepted', "Staff {$staffRiderName} accepted custom order #{$actId} via Staff Panel", $staffRiderName, $userRoleType);
        header("Location: dashboard.php?tab=staff_panel&subtab_staff=receive&msg=custom_accepted");
        exit;
    }

    if ($act === 'staff_ship_order' && $actId > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'delivering', rider_id = ?, rider_name = ?, rider_phone = ?, assigned_at = NOW() WHERE id = ?");
        $stmt->execute([$currentUserId ?: 1, $adminName, $currentUserPhone, $actId]);
        logAuditActivity($pdo, 'Order Shipped', "Staff {$adminName} started delivery for order #{$actId} via Staff Panel", $adminName, $userRoleType);
        header("Location: dashboard.php?tab=staff_panel&subtab_staff=delivery&msg=order_shipped");
        exit;
    }

    if ($act === 'staff_deliver_order' && $actId > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt->execute([$actId]);
        logAuditActivity($pdo, 'Order Delivered', "Staff {$adminName} marked order #{$actId} as delivered via Staff Panel", $adminName, $userRoleType);
        header("Location: dashboard.php?tab=staff_panel&subtab_staff=delivery&msg=order_delivered");
        exit;
    }
}

// Intelligent Hub & Zone Location Matching Helper Function
if (!function_exists('checkStaffLocationMatch')) {
    function checkStaffLocationMatch($assignedLocations, $item, $currentStaffId = null, &$matchedZone = null) {
        if ($currentStaffId && !empty($item['rider_id']) && $item['rider_id'] == $currentStaffId) {
            $matchedZone = 'Directly Assigned';
            return true;
        }
        if (empty($assignedLocations) || !is_array($assignedLocations)) {
            return false;
        }

        global $pdo;
        static $hubSubAreas = null;
        if ($hubSubAreas === null && isset($pdo)) {
            try {
                $hubSubAreas = $pdo->query("SELECT LOWER(police_station) as ps, LOWER(sub_area) as sub FROM hub_sub_areas")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                $hubSubAreas = [];
            }
        }

        static $zoneMap = null;
        if ($zoneMap === null && isset($pdo)) {
            try {
                $zRows = $pdo->query("SELECT LOWER(zone_name) as zn, LOWER(thana_list) as thanas, id FROM pricing_zones WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($zRows as $zr) {
                    $zoneMap[$zr['zn']] = [
                        'id' => (int)$zr['id'],
                        'thanas' => array_map('trim', explode(',', $zr['thanas']))
                    ];
                }
            } catch (Exception $e) {
                $zoneMap = [];
            }
        }

        $fullText = strtolower(
            ($item['customer_address'] ?? '') . ' ' . 
            ($item['police_station'] ?? '') . ' ' . 
            ($item['district'] ?? '') . ' ' . 
            ($item['division'] ?? '')
        );

        foreach ($assignedLocations as $loc) {
            $ps = strtolower(trim($loc['police_station'] ?? ''));
            $dist = strtolower(trim($loc['district'] ?? ''));

            if (empty($ps) && empty($dist)) continue;

            // Check if District word is present in address text
            $distMatched = empty($dist) || strpos($fullText, $dist) !== false;

            // Check if Police Station / Thana word is present in address text
            $psMatched = false;
            $matchedSubName = null;

            if (!empty($ps)) {
                if (strpos($fullText, $ps) !== false) {
                    $psMatched = true;
                } else {
                    $psWords = preg_split('/\s+/', $ps);
                    foreach ($psWords as $w) {
                        if (strlen($w) >= 4 && !in_array($w, ['east', 'west', 'sadar', 'north', 'south', 'model', 'thana']) && strpos($fullText, $w) !== false) {
                            $psMatched = true;
                            break;
                        }
                    }
                }

                // 1) Hub Sub-Area check (e.g. Uttara -> Sector 4, Bhatulia, Kamarpara, Sujabad, etc.)
                if (!$psMatched && !empty($hubSubAreas[$ps])) {
                    foreach ($hubSubAreas[$ps] as $sub) {
                        if (strpos($fullText, $sub) !== false) {
                            $psMatched = true;
                            $matchedSubName = ucwords($sub);
                            break;
                        }
                    }
                }

                // 2) Zone-level matching (if staff assigned to "Dhaka North", "Dhaka South", etc.)
                if (!$psMatched && !empty($zoneMap[$ps])) {
                    $targetZone = $zoneMap[$ps];
                    foreach ($targetZone['thanas'] as $zt) {
                        if (!empty($zt) && strpos($fullText, $zt) !== false) {
                            $psMatched = true;
                            $matchedSubName = ucwords($zt);
                            break;
                        }
                        if (!empty($hubSubAreas[$zt])) {
                            foreach ($hubSubAreas[$zt] as $sub) {
                                if (strpos($fullText, $sub) !== false) {
                                    $psMatched = true;
                                    $matchedSubName = ucwords($sub);
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } else {
                $psMatched = true;
            }

            // Both Thana/Hub & District matched
            if ($distMatched && $psMatched) {
                $label = $loc['police_station'];
                if ($matchedSubName) {
                    $label .= " ({$matchedSubName})";
                }
                $matchedZone = ($label . ', ' . $loc['district']);
                return true;
            }
        }

        return false;
    }
}

// Fetch Staff Panel Data
$staffCartOrders = [];
$staffCustomLists = [];
$staffReceiveLists = [];
$staffDeliveryOrders = [];
$myAssignedLocations = [];

if (in_array($tab, ['staff_panel', 'staff_ops', 'staff'])) {
    if ($currentUserId) {
        $stLocQuery = $pdo->prepare("SELECT id, district, police_station FROM staff_locations WHERE staff_id = ? ORDER BY id ASC");
        $stLocQuery->execute([$currentUserId]);
        $myAssignedLocations = $stLocQuery->fetchAll(PDO::FETCH_ASSOC);
    }

    $allStaffLocs = $pdo->query("SELECT id, district, police_station FROM staff_locations ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $cartStmt = $pdo->query("
        SELECT 
            o.id, o.order_number, o.invoice_number, o.customer_name, o.customer_phone,
            o.customer_address, o.police_station, o.district, o.division, o.order_type,
            o.rider_id, o.rider_name,
            CAST(o.total_amount AS FLOAT) as total_amount, CAST(o.delivery_charge AS FLOAT) as delivery_charge,
            o.status, o.html_content,
            DATE_FORMAT(o.created_at, '%b %d, %Y') as formatted_date_only,
            DATE_FORMAT(o.created_at, '%h:%i %p') as formatted_time_only,
            DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as formatted_date
        FROM orders o
        WHERE o.status = 'pending' AND (o.order_type = 'website' OR o.order_type IS NULL OR o.order_type = '' OR o.order_type = 'cart')
        ORDER BY o.id DESC
    ");
    $staffCartOrders = $cartStmt ? $cartStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $custListsStmt = $pdo->query("
        SELECT 
            cl.id, cl.list_code, cl.title, cl.customer_name, cl.customer_phone,
            cl.customer_address, cl.list_type, cl.images, cl.content_text, cl.status,
            0 as is_accepted,
            DATE_FORMAT(cl.created_at, '%b %d, %Y') as formatted_date_only,
            DATE_FORMAT(cl.created_at, '%h:%i %p') as formatted_time_only,
            DATE_FORMAT(cl.created_at, '%b %d, %Y %h:%i %p') as formatted_date
        FROM custom_lists cl
        WHERE (cl.is_accepted = 0 OR cl.is_accepted IS NULL)
          AND (cl.status IS NULL OR (cl.status != 'draft' AND cl.status != 'saved' AND cl.status != 'Draft'))
        ORDER BY cl.id DESC
    ");
    $staffCustomLists = $custListsStmt ? $custListsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Also include pending custom orders from orders table (written or uploaded)
    try {
        $customOrdersStmt = $pdo->query("
            SELECT 
                o.id, o.order_number as list_code, o.order_number as title, o.customer_name, o.customer_phone,
                o.customer_address, o.order_type as list_type, '' as images, o.html_content as content_text, o.status,
                0 as is_accepted,
                DATE_FORMAT(o.created_at, '%b %d, %Y') as formatted_date_only,
                DATE_FORMAT(o.created_at, '%h:%i %p') as formatted_time_only,
                DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as formatted_date
            FROM orders o
            WHERE o.status = 'pending' AND o.order_type IN ('text_list', 'uploaded', 'custom_list')
            ORDER BY o.id DESC
        ");
        $extraCustomOrders = $customOrdersStmt ? $customOrdersStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $existingCodes = array_flip(array_filter(array_column($staffCustomLists, 'list_code')));
        foreach ($extraCustomOrders as $eco) {
            if (!empty($eco['list_code']) && !isset($existingCodes[$eco['list_code']])) {
                $staffCustomLists[] = $eco;
                $existingCodes[$eco['list_code']] = true;
            }
        }
    } catch (Exception $e) {}

    $recListsStmt = $pdo->query("
        SELECT 
            cl.id, cl.list_code, cl.title, cl.customer_name, cl.customer_phone,
            cl.customer_address, cl.list_type, cl.images, cl.content_text, cl.status,
            1 as is_accepted,
            DATE_FORMAT(cl.created_at, '%b %d, %Y') as formatted_date_only,
            DATE_FORMAT(cl.created_at, '%h:%i %p') as formatted_time_only,
            DATE_FORMAT(cl.created_at, '%b %d, %Y %h:%i %p') as formatted_date
        FROM custom_lists cl
        WHERE cl.is_accepted = 1 AND (cl.is_processed = 0 OR cl.is_processed IS NULL)
        ORDER BY cl.id DESC
    ");
    $staffReceiveLists = $recListsStmt ? $recListsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Also include accepted custom orders from orders table ready for pricing & invoice
    try {
        $recOrdersStmt = $pdo->query("
            SELECT 
                o.id, o.order_number as list_code, o.order_number as title, o.customer_name, o.customer_phone,
                o.customer_address, o.order_type as list_type, '' as images, o.html_content as content_text, o.status,
                1 as is_accepted,
                o.rider_id, o.rider_name,
                DATE_FORMAT(o.created_at, '%b %d, %Y') as formatted_date_only,
                DATE_FORMAT(o.created_at, '%h:%i %p') as formatted_time_only,
                DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as formatted_date
            FROM orders o
            WHERE (o.status = 'accepted' OR o.status = 'processing' OR (o.status = 'invoice_created' AND (o.total_amount <= 0 OR o.total_amount IS NULL)))
              AND o.order_type IN ('text_list', 'uploaded', 'custom_list')
            ORDER BY o.id DESC
        ");
        $extraRecOrders = $recOrdersStmt ? $recOrdersStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $existingRecCodes = array_flip(array_filter(array_column($staffReceiveLists, 'list_code')));
        foreach ($extraRecOrders as $ero) {
            if (!empty($ero['list_code']) && !isset($existingRecCodes[$ero['list_code']])) {
                $staffReceiveLists[] = $ero;
                $existingRecCodes[$ero['list_code']] = true;
            }
        }
    } catch (Exception $e) {}

    $delivStmt = $pdo->query("
        SELECT 
            o.id, o.order_number, o.invoice_number, o.customer_name, o.customer_phone,
            o.customer_address, o.police_station, o.district, o.division, o.order_type,
            o.rider_id, o.rider_name,
            CAST(o.total_amount AS FLOAT) as total_amount, CAST(o.delivery_charge AS FLOAT) as delivery_charge,
            o.status, o.html_content,
            DATE_FORMAT(o.created_at, '%b %d, %Y') as formatted_date_only,
            DATE_FORMAT(o.created_at, '%h:%i %p') as formatted_time_only,
            DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as formatted_date
        FROM orders o
        WHERE o.status NOT IN ('pending', 'accepted') 
          AND NOT (o.status = 'invoice_created' AND (o.total_amount <= 0 OR o.total_amount IS NULL) AND o.order_type IN ('text_list', 'uploaded', 'custom_list'))
        ORDER BY o.id DESC
    ");
    $staffDeliveryOrders = $delivStmt ? $delivStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Decorate items with matched_zone for display
    $activeLocsForDecoration = ($userRoleType === 'staff') ? $myAssignedLocations : $allStaffLocs;
    foreach ($staffCartOrders as &$itemRef) {
        $zone = null;
        if (checkStaffLocationMatch($activeLocsForDecoration, $itemRef, $currentUserId, $zone)) {
            $itemRef['matched_zone'] = $zone;
        }
    }
    unset($itemRef);
    foreach ($staffCustomLists as &$itemRef) {
        $zone = null;
        if (checkStaffLocationMatch($activeLocsForDecoration, $itemRef, $currentUserId, $zone)) {
            $itemRef['matched_zone'] = $zone;
        }
    }
    unset($itemRef);
    foreach ($staffReceiveLists as &$itemRef) {
        $zone = null;
        if (checkStaffLocationMatch($activeLocsForDecoration, $itemRef, $currentUserId, $zone)) {
            $itemRef['matched_zone'] = $zone;
        }
    }
    unset($itemRef);
    foreach ($staffDeliveryOrders as &$itemRef) {
        $zone = null;
        if (checkStaffLocationMatch($activeLocsForDecoration, $itemRef, $currentUserId, $zone)) {
            $itemRef['matched_zone'] = $zone;
        }
    }
    unset($itemRef);

    // Optional admin/manager rider filter
    $selectedRiderFilter = isset($_GET['rider_filter']) && $_GET['rider_filter'] !== '' ? intval($_GET['rider_filter']) : null;
    if ($selectedRiderFilter) {
        $filterLocStmt = $pdo->prepare("SELECT id, district, police_station FROM staff_locations WHERE staff_id = ? ORDER BY id ASC");
        $filterLocStmt->execute([$selectedRiderFilter]);
        $filterLocs = $filterLocStmt->fetchAll(PDO::FETCH_ASSOC);

        $staffCartOrders = array_values(array_filter($staffCartOrders, function($o) use ($filterLocs, $selectedRiderFilter) {
            return checkStaffLocationMatch($filterLocs, $o, $selectedRiderFilter);
        }));
        $staffCustomLists = array_values(array_filter($staffCustomLists, function($cl) use ($filterLocs, $selectedRiderFilter) {
            return checkStaffLocationMatch($filterLocs, $cl, $selectedRiderFilter);
        }));
        $staffReceiveLists = array_values(array_filter($staffReceiveLists, function($rl) use ($filterLocs, $selectedRiderFilter) {
            return checkStaffLocationMatch($filterLocs, $rl, $selectedRiderFilter);
        }));
        $staffDeliveryOrders = array_values(array_filter($staffDeliveryOrders, function($d) use ($filterLocs, $selectedRiderFilter) {
            return checkStaffLocationMatch($filterLocs, $d, $selectedRiderFilter);
        }));
    } elseif ($userRoleType === 'staff') {
        // Automatically filter for logged in Delivery Staff by their assigned locations
        $staffCartOrders = array_values(array_filter($staffCartOrders, function($o) use ($myAssignedLocations, $currentUserId) {
            return checkStaffLocationMatch($myAssignedLocations, $o, $currentUserId);
        }));
        $staffCustomLists = array_values(array_filter($staffCustomLists, function($cl) use ($myAssignedLocations, $currentUserId) {
            return checkStaffLocationMatch($myAssignedLocations, $cl, $currentUserId);
        }));
        $staffReceiveLists = array_values(array_filter($staffReceiveLists, function($rl) use ($myAssignedLocations, $currentUserId) {
            return checkStaffLocationMatch($myAssignedLocations, $rl, $currentUserId);
        }));
        $staffDeliveryOrders = array_values(array_filter($staffDeliveryOrders, function($d) use ($myAssignedLocations, $currentUserId) {
            return checkStaffLocationMatch($myAssignedLocations, $d, $currentUserId);
        }));
    }
}

// 1. Fetch Comprehensive KPI Data
$orderStats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_count,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
        COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_count,
        COALESCE(SUM(CASE WHEN status = 'picked' THEN 1 ELSE 0 END), 0) as picked_count,
        COALESCE(SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END), 0) as out_for_delivery_count,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count,
        COALESCE(SUM(CASE WHEN order_type = 'custom_list' THEN 1 ELSE 0 END), 0) as custom_orders_count,
        COALESCE(SUM(CASE WHEN order_type != 'custom_list' THEN 1 ELSE 0 END), 0) as direct_orders_count
    FROM orders
")->fetch();

$totalOrders = $orderStats['total_orders'] ?? 0;
$totalRevenue = $orderStats['total_revenue'] ?? 0;
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$managementList = $pdo->query("SELECT * FROM users WHERE role IN ('management', 'admin') ORDER BY id ASC")->fetchAll();
$totalManagement = count($managementList);
$managerList = $pdo->query("SELECT * FROM users WHERE role = 'manager' ORDER BY id ASC")->fetchAll();
$staffList = $pdo->query("SELECT * FROM users WHERE role = 'rider' ORDER BY id DESC")->fetchAll();
foreach ($staffList as &$stf) {
    $locStmt = $pdo->prepare("SELECT id, district, police_station FROM staff_locations WHERE staff_id = ? ORDER BY id ASC");
    $locStmt->execute([$stf['id']]);
    $stf['locations'] = $locStmt->fetchAll();
}
unset($stf);
$totalManagers = count($managerList);
$totalStaff = count($staffList);
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

// Location / Police Station Performance Summary
$locationStats = $pdo->query("
    SELECT 
        COALESCE(NULLIF(TRIM(police_station), ''), 'Unassigned Zone') as police_station,
        COALESCE(NULLIF(TRIM(district), ''), 'Dhaka') as district,
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_orders,
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue,
        (SELECT COUNT(DISTINCT staff_id) FROM staff_locations sl WHERE sl.police_station = orders.police_station) as active_riders
    FROM orders
    GROUP BY police_station, district
    ORDER BY total_orders DESC, total_revenue DESC
")->fetchAll();

// Audit Logs
$auditLogs = [];
$totalLogs = 0;
$adminLogsCount = 0;
$managerLogsCount = 0;
$todayLogsCount = 0;
try {
    $auditLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 250")->fetchAll();
    $totalLogs = count($auditLogs);
    foreach ($auditLogs as $al) {
        if ($al['user_role'] === 'admin') $adminLogsCount++;
        if ($al['user_role'] === 'manager') $managerLogsCount++;
        if (date('Y-m-d', strtotime($al['created_at'])) === date('Y-m-d')) $todayLogsCount++;
    }
} catch (Exception $e) {
    $auditLogs = [];
}

// 2. Fetch Data for Current Tab
$ridersList = $pdo->query("SELECT id, name, phone FROM users WHERE role = 'rider' AND status = 'active' ORDER BY name ASC")->fetchAll();

// Customers
$customerList = $pdo->query("
    SELECT u.*, 
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id OR customer_phone = u.phone) as order_count,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE (user_id = u.id OR customer_phone = u.phone) AND status != 'cancelled') as total_spent
    FROM users u 
    WHERE u.role = 'customer' 
    ORDER BY u.id DESC
")->fetchAll();

// Categories
// 1.1 Role-Based Access Control (RBAC) Data
$rolePermissionsList = $pdo->query("SELECT * FROM role_permissions ORDER BY FIELD(role_key, 'admin', 'management', 'operation', 'manager', 'rider'), is_system DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$rolesMap = [];
foreach ($rolePermissionsList as $rp) {
    $rolesMap[$rp['role_key']] = $rp;
}

// 1.2 Internal Staff & Users
$internalUsersList = $pdo->query("
    SELECT id, name, username, phone, email, role, status, default_location, permissions, created_at 
    FROM users 
    WHERE role IN ('admin', 'management', 'operation', 'manager', 'rider') 
    ORDER BY FIELD(role, 'admin', 'management', 'operation', 'manager', 'rider'), id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$staffLocStmt = $pdo->prepare("SELECT id, district, police_station FROM staff_locations WHERE staff_id = ? ORDER BY id ASC");
foreach ($internalUsersList as &$u) {
    $staffLocStmt->execute([$u['id']]);
    $u['locations'] = $staffLocStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($u);

$moduleMeta = [
    'Dashboard & Analytics' => ['icon' => 'fa-chart-pie', 'color' => '#10b981'],
    'Users & Staff' => ['icon' => 'fa-users-gear', 'color' => '#6366f1'],
    'RBAC & Roles' => ['icon' => 'fa-user-shield', 'color' => '#8b5cf6'],
    'Customers' => ['icon' => 'fa-users', 'color' => '#0284c7'],
    'Categories' => ['icon' => 'fa-layer-group', 'color' => '#3b82f6'],
    'Products & Catalog' => ['icon' => 'fa-boxes-stacked', 'color' => '#2563eb'],
    'Inventory & Stocks' => ['icon' => 'fa-warehouse', 'color' => '#059669'],
    'Orders & Sales' => ['icon' => 'fa-cart-shopping', 'color' => '#d97706'],
    'DP Wholesale Sales' => ['icon' => 'fa-handshake', 'color' => '#b45309'],
    'Rider Logistics' => ['icon' => 'fa-person-biking', 'color' => '#16a34a'],
    'Delivery & Discounts' => ['icon' => 'fa-truck-fast', 'color' => '#0891b2'],
    'Portal Information' => ['icon' => 'fa-sliders', 'color' => '#059669'],
    'Security & Financial' => ['icon' => 'fa-shield-halved', 'color' => '#7c3aed'],
];

$systemModules = [];
try {
    $dbPermsList = $pdo->query("SELECT * FROM `permissions` WHERE status = 'active' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dbPermsList as $dp) {
        $mName = $dp['module'];
        if (!isset($systemModules[$mName])) {
            $meta = $moduleMeta[$mName] ?? ['icon' => 'fa-cube', 'color' => '#6366f1'];
            $systemModules[$mName] = [
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'permissions' => []
            ];
        }
        $systemModules[$mName]['permissions'][$dp['slug']] = [
            'name' => $dp['name'],
            'legacy_slug' => $dp['legacy_slug'] ?? '',
            'description' => $dp['description'] ?? '',
            'category' => $dp['category'] ?? 'action'
        ];
    }
} catch (Exception $e) {}

if (empty($systemModules)) {
    $systemModules = [
        'Staff & Riders' => [
            'icon' => 'fa-users-gear',
            'color' => '#6366f1',
            'permissions' => [
                'view_staff' => ['name' => 'View Staff & Riders Directory', 'legacy_slug' => 'view_staff', 'description' => ''],
                'create_staff' => ['name' => 'Create New Staff / Rider', 'legacy_slug' => 'create_staff', 'description' => ''],
                'edit_staff' => ['name' => 'Edit Staff Details & Locations', 'legacy_slug' => 'edit_staff', 'description' => ''],
                'toggle_staff_status' => ['name' => 'Lock / Unlock Staff Status', 'legacy_slug' => 'toggle_staff_status', 'description' => ''],
                'delete_staff' => ['name' => 'Delete Staff Account', 'legacy_slug' => 'delete_staff', 'description' => ''],
            ]
        ]
    ];
}

$categoryList = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
    FROM categories c 
    ORDER BY c.id ASC
")->fetchAll();

// Products
$productList = $pdo->query("
    SELECT p.*, c.name as category_name, c.name_bn as category_name_bn 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();

// Batches & Expiry Data
$batchesList = $pdo->query("
    SELECT 
        b.*,
        p.product_code,
        p.title as product_title,
        p.title_bn as product_title_bn,
        p.image as product_image,
        COALESCE(s.current_stock, 0) as current_stock,
        COALESCE(pr.selling_price, p.price) as selling_price,
        COALESCE(pr.cost_price, 0) as cost_price
    FROM product_batches b
    JOIN products p ON b.product_id = p.id
    LEFT JOIN product_stocks s ON b.id = s.batch_id
    LEFT JOIN product_pricing pr ON b.id = pr.batch_id
    ORDER BY b.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Pricing Matrix Data
$pricingList = $pdo->query("
    SELECT 
        pr.*,
        p.product_code,
        p.title as product_title,
        p.title_bn as product_title_bn,
        p.poriman,
        b.batch_number,
        z.zone_name,
        z.zone_code
    FROM product_pricing pr
    JOIN products p ON pr.product_id = p.id
    LEFT JOIN product_batches b ON pr.batch_id = b.id
    LEFT JOIN pricing_zones z ON pr.zone_id = z.id
    ORDER BY p.id DESC, pr.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Stock & Inventory Ledger Data
$stocksList = $pdo->query("
    SELECT 
        s.*,
        p.product_code,
        p.title as product_title,
        p.title_bn as product_title_bn,
        p.poriman,
        p.image,
        b.batch_number,
        z.zone_name
    FROM product_stocks s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN product_batches b ON s.batch_id = b.id
    LEFT JOIN pricing_zones z ON s.zone_id = z.id
    ORDER BY (s.district = 'All Bangladesh') DESC, s.district ASC, s.current_stock ASC, p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pricingZonesList = $pdo->query("SELECT * FROM pricing_zones ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Orders
$orderList = $pdo->query("
    SELECT o.*, 
        u.name as rider_name,
        DATE_FORMAT(o.created_at, '%b %d, %Y %h:%i %p') as formatted_date,
        col.list_type as custom_list_type,
        col.raw_text as custom_raw_text,
        col.image_urls as custom_image_urls
    FROM orders o
    LEFT JOIN users u ON o.rider_id = u.id
    LEFT JOIN custom_order_lists col ON col.order_id = o.id
    ORDER BY o.id DESC
")->fetchAll();

// Delivery Rules
$delType = $_GET['type'] ?? 'custom_list';
if ($delType === 'all') {
    $delRulesStmt = $pdo->query("SELECT * FROM `delivery_charge_rules` ORDER BY `priority` ASC, `id` ASC");
    $deliveryRulesList = $delRulesStmt->fetchAll();
} else {
    $delRulesStmt = $pdo->prepare("SELECT * FROM `delivery_charge_rules` WHERE `target_type` = ? OR `target_type` = 'all' ORDER BY `priority` ASC, `id` ASC");
    $delRulesStmt->execute([$delType]);
    $deliveryRulesList = $delRulesStmt->fetchAll();
}

// Discount Rules
$productLookupMap = [];
foreach ($productList as $p) {
    $productLookupMap[$p['id']] = $p['title'];
}

$discountRulesList = $pdo->query("
    SELECT 
        r.*,
        p.title as target_product_title,
        p.product_code as target_product_code,
        c.name as target_category_name,
        rp.title as reward_product_title,
        rp.product_code as reward_product_code
    FROM discount_rules r
    LEFT JOIN products p ON r.target_type = 'single_product' AND r.target_id = p.id
    LEFT JOIN categories c ON r.target_type = 'category' AND r.target_id = c.id
    LEFT JOIN products rp ON r.reward_product_id = rp.id
    ORDER BY r.is_active DESC, r.priority ASC, r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($discountRulesList as &$drItem) {
    $drItem['combo_products_text'] = '';
    if (!empty($drItem['target_ids_json'])) {
        $ids = json_decode($drItem['target_ids_json'], true);
        if (is_array($ids)) {
            $titles = [];
            foreach ($ids as $tid) {
                $itemId = is_array($tid) ? ($tid['id'] ?? 0) : $tid;
                $itemQty = is_array($tid) ? ($tid['qty'] ?? 1) : 1;
                $pName = $productLookupMap[$itemId] ?? ('Item #' . $itemId);
                $titles[] = ($itemQty > 1 ? ($itemQty . 'x ') : '') . $pName;
            }
            $drItem['combo_products_text'] = implode(' + ', $titles);
        }
    }
}
unset($drItem);

// DP Sales (Wholesale / Dealer Orders)
$dpSalesList = [];
$totalDpSalesRevenue = 0;
$totalDpProfit = 0;
$totalDpDue = 0;
try {
    $dpSalesList = $pdo->query("SELECT * FROM dp_sales ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dpSalesList as $dps) {
        $totalDpSalesRevenue += floatval($dps['total_amount'] ?? 0);
        $totalDpProfit += floatval($dps['total_profit'] ?? 0);
        $totalDpDue += floatval($dps['due_amount'] ?? 0);
    }
} catch (Exception $e) {}

// Portal Settings
$settingsRaw = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$subtab = $_GET['subtab'] ?? 'links_contacts';

// Auto-navigate to first authorized portal sub-tab if user lacks permission for current one
if (!isSuperAdmin()) {
    $portalSubtabPerms = [
        'links_contacts' => 'manage_contacts',
        'legal_policies' => 'manage_policies',
        'happy_customers' => 'manage_happy_customers',
        'broadcast_notices' => 'manage_broadcast',
        'sms_gateway' => 'manage_sms_gateway',
        'payment_methods' => 'manage_payment_methods'
    ];
    if (isset($portalSubtabPerms[$subtab]) && !hasPermission($portalSubtabPerms[$subtab])) {
        foreach ($portalSubtabPerms as $stKey => $perm) {
            if (hasPermission($perm)) {
                $subtab = $stKey;
                break;
            }
        }
    }
}

$portalPhone = $settingsRaw['hotline_phone'] ?? '01919-668960';
$altPhone = $settingsRaw['alt_phone'] ?? '01711-223344';
$whatsappNum = $settingsRaw['whatsapp_number'] ?? '01919668960';
$facebookUrl = $settingsRaw['facebook_url'] ?? 'https://facebook.com/bazardotcom';
$portalEmail = $settingsRaw['support_email'] ?? 'support@bazar.com';

$supportHoursEn = $settingsRaw['support_hours_en'] ?? ($settingsRaw['order_hours_en'] ?? '24/7 Support Center');
$supportHoursBn = $settingsRaw['support_hours_bn'] ?? ($settingsRaw['order_hours_bn'] ?? '২৪/৭ সাপোর্ট সেন্টার');

$officeAddressEn = $settingsRaw['office_address_en'] ?? ($settingsRaw['office_address'] ?? 'Uttara, Dhaka-1230');
$officeAddressBn = $settingsRaw['office_address_bn'] ?? 'উত্তরা, ঢাকা-১২৩০';

$taglineEn = $settingsRaw['tagline_en'] ?? 'Skip the market hassle and get 100% fresh, healthy groceries';
$taglineBn = $settingsRaw['tagline_bn'] ?? 'বাজারের ঝামেলা ভুলে এক ক্লিকেই ঘরে আনুন সতেজ নিত্যপণ্য; পরিবারের সুস্বাস্থ্যে বাজারের সেরা মান!';

$deliveryTimeEstimateEn = $settingsRaw['delivery_time_estimate_en'] ?? '20-35 mins';
$deliveryTimeEstimateBn = $settingsRaw['delivery_time_estimate_bn'] ?? '২০-৩৫ মিনিট';

$aboutUsEn = $settingsRaw['about_us_en'] ?? "Skip the market hassle—let the market come to your home.\n\nBazarKinun.com is a modern hyper-local grocery platform dedicated to sourcing farm-fresh produce, local fish, quality meat, and essential groceries directly from trusted wholesale markets to your doorstep.";
$aboutUsBn = $settingsRaw['about_us_bn'] ?? "বাজারে নয়, বাজার আসবে আপনার ঘরে।\n\nBazarKinun.com একটি আধুনিক হাইপার-লোকাল গ্রোসারি প্ল্যাটফর্ম। প্রতিদিনের টাটকা শাকসবজি, দেশি মাছ, মাংস ও খাঁটি মুদিপণ্য সরাসরি মাঠ ও নির্ভরযোগ্য পাইকারি বাজার থেকে বাছাই করে দ্রুততম সময়ে আপনার ঘরে পৌঁছে দেওয়াই আমাদের লক্ষ্য।";

$privacyPolicyEn = $settingsRaw['privacy_policy_en'] ?? "We value your trust and are committed to protecting your personal information. When you use BazarKinun.com, we only collect essential data required to process and deliver your grocery orders safely.";
$privacyPolicyBn = $settingsRaw['privacy_policy_bn'] ?? "আমরা আপনার বিশ্বাসের মূল্য দিই এবং আপনার ব্যক্তিগত তথ্য রক্ষা করতে প্রতিশ্রুতিবদ্ধ। BazarKinun.com ব্যবহার করার সময় আমরা শুধুমাত্র আপনার বাজার সরবরাহ নিশ্চিত করার জন্য প্রয়োজনীয় তথ্য সংগ্রহ করি।";

$termsConditionsEn = $settingsRaw['terms_conditions_en'] ?? "By placing an order on BazarKinun.com, you agree to our standard terms of service. All orders are subject to product availability and confirmation by our store managers.";
$termsConditionsBn = $settingsRaw['terms_conditions_bn'] ?? "BazarKinun.com এ অর্ডার করার মাধ্যমে আপনি আমাদের সাধারণ শর্তাবলীতে সম্মতি প্রদান করছেন। সমস্ত অর্ডার পণ্যের প্রাপ্যতা এবং আমাদের স্টোর ম্যানেজারের নিশ্চিতকরণের সাপেক্ষে কার্যকর হবে।";

$refundPolicyEn = $settingsRaw['refund_policy_en'] ?? "If you receive any damaged or unsatisfactory fresh items, you can inspect and return them immediately to our delivery rider at the doorstep for instant replacement or refund.";
$refundPolicyBn = $settingsRaw['refund_policy_bn'] ?? "কোনো পণ্য ক্ষতিগ্রস্ত বা মানসম্মত না হলে, আপনি ডেলিভারির সময়ই রাইডারের কাছে তাৎক্ষণিক ফেরত দিয়ে বদল বা রিফান্ড নিতে পারেন।";

$happyCustomersCount = $settingsRaw['happy_customers_count'] ?? '15,000+';
$satisfactionRate = $settingsRaw['satisfaction_rate'] ?? '99.4%';
$deliverySuccessRate = $settingsRaw['delivery_success_rate'] ?? '99.8%';

$broadcastEn = $settingsRaw['broadcast_notice_en'] ?? 'Welcome to BazarKinun.com! Free delivery on orders above ৳1000!';
$broadcastBn = $settingsRaw['broadcast_notice_bn'] ?? 'স্বাগতম BazarKinun.com এ! ১০০০ টাকার উপরে অর্ডারে ফ্রি ডেলিভারি!';
$broadcastActive = ($settingsRaw['broadcast_active'] ?? '1') === '1';

// SMS Gateway Settings
$smsGatewayEnabled = ($settingsRaw['sms_gateway_enabled'] ?? '0') === '1';
$smsGatewayProvider = $settingsRaw['sms_gateway_provider'] ?? 'greenweb';
$smsGatewayApiKey = $settingsRaw['sms_gateway_api_key'] ?? '';
$smsGatewaySenderId = $settingsRaw['sms_gateway_sender_id'] ?? '';
$smsGatewayCustomUrl = $settingsRaw['sms_gateway_custom_url'] ?? '';

// Payment Methods Settings
$payCodEnabled = ($settingsRaw['payment_cod_enabled'] ?? '1') === '1';
$payBkashEnabled = ($settingsRaw['payment_bkash_enabled'] ?? '1') === '1';
$payBkashNumber = $settingsRaw['payment_bkash_number'] ?? '01332563212';
$payBkashType = $settingsRaw['payment_bkash_type'] ?? 'Personal (Send Money)';
$payNagadEnabled = ($settingsRaw['payment_nagad_enabled'] ?? '1') === '1';
$payNagadNumber = $settingsRaw['payment_nagad_number'] ?? '01332563212';
$payNagadType = $settingsRaw['payment_nagad_type'] ?? 'Personal (Send Money)';

// Happy Customers (OHC) Reviews
try {
    $ohcList = $pdo->query("SELECT * FROM `happy_customers` ORDER BY `id` ASC")->fetchAll();
} catch (Exception $e) {
    $ohcList = [];
}

// Broadcast Announcements & Counts
try {
    $broadcastNotices = $pdo->query("SELECT * FROM `broadcast_notices` ORDER BY `id` DESC")->fetchAll();
    $staffCount = $pdo->query("SELECT count(*) FROM users WHERE role IN ('manager', 'rider', 'admin')")->fetchColumn() ?: 3;
    $customerCount = $pdo->query("SELECT count(DISTINCT customer_phone) FROM orders WHERE customer_phone IS NOT NULL AND customer_phone != ''")->fetchColumn() ?: 3;
} catch (Exception $e) {
    $broadcastNotices = [];
    $staffCount = 3;
    $customerCount = 3;
}

if (!function_exists('renderRichEditor')) {
    function renderRichEditor($id, $label, $initialValue) {
        $formattedVal = $initialValue;
        if (strpos($formattedVal, '<') === false) {
            $paragraphs = explode("\n\n", trim($formattedVal));
            $htmlParts = [];
            foreach ($paragraphs as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $htmlParts[] = '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                }
            }
            $formattedVal = !empty($htmlParts) ? implode('', $htmlParts) : '<p><br></p>';
        }
        ob_start();
        ?>
        <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
            <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13.5px;"><?= $label ?></label>
            <div class="rich-editor-wrapper" id="wrapper-<?= $id ?>">
                <div class="rich-editor-toolbar">
                    <button type="button" class="rich-btn" onclick="formatDoc('<?= $id ?>', 'bold')" title="Bold (Ctrl+B)"><b>B</b></button>
                    <button type="button" class="rich-btn" onclick="formatDoc('<?= $id ?>', 'italic')" title="Italic (Ctrl+I)"><i>I</i></button>
                    <button type="button" class="rich-btn" onclick="formatDoc('<?= $id ?>', 'underline')" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button type="button" class="rich-btn" onclick="formatHeading('<?= $id ?>')" title="Heading / Subtitle"><b>H</b></button>
                    <button type="button" class="rich-btn" onclick="formatQuote('<?= $id ?>')" title="Quote Callout" style="color: #ea580c;"><i class="fa-solid fa-quote-left"></i> Quote</button>
                    <button type="button" class="rich-btn" onclick="formatNotice('<?= $id ?>')" title="Notice / Callout Box" style="color: #0d9488;"><i class="fa-solid fa-circle-info"></i> Notice</button>
                    <span style="display: inline-block; width: 1px; height: 16px; background: #cbd5e1; margin: 0 4px;"></span>
                    <button type="button" class="rich-btn" onclick="formatDoc('<?= $id ?>', 'insertUnorderedList')" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                    <button type="button" class="rich-btn" onclick="formatDoc('<?= $id ?>', 'insertOrderedList')" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
                    <button type="button" class="rich-btn" onclick="formatHighlight('<?= $id ?>')" title="Highlight Text" style="color: #d97706;"><i class="fa-solid fa-highlighter"></i></button>
                    <button type="button" class="rich-btn" onclick="formatDoc('<?= $id ?>', 'removeFormat')" title="Clear Formatting" style="color: #64748b;"><i class="fa-solid fa-eraser"></i></button>
                    <span style="display: inline-block; width: 1px; height: 16px; background: #cbd5e1; margin: 0 4px;"></span>
                    <button type="button" class="rich-btn" onclick="toggleHtmlView('<?= $id ?>')" title="View / Edit HTML Source" style="font-size: 11.5px; font-weight: 800; color: #475569; font-family: monospace;">&lt; / &gt; HTML</button>
                </div>
                <div id="editor-<?= $id ?>" class="rich-editor-content" contenteditable="true" oninput="syncEditor('<?= $id ?>')"><?= $formattedVal ?></div>
                <textarea id="<?= $id ?>" class="form-control" style="display: none; width: 100%; min-height: 140px; border: none; border-radius: 0; font-family: monospace; font-size: 13px; padding: 14px 16px; resize: vertical;" oninput="syncFromTextarea('<?= $id ?>')"><?= htmlspecialchars($formattedVal) ?></textarea>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(generateCsrfToken()) ?>">
    <title>Admin Panel - BazarKinun.com</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/bazarkinundotcom_logo.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bangladesh Locations Dataset -->
    <script src="js/bd_locations.js"></script>
    <script>window.csrfToken = <?= json_encode(generateCsrfToken()) ?>;</script>
    <style>
        :root {
            --primary-color: #7c3aed; /* Modern Purple */
            --primary-hover: #6d28d9;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e2e8f0;
            --sidebar-width: 260px;
            --sidebar-mini-width: 76px;
            --accent-color: #f59e0b;
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * { box-sizing: border-box; }

        /* Disable number input spinner arrows */
        input.no-spin-input::-webkit-outer-spin-button,
        input.no-spin-input::-webkit-inner-spin-button,
        #dp-discount::-webkit-outer-spin-button,
        #dp-discount::-webkit-inner-spin-button,
        #dp-delivery-charge::-webkit-outer-spin-button,
        #dp-delivery-charge::-webkit-inner-spin-button,
        #dp-paid-amount::-webkit-outer-spin-button,
        #dp-paid-amount::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input.no-spin-input[type=number],
        #dp-discount,
        #dp-delivery-charge,
        #dp-paid-amount {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Layout Container with Sidebar */
        .auth-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Left Sidebar */
        .auth-sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1050;
            box-shadow: 2px 0 16px rgba(0, 0, 0, 0.03);
            transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1), transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            height: 72px;
            box-sizing: border-box;
            user-select: none;
        }

        .sidebar-brand-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            width: 100%;
            transition: opacity 0.2s;
        }
        .sidebar-brand-btn:hover {
            opacity: 0.9;
        }

        .sidebar-logo {
            height: 42px;
            width: 42px;
            object-fit: contain;
            flex-shrink: 0;
            transition: transform 0.2s;
        }
        .sidebar-brand-btn:hover .sidebar-logo {
            transform: scale(1.05);
        }

        .sidebar-brand-text {
            display: flex;
            flex-direction: column;
            white-space: nowrap;
        }

        .brand-title {
            font-size: 19px;
            font-weight: 900;
            margin: 0;
            line-height: 1.1;
        }

        .brand-badge {
            font-size: 8.5px;
            font-weight: 800;
            color: #7c3aed;
            letter-spacing: 2px;
        }

        .sidebar-close-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
        }

        .sidebar-nav-wrap {
            flex: 1;
            overflow-y: auto;
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-nav-wrap::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-nav-wrap::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .sidebar-group-title {
            font-size: 10.5px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 8px 12px 6px;
            margin-top: 6px;
            white-space: nowrap;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 14px;
            color: #475569;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.2s ease;
            position: relative;
            white-space: nowrap;
        }

        .sidebar-link i {
            font-size: 15px;
            width: 20px;
            text-align: center;
            color: #64748b;
            transition: color 0.2s;
            flex-shrink: 0;
        }

        .sidebar-link:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .sidebar-link:hover i {
            color: #7c3aed;
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%);
            color: #6d28d9;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.08);
        }
        .sidebar-link.active i {
            color: #7c3aed;
        }

        /* Sidebar Dropdown Menu */
        .sidebar-dropdown {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .sidebar-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 14px;
            color: #475569;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.2s ease;
            cursor: pointer;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            font-family: inherit;
            box-sizing: border-box;
            white-space: nowrap;
        }

        .sidebar-dropdown-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .sidebar-dropdown-btn i.menu-icon {
            font-size: 15px;
            width: 20px;
            text-align: center;
            color: #64748b;
            transition: color 0.2s;
            flex-shrink: 0;
        }

        .sidebar-dropdown-btn:hover i.menu-icon {
            color: #7c3aed;
        }

        .sidebar-dropdown-btn .dropdown-arrow {
            margin-left: auto;
            font-size: 11px;
            color: #94a3b8;
            transition: transform 0.25s ease;
        }

        .sidebar-dropdown.open .sidebar-dropdown-btn .dropdown-arrow {
            transform: rotate(180deg);
        }

        .sidebar-dropdown.active .sidebar-dropdown-btn {
            color: #6d28d9;
            font-weight: 800;
            background: #f5f3ff;
        }
        .sidebar-dropdown.active .sidebar-dropdown-btn i.menu-icon {
            color: #7c3aed;
        }

        .sidebar-submenu {
            display: none;
            flex-direction: column;
            gap: 3px;
            padding: 5px 0 5px 14px;
            margin-left: 10px;
            border-left: 2px solid #ede9fe;
            animation: submenuFadeIn 0.2s ease;
        }

        @keyframes submenuFadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sidebar-dropdown.open .sidebar-submenu {
            display: flex;
        }

        .sidebar-submenu .sidebar-sublink {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 12px;
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .sidebar-submenu .sidebar-sublink:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .sidebar-submenu .sidebar-sublink i {
            font-size: 13.5px;
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-submenu .sidebar-sublink.active {
            background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%);
            color: #6d28d9;
            font-weight: 800;
        }
        .sidebar-submenu .sidebar-sublink.active i {
            color: #7c3aed;
        }

        .sidebar-sublink .sub-count {
            margin-left: auto;
            background: #f1f5f9;
            color: #64748b;
            font-size: 10.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 999px;
            min-width: 16px;
            text-align: center;
        }

        .sidebar-submenu .sidebar-sublink.active .sub-count {
            background: #7c3aed;
            color: #ffffff;
        }

        .nav-count {
            margin-left: auto;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
        }
        .sidebar-link.active .nav-count {
            background: #7c3aed;
            color: #ffffff;
        }

        .page-title-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ede9fe;
            color: #6d28d9;
            font-size: 13.5px;
            font-weight: 800;
            padding: 2px 11px;
            border-radius: 999px;
            vertical-align: middle;
            margin-left: 8px;
            line-height: 1.3;
        }

        .link-portal {
            font-size: 13px;
        }

        /* Collapsed / Mini Sidebar state */
        .auth-sidebar.collapsed {
            width: var(--sidebar-mini-width);
        }

        .auth-main-wrapper.sidebar-collapsed {
            margin-left: var(--sidebar-mini-width);
            width: calc(100% - var(--sidebar-mini-width));
        }

        .auth-sidebar.collapsed .sidebar-brand-text,
        .auth-sidebar.collapsed .sidebar-group-title,
        .auth-sidebar.collapsed .sidebar-link span,
        .auth-sidebar.collapsed .sidebar-dropdown-btn span,
        .auth-sidebar.collapsed .nav-count,
        .auth-sidebar.collapsed .dropdown-arrow,
        .auth-sidebar.collapsed .user-meta,
        .auth-sidebar.collapsed .sidebar-submenu {
            display: none !important;
        }

        .auth-sidebar.collapsed .sidebar-header {
            padding: 14px 8px;
            justify-content: center;
        }

        .auth-sidebar.collapsed .sidebar-brand-btn {
            justify-content: center;
            gap: 0;
        }

        .auth-sidebar.collapsed .sidebar-logo {
            height: 42px;
            width: 42px;
        }

        .auth-sidebar.collapsed .sidebar-nav-wrap {
            padding: 16px 8px;
            align-items: center;
        }

        .auth-sidebar.collapsed .sidebar-menu {
            width: 100%;
            align-items: center;
        }

        .auth-sidebar.collapsed .sidebar-link,
        .auth-sidebar.collapsed .sidebar-dropdown-btn {
            padding: 12px;
            justify-content: center;
            width: 46px;
            height: 46px;
            margin: 0 auto;
        }

        .auth-sidebar.collapsed .sidebar-link i,
        .auth-sidebar.collapsed .sidebar-dropdown-btn i.menu-icon {
            font-size: 18px;
            width: auto;
            margin: 0;
        }

        .auth-sidebar.collapsed .sidebar-footer {
            padding: 10px 8px;
        }

        .auth-sidebar.collapsed .user-profile-card {
            padding: 6px;
            justify-content: center;
            gap: 0;
        }
        .auth-sidebar.collapsed .btn-logout {
            display: none;
        }

        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
            transition: all 0.28s ease;
        }
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
        }

        .user-profile-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #ede9fe;
            color: #7c3aed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .user-meta {
            flex: 1;
            min-width: 0;
        }
        .user-name {
            display: block;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role-label {
            font-size: 11px;
            color: #7c3aed;
            font-weight: 700;
            display: block;
        }

        .btn-logout {
            color: #ef4444;
            text-decoration: none;
            padding: 6px;
            border-radius: 6px;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .btn-logout:hover {
            background: #fee2e2;
        }

        /* Right Content Area */
        .auth-main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: calc(100% - var(--sidebar-width));
        }

        /* Mobile Floating Sidebar Toggle */
        .mobile-floating-toggle {
            display: none;
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #4f46e5;
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            border: none;
            cursor: pointer;
            z-index: 1045;
            transition: transform 0.2s;
        }
        .mobile-floating-toggle:active {
            transform: scale(0.92);
        }

        /* Staff Operations Card Grid */
        .card-list-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }
        .staff-card {
            background: white;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.025);
            border: 1px solid #e2e8f0;
            border-left: 4px solid #10b981;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .staff-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.06);
        }
        .staff-card-thumb {
            width: 60px;
            height: 60px;
            background: #ecfdf5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #f1f5f9;
        }
        .staff-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .staff-card-thumb i {
            font-size: 24px;
            color: #047857;
        }
        .staff-card-body {
            flex: 1;
            min-width: 0;
        }
        .staff-card-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .staff-card-meta {
            color: #4b5563;
            font-size: 14px;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .staff-card-meta a {
            color: #10b981;
            text-decoration: none;
            font-weight: 700;
        }
        .staff-card-meta a:hover {
            text-decoration: underline;
        }
        .staff-card-sub {
            color: #6b7280;
            font-size: 13px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .staff-card-action {
            margin-left: 20px;
            flex-shrink: 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .auth-page-content {
            padding: 26px 28px 60px;
            flex: 1;
            box-sizing: border-box;
            max-width: 1500px;
            width: 100%;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            z-index: 1040;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .auth-sidebar {
                transform: translateX(-100%);
            }
            .auth-sidebar.open {
                transform: translateX(0);
            }
            .sidebar-close-btn {
                display: block;
            }
            .sidebar-overlay.open {
                display: block;
            }
            .auth-main-wrapper {
                margin-left: 0;
                width: 100%;
            }
            .mobile-floating-toggle {
                display: flex;
            }
            .auth-page-content {
                padding: 20px 16px 50px;
            }
        }

        @media (max-width: 768px) {
            .auth-page-content {
                padding: 16px 12px 60px;
            }
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)) !important;
                gap: 12px !important;
                margin-bottom: 20px !important;
            }
            .kpi-card {
                padding: 14px 12px !important;
                gap: 10px !important;
            }
            .kpi-icon {
                width: 42px !important;
                height: 42px !important;
                font-size: 18px !important;
            }
            .kpi-info h3 {
                font-size: 20px !important;
            }
            .filter-row, .table-filter-bar, .search-filter-wrapper {
                flex-wrap: wrap !important;
                gap: 10px !important;
            }
            .admin-table, table.data-table, #orders-table, #invoices-table, #products-table {
                min-width: 600px !important;
            }
            .table-responsive, .table-container, .auth-table-card {
                width: 100% !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }
            .custom-modal-backdrop > div {
                width: 95% !important;
                max-width: 95% !important;
                margin: 10px auto !important;
                max-height: 92vh !important;
            }
        }

        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
            }
            .kpi-card {
                padding: 12px 10px !important;
                flex-direction: column !important;
                align-items: flex-start !important;
            }
            .kpi-info h3 {
                font-size: 18px !important;
            }
        }

        @media (max-width: 374px) {
            .auth-page-content {
                padding: 12px 8px 60px;
            }
            .kpi-grid {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }
        }

        @media (min-width: 2560px) {
            .auth-page-content {
                max-width: 2200px !important;
                padding: 36px 40px 80px !important;
            }
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
                gap: 24px !important;
            }
            .kpi-card {
                padding: 28px !important;
            }
            .kpi-icon {
                width: 64px !important;
                height: 64px !important;
                font-size: 28px !important;
            }
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .kpi-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: 0.25s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 18px -3px rgba(139, 92, 246, 0.08);
            border-color: #d8b4fe;
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .kpi-info h4 {
            margin: 0 0 4px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .kpi-info h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            color: var(--text-dark);
        }

        /* Content Sections */
        .panel-box {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            background: #fafafa;
        }

        .panel-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.25s;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
        }
        .btn-action:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }

        table.data-table th {
            background: #f8fafc;
            color: #475569;
            padding: 14px 18px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }

        table.data-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }

        table.data-table tr:hover {
            background-color: #fdfbf7;
        }

        /* Badges */
        .badge-role {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        .badge-rider { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-manager { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-admin { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-active { background: #ecfdf5; color: #047857; }
        .status-inactive { background: #fee2e2; color: #b91c1c; }

        .btn-icon {
            background: white;
            border: 1px solid var(--border-color);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            transition: 0.2s;
            font-size: 13px;
        }
        .btn-icon:hover {
            background: #ede9fe;
            color: var(--primary-color);
            border-color: #c4b5fd;
        }
        .btn-icon.delete:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fca5a5;
        }

        .btn-rule-action {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 13px;
        }
        .btn-rule-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-box {
            background: white;
            border-radius: 16px;
            max-width: calc(620px + 4cm);
            width: 100%;
            max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        .form-control {
            padding: 10px 14px;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
            transition: 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
        }

        /* Rich Text Editor Styles */
        .rich-editor-wrapper {
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            background: white;
            transition: all 0.2s ease;
        }
        .rich-editor-wrapper:focus-within {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
        }
        .rich-editor-toolbar {
            display: flex;
            gap: 4px;
            padding: 7px 10px;
            background: #f8fafc;
            border-bottom: 1.5px solid var(--border-color);
            align-items: center;
            flex-wrap: wrap;
            user-select: none;
        }
        .rich-btn {
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            padding: 5px 9px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
            line-height: 1;
        }
        .rich-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .rich-btn:active {
            background: #cbd5e1;
        }
        .rich-editor-content {
            min-height: 140px;
            max-height: 380px;
            padding: 14px 16px;
            outline: none;
            font-size: 14.5px;
            line-height: 1.7;
            color: #1e293b;
            overflow-y: auto;
            font-family: inherit;
        }
        .rich-editor-content p {
            margin: 0 0 10px 0;
        }
        .rich-editor-content blockquote {
            border-left: 3.5px solid #8b5cf6;
            padding: 8px 14px;
            margin: 10px 0;
            background: #f5f3ff;
            color: #5b21b6;
            border-radius: 0 8px 8px 0;
            font-style: italic;
        }
        .rich-editor-content .editor-notice {
            background: #e0f2fe;
            border: 1.5px solid #bae6fd;
            border-radius: 8px;
            padding: 10px 14px;
            margin: 10px 0;
            color: #0369a1;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        /* Mobile Styles */
        .auth-mobile-btn {
            display: none;
            background: transparent;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }

        @media (max-width: 1100px) {
            .auth-desktop-only { display: none !important; }
            .auth-mobile-btn { display: flex !important; }
        }
    </style>
</head>
<body>

    <div class="auth-layout">
        <!-- 1. LEFT SIDEBAR -->
        <aside class="auth-sidebar" id="auth-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand-btn" onclick="toggleSidebarCollapse()" title="Click to collapse / expand sidebar">
                    <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" class="sidebar-logo" onerror="this.src='https://placehold.co/120x46?text=BazarKinun.com';">
                    <div class="sidebar-brand-text">
                        <h1 class="brand-title"><span style="color: #15803d;">Bazar</span><span style="color: #f97316;">Kinun</span><span style="color: #15803d;">.com</span></h1>
                        <span class="brand-badge" style="<?= $userRoleType === 'management' ? 'background: #334155; color: #fff;' : ($userRoleType === 'operation' ? 'background: #d97706; color: #fff;' : ($userRoleType === 'manager' ? 'background: #059669; color: #fff;' : ($userRoleType === 'staff' ? 'background: #2563eb; color: #fff;' : ''))) ?>"><?= strtoupper($roleLabel) ?></span>
                    </div>
                </div>
                <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()">&times;</button>
            </div>

            <div class="sidebar-nav-wrap">
                <div class="sidebar-group-title">MAIN NAVIGATION</div>
                <nav class="sidebar-menu">
                    <?php if ($userRoleType === 'staff'): ?>
                        <!-- Delivery Staff Navigation -->
                        <a href="dashboard.php?tab=staff_panel" class="sidebar-link <?= in_array($tab, ['staff_panel', 'staff_ops', 'staff']) ? 'active' : '' ?>" title="Delivery Operations">
                            <i class="fa-solid fa-motorcycle"></i>
                            <span>Delivery Operations</span>
                        </a>
                    <?php else: ?>
                        <!-- Admin & Manager Navigation -->
                        <a href="dashboard.php?tab=summary" class="sidebar-link <?= $tab === 'summary' ? 'active' : '' ?>" title="Summary">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Summary</span>
                        </a>

                        <?php if (hasPermission('view_staff')): ?>
                        <!-- UNIFIED USERS SINGLE LINK -->
                        <a href="dashboard.php?tab=users" class="sidebar-link <?= in_array($tab, ['users', 'management', 'manager', 'staff']) ? 'active' : '' ?>" title="Users">
                            <i class="fa-solid fa-users-gear"></i>
                            <span>Users</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('manage_roles_permissions')): ?>
                        <a href="dashboard.php?tab=permission" class="sidebar-link <?= $tab === 'permission' ? 'active' : '' ?>" title="Permission">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Permission</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('view_customers')): ?>
                        <a href="dashboard.php?tab=customer" class="sidebar-link <?= $tab === 'customer' ? 'active' : '' ?>" title="Customer">
                            <i class="fa-solid fa-users"></i>
                            <span>Customer</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('manage_categories')): ?>
                        <a href="dashboard.php?tab=category" class="sidebar-link <?= $tab === 'category' ? 'active' : '' ?>" title="Category">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Category</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('view_products')): ?>
                        <a href="dashboard.php?tab=product" class="sidebar-link <?= $tab === 'product' ? 'active' : '' ?>" title="Product">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Product</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('view_orders')): ?>
                        <a href="dashboard.php?tab=order" class="sidebar-link <?= $tab === 'order' ? 'active' : '' ?>" title="Order">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>Order</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('view_products') || hasPermission('view_orders')): ?>
                        <a href="dashboard.php?tab=dp_sale" class="sidebar-link <?= $tab === 'dp_sale' ? 'active' : '' ?>" title="DP Sale (Dealer / Wholesale Sales)">
                            <i class="fa-solid fa-handshake"></i>
                            <span>DP Sale</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('rider_trip_status')): ?>
                        <a href="dashboard.php?tab=staff_panel" class="sidebar-link <?= in_array($tab, ['staff_panel', 'staff_ops', 'staff']) ? 'active' : '' ?>" title="Staff Panel">
                            <i class="fa-solid fa-motorcycle"></i>
                            <span>Staff Panel</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('manage_delivery_rules') || hasPermission('manage_discounts')): ?>
                        <a href="dashboard.php?tab=delivery_charge" class="sidebar-link <?= $tab === 'delivery_charge' ? 'active' : '' ?>" title="DC & Discount (Delivery Charge & Discount)">
                            <i class="fa-solid fa-truck-fast"></i>
                            <span>DC & Discount</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('manage_contacts') || hasPermission('manage_policies') || hasPermission('manage_happy_customers') || hasPermission('manage_broadcast') || hasPermission('manage_sms_gateway') || hasPermission('manage_payment_methods')): ?>
                        <a href="dashboard.php?tab=portal_info" class="sidebar-link <?= $tab === 'portal_info' ? 'active' : '' ?>" title="Portal Info">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Portal Info</span>
                        </a>
                        <?php endif; ?>

                        <?php if (hasPermission('view_audit_logs')): ?>
                        <a href="dashboard.php?tab=audit_log" class="sidebar-link <?= $tab === 'audit_log' ? 'active' : '' ?>" title="Audit Log">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Audit Log</span>
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>

                <div class="sidebar-group-title" style="margin-top: 16px;">STORE &amp; PORTAL</div>
                <nav class="sidebar-menu">
                    <a href="index.php" class="sidebar-link link-portal" target="_blank" title="Customer Store">
                        <i class="fa-solid fa-store" style="color: #10b981;"></i>
                        <span>Customer Store</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer">
                <div class="user-profile-card">
                    <div class="user-avatar" style="<?= $userRoleType === 'management' ? 'background:#f1f5f9; color:#334155;' : ($userRoleType === 'operation' ? 'background:#fffbeb; color:#d97706;' : ($userRoleType === 'manager' ? 'background:#ecfdf5; color:#059669;' : ($userRoleType === 'staff' ? 'background:#eff6ff; color:#2563eb;' : ''))) ?>">
                        <?php if ($userRoleType === 'management'): ?>
                            <i class="fa-solid fa-building-columns"></i>
                        <?php elseif ($userRoleType === 'operation'): ?>
                            <i class="fa-solid fa-gears"></i>
                        <?php elseif ($userRoleType === 'manager'): ?>
                            <i class="fa-solid fa-user-tie"></i>
                        <?php elseif ($userRoleType === 'staff'): ?>
                            <i class="fa-solid fa-motorcycle"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-user-shield"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-meta">
                        <strong class="user-name"><?= htmlspecialchars($adminName) ?></strong>
                        <span class="user-role-label"><?= htmlspecialchars($roleLabel) ?></span>
                    </div>
                    <a href="dashboard.php?action=logout" class="btn-logout" title="Sign Out">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Overlay for mobile drawer -->
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- 2. RIGHT MAIN CONTENT AREA -->
        <div class="auth-main-wrapper">
            <!-- Mobile Floating Toggle Button -->
            <button type="button" class="mobile-floating-toggle" onclick="toggleSidebar()" title="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Main Page Content Body -->
            <main class="auth-page-content">

        <!-- TAB 0: EXECUTIVE SUMMARY & ANALYTICS -->
        <?php if ($tab === 'summary'): ?>
            <?php 
                $successRate = $totalOrders > 0 ? round(($orderStats['delivered_count'] / $totalOrders) * 100, 1) : 100;
                $activeOrdersCount = ($orderStats['delivered_count'] + $orderStats['processing_count'] + $orderStats['picked_count'] + $orderStats['out_for_delivery_count']);
                $aov = $activeOrdersCount > 0 ? round($totalRevenue / max(1, $activeOrdersCount), 0) : 0;
            ?>
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- Welcome Executive Header Banner -->
                <div style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%); border-radius: 18px; padding: 26px 30px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px; box-shadow: 0 10px 30px rgba(124, 58, 237, 0.22); position: relative; overflow: hidden;">
                    <div style="position: absolute; right: -20px; bottom: -30px; opacity: 0.12; font-size: 160px; color: white; pointer-events: none;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div style="position: relative; z-index: 1;">
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.18); padding: 5px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; margin-bottom: 10px; backdrop-filter: blur(4px);">
                            <i class="fa-solid fa-crown" style="color: #fde047;"></i> Super Admin Executive Suite
                        </div>
                        <h2 style="margin: 0 0 6px 0; font-size: 26px; font-weight: 900; letter-spacing: -0.5px;">
                            BazarKinun.com Business Summary &amp; Analytics
                        </h2>
                        <p style="margin: 0; opacity: 0.92; font-size: 14px; max-width: 650px; line-height: 1.5;">
                            Real-time overview of revenue performance, multi-zone delivery operations, user acquisition, and police station fulfillment metrics.
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; position: relative; z-index: 1;">
                        <a href="dashboard.php?tab=order" style="background: white; color: #6d28d9; text-decoration: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: 0.2s;">
                            <i class="fa-solid fa-bag-shopping"></i> Manage Orders
                        </a>
                        <a href="dashboard.php?tab=audit_log" style="background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 11px 18px; border-radius: 10px; font-weight: 700; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.3); transition: 0.2s;">
                            <i class="fa-solid fa-shield-halved"></i> View Audit Trail
                        </a>
                    </div>
                </div>

                <!-- 4 Primary KPI Summary Cards -->
                <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 0;">
                    <!-- 1. Total Revenue -->
                    <div class="kpi-card" style="border-left: 4px solid #10b981;">
                        <div class="kpi-icon" style="background: #ecfdf5; color: #059669;">
                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                        </div>
                        <div class="kpi-info" style="flex: 1;">
                            <h4>Total Net Revenue</h4>
                            <h3 style="color: #047857; font-size: 26px;">৳<?= number_format($totalRevenue, 2) ?></h3>
                            <span style="font-size: 12px; color: #64748b; font-weight: 600; display: block; margin-top: 2px;">
                                AOV: <strong>৳<?= number_format($aov, 0) ?></strong> per valid order
                            </span>
                        </div>
                    </div>

                    <!-- 2. Total Orders -->
                    <div class="kpi-card" style="border-left: 4px solid #6366f1;">
                        <div class="kpi-icon" style="background: #e0e7ff; color: #4338ca;">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div class="kpi-info" style="flex: 1;">
                            <h4>Total Orders Placed</h4>
                            <h3 style="color: #3730a3; font-size: 26px;"><?= number_format($totalOrders) ?></h3>
                            <span style="font-size: 12px; color: #16a34a; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;">
                                <i class="fa-solid fa-circle-check"></i> <?= $successRate ?>% Delivery Rate
                            </span>
                        </div>
                    </div>

                    <!-- 3. Total Users Ecosystem -->
                    <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
                        <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="kpi-info" style="flex: 1;">
                            <h4>Platform Users</h4>
                            <h3 style="color: #b45309; font-size: 26px;"><?= number_format($totalCustomers + $totalStaff + $totalManagers + $totalManagement) ?></h3>
                            <span style="font-size: 11.5px; color: #64748b; font-weight: 600; display: block; margin-top: 2px;">
                                <?= $totalCustomers ?> Cust &bull; <?= $totalStaff ?> Riders &bull; <?= $totalManagers ?> Mgrs
                            </span>
                        </div>
                    </div>

                    <!-- 4. Catalog Products & Categories -->
                    <div class="kpi-card" style="border-left: 4px solid #8b5cf6;">
                        <div class="kpi-icon" style="background: #ede9fe; color: #7c3aed;">
                            <i class="fa-solid fa-cubes"></i>
                        </div>
                        <div class="kpi-info" style="flex: 1;">
                            <h4>Inventory Catalog</h4>
                            <h3 style="color: #6d28d9; font-size: 26px;"><?= number_format($totalProducts) ?></h3>
                            <span style="font-size: 12px; color: #64748b; font-weight: 600; display: block; margin-top: 2px;">
                                across <?= count($categoryList) ?> Active Categories
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Order Status Pipeline Breakdown -->
                <div class="panel-box" style="padding: 24px 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-sliders" style="color: #7c3aed;"></i> Order Status Pipeline &amp; Channel Metrics
                            </h3>
                            <span style="font-size: 13px; color: #64748b;">Live distribution of customer orders across fulfillment stages and order submission types</span>
                        </div>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700; padding: 6px 12px; border-radius: 20px; font-size: 12px;">
                            <i class="fa-solid fa-arrows-rotate" style="margin-right: 5px; color: #8b5cf6;"></i> Live Tracking
                        </span>
                    </div>

                    <!-- 6 Status Pipeline Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px;">
                        
                        <!-- Pending -->
                        <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 16px; text-align: center;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 11.5px; font-weight: 800; color: #b45309; text-transform: uppercase;">Pending</span>
                                <i class="fa-solid fa-clock" style="color: #d97706; font-size: 14px;"></i>
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #92400e;"><?= intval($orderStats['pending_count'] ?? 0) ?></div>
                            <small style="color: #b45309; font-size: 11.5px;">Awaiting Manager Review</small>
                        </div>

                        <!-- Processing -->
                        <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; text-align: center;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 11.5px; font-weight: 800; color: #1d4ed8; text-transform: uppercase;">Processing</span>
                                <i class="fa-solid fa-spinner" style="color: #2563eb; font-size: 14px;"></i>
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #1e40af;"><?= intval($orderStats['processing_count'] ?? 0) ?></div>
                            <small style="color: #1d4ed8; font-size: 11.5px;">Being Packed &amp; Prepared</small>
                        </div>

                        <!-- Picked -->
                        <div style="background: #faf5ff; border: 1.5px solid #e9d5ff; border-radius: 12px; padding: 16px; text-align: center;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 11.5px; font-weight: 800; color: #7e22ce; text-transform: uppercase;">Picked</span>
                                <i class="fa-solid fa-box-open" style="color: #9333ea; font-size: 14px;"></i>
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #6b21a8;"><?= intval($orderStats['picked_count'] ?? 0) ?></div>
                            <small style="color: #7e22ce; font-size: 11.5px;">Ready for Rider Handover</small>
                        </div>

                        <!-- Out for Delivery -->
                        <div style="background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 16px; text-align: center;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 11.5px; font-weight: 800; color: #c2410c; text-transform: uppercase;">In Transit</span>
                                <i class="fa-solid fa-motorcycle" style="color: #ea580c; font-size: 14px;"></i>
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #9a3412;"><?= intval($orderStats['out_for_delivery_count'] ?? 0) ?></div>
                            <small style="color: #c2410c; font-size: 11.5px;">On the Way to Doorstep</small>
                        </div>

                        <!-- Delivered -->
                        <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px; text-align: center;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 11.5px; font-weight: 800; color: #15803d; text-transform: uppercase;">Delivered</span>
                                <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 14px;"></i>
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #166534;"><?= intval($orderStats['delivered_count'] ?? 0) ?></div>
                            <small style="color: #15803d; font-size: 11.5px;">Successfully Fulfilled</small>
                        </div>

                        <!-- Cancelled -->
                        <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 16px; text-align: center;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span style="font-size: 11.5px; font-weight: 800; color: #b91c1c; text-transform: uppercase;">Cancelled</span>
                                <i class="fa-solid fa-circle-xmark" style="color: #dc2626; font-size: 14px;"></i>
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #991b1b;"><?= intval($orderStats['cancelled_count'] ?? 0) ?></div>
                            <small style="color: #b91c1c; font-size: 11.5px;">Rejected or Aborted</small>
                        </div>

                    </div>

                    <!-- Channel / Type Summary Box -->
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </div>
                            <div>
                                <strong style="color: #0f172a; font-size: 14.5px; display: block;">Standard Store Cart Orders</strong>
                                <span style="font-size: 13px; color: #64748b;"><?= intval($orderStats['direct_orders_count'] ?? 0) ?> orders placed directly from product catalog</span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #fdf4ff; color: #c026d3; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <div>
                                <strong style="color: #0f172a; font-size: 14.5px; display: block;">Custom List Orders (Handwritten / Photo)</strong>
                                <span style="font-size: 13px; color: #64748b;"><?= intval($orderStats['custom_orders_count'] ?? 0) ?> custom uploaded / typed grocery lists</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Police Station & Location Performance Breakdown Table -->
                <div class="panel-box" style="padding: 24px 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-map-location-dot" style="color: #2563eb;"></i> Police Station &amp; Upazila Performance Breakdown
                            </h3>
                            <span style="font-size: 13px; color: #64748b;">Order volume, delivered revenue, and assigned delivery riders grouped by Police Station / Thana</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="position: relative;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
                                <input type="text" id="loc-search-input" onkeyup="filterLocationTable()" placeholder="Search Thana / District..." style="padding: 8px 12px 8px 34px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; width: 220px; outline: none; font-family: inherit;">
                            </div>
                            <a href="dashboard.php?tab=staff" class="btn-action" style="background: #2563eb; color: white; text-decoration: none; padding: 8px 14px; font-size: 13px;">
                                <i class="fa-solid fa-user-plus"></i> Assign Riders
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" id="location-summary-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Police Station / Thana</th>
                                    <th>District</th>
                                    <th>Total Orders</th>
                                    <th>Delivered</th>
                                    <th>Success Rate</th>
                                    <th>Total Revenue</th>
                                    <th>Assigned Riders</th>
                                    <th style="text-align: right;">Coverage Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($locationStats)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">
                                            <i class="fa-solid fa-location-dot" style="font-size: 28px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                            No order data recorded yet across police stations.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $lidx = 1; foreach ($locationStats as $ls): ?>
                                        <?php 
                                            $lSuccess = $ls['total_orders'] > 0 ? round(($ls['delivered_orders'] / $ls['total_orders']) * 100, 1) : 0;
                                            $hasRiders = intval($ls['active_riders'] ?? 0) > 0;
                                        ?>
                                        <tr class="loc-row">
                                            <td style="color: #64748b; font-weight: 700;"><?= $lidx++ ?></td>
                                            <td>
                                                <strong style="color: #0f172a; font-size: 14px; display: flex; align-items: center; gap: 7px;">
                                                    <i class="fa-solid fa-location-dot" style="color: #ef4444; font-size: 13px;"></i>
                                                    <span class="loc-thana-name"><?= htmlspecialchars($ls['police_station']) ?></span>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="loc-dist-name" style="color: #475569; font-weight: 600; font-size: 13px;"><?= htmlspecialchars($ls['district']) ?></span>
                                            </td>
                                            <td>
                                                <strong style="color: #1e293b; font-size: 14px;"><?= number_format($ls['total_orders']) ?></strong>
                                            </td>
                                            <td>
                                                <span style="color: #15803d; font-weight: 700;"><?= number_format($ls['delivered_orders']) ?></span>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div style="flex: 1; height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden; min-width: 50px;">
                                                        <div style="width: <?= min(100, $lSuccess) ?>%; height: 100%; background: <?= $lSuccess >= 80 ? '#10b981' : ($lSuccess >= 50 ? '#f59e0b' : '#ef4444') ?>; border-radius: 4px;"></div>
                                                    </div>
                                                    <span style="font-size: 12px; font-weight: 700; color: #475569;"><?= $lSuccess ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="color: #047857; font-size: 14px;">৳<?= number_format($ls['total_revenue'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($hasRiders): ?>
                                                    <span class="badge" style="background: #ede9fe; color: #6d28d9; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-motorcycle"></i> <?= $ls['active_riders'] ?> Assigned
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: #fef2f2; color: #b91c1c; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-triangle-exclamation"></i> 0 Riders
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if ($hasRiders): ?>
                                                    <span class="badge-status status-active" style="font-size: 11.5px;">
                                                        <i class="fa-solid fa-circle" style="font-size: 7px;"></i> Covered
                                                    </span>
                                                <?php else: ?>
                                                    <a href="dashboard.php?tab=staff" style="text-decoration: none; font-size: 12px; color: #2563eb; font-weight: 700;">+ Assign Staff</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions Shortcuts & Recent Feed Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                    
                    <!-- Quick Shortcuts Box -->
                    <div class="panel-box" style="padding: 24px;">
                        <h3 style="margin: 0 0 16px 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Quick Author Operations
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <a href="dashboard.php?tab=product" style="text-decoration: none; padding: 12px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 10px; transition: 0.2s;" onmouseover="this.style.background='#ede9fe'; this.style.borderColor='#c4b5fd';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                                <i class="fa-solid fa-plus-circle" style="color: #7c3aed; font-size: 16px;"></i>
                                <span>Add Product</span>
                            </a>
                            <a href="dashboard.php?tab=portal_info&subtab=broadcast_notices" style="text-decoration: none; padding: 12px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 10px; transition: 0.2s;" onmouseover="this.style.background='#eff6ff'; this.style.borderColor='#bfdbfe';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                                <i class="fa-solid fa-bullhorn" style="color: #2563eb; font-size: 16px;"></i>
                                <span>Broadcast Notice</span>
                            </a>
                            <a href="dashboard.php?tab=delivery_charge" style="text-decoration: none; padding: 12px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 10px; transition: 0.2s;" onmouseover="this.style.background='#f0fdf4'; this.style.borderColor='#bbf7d0';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                                <i class="fa-solid fa-truck" style="color: #16a34a; font-size: 16px;"></i>
                                <span>Delivery Rules</span>
                            </a>
                            <a href="dashboard.php?tab=audit_log" style="text-decoration: none; padding: 12px 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 10px; transition: 0.2s;" onmouseover="this.style.background='#fffbeb'; this.style.borderColor='#fde68a';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                                <i class="fa-solid fa-shield-halved" style="color: #d97706; font-size: 16px;"></i>
                                <span>Security Logs</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent System Activity Fast Widget -->
                    <div class="panel-box" style="padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-clock-rotate-left" style="color: #64748b;"></i> Recent Audit Trail Feed
                            </h3>
                            <a href="dashboard.php?tab=audit_log" style="text-decoration: none; font-size: 12.5px; color: #7c3aed; font-weight: 700;">View All (<?= $totalLogs ?>)</a>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php if (empty($auditLogs)): ?>
                                <p style="color: #94a3b8; font-size: 13px; margin: 0;">No audit events recorded yet.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($auditLogs, 0, 4) as $ral): ?>
                                    <div style="display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                                        <div style="width: 28px; height: 28px; border-radius: 6px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 12px; margin-top: 2px; flex-shrink: 0;">
                                            <i class="fa-solid fa-bolt"></i>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 6px;">
                                                <strong style="color: #0f172a; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($ral['action']) ?></strong>
                                                <small style="color: #94a3b8; font-size: 11px; white-space: nowrap;"><?= date('h:i A', strtotime($ral['created_at'])) ?></small>
                                            </div>
                                            <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.35;"><?= htmlspecialchars($ral['details'] ?? '') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>

                <!-- TAB 1: UNIFIED USERS DIRECTORY -->
        <?php elseif (in_array($tab, ['users', 'management', 'manager', 'staff'])): ?>
            <?php 
                $totalInternalUsersCount = count($internalUsersList);
                $defaultFilter = in_array($tab, ['management', 'manager', 'staff']) ? $tab : 'all';
                $adminCount = count(array_filter($internalUsersList, fn($u) => $u['role'] === 'admin'));
                $mngCount = count(array_filter($internalUsersList, fn($u) => $u['role'] === 'management'));
                $mgrCount = count(array_filter($internalUsersList, fn($u) => $u['role'] === 'manager'));
                $operCount = count(array_filter($internalUsersList, fn($u) => $u['role'] === 'operation'));
                $riderCount = count(array_filter($internalUsersList, fn($u) => in_array($u['role'], ['rider', 'staff'])));
            ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Header Banner -->
                <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h2 style="margin: 0 0 4px 0; font-size: 22px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-users-gear" style="color: #6366f1;"></i>
                            Internal Staff &amp; Users Directory <span class="page-title-badge"><?= $totalInternalUsersCount ?></span>
                        </h2>
                        <span style="font-size: 13.5px; color: #64748b;">
                            Directory of internal staff, management officers, and delivery riders
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <?php if (hasPermission('create_staff')): ?>
                        <button type="button" onclick="openAddUserModal()" class="btn-action" style="background: #4f46e5; color: white; border: none; padding: 10px 20px; font-weight: 800; font-size: 13.5px; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25); transition: 0.2s;">
                            <i class="fa-solid fa-user-plus"></i> + Add User
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Unified Filter Toolbar -->
                <div style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 16px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                    
                    <!-- Role Filter Pills -->
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-right: 4px;">Filter Role:</span>
                        <button type="button" class="user-role-filter-btn active" data-role="all" onclick="setUserRoleFilter('all', this)" style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 800; border: 1.5px solid #6366f1; background: #6366f1; color: white; cursor: pointer; transition: 0.2s;">
                            All (<?= count($internalUsersList) ?>)
                        </button>
                        <button type="button" class="user-role-filter-btn" data-role="admin" onclick="setUserRoleFilter('admin', this)" style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: 0.2s;">
                            Admin (<?= $adminCount ?>)
                        </button>
                        <button type="button" class="user-role-filter-btn" data-role="management" onclick="setUserRoleFilter('management', this)" style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: 0.2s;">
                            Management (<?= $mngCount ?>)
                        </button>
                        <button type="button" class="user-role-filter-btn" data-role="operation" onclick="setUserRoleFilter('operation', this)" style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: 0.2s;">
                            Operation (<?= $operCount ?>)
                        </button>
                        <button type="button" class="user-role-filter-btn" data-role="manager" onclick="setUserRoleFilter('manager', this)" style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: 0.2s;">
                            Manager (<?= $mgrCount ?>)
                        </button>
                        <button type="button" class="user-role-filter-btn" data-role="rider" onclick="setUserRoleFilter('rider', this)" style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; cursor: pointer; transition: 0.2s;">
                            Staff / Rider (<?= $riderCount ?>)
                        </button>
                    </div>

                    <!-- Search & Status Filter -->
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <select id="userStatusFilter" onchange="applyUnifiedUserFilter()" style="padding: 8px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 13px; font-weight: 700; background: white; color: #334155; outline: none;">
                            <option value="all">All Statuses</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                        <div style="position: relative;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
                            <input type="text" id="unifiedUserSearchInput" onkeyup="applyUnifiedUserFilter()" placeholder="Search name, phone, zone..." style="padding: 8px 14px 8px 34px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; outline: none; width: 240px; transition: 0.2s;" onfocus="this.style.borderColor='#6366f1';">
                        </div>
                    </div>
                </div>

                <!-- Unified Users Table Panel -->
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; padding: 0;">
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="unifiedUsersTable" style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1.5px solid #e2e8f0; background: #f8fafc;">
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; width: 60px;">ID</th>
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">User Profile</th>
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; width: 140px;">Role</th>
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Contact Details</th>
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase;">Assigned Locations</th>
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; text-align: center; width: 100px;">Status</th>
                                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; text-align: right; width: 170px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($internalUsersList)): ?>
                                    <tr><td colspan="7" style="padding: 40px; text-align: center; color: #94a3b8;">No staff or management users found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($internalUsersList as $u): ?>
                                        <?php 
                                            $uRole = strtolower($u['role'] ?? 'rider');
                                            $normalizedRole = in_array($uRole, ['rider', 'staff']) ? 'rider' : $uRole;
                                            $isAdm = ($uRole === 'admin');
                                            $isMng = ($uRole === 'management');
                                            $isMgr = ($uRole === 'manager');
                                            $isOper = ($uRole === 'operation');
                                            $isRdr = in_array($uRole, ['rider', 'staff']);
                                            
                                            $roleBadgeBg = $isAdm ? '#ede9fe' : ($isMng ? '#f1f5f9' : ($isMgr ? '#dbeafe' : ($isOper ? '#fef3c7' : '#dcfce7')));
                                            $roleBadgeColor = $isAdm ? '#7c3aed' : ($isMng ? '#0f172a' : ($isMgr ? '#1d4ed8' : ($isOper ? '#d97706' : '#15803d')));
                                            $roleBadgeBorder = $isAdm ? '#ddd6fe' : ($isMng ? '#cbd5e1' : ($isMgr ? '#bfdbfe' : ($isOper ? '#fde68a' : '#bbf7d0')));
                                            $roleTitle = $isAdm ? 'Super Admin' : ($isMng ? 'Management' : ($isMgr ? 'Store Manager' : ($isOper ? 'Operation' : 'Delivery Rider')));
                                            $roleIcon = $isAdm ? 'fa-crown' : ($isMng ? 'fa-building-columns' : ($isMgr ? 'fa-user-tie' : ($isOper ? 'fa-gears' : 'fa-person-biking')));
                                        ?>
                                        <tr class="unified-user-row" data-role="<?= htmlspecialchars($normalizedRole) ?>" data-status="<?= htmlspecialchars($u['status']) ?>" style="border-bottom: 1px solid #f1f5f9; transition: 0.15s;" onmouseover="this.style.background='#fafafb';" onmouseout="this.style.background='white';">
                                            <td style="padding: 14px 18px; font-weight: 800; color: #64748b;">#<?= $u['id'] ?></td>
                                            <td style="padding: 14px 18px;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= $roleBadgeBg ?>; color: <?= $roleBadgeColor ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; border: 1.5px solid <?= $roleBadgeBorder ?>;">
                                                        <i class="fa-solid <?= $roleIcon ?>"></i>
                                                    </div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-size: 14px; display: block;"><?= htmlspecialchars($u['name']) ?></strong>
                                                        <?php 
                                                            $userLocSummary = $u['default_location'];
                                                            if ($isRdr) {
                                                                if (!empty($u['locations'])) {
                                                                    $psNames = array_map(function($l) { 
                                                                        $txt = $l['police_station'];
                                                                        if (!empty($l['district']) && $l['district'] !== 'Dhaka') {
                                                                            $txt .= ' (' . $l['district'] . ')';
                                                                        }
                                                                        return $txt;
                                                                    }, $u['locations']);
                                                                    $userLocSummary = implode(', ', array_slice($psNames, 0, 2)) . (count($psNames) > 2 ? ' +' . (count($psNames) - 2) : '');
                                                                } else {
                                                                    $userLocSummary = $u['default_location'] ?: 'Unassigned Zone';
                                                                }
                                                            }
                                                        ?>
                                                        <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                                                            @<?= htmlspecialchars($u['username'] ?? $u['phone']) ?> &bull; <span style="color: #94a3b8;"><?= htmlspecialchars($userLocSummary ?: 'Corporate Hub') ?></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding: 14px 18px;">
                                                <span style="background: <?= $roleBadgeBg ?>; color: <?= $roleBadgeColor ?>; border: 1px solid <?= $roleBadgeBorder ?>; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                                                    <i class="fa-solid <?= $roleIcon ?>"></i>
                                                    <?= $roleTitle ?>
                                                </span>
                                            </td>
                                            <td style="padding: 14px 18px; font-size: 13px; color: #334155;">
                                                <div><code><?= htmlspecialchars($u['phone']) ?></code></div>
                                                <?php if (!empty($u['email'])): ?>
                                                    <div style="font-size: 11.5px; color: #94a3b8;"><?= htmlspecialchars($u['email']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 14px 18px;">
                                                <?php if ($isRdr): ?>
                                                    <?php if (!empty($u['locations'])): ?>
                                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 250px;">
                                                            <?php foreach ($u['locations'] as $loc): ?>
                                                                <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                                                    <i class="fa-solid fa-location-dot" style="font-size: 9px;"></i>
                                                                    <?= htmlspecialchars($loc['police_station']) ?><?= (!empty($loc['district']) && $loc['district'] !== 'Dhaka') ? ' (' . htmlspecialchars($loc['district']) . ')' : '' ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="color: #d97706; background: #fef3c7; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-circle-exclamation"></i> No Zone Assigned
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif (!empty($u['default_location'])): ?>
                                                    <span style="font-size: 12.5px; color: #475569; font-weight: 600;"><?= htmlspecialchars($u['default_location']) ?></span>
                                                <?php else: ?>
                                                    <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Head Office / Central</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 14px 18px; text-align: center;">
                                                <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 800; padding: 3px 10px; border-radius: 12px; background: <?= $u['status'] === 'active' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $u['status'] === 'active' ? '#15803d' : '#dc2626' ?>;">
                                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: <?= $u['status'] === 'active' ? '#15803d' : '#dc2626' ?>;"></span>
                                                    <?= ucfirst($u['status'] ?? 'active') ?>
                                                </span>
                                            </td>
                                            <td style="padding: 14px 18px; text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                                    <?php if ($isRdr && hasPermission('edit_staff')): ?>
                                                        <button type="button" onclick='openAssignLocationModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Assign Locations" style="border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 6px; padding: 5px 9px; font-size: 12px; font-weight: 700; color: #1d4ed8; cursor: pointer;">
                                                            <i class="fa-solid fa-location-dot"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('edit_staff')): ?>
                                                    <button type="button" onclick='openEditUserModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit User" style="border: 1px solid #fde68a; background: white; border-radius: 6px; padding: 5px 9px; font-size: 12px; font-weight: 700; color: #d97706; cursor: pointer; transition: 0.15s;" onmouseover="this.style.background='#fffbeb';" onmouseout="this.style.background='white';">
                                                        <i class="fa-solid fa-pen" style="color: #f59e0b;"></i>
                                                    </button>
                                                    <?php endif; ?>

                                                    <?php if ($u['id'] != 1): ?>
                                                        <?php if (hasPermission('toggle_staff_status')): ?>
                                                        <button type="button" onclick="toggleStaffStatus(<?= $u['id'] ?>)" title="Toggle Active/Inactive" style="border: 1px solid #e2e8f0; background: white; border-radius: 6px; padding: 5px 9px; font-size: 12px; font-weight: 700; color: <?= $u['status'] === 'active' ? '#ef4444' : '#10b981' ?>; cursor: pointer;">
                                                            <i class="fa-solid fa-power-off"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <?php if (hasPermission('delete_staff')): ?>
                                                        <button type="button" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')" title="Delete User" style="border: 1px solid #fecaca; background: white; border-radius: 6px; padding: 5px 9px; font-size: 12px; font-weight: 700; color: #dc2626; cursor: pointer; transition: 0.15s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='white';">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
                let currentRoleFilter = '<?= $defaultFilter ?>';

                function setUserRoleFilter(role, btn) {
                    currentRoleFilter = role;
                    document.querySelectorAll('.user-role-filter-btn').forEach(b => {
                        b.style.background = '#f8fafc';
                        b.style.color = '#475569';
                        b.style.borderColor = '#e2e8f0';
                        b.classList.remove('active');
                    });
                    btn.style.background = '#6366f1';
                    btn.style.color = 'white';
                    btn.style.borderColor = '#6366f1';
                    btn.classList.add('active');

                    applyUnifiedUserFilter();
                }

                function applyUnifiedUserFilter() {
                    const statusFilter = document.getElementById('userStatusFilter').value;
                    const searchQ = document.getElementById('unifiedUserSearchInput').value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#unifiedUsersTable tbody tr.unified-user-row');

                    rows.forEach(r => {
                        const rowRole = r.getAttribute('data-role');
                        const rowStatus = r.getAttribute('data-status');
                        const rowText = r.innerText.toLowerCase();

                        const matchRole = (currentRoleFilter === 'all' || rowRole === currentRoleFilter);
                        const matchStatus = (statusFilter === 'all' || rowStatus === statusFilter);
                        const matchSearch = (!searchQ || rowText.includes(searchQ));

                        r.style.display = (matchRole && matchStatus && matchSearch) ? '' : 'none';
                    });
                }

                // If loaded with a specific subtab, activate its filter pill on load
                document.addEventListener('DOMContentLoaded', () => {
                    if (currentRoleFilter !== 'all') {
                        const btn = document.querySelector(`.user-role-filter-btn[data-role="${currentRoleFilter}"]`);
                        if (btn) setUserRoleFilter(currentRoleFilter, btn);
                    }
                });
            </script>
<!-- TAB 2: CUSTOMERS -->
        <?php elseif ($tab === 'customer'): ?>
            <div class="panel-box">
                <div class="panel-header">
                    <div>
                        <h3><i class="fa-solid fa-user-group" style="color: var(--primary-color);"></i> Customer Directory <span class="page-title-badge" style="background: #e0f2fe; color: #0369a1;"><?= $totalCustomers ?></span></h3>
                        <span style="font-size: 13px; color: var(--text-muted);">Registered shoppers, purchase frequency, and locations</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Default Location</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customerList)): ?>
                                <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">No customers registered yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($customerList as $c): ?>
                                    <tr>
                                        <td>#<?= $c['id'] ?></td>
                                        <td>
                                            <a href="mng_customer_detail.php?id=<?= $c['id'] ?>&portal=author" style="font-weight: 800; color: #8b5cf6; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                                <i class="fa-solid fa-user-tag" style="font-size: 12px;"></i> <?= htmlspecialchars($c['name']) ?>
                                            </a>
                                        </td>
                                        <td><code><?= htmlspecialchars($c['phone']) ?></code></td>
                                        <td><?= htmlspecialchars($c['email'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($c['default_location'] ?: 'Not set') ?></td>
                                        <td><span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 12px; font-weight: 800;"><?= $c['order_count'] ?> Orders</span></td>
                                        <td><strong>৳<?= number_format($c['total_spent'], 2) ?></strong></td>
                                        <td style="color: var(--text-muted); font-size: 12.5px;"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                        <td style="text-align: right;">
                                            <a href="mng_customer_detail.php?id=<?= $c['id'] ?>&portal=author" style="padding: 6px 14px; background: #ede9fe; color: #7c3aed; border: 1px solid #ddd6fe; border-radius: 6px; font-weight: 700; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                                <i class="fa-solid fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

                        <!-- TAB: ROLE & PERMISSION MANAGEMENT -->
        <?php elseif ($tab === 'permission'): ?>
            <div style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- Header Banner -->
                <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); border-radius: 18px; padding: 26px 30px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px; box-shadow: 0 10px 30px rgba(49, 46, 129, 0.25); position: relative; overflow: hidden;">
                    <div style="position: absolute; right: -15px; bottom: -25px; opacity: 0.12; font-size: 150px; color: white; pointer-events: none;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div style="position: relative; z-index: 1;">
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.18); padding: 5px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; margin-bottom: 10px; backdrop-filter: blur(4px);">
                            <i class="fa-solid fa-key" style="color: #fde047;"></i> Role-Based Access Control (RBAC Hub)
                        </div>
                        <h2 style="margin: 0 0 6px 0; font-size: 26px; font-weight: 900; letter-spacing: -0.5px;">
                            Role &amp; Permission Delegation Center
                        </h2>
                        <p style="margin: 0; opacity: 0.92; font-size: 14px; max-width: 700px; line-height: 1.5;">
                            Configure and manage granular module privileges and capabilities for each system and custom role.
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; position: relative; z-index: 1;">
                        <button type="button" onclick="openAddRoleModal()" style="background: white; color: #312e81; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); cursor: pointer; transition: 0.2s;">
                            <i class="fa-solid fa-plus-circle"></i> Create New Role
                        </button>
                    </div>
                </div>

                <!-- ROLE PERMISSION CONFIGURATION -->
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; padding: 24px 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-shield-halved" style="color: #6366f1;"></i>
                                Role Permission Settings <span class="page-title-badge"><?= count($rolePermissionsList) ?></span>
                            </h2>
                        </div>
                        <span style="font-size: 13px; color: #64748b; font-weight: 600;">
                            Total Roles Configured: <strong><?= count($rolePermissionsList) ?></strong>
                        </span>
                    </div>

                    <!-- Roles Vertical List (লম্বালম্বি কার্ড) -->
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($rolePermissionsList as $rp): ?>
                            <?php 
                                $rKey = $rp['role_key'];
                                $rPerms = json_decode($rp['permissions'], true) ?? [];
                                $isSystemAdmin = ($rKey === 'admin');
                                $userCountForRole = count(array_filter($internalUsersList, fn($u) => $u['role'] === $rKey));

                                if ($isSystemAdmin) {
                                    $icon = 'fa-crown';
                                    $iconBg = '#ede9fe';
                                    $iconColor = '#7c3aed';
                                    $cardBg = '#faf5ff';
                                    $cardBorder = '#ddd6fe';
                                } elseif ($rKey === 'management') {
                                    $icon = 'fa-building-columns';
                                    $iconBg = '#f1f5f9';
                                    $iconColor = '#0f172a';
                                    $cardBg = '#ffffff';
                                    $cardBorder = '#e2e8f0';
                                } elseif ($rKey === 'operation') {
                                    $icon = 'fa-gears';
                                    $iconBg = '#fef3c7';
                                    $iconColor = '#d97706';
                                    $cardBg = '#ffffff';
                                    $cardBorder = '#e2e8f0';
                                } elseif ($rKey === 'manager') {
                                    $icon = 'fa-user-tie';
                                    $iconBg = '#dbeafe';
                                    $iconColor = '#1d4ed8';
                                    $cardBg = '#ffffff';
                                    $cardBorder = '#e2e8f0';
                                } elseif ($rKey === 'rider') {
                                    $icon = 'fa-person-biking';
                                    $iconBg = '#dcfce7';
                                    $iconColor = '#15803d';
                                    $cardBg = '#ffffff';
                                    $cardBorder = '#e2e8f0';
                                } else {
                                    $icon = 'fa-user-shield';
                                    $iconBg = '#f3e8ff';
                                    $iconColor = '#9333ea';
                                    $cardBg = '#ffffff';
                                    $cardBorder = '#e2e8f0';
                                }
                            ?>
                            <div style="border: 1.5px solid <?= $cardBorder ?>; border-radius: 14px; padding: 20px 24px; background: <?= $cardBg ?>; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; transition: all 0.2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.05)'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.boxShadow='none'; this.style.borderColor='<?= $cardBorder ?>';">
                                
                                <!-- Left: Role Avatar, Title, Key & Description -->
                                <div style="display: flex; align-items: flex-start; gap: 16px; min-width: 280px; max-width: 340px; flex-shrink: 0;">
                                    <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= $iconBg ?>; color: <?= $iconColor ?>; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; margin-top: 2px;">
                                        <i class="fa-solid <?= $icon ?>"></i>
                                    </div>
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($rp['role_title']) ?></h3>
                                            <code style="font-size: 11px; background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; color: #64748b; font-weight: 600;"><?= htmlspecialchars($rKey) ?></code>
                                        </div>
                                        <p style="font-size: 12.5px; color: #64748b; line-height: 1.4; margin: 4px 0 0 0;">
                                            <?= htmlspecialchars($rp['description'] ?: 'No role description provided.') ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Middle: Active Privileges Tags -->
                                <div style="flex: 1; min-width: 260px;">
                                    <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Active Privileges:</span>
                                    <?php if ($isSystemAdmin): ?>
                                        <span style="background: #ede9fe; color: #7c3aed; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-infinity"></i> Full Master Privileges (100% System Access)
                                        </span>
                                    <?php elseif (empty($rPerms)): ?>
                                        <span style="font-size: 12.5px; color: #94a3b8; font-style: italic;">No permissions assigned yet</span>
                                    <?php else: ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            <?php foreach (array_slice($rPerms, 0, 7) as $permKey): ?>
                                                <span style="background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-size: 11.5px; font-weight: 700; padding: 3px 9px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                                    <i class="fa-solid fa-check" style="font-size: 9px; color: #16a34a;"></i>
                                                    <?= htmlspecialchars(str_replace('_', ' ', $permKey)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($rPerms) > 7): ?>
                                                <span style="background: #e2e8f0; color: #475569; font-size: 11.5px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">
                                                    +<?= count($rPerms) - 7 ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Right: User Count & Action Buttons -->
                                <div style="display: flex; align-items: center; gap: 14px; justify-content: flex-end; flex-shrink: 0; min-width: 220px;">
                                    <span style="background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 800; padding: 6px 12px; border-radius: 20px; white-space: nowrap; border: 1px solid #e2e8f0;">
                                        <i class="fa-solid fa-users" style="margin-right: 4px; color: #64748b;"></i> <?= $userCountForRole ?> Users
                                    </span>

                                    <?php if ($isSystemAdmin): ?>
                                        <button type="button" disabled style="background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; padding: 9px 18px; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: not-allowed; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                                            <i class="fa-solid fa-lock"></i> Master Locked
                                        </button>
                                    <?php else: ?>
                                        <button type="button" onclick='openRolePermissionModal(<?= json_encode($rp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' style="background: #ede9fe; color: #6d28d9; border: 1.5px solid #ddd6fe; padding: 9px 18px; border-radius: 8px; font-size: 12.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; white-space: nowrap;" onmouseover="this.style.background='#ddd6fe';" onmouseout="this.style.background='#ede9fe';">
                                            <i class="fa-solid fa-sliders"></i> Configure Permissions
                                        </button>
                                        <?php if (empty($rp['is_system'])): ?>
                                            <button type="button" onclick="deleteCustomRole('<?= htmlspecialchars($rKey) ?>', '<?= htmlspecialchars($rp['role_title']) ?>')" style="background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 9px 12px; border-radius: 8px; font-size: 12px; cursor: pointer;" title="Delete Custom Role">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
<!-- TAB 3: CATEGORIES -->
        <?php elseif ($tab === 'category'): ?>
            <div class="panel-box">
                <div class="panel-header">
                    <div>
                        <h3><i class="fa-solid fa-layer-group" style="color: var(--primary-color);"></i> Category Management <span class="page-title-badge" style="background: #f1f5f9; color: #334155;"><?= count($categoryList) ?></span></h3>
                        <span style="font-size: 13px; color: var(--text-muted);">Organize product categories and bilingual labels</span>
                    </div>
                    <?php if (hasPermission('manage_categories')): ?>
                    <button type="button" onclick="openCategoryModal()" class="btn-action">
                        <i class="fa-solid fa-plus"></i> Add New Category
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Category Name (English)</th>
                                <th>বাংলা নাম (Bengali)</th>
                                <th>Slug</th>
                                <th>Items</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryList as $cat): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($cat['img']) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-color);" onerror="this.src='https://placehold.co/44';">
                                    </td>
                                    <td><strong style="font-size: 14.5px;"><?= htmlspecialchars($cat['name']) ?></strong></td>
                                    <td><span style="font-weight: 600; color: #16a34a; font-size: 15px;"><?= htmlspecialchars($cat['name_bn']) ?></span></td>
                                    <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                    <td><span style="background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-weight: 800; font-size: 12px;"><?= $cat['product_count'] ?> Products</span></td>
                                    <td style="text-align: right;">
                                        <?php if (hasPermission('manage_categories')): ?>
                                        <button type="button" class="btn-icon" title="Edit Category" onclick='openEditCategoryModal(<?= json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn-icon delete" title="Delete Category" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- TAB 4: PRODUCTS & INVENTORY CONTROL -->
        <?php elseif ($tab === 'product'): ?>
            <?php 
                $subtab = $_GET['subtab'] ?? 'catalog';
                if (!in_array($subtab, ['catalog', 'batches', 'pricing', 'stocks'])) {
                    $subtab = 'catalog';
                }
            ?>
            <!-- Top 4 Options Navigation Header (Large Bolder Style) -->
            <div style="background: white; border-radius: 18px; border: 1.5px solid #e2e8f0; padding: 14px 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;" id="productSubtabNav">
                    <!-- 1. Products -->
                    <button type="button" class="prod-subtab-btn <?= $subtab === 'catalog' ? 'active' : '' ?>" id="prod-subtab-btn-catalog" onclick="switchProductSubtab('catalog', this)" style="padding: 15px 22px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $subtab === 'catalog' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $subtab === 'catalog' ? '#6366f1' : '#f8fafc' ?>; color: <?= $subtab === 'catalog' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtab === 'catalog' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.28);' : '' ?>">
                        <i class="fa-solid fa-apple-whole" style="font-size: 18px; color: <?= $subtab === 'catalog' ? 'white' : '#6366f1' ?>;"></i>
                        <span>Products</span>
                        <span class="subtab-badge" style="background: <?= $subtab === 'catalog' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $subtab === 'catalog' ? 'white' : '#475569' ?>; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= count($productList) ?></span>
                    </button>
                    <!-- 2. Batch & Expiry -->
                    <button type="button" class="prod-subtab-btn <?= $subtab === 'batches' ? 'active' : '' ?>" id="prod-subtab-btn-batches" onclick="switchProductSubtab('batches', this)" style="padding: 15px 22px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $subtab === 'batches' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $subtab === 'batches' ? '#6366f1' : '#f8fafc' ?>; color: <?= $subtab === 'batches' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtab === 'batches' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.28);' : '' ?>">
                        <i class="fa-solid fa-barcode" style="font-size: 18px; color: <?= $subtab === 'batches' ? 'white' : '#2563eb' ?>;"></i>
                        <span>Batch &amp; Expiry</span>
                        <span class="subtab-badge" style="background: <?= $subtab === 'batches' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $subtab === 'batches' ? 'white' : '#475569' ?>; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= count($batchesList) ?></span>
                    </button>
                    <!-- 3. Pricing Table -->
                    <button type="button" class="prod-subtab-btn <?= $subtab === 'pricing' ? 'active' : '' ?>" id="prod-subtab-btn-pricing" onclick="switchProductSubtab('pricing', this)" style="padding: 15px 22px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $subtab === 'pricing' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $subtab === 'pricing' ? '#6366f1' : '#f8fafc' ?>; color: <?= $subtab === 'pricing' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtab === 'pricing' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.28);' : '' ?>">
                        <i class="fa-solid fa-tags" style="font-size: 18px; color: <?= $subtab === 'pricing' ? 'white' : '#059669' ?>;"></i>
                        <span>Pricing Table</span>
                        <span class="subtab-badge" style="background: <?= $subtab === 'pricing' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $subtab === 'pricing' ? 'white' : '#475569' ?>; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= count($pricingList) ?></span>
                    </button>
                    <!-- 4. Stock & Inventory -->
                    <button type="button" class="prod-subtab-btn <?= $subtab === 'stocks' ? 'active' : '' ?>" id="prod-subtab-btn-stocks" onclick="switchProductSubtab('stocks', this)" style="padding: 15px 22px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $subtab === 'stocks' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $subtab === 'stocks' ? '#6366f1' : '#f8fafc' ?>; color: <?= $subtab === 'stocks' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtab === 'stocks' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.28);' : '' ?>">
                        <i class="fa-solid fa-warehouse" style="font-size: 18px; color: <?= $subtab === 'stocks' ? 'white' : '#d97706' ?>;"></i>
                        <span>Stock &amp; Inventory</span>
                        <span class="subtab-badge" style="background: <?= $subtab === 'stocks' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $subtab === 'stocks' ? 'white' : '#475569' ?>; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= count($stocksList) ?></span>
                    </button>
                </div>
            </div>

            <!-- PANEL 1: PRODUCT CATALOG -->
            <div id="prod-panel-catalog" class="prod-subtab-panel" style="<?= $subtab === 'catalog' ? 'display: block;' : 'display: none;' ?>">
                <div class="panel-box">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <h3><i class="fa-solid fa-apple-whole" style="color: var(--primary-color);"></i> Product Catalog <span class="page-title-badge" style="background: #ede9fe; color: #6d28d9;"><?= $totalProducts ?></span></h3>
                            <span style="font-size: 13px; color: var(--text-muted);">Manage products, categories, and bilingual catalog details</span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <!-- Live Search Input -->
                            <div style="position: relative; width: 250px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none;"></i>
                                <input type="text" id="prod-search-input" oninput="filterProductsTable()" placeholder="Search product (EN/বাংলা)..." class="form-control" style="padding-left: 34px; padding-top: 8px; padding-bottom: 8px; border-radius: 8px; font-size: 13.5px; width: 100%; border: 1.5px solid var(--border-color);">
                            </div>
                            <!-- Category Filter Dropdown -->
                            <select id="prod-cat-filter" onchange="filterProductsTable()" class="form-control" style="padding: 8px 14px; border-radius: 8px; font-size: 13.5px; background: white; font-weight: 600; min-width: 190px; border: 1.5px solid var(--border-color);">
                                <option value="">All Categories (সকল ক্যাটাগরি)</option>
                                <?php foreach ($categoryList as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['name_bn']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Add Button -->
                            <?php if (hasPermission('create_products')): ?>
                            <button type="button" onclick="openProductModal()" class="btn-action" style="white-space: nowrap;">
                                <i class="fa-solid fa-plus"></i> Add New Product
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width: 145px; min-width: 140px; white-space: nowrap;">Product No</th>
                                    <th style="width: 70px; text-align: center;">Image</th>
                                    <th style="width: 30%;">Title (EN / BN)</th>
                                    <th style="width: 18%;">Category</th>
                                    <th style="width: 18%;">Unit / পরিমাপ</th>
                                    <th style="width: 120px; text-align: right; padding-right: 18px; white-space: nowrap;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productList as $p): 
                                    $pActive = (!isset($p['is_active']) || intval($p['is_active']) === 1) && ($p['status'] ?? 'active') !== 'inactive';
                                    $pCode = !empty($p['product_code']) ? $p['product_code'] : ('PRD-' . str_pad($p['id'], 5, '0', STR_PAD_LEFT));
                                ?>
                                    <tr class="product-table-row" data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>" data-title-bn="<?= htmlspecialchars(mb_strtolower($p['title_bn'], 'UTF-8')) ?>" data-code="<?= htmlspecialchars(strtolower($pCode)) ?>" data-cat-id="<?= $p['category_id'] ?>" data-cat-name="<?= htmlspecialchars(strtolower($p['category_name'] ?? '')) ?>" style="<?= !$pActive ? 'opacity: 0.7;' : '' ?>">
                                        <td style="width: 145px; min-width: 140px; white-space: nowrap;">
                                            <span style="background: #eff6ff; color: #2563eb; font-weight: 800; font-size: 12.5px; padding: 5px 10px; border-radius: 6px; border: 1px solid #bfdbfe; display: inline-flex; align-items: center; gap: 4px; font-family: monospace; letter-spacing: 0.5px; white-space: nowrap;">
                                                <i class="fa-solid fa-hashtag" style="font-size: 10px; color: #3b82f6;"></i><?= htmlspecialchars($pCode) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center; width: 70px;">
                                            <img src="<?= htmlspecialchars($p['image']) ?>" style="width: 46px; height: 46px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border-color); display: inline-block;" onerror="this.src='https://placehold.co/46';">
                                        </td>
                                        <td style="width: 30%;">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <strong style="font-size: 14.5px; color: var(--text-dark); font-weight: 800;"><?= htmlspecialchars($p['title']) ?></strong>
                                                <?php if (!$pActive): ?>
                                                    <span style="background: #fee2e2; color: #dc2626; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px;">INACTIVE</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 13px; color: #16a34a; font-weight: 700; margin-top: 2px;"><?= htmlspecialchars($p['title_bn']) ?></div>
                                        </td>
                                        <td style="width: 18%;">
                                            <span style="background: #f1f5f9; padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 12.5px; color: #334155; border: 1px solid #e2e8f0; display: inline-block;">
                                                <?= htmlspecialchars($p['category_name'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td style="width: 18%;">
                                            <strong style="color: #1e293b; font-size: 14px; font-weight: 700;"><?= htmlspecialchars($p['poriman']) ?></strong>
                                            <?php if (!empty($p['poriman_bn'])): ?>
                                                <span style="color: #64748b; font-size: 13px; font-weight: 600;">/ <?= htmlspecialchars($p['poriman_bn']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width: 120px; text-align: right; padding-right: 18px; white-space: nowrap;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                                <?php if (hasPermission('edit_products')): ?>
                                                <button type="button" class="btn-icon" title="Edit Product" onclick='openEditProductModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button type="button" class="btn-icon" title="<?= $pActive ? 'Deactivate Product' : 'Activate Product' ?>" onclick="toggleProductStatus(<?= $p['id'] ?>)" style="color: <?= $pActive ? '#10b981' : '#ef4444' ?>;">
                                                    <i class="fa-solid fa-power-off"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (hasPermission('delete_products')): ?>
                                                <button type="button" class="btn-icon delete" title="Delete Product" onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['title'])) ?>')">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="prod-no-match-row" style="display: none;">
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 35px; font-size: 14.5px;">
                                        <i class="fa-solid fa-box-open" style="font-size: 26px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                        No products match your search query or selected category.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL 2: BATCH & EXPIRY -->
            <div id="prod-panel-batches" class="prod-subtab-panel" style="<?= $subtab === 'batches' ? 'display: block;' : 'display: none;' ?>">
                <div class="panel-box">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <h3><i class="fa-solid fa-barcode" style="color: #2563eb;"></i> Product Batches &amp; Expiry Tracking</h3>
                            <span style="font-size: 13px; color: var(--text-muted);">Manage product batch numbers, manufacturing &amp; expiry dates, and suppliers</span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <div style="position: relative; width: 250px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none;"></i>
                                <input type="text" id="batch-search-input" oninput="filterBatchesTable()" placeholder="Search batch / product..." class="form-control" style="padding-left: 34px; padding-top: 8px; padding-bottom: 8px; border-radius: 8px; font-size: 13.5px; width: 100%; border: 1.5px solid var(--border-color);">
                            </div>
                            <button type="button" onclick="openAddBatchModal()" class="btn-action" style="background: #2563eb; color: white; border: none; white-space: nowrap;">
                                <i class="fa-solid fa-plus"></i> Create New Batch
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Batch Number</th>
                                    <th>Product Title</th>
                                    <th>Mfg Date</th>
                                    <th>Expiry Date</th>
                                    <th>Supplier</th>
                                    <th>Cost / Sale</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($batchesList)): ?>
                                    <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 35px;">No product batches recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($batchesList as $b): ?>
                                        <tr class="batch-table-row" id="batch-row-<?= $b['id'] ?>" data-search="<?= htmlspecialchars(strtolower($b['batch_number'] . ' ' . $b['product_title'] . ' ' . ($b['supplier_name'] ?? ''))) ?>">
                                            <td style="font-weight: 800; color: #2563eb; font-size: 13.5px;"><?= htmlspecialchars($b['batch_number']) ?></td>
                                            <td>
                                                <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($b['product_title']) ?></strong>
                                                <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($b['product_code']) ?></div>
                                            </td>
                                            <td style="font-size: 12.5px; color: #475569;"><?= htmlspecialchars($b['mfg_date'] ?: '-') ?></td>
                                            <td style="font-size: 12.5px; font-weight: 700; color: <?= (!empty($b['exp_date']) && $b['exp_date'] < date('Y-m-d')) ? '#dc2626' : '#166534' ?>;">
                                                <?= htmlspecialchars($b['exp_date'] ?: '-') ?>
                                                <?php if (!empty($b['exp_date']) && $b['exp_date'] < date('Y-m-d')): ?>
                                                    <span style="background: #fee2e2; color: #dc2626; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 4px; font-weight: 800;">EXPIRED</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size: 12.5px; color: #334155;"><?= htmlspecialchars($b['supplier_name'] ?: 'Direct Mart') ?></td>
                                            <td style="font-size: 12.5px;">
                                                <?php if (hasPermission('view_cost_profit')): ?>
                                                    <span style="color: #64748b;">CP: ৳<?= number_format($b['cost_price'], 2) ?></span><br>
                                                <?php endif; ?>
                                                <strong style="color: #047857;">Sale: ৳<?= number_format($b['selling_price'], 2) ?></strong>
                                            </td>
                                            <td style="font-weight: 800; font-size: 13.5px;"><?= htmlspecialchars($b['current_stock']) ?> pcs</td>
                                            <td>
                                                <span style="padding: 3px 9px; border-radius: 12px; font-size: 11.5px; font-weight: 800; background: <?= $b['status'] === 'active' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $b['status'] === 'active' ? '#15803d' : '#dc2626' ?>;">
                                                    <?= ucfirst($b['status']) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn-icon" title="Edit Batch" onclick='openEditBatchModal(<?= json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button type="button" class="btn-icon delete" title="Delete Batch" onclick="deleteBatch(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['batch_number'])) ?>')">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL 3: PRICING TABLE -->
            <div id="prod-panel-pricing" class="prod-subtab-panel" style="<?= $subtab === 'pricing' ? 'display: block;' : 'display: none;' ?>">
                <div class="panel-box">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <h3><i class="fa-solid fa-tags" style="color: #059669;"></i> Multi-Tier Pricing Table</h3>
                            <span style="font-size: 13px; color: var(--text-muted);">Manage purchase cost (CP), dealer rate (DP), MRP, customer selling price, and zone overrides</span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <div style="position: relative; width: 250px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none;"></i>
                                <input type="text" id="pricing-search-input" oninput="filterPricingTable()" placeholder="Search price matrix..." class="form-control" style="padding-left: 34px; padding-top: 8px; padding-bottom: 8px; border-radius: 8px; font-size: 13.5px; width: 100%; border: 1.5px solid var(--border-color);">
                            </div>
                            <button type="button" onclick="openEditPricingModal()" class="btn-action" style="background: #059669; color: white; border: none; white-space: nowrap;">
                                <i class="fa-solid fa-plus"></i> Set Price Entry
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Zone / Location</th>
                                    <th>Cost Price (CP)</th>
                                    <th>Dealer Price (DP)</th>
                                    <th>MRP</th>
                                    <th>Selling Price</th>
                                    <th>Gross Margin</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pricingList)): ?>
                                    <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 35px;">No pricing records configured yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pricingList as $pr): 
                                        $margin = floatval($pr['selling_price']) - floatval($pr['cost_price']);
                                        $locDisplay = 'Default / All Bangladesh';
                                        if (!empty($pr['district']) && $pr['district'] !== 'All Bangladesh') {
                                            $locDisplay = htmlspecialchars($pr['district']);
                                            if (!empty($pr['police_station']) && $pr['police_station'] !== 'All Police Stations') {
                                                $locDisplay .= ' - ' . htmlspecialchars($pr['police_station']);
                                            }
                                        } elseif (!empty($pr['zone_name']) && $pr['zone_name'] !== 'Default / All Bangladesh') {
                                            $locDisplay = htmlspecialchars($pr['zone_name']);
                                        }
                                    ?>
                                        <tr class="pricing-table-row" id="pricing-row-<?= $pr['id'] ?>" data-search="<?= htmlspecialchars(strtolower($pr['product_title'] . ' ' . ($pr['batch_number'] ?? '') . ' ' . ($pr['zone_name'] ?? '') . ' ' . ($pr['district'] ?? '') . ' ' . ($pr['police_station'] ?? ''))) ?>">
                                            <td>
                                                <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($pr['product_title']) ?></strong>
                                                <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($pr['product_code']) ?> (<?= htmlspecialchars($pr['poriman']) ?>)</div>
                                            </td>
                                            <td>
                                                <span style="font-size: 12px; font-weight: 700; color: #2563eb;"><?= htmlspecialchars($pr['batch_number'] ?: 'All Batches') ?></span>
                                            </td>
                                            <td>
                                                <span style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 3px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                    <i class="fa-solid fa-location-dot" style="font-size: 10px; color: #3b82f6;"></i> <?= $locDisplay ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 700; color: #64748b;">
                                                <?= hasPermission('view_cost_profit') ? '৳' . number_format($pr['cost_price'], 2) : '<span style="color:#94a3b8;" title="Cost Price Restricted">৳***</span>' ?>
                                            </td>
                                            <td style="font-weight: 700; color: #475569;">৳<?= number_format($pr['dp_price'], 2) ?></td>
                                            <td style="font-weight: 700; color: #94a3b8; text-decoration: line-through;">৳<?= number_format($pr['mrp_price'], 2) ?></td>
                                            <td style="font-weight: 900; color: #059669; font-size: 14.5px;">৳<?= number_format($pr['selling_price'], 2) ?></td>
                                            <td style="font-weight: 800; color: <?= $margin >= 0 ? '#059669' : '#dc2626' ?>;">
                                                <?= hasPermission('view_cost_profit') ? '৳' . number_format($margin, 2) : '<span style="color:#94a3b8;">***</span>' ?>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                                    <button type="button" class="btn-icon" title="Edit Pricing" onclick='openEditPricingModal(<?= json_encode($pr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="btn-icon delete" title="Delete Pricing Entry" onclick="deletePricingEntry(<?= $pr['id'] ?>, '<?= htmlspecialchars(addslashes($pr['product_title'])) ?>', '<?= htmlspecialchars(addslashes($locDisplay)) ?>')" style="color: #ef4444;">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL 4: STOCK & INVENTORY -->
            <div id="prod-panel-stocks" class="prod-subtab-panel" style="<?= $subtab === 'stocks' ? 'display: block;' : 'display: none;' ?>">
                <div class="panel-box">
                    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <h3><i class="fa-solid fa-warehouse" style="color: #d97706;"></i> Stock Inventory &amp; Ledger Operations</h3>
                            <span style="font-size: 13px; color: var(--text-muted);">Perform real-time Stock IN, Stock OUT, Damage logging, and view audit history</span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <div style="position: relative; width: 250px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none;"></i>
                                <input type="text" id="stock-search-input" oninput="filterStocksTable()" placeholder="Search stock inventory..." class="form-control" style="padding-left: 34px; padding-top: 8px; padding-bottom: 8px; border-radius: 8px; font-size: 13.5px; width: 100%; border: 1.5px solid var(--border-color);">
                            </div>
                            <button type="button" onclick="openStockLogsModal()" class="btn-action" style="background: #334155; color: white; border: none; white-space: nowrap;">
                                <i class="fa-solid fa-clock-rotate-left"></i> Stock Audit Logs
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Warehouse / Location</th>
                                    <th>Current Stock</th>
                                    <th>Reserved</th>
                                    <th>Damaged</th>
                                    <th>Threshold Alert</th>
                                    <th style="text-align: right;">Quick Stock Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stocksList)): ?>
                                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px;">No stock ledger entries found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($stocksList as $s): 
                                        $isLow = (intval($s['current_stock']) <= intval($s['low_stock_threshold'] ?? 5));
                                        $locDisplay = (!empty($s['district']) && $s['district'] !== 'All Bangladesh') 
                                            ? ($s['district'] . (($s['police_station'] && $s['police_station'] !== 'All Police Stations') ? (' - ' . $s['police_station']) : ''))
                                            : ($s['zone_name'] ?: 'All Bangladesh / Central Hub');
                                    ?>
                                        <tr class="stock-table-row" id="stock-row-<?= $s['id'] ?>" data-search="<?= htmlspecialchars(strtolower($s['product_title'] . ' ' . ($s['batch_number'] ?? '') . ' ' . ($s['district'] ?? '') . ' ' . ($s['police_station'] ?? '') . ' ' . ($s['zone_name'] ?? ''))) ?>">
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <img src="<?= htmlspecialchars($s['image']) ?>" style="width: 38px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);" onerror="this.src='https://placehold.co/38';">
                                                    <div>
                                                        <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($s['product_title']) ?></strong>
                                                        <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($s['product_code']) ?> (<?= htmlspecialchars($s['poriman']) ?>)</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-size: 12px; font-weight: 700; color: #2563eb;"><?= htmlspecialchars($s['batch_number'] ?: 'Main Store') ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($s['district']) && $s['district'] !== 'All Bangladesh'): ?>
                                                    <span style="background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-location-dot" style="color: #ea580c;"></i>
                                                        <?= htmlspecialchars($s['district']) ?><?= ($s['police_station'] && $s['police_station'] !== 'All Police Stations') ? (' (' . htmlspecialchars($s['police_station']) . ')') : '' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: #f1f5f9; color: #334155; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-warehouse" style="color: #64748b;"></i>
                                                        <?= htmlspecialchars($s['zone_name'] ?: 'All Bangladesh (Central Hub)') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-size: 15px; font-weight: 900; color: <?= $isLow ? '#dc2626' : '#0f172a' ?>;">
                                                    <?= htmlspecialchars($s['current_stock']) ?> pcs
                                                </span>
                                            </td>
                                            <td style="color: #64748b; font-weight: 600;"><?= htmlspecialchars($s['reserved_stock'] ?? 0) ?> pcs</td>
                                            <td style="color: #dc2626; font-weight: 600;"><?= htmlspecialchars($s['damaged_stock'] ?? 0) ?> pcs</td>
                                            <td>
                                                <?php if ($isLow): ?>
                                                    <span style="background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-triangle-exclamation"></i> Low Stock (≤ <?= $s['low_stock_threshold'] ?? 5 ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-circle-check"></i> Normal
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; gap: 5px; justify-content: flex-end;">
                                                    <button type="button" onclick="openStockOpModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['product_title'])) ?>', '<?= htmlspecialchars(addslashes($s['batch_number'] ?? 'Main')) ?>', '<?= htmlspecialchars(addslashes($locDisplay)) ?>', <?= $s['current_stock'] ?>, 'stock_in')" title="Stock IN (Add)" style="padding: 5px 9px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 6px; font-size: 11.5px; font-weight: 800; cursor: pointer;">
                                                        <i class="fa-solid fa-plus"></i> IN
                                                    </button>
                                                    <button type="button" onclick="openStockOpModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['product_title'])) ?>', '<?= htmlspecialchars(addslashes($s['batch_number'] ?? 'Main')) ?>', '<?= htmlspecialchars(addslashes($locDisplay)) ?>', <?= $s['current_stock'] ?>, 'stock_out')" title="Stock OUT (Reduce)" style="padding: 5px 9px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; font-size: 11.5px; font-weight: 800; cursor: pointer;">
                                                        <i class="fa-solid fa-minus"></i> OUT
                                                    </button>
                                                    <button type="button" onclick="openStockOpModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['product_title'])) ?>', '<?= htmlspecialchars(addslashes($s['batch_number'] ?? 'Main')) ?>', '<?= htmlspecialchars(addslashes($locDisplay)) ?>', <?= $s['current_stock'] ?>, 'damage')" title="Mark Damaged" style="padding: 5px 9px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; border-radius: 6px; font-size: 11.5px; font-weight: 800; cursor: pointer;">
                                                        <i class="fa-solid fa-triangle-exclamation"></i> DMG
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- TAB 5: ORDERS & INVOICES -->
        <?php elseif ($tab === 'order'): ?>
            <?php
                $orderStatusFilter = $_GET['order_status'] ?? 'all';
                $cntAll = count($orderList);
                $cntPending = 0;
                $cntReceived = 0;
                $cntDelivered = 0;

                foreach ($orderList as $ord) {
                    $st = strtolower($ord['status'] ?? '');
                    if ($st === 'pending') {
                        $cntPending++;
                    } elseif (in_array($st, ['processing', 'customer_confirmed', 'received', 'delivering', 'confirmed'])) {
                        $cntReceived++;
                    } elseif (in_array($st, ['completed', 'delivered'])) {
                        $cntDelivered++;
                    }
                }
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="font-size: 26px; font-weight: 900; color: #0f172a; margin: 0 0 4px 0;">Orders &amp; Live Operations</h2>
                    <p style="color: #64748b; margin: 0; font-size: 14px;">Welcome back, <strong><?= htmlspecialchars($adminName) ?></strong>! Supervise live orders, assign riders, and process customer receipts</p>
                </div>
            </div>

            <!-- Top 4 Main Order Status Buttons (Large Bolder Style) -->
            <div style="background: white; border-radius: 18px; border: 1.5px solid #e2e8f0; padding: 14px 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;" id="orderStatusSubtabNav">
                    <!-- 1. All Orders -->
                    <button type="button" class="order-status-btn <?= $orderStatusFilter === 'all' ? 'active' : '' ?>" id="order-btn-all" onclick="filterOrdersByStatus('all', this)" style="padding: 16px 20px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $orderStatusFilter === 'all' ? '#2563eb' : '#e2e8f0' ?>; background: <?= $orderStatusFilter === 'all' ? '#2563eb' : '#f8fafc' ?>; color: <?= $orderStatusFilter === 'all' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $orderStatusFilter === 'all' ? 'box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);' : '' ?>">
                        <i class="fa-solid fa-boxes-stacked" style="font-size: 18px; color: <?= $orderStatusFilter === 'all' ? 'white' : '#2563eb' ?>;"></i>
                        <span>All Orders</span>
                        <span class="subtab-badge" style="background: <?= $orderStatusFilter === 'all' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $orderStatusFilter === 'all' ? 'white' : '#475569' ?>; padding: 2px 9px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= $cntAll ?></span>
                    </button>

                    <!-- 2. Pending Orders -->
                    <button type="button" class="order-status-btn <?= $orderStatusFilter === 'pending' ? 'active' : '' ?>" id="order-btn-pending" onclick="filterOrdersByStatus('pending', this)" style="padding: 16px 20px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $orderStatusFilter === 'pending' ? '#d97706' : '#e2e8f0' ?>; background: <?= $orderStatusFilter === 'pending' ? '#d97706' : '#f8fafc' ?>; color: <?= $orderStatusFilter === 'pending' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $orderStatusFilter === 'pending' ? 'box-shadow: 0 6px 18px rgba(217, 119, 6, 0.25);' : '' ?>">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 18px; color: <?= $orderStatusFilter === 'pending' ? 'white' : '#d97706' ?>;"></i>
                        <span>Pending</span>
                        <span class="subtab-badge" style="background: <?= $orderStatusFilter === 'pending' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $orderStatusFilter === 'pending' ? 'white' : '#475569' ?>; padding: 2px 9px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= $cntPending ?></span>
                    </button>

                    <!-- 3. Received / Processing Orders -->
                    <button type="button" class="order-status-btn <?= $orderStatusFilter === 'received' ? 'active' : '' ?>" id="order-btn-received" onclick="filterOrdersByStatus('received', this)" style="padding: 16px 20px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $orderStatusFilter === 'received' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $orderStatusFilter === 'received' ? '#6366f1' : '#f8fafc' ?>; color: <?= $orderStatusFilter === 'received' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $orderStatusFilter === 'received' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.25);' : '' ?>">
                        <i class="fa-solid fa-boxes-packing" style="font-size: 18px; color: <?= $orderStatusFilter === 'received' ? 'white' : '#6366f1' ?>;"></i>
                        <span>Received</span>
                        <span class="subtab-badge" style="background: <?= $orderStatusFilter === 'received' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $orderStatusFilter === 'received' ? 'white' : '#475569' ?>; padding: 2px 9px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= $cntReceived ?></span>
                    </button>

                    <!-- 4. Delivered Orders -->
                    <button type="button" class="order-status-btn <?= $orderStatusFilter === 'delivered' ? 'active' : '' ?>" id="order-btn-delivered" onclick="filterOrdersByStatus('delivered', this)" style="padding: 16px 20px; border-radius: 12px; font-size: 15.5px; font-weight: 800; border: 2px solid <?= $orderStatusFilter === 'delivered' ? '#16a34a' : '#e2e8f0' ?>; background: <?= $orderStatusFilter === 'delivered' ? '#16a34a' : '#f8fafc' ?>; color: <?= $orderStatusFilter === 'delivered' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $orderStatusFilter === 'delivered' ? 'box-shadow: 0 6px 18px rgba(22, 163, 74, 0.25);' : '' ?>">
                        <i class="fa-solid fa-circle-check" style="font-size: 18px; color: <?= $orderStatusFilter === 'delivered' ? 'white' : '#16a34a' ?>;"></i>
                        <span>Delivered</span>
                        <span class="subtab-badge" style="background: <?= $orderStatusFilter === 'delivered' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $orderStatusFilter === 'delivered' ? 'white' : '#475569' ?>; padding: 2px 9px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= $cntDelivered ?></span>
                    </button>
                </div>
            </div>

            <div class="panel-box">
                <div class="panel-header">
                    <div>
                        <h3><i class="fa-solid fa-boxes-packing" style="color: var(--primary-color);"></i> Orders &amp; Live Operations <span class="page-title-badge" style="background: #dcfce7; color: #15803d;"><?= $totalOrders ?></span></h3>
                        <span style="font-size: 13px; color: var(--text-muted);">Supervise customer orders, assign riders, and view receipts</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Assigned Rider</th>
                                <th>Date</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-tbody">
                            <?php if (empty($orderList)): ?>
                                <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orderList as $o): ?>
                                    <tr class="order-table-row" data-order-status="<?= htmlspecialchars(strtolower($o['status'])) ?>">
                                        <td>
                                            <strong style="color: var(--primary-color); font-size: 14px;"><?= htmlspecialchars($o['order_number']) ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($o['customer_name']) ?></strong><br>
                                            <small style="color: #64748b;"><?= htmlspecialchars($o['customer_phone']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($o['order_type'] === 'uploaded'): ?>
                                                <span style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;"><i class="fa-solid fa-camera"></i> Photo List</span>
                                            <?php elseif ($o['order_type'] === 'text_list'): ?>
                                                <span style="background: #e0e7ff; color: #4338ca; padding: 4px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;"><i class="fa-solid fa-file-lines"></i> Written List</span>
                                            <?php else: ?>
                                                <span style="background: #ecfdf5; color: #047857; padding: 4px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;"><i class="fa-solid fa-cart-shopping"></i> Website</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="font-size: 15px;">৳<?= number_format($o['total_amount'], 2) ?></strong></td>
                                        <td>
                                            <?php 
                                                $pMethod = strtolower($o['payment_method'] ?? 'cod');
                                                $pStatus = strtolower($o['payment_status'] ?? 'pending');
                                                $trxId = trim($o['transaction_id'] ?? '');
                                                $senderPhone = trim($o['payment_sender_phone'] ?? '');
                                            ?>
                                            <div style="display: flex; flex-direction: column; gap: 4px; min-width: 130px;">
                                                <div>
                                                    <?php if ($pMethod === 'bkash'): ?>
                                                        <span style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-mobile-screen"></i> bKash
                                                        </span>
                                                    <?php elseif ($pMethod === 'nagad'): ?>
                                                        <span style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-mobile-screen"></i> Nagad
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-hand-holding-dollar"></i> COD
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($trxId): ?>
                                                    <div style="font-size: 11px; font-weight: 700; color: #0f172a;" title="Transaction ID">
                                                        <span style="color: #64748b; font-weight: 600;">Trx:</span> <code style="background: #fdf2f8; padding: 1px 4px; border-radius: 4px; color: #be185d; font-size: 11px; font-weight: 800;"><?= htmlspecialchars($trxId) ?></code>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($senderPhone): ?>
                                                    <div style="font-size: 10.5px; color: #64748b;" title="Sender Mobile">
                                                        <span>From:</span> <strong><?= htmlspecialchars($senderPhone) ?></strong>
                                                    </div>
                                                <?php endif; ?>

                                                <div style="margin-top: 2px;">
                                                    <select onchange="updatePaymentStatus(<?= $o['id'] ?>, this.value, this)" style="padding: 3px 6px; border-radius: 6px; border: 1.5px solid <?= $pStatus === 'paid' ? '#86efac' : '#fde68a' ?>; font-weight: 700; font-size: 11px; outline: none; background: <?= $pStatus === 'paid' ? '#f0fdf4' : '#fffbeb' ?>; color: <?= $pStatus === 'paid' ? '#15803d' : '#b45309' ?>; cursor: pointer;">
                                                        <option value="pending" <?= $pStatus === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                                        <option value="paid" <?= $pStatus === 'paid' ? 'selected' : '' ?>>✅ Paid</option>
                                                        <option value="refunded" <?= $pStatus === 'refunded' ? 'selected' : '' ?>>↩️ Refunded</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (hasPermission('update_order_status')): ?>
                                            <select onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)" style="padding: 6px 10px; border-radius: 8px; border: 1.5px solid var(--border-color); font-weight: 700; font-size: 12px; outline: none; background: white;">
                                                <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="delivering" <?= $o['status'] === 'delivering' ? 'selected' : '' ?>>Delivering</option>
                                                <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <?php else: ?>
                                                <span style="font-weight: 700; font-size: 12px; text-transform: capitalize;"><?= htmlspecialchars($o['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (hasPermission('assign_riders')): ?>
                                            <select onchange="assignRider(<?= $o['id'] ?>, this.value)" style="padding: 6px 10px; border-radius: 8px; border: 1.5px solid var(--border-color); font-weight: 600; font-size: 12px; outline: none; background: white;">
                                                <option value="">-- Unassigned --</option>
                                                <?php foreach ($ridersList as $r): ?>
                                                    <option value="<?= $r['id'] ?>" <?= $o['rider_id'] == $r['id'] ? 'selected' : '' ?>>
                                                         🚴 <?= htmlspecialchars($r['name']) ?> (<?= $r['phone'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php else: ?>
                                                <span style="font-size: 12px; color: #475569; font-weight: 600;"><?= htmlspecialchars($o['rider_name'] ?: 'Unassigned') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 12px;"><?= $o['formatted_date'] ?></td>
                                        <td style="text-align: right;">
                                            <button type="button" class="btn-icon" title="View Details / Receipt" onclick="viewOrderModal(<?= $o['id'] ?>)">
                                                <i class="fa-solid fa-file-invoice"></i>
                                            </button>
                                            <?php if (hasPermission('cancel_orders')): ?>
                                            <button type="button" class="btn-icon delete" title="Delete Order" onclick="deleteOrder(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['order_number'])) ?>')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <script>
                    window.allOrdersMap = <?= json_encode(array_column($orderList, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || {};
                    (function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const initialStatus = urlParams.get('order_status') || '<?= $orderStatusFilter ?>';
                        if (initialStatus && initialStatus !== 'all') {
                            const btn = document.getElementById('order-btn-' + initialStatus);
                            if (btn && typeof filterOrdersByStatus === 'function') {
                                filterOrdersByStatus(initialStatus, btn);
                            }
                        }
                    })();
                </script>
            </div>

        <!-- TAB: DP SALE & DEALER WHOLESALE OPERATIONS -->
        <?php elseif ($tab === 'dp_sale'): ?>
            <?php 
                $dpSubtab = $_GET['dp_subtab'] ?? 'summary';
            ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Main Top Subtab Navigation Bar (Large & Top-aligned) -->
                <div style="background: white; border-radius: 16px; border: 1.5px solid #e2e8f0; padding: 10px 12px; display: flex; gap: 12px; flex-wrap: wrap; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
                    <button type="button" id="dp-tab-btn-summary" onclick="switchDpSubtab('summary')" class="dp-nav-btn <?= $dpSubtab === 'summary' ? 'active' : '' ?>" style="padding: 13px 26px; border-radius: 12px; font-weight: 800; font-size: 15px; border: 1.5px solid <?= $dpSubtab === 'summary' ? '#0284c7' : '#e2e8f0' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; background: <?= $dpSubtab === 'summary' ? '#0284c7' : '#f8fafc' ?>; color: <?= $dpSubtab === 'summary' ? 'white' : '#334155' ?>; transition: all 0.2s; box-shadow: <?= $dpSubtab === 'summary' ? '0 4px 14px rgba(2, 132, 199, 0.28)' : 'none' ?>;">
                        <i class="fa-solid fa-chart-pie" style="font-size: 16px;"></i> DP Sale Summary
                    </button>
                    <button type="button" id="dp-tab-btn-history" onclick="switchDpSubtab('history')" class="dp-nav-btn <?= $dpSubtab === 'history' ? 'active' : '' ?>" style="padding: 13px 26px; border-radius: 12px; font-weight: 800; font-size: 15px; border: 1.5px solid <?= $dpSubtab === 'history' ? '#0284c7' : '#e2e8f0' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; background: <?= $dpSubtab === 'history' ? '#0284c7' : '#f8fafc' ?>; color: <?= $dpSubtab === 'history' ? 'white' : '#334155' ?>; transition: all 0.2s; box-shadow: <?= $dpSubtab === 'history' ? '0 4px 14px rgba(2, 132, 199, 0.28)' : 'none' ?>;">
                        <i class="fa-solid fa-list-check" style="font-size: 16px;"></i> DP Invoices &amp; History
                        <span style="background: <?= $dpSubtab === 'history' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $dpSubtab === 'history' ? 'white' : '#0f172a' ?>; padding: 2px 10px; border-radius: 16px; font-size: 12px; font-weight: 800;"><?= count($dpSalesList) ?></span>
                    </button>
                    <button type="button" id="dp-tab-btn-pos" onclick="switchDpSubtab('pos')" class="dp-nav-btn <?= $dpSubtab === 'pos' ? 'active' : '' ?>" style="padding: 13px 26px; border-radius: 12px; font-weight: 800; font-size: 15px; border: 1.5px solid <?= $dpSubtab === 'pos' ? '#0284c7' : '#e2e8f0' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; background: <?= $dpSubtab === 'pos' ? '#0284c7' : '#f8fafc' ?>; color: <?= $dpSubtab === 'pos' ? 'white' : '#334155' ?>; transition: all 0.2s; box-shadow: <?= $dpSubtab === 'pos' ? '0 4px 14px rgba(2, 132, 199, 0.28)' : 'none' ?>;">
                        <i class="fa-solid fa-cart-flatbed" style="font-size: 16px;"></i> + New Wholesale Billing (POS)
                    </button>
                    <button type="button" id="dp-tab-btn-catalog" onclick="switchDpSubtab('catalog')" class="dp-nav-btn <?= $dpSubtab === 'catalog' ? 'active' : '' ?>" style="padding: 13px 26px; border-radius: 12px; font-weight: 800; font-size: 15px; border: 1.5px solid <?= $dpSubtab === 'catalog' ? '#0284c7' : '#e2e8f0' ?>; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; background: <?= $dpSubtab === 'catalog' ? '#0284c7' : '#f8fafc' ?>; color: <?= $dpSubtab === 'catalog' ? 'white' : '#334155' ?>; transition: all 0.2s; box-shadow: <?= $dpSubtab === 'catalog' ? '0 4px 14px rgba(2, 132, 199, 0.28)' : 'none' ?>;">
                        <i class="fa-solid fa-table-list" style="font-size: 16px;"></i> DP Rate Chart &amp; Margins
                    </button>
                </div>

                <!-- SUBTAB 0: DP SALE SUMMARY -->
                <div id="dp-panel-summary" class="dp-subpanel" style="display: <?= $dpSubtab === 'summary' ? 'block' : 'none' ?>;">
                    <!-- Header Banner -->
                    <div style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #075985 100%); border-radius: 18px; padding: 24px 28px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px; box-shadow: 0 10px 30px rgba(2, 132, 199, 0.22); position: relative; overflow: hidden; margin-bottom: 24px;">
                        <div style="position: absolute; right: -15px; bottom: -25px; opacity: 0.12; font-size: 150px; color: white; pointer-events: none;">
                            <i class="fa-solid fa-handshake"></i>
                        </div>
                        <div style="position: relative; z-index: 1;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.18); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 8px; backdrop-filter: blur(4px);">
                                <i class="fa-solid fa-tags" style="color: #fde047;"></i> B2B Wholesale &amp; Dealer POS
                            </div>
                            <h2 style="margin: 0 0 4px 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px;">
                                DP Sale &amp; Wholesale Operations
                            </h2>
                            <p style="margin: 0; opacity: 0.92; font-size: 13.5px; max-width: 650px; line-height: 1.4;">
                                Generate dealer invoices with custom DP rates, track wholesale receivables, batch inventory deductions, and calculate real-time profit margins.
                            </p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; position: relative; z-index: 1;">
                            <button type="button" onclick="switchDpSubtab('pos')" style="background: white; color: #0284c7; border: none; padding: 12px 22px; border-radius: 10px; font-weight: 800; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.15); cursor: pointer; transition: 0.2s;">
                                <i class="fa-solid fa-plus-circle"></i> Create New DP Sale
                            </button>
                        </div>
                    </div>

                    <!-- 4 KPI Summary Cards -->
                    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
                        <div class="kpi-card" style="border-left: 4px solid #0284c7;">
                            <div class="kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                            <div class="kpi-info" style="flex: 1;">
                                <h4>Total DP Revenue</h4>
                                <h3 style="color: #0369a1; font-size: 24px;">৳<?= number_format($totalDpSalesRevenue, 2) ?></h3>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Total wholesale billing</span>
                            </div>
                        </div>

                        <div class="kpi-card" style="border-left: 4px solid #10b981;">
                            <div class="kpi-icon" style="background: #ecfdf5; color: #059669;">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="kpi-info" style="flex: 1;">
                                <h4>Wholesale Profit</h4>
                                <h3 style="color: #047857; font-size: 24px;"><?= hasPermission('view_cost_profit') ? '৳' . number_format($totalDpProfit, 2) : '<span style="color:#94a3b8;" title="Profit Restricted">৳***</span>' ?></h3>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Net DP vs Cost margin</span>
                            </div>
                        </div>

                        <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
                            <div class="kpi-icon" style="background: #fffbeb; color: #d97706;">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            <div class="kpi-info" style="flex: 1;">
                                <h4>Total Due Receivables</h4>
                                <h3 style="color: #b45309; font-size: 24px;">৳<?= number_format($totalDpDue, 2) ?></h3>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Pending dealer collections</span>
                            </div>
                        </div>

                        <div class="kpi-card" style="border-left: 4px solid #6366f1;">
                            <div class="kpi-icon" style="background: #eef2ff; color: #4f46e5;">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div class="kpi-info" style="flex: 1;">
                                <h4>Total Invoices</h4>
                                <h3 style="color: #4338ca; font-size: 24px;"><?= count($dpSalesList) ?></h3>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Completed dealer orders</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Performance & Quick Action Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                        <div class="panel-box">
                            <div class="panel-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;"><i class="fa-solid fa-chart-pie" style="color: #0284c7;"></i> Collection &amp; Payment Status</h3>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; font-size: 13.5px; font-weight: 700; margin-bottom: 6px;">
                                        <span>Collected Paid Amount</span>
                                        <span style="color: #059669;">৳<?= number_format($totalDpSalesRevenue - $totalDpDue, 2) ?> (<?= $totalDpSalesRevenue > 0 ? round((($totalDpSalesRevenue - $totalDpDue) / $totalDpSalesRevenue) * 100, 1) : 0 ?>%)</span>
                                    </div>
                                    <div style="height: 10px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= $totalDpSalesRevenue > 0 ? min(100, round((($totalDpSalesRevenue - $totalDpDue) / $totalDpSalesRevenue) * 100, 1)) : 0 ?>%; background: #10b981; border-radius: 10px;"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; font-size: 13.5px; font-weight: 700; margin-bottom: 6px;">
                                        <span>Pending Due Amount</span>
                                        <span style="color: #dc2626;">৳<?= number_format($totalDpDue, 2) ?> (<?= $totalDpSalesRevenue > 0 ? round(($totalDpDue / $totalDpSalesRevenue) * 100, 1) : 0 ?>%)</span>
                                    </div>
                                    <div style="height: 10px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= $totalDpSalesRevenue > 0 ? min(100, round(($totalDpDue / $totalDpSalesRevenue) * 100, 1)) : 0 ?>%; background: #ef4444; border-radius: 10px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel-box">
                            <div class="panel-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Quick Operations</h3>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <button type="button" onclick="switchDpSubtab('pos')" style="padding: 14px; background: #e0f2fe; color: #0284c7; border: 1.5px solid #bae6fd; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-cart-plus" style="font-size: 20px;"></i>
                                    <span>New POS Billing</span>
                                </button>
                                <button type="button" onclick="switchDpSubtab('history')" style="padding: 14px; background: #f0fdf4; color: #059669; border: 1.5px solid #bbf7d0; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-list-check" style="font-size: 20px;"></i>
                                    <span>View Invoices</span>
                                </button>
                                <button type="button" onclick="switchDpSubtab('catalog')" style="padding: 14px; background: #faf5ff; color: #7c3aed; border: 1.5px solid #e9d5ff; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; grid-column: span 2;">
                                    <i class="fa-solid fa-table-list" style="font-size: 20px;"></i>
                                    <span>Check DP Rates &amp; Margins Chart</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 1: INVOICES & HISTORY -->
                <div id="dp-panel-history" class="dp-subpanel" style="display: <?= $dpSubtab === 'history' ? 'block' : 'none' ?>;">
                    <div class="panel-box">
                        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;"><i class="fa-solid fa-receipt" style="color: #0284c7;"></i> Wholesale &amp; Dealer Invoices</h3>
                                <span style="font-size: 12.5px; color: #64748b;">Manage customer wholesale billing, invoice receipts and collection status</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="position: relative;">
                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 12px; color: #94a3b8; font-size: 13px;"></i>
                                    <input type="text" id="dp-search-input" onkeyup="filterDpSalesTable()" placeholder="Search dealer, invoice, phone..." style="padding: 8px 12px 8px 34px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; width: 240px;">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table" id="dp-sales-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Dealer / Client</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                        <th>Est. Profit</th>
                                        <th>Payment</th>
                                        <th>Date</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dpSalesList)): ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 45px 20px; color: #64748b;">
                                                <i class="fa-solid fa-handshake" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                                <strong style="font-size: 15px; color: #334155;">No DP Wholesale Sales recorded yet.</strong>
                                                <p style="margin: 4px 0 14px 0; font-size: 13px;">Click '+ New Wholesale Billing (POS)' to create your first dealer invoice with DP rates.</p>
                                                <button type="button" onclick="switchDpSubtab('pos')" style="padding: 8px 18px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">+ Create First DP Sale</button>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($dpSalesList as $d): ?>
                                            <tr class="dp-table-row" data-search="<?= htmlspecialchars(strtolower($d['invoice_no'] . ' ' . $d['dealer_name'] . ' ' . ($d['dealer_phone'] ?? '') . ' ' . ($d['payment_status'] ?? ''))) ?>">
                                                <td style="font-weight: 800; color: #0284c7; font-size: 13.5px;">
                                                    <i class="fa-solid fa-file-invoice" style="margin-right: 4px; font-size: 12px;"></i>
                                                    <?= htmlspecialchars($d['invoice_no']) ?>
                                                </td>
                                                <td>
                                                    <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($d['dealer_name']) ?></strong>
                                                    <?php if (!empty($d['dealer_phone'])): ?>
                                                        <div style="font-size: 11.5px; color: #64748b;"><i class="fa-solid fa-phone" style="font-size: 10px;"></i> <?= htmlspecialchars($d['dealer_phone']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-weight: 900; color: #0f172a; font-size: 14.5px;">৳<?= number_format($d['total_amount'], 2) ?></td>
                                                <td style="font-weight: 800; color: #059669;">৳<?= number_format($d['paid_amount'], 2) ?></td>
                                                <td style="font-weight: 800; color: <?= floatval($d['due_amount']) > 0 ? '#dc2626' : '#64748b' ?>;">
                                                    ৳<?= number_format($d['due_amount'], 2) ?>
                                                </td>
                                                <td style="font-weight: 800; color: #047857; background: #f0fdf4;">
                                                    <?= hasPermission('view_cost_profit') ? '+৳' . number_format($d['total_profit'], 2) : '<span style="color:#94a3b8;">***</span>' ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $pSt = strtolower($d['payment_status'] ?? 'paid');
                                                        $badgeBg = $pSt === 'paid' ? '#dcfce7' : ($pSt === 'partial' ? '#fef3c7' : '#fee2e2');
                                                        $badgeColor = $pSt === 'paid' ? '#15803d' : ($pSt === 'partial' ? '#b45309' : '#b91c1c');
                                                    ?>
                                                    <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid <?= $pSt === 'paid' ? 'fa-check-circle' : 'fa-clock' ?>" style="font-size: 10px;"></i>
                                                        <?= ucfirst($d['payment_status'] ?? 'Paid') ?> (<?= htmlspecialchars($d['payment_method'] ?? 'Cash') ?>)
                                                    </span>
                                                </td>
                                                <td style="font-size: 12px; color: #64748b;">
                                                    <?= date('M d, Y h:i A', strtotime($d['created_at'])) ?>
                                                </td>
                                                <td style="text-align: right; white-space: nowrap;">
                                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                        <button type="button" class="btn-icon" title="View Invoice" onclick="viewDpInvoice(<?= $d['id'] ?>)" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn-icon delete" title="Delete DP Sale" onclick="deleteDpSale(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['invoice_no'])) ?>')" style="color: #ef4444;">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SUBTAB 2: NEW WHOLESALE POS TERMINAL -->
                <div id="dp-panel-pos" class="dp-subpanel" style="display: none;">
                    <div class="panel-box">
                        <div class="panel-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px;">
                            <div>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0284c7;"><i class="fa-solid fa-cart-flatbed"></i> Create New DP Wholesale Invoice</h3>
                                <span style="font-size: 13px; color: #64748b;">Issue wholesale bills with auto-calculated dealer rates, purchase margins, and batch stock deductions</span>
                            </div>
                        </div>

                        <form id="dpSaleForm" onsubmit="submitSaveDpSale(event)">
                            <!-- Dealer & Client Info Box -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 22px;">
                                <h4 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-user-tag" style="color: #0284c7; margin-right: 6px;"></i> 1. Dealer / Client Information
                                </h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Dealer / Store Name *</label>
                                        <input type="text" id="dp-dealer-name" required placeholder="Enter dealer or store name" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Contact Phone</label>
                                        <input type="text" id="dp-dealer-phone" placeholder="Enter contact number" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Delivery / Store Address</label>
                                        <input type="text" id="dp-dealer-address" placeholder="Enter address" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Payment Method</label>
                                        <select id="dp-payment-method" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 700; background: white;">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="bKash / Nagad">bKash / Nagad</option>
                                            <option value="Due / Credit">Due / Credit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Item List Box -->
                            <div style="background: white; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 22px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-boxes-packing" style="color: #0284c7; margin-right: 6px;"></i> 2. Product Items &amp; DP Rates
                                    </h4>
                                    <button type="button" onclick="addDpSaleRow()" style="padding: 6px 14px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; border-radius: 6px; font-size: 12.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-plus"></i> Add Another Item
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table style="width: 100%; border-collapse: collapse;" id="dpItemsTable">
                                        <thead>
                                            <tr style="background: #f1f5f9; text-align: left; font-size: 12px; color: #475569; font-weight: 800;">
                                                <th style="padding: 10px; width: 35%;">Product Item *</th>
                                                <th style="padding: 10px; width: 20%;">Batch</th>
                                                <th style="padding: 10px; width: 15%;">DP Rate ৳</th>
                                                <th style="padding: 10px; width: 12%;">Qty</th>
                                                <th style="padding: 10px; width: 15%;">Total ৳</th>
                                                <th style="padding: 10px; width: 3%; text-align: center;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="dpItemsBody">
                                            <!-- Dynamic Rows populated via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Calculation & Summary Footer Box -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">Notes / Remarks</label>
                                    <textarea id="dp-notes" rows="4" placeholder="Special bulk dealer discount applied. Delivered via Transport." style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; box-sizing: border-box; resize: vertical;"></textarea>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 700; color: #475569;">
                                        <span>Subtotal:</span>
                                        <span id="dp-summary-subtotal" style="font-weight: 900; color: #0f172a;">৳0.00</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; font-weight: 700; color: #475569;">
                                        <span>Special Discount ৳:</span>
                                        <input type="number" id="dp-discount" class="no-spin-input" step="0.5" onkeyup="calcDpSummary()" onchange="calcDpSummary()" style="width: 120px; padding: 6px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-weight: 800; text-align: right; outline: none; -moz-appearance: textfield; appearance: textfield;">
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; font-weight: 700; color: #475569;">
                                        <span>Delivery / Transport Charge ৳:</span>
                                        <input type="number" id="dp-delivery-charge" class="no-spin-input" step="0.5" onkeyup="calcDpSummary()" onchange="calcDpSummary()" style="width: 120px; padding: 6px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-weight: 800; text-align: right; outline: none; -moz-appearance: textfield; appearance: textfield;">
                                    </div>
                                    <div style="border-top: 1.5px solid #cbd5e1; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 900; color: #0284c7;">
                                        <span>Net Payable:</span>
                                        <span id="dp-summary-total" style="font-size: 18px;">৳0.00</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 700; color: #059669;">
                                        <span>Paid Amount ৳:</span>
                                        <input type="number" id="dp-paid-amount" class="no-spin-input" step="0.5" onkeyup="calcDpSummary()" onchange="calcDpSummary()" style="width: 120px; padding: 6px 10px; border: 1.5px solid #a7f3d0; border-radius: 6px; font-weight: 800; color: #047857; text-align: right; outline: none; -moz-appearance: textfield; appearance: textfield;">
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14.5px; font-weight: 900; color: #dc2626;">
                                        <span>Due Balance:</span>
                                        <span id="dp-summary-due">৳0.00</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                <button type="button" onclick="switchDpSubtab('history')" style="padding: 11px 20px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                                <button type="submit" id="saveDpSaleBtn" style="padding: 11px 28px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3); display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> Confirm &amp; Save DP Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SUBTAB 3: DP RATE CHART & MARGINS -->
                <div id="dp-panel-catalog" class="dp-subpanel" style="display: none;">
                    <div class="panel-box">
                        <div class="panel-header">
                            <div>
                                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;"><i class="fa-solid fa-table-list" style="color: #0284c7;"></i> Product DP Rate &amp; Margin Matrix</h3>
                                <span style="font-size: 12.5px; color: #64748b;">Compare Cost Price (CP), Dealer Rate (DP), and Customer MRP for wholesale pricing</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product Code &amp; Title</th>
                                        <th>Active Batch</th>
                                        <th>Cost Price (CP)</th>
                                        <th>Dealer Rate (DP)</th>
                                        <th>Retail MRP</th>
                                        <th>Dealer Margin (৳)</th>
                                        <th>Wholesale Profit (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productList as $p): ?>
                                        <?php 
                                            // Find pricing for this product
                                            $matchedPrice = null;
                                            foreach ($pricingList as $pr) {
                                                if (intval($pr['product_id']) === intval($p['id'])) {
                                                    $matchedPrice = $pr;
                                                    break;
                                                }
                                            }
                                            $cp = $matchedPrice ? floatval($matchedPrice['cost_price']) : round(floatval($p['price']) * 0.8, 2);
                                            $dp = $matchedPrice ? floatval($matchedPrice['dp_price']) : round(floatval($p['price']) * 0.9, 2);
                                            $mrp = $matchedPrice ? floatval($matchedPrice['mrp_price']) : floatval($p['old_price'] ?: ($p['price'] * 1.15));
                                            $margin = round($dp - $cp, 2);
                                            $marginPct = $cp > 0 ? round(($margin / $cp) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($p['title']) ?></strong>
                                                <div style="font-size: 11.5px; color: #64748b;"><?= htmlspecialchars($p['product_code'] ?? ('PRD-' . $p['id'])) ?> (<?= htmlspecialchars($p['poriman'] ?? '1 unit') ?>)</div>
                                            </td>
                                            <td>
                                                <span style="color: #2563eb; font-weight: 700; font-size: 12px;"><?= htmlspecialchars($matchedPrice['batch_number'] ?? 'Default') ?></span>
                                            </td>
                                            <td style="font-weight: 700; color: #64748b;">
                                                <?= hasPermission('view_cost_profit') ? '৳' . number_format($cp, 2) : '<span style="color:#94a3b8;">৳***</span>' ?>
                                            </td>
                                            <td style="font-weight: 900; color: #0284c7; font-size: 14.5px;">৳<?= number_format($dp, 2) ?></td>
                                            <td style="font-weight: 700; color: #94a3b8; text-decoration: line-through;">৳<?= number_format($mrp, 2) ?></td>
                                            <td style="font-weight: 800; color: <?= $margin >= 0 ? '#059669' : '#dc2626' ?>;">
                                                <?= hasPermission('view_cost_profit') ? '৳' . number_format($margin, 2) : '<span style="color:#94a3b8;">***</span>' ?>
                                            </td>
                                            <td>
                                                <span style="background: <?= $marginPct >= 0 ? '#ecfdf5' : '#fee2e2' ?>; color: <?= $marginPct >= 0 ? '#047857' : '#b91c1c' ?>; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 800;">
                                                    <?= hasPermission('view_cost_profit') ? $marginPct . '%' : '***' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <!-- TAB: STAFF & RIDER DELIVERY OPERATIONS PANEL -->
        <?php elseif ($tab === 'staff_panel' || $tab === 'staff_ops'): ?>
            <?php
                $subtabStaff = $_GET['subtab_staff'] ?? 'pending';
                if (!in_array($subtabStaff, ['pending', 'receive', 'delivery'])) {
                    $subtabStaff = 'pending';
                }
                $pendingType = $_GET['pending_type'] ?? 'cart';
                if (!in_array($pendingType, ['custom', 'cart'])) {
                    $pendingType = 'cart';
                }
            ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- 3 Top Navigation Switcher Cards -->
                <div style="background: white; border-radius: 18px; border: 1.5px solid #e2e8f0; padding: 14px 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                        <a href="dashboard.php?tab=staff_panel&subtab_staff=pending" style="text-decoration: none; padding: 14px 20px; border-radius: 12px; font-size: 15px; font-weight: 800; border: 2px solid <?= $subtabStaff === 'pending' ? '#10b981' : '#e2e8f0' ?>; background: <?= $subtabStaff === 'pending' ? '#ecfdf5' : '#f8fafc' ?>; color: <?= $subtabStaff === 'pending' ? '#047857' : '#334155' ?>; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtabStaff === 'pending' ? 'box-shadow: 0 4px 14px rgba(16, 185, 129, 0.2);' : '' ?>">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Pending Orders &amp; Lists</span>
                            <span style="background: <?= $subtabStaff === 'pending' ? '#10b981' : '#e2e8f0' ?>; color: <?= $subtabStaff === 'pending' ? 'white' : '#475569' ?>; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 900;"><?= count($staffCartOrders) + count($staffCustomLists) ?></span>
                        </a>
                        <a href="dashboard.php?tab=staff_panel&subtab_staff=receive" style="text-decoration: none; padding: 14px 20px; border-radius: 12px; font-size: 15px; font-weight: 800; border: 2px solid <?= $subtabStaff === 'receive' ? '#f59e0b' : '#e2e8f0' ?>; background: <?= $subtabStaff === 'receive' ? '#fef3c7' : '#f8fafc' ?>; color: <?= $subtabStaff === 'receive' ? '#b45309' : '#334155' ?>; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtabStaff === 'receive' ? 'box-shadow: 0 4px 14px rgba(245, 158, 11, 0.2);' : '' ?>">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                            <span>Receive &amp; Invoice</span>
                            <span style="background: <?= $subtabStaff === 'receive' ? '#f59e0b' : '#e2e8f0' ?>; color: <?= $subtabStaff === 'receive' ? 'white' : '#475569' ?>; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 900;"><?= count($staffReceiveLists) ?></span>
                        </a>
                        <a href="dashboard.php?tab=staff_panel&subtab_staff=delivery" style="text-decoration: none; padding: 14px 20px; border-radius: 12px; font-size: 15px; font-weight: 800; border: 2px solid <?= $subtabStaff === 'delivery' ? '#2563eb' : '#e2e8f0' ?>; background: <?= $subtabStaff === 'delivery' ? '#eff6ff' : '#f8fafc' ?>; color: <?= $subtabStaff === 'delivery' ? '#1d4ed8' : '#334155' ?>; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $subtabStaff === 'delivery' ? 'box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);' : '' ?>">
                            <i class="fa-solid fa-truck-fast"></i>
                            <span>Delivery Queue &amp; Delivered</span>
                            <span style="background: <?= $subtabStaff === 'delivery' ? '#2563eb' : '#e2e8f0' ?>; color: <?= $subtabStaff === 'delivery' ? 'white' : '#475569' ?>; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 900;"><?= count($staffDeliveryOrders) ?></span>
                        </a>
                    </div>
                </div>

                <!-- Main Header Title & Location Info -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="font-size: 26px; font-weight: 900; color: #0f172a; margin: 0 0 4px 0;">
                            <?php if ($subtabStaff === 'pending'): ?>
                                📥 Pending Orders &amp; Custom Lists
                            <?php elseif ($subtabStaff === 'receive'): ?>
                                🧾 Receive Orders (Invoice Generation)
                            <?php elseif ($subtabStaff === 'delivery'): ?>
                                🚀 Ready for Delivery &amp; Delivered
                            <?php endif; ?>
                        </h2>
                        <p style="color: #6b7280; margin: 0; font-size: 14px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <strong style="color: #1e293b;">Assigned Delivery Zones:</strong>
                            <?php if ($userRoleType === 'staff'): ?>
                                <?php if (!empty($myAssignedLocations)): ?>
                                    <?php foreach ($myAssignedLocations as $mal): ?>
                                        <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 3px 10px; border-radius: 12px; font-weight: 700; font-size: 12.5px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-location-dot" style="color: #10b981;"></i> <?= htmlspecialchars($mal['police_station']) ?>, <?= htmlspecialchars($mal['district']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-weight: 700; font-size: 13px;"><i class="fa-solid fa-triangle-exclamation"></i> No delivery zones assigned yet. Please contact Admin.</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #10b981; font-weight: 700; font-size: 14px;"><i class="fa-solid fa-earth-asia"></i> All Locations (Admin / Store Manager Overview)</span>
                                <?php if (!empty($selectedRiderFilter)): ?>
                                    <?php 
                                        $fRider = array_filter($staffList, fn($s) => $s['id'] == $selectedRiderFilter);
                                        $fRiderName = !empty($fRider) ? reset($fRider)['name'] : "Rider #{$selectedRiderFilter}";
                                    ?>
                                    <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 3px 10px; border-radius: 12px; font-weight: 700; font-size: 12px;">
                                        Filtered for: <strong><?= htmlspecialchars($fRiderName) ?></strong>
                                        <a href="dashboard.php?tab=staff_panel&subtab_staff=<?= $subtabStaff ?>" style="color: #ef4444; margin-left: 5px; text-decoration: none;">&times; Clear</a>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($subtabStaff === 'pending'): ?>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="dashboard.php?tab=staff_panel&subtab_staff=pending&pending_type=custom" class="btn-outline" style="padding: 10px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; <?= $pendingType !== 'cart' ? 'background: #ecfdf5; border: 2px solid #10b981; color: #047857;' : 'background: white; border: 1px solid #d1d5db; color: #374151;' ?>">
                                <i class="fa-solid fa-file-pen" style="<?= $pendingType !== 'cart' ? 'color: #047857;' : 'color: #9ca3af;' ?>"></i> Customer Custom Orders
                            </a>
                            <a href="dashboard.php?tab=staff_panel&subtab_staff=pending&pending_type=cart" class="btn-outline" style="padding: 10px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; <?= $pendingType === 'cart' ? 'background: #ecfdf5; border: 2px solid #10b981; color: #047857;' : 'background: white; border: 1px solid #d1d5db; color: #374151;' ?>">
                                <i class="fa-solid fa-cart-shopping" style="<?= $pendingType === 'cart' ? 'color: #047857;' : 'color: #9ca3af;' ?>"></i> Direct Store Orders (Cart)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Subtab Content -->
                <?php if ($subtabStaff === 'pending'): ?>
                    <?php if ($pendingType !== 'cart'): ?>
                        <!-- Custom Orders Pending -->
                        <h3 style="color: #374151; font-size: 16px; margin: 0 0 5px 0;">
                            <i class="fa-solid fa-file-pen" style="color: #10b981;"></i> Customer Custom Orders Awaiting Review
                        </h3>
                        <?php if (!empty($staffCustomLists)): ?>
                            <div class="card-list-grid">
                                <?php foreach ($staffCustomLists as $item): ?>
                                    <div class="staff-card">
                                        <div class="staff-card-thumb">
                                            <?php if (!empty($item['images'])): ?>
                                                <img src="<?= htmlspecialchars(explode(',', $item['images'])[0]) ?>" alt="List Photo">
                                            <?php else: ?>
                                                <i class="fa-solid fa-file-lines"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="staff-card-body">
                                            <h3 class="staff-card-title">
                                                Order <?= htmlspecialchars($item['list_code'] ?: ('LST-' . $item['id'])) ?>
                                                <?php if (!empty($item['images'])): ?>
                                                    <span class="badge" style="background: #e0e7ff; color: #4338ca;"><i class="fa-solid fa-image"></i> Photo</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: #fef3c7; color: #b45309;"><i class="fa-solid fa-file-lines"></i> Written</span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['matched_zone'])): ?>
                                                    <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"><i class="fa-solid fa-location-crosshairs" style="color: #2563eb;"></i> <?= htmlspecialchars($item['matched_zone']) ?></span>
                                                <?php endif; ?>
                                                <span class="badge" style="background: #fef3c7; color: #b45309;"><i class="fa-solid fa-clock"></i> Pending Review</span>
                                            </h3>
                                            <div class="staff-card-meta">
                                                <span><i class="fa-solid fa-user" style="color: #9ca3af;"></i> <strong><?= htmlspecialchars($item['customer_name'] ?: 'Customer') ?></strong></span>
                                                <a href="tel:<?= htmlspecialchars($item['customer_phone']) ?>"><i class="fa-solid fa-phone" style="color: #10b981;"></i> <?= htmlspecialchars($item['customer_phone']) ?></a>
                                            </div>
                                            <div class="staff-card-sub">
                                                <span><i class="fa-solid fa-location-dot" style="color: #10b981;"></i> <?= htmlspecialchars($item['customer_address'] ?: 'Location not specified') ?></span>
                                                <span style="margin-left: auto; text-align: right; color: #6b7280; font-size: 12.5px; white-space: nowrap;">
                                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['formatted_date']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="staff-card-action">
                                            <button type="button" class="btn-outline" onclick="openStaffCustomListModal(<?= $item['id'] ?>)">
                                                <i class="fa-solid fa-eye"></i> View Details
                                            </button>
                                            <a href="dashboard.php?action=staff_accept_custom_list&id=<?= $item['id'] ?>&tab=staff_panel&subtab_staff=pending&pending_type=custom" class="btn-primary" style="background: #00c675; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px;">
                                                <i class="fa-solid fa-check"></i> Accept (OK)
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="background: white; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; color: #6b7280; margin-bottom: 30px;">
                                <i class="fa-solid fa-inbox" style="font-size: 40px; color: #cbd5e1; margin-bottom: 12px;"></i>
                                <p style="margin: 0; font-size: 15px;">No pending custom orders awaiting review.</p>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Direct Store Orders (Cart) -->
                        <h3 style="color: #374151; font-size: 16px; margin: 0 0 5px 0;">
                            <i class="fa-solid fa-cart-shopping" style="color: #10b981;"></i> Direct Store Orders (Cart)
                        </h3>
                        <?php if (!empty($staffCartOrders)): ?>
                            <div class="card-list-grid">
                                <?php foreach ($staffCartOrders as $order): ?>
                                    <div class="staff-card" style="border-left: 4px solid #10b981;">
                                        <div class="staff-card-thumb" style="background: #ecfdf5;">
                                            <i class="fa-solid fa-receipt" style="color: #047857;"></i>
                                        </div>
                                        <div class="staff-card-body">
                                            <h3 class="staff-card-title">
                                                Invoice <?= htmlspecialchars($order['invoice_number'] ?: $order['order_number']) ?>
                                                <span style="color: #047857; background: #d1fae5; padding: 3px 10px; border-radius: 12px; font-size: 14px; font-weight: 800; margin-left: 5px;">৳<?= number_format($order['total_amount'], 2) ?></span>
                                                <?php if (!empty($order['matched_zone'])): ?>
                                                    <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"><i class="fa-solid fa-location-crosshairs" style="color: #2563eb;"></i> <?= htmlspecialchars($order['matched_zone']) ?></span>
                                                <?php endif; ?>
                                                <span class="badge" style="background: #fef3c7; color: #b45309;">Pending</span>
                                            </h3>
                                            <div class="staff-card-meta">
                                                <span><i class="fa-solid fa-user" style="color: #9ca3af;"></i> <strong><?= htmlspecialchars($order['customer_name']) ?></strong></span>
                                                <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>"><i class="fa-solid fa-phone" style="color: #10b981;"></i> <?= htmlspecialchars($order['customer_phone']) ?></a>
                                            </div>
                                            <div class="staff-card-sub">
                                                <span><i class="fa-solid fa-location-dot" style="color: #10b981;"></i> <?= htmlspecialchars($order['customer_address'] ?: $order['police_station'] ?: 'Location not specified') ?></span>
                                                <span style="margin-left: auto; text-align: right; color: #6b7280; font-size: 12.5px; white-space: nowrap;">
                                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($order['formatted_date_only']) ?><br><?= htmlspecialchars($order['formatted_time_only']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="staff-card-action">
                                            <button type="button" class="btn-outline" onclick="viewStaffOrderModal(<?= $order['id'] ?>)">
                                                <i class="fa-solid fa-eye"></i> View Details
                                            </button>
                                            <a href="dashboard.php?action=staff_accept_order&id=<?= $order['id'] ?>&tab=staff_panel&subtab_staff=pending&pending_type=cart" class="btn-primary" style="background: #00c675; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px;">
                                                <i class="fa-solid fa-check"></i> Accept (OK)
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="background: white; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; color: #6b7280;">
                                <i class="fa-solid fa-clipboard-check" style="font-size: 40px; color: #cbd5e1; margin-bottom: 12px;"></i>
                                <p style="margin: 0; font-size: 15px;">No pending store orders found in queue.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php elseif ($subtabStaff === 'receive'): ?>
                    <!-- Receive Orders / Accepted Custom Lists ready for pricing -->
                    <h3 style="color: #374151; font-size: 16px; margin: 0 0 5px 0;">
                        <i class="fa-solid fa-file-invoice-dollar" style="color: #b45309;"></i> Accepted Lists Ready for Invoice Creation
                    </h3>
                    <?php if (!empty($staffReceiveLists)): ?>
                        <div class="card-list-grid">
                            <?php foreach ($staffReceiveLists as $item): ?>
                                <div class="staff-card" style="border-left: 4px solid #f59e0b;">
                                    <div class="staff-card-thumb" style="background: #fef3c7;">
                                        <i class="fa-solid fa-file-invoice-dollar" style="color: #b45309;"></i>
                                    </div>
                                    <div class="staff-card-body">
                                        <h3 class="staff-card-title">
                                            Order <?= htmlspecialchars($item['list_code'] ?: ('LST-' . $item['id'])) ?>
                                            <?php if (!empty($item['images'])): ?>
                                                <span class="badge" style="background: #e0e7ff; color: #4338ca;"><i class="fa-solid fa-image"></i> Photo</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #fef3c7; color: #b45309;"><i class="fa-solid fa-file-lines"></i> Written</span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['matched_zone'])): ?>
                                                <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"><i class="fa-solid fa-location-crosshairs" style="color: #2563eb;"></i> <?= htmlspecialchars($item['matched_zone']) ?></span>
                                            <?php endif; ?>
                                            <span class="badge" style="background: #d1fae5; color: #047857;"><i class="fa-solid fa-check"></i> Accepted</span>
                                        </h3>
                                        <div class="staff-card-meta">
                                            <span><i class="fa-solid fa-user" style="color: #9ca3af;"></i> <strong><?= htmlspecialchars($item['customer_name'] ?: 'Customer') ?></strong></span>
                                            <a href="tel:<?= htmlspecialchars($item['customer_phone']) ?>"><i class="fa-solid fa-phone" style="color: #10b981;"></i> <?= htmlspecialchars($item['customer_phone']) ?></a>
                                        </div>
                                        <div class="staff-card-sub">
                                            <span><i class="fa-solid fa-location-dot" style="color: #10b981;"></i> <?= htmlspecialchars($item['customer_address'] ?: 'Location not specified') ?></span>
                                            <span style="margin-left: auto; text-align: right; color: #6b7280; font-size: 12.5px; white-space: nowrap;">
                                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['formatted_date']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="staff-card-action">
                                        <button type="button" class="btn-outline" onclick="openStaffCustomListModal(<?= $item['id'] ?>)">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </button>
                                        <a href="staff_invoice_edit.php?custom_list_id=<?= $item['id'] ?>&order_id=<?= $item['id'] ?>" class="btn-warning" style="background: #f59e0b; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px;">
                                            <i class="fa-solid fa-file-invoice-dollar"></i> Price Bazar &amp; Generate Invoice
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="background: white; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; color: #6b7280;">
                            <i class="fa-solid fa-clipboard-check" style="font-size: 40px; color: #cbd5e1; margin-bottom: 12px;"></i>
                            <p style="margin: 0; font-size: 15px;">No lists awaiting invoice generation. Accepted lists will appear here.</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($subtabStaff === 'delivery'): ?>
                    <!-- Ready for Delivery & Delivered Orders -->
                    <h3 style="color: #374151; font-size: 16px; margin: 0 0 5px 0;">
                        <i class="fa-solid fa-truck-fast" style="color: #10b981;"></i> Ready for Delivery &amp; Delivered Queue
                    </h3>
                    <?php if (!empty($staffDeliveryOrders)): ?>
                        <div class="card-list-grid">
                            <?php foreach ($staffDeliveryOrders as $order): ?>
                                <?php
                                    $borderCol = '#3b82f6';
                                    $bgThumb = '#dbeafe';
                                    $iconCol = '#1d4ed8';
                                    if ($order['status'] === 'completed') {
                                        $borderCol = '#047857'; $bgThumb = '#d1fae5'; $iconCol = '#047857';
                                    } elseif ($order['status'] === 'delivering') {
                                        $borderCol = '#6366f1'; $bgThumb = '#e0e7ff'; $iconCol = '#4f46e5';
                                    } elseif ($order['status'] === 'cancelled') {
                                        $borderCol = '#ef4444'; $bgThumb = '#fee2e2'; $iconCol = '#dc2626';
                                    }
                                ?>
                                <div class="staff-card" style="border-left: 4px solid <?= $borderCol ?>;">
                                    <div class="staff-card-thumb" style="background: <?= $bgThumb ?>;">
                                        <i class="fa-solid fa-truck-fast" style="color: <?= $iconCol ?>;"></i>
                                    </div>
                                    <div class="staff-card-body">
                                        <h3 class="staff-card-title">
                                            Invoice <?= htmlspecialchars($order['invoice_number'] ?: $order['order_number']) ?>
                                            <span style="color: #047857; background: #d1fae5; padding: 3px 10px; border-radius: 12px; font-size: 14px; font-weight: 800; margin-left: 5px;">৳<?= number_format($order['total_amount'], 2) ?></span>
                                            <?php if (!empty($order['matched_zone'])): ?>
                                                <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"><i class="fa-solid fa-location-crosshairs" style="color: #2563eb;"></i> <?= htmlspecialchars($order['matched_zone']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($order['status'] === 'completed'): ?>
                                                <span class="badge" style="background: #d1fae5; color: #047857;"><i class="fa-solid fa-circle-check"></i> Delivered</span>
                                            <?php elseif ($order['status'] === 'delivering'): ?>
                                                <span class="badge" style="background: #e0e7ff; color: #3730a3;"><i class="fa-solid fa-person-biking"></i> Out for Delivery</span>
                                            <?php elseif ($order['status'] === 'cancelled'): ?>
                                                <span class="badge" style="background: #fee2e2; color: #b91c1c;">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #e0e7ff; color: #4338ca;"><?= ucfirst($order['status']) ?></span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="staff-card-meta">
                                            <span><i class="fa-solid fa-user" style="color: #9ca3af;"></i> <strong><?= htmlspecialchars($order['customer_name']) ?></strong></span>
                                            <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>"><i class="fa-solid fa-phone" style="color: #10b981;"></i> <?= htmlspecialchars($order['customer_phone']) ?></a>
                                        </div>
                                        <div class="staff-card-sub">
                                            <span><i class="fa-solid fa-location-dot" style="color: #10b981;"></i> <?= htmlspecialchars($order['customer_address'] ?: $order['police_station'] ?: 'Location not specified') ?></span>
                                            <span style="margin-left: auto; text-align: right; color: #6b7280; font-size: 12.5px; white-space: nowrap;">
                                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($order['formatted_date_only']) ?><br><?= htmlspecialchars($order['formatted_time_only']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="staff-card-action">
                                        <button type="button" class="btn-outline" onclick="viewStaffOrderModal(<?= $order['id'] ?>)">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </button>
                                        <?php if ($order['status'] === 'delivering'): ?>
                                            <a href="dashboard.php?action=staff_deliver_order&id=<?= $order['id'] ?>&tab=staff_panel&subtab_staff=delivery" class="btn-primary" style="background: #059669; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px;">
                                                <i class="fa-solid fa-check-double"></i> Mark Delivered
                                            </a>
                                        <?php elseif ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                            <a href="dashboard.php?action=staff_ship_order&id=<?= $order['id'] ?>&tab=staff_panel&subtab_staff=delivery" class="btn-primary" style="background: #2563eb; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px;">
                                                <i class="fa-solid fa-person-biking"></i> Start Delivery
                                            </a>
                                            <a href="dashboard.php?action=staff_deliver_order&id=<?= $order['id'] ?>&tab=staff_panel&subtab_staff=delivery" class="btn-outline" style="color: #059669; border-color: #10b981; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 12px;">
                                                <i class="fa-solid fa-check"></i> Deliver Direct
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #047857; font-weight: 800; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                                                <i class="fa-solid fa-circle-check"></i> Completed
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="background: white; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; color: #6b7280;">
                            <i class="fa-solid fa-truck-fast" style="font-size: 40px; color: #cbd5e1; margin-bottom: 12px;"></i>
                            <p style="margin: 0; font-size: 15px;">No active delivery orders found.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Staff Clean A4 Preview Modal -->
            <div id="staff-admin-preview-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; flex-direction: column; align-items: center; overflow-y: auto; padding: 40px 20px; box-sizing: border-box;" onclick="if(event.target===this)closeStaffAdminPreviewModal()">
                <div style="width: 100%; max-width: 800px; position: relative;" id="staff-preview-wrapper">
                    <button type="button" onclick="closeStaffAdminPreviewModal()" title="Close Preview" style="position: absolute; top: -16px; right: -16px; background: #ffffff; border: 2px solid #e2e8f0; width: 42px; height: 42px; border-radius: 50%; font-size: 18px; color: #1e293b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); z-index: 3100; box-shadow: 0 4px 16px rgba(0,0,0,0.18);" onmouseover="this.style.background='#fee2e2'; this.style.color='#ef4444'; this.style.borderColor='#fca5a5'; this.style.transform='scale(1.08)';" onmouseout="this.style.background='#ffffff'; this.style.color='#1e293b'; this.style.borderColor='#e2e8f0'; this.style.transform='scale(1)';">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div id="staff-modal-content-area">
                        <!-- Pure A4 Document content injected here -->
                    </div>
                </div>
            </div>

            <script>
                window.allStaffOrdersMap = <?= json_encode(array_column(array_merge($staffCartOrders, $staffDeliveryOrders), null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || {};
                window.allStaffCustomListsMap = <?= json_encode(array_column(array_merge($staffCustomLists, $staffReceiveLists), null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || {};

                function generateStaffA4SinglePage({ name, phone, location, orderNo, dateTime, contentHtml, pageIndex = 0, totalPages = 1, footerExtra = '' }) {
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

                function parseStaffListItems(rawText) {
                    if (!rawText) return { type: 'rows', items: [] };
                    const lines = rawText.split('\n').map(l => l.trim()).filter(Boolean);
                    const items = [];
                    lines.forEach((line, idx) => {
                        let text = line.replace(/^[0-9\.\-\*\•\)\s]+/, '').trim();
                        let spec = '';
                        const match = text.match(/^(.*?)\s*[\(\[\{](.*?)[\)\]\}]\s*$/);
                        if (match) {
                            text = match[1].trim();
                            spec = match[2].trim();
                        }
                        items.push({
                            sl: idx + 1,
                            item: text || line,
                            spec: spec,
                            qty: '1'
                        });
                    });
                    return { type: 'rows', items };
                }

                function viewStaffOrderModal(orderId) {
                    const order = window.allStaffOrdersMap[orderId];
                    if (!order) return;

                    const itemCode = order.invoice_number || order.order_number || ('ORD-' + order.id);
                    const custName = order.customer_name || 'Customer';
                    const custPhone = order.customer_phone || '';
                    const custAddress = order.customer_address || order.police_station || 'N/A';
                    const custDate = order.created_at || 'Today';

                    let contentHtml = '';
                    if (order.html_content && order.html_content.trim() !== '') {
                        contentHtml = order.html_content;
                    } else {
                        let footerExtra = `
                            <div style="text-align: right;">
                                <span style="font-size: 13px; color: #64748b; font-weight: 700;">Total: </span>
                                <strong style="font-size: 20px; font-weight: 900; color: #00c675;">৳${parseFloat(order.total_amount || 0).toFixed(2)}</strong>
                            </div>
                        `;
                        let fallbackContent = `
                            <div style="background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #0f172a; font-size: 18px;">Order #${order.order_number || order.id}</h4>
                                <p style="margin: 0; color: #64748b;">Status: <span style="font-weight: 700; color: #059669;">${order.status}</span></p>
                            </div>
                        `;
                        contentHtml = generateStaffA4SinglePage({
                            name: custName,
                            phone: custPhone,
                            location: custAddress,
                            orderNo: itemCode,
                            dateTime: custDate,
                            contentHtml: fallbackContent,
                            pageIndex: 0,
                            totalPages: 1,
                            footerExtra
                        });
                    }

                    let actionBtnHtml = '';
                    if (order.status === 'pending') {
                        actionBtnHtml = `
                            <div class="no-print" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); width: 100%; max-width: 800px; box-sizing: border-box; margin: 12px auto 0 auto;">
                                <button type="button" onclick="closeStaffAdminPreviewModal()" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'">
                                    Close
                                </button>
                                <a href="dashboard.php?action=staff_accept_order&id=${order.id}&tab=staff_panel&subtab_staff=pending&pending_type=cart" style="background: #00c675; color: white; padding: 9px 24px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3);">
                                    <i class="fa-solid fa-check"></i> Accept (OK)
                                </a>
                                <button type="button" onclick="window.print()" style="background: #00c675; border: none; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;" onmouseover="this.style.background='#00b368'" onmouseout="this.style.background='#00c675'">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        `;
                    } else if (order.status === 'delivering') {
                        actionBtnHtml = `
                            <div class="no-print" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); width: 100%; max-width: 800px; box-sizing: border-box; margin: 12px auto 0 auto;">
                                <button type="button" onclick="closeStaffAdminPreviewModal()" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'">
                                    Close
                                </button>
                                <a href="dashboard.php?action=staff_deliver_order&id=${order.id}&tab=staff_panel&subtab_staff=delivery" style="background: #059669; color: white; padding: 9px 24px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);">
                                    <i class="fa-solid fa-check-double"></i> Mark Delivered
                                </a>
                                <button type="button" onclick="window.print()" style="background: #00c675; border: none; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;" onmouseover="this.style.background='#00b368'" onmouseout="this.style.background='#00c675'">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        `;
                    } else {
                        actionBtnHtml = `
                            <div class="no-print" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); width: 100%; max-width: 800px; box-sizing: border-box; margin: 12px auto 0 auto;">
                                <a href="staff_invoice_edit.php?order_id=${order.id}" style="background: #ffffff; border: 1.5px solid #10b981; color: #059669; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1); transition: all 0.2s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#ffffff'">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <button type="button" onclick="closeStaffAdminPreviewModal()" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'">
                                    Close
                                </button>
                                <button type="button" onclick="window.print()" style="background: #00c675; border: none; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;" onmouseover="this.style.background='#00b368'" onmouseout="this.style.background='#00c675'">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        `;
                    }

                    document.getElementById('staff-modal-content-area').innerHTML = contentHtml + actionBtnHtml;
                    document.getElementById('staff-admin-preview-modal').style.display = 'flex';
                }

                function openStaffCustomListModal(listId) {
                    const item = window.allStaffCustomListsMap[listId];
                    if (!item) return;

                    const itemCode = item.list_code || ('LST-' + item.id);
                    const custName = item.customer_name || 'Customer';
                    const custPhone = item.customer_phone || '';
                    const custAddress = item.customer_address || item.police_station || 'N/A';
                    const custDate = item.created_at || 'Today';

                    let contentHtml = '';

                    // Check if photo or text
                    const hasImages = (item.images && item.images.trim() !== '') || !!item.image_url;
                    if (hasImages) {
                        const imgArr = item.images ? item.images.split(',').map(s => s.trim()).filter(Boolean) : [item.image_url];
                        const totalPages = imgArr.length || 1;
                        let fullHtml = '<div style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">';
                        for (let p = 0; p < totalPages; p++) {
                            const img = imgArr[p];
                            const isFirst = (p === 0);
                            const maxImgHeight = isFirst ? '640px' : '820px';
                            const grid = `
                                <div style="display: flex; justify-content: center; align-items: center; margin: 15px 0; position: relative; z-index: 2; width: 100%; flex: 1;">
                                    <div style="border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fafafa; width: 100%; display: flex; justify-content: center; align-items: center; padding: 12px; box-sizing: border-box; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                                        <img src="${img}" alt="Grocery List Photo ${p + 1}" style="max-width: 100%; max-height: ${maxImgHeight}; width: auto; height: auto; object-fit: contain; display: block; border-radius: 6px;">
                                    </div>
                                </div>
                            `;
                            fullHtml += generateStaffA4SinglePage({
                                name: custName,
                                phone: custPhone,
                                location: custAddress,
                                orderNo: itemCode,
                                dateTime: custDate,
                                contentHtml: grid,
                                pageIndex: p,
                                totalPages
                            });
                        }
                        fullHtml += '</div>';
                        contentHtml = fullHtml;
                    } else {
                        const rawText = item.content_text || '';
                        if (rawText.includes('<div') || rawText.includes('a4-page')) {
                            contentHtml = rawText;
                        } else {
                            const parsed = parseStaffListItems(rawText);
                            let rowsHtml = '';
                            parsed.items.forEach(row => {
                                const specHtml = row.spec ? ` <span style="color: #64748b; font-weight: 500;">(${row.spec})</span>` : '';
                                rowsHtml += `
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700; text-align: center;">${row.sl}</td>
                                        <td style="padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700;">${row.item}${specHtml}</td>
                                        <td style="padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13.5px; font-weight: 800;">${row.qty || '1'}</td>
                                    </tr>
                                `;
                            });
                            const tableHtml = `
                                <table style="width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-top: 1.5px solid #e2e8f0; border-bottom: 1.5px solid #e2e8f0;">
                                            <th style="padding: 11px 8px; color: #475569; font-size: 12.5px; font-weight: 800; width: 45px; text-align: center;">SL</th>
                                            <th style="padding: 11px 8px; color: #475569; font-size: 12.5px; font-weight: 800;">Item Description</th>
                                            <th style="padding: 11px 8px; color: #475569; text-align: center; font-size: 12.5px; font-weight: 800; width: 95px;">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rowsHtml}</tbody>
                                </table>
                            `;
                            contentHtml = generateStaffA4SinglePage({
                                name: custName,
                                phone: custPhone,
                                location: custAddress,
                                orderNo: itemCode,
                                dateTime: custDate,
                                contentHtml: tableHtml,
                                pageIndex: 0,
                                totalPages: 1
                            });
                        }
                    }

                    let actionBtnHtml = '';
                    if (parseInt(item.is_accepted) === 1 || item.status === 'accepted' || item.status === 'processing') {
                        actionBtnHtml = `
                            <div class="no-print" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); width: 100%; max-width: 800px; box-sizing: border-box; margin: 12px auto 0 auto;">
                                <a href="staff_invoice_edit.php?custom_list_id=${item.id}&order_id=${item.id}" style="background: #ffffff; border: 1.5px solid #10b981; color: #059669; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1); transition: all 0.2s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#ffffff'">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <button type="button" onclick="closeStaffAdminPreviewModal()" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'">
                                    Close
                                </button>
                                <button type="button" onclick="window.print()" style="background: #00c675; border: none; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;" onmouseover="this.style.background='#00b368'" onmouseout="this.style.background='#00c675'">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        `;
                    } else {
                        actionBtnHtml = `
                            <div class="no-print" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); width: 100%; max-width: 800px; box-sizing: border-box; margin: 12px auto 0 auto;">
                                <button type="button" onclick="closeStaffAdminPreviewModal()" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1'">
                                    Close
                                </button>
                                <a href="dashboard.php?action=staff_accept_custom_list&id=${item.id}&tab=staff_panel&subtab_staff=pending&pending_type=custom" style="background: #00c675; color: white; padding: 9px 24px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3);">
                                    <i class="fa-solid fa-check"></i> Accept (OK)
                                </a>
                                <button type="button" onclick="window.print()" style="background: #00c675; border: none; color: #ffffff; padding: 9px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;" onmouseover="this.style.background='#00b368'" onmouseout="this.style.background='#00c675'">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        `;
                    }

                    document.getElementById('staff-modal-content-area').innerHTML = contentHtml + actionBtnHtml;
                    document.getElementById('staff-admin-preview-modal').style.display = 'flex';
                }

                function closeStaffAdminPreviewModal() {
                    document.getElementById('staff-admin-preview-modal').style.display = 'none';
                }
            </script>

        <!-- TAB 6: DELIVERY CHARGE & DISCOUNT MANAGEMENT -->
        <?php elseif ($tab === 'delivery_charge'): ?>
            <?php 
                $dcSubtab = $_GET['dc_subtab'] ?? 'delivery';
                if (!in_array($dcSubtab, ['delivery', 'discount'])) {
                    $dcSubtab = 'delivery';
                }
            ?>
            <!-- Top 2 Main Module Buttons (Large Bolder Style) -->
            <div style="background: white; border-radius: 18px; border: 1.5px solid #e2e8f0; padding: 14px 16px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;" id="dcSubtabNav">
                    <!-- 1. Delivery Charge -->
                    <button type="button" class="dc-subtab-btn <?= $dcSubtab === 'delivery' ? 'active' : '' ?>" id="dc-subtab-btn-delivery" onclick="switchDcSubtab('delivery', this)" style="padding: 16px 24px; border-radius: 12px; font-size: 16px; font-weight: 800; border: 2px solid <?= $dcSubtab === 'delivery' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $dcSubtab === 'delivery' ? '#6366f1' : '#f8fafc' ?>; color: <?= $dcSubtab === 'delivery' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $dcSubtab === 'delivery' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.28);' : '' ?>">
                        <i class="fa-solid fa-truck-fast" style="font-size: 19px; color: <?= $dcSubtab === 'delivery' ? 'white' : '#6366f1' ?>;"></i>
                        <span>Delivery Charge</span>
                        <span class="subtab-badge" style="background: <?= $dcSubtab === 'delivery' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $dcSubtab === 'delivery' ? 'white' : '#475569' ?>; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= count($deliveryRulesList) ?></span>
                    </button>
                    <!-- 2. Discount -->
                    <button type="button" class="dc-subtab-btn <?= $dcSubtab === 'discount' ? 'active' : '' ?>" id="dc-subtab-btn-discount" onclick="switchDcSubtab('discount', this)" style="padding: 16px 24px; border-radius: 12px; font-size: 16px; font-weight: 800; border: 2px solid <?= $dcSubtab === 'discount' ? '#6366f1' : '#e2e8f0' ?>; background: <?= $dcSubtab === 'discount' ? '#6366f1' : '#f8fafc' ?>; color: <?= $dcSubtab === 'discount' ? 'white' : '#334155' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; <?= $dcSubtab === 'discount' ? 'box-shadow: 0 6px 18px rgba(99, 102, 241, 0.28);' : '' ?>">
                        <i class="fa-solid fa-percent" style="font-size: 19px; color: <?= $dcSubtab === 'discount' ? 'white' : '#f59e0b' ?>;"></i>
                        <span>Discount</span>
                        <span class="subtab-badge" style="background: <?= $dcSubtab === 'discount' ? 'rgba(255,255,255,0.25)' : '#e2e8f0' ?>; color: <?= $dcSubtab === 'discount' ? 'white' : '#475569' ?>; padding: 2px 10px; border-radius: 20px; font-size: 13px; font-weight: 900; margin-left: 4px;"><?= count($discountRulesList) ?></span>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="font-size: 26px; font-weight: 900; color: #0f172a; margin: 0 0 4px 0;">DC &amp; Discount Management</h2>
                    <p style="color: #64748b; margin: 0; font-size: 14px;">Welcome back, <strong><?= htmlspecialchars($adminName) ?></strong>! Supervise delivery rules, discounts &amp; promo coupons</p>
                </div>
            </div>

            <!-- PANEL 1: DELIVERY CHARGE RULES -->
            <div id="dc-panel-delivery" class="dc-subtab-panel" style="<?= $dcSubtab === 'delivery' ? 'display: block;' : 'display: none;' ?>">
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-header" style="padding: 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid <?= $delType === 'website' ? 'fa-globe' : ($delType === 'all' ? 'fa-boxes-stacked' : 'fa-list-check') ?>" style="color: #2563eb; font-size: 17px;"></i>
                                Delivery Charge Rules - <?= $delType === 'website' ? 'Order from Website' : ($delType === 'all' ? 'All Orders' : 'Order Custom List') ?> <span class="page-title-badge" style="background: #e0e7ff; color: #3730a3;"><?= count($deliveryRulesList) ?></span>
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                <?= $delType === 'website' ? 'Configure dynamic delivery charges for online website orders.' : ($delType === 'all' ? 'Configure global delivery charges applied across all order channels.' : 'Configure dynamic delivery charges for custom list orders.') ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <a href="dashboard.php?tab=delivery_charge&dc_subtab=delivery&type=custom_list" 
                               style="padding: 10px 18px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; border-radius: 8px; transition: 0.2s; <?= $delType === 'custom_list' ? 'background: #2563eb; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #cbd5e1;' ?>">
                                <i class="fa-solid fa-list-check"></i> Order Custom List
                            </a>
                            <a href="dashboard.php?tab=delivery_charge&dc_subtab=delivery&type=website" 
                               style="padding: 10px 18px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; border-radius: 8px; transition: 0.2s; <?= $delType === 'website' ? 'background: #2563eb; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #cbd5e1;' ?>">
                                <i class="fa-solid fa-globe"></i> Order from Website
                            </a>
                            <a href="dashboard.php?tab=delivery_charge&dc_subtab=delivery&type=all" 
                               style="padding: 10px 18px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; border-radius: 8px; transition: 0.2s; <?= $delType === 'all' ? 'background: #2563eb; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #cbd5e1;' ?>">
                                <i class="fa-solid fa-boxes-stacked"></i> All Orders
                            </a>
                            <button type="button" onclick="openCreateDeliveryRuleModal('<?= $delType === 'all' ? 'all' : $delType ?>')" class="btn-action" style="background: #2563eb; border: none; padding: 11px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); display: inline-flex; align-items: center; gap: 8px; color: white; cursor: pointer;">
                                <i class="fa-solid fa-plus"></i> Add New Rule
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">PRIORITY</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">RULE NAME</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">BASED ON</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">CONDITION / CRITERIA</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">CALCULATION METHOD</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">CHARGE / RATE</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff;">STATUS</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; background: #ffffff; text-align: right;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deliveryRulesList)): ?>
                                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px; font-size: 14px;">No delivery charge rules found for <?= $delType === 'website' ? 'Website' : ($delType === 'all' ? 'All Orders' : 'Custom List') ?>. Click "Add New Rule" above to create one.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($deliveryRulesList as $rule): ?>
                                        <tr style="border-bottom: 1px solid #f8fafc;">
                                            <td style="padding: 18px 20px; font-weight: 800; font-size: 14px; color: #0f172a;">
                                                #<?= $rule['priority'] ?>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <strong style="color: #0f172a; font-size: 15px; font-weight: 800;"><?= htmlspecialchars($rule['rule_name']) ?></strong>
                                                <div style="color: #64748b; font-size: 12px; font-weight: 500; margin-top: 2px;">Scope: <?= $rule['target_type'] === 'website' ? 'Order from Website' : ($rule['target_type'] === 'all' ? 'All Orders' : 'Order Custom List') ?></div>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <span style="background: #ede9fe; color: #6d28d9; padding: 5px 14px; border-radius: 50px; font-weight: 700; font-size: 12.5px; display: inline-block;">
                                                    <?= ucfirst(str_replace('_', ' ', $rule['based_on'])) ?>
                                                </span>
                                            </td>
                                            <td style="padding: 18px 20px; font-size: 14px; color: #334155; font-weight: 500;">
                                                <?php
                                                $basedLabel = match($rule['based_on']) {
                                                    'order_amount' => 'Order Amount',
                                                    'weight' => 'Weight',
                                                    'distance' => 'Distance',
                                                    'zone' => 'Delivery Zone/Area',
                                                    'category' => 'Product Category',
                                                    'amount_weight' => 'Amount + Weight',
                                                    'distance_weight' => 'Distance + Weight',
                                                    'custom' => 'Custom Rule',
                                                    default => ucfirst(str_replace('_', ' ', $rule['based_on']))
                                                };
                                                $unit = match($rule['based_on']) {
                                                    'order_amount' => '৳',
                                                    'weight' => 'KG',
                                                    'distance' => 'KM',
                                                    default => ''
                                                };
                                                if ($rule['operator'] === 'between') {
                                                    if ($rule['based_on'] === 'order_amount') {
                                                        echo "Amount Between ৳" . number_format($rule['min_value'], 2) . " and ৳" . number_format($rule['max_value'], 2);
                                                    } else {
                                                        echo "{$basedLabel} Between " . number_format($rule['min_value'], 2) . " {$unit} and " . number_format($rule['max_value'], 2) . " {$unit}";
                                                    }
                                                } elseif ($rule['operator'] === 'greater_than_or_equal') {
                                                    echo "{$basedLabel} &gt;= " . ($rule['based_on'] === 'order_amount' ? '৳' : '') . number_format($rule['min_value'], 2) . ($rule['based_on'] !== 'order_amount' ? " {$unit}" : '');
                                                } elseif ($rule['operator'] === 'less_than_or_equal') {
                                                    echo "{$basedLabel} &lt;= " . ($rule['based_on'] === 'order_amount' ? '৳' : '') . number_format($rule['min_value'] ?: $rule['max_value'], 2) . ($rule['based_on'] !== 'order_amount' ? " {$unit}" : '');
                                                } elseif ($rule['operator'] === 'greater_than') {
                                                    echo "{$basedLabel} &gt; " . ($rule['based_on'] === 'order_amount' ? '৳' : '') . number_format($rule['min_value'], 2) . ($rule['based_on'] !== 'order_amount' ? " {$unit}" : '');
                                                } elseif ($rule['operator'] === 'less_than') {
                                                    echo "{$basedLabel} &lt; " . ($rule['based_on'] === 'order_amount' ? '৳' : '') . number_format($rule['max_value'] ?: $rule['min_value'], 2) . ($rule['based_on'] !== 'order_amount' ? " {$unit}" : '');
                                                } elseif ($rule['operator'] === 'exact') {
                                                    echo "{$basedLabel} = " . ($rule['based_on'] === 'order_amount' ? '৳' : '') . number_format($rule['min_value'], 2) . ($rule['based_on'] !== 'order_amount' ? " {$unit}" : '');
                                                } else {
                                                    echo "{$basedLabel} " . htmlspecialchars($rule['operator']) . " " . $rule['min_value'];
                                                }
                                                ?>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <span style="background: #dbeafe; color: #2563eb; padding: 5px 14px; border-radius: 50px; font-weight: 700; font-size: 12.5px; display: inline-block;">
                                                    <?php
                                                    echo match($rule['calculation_method']) {
                                                        'free' => 'Free Delivery',
                                                        'fixed' => 'Fixed Amount',
                                                        'percentage' => 'Percentage (%)',
                                                        'per_kg' => 'Per KG Rate',
                                                        'per_km' => 'Per KM Rate',
                                                        'base_per_kg' => 'Base + Per KG',
                                                        'base_per_km' => 'Base + Per KM',
                                                        'additional_charge' => 'Additional Charge',
                                                        default => ucfirst(str_replace('_', ' ', $rule['calculation_method']))
                                                    };
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <strong style="color: #10b981; font-size: 15px; font-weight: 800;">
                                                    <?php
                                                    if ($rule['calculation_method'] === 'free') {
                                                        echo 'FREE';
                                                    } elseif ($rule['calculation_method'] === 'base_per_kg') {
                                                        $base = $rule['base_charge'] ?? $rule['fixed_amount'] ?? 40;
                                                        $rate = $rule['per_unit_rate'] ?? 10;
                                                        echo "৳" . number_format($base, 0) . " + ৳" . number_format($rate, 0) . "/KG";
                                                    } elseif ($rule['calculation_method'] === 'base_per_km') {
                                                        $base = $rule['base_charge'] ?? $rule['fixed_amount'] ?? 40;
                                                        $rate = $rule['per_unit_rate'] ?? 15;
                                                        echo "৳" . number_format($base, 0) . " + ৳" . number_format($rate, 0) . "/KM";
                                                    } elseif ($rule['calculation_method'] === 'per_kg') {
                                                        echo "৳" . number_format($rule['fixed_amount'] ?: $rule['charge'], 2) . "/KG";
                                                    } elseif ($rule['calculation_method'] === 'per_km') {
                                                        echo "৳" . number_format($rule['fixed_amount'] ?: $rule['charge'], 2) . "/KM";
                                                    } elseif ($rule['calculation_method'] === 'percentage') {
                                                        echo number_format($rule['fixed_amount'] ?: $rule['charge'], 1) . "%";
                                                    } else {
                                                        echo "৳" . number_format($rule['fixed_amount'] ?: $rule['charge'], 2);
                                                    }
                                                    ?>
                                                </strong>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <span style="background: <?= $rule['is_active'] ? '#dcfce7' : '#fee2e2' ?>; color: <?= $rule['is_active'] ? '#16a34a' : '#dc2626' ?>; padding: 5px 14px; border-radius: 50px; font-weight: 700; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                                                    <i class="fa-solid <?= $rule['is_active'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>" style="font-size: 12px;"></i>
                                                    <?= $rule['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td style="padding: 18px 20px; text-align: right;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <button type="button" class="btn-rule-action" title="Edit Rule" onclick='editDeliveryRule(<?= json_encode($rule, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i>
                                                    </button>
                                                    <button type="button" class="btn-rule-action" title="Duplicate Rule" onclick="duplicateDeliveryRule(<?= $rule['id'] ?>)">
                                                        <i class="fa-solid fa-copy" style="color: #8b5cf6;"></i>
                                                    </button>
                                                    <button type="button" class="btn-rule-action" title="Toggle Active/Inactive" onclick="toggleDeliveryRule(<?= $rule['id'] ?>)">
                                                        <i class="fa-solid fa-power-off" style="color: <?= $rule['is_active'] ? '#ef4444' : '#10b981' ?>;"></i>
                                                    </button>
                                                    <button type="button" class="btn-rule-action" title="Delete Rule" onclick="deleteDeliveryRule(<?= $rule['id'] ?>)">
                                                        <i class="fa-solid fa-trash-can" style="color: #ef4444;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL 2: DISCOUNTS & OFFERS -->
            <div id="dc-panel-discount" class="dc-subtab-panel" style="<?= $dcSubtab === 'discount' ? 'display: block;' : 'display: none;' ?>">
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-header" style="padding: 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-tags" style="color: #f59e0b; font-size: 18px;"></i>
                                Discounts, BOGO &amp; Coupon Hub <span class="page-title-badge" style="background: #fef3c7; color: #b45309;"><?= count($discountRulesList) ?></span>
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Configure Fixed Discounts, Percentage, BOGO (Buy 1 Get 1), Cart Spend, and Coupon Codes.
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <div style="position: relative; width: 250px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none;"></i>
                                <input type="text" id="discount-search-input" oninput="filterDiscountsTable()" placeholder="Search discount offers..." class="form-control" style="padding-left: 34px; padding-top: 8px; padding-bottom: 8px; border-radius: 8px; font-size: 13.5px; width: 100%; border: 1.5px solid var(--border-color);">
                            </div>
                            <button type="button" onclick="openAddDiscountModal()" class="btn-action" style="background: #f59e0b; border: none; padding: 11px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); display: inline-flex; align-items: center; gap: 8px; color: white; cursor: pointer;">
                                <i class="fa-solid fa-plus"></i> Create Discount Rule
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">OFFER TITLE &amp; BADGE</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">TYPE</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">TARGET SCOPE / COMBO</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">DISCOUNT VALUE / REWARD</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">CONDITIONS &amp; TARGET</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">COUPON CODE</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b;">STATUS</th>
                                    <th style="padding: 16px 20px; font-size: 12px; font-weight: 800; color: #64748b; text-align: right;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($discountRulesList)): ?>
                                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px; font-size: 14px;">No discount rules created yet. Click "Create Discount Rule" above to create one.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($discountRulesList as $dr): ?>
                                        <tr class="discount-table-row" data-search="<?= htmlspecialchars(strtolower($dr['title'] . ' ' . ($dr['title_bn'] ?? '') . ' ' . ($dr['coupon_code'] ?? '') . ' ' . ($dr['target_product_title'] ?? '') . ' ' . ($dr['target_category_name'] ?? '') . ' ' . ($dr['combo_products_text'] ?? ''))) ?>" style="border-bottom: 1px solid #f8fafc;">
                                            <td style="padding: 18px 20px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <strong style="color: #0f172a; font-size: 14.5px;"><?= htmlspecialchars($dr['title']) ?></strong>
                                                    <?php if (!empty($dr['badge_text'])): ?>
                                                        <span style="background: #fef3c7; color: #92400e; font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 4px; border: 1px solid #fde68a;"><?= htmlspecialchars($dr['badge_text']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($dr['title_bn'])): ?>
                                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($dr['title_bn']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <?php if ($dr['discount_type'] === 'combo_bundle'): ?>
                                                    <span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-boxes-packing"></i> Combo Bundle</span>
                                                <?php elseif ($dr['discount_type'] === 'free_gift'): ?>
                                                    <span style="background: #fdf2f8; color: #db2777; border: 1px solid #fbcfe8; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-gift"></i> Free Gift</span>
                                                <?php elseif ($dr['discount_type'] === 'free_delivery'): ?>
                                                    <span style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-truck-fast"></i> Free Delivery</span>
                                                <?php elseif ($dr['discount_type'] === 'bogo'): ?>
                                                    <span style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-gift"></i> BOGO FREE</span>
                                                <?php elseif ($dr['discount_type'] === 'percentage'): ?>
                                                    <span style="background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-percent"></i> Percentage</span>
                                                <?php elseif ($dr['discount_type'] === 'fixed_amount'): ?>
                                                    <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-tag"></i> Fixed Amount</span>
                                                <?php elseif ($dr['discount_type'] === 'cart_spend'): ?>
                                                    <span style="background: #fae8ff; color: #86198f; border: 1px solid #f0abfc; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-basket-shopping"></i> Spend Tier</span>
                                                <?php elseif ($dr['discount_type'] === 'coupon'): ?>
                                                    <span style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><i class="fa-solid fa-ticket"></i> Promo Coupon</span>
                                                <?php else: ?>
                                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800;"><?= htmlspecialchars($dr['discount_type']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 18px 20px; font-size: 13px;">
                                                <?php if ($dr['target_type'] === 'multi_products' || $dr['discount_type'] === 'combo_bundle'): ?>
                                                    <div style="font-weight: 700; color: #1e293b;">
                                                        <i class="fa-solid fa-link" style="color: #f59e0b; font-size: 11px;"></i> <?= htmlspecialchars($dr['combo_products_text'] ?: 'Multiple Required Items') ?>
                                                    </div>
                                                <?php elseif ($dr['target_type'] === 'single_product'): ?>
                                                    <span style="color: #2563eb; font-weight: 700;"><?= htmlspecialchars($dr['target_product_title'] ?: 'Product #' . $dr['target_id']) ?></span>
                                                <?php elseif ($dr['target_type'] === 'category'): ?>
                                                    <span>Category: <strong><?= htmlspecialchars($dr['target_category_name'] ?: 'Category #' . $dr['target_id']) ?></strong></span>
                                                <?php else: ?>
                                                    <span style="color: #64748b; font-weight: 600;">Entire Cart / Order</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 18px 20px; font-weight: 800; color: #047857; font-size: 14px;">
                                                <?php if ($dr['discount_type'] === 'combo_bundle' || $dr['discount_type'] === 'free_gift' || $dr['reward_type'] === 'free_product'): ?>
                                                    <div style="display: flex; align-items: center; gap: 6px; color: #db2777;">
                                                        <i class="fa-solid fa-gift"></i>
                                                        <span>FREE: <?= htmlspecialchars($dr['reward_product_title'] ?: 'Gift Item') ?> (<?= floatval($dr['reward_qty'] ?: 1) ?> pcs)</span>
                                                    </div>
                                                    <?php if (!empty($dr['max_reward_limit']) && intval($dr['max_reward_limit']) > 0): ?>
                                                        <div style="font-size: 11px; color: #b45309; font-weight: 700; margin-top: 2px;">
                                                            <i class="fa-solid fa-hand"></i> অর্ডারে সর্বোচ্চ <?= intval($dr['max_reward_limit']) ?> টি ফ্রি
                                                        </div>
                                                    <?php else: ?>
                                                        <div style="font-size: 11px; color: #059669; font-weight: 700; margin-top: 2px;">
                                                            <i class="fa-solid fa-infinity"></i> প্রতি সেটে ১টি (সমানুপাতিক)
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (floatval($dr['discount_value']) > 0): ?>
                                                        <div style="font-size: 12px; color: #059669; margin-top: 2px;">+ ৳<?= number_format($dr['discount_value'], 2) ?> Off</div>
                                                    <?php endif; ?>
                                                <?php elseif ($dr['discount_type'] === 'free_delivery' || $dr['reward_type'] === 'free_delivery'): ?>
                                                    <span style="color: #0284c7;"><i class="fa-solid fa-truck-fast"></i> 100% Free Delivery</span>
                                                <?php elseif ($dr['discount_type'] === 'bogo'): ?>
                                                    100% Free (<?= htmlspecialchars($dr['reward_product_title'] ?: 'Same Product') ?>)
                                                <?php elseif ($dr['discount_type'] === 'percentage'): ?>
                                                    <?= htmlspecialchars($dr['discount_value']) ?>% Off
                                                <?php else: ?>
                                                    ৳<?= number_format($dr['discount_value'], 2) ?> Off
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 18px 20px; font-size: 12.5px; color: #475569;">
                                                <?php if ($dr['min_cart_amount'] > 0): ?>
                                                    <div>Min Cart: <strong>৳<?= number_format($dr['min_cart_amount'], 2) ?></strong></div>
                                                <?php endif; ?>
                                                <?php if ($dr['min_qty_required'] > 1): ?>
                                                    <div>Min Qty: <strong><?= $dr['min_qty_required'] ?> pcs</strong></div>
                                                <?php endif; ?>
                                                <?php if (empty($dr['min_cart_amount']) && (empty($dr['min_qty_required']) || $dr['min_qty_required'] <= 1)): ?>
                                                    <span style="color: #94a3b8;">No min limit</span>
                                                <?php endif; ?>
                                                
                                                <!-- Target badges -->
                                                <div style="display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap;">
                                                    <?php if (!empty($dr['customer_target']) && $dr['customer_target'] !== 'all'): ?>
                                                        <span style="background: #eff6ff; color: #1d4ed8; font-size: 10.5px; font-weight: 700; padding: 1px 6px; border-radius: 4px;">👤 <?= htmlspecialchars(ucwords(str_replace('_', ' ', $dr['customer_target']))) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($dr['payment_method_target']) && $dr['payment_method_target'] !== 'all'): ?>
                                                        <span style="background: #f0fdf4; color: #15803d; font-size: 10.5px; font-weight: 700; padding: 1px 6px; border-radius: 4px;">💳 <?= htmlspecialchars(ucwords(str_replace('_', ' ', $dr['payment_method_target']))) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <?php if (!empty($dr['coupon_code'])): ?>
                                                    <strong style="background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-family: monospace; letter-spacing: 1px; color: #c2410c; border: 1px dashed #fdba74;">
                                                        <?= htmlspecialchars($dr['coupon_code']) ?>
                                                    </strong>
                                                <?php else: ?>
                                                    <span style="color: #94a3b8; font-size: 12px;">Automatic</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 18px 20px;">
                                                <button type="button" onclick="toggleDiscountStatus(<?= $dr['id'] ?>, <?= $dr['is_active'] ? 0 : 1 ?>)" style="cursor: pointer; border: none; background: <?= $dr['is_active'] ? '#dcfce7' : '#fee2e2' ?>; color: <?= $dr['is_active'] ? '#16a34a' : '#dc2626' ?>; padding: 4px 12px; border-radius: 50px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                    <i class="fa-solid <?= $dr['is_active'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>" style="font-size: 11px;"></i>
                                                    <?= $dr['is_active'] ? 'Active' : 'Disabled' ?>
                                                </button>
                                            </td>
                                            <td style="padding: 18px 20px; text-align: right;">
                                                <div style="display: inline-flex; gap: 6px;">
                                                    <button type="button" class="btn-rule-action" title="Edit Discount" onclick='openEditDiscountModal(<?= json_encode($dr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i>
                                                    </button>
                                                    <button type="button" class="btn-rule-action" title="Delete Discount" onclick="deleteDiscountRule(<?= $dr['id'] ?>, '<?= htmlspecialchars(addslashes($dr['title'])) ?>')">
                                                        <i class="fa-solid fa-trash-can" style="color: #ef4444;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- TAB 7: PORTAL INFO & BROADCAST -->
        <?php elseif ($tab === 'portal_info'): ?>
            <!-- Portal Sub-Tabs Navigation -->
            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; flex-wrap: wrap;">
                <?php if (hasPermission('manage_contacts')): ?>
                <a href="dashboard.php?tab=portal_info&subtab=links_contacts" 
                   style="padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-radius: 10px; transition: 0.2s; <?= $subtab === 'links_contacts' ? 'background: #2563eb; color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #e2e8f0;' ?>">
                    <i class="fa-solid fa-phone"></i> Links & Contacts
                </a>
                <?php endif; ?>
                <?php if (hasPermission('manage_policies')): ?>
                <a href="dashboard.php?tab=portal_info&subtab=legal_policies" 
                   style="padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-radius: 10px; transition: 0.2s; <?= $subtab === 'legal_policies' ? 'background: #2563eb; color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #e2e8f0;' ?>">
                    <i class="fa-solid fa-scale-balanced"></i> Legal & Policies
                </a>
                <?php endif; ?>
                <?php if (hasPermission('manage_happy_customers')): ?>
                <a href="dashboard.php?tab=portal_info&subtab=happy_customers" 
                   style="padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-radius: 10px; transition: 0.2s; <?= $subtab === 'happy_customers' ? 'background: #2563eb; color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #e2e8f0;' ?>">
                    <i class="fa-solid fa-comments"></i> Happy Customers (OHC)
                </a>
                <?php endif; ?>
                <?php if (hasPermission('manage_broadcast')): ?>
                <a href="dashboard.php?tab=portal_info&subtab=broadcast_notices" 
                   style="padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-radius: 10px; transition: 0.2s; <?= $subtab === 'broadcast_notices' ? 'background: #2563eb; color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #e2e8f0;' ?>">
                    <i class="fa-solid fa-bullhorn"></i> Broadcast Notices
                </a>
                <?php endif; ?>
                <?php if (hasPermission('manage_sms_gateway')): ?>
                <a href="dashboard.php?tab=portal_info&subtab=sms_gateway" 
                   style="padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-radius: 10px; transition: 0.2s; <?= $subtab === 'sms_gateway' ? 'background: #2563eb; color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #e2e8f0;' ?>">
                    <i class="fa-solid fa-tower-broadcast"></i> SMS Gateway Config
                </a>
                <?php endif; ?>
                <?php if (hasPermission('manage_payment_methods')): ?>
                <a href="dashboard.php?tab=portal_info&subtab=payment_methods" 
                   style="padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border-radius: 10px; transition: 0.2s; <?= $subtab === 'payment_methods' ? 'background: #2563eb; color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25); border: 1.5px solid #2563eb;' : 'background: white; color: #334155; border: 1.5px solid #e2e8f0;' ?>">
                    <i class="fa-solid fa-wallet"></i> Payment Methods (bKash/Nagad/COD)
                </a>
                <?php endif; ?>
            </div>

            <!-- SUBTAB 1: LINKS & CONTACTS -->
            <?php if ($subtab === 'links_contacts'): ?>
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-header" style="padding: 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-id-card" style="color: #10b981; font-size: 20px;"></i>
                                Store Links, Phone Numbers & Social Contacts
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Manage customer-facing hotline numbers, WhatsApp, Facebook, support hours, office address and taglines.
                            </span>
                        </div>
                    </div>

                    <div style="padding: 28px 30px;">
                        <form onsubmit="savePortalSettings(event)" style="display: flex; flex-direction: column; gap: 28px;">
                            
                            <!-- 1. Phone Numbers & Social Media -->
                            <div>
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <i class="fa-solid fa-phone" style="color: #2563eb;"></i> Phone Numbers & Social Media
                                </h4>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 18px;">
                                    <div class="form-group">
                                        <label>Support Phone / Hotline *</label>
                                        <input type="text" id="set-hotline-phone" class="form-control" value="<?= htmlspecialchars($portalPhone) ?>" placeholder="01919-668960" required>
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px;">Appears in website header, footer hotline, and call buttons.</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Alternative Cell Phone</label>
                                        <input type="text" id="set-alt-phone" class="form-control" value="<?= htmlspecialchars($altPhone) ?>" placeholder="01711-223344">
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px;">Secondary customer support phone number.</span>
                                    </div>
                                    <div class="form-group">
                                        <label>WhatsApp Number</label>
                                        <input type="text" id="set-whatsapp-number" class="form-control" value="<?= htmlspecialchars($whatsappNum) ?>" placeholder="01919668960">
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px;">Customers will be redirected to chat with this WhatsApp number.</span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 18px;">
                                    <div class="form-group" style="max-width: 480px;">
                                        <label>Facebook Page URL</label>
                                        <input type="text" id="set-facebook-url" class="form-control" value="<?= htmlspecialchars($facebookUrl) ?>" placeholder="https://facebook.com/bazardotcom">
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px;">Link attached to footer Facebook button.</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Support Email Address</label>
                                    <input type="email" id="set-support-email" class="form-control" value="<?= htmlspecialchars($portalEmail) ?>" placeholder="support@bazar.com" required>
                                </div>
                            </div>

                            <!-- 2. Support Working Hours -->
                            <div>
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <i class="fa-regular fa-clock" style="color: #2563eb;"></i> Support Working Hours
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                                    <div class="form-group">
                                        <label>Support Hours (English)</label>
                                        <input type="text" id="set-support-hours-en" class="form-control" value="<?= htmlspecialchars($supportHoursEn) ?>" placeholder="24/7 Support Center">
                                    </div>
                                    <div class="form-group">
                                        <label>Support Hours (Bangla)</label>
                                        <input type="text" id="set-support-hours-bn" class="form-control" value="<?= htmlspecialchars($supportHoursBn) ?>" placeholder="২৪/৭ সাপোর্ট সেন্টার">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Estimated Delivery Time Range -->
                            <div>
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <i class="fa-solid fa-truck-fast" style="color: #2563eb;"></i> Estimated Delivery Time (আনুমানিক ডেলিভারি সময়)
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                                    <div class="form-group">
                                        <label>Estimated Time Range (English)</label>
                                        <input type="text" id="set-delivery-time-estimate-en" class="form-control" value="<?= htmlspecialchars($deliveryTimeEstimateEn) ?>" placeholder="20-35 mins">
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px;">Displayed on Live Order Tracker (e.g., 20-35 mins, 30-45 mins).</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Estimated Time Range (Bangla)</label>
                                        <input type="text" id="set-delivery-time-estimate-bn" class="form-control" value="<?= htmlspecialchars($deliveryTimeEstimateBn) ?>" placeholder="২০-৩৫ মিনিট">
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px;">লাইভ অর্ডার ট্র্যাকার স্ক্রিনে আনুমানিক সময় হিসেবে প্রদর্শিত হবে।</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Physical Office Address -->
                            <div>
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <i class="fa-solid fa-location-dot" style="color: #2563eb;"></i> Physical Office Address
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                                    <div class="form-group">
                                        <label>Office Address (English)</label>
                                        <textarea id="set-office-address-en" class="form-control" rows="2"><?= htmlspecialchars($officeAddressEn) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Office Address (Bangla)</label>
                                        <textarea id="set-office-address-bn" class="form-control" rows="2"><?= htmlspecialchars($officeAddressBn) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Website Slogan & Footer Tagline -->
                            <div>
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <i class="fa-solid fa-quote-left" style="color: #2563eb;"></i> Website Slogan & Footer Tagline
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                                    <div class="form-group">
                                        <label>Tagline (English)</label>
                                        <textarea id="set-tagline-en" class="form-control" rows="2"><?= htmlspecialchars($taglineEn) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Tagline (Bangla)</label>
                                        <textarea id="set-tagline-bn" class="form-control" rows="2"><?= htmlspecialchars($taglineBn) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div style="padding-top: 10px;">
                                <button type="submit" class="btn-action" style="background: #2563eb; border: none; padding: 12px 28px; font-weight: 700; font-size: 14.5px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); cursor: pointer;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Portal Information
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <!-- SUBTAB 2: LEGAL & POLICIES -->
            <?php elseif ($subtab === 'legal_policies'): ?>
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-header" style="padding: 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-scale-balanced" style="color: #7c3aed; font-size: 20px;"></i>
                                Store Legal Policies & About Us Content
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Edit company introduction, terms, privacy, and refund policies in both English and Bangla. Instant live update on customer storefront.
                            </span>
                        </div>
                    </div>

                    <div style="padding: 28px 30px;">
                        <form onsubmit="savePortalSettings(event)" style="display: flex; flex-direction: column; gap: 26px;">
                            
                            <!-- 1. About Us (আমাদের সম্পর্কে) -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 22px 24px;">
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15.5px; font-weight: 800; color: #1e293b;">
                                    <i class="fa-solid fa-circle-info" style="color: #2563eb;"></i> 1. About Us (আমাদের সম্পর্কে)
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <?= renderRichEditor('set-about-us-en', 'English Content', $aboutUsEn) ?>
                                    <?= renderRichEditor('set-about-us-bn', 'Bangla Content (বাংলা)', $aboutUsBn) ?>
                                </div>
                            </div>

                            <!-- 2. Privacy Policy (প্রাইভেসি পলিসি) -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 22px 24px;">
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15.5px; font-weight: 800; color: #1e293b;">
                                    <i class="fa-solid fa-user-shield" style="color: #2563eb;"></i> 2. Privacy Policy (প্রাইভেসি পলিসি)
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <?= renderRichEditor('set-privacy-policy-en', 'English Content', $privacyPolicyEn) ?>
                                    <?= renderRichEditor('set-privacy-policy-bn', 'Bangla Content (বাংলা)', $privacyPolicyBn) ?>
                                </div>
                            </div>

                            <!-- 3. Terms & Conditions (শর্তাবলী) -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 22px 24px;">
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15.5px; font-weight: 800; color: #1e293b;">
                                    <i class="fa-solid fa-file-contract" style="color: #2563eb;"></i> 3. Terms & Conditions (শর্তাবলী)
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <?= renderRichEditor('set-terms-conditions-en', 'English Content', $termsConditionsEn) ?>
                                    <?= renderRichEditor('set-terms-conditions-bn', 'Bangla Content (বাংলা)', $termsConditionsBn) ?>
                                </div>
                            </div>

                            <!-- 4. Return & Refund Policy (রিটার্ন ও রিফান্ড নীতি) -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 22px 24px;">
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15.5px; font-weight: 800; color: #1e293b;">
                                    <i class="fa-solid fa-rotate-left" style="color: #2563eb;"></i> 4. Return & Refund Policy (রিটার্ন ও রিফান্ড পলিসি)
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <?= renderRichEditor('set-refund-policy-en', 'English Content', $refundPolicyEn) ?>
                                    <?= renderRichEditor('set-refund-policy-bn', 'Bangla Content (বাংলা)', $refundPolicyBn) ?>
                                </div>
                            </div>

                            <div style="padding-top: 6px;">
                                <button type="submit" class="btn-action" style="background: #2563eb; color: white; border: none; padding: 13px 32px; font-weight: 800; font-size: 15px; border-radius: 9px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Policies & Content
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <!-- SUBTAB 3: HAPPY CUSTOMERS -->
            <?php elseif ($subtab === 'happy_customers'): ?>
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-comments" style="color: #f59e0b; font-size: 20px;"></i>
                                Customer Reviews &amp; Testimonials (OHC) <span class="page-title-badge" style="background: #fef3c7; color: #b45309;"><?= count($ohcList) ?></span>
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Manage testimonials displayed on the homepage. Edit reviews, star ratings, and toggle visibility.
                            </span>
                        </div>
                        <button type="button" class="btn-action" onclick="openAddOhcModal()" style="background: #2563eb; color: white; border: none; padding: 10px 20px; font-weight: 700; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-plus"></i> Add Customer Review
                        </button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 45px;">#</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 180px;">CUSTOMER NAME</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 130px;">RATING</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">COMMENT (ENGLISH)</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">COMMENT (BANGLA)</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 110px; text-align: center;">STATUS</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 120px; text-align: center;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ohcList)): ?>
                                    <tr>
                                        <td colspan="7" style="padding: 40px; text-align: center; color: #64748b; font-size: 14px;">
                                            <i class="fa-solid fa-comments" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                            No customer reviews found. Click <strong>"+ Add Customer Review"</strong> to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $idx = 1; foreach ($ohcList as $r): ?>
                                        <tr style="border-bottom: 1px solid #f8fafc; transition: background 0.15s ease;" onmouseover="this.style.background='#fbfcfd'" onmouseout="this.style.background='white'">
                                            <td style="padding: 16px; font-size: 13.5px; font-weight: 700; color: #64748b; vertical-align: middle;">
                                                <?= $idx++ ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <div style="font-weight: 800; color: #0f172a; font-size: 14px;"><?= htmlspecialchars($r['name']) ?></div>
                                                <?php if (!empty($r['name_bn'])): ?>
                                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($r['name_bn']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <div style="display: flex; align-items: center; gap: 4px;">
                                                    <span style="color: #f59e0b; font-size: 13.5px; letter-spacing: 1px;">
                                                        <?php 
                                                            $rating = intval($r['rating']);
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo ($i <= $rating) ? '★' : '☆';
                                                            }
                                                        ?>
                                                    </span>
                                                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">(<?= $rating ?>★)</span>
                                                </div>
                                            </td>
                                            <td style="padding: 16px; font-size: 13px; color: #334155; line-height: 1.45; vertical-align: middle; max-width: 260px;">
                                                <?= htmlspecialchars($r['comment']) ?>
                                            </td>
                                            <td style="padding: 16px; font-size: 13px; color: #334155; line-height: 1.45; vertical-align: middle; max-width: 260px;">
                                                <?= !empty($r['comment_bn']) ? htmlspecialchars($r['comment_bn']) : '<span style="color:#94a3b8;">-</span>' ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                                <?php if (intval($r['is_active']) === 1): ?>
                                                    <span class="badge" style="background: #ecfdf5; color: #047857; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-circle-check" style="font-size: 11px;"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: #f1f5f9; color: #64748b; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-eye-slash" style="font-size: 11px;"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: center;">
                                                    <button type="button" onclick='openEditOhcModal(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Review" style="background: white; border: 1.5px solid #e0e7ff; color: #6366f1; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='white'">
                                                        <i class="fa-solid fa-pen-to-square" style="font-size: 13px;"></i>
                                                    </button>
                                                    <button type="button" onclick="toggleOhcStatus(<?= $r['id'] ?>)" title="Toggle Active / Inactive" style="background: white; border: 1.5px solid #fee2e2; color: <?= intval($r['is_active']) === 1 ? '#ef4444' : '#10b981' ?>; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background='white'">
                                                        <i class="fa-solid fa-power-off" style="font-size: 13px;"></i>
                                                    </button>
                                                    <button type="button" onclick="deleteOhc(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['name'])) ?>')" title="Delete Review" style="background: white; border: 1.5px solid #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background='white'">
                                                        <i class="fa-solid fa-trash-can" style="font-size: 13px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- SUBTAB 4: BROADCAST NOTICES -->
            <?php elseif ($subtab === 'broadcast_notices'): ?>
                <!-- Compose Broadcast Announcement Card -->
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; padding: 24px 28px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-bullhorn" style="color: #7c3aed; font-size: 20px;"></i>
                                Compose Broadcast Announcement &amp; Notice
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Publish real-time announcements to staff dashboards, customer store banners, and optionally blast SMS alerts.
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <span class="badge" style="background: #ede9fe; color: #6d28d9; padding: 6px 14px; font-size: 13px; font-weight: 700; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-user-group"></i> Staff: <strong><?= intval($staffCount) ?></strong>
                            </span>
                            <span class="badge" style="background: #dcfce7; color: #15803d; padding: 6px 14px; font-size: 13px; font-weight: 700; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-users"></i> Customers: <strong><?= intval($customerCount) ?></strong>
                            </span>
                        </div>
                    </div>

                    <form onsubmit="handleBroadcastSubmit(event)" style="display: flex; flex-direction: column; gap: 18px;">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Notice Title *</label>
                                <input type="text" id="bc-title" class="form-control" required placeholder="Eid Delivery Schedule / Flash Discount" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Target Audience *</label>
                                <select id="bc-target-audience" class="form-control" required onchange="handleBroadcastTargetChange(this)" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;">
                                    <option value="all">All (Staff &amp; Customers)</option>
                                    <option value="staff">Staff Only (Managers &amp; Riders)</option>
                                    <option value="customer">Customers Only (Storefront)</option>
                                    <option value="custom">Custom Phone Numbers</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Notice Type (Banner Style) *</label>
                                <select id="bc-notice-type" class="form-control" required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;">
                                    <option value="info">Info Notice (Blue)</option>
                                    <option value="success">Success Announcement (Green)</option>
                                    <option value="warning">Warning Alert (Yellow/Amber)</option>
                                    <option value="urgent">Urgent / Critical Notice (Red)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="bc-custom-numbers-group" style="display: none;">
                            <label style="font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Specific Phone Numbers (Comma-separated) *</label>
                            <input type="text" id="bc-custom-numbers" class="form-control" placeholder="01712345678, 01987654321, 01800000000" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                            <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">Enter Bangladeshi 11-digit mobile numbers separated by commas.</small>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-size: 13.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Notice Message Body *</label>
                            <textarea id="bc-message" class="form-control" rows="3" required placeholder="Type the full announcement message here..." style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                        </div>

                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; font-weight: 700; color: #1e293b; font-size: 14px;">
                                <input type="checkbox" id="bc-send-sms" style="width: 18px; height: 18px; accent-color: #7c3aed; cursor: pointer;">
                                <i class="fa-solid fa-comment-sms" style="color: #7c3aed; font-size: 16px;"></i>
                                <span>Also dispatch this message via SMS Gateway to target recipients</span>
                            </label>
                            <small style="color: #64748b; font-size: 12.5px;">Standard SMS rates apply via active SMS gateway</small>
                        </div>

                        <div>
                            <button type="submit" class="btn-action" style="background: #7c3aed; color: white; border: none; padding: 12px 26px; font-weight: 800; font-size: 14.5px; border-radius: 9px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-paper-plane"></i> Send / Broadcast Notice
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Broadcast Announcement History Table Card -->
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; padding: 24px 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                        <h3 style="display: flex; align-items: center; gap: 10px; margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                            <i class="fa-solid fa-clock-rotate-left" style="color: #64748b; font-size: 19px;"></i>
                            Broadcast Announcement History <span class="page-title-badge"><?= count($broadcastNotices) ?></span>
                        </h3>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 45px;">#</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">TITLE &amp; MESSAGE</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 150px;">TARGET AUDIENCE</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 140px;">BANNER STYLE</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 120px; text-align: center;">DISPLAY STATUS</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 120px; text-align: center;">SMS DISPATCH</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 100px;">AUTHOR</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 120px;">CREATED AT</th>
                                    <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 90px; text-align: center;">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($broadcastNotices)): ?>
                                    <tr>
                                        <td colspan="9" style="padding: 40px; text-align: center; color: #64748b; font-size: 14px;">
                                            <i class="fa-solid fa-bullhorn" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                            No broadcast announcements published yet. Compose a new notice above.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $idx = 1; foreach ($broadcastNotices as $n): ?>
                                        <tr style="border-bottom: 1px solid #f8fafc; transition: background 0.15s ease;" onmouseover="this.style.background='#fbfcfd'" onmouseout="this.style.background='white'">
                                            <td style="padding: 16px; font-size: 13.5px; font-weight: 700; color: #64748b; vertical-align: middle;">
                                                <?= $idx++ ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <strong style="color: #0f172a; font-size: 14px;"><?= htmlspecialchars($n['title']) ?></strong>
                                                <p style="margin: 4px 0 0 0; color: #475569; font-size: 13px; max-width: 320px; line-height: 1.45;"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                                <?php if ($n['target_audience'] === 'custom' && !empty($n['custom_numbers'])): ?>
                                                    <small style="color: #64748b; font-size: 11px; display: block; margin-top: 4px;">Recipients: <?= htmlspecialchars($n['custom_numbers']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <span class="badge" style="background: #f1f5f9; color: #334155; font-weight: 600; padding: 4px 10px; border-radius: 6px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                    <?php if ($n['target_audience'] === 'all'): ?>
                                                        <i class="fa-solid fa-globe"></i> All Users
                                                    <?php elseif ($n['target_audience'] === 'staff'): ?>
                                                        <i class="fa-solid fa-user-tie"></i> Staff Only
                                                    <?php elseif ($n['target_audience'] === 'customer'): ?>
                                                        <i class="fa-solid fa-users"></i> Customers Only
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-phone"></i> Custom (<?= htmlspecialchars(substr($n['custom_numbers'] ?? '', 0, 15)) ?>...)
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle;">
                                                <?php if ($n['notice_type'] === 'warning'): ?>
                                                    <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;"><i class="fa-solid fa-triangle-exclamation"></i> Warning Alert</span>
                                                <?php elseif ($n['notice_type'] === 'urgent'): ?>
                                                    <span class="badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;"><i class="fa-solid fa-circle-exclamation"></i> Urgent / Critical</span>
                                                <?php elseif ($n['notice_type'] === 'success'): ?>
                                                    <span class="badge" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> Promotion</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;"><i class="fa-solid fa-circle-info"></i> Info Notice</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                                <?php if (intval($n['is_active']) === 1): ?>
                                                    <span class="badge" style="background: #ecfdf5; color: #047857; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-eye" style="font-size: 11px;"></i> Active (Live)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: #f1f5f9; color: #64748b; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-eye-slash" style="font-size: 11px;"></i> Hidden
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                                <?php if (intval($n['send_sms']) === 1): ?>
                                                    <span style="font-size: 12px; font-weight: 700; color: #6d28d9; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-comment-sms"></i> <?= htmlspecialchars($n['sms_status'] ?? 'Sent') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="font-size: 12px; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-laptop"></i> In-App Only
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px; font-size: 13px; color: #475569; vertical-align: middle;">
                                                <?= htmlspecialchars($n['created_by'] ?? 'Author') ?>
                                            </td>
                                            <td style="padding: 16px; font-size: 12px; color: #64748b; vertical-align: middle; line-height: 1.4;">
                                                <?= date("M d, Y", strtotime($n['created_at'])) ?><br>
                                                <small style="color: #94a3b8;"><?= date("h:i A", strtotime($n['created_at'])) ?></small>
                                            </td>
                                            <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: center;">
                                                    <button type="button" onclick="toggleBroadcastNotice(<?= $n['id'] ?>)" title="Toggle Visible / Hidden" style="background: white; border: 1.5px solid #fee2e2; color: <?= intval($n['is_active']) === 1 ? '#ef4444' : '#10b981' ?>; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background='white'">
                                                        <i class="fa-solid fa-power-off" style="font-size: 13px;"></i>
                                                    </button>
                                                    <button type="button" onclick="deleteBroadcastNotice(<?= $n['id'] ?>, '<?= htmlspecialchars(addslashes($n['title'])) ?>')" title="Delete Notice" style="background: white; border: 1.5px solid #fee2e2; color: #ef4444; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background='white'">
                                                        <i class="fa-solid fa-trash-can" style="font-size: 13px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- SUBTAB 5: SMS GATEWAY CONFIGURATION -->
            <?php elseif ($subtab === 'sms_gateway'): ?>
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-header" style="padding: 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-tower-broadcast" style="color: #2563eb; font-size: 20px;"></i>
                                Dynamic SMS Gateway API Settings
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Configure Bangladeshi SMS Gateways (Greenweb BD, BulkSMSBD, SSL Wireless, Custom Webhook) dynamically without changing PHP source code.
                            </span>
                        </div>
                        <div>
                            <span class="badge" style="background: <?= $smsGatewayEnabled ? '#ecfdf5' : '#f1f5f9' ?>; color: <?= $smsGatewayEnabled ? '#059669' : '#64748b' ?>; font-weight: 800; padding: 6px 14px; border-radius: 20px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid <?= $smsGatewayEnabled ? '#a7f3d0' : '#e2e8f0' ?>;">
                                <i class="fa-solid <?= $smsGatewayEnabled ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                                Gateway Status: <?= $smsGatewayEnabled ? 'Active (Live)' : 'Simulation Mode / Inactive' ?>
                            </span>
                        </div>
                    </div>

                    <div style="padding: 28px 30px;">
                        <!-- Settings Form -->
                        <form onsubmit="savePortalSettings(event)" style="display: flex; flex-direction: column; gap: 26px;">
                            
                            <!-- Master Toggle -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                                <div>
                                    <strong style="font-size: 15px; color: #0f172a; display: block; margin-bottom: 2px;">Enable Real SMS Gateway Delivery</strong>
                                    <span style="font-size: 13px; color: #64748b;">Turn ON when you have SMS credits. When OFF, OTP and notices run safely in simulation mode (no failed API calls).</span>
                                </div>
                                <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; background: white; padding: 8px 16px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                                    <input type="checkbox" id="set-sms-gateway-enabled" <?= $smsGatewayEnabled ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: #2563eb; cursor: pointer;">
                                    <span style="font-weight: 700; font-size: 14px; color: #0f172a;">Live Gateway Active</span>
                                </label>
                            </div>

                            <!-- Gateway Credentials -->
                            <div style="border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 16px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <i class="fa-solid fa-key" style="color: #7c3aed;"></i> SMS Provider Credentials
                                </h4>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 18px;">
                                    <div class="form-group">
                                        <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13.5px;">SMS Gateway Provider *</label>
                                        <select id="set-sms-gateway-provider" class="form-control" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-weight: 600; font-size: 14px;" onchange="handleProviderChange(this.value)">
                                            <option value="httpsms" <?= $smsGatewayProvider === 'httpsms' ? 'selected' : '' ?>>httpSMS (Android Phone Gateway - Free via your SIM)</option>
                                            <option value="greenweb" <?= $smsGatewayProvider === 'greenweb' ? 'selected' : '' ?>>Greenweb BD (api.greenweb.com.bd)</option>
                                            <option value="bulksmsbd" <?= $smsGatewayProvider === 'bulksmsbd' ? 'selected' : '' ?>>BulkSMSBD.net / Onnorokom</option>
                                            <option value="sslwireless" <?= $smsGatewayProvider === 'sslwireless' ? 'selected' : '' ?>>SSL Wireless (Push SMS API)</option>
                                            <option value="custom" <?= $smsGatewayProvider === 'custom' ? 'selected' : '' ?>>Custom Gateway (Generic HTTP GET/POST)</option>
                                        </select>
                                        <span style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;" id="sms-provider-desc">Select the SMS gateway company or use your Android phone as a free gateway.</span>
                                    </div>

                                    <div class="form-group">
                                        <label id="lbl-sms-api-key" style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13.5px;">API Key / Auth Token *</label>
                                        <input type="text" id="set-sms-gateway-api-key" class="form-control" value="<?= htmlspecialchars($smsGatewayApiKey) ?>" placeholder="Paste your API token here" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: monospace; font-size: 13.5px;">
                                        <span id="hint-sms-api-key" style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Secret API Token from your SMS provider's portal dashboard.</span>
                                    </div>

                                    <div class="form-group">
                                        <label id="lbl-sms-sender-id" style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13.5px;">Sender ID / Masking (Optional)</label>
                                        <input type="text" id="set-sms-gateway-sender-id" class="form-control" value="<?= htmlspecialchars($smsGatewaySenderId) ?>" placeholder="e.g. BazarKinun.com or 8809612..." style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                                        <span id="hint-sms-sender-id" style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Approved sender Masking Name or Non-Masking numeric ID.</span>
                                    </div>
                                </div>

                                <div class="form-group" id="custom-url-container" style="display: <?= $smsGatewayProvider === 'custom' ? 'block' : 'none' ?>; margin-bottom: 18px;">
                                    <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13.5px;">Custom Webhook / API Endpoint URL</label>
                                    <input type="url" id="set-sms-gateway-custom-url" class="form-control" value="<?= htmlspecialchars($smsGatewayCustomUrl) ?>" placeholder="https://api.myprovider.com/sms/send?token={token}&to={phone}&message={message}" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: monospace; font-size: 13px;">
                                    <span style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Supports place-holders: <code>{token}</code>, <code>{phone}</code>, <code>{message}</code>, <code>{sender_id}</code></span>
                                </div>
                            </div>

                            <!-- Guide / Notice Card -->
                            <div id="sms-guide-box" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; font-size: 13px; color: #1e40af; line-height: 1.5;">
                                <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-circle-info"></i> <span id="sms-guide-title">SMS Gateway Integration:</span>
                                </div>
                                <div id="sms-guide-content">
                                    1. <strong>httpSMS (Android Phone):</strong> Install the httpSMS app on your phone, copy your API Key from httpsms.com, and enter your SIM number as Sender ID.<br>
                                    2. <strong>Commercial Gateways:</strong> Whenever you buy SMS credits from Greenweb BD, BulkSMSBD, or SSL Wireless, just paste the <strong>API Key</strong> here.<br>
                                    3. Instant Customer OTP, Order Confirmation, and Broadcast SMS will automatically send via your active gateway.
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div style="display: flex; gap: 14px; align-items: center;">
                                <button type="submit" class="btn" style="background: #2563eb; color: white; padding: 12px 28px; font-weight: 700; border-radius: 10px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Gateway Settings
                                </button>
                            </div>
                        </form>

                        <!-- Live SMS Gateway Tester -->
                        <div style="margin-top: 36px; border-top: 2px dashed #e2e8f0; padding-top: 28px;">
                            <h4 style="display: flex; align-items: center; gap: 8px; margin: 0 0 8px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-paper-plane" style="color: #059669;"></i> Live SMS Gateway Connection Test
                            </h4>
                            <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">
                                Send a test SMS to your phone number to verify that your API Key and Sender ID are functioning properly.
                            </p>

                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; max-width: 600px;">
                                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                                    <input type="text" id="test-sms-phone" placeholder="Enter recipient phone (e.g. 01700000000)" value="<?= htmlspecialchars($currentUserPhone) ?>" style="flex: 1; min-width: 220px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                                    <button type="button" id="btn-test-sms" onclick="testSmsGateway()" style="background: #059669; color: white; padding: 10px 20px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.2);">
                                        <i class="fa-solid fa-paper-plane"></i> Send Test SMS
                                    </button>
                                </div>
                                <div id="test-sms-result" style="display: none; padding: 12px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 600; line-height: 1.4;"></div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php elseif ($subtab === 'payment_methods'): ?>
                <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                    <div class="panel-header" style="padding: 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">
                                <i class="fa-solid fa-wallet" style="color: #2563eb; font-size: 20px;"></i>
                                Payment Methods Configuration (COD, bKash, Nagad)
                            </h3>
                            <span style="font-size: 13.5px; color: #64748b;">
                                Configure which payment options customers can choose during checkout (Cash on Delivery, bKash Send Money, Nagad Send Money).
                            </span>
                        </div>
                    </div>

                    <div style="padding: 28px 30px;">
                        <form onsubmit="savePortalSettings(event)" style="display: flex; flex-direction: column; gap: 24px;">
                            
                            <!-- 1. Cash on Delivery Card -->
                            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 20px 22px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="font-size: 24px;">💵</div>
                                        <div>
                                            <strong style="font-size: 15px; color: #0f172a; display: block;">Cash on Delivery (COD)</strong>
                                            <span style="font-size: 12.5px; color: #64748b;">Customers pay cash to the rider upon receiving products.</span>
                                        </div>
                                    </div>
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; background: white; padding: 8px 16px; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                                        <input type="checkbox" id="set-payment-cod-enabled" <?= $payCodEnabled ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #10b981; cursor: pointer;">
                                        <span style="font-weight: 700; font-size: 13.5px; color: #0f172a;">Enabled</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 2. bKash Configuration Card -->
                            <div style="background: #fdf2f8; border: 1.5px solid #fbcfe8; border-radius: 12px; padding: 20px 22px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="background: #e2136e; color: white; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        </div>
                                        <div>
                                            <strong style="font-size: 15px; color: #831843; display: block;">bKash Manual Payment (Send Money)</strong>
                                            <span style="font-size: 12.5px; color: #9d174d;">Customer sends money to your bKash number and provides their TrxID.</span>
                                        </div>
                                    </div>
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; background: white; padding: 8px 16px; border-radius: 8px; border: 1.5px solid #f472b6;">
                                        <input type="checkbox" id="set-payment-bkash-enabled" <?= $payBkashEnabled ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #e2136e; cursor: pointer;">
                                        <span style="font-weight: 700; font-size: 13.5px; color: #831843;">Enabled</span>
                                    </label>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; background: white; border-radius: 8px; padding: 16px; border: 1px solid #fce7f3;">
                                    <div class="form-group">
                                        <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13px;">bKash Number (Recipient) *</label>
                                        <input type="text" id="set-payment-bkash-number" class="form-control" value="<?= htmlspecialchars($payBkashNumber) ?>" placeholder="01XXXXXXXXX" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: monospace; font-size: 14px; font-weight: 700;">
                                        <span style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: block;">The number displayed to customers for Send Money.</span>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13px;">Account Type</label>
                                        <select id="set-payment-bkash-type" class="form-control" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-weight: 600; font-size: 13.5px;">
                                            <option value="Personal (Send Money)" <?= $payBkashType === 'Personal (Send Money)' ? 'selected' : '' ?>>Personal (Send Money)</option>
                                            <option value="Merchant (Payment)" <?= $payBkashType === 'Merchant (Payment)' ? 'selected' : '' ?>>Merchant (Payment)</option>
                                            <option value="Agent (Cash Out)" <?= $payBkashType === 'Agent (Cash Out)' ? 'selected' : '' ?>>Agent (Cash Out)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Nagad Configuration Card -->
                            <div style="background: #fff7ed; border: 1.5px solid #ffedd5; border-radius: 12px; padding: 20px 22px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="background: #ea580c; color: white; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        </div>
                                        <div>
                                            <strong style="font-size: 15px; color: #9a3412; display: block;">Nagad Manual Payment (Send Money)</strong>
                                            <span style="font-size: 12.5px; color: #c2410c;">Customer sends money to your Nagad number and provides their TrxID.</span>
                                        </div>
                                    </div>
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; background: white; padding: 8px 16px; border-radius: 8px; border: 1.5px solid #fb923c;">
                                        <input type="checkbox" id="set-payment-nagad-enabled" <?= $payNagadEnabled ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #ea580c; cursor: pointer;">
                                        <span style="font-weight: 700; font-size: 13.5px; color: #9a3412;">Enabled</span>
                                    </label>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; background: white; border-radius: 8px; padding: 16px; border: 1px solid #fed7aa;">
                                    <div class="form-group">
                                        <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13px;">Nagad Number (Recipient) *</label>
                                        <input type="text" id="set-payment-nagad-number" class="form-control" value="<?= htmlspecialchars($payNagadNumber) ?>" placeholder="01XXXXXXXXX" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: monospace; font-size: 14px; font-weight: 700;">
                                        <span style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: block;">The number displayed to customers for Send Money.</span>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-weight: 700; color: #334155; margin-bottom: 6px; display: block; font-size: 13px;">Account Type</label>
                                        <select id="set-payment-nagad-type" class="form-control" style="width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-weight: 600; font-size: 13.5px;">
                                            <option value="Personal (Send Money)" <?= $payNagadType === 'Personal (Send Money)' ? 'selected' : '' ?>>Personal (Send Money)</option>
                                            <option value="Merchant (Payment)" <?= $payNagadType === 'Merchant (Payment)' ? 'selected' : '' ?>>Merchant (Payment)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div style="display: flex; gap: 14px; align-items: center; margin-top: 8px;">
                                <button type="submit" class="btn" style="background: #2563eb; color: white; padding: 12px 28px; font-weight: 700; border-radius: 10px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Payment Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        <!-- TAB 8: AUDIT LOGS -->
        <?php elseif ($tab === 'audit_log'): ?>
            <div class="panel-box" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; padding: 24px 28px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; flex-wrap: wrap; gap: 14px;">
                    <div>
                        <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 4px 0; font-size: 19px; font-weight: 800; color: #0f172a;">
                            <i class="fa-solid fa-shield-halved" style="color: #7c3aed; font-size: 20px;"></i>
                            System Audit Logs &amp; Security Trail <span class="page-title-badge"><?= $totalLogs ?></span>
                        </h3>
                        <span style="font-size: 13.5px; color: #64748b;">
                            Immutable real-time audit log of administrative operations, catalog changes, and logins.
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge" style="background: #ede9fe; color: #7c3aed; padding: 6px 14px; font-size: 13px; font-weight: 700; border-radius: 20px;">
                            <i class="fa-solid fa-calendar-day"></i> Today: <strong><?= $todayLogsCount ?></strong>
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 60px;">#ID</th>
                                <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">USER &amp; ROLE</th>
                                <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">ACTION PERFORMED</th>
                                <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">EVENT DETAILS</th>
                                <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 140px;">IP ADDRESS</th>
                                <th style="padding: 14px 16px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; width: 170px;">TIMESTAMP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($auditLogs)): ?>
                                <tr>
                                    <td colspan="6" style="padding: 40px; text-align: center; color: #64748b; font-size: 14px;">
                                        <i class="fa-solid fa-shield-halved" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                        No audit log entries found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $al): ?>
                                    <tr style="border-bottom: 1px solid #f8fafc;">
                                        <td style="padding: 14px 16px; font-weight: 700; color: #64748b; font-size: 12.5px;">#<?= $al['id'] ?></td>
                                        <td style="padding: 14px 16px;">
                                            <strong style="color: #0f172a; font-size: 13.5px;"><?= htmlspecialchars($al['username'] ?? 'System') ?></strong>
                                            <span style="display: block; font-size: 11px; color: #7c3aed; font-weight: 700; text-transform: uppercase;"><?= htmlspecialchars($al['user_role'] ?? 'admin') ?></span>
                                        </td>
                                        <td style="padding: 14px 16px;">
                                            <span style="background: #f1f5f9; color: #334155; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                                <?= htmlspecialchars($al['action']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 16px; font-size: 13px; color: #475569; line-height: 1.45;">
                                            <?= htmlspecialchars($al['details'] ?? '') ?>
                                        </td>
                                        <td style="padding: 14px 16px; font-size: 12px; color: #64748b; font-family: monospace;">
                                            <?= htmlspecialchars($al['ip_address'] ?? '127.0.0.1') ?>
                                        </td>
                                        <td style="padding: 14px 16px; font-size: 12px; color: #64748b; white-space: nowrap;">
                                            <?= date('M d, Y', strtotime($al['created_at'])) ?><br>
                                            <small style="color: #94a3b8;"><?= date('h:i:s A', strtotime($al['created_at'])) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT MANAGEMENT ACCOUNT -->
    <div id="management-modal" class="modal-overlay" style="align-items: flex-start; padding: 35px 15px; overflow-y: auto;">
        <div class="modal-box" style="max-width: 480px; width: 100%; border-radius: 16px; margin: auto; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); background: #ffffff;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-users-gear" style="color: #2563eb; font-size: 20px;"></i>
                    <h3 id="mng-modal-title" style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;">Add New Management</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('management-modal')" style="font-size: 22px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1; padding: 2px;">&times;</button>
            </div>
            
            <form id="management-form" onsubmit="handleManagementSubmit(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                <input type="hidden" id="mng-id" value="">

                <!-- 1. Full Name -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Management Full Name *</label>
                    <input type="text" id="mng-name" class="form-control" placeholder="Md. Kabir Hossain" required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 2. Phone / Login ID -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Mobile Number / Login Phone *</label>
                    <input type="text" id="mng-phone" class="form-control" placeholder="018XXXXXXXX" required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 3. Email Address -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Email Address</label>
                    <input type="email" id="mng-email" class="form-control" placeholder="management@bazarkinun.com" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 4. Designation / Branch -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Department / Location</label>
                    <input type="text" id="mng-location" class="form-control" placeholder="Corporate Head Office, Dhaka" value="Corporate Head Office, Dhaka" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 5. Role & Status Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Role / Access Level</label>
                        <select id="mng-role" class="form-control" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;">
                            <option value="management" selected>Management</option>
                            <option value="admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Account Status</label>
                        <select id="mng-status" class="form-control" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- 6. Password -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Password</label>
                    <input type="password" id="mng-password" class="form-control" placeholder="Default: 123456 (or leave blank for unchanged)" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <div style="padding-top: 6px;">
                    <button type="submit" class="btn-action" style="width: 100%; justify-content: center; background: #2563eb; color: white; border: none; padding: 13px; font-weight: 700; font-size: 14.5px; border-radius: 8px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28); cursor: pointer;">
                        <span id="mng-btn-text">Create Management Account</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT DELIVERY STAFF (RIDER) -->
    <div id="delivery-staff-modal" class="modal-overlay" style="align-items: flex-start; padding: 40px 15px; overflow-y: auto;">
        <div class="modal-box" style="max-width: 460px; width: 100%; border-radius: 16px; margin: auto; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); background: #ffffff;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-person-biking" style="color: #2563eb; font-size: 22px;"></i>
                    <h3 id="delivery-staff-modal-title" style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;">Add New Delivery Staff</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('delivery-staff-modal')" style="font-size: 22px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1; padding: 2px;">&times;</button>
            </div>
            
            <form id="delivery-staff-form" onsubmit="handleDeliveryStaffSubmit(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 18px;">
                <input type="hidden" id="del-staff-id" value="">
                <input type="hidden" id="del-staff-role" value="rider">

                <!-- 1. Full Name -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Full Name *</label>
                    <input type="text" id="del-staff-fullname" class="form-control" placeholder="Md. Rayhan Ahmed" required style="padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 2. Username -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Username *</label>
                    <input type="text" id="del-staff-username" class="form-control" placeholder="delivery_dhanmondi" required style="padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 3. Password -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Password *</label>
                    <input type="password" id="del-staff-password" class="form-control" placeholder="••••••••" style="padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 4. Phone Number -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Phone Number</label>
                    <input type="text" id="del-staff-phone" class="form-control" placeholder="017XXXXXXXX" style="padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <div style="padding-top: 6px;">
                    <button type="submit" class="btn-action" style="width: 100%; justify-content: center; background: #2563eb; color: white; border: none; padding: 13px; font-weight: 700; font-size: 14.5px; border-radius: 8px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28); cursor: pointer;">
                        <span id="del-staff-btn-text">Create Staff Account</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ASSIGN STAFF LOCATIONS -->
    <div id="assign-location-modal" class="modal-overlay" style="align-items: flex-start; padding: 40px 15px; overflow-y: auto;">
        <div class="modal-box" style="max-width: 480px; width: 100%; border-radius: 16px; margin: auto; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); background: #ffffff;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-map-pin" style="color: #2563eb; font-size: 20px;"></i>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Assign Locations to <span id="assign-loc-username" style="color: #2563eb;"></span></h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('assign-location-modal')" style="font-size: 22px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1; padding: 2px;">&times;</button>
            </div>
            
            <div style="padding: 24px;">
                <!-- Existing Assigned Locations List -->
                <div style="margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 700; color: #374151;">Currently Assigned Locations:</h4>
                    <div id="current-locations-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 150px; overflow-y: auto;">
                        <!-- Injected via JS -->
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 18px 0;">

                <!-- Add New Location Form -->
                <form id="assign-loc-form" onsubmit="submitAddStaffLocation(event)" style="display: flex; flex-direction: column; gap: 14px;">
                    <input type="hidden" id="assign-loc-staff-id" value="">
                    
                    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-weight: 700; font-size: 13px; color: #334155;">Country</label>
                        <input type="text" class="form-control" value="Bangladesh" readonly style="background: #f8fafc; color: #64748b; font-weight: 700; padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13.5px;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-weight: 700; font-size: 13px; color: #334155;">District (64 Districts) *</label>
                            <select id="assign-loc-district" class="form-control" onchange="updateAssignLocationThanas()" required style="padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: white;">
                                <option value="Bagerhat">Bagerhat</option>
                                <option value="Bandarban">Bandarban</option>
                                <option value="Barguna">Barguna</option>
                                <option value="Barishal">Barishal</option>
                                <option value="Bhola">Bhola</option>
                                <option value="Bogura">Bogura</option>
                                <option value="Brahmanbaria">Brahmanbaria</option>
                                <option value="Chandpur">Chandpur</option>
                                <option value="Chapainawabganj">Chapainawabganj</option>
                                <option value="Chattogram">Chattogram</option>
                                <option value="Chuadanga">Chuadanga</option>
                                <option value="Cox's Bazar">Cox's Bazar</option>
                                <option value="Cumilla">Cumilla</option>
                                <option value="Dhaka" selected>Dhaka</option>
                                <option value="Dinajpur">Dinajpur</option>
                                <option value="Faridpur">Faridpur</option>
                                <option value="Feni">Feni</option>
                                <option value="Gaibandha">Gaibandha</option>
                                <option value="Gazipur">Gazipur</option>
                                <option value="Gopalganj">Gopalganj</option>
                                <option value="Habiganj">Habiganj</option>
                                <option value="Jamalpur">Jamalpur</option>
                                <option value="Jashore">Jashore</option>
                                <option value="Jhalokathi">Jhalokathi</option>
                                <option value="Jhenaidah">Jhenaidah</option>
                                <option value="Joypurhat">Joypurhat</option>
                                <option value="Khagrachhari">Khagrachhari</option>
                                <option value="Khulna">Khulna</option>
                                <option value="Kishoreganj">Kishoreganj</option>
                                <option value="Kurigram">Kurigram</option>
                                <option value="Kushtia">Kushtia</option>
                                <option value="Lakshmipur">Lakshmipur</option>
                                <option value="Lalmonirhat">Lalmonirhat</option>
                                <option value="Madaripur">Madaripur</option>
                                <option value="Magura">Magura</option>
                                <option value="Manikganj">Manikganj</option>
                                <option value="Meherpur">Meherpur</option>
                                <option value="Moulvibazar">Moulvibazar</option>
                                <option value="Munshiganj">Munshiganj</option>
                                <option value="Mymensingh">Mymensingh</option>
                                <option value="Naogaon">Naogaon</option>
                                <option value="Narail">Narail</option>
                                <option value="Narayanganj">Narayanganj</option>
                                <option value="Narsingdi">Narsingdi</option>
                                <option value="Natore">Natore</option>
                                <option value="Netrokona">Netrokona</option>
                                <option value="Nilphamari">Nilphamari</option>
                                <option value="Noakhali">Noakhali</option>
                                <option value="Pabna">Pabna</option>
                                <option value="Panchagarh">Panchagarh</option>
                                <option value="Patuakhali">Patuakhali</option>
                                <option value="Pirojpur">Pirojpur</option>
                                <option value="Rajbari">Rajbari</option>
                                <option value="Rajshahi">Rajshahi</option>
                                <option value="Rangamati">Rangamati</option>
                                <option value="Rangpur">Rangpur</option>
                                <option value="Satkhira">Satkhira</option>
                                <option value="Shariatpur">Shariatpur</option>
                                <option value="Sherpur">Sherpur</option>
                                <option value="Sirajganj">Sirajganj</option>
                                <option value="Sunamganj">Sunamganj</option>
                                <option value="Sylhet">Sylhet</option>
                                <option value="Tangail">Tangail</option>
                                <option value="Thakurgaon">Thakurgaon</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-weight: 700; font-size: 13px; color: #334155;">Police Station / Thana *</label>
                            <select id="assign-loc-station" class="form-control" required style="padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; background: white;">
                                <option value="">-- Select Thana / Police Station --</option>
                            </select>
                        </div>
                    </div>

                    <div style="padding-top: 4px;">
                        <button type="submit" class="btn-action" style="width: 100%; justify-content: center; background: #2563eb; color: white; border: none; padding: 11px; font-weight: 700; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-plus"></i> Add Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT AUTHOR / MANAGER / STAFF -->
    <div id="staff-modal" class="modal-overlay" style="align-items: flex-start; padding: 30px 15px; overflow-y: auto;">
        <div class="modal-box" style="max-width: 520px; width: 100%; border-radius: 16px; margin: auto; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); background: #ffffff;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-user-plus" style="color: #2563eb; font-size: 20px;"></i>
                    <h3 id="staff-modal-title" style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;">Add New Author</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('staff-modal')" style="font-size: 24px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1; padding: 4px;">&times;</button>
            </div>
            
            <form id="staff-form" onsubmit="handleStaffSubmit(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                <input type="hidden" id="staff-id" value="">
                <input type="hidden" id="staff-role" value="manager">
                <input type="hidden" id="staff-status" value="active">

                <!-- 1. Username -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Username *</label>
                    <input type="text" id="staff-username" class="form-control" placeholder="author_dhaka" required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 2. Password -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Password *</label>
                    <input type="password" id="staff-password" class="form-control" placeholder="••••••••" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 3. Full Name -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Full Name</label>
                    <input type="text" id="staff-name" class="form-control" placeholder="Rayhan Ahmed" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 4. Phone Number -->
                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Phone Number</label>
                    <input type="text" id="staff-phone" class="form-control" placeholder="017XXXXXXXX" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                </div>

                <!-- 5. Core Module Permissions -->
                <div style="margin-top: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 6px; font-weight: 800; font-size: 13.5px; color: #1e293b;">
                            <i class="fa-solid fa-shield-halved" style="color: #2563eb;"></i>
                            <span>Core Module Permissions</span>
                        </div>
                        <span style="font-size: 12px; color: #64748b;">(Default: None)</span>
                    </div>

                    <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px;">
                        <!-- Column 1 -->
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_staff" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Staff & Delivery</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_categories" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Categories</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_orders" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Orders</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_delivery_charges" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Delivery Charges</span>
                            </label>
                        </div>

                        <!-- Column 2 -->
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_customers" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Customers</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_products" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Products</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #334155; cursor: pointer;">
                                <input type="checkbox" id="perm_invoices" style="width: 17px; height: 17px; accent-color: #2563eb; cursor: pointer;">
                                <span>Invoices</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 6. Portal Info Access Container -->
                <div style="background: #f8faff; border: 1.5px solid #c7d2fe; border-radius: 12px; padding: 16px 18px; margin-top: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 6px; font-weight: 800; font-size: 13.5px; color: #312e81;">
                            <i class="fa-solid fa-sliders" style="color: #6366f1;"></i>
                            <span>Portal Info Access</span>
                        </div>
                        <select id="perm_portal_mode" onchange="handlePortalPermModeChange()" style="padding: 5px 12px; border-radius: 8px; border: 1.5px solid #c7d2fe; font-size: 12.5px; font-weight: 700; color: #334155; background: white; cursor: pointer; outline: none;">
                            <option value="custom">Custom Access...</option>
                            <option value="full">Full Access</option>
                            <option value="none">No Access</option>
                        </select>
                    </div>

                    <div style="border-top: 1px dashed #c7d2fe; margin: 12px 0 14px 0;"></div>

                    <div id="portal_sub_grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; background: white; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" id="perm_portal_links" onchange="onPortalSubCheckChange()" style="width: 16px; height: 16px; accent-color: #6366f1; cursor: pointer;">
                            <span><i class="fa-solid fa-id-badge" style="color: #6366f1; margin-right: 2px;"></i> Links & Contact</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; background: white; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" id="perm_portal_policies" onchange="onPortalSubCheckChange()" style="width: 16px; height: 16px; accent-color: #8b5cf6; cursor: pointer;">
                            <span><i class="fa-solid fa-file-contract" style="color: #8b5cf6; margin-right: 2px;"></i> Legal Policies</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; background: white; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" id="perm_portal_ohc" onchange="onPortalSubCheckChange()" style="width: 16px; height: 16px; accent-color: #f59e0b; cursor: pointer;">
                            <span><i class="fa-solid fa-star" style="color: #f59e0b; margin-right: 2px;"></i> Customer Reviews</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 8px; padding: 10px 12px; font-size: 13px; font-weight: 600; color: #be123c; cursor: pointer;">
                            <input type="checkbox" id="perm_portal_broadcast" onchange="onPortalSubCheckChange()" style="width: 16px; height: 16px; accent-color: #e11d48; cursor: pointer;">
                            <span><i class="fa-solid fa-tower-broadcast" style="color: #e11d48; margin-right: 2px;"></i> Broadcast (SMS)</span>
                        </label>
                    </div>
                </div>

                <!-- 7. Submit Button -->
                <button type="submit" id="staff-submit-btn" style="width: 100%; background: #2563eb; color: white; border: none; padding: 13px 20px; font-weight: 800; font-size: 15px; border-radius: 10px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); cursor: pointer; transition: all 0.2s; margin-top: 4px;">
                    Create Author Account
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT CATEGORY -->
    <div id="category-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="cat-modal-title">Add Category</h3>
                <button type="button" class="modal-close" onclick="closeModal('category-modal')">&times;</button>
            </div>
            <form id="cat-form" onsubmit="handleCategorySubmit(event)">
                <input type="hidden" id="cat-id" value="">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Category Name (English) *</label>
                            <input type="text" id="cat-name" class="form-control" placeholder="Bakery & Snacks" required onkeyup="autoSlug(this.value)">
                        </div>
                        <div class="form-group">
                            <label>বাংলা নাম (Bengali) *</label>
                            <input type="text" id="cat-name-bn" class="form-control" placeholder="যেমন: বেকারি ও স্ন্যাক্স" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>URL Slug *</label>
                        <input type="text" id="cat-slug" class="form-control" placeholder="bakery-snacks" required>
                    </div>
                    <div class="form-group">
                        <label>Image (URL or File Upload)</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="cat-img" class="form-control" placeholder="https://images.unsplash.com/..." value="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300" style="flex: 1;">
                            <label class="btn-action" style="padding: 9px 14px; cursor: pointer; font-size: 13px; border-radius: 8px; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-upload"></i> Upload
                                <input type="file" accept="image/*" style="display: none;" onchange="uploadCategoryFile(event)">
                            </label>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px 24px; background: #fafafa; border-top: 1px solid var(--border-color); text-align: right;">
                    <button type="button" class="form-control" onclick="closeModal('category-modal')" style="cursor: pointer; margin-right: 8px;">Cancel</button>
                    <button type="submit" class="btn-action"><i class="fa-solid fa-check"></i> Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT PRODUCT -->
    <div id="product-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="prod-modal-title">Add New Product</h3>
                <button type="button" class="modal-close" onclick="closeModal('product-modal')">&times;</button>
            </div>
            <form id="prod-form" onsubmit="handleProductSubmit(event)">
                <input type="hidden" id="prod-id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category</label>
                        <select id="prod-category" class="form-control" required>
                            <?php foreach ($categoryList as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['name_bn']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Title (English) *</label>
                            <input type="text" id="prod-title" class="form-control" placeholder="Fresh Cucumber" required>
                        </div>
                        <div class="form-group">
                            <label>নাম (বাংলা) *</label>
                            <input type="text" id="prod-title-bn" class="form-control" placeholder="তাজা শসা" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Description (English)</label>
                            <input type="text" id="prod-desc" class="form-control" placeholder="Fresh and organic daily product">
                        </div>
                        <div class="form-group">
                            <label>বর্ণনা (বাংলা)</label>
                            <input type="text" id="prod-desc-bn" class="form-control" placeholder="তাজা এবং অর্গানিক পণ্য">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Poriman / Unit (EN)</label>
                            <input type="text" id="prod-poriman" class="form-control" value="1 kg" required>
                        </div>
                        <div class="form-group">
                            <label>পরিমাপ (বাংলা)</label>
                            <input type="text" id="prod-poriman-bn" class="form-control" value="১ কেজি" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Product Image</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="prod-image" class="form-control" placeholder="https://..." value="https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=500" style="flex: 1;">
                            <label class="btn-action" style="padding: 9px 12px; cursor: pointer; font-size: 12.5px; border-radius: 8px; white-space: nowrap;">
                                <i class="fa-solid fa-upload"></i> Upload
                                <input type="file" accept="image/*" style="display: none;" onchange="uploadProductFile(event)">
                            </label>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px 24px; background: #fafafa; border-top: 1px solid var(--border-color); text-align: right;">
                    <button type="button" class="form-control" onclick="closeModal('product-modal')" style="cursor: pointer; margin-right: 8px;">Cancel</button>
                    <button type="submit" class="btn-action"><i class="fa-solid fa-check"></i> Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ORDER DETAILS / INVOICE -->
    <div id="order-modal" class="modal-overlay">
        <div class="modal-box" style="max-width: 680px;">
            <div class="modal-header">
                <h3 id="modal-order-number">Order Details</h3>
                <button type="button" class="modal-close" onclick="closeModal('order-modal')">&times;</button>
            </div>
            <div class="modal-body" id="modal-order-content">
                <!-- Injected dynamically -->
            </div>
            <div style="padding: 16px 24px; background: #fafafa; border-top: 1px solid var(--border-color); text-align: right;">
                <button type="button" class="form-control" onclick="closeModal('order-modal')" style="cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT DELIVERY RULE -->
    <div id="delivery-rule-modal" class="modal-overlay">
        <div class="modal-box" style="max-width: 750px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: white; border-bottom: 1px solid #e2e8f0; padding: 18px 24px;">
                <h3 id="delivery-rule-modal-title" style="display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; color: #0f172a;">
                    <i class="fa-solid fa-sliders" style="color: #2563eb;"></i> Create Delivery Charge Rule
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('delivery-rule-modal')" style="font-size: 22px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1;">&times;</button>
            </div>
            <form id="del-rule-form" onsubmit="handleDeliveryRuleSubmit(event)">
                <input type="hidden" id="del-rule-id" value="">
                <div class="modal-body" style="padding: 22px 24px; max-height: 78vh; overflow-y: auto;">
                    <!-- Rule Name -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Rule Name *</label>
                        <input type="text" id="del-rule-name" class="form-control" placeholder="Free Delivery over 1000 TK" required style="padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px;">
                    </div>

                    <!-- Target Scope & Charge Based On -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                        <div class="form-group">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Target Scope *</label>
                            <select id="del-rule-target" class="form-control" style="padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px; background: white;" required>
                                <option value="custom_list">Order Custom List</option>
                                <option value="website">Order from Website</option>
                                <option value="all">All Orders</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Charge Based On *</label>
                            <select id="del-rule-based-on" class="form-control" onchange="updateRuleDynamicFields()" style="padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px; background: white;" required>
                                <option value="order_amount">Order Amount (৳)</option>
                                <option value="weight">Weight (KG)</option>
                                <option value="distance">Distance (KM)</option>
                                <option value="zone">Delivery Zone/Area</option>
                                <option value="category">Product Category</option>
                                <option value="amount_weight">Order Amount + Weight</option>
                                <option value="distance_weight">Distance + Weight</option>
                                <option value="custom">Advanced Custom Rule</option>
                            </select>
                        </div>
                    </div>

                    <!-- RULE CONDITION SETTINGS Card -->
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #475569; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 12px;">RULE CONDITION SETTINGS</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="form-group">
                                <label style="font-size: 13px; font-weight: 700; color: #334155;">Condition Operator</label>
                                <select id="del-rule-operator" class="form-control" onchange="updateRuleDynamicFields()" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px; background: white;" required>
                                    <option value="greater_than_or_equal">Greater Than or Equal (&gt;=)</option>
                                    <option value="less_than_or_equal">Less Than or Equal (&lt;=)</option>
                                    <option value="between">Between</option>
                                    <option value="exact">Exact Match (=)</option>
                                    <option value="greater_than">Greater Than (&gt;)</option>
                                    <option value="less_than">Less Than (&lt;)</option>
                                </select>
                            </div>
                            <div class="form-group" id="del-rule-min-wrapper">
                                <label id="del-rule-min-label" style="font-size: 13px; font-weight: 700; color: #334155;">Order Amount / Min (৳)</label>
                                <input type="number" step="any" id="del-rule-min" class="form-control" placeholder="1000" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                            </div>
                        </div>
                        <div class="form-group" id="del-rule-max-wrapper" style="display: none; margin-top: 12px;">
                            <label id="del-rule-max-label" style="font-size: 13px; font-weight: 700; color: #334155;">Order Amount / Max (৳)</label>
                            <input type="number" step="any" id="del-rule-max" class="form-control" placeholder="2000" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                    </div>

                    <!-- CHARGE CALCULATION METHOD Card -->
                    <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #15803d; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 12px;">CHARGE CALCULATION METHOD</div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Calculation Method *</label>
                            <select id="del-rule-calc-method" class="form-control" onchange="updateCalcMethodFields()" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px; background: white;" required>
                                <option value="fixed">Fixed Amount (৳)</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="per_kg">Per KG Rate</option>
                                <option value="per_km">Per KM Rate</option>
                                <option value="base_per_kg">Base Charge + Per KG</option>
                                <option value="base_per_km">Base Charge + Per KM</option>
                                <option value="additional_charge">Additional Charge</option>
                                <option value="free">Free Delivery (৳0)</option>
                            </select>
                        </div>
                        
                        <!-- Single Value Input (Fixed, Percentage, Per KG, Per KM, Additional, Free) -->
                        <div class="form-group" id="del-rule-single-wrapper">
                            <label id="del-rule-charge-label" style="font-size: 13px; font-weight: 700; color: #334155;">Fixed Amount (৳)</label>
                            <input type="number" step="0.01" id="del-rule-charge" class="form-control" placeholder="60.00" value="60.00" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>

                        <!-- Dual Value Inputs: Base Charge + Rate Per KG / KM -->
                        <div id="del-rule-dual-wrapper" style="display: none; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="form-group">
                                <label style="font-size: 13px; font-weight: 700; color: #334155;">Base Charge (৳)</label>
                                <input type="number" step="0.01" id="del-rule-base-charge" class="form-control" placeholder="40.00" value="40.00" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                            </div>
                            <div class="form-group">
                                <label id="del-rule-per-unit-label" style="font-size: 13px; font-weight: 700; color: #334155;">Rate Per KG (৳)</label>
                                <input type="number" step="0.01" id="del-rule-per-unit-rate" class="form-control" placeholder="10.00" value="10.00" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                            </div>
                        </div>
                    </div>

                    <!-- Priority & Active Status -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; align-items: flex-end;">
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Priority (Lower number = evaluated first)</label>
                            <input type="number" id="del-rule-priority" class="form-control" value="1" required style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Active Status</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 700; color: #1e293b; user-select: none; padding-bottom: 4px;">
                                <input type="checkbox" id="del-rule-active-checkbox" checked style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer; border-radius: 4px;">
                                Rule Active
                            </label>
                        </div>
                    </div>

                    <!-- Min & Max Charge Limit -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Min Charge Limit (৳)</label>
                            <input type="number" step="0.01" id="del-rule-min-limit" class="form-control" placeholder="Optional min" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Max Charge Limit (৳)</label>
                            <input type="number" step="0.01" id="del-rule-max-limit" class="form-control" placeholder="Optional max" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                    </div>

                    <!-- Effective Dates -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 6px;">
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Effective From</label>
                            <input type="date" id="del-rule-effective-from" class="form-control" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Effective To</label>
                            <input type="date" id="del-rule-effective-to" class="form-control" style="padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                    </div>
                </div>
                <div style="padding: 16px 24px; background: #fafafa; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="form-control" onclick="closeModal('delivery-rule-modal')" style="cursor: pointer; width: auto; font-weight: 700; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; padding: 10px 22px;">Cancel</button>
                    <button type="submit" class="btn-action" style="background: #2563eb; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); display: inline-flex; align-items: center; gap: 8px; color: white; cursor: pointer;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT DISCOUNT & OFFER RULE -->
    <div id="discount-modal" class="modal-overlay">
        <div class="modal-box" style="max-width: 780px; width: 94vw; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2); box-sizing: border-box;">
            <div class="modal-header" style="background: white; border-bottom: 1px solid #e2e8f0; padding: 18px 24px;">
                <h3 id="discount-modal-title" style="display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; color: #0f172a;">
                    <i class="fa-solid fa-tags" style="color: #f59e0b;"></i> Create Discount / Offer Rule
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('discount-modal')" style="font-size: 22px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1;">&times;</button>
            </div>
            <form id="discount-rule-form" onsubmit="submitSaveDiscount(event)">
                <input type="hidden" id="disc-rule-id" value="">
                <div class="modal-body" style="padding: 22px 24px; max-height: 78vh; overflow-y: auto; overflow-x: hidden; box-sizing: border-box;">
                    <!-- Offer Title EN & BN -->
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-bottom: 14px;">
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Offer Title *</label>
                            <input type="text" id="disc-title" class="form-control" placeholder="Buy Rice + Chicken Get Green Chili Free" required style="width: 100%; min-width: 0; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px;">
                        </div>
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Offer Title (Bangla)</label>
                            <input type="text" id="disc-title-bn" class="form-control" placeholder="Optional Bengali title" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px;">
                        </div>
                    </div>

                    <!-- Badge / Ribbon Text -->
                    <div class="form-group" style="margin-bottom: 14px; min-width: 0;">
                        <label style="font-size: 13px; font-weight: 700; color: #334155;">Promo Ribbon / Badge Text (Optional)</label>
                        <input type="text" id="disc-badge-text" class="form-control" placeholder="e.g. HOT COMBO, FREE GIFT, 10% OFF" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                    </div>

                    <!-- Discount Type & Target Scope -->
                    <div style="display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr); gap: 14px; margin-bottom: 16px;">
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13px; font-weight: 700; color: #1e293b;">Discount / Offer Type *</label>
                            <select id="disc-type" class="form-control" onchange="handleDiscountTypeChange()" style="width: 100%; min-width: 0; max-width: 100%; box-sizing: border-box; text-overflow: ellipsis; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 700; background: white;" required>
                                <option value="combo_bundle">🎁 Combo Bundle (Buy X + Y Get Z)</option>
                                <option value="free_gift">🎁 Free Gift with Order</option>
                                <option value="fixed_amount">Fixed Amount (৳ Off)</option>
                                <option value="percentage">Percentage Discount (% Off)</option>
                                <option value="bogo">Buy 1 Get 1 (BOGO Free)</option>
                                <option value="cart_spend">Cart Spend Tier Discount</option>
                                <option value="free_delivery">🚚 Free Delivery</option>
                                <option value="coupon">Promo Coupon Code</option>
                            </select>
                        </div>
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13px; font-weight: 700; color: #1e293b;">Trigger Condition Scope *</label>
                            <select id="disc-target-type" class="form-control" onchange="handleTargetTypeChange()" style="width: 100%; min-width: 0; max-width: 100%; box-sizing: border-box; text-overflow: ellipsis; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; font-weight: 700; background: white;" required>
                                <option value="multi_products">Multi-Products (Combo Bundle)</option>
                                <option value="entire_cart">Entire Cart</option>
                                <option value="single_product">Single Product</option>
                                <option value="category">Category</option>
                            </select>
                        </div>
                    </div>

                    <!-- Target Selector (Product or Category) -->
                    <div id="disc-product-wrapper" class="form-group" style="display: none; margin-bottom: 16px; min-width: 0;">
                        <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Select Trigger Product *</label>
                        <select id="disc-target-product" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px; background: white;">
                            <option value="">-- Choose Product --</option>
                            <?php foreach ($productList as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (৳<?= number_format($p['price'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="disc-category-wrapper" class="form-group" style="display: none; margin-bottom: 16px; min-width: 0;">
                        <label style="font-size: 13.5px; font-weight: 700; color: #1e293b;">Select Category *</label>
                        <select id="disc-target-category" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 14px; background: white;">
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($categoryList as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Target Selector: Multi-Products Combo Checklist -->
                    <div id="disc-multi-products-wrapper" style="display: none; margin-bottom: 18px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 14px 16px; box-sizing: border-box;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                            <label style="font-size: 13px; font-weight: 800; color: #1e293b;">
                                <i class="fa-solid fa-layer-group" style="color: #2563eb;"></i> Select Required Products for Combo (Buy all of these) *
                            </label>
                            <span id="disc-combo-selected-count" style="font-size: 11.5px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 12px;">0 items selected</span>
                        </div>
                        <div style="position: relative; margin-bottom: 10px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
                            <input type="text" id="disc-combo-search" oninput="filterDiscComboProducts()" placeholder="Filter combo products by name..." style="width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px 7px 30px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; outline: none;">
                        </div>
                        <div id="disc-combo-products-list" style="max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px;">
                            <?php foreach ($productList as $p): ?>
                                <label class="disc-combo-item" data-search="<?= htmlspecialchars(strtolower($p['title'] . ' ' . ($p['title_bn'] ?? '') . ' ' . ($p['product_code'] ?? ''))) ?>" style="display: flex; align-items: center; justify-content: space-between; background: white; padding: 7px 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; font-size: 13px; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                                        <input type="checkbox" value="<?= $p['id'] ?>" class="disc-combo-check" onchange="updateDiscComboCount()" style="width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; flex-shrink: 0;">
                                        <span style="font-weight: 700; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($p['title']) ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                        <span style="font-weight: 800; color: #059669; font-size: 12px;">৳<?= number_format($p['price'], 2) ?></span>
                                        <div class="disc-combo-qty-wrap" style="display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 2px 6px; border-radius: 6px;" onclick="event.stopPropagation();">
                                            <span style="font-size: 11px; color: #64748b; font-weight: 700;">Qty:</span>
                                            <input type="number" min="1" step="1" value="1" class="disc-combo-item-qty" data-prod-id="<?= $p['id'] ?>" style="width: 44px; padding: 2px 4px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; text-align: center; font-weight: 800; background: white;">
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- REWARD / BENEFIT CONFIGURATION CARD -->
                    <div id="disc-reward-section" style="background: #fdf2f8; border: 1.5px solid #fbcfe8; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px; box-sizing: border-box;">
                        <div style="font-size: 12px; font-weight: 800; color: #be185d; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-gift"></i> REWARD / BENEFIT (WHAT THE CUSTOMER GETS)
                        </div>
                        <div style="display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr); gap: 14px; margin-bottom: 12px;">
                            <div class="form-group" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #831843;">Reward Type</label>
                                <select id="disc-reward-type" class="form-control" onchange="handleRewardTypeChange()" style="width: 100%; min-width: 0; box-sizing: border-box; text-overflow: ellipsis; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #fbcfe8; font-size: 13px; font-weight: 700; background: white;">
                                    <option value="free_product">🎁 Free Gift Product</option>
                                    <option value="discount_amount">৳ Fixed Cash Discount</option>
                                    <option value="discount_percent">% Percentage Discount</option>
                                    <option value="free_delivery">🚚 Free Shipping</option>
                                </select>
                            </div>
                            <div class="form-group" id="disc-reward-qty-wrapper" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #831843;">Gift Qty (pcs)</label>
                                <input type="number" id="disc-reward-qty" class="form-control" value="1" min="1" step="1" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #fbcfe8; font-size: 13px; font-weight: 700;">
                            </div>
                        </div>

                        <div class="form-group" id="disc-reward-product-wrapper" style="min-width: 0; margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: 700; color: #831843;">Select Free Gift / Reward Product *</label>
                            <select id="disc-reward-product" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #fbcfe8; font-size: 13.5px; background: white;">
                                <option value="">-- Choose Free Gift Item --</option>
                                <?php foreach ($productList as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (৳<?= number_format($p['price'], 2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Reward Limit / Distribution Rule (Proportional vs Fixed Cap) -->
                        <div id="disc-reward-limit-card" style="background: white; border: 1.5px solid #fbcfe8; border-radius: 8px; padding: 12px 14px; margin-top: 6px;">
                            <label style="font-size: 12.5px; font-weight: 800; color: #9d174d; display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                <i class="fa-solid fa-sliders"></i> ফ্রি উপহার প্রদানের নিয়ম (Reward Distribution Limit):
                            </label>
                            <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 0.6fr); gap: 10px; align-items: center;">
                                <select id="disc-reward-limit-mode" onchange="handleRewardLimitModeChange()" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1.5px solid #f472b6; font-size: 12.5px; font-weight: 700; background: #fff5f7;">
                                    <option value="unlimited">প্রতি সেটের অনুপাতে (৫ সেটে ৫টি ফ্রি / সমানুপাতিক)</option>
                                    <option value="max_1">প্রতি অর্ডারে সর্বোচ্চ ১টি ফ্রি (Fixed Cap: Max 1)</option>
                                    <option value="custom">কাস্টম সর্বোচ্চ সীমা (Custom Max Limit)</option>
                                </select>
                                <div id="disc-custom-limit-wrap" style="display: none;">
                                    <input type="number" id="disc-custom-max-limit" min="1" step="1" placeholder="Max Qty (e.g. 2)" value="1" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1.5px solid #f472b6; font-size: 12.5px; font-weight: 800; text-align: center; box-sizing: border-box;">
                                </div>
                            </div>
                            <span style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: block;">
                                গ্রাহক ৫টি আলু ও ৫টি পেঁয়াজ কিনলে ৫টি মরিচ ফ্রি পাবে নাকি প্রতি অর্ডারে সর্বোচ্চ ১টি ফ্রি পাবে তা এখান থেকে নির্ধারণ করুন।
                            </span>
                        </div>
                    </div>

                    <!-- Value & Requirements Card -->
                    <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px; box-sizing: border-box;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #b45309; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 12px;">DISCOUNT VALUE &amp; CONDITIONS</div>
                        <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px;">
                            <div class="form-group" id="disc-val-wrapper" style="min-width: 0;">
                                <label id="disc-val-label" style="font-size: 13px; font-weight: 700; color: #334155;">Discount Amount (৳)</label>
                                <input type="number" step="0.01" id="disc-value" class="form-control" placeholder="50" value="50" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                            </div>
                            <div class="form-group" id="disc-min-cart-wrapper" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #334155;">Min Cart Amount (৳)</label>
                                <input type="number" step="any" id="disc-min-cart" class="form-control" placeholder="0 = No Min" value="0" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-top: 12px;">
                            <div class="form-group" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #334155;">Min Quantity (pcs)</label>
                                <input type="number" id="disc-min-qty" class="form-control" placeholder="1" value="1" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                            </div>
                            <div class="form-group" id="disc-coupon-wrapper" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #334155;">Coupon Code (Optional)</label>
                                <input type="text" id="disc-coupon-code" class="form-control" placeholder="SAVE50" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOMER & PAYMENT TARGETING -->
                    <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px; box-sizing: border-box;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #15803d; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 12px;">AUDIENCE &amp; PAYMENT TARGETING</div>
                        <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px;">
                            <div class="form-group" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #166534;">Target Customer Segment</label>
                                <select id="disc-customer-target" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; text-overflow: ellipsis; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #bbf7d0; font-size: 13px; font-weight: 700; background: white;">
                                    <option value="all">All Customers</option>
                                    <option value="new_customers">New Customers</option>
                                    <option value="registered">Registered Customers</option>
                                </select>
                            </div>
                            <div class="form-group" style="min-width: 0;">
                                <label style="font-size: 13px; font-weight: 700; color: #166534;">Target Payment Method</label>
                                <select id="disc-payment-method-target" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; text-overflow: ellipsis; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #bbf7d0; font-size: 13px; font-weight: 700; background: white;">
                                    <option value="all">All Payment Methods</option>
                                    <option value="online_only">Online / bKash Only</option>
                                    <option value="cod_only">Cash on Delivery (COD)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Priority & Active Status -->
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-bottom: 16px; align-items: flex-end;">
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Priority (Evaluation order)</label>
                            <input type="number" id="disc-priority" class="form-control" value="1" required style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Active Status</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 700; color: #1e293b; user-select: none; padding-bottom: 4px;">
                                <input type="checkbox" id="disc-active-checkbox" checked style="width: 18px; height: 18px; accent-color: #f59e0b; cursor: pointer; border-radius: 4px;">
                                Discount Rule Active
                            </label>
                        </div>
                    </div>

                    <!-- Start & End Date -->
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-bottom: 6px;">
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">Start Date &amp; Time</label>
                            <input type="datetime-local" id="disc-start-date" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                        <div class="form-group" style="min-width: 0;">
                            <label style="font-size: 13px; font-weight: 700; color: #334155;">End Date &amp; Time</label>
                            <input type="datetime-local" id="disc-end-date" class="form-control" style="width: 100%; min-width: 0; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13.5px;">
                        </div>
                    </div>
                </div>
                <div style="padding: 16px 24px; background: #fafafa; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="form-control" onclick="closeModal('discount-modal')" style="cursor: pointer; width: auto; font-weight: 700; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; padding: 10px 22px;">Cancel</button>
                    <button type="submit" class="btn-action" style="background: #f59e0b; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); display: inline-flex; align-items: center; gap: 8px; color: white; cursor: pointer;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Discount
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT CUSTOMER REVIEW (OHC) -->
    <div id="ohc-modal" class="modal-overlay" style="align-items: flex-start; padding: 30px 15px; overflow-y: auto;">
        <div class="modal-box" style="max-width: 540px; width: 100%; border-radius: 16px; margin: auto; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); background: #ffffff;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-comments" style="color: #2563eb; font-size: 20px;"></i>
                    <h3 id="ohc-modal-title" style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;">Add Customer Review</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('ohc-modal')" style="font-size: 24px; color: #94a3b8; cursor: pointer; border: none; background: none; line-height: 1; padding: 4px;">&times;</button>
            </div>
            
            <form id="ohc-form" onsubmit="handleOhcSubmit(event)" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                <input type="hidden" id="ohc-id" value="">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Customer Name (English) *</label>
                        <input type="text" id="ohc-name" class="form-control" placeholder="Mahade Hasan" required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Customer Name (Bangla)</label>
                        <input type="text" id="ohc-name-bn" class="form-control" placeholder="যেমন: মাহাদি হাসান" style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                    </div>
                </div>

                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Rating *</label>
                    <select id="ohc-rating" class="form-control" required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white;">
                        <option value="5" selected>★★★★★ (5 Stars - Excellent)</option>
                        <option value="4">★★★★☆ (4 Stars - Very Good)</option>
                        <option value="3">★★★☆☆ (3 Stars - Good)</option>
                        <option value="2">★★☆☆☆ (2 Stars - Fair)</option>
                        <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                    </select>
                </div>

                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Comment (English) *</label>
                    <textarea id="ohc-comment" class="form-control" rows="3" placeholder="Write customer feedback in English..." required style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                </div>

                <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-weight: 700; font-size: 13.5px; color: #334155;">Comment (Bangla)</label>
                    <textarea id="ohc-comment-bn" class="form-control" rows="3" placeholder="গ্রাহকের মন্তব্য বাংলায় লিখুন..." style="padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                </div>

                <div class="form-group" style="padding-top: 4px;">
                    <label style="display: inline-flex; align-items: center; gap: 10px; font-weight: 700; font-size: 14px; cursor: pointer; color: #1e293b;">
                        <input type="checkbox" id="ohc-is-active" checked style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                        <span>Show on homepage (Active)</span>
                    </label>
                </div>

                <div style="padding-top: 10px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="form-control" onclick="closeModal('ohc-modal')" style="width: auto; padding: 10px 20px; font-weight: 700; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; border-radius: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn-action" style="background: #2563eb; color: white; border: none; padding: 10px 24px; font-weight: 700; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-check"></i> <span id="ohc-btn-text">Save Review</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- BEAUTIFUL CUSTOM CONFIRMATION POPUP -->
    <div id="custom-confirm-modal" class="modal-overlay" style="z-index: 4000;">
        <div class="modal-box" style="max-width: 440px; text-align: center; padding: 32px 26px; border-radius: 20px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.4); animation: modalFadeIn 0.2s ease-out;">
            <div id="confirm-icon-wrapper" style="width: 70px; height: 70px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 18px; box-shadow: 0 8px 20px rgba(239, 68, 68, 0.15);">
                <i id="confirm-icon" class="fa-solid fa-trash-can"></i>
            </div>
            <h3 id="confirm-title" style="margin: 0 0 10px 0; font-size: 20px; font-weight: 900; color: #0f172a;">Confirm Action</h3>
            <p id="confirm-message" style="margin: 0 0 26px 0; font-size: 14.5px; color: #64748b; line-height: 1.55;">Are you sure you want to proceed?</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" id="confirm-btn-cancel" class="form-control" style="flex: 1; padding: 12px; font-weight: 700; border-radius: 10px; cursor: pointer; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; font-size: 14px;">
                    Cancel
                </button>
                <button type="button" id="confirm-btn-ok" class="btn-action" style="flex: 1; padding: 12px; font-weight: 700; border-radius: 10px; justify-content: center; background: #ef4444; border: none; color: white; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35); cursor: pointer; font-size: 14px;">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- BEAUTIFUL CUSTOM PROMPT POPUP -->
    <div id="custom-prompt-modal" class="modal-overlay" style="z-index: 4001; display: none;">
        <div class="modal-box" style="max-width: 460px; text-align: left; padding: 28px 26px; border-radius: 20px; box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.4); animation: modalFadeIn 0.2s ease-out; background: white;">
            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                <div id="prompt-icon-wrapper" style="width: 48px; height: 48px; border-radius: 12px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i id="prompt-icon" class="fa-solid fa-pen-nib"></i>
                </div>
                <div>
                    <h3 id="prompt-title" style="margin: 0; font-size: 18px; font-weight: 900; color: #0f172a;">Input Required</h3>
                    <p id="prompt-message" style="margin: 3px 0 0 0; font-size: 13.5px; color: #64748b; line-height: 1.4;">Please enter the required information:</p>
                </div>
            </div>
            
            <div style="margin-bottom: 22px;">
                <input type="text" id="prompt-input" class="form-control" style="width: 100%; padding: 12px 14px; font-size: 14px; font-weight: 600; border: 1.5px solid #cbd5e1; border-radius: 10px; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                <small id="prompt-hint" style="color: #94a3b8; font-size: 12px; margin-top: 5px; display: block;"></small>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" id="prompt-btn-cancel" class="form-control" style="width: auto; padding: 10px 20px; font-weight: 700; border-radius: 10px; cursor: pointer; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; font-size: 13.5px;">
                    Cancel
                </button>
                <button type="button" id="prompt-btn-ok" class="btn-action" style="width: auto; padding: 10px 24px; font-weight: 700; border-radius: 10px; justify-content: center; background: #4f46e5; border: none; color: white; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3); cursor: pointer; font-size: 13.5px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-check"></i> Continue
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript Actions -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('auth-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        }

        function toggleSidebarCollapse() {
            if (window.innerWidth <= 1024) {
                toggleSidebar();
                return;
            }
            const sidebar = document.getElementById('auth-sidebar');
            const wrapper = document.querySelector('.auth-main-wrapper');
            if (!sidebar) return;

            const isCollapsed = sidebar.classList.toggle('collapsed');
            if (wrapper) wrapper.classList.toggle('sidebar-collapsed', isCollapsed);
            localStorage.setItem('auth_sidebar_collapsed', isCollapsed ? '1' : '0');
        }

        function toggleUsersDropdown(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const sidebar = document.getElementById('auth-sidebar');
            if (sidebar && sidebar.classList.contains('collapsed')) {
                toggleSidebarCollapse();
            }
            const dropdown = document.getElementById('users-sidebar-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('open');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('auth_sidebar_collapsed') === '1' && window.innerWidth > 1024) {
                const sidebar = document.getElementById('auth-sidebar');
                const wrapper = document.querySelector('.auth-main-wrapper');
                if (sidebar) sidebar.classList.add('collapsed');
                if (wrapper) wrapper.classList.add('sidebar-collapsed');
            }
        });

        function toggleUserDropdown() {
            const drop = document.getElementById('user-dropdown');
            drop.style.display = drop.style.display === 'block' ? 'none' : 'block';
        }

        function toggleMobileMenu() {
            const drawer = document.getElementById('auth-drawer');
            drawer.style.display = drawer.style.display === 'block' ? 'none' : 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function showToast(msg, isSuccess = true) {
            const div = document.createElement('div');
            div.style = `position: fixed; top: 20px; right: 20px; z-index: 9999; background: ${isSuccess ? '#10b981' : '#ef4444'}; color: white; padding: 14px 22px; border-radius: 8px; font-weight: 700; box-shadow: 0 10px 25px rgba(0,0,0,0.15); font-size: 14px; display: flex; align-items: center; gap: 8px;`;
            div.innerHTML = `<i class="fa-solid ${isSuccess ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> ` + msg;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3200);
        }

        // --- CUSTOM CONFIRM DIALOG ---
        function showCustomConfirm({ title, message, icon = 'fa-trash-can', iconBg = '#fee2e2', iconColor = '#ef4444', confirmText = 'Yes, Delete', confirmBtnBg = '#ef4444', cancelText = 'Cancel' }) {
            return new Promise((resolve) => {
                const modal = document.getElementById('custom-confirm-modal');
                const titleEl = document.getElementById('confirm-title');
                const msgEl = document.getElementById('confirm-message');
                const iconEl = document.getElementById('confirm-icon');
                const iconWrap = document.getElementById('confirm-icon-wrapper');
                const btnOk = document.getElementById('confirm-btn-ok');
                const btnCancel = document.getElementById('confirm-btn-cancel');

                titleEl.innerText = title;
                msgEl.innerHTML = message;
                iconEl.className = 'fa-solid ' + icon;
                iconWrap.style.background = iconBg;
                iconWrap.style.color = iconColor;
                btnOk.innerHTML = `<i class="fa-solid ${icon}"></i> ${confirmText}`;
                btnOk.style.background = confirmBtnBg;
                btnCancel.innerText = cancelText;

                modal.style.display = 'flex';

                const handleOk = () => {
                    cleanup();
                    resolve(true);
                };

                const handleCancel = () => {
                    cleanup();
                    resolve(false);
                };

                const cleanup = () => {
                    modal.style.display = 'none';
                    btnOk.onclick = null;
                    btnCancel.onclick = null;
                };

                btnOk.onclick = handleOk;
                btnCancel.onclick = handleCancel;
            });
        }

        // --- CUSTOM PROMPT DIALOG ---
        function showCustomPrompt({ title = 'Input Required', message = 'Please enter value:', placeholder = '', defaultValue = '', hint = '', icon = 'fa-pen-nib', iconBg = '#ede9fe', iconColor = '#7c3aed', confirmText = 'Continue', confirmBtnBg = '#4f46e5' }) {
            return new Promise((resolve) => {
                const modal = document.getElementById('custom-prompt-modal');
                const titleEl = document.getElementById('prompt-title');
                const msgEl = document.getElementById('prompt-message');
                const iconEl = document.getElementById('prompt-icon');
                const iconWrap = document.getElementById('prompt-icon-wrapper');
                const inputEl = document.getElementById('prompt-input');
                const hintEl = document.getElementById('prompt-hint');
                const btnOk = document.getElementById('prompt-btn-ok');
                const btnCancel = document.getElementById('prompt-btn-cancel');

                titleEl.innerText = title;
                msgEl.innerHTML = message;
                iconEl.className = 'fa-solid ' + icon;
                iconWrap.style.background = iconBg;
                iconWrap.style.color = iconColor;
                inputEl.placeholder = placeholder;
                inputEl.value = defaultValue;
                hintEl.innerText = hint;
                btnOk.innerHTML = `<i class="fa-solid fa-check"></i> ${confirmText}`;
                btnOk.style.background = confirmBtnBg;

                modal.style.display = 'flex';
                setTimeout(() => {
                    inputEl.focus();
                    inputEl.select();
                }, 50);

                const handleOk = () => {
                    const val = inputEl.value;
                    cleanup();
                    resolve(val);
                };

                const handleCancel = () => {
                    cleanup();
                    resolve(null);
                };

                const handleKeydown = (e) => {
                    if (e.key === 'Enter') handleOk();
                    if (e.key === 'Escape') handleCancel();
                };

                const cleanup = () => {
                    modal.style.display = 'none';
                    btnOk.onclick = null;
                    btnCancel.onclick = null;
                    inputEl.removeEventListener('keydown', handleKeydown);
                };

                btnOk.onclick = handleOk;
                btnCancel.onclick = handleCancel;
                inputEl.addEventListener('keydown', handleKeydown);
            });
        }

        // --- MANAGEMENT ACTIONS ---
        function openManagementModal() {
            document.getElementById('mng-id').value = '';
            document.getElementById('mng-name').value = '';
            document.getElementById('mng-phone').value = '';
            document.getElementById('mng-email').value = '';
            document.getElementById('mng-location').value = 'Corporate Head Office, Dhaka';
            document.getElementById('mng-role').value = 'management';
            document.getElementById('mng-status').value = 'active';
            document.getElementById('mng-password').value = '';
            document.getElementById('mng-password').placeholder = 'Default: 123456';
            document.getElementById('mng-modal-title').innerText = 'Add New Management';
            document.getElementById('mng-btn-text').innerText = 'Create Management Account';
            document.getElementById('management-modal').style.display = 'flex';
        }

        function openEditManagementModal(m) {
            document.getElementById('mng-id').value = m.id;
            document.getElementById('mng-name').value = m.name || '';
            document.getElementById('mng-phone').value = m.phone || '';
            document.getElementById('mng-email').value = m.email || '';
            document.getElementById('mng-location').value = m.default_location || 'Corporate Head Office, Dhaka';
            document.getElementById('mng-role').value = m.role || 'management';
            document.getElementById('mng-status').value = m.status || 'active';
            document.getElementById('mng-password').value = '';
            document.getElementById('mng-password').placeholder = 'Leave blank to keep current password';
            document.getElementById('mng-modal-title').innerText = 'Edit Management Account #' + m.id;
            document.getElementById('mng-btn-text').innerText = 'Update Management Account';
            document.getElementById('management-modal').style.display = 'flex';
        }

        async function handleManagementSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('mng-id').value;
            const action = id ? 'edit_management' : 'add_management';
            const payload = {
                id: id,
                name: document.getElementById('mng-name').value.trim(),
                phone: document.getElementById('mng-phone').value.trim(),
                email: document.getElementById('mng-email').value.trim(),
                location: document.getElementById('mng-location').value.trim(),
                role: document.getElementById('mng-role').value,
                status: document.getElementById('mng-status').value,
                password: document.getElementById('mng-password').value.trim()
            };

            const res = await fetch('api/admin.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('management-modal');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteManagement(id, name) {
            const ok = await showCustomConfirm({
                title: "Delete Management Account?",
                message: `Are you sure you want to delete management account <strong>'${name}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">This account will permanently lose management access.</span>`,
                confirmText: "Yes, Delete",
                icon: "fa-user-xmark"
            });
            if (!ok) return;

            const res = await fetch('api/admin.php?action=delete_management', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.message, false);
            }
        }

        // --- AUTHOR & MANAGER ACTIONS ---
        function handlePortalPermModeChange() {
            const mode = document.getElementById('perm_portal_mode').value;
            const subKeys = ['perm_portal_links', 'perm_portal_policies', 'perm_portal_ohc', 'perm_portal_broadcast'];
            if (mode === 'full') {
                subKeys.forEach(k => {
                    const el = document.getElementById(k);
                    if (el) el.checked = true;
                });
            } else if (mode === 'none') {
                subKeys.forEach(k => {
                    const el = document.getElementById(k);
                    if (el) el.checked = false;
                });
            }
        }

        function onPortalSubCheckChange() {
            const subKeys = ['perm_portal_links', 'perm_portal_policies', 'perm_portal_ohc', 'perm_portal_broadcast'];
            const allChecked = subKeys.every(k => document.getElementById(k)?.checked);
            const allUnchecked = subKeys.every(k => !document.getElementById(k)?.checked);
            const select = document.getElementById('perm_portal_mode');
            if (select) {
                if (allChecked) select.value = 'full';
                else if (allUnchecked) select.value = 'none';
                else select.value = 'custom';
            }
        }

        // --- DELIVERY STAFF (RIDER) ACTIONS ---
        function openDeliveryStaffModal() {
            document.getElementById('del-staff-id').value = '';
            document.getElementById('del-staff-fullname').value = '';
            document.getElementById('del-staff-username').value = '';
            document.getElementById('del-staff-phone').value = '';
            document.getElementById('del-staff-password').value = '';
            document.getElementById('del-staff-password').required = true;
            document.getElementById('del-staff-password').placeholder = '••••••••';
            document.getElementById('delivery-staff-modal-title').innerText = 'Add New Delivery Staff';
            document.getElementById('del-staff-btn-text').innerText = 'Create Staff Account';
            document.getElementById('delivery-staff-modal').style.display = 'flex';
        }

        function openEditDeliveryStaffModal(s) {
            document.getElementById('del-staff-id').value = s.id;
            document.getElementById('del-staff-fullname').value = s.name || '';
            document.getElementById('del-staff-username').value = s.username || s.name || '';
            document.getElementById('del-staff-phone').value = s.phone || '';
            document.getElementById('del-staff-password').value = '';
            document.getElementById('del-staff-password').required = false;
            document.getElementById('del-staff-password').placeholder = 'Leave blank to keep password';
            document.getElementById('delivery-staff-modal-title').innerText = 'Edit Delivery Staff';
            document.getElementById('del-staff-btn-text').innerText = 'Save Changes';
            document.getElementById('delivery-staff-modal').style.display = 'flex';
        }

        async function handleDeliveryStaffSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('del-staff-id').value;
            const action = id ? 'edit_staff' : 'add_staff';
            const fullname = document.getElementById('del-staff-fullname').value.trim();
            const username = document.getElementById('del-staff-username').value.trim();
            const phone = document.getElementById('del-staff-phone').value.trim();
            const payload = {
                id: id,
                name: fullname || username,
                full_name: fullname || username,
                username: username || fullname,
                phone: phone ? phone : username,
                password: document.getElementById('del-staff-password').value,
                role: 'rider',
                status: 'active'
            };

            const res = await fetch('api/admin.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('delivery-staff-modal');
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(data.message, false);
            }
        }

        // --- ASSIGN LOCATIONS ACTIONS ---
        function updateAssignLocationThanas(selectedThana = '') {
            const districtSelect = document.getElementById('assign-loc-district');
            const thanaSelect = document.getElementById('assign-loc-station');
            if (!districtSelect || !thanaSelect) return;

            const district = districtSelect.value;
            let thanas = [];

            if (typeof BD_LOCATIONS !== 'undefined' && BD_LOCATIONS[district]) {
                thanas = BD_LOCATIONS[district];
            } else if (typeof getUpazilasForDistrict === 'function') {
                thanas = getUpazilasForDistrict(district);
            }

            thanaSelect.innerHTML = '<option value="">-- Select Thana / Police Station --</option>';

            // Add Delivery Hub options for Dhaka
            if (district.toLowerCase() === 'dhaka') {
                const hubGroup = document.createElement('optgroup');
                hubGroup.label = '⚡ Delivery Hubs (All Areas in Zone)';
                const hubs = [
                    { val: 'Dhaka North', label: 'Dhaka North Hub (Uttara, Mirpur, Gulshan, Banani, Badda...)' },
                    { val: 'Dhaka South', label: 'Dhaka South Hub (Dhanmondi, Motijheel, Lalbagh, Old Dhaka...)' }
                ];
                hubs.forEach(h => {
                    const opt = document.createElement('option');
                    opt.value = h.val;
                    opt.textContent = h.label;
                    if (selectedThana && h.val.toLowerCase() === selectedThana.toLowerCase()) opt.selected = true;
                    hubGroup.appendChild(opt);
                });
                thanaSelect.appendChild(hubGroup);
            }

            const thanaGroup = document.createElement('optgroup');
            thanaGroup.label = '📍 Specific Police Stations / Thanas';

            if (Array.isArray(thanas) && thanas.length > 0) {
                thanas.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t;
                    opt.textContent = t;
                    if (selectedThana && t.toLowerCase() === selectedThana.toLowerCase()) {
                        opt.selected = true;
                    }
                    thanaGroup.appendChild(opt);
                });
                thanaSelect.appendChild(thanaGroup);
            } else {
                const opt = document.createElement('option');
                opt.value = district + " Sadar";
                opt.textContent = district + " Sadar";
                thanaSelect.appendChild(opt);
            }
        }

        function initAssignLocationDropdowns(defaultDistrict = 'Dhaka') {
            const districtSelect = document.getElementById('assign-loc-district');
            if (!districtSelect) return;

            if (typeof BD_DISTRICTS !== 'undefined' && BD_DISTRICTS.length > 0) {
                districtSelect.innerHTML = '';
                BD_DISTRICTS.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.textContent = d;
                    if (d.toLowerCase() === defaultDistrict.toLowerCase()) {
                        opt.selected = true;
                    }
                    districtSelect.appendChild(opt);
                });
            }
            updateAssignLocationThanas();
        }

        function renderAssignedLocations(staffId, locs) {
            const container = document.getElementById('current-locations-list');
            if (!locs || locs.length === 0) {
                container.innerHTML = `<span style="color: #94a3b8; font-size: 13px; font-style: italic;">No locations assigned yet.</span>`;
                return;
            }
            let html = '';
            locs.forEach(l => {
                html += `
                    <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #1e293b;">
                        <span style="display: flex; align-items: center; gap: 6px; font-weight: 600;">
                            <i class="fa-solid fa-location-dot" style="color: #2563eb;"></i> ${l.police_station}, ${l.district}
                        </span>
                        <button type="button" onclick="deleteStaffLocation(${l.id}, ${staffId})" style="border: none; background: #fee2e2; color: #ef4444; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px;" title="Remove Location">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        async function openAssignLocationModal(s) {
            document.getElementById('assign-loc-staff-id').value = s.id;
            document.getElementById('assign-loc-username').innerText = s.name;
            
            initAssignLocationDropdowns('Dhaka');

            renderAssignedLocations(s.id, s.locations || []);
            document.getElementById('assign-location-modal').style.display = 'flex';

            // Fetch latest from API
            try {
                const res = await fetch('api/admin.php?action=get_staff_locations&staff_id=' + s.id);
                const data = await res.json();
                if (data.status === 'success') {
                    renderAssignedLocations(s.id, data.locations);
                }
            } catch(e) {}
        }

        async function submitAddStaffLocation(e) {
            e.preventDefault();
            const staffId = document.getElementById('assign-loc-staff-id').value;
            const district = document.getElementById('assign-loc-district').value;
            const station = document.getElementById('assign-loc-station').value.trim();

            if (!station) {
                showToast("Please select a Thana / Police Station", false);
                return;
            }

            const res = await fetch('api/admin.php?action=add_staff_location', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    staff_id: staffId,
                    district: district,
                    police_station: station
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                updateAssignLocationThanas();
                renderAssignedLocations(staffId, data.locations);
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteStaffLocation(locId, staffId) {
            const res = await fetch('api/admin.php?action=delete_staff_location', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    location_id: locId,
                    staff_id: staffId
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                renderAssignedLocations(staffId, data.locations);
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.message, false);
            }
        }

        function openStaffModal(defaultRole = 'manager') {
            document.getElementById('staff-id').value = '';
            document.getElementById('staff-username').value = '';
            document.getElementById('staff-name').value = '';
            document.getElementById('staff-phone').value = '';
            document.getElementById('staff-password').value = '';
            document.getElementById('staff-password').placeholder = '••••••••';
            document.getElementById('staff-role').value = defaultRole;
            document.getElementById('staff-status').value = 'active';

            // Check default core permissions for managers
            ['perm_staff', 'perm_categories', 'perm_orders', 'perm_delivery_charges', 'perm_customers', 'perm_products', 'perm_invoices'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.checked = true;
            });
            ['perm_portal_links', 'perm_portal_policies', 'perm_portal_ohc', 'perm_portal_broadcast'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.checked = false;
            });
            document.getElementById('perm_portal_mode').value = 'custom';

            const roleTitle = defaultRole === 'manager' ? 'Manager' : (defaultRole === 'admin' ? 'Author' : 'Staff');
            document.getElementById('staff-modal-title').innerText = 'Add New ' + roleTitle;
            document.getElementById('staff-submit-btn').innerText = 'Create ' + roleTitle + ' Account';
            document.getElementById('staff-modal').style.display = 'flex';
        }

        function openEditStaffModal(s) {
            document.getElementById('staff-id').value = s.id;
            document.getElementById('staff-username').value = s.username || s.name || s.phone || '';
            document.getElementById('staff-name').value = s.name || '';
            document.getElementById('staff-phone').value = s.phone || '';
            document.getElementById('staff-role').value = s.role || 'manager';
            document.getElementById('staff-status').value = s.status || 'active';
            document.getElementById('staff-password').value = '';
            document.getElementById('staff-password').placeholder = '(Leave blank to keep password)';

            let perms = s.permissions;
            if (typeof perms === 'string') {
                try { perms = JSON.parse(perms); } catch(e) { perms = null; }
            }

            if (perms && typeof perms === 'object') {
                if (document.getElementById('perm_staff')) document.getElementById('perm_staff').checked = !!perms.staff;
                if (document.getElementById('perm_customers')) document.getElementById('perm_customers').checked = !!(perms.customers || perms.customer);
                if (document.getElementById('perm_categories')) document.getElementById('perm_categories').checked = !!(perms.categories || perms.category);
                if (document.getElementById('perm_products')) document.getElementById('perm_products').checked = !!(perms.products || perms.product);
                if (document.getElementById('perm_orders')) document.getElementById('perm_orders').checked = !!(perms.orders || perms.order);
                if (document.getElementById('perm_invoices')) document.getElementById('perm_invoices').checked = !!(perms.invoices || perms.invoice);
                if (document.getElementById('perm_delivery_charges')) document.getElementById('perm_delivery_charges').checked = !!perms.delivery_charges;
                if (document.getElementById('perm_portal_links')) document.getElementById('perm_portal_links').checked = !!perms.portal_links;
                if (document.getElementById('perm_portal_policies')) document.getElementById('perm_portal_policies').checked = !!perms.portal_policies;
                if (document.getElementById('perm_portal_ohc')) document.getElementById('perm_portal_ohc').checked = !!perms.portal_ohc;
                if (document.getElementById('perm_portal_broadcast')) document.getElementById('perm_portal_broadcast').checked = !!perms.portal_broadcast;
            } else {
                ['perm_staff', 'perm_categories', 'perm_orders', 'perm_delivery_charges', 'perm_customers', 'perm_products', 'perm_invoices'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.checked = true;
                });
                ['perm_portal_links', 'perm_portal_policies', 'perm_portal_ohc', 'perm_portal_broadcast'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.checked = true;
                });
            }

            const roleTitle = s.role === 'manager' ? 'Manager' : (s.role === 'admin' ? 'Author' : 'Staff');
            document.getElementById('staff-modal-title').innerText = 'Edit ' + roleTitle + ' #' + s.id;
            document.getElementById('staff-submit-btn').innerText = 'Update ' + roleTitle + ' Account';
            document.getElementById('staff-modal').style.display = 'flex';
        }

        async function handleStaffSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('staff-id').value;
            const action = id ? 'edit_staff' : 'add_staff';
            const payload = {
                id: id,
                username: document.getElementById('staff-username').value,
                name: document.getElementById('staff-name').value,
                phone: document.getElementById('staff-phone').value,
                role: document.getElementById('staff-role').value,
                status: document.getElementById('staff-status').value,
                password: document.getElementById('staff-password').value,
                permissions: {
                    staff: document.getElementById('perm_staff').checked,
                    categories: document.getElementById('perm_categories').checked,
                    orders: document.getElementById('perm_orders').checked,
                    delivery_charges: document.getElementById('perm_delivery_charges').checked,
                    customers: document.getElementById('perm_customers').checked,
                    products: document.getElementById('perm_products').checked,
                    invoices: document.getElementById('perm_invoices').checked,
                    portal_links: document.getElementById('perm_portal_links').checked,
                    portal_policies: document.getElementById('perm_portal_policies').checked,
                    portal_ohc: document.getElementById('perm_portal_ohc').checked,
                    portal_broadcast: document.getElementById('perm_portal_broadcast').checked
                }
            };

            const res = await fetch('api/admin.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('staff-modal');
                setTimeout(() => window.location.reload(), 800);
            } else {
                showToast(data.message, false);
            }
        }

        async function toggleStaffStatus(id) {
            const res = await fetch('api/admin.php?action=toggle_staff_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteUser(id, name) {
            const ok = await showCustomConfirm({
                title: "Delete User Account?",
                message: `Are you sure you want to permanently delete user <strong>'${name}'</strong> (#${id})?<br><span style="color: #ef4444; font-size: 13px;">This account and portal access will be removed immediately.</span>`,
                confirmText: "Yes, Delete User",
                icon: "fa-trash-can",
                confirmBtnBg: "#dc2626"
            });
            if (!ok) return;

            try {
                const res = await fetch('api/admin.php?action=delete_user', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'User deleted successfully!');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to delete user', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        async function deleteStaff(id, name) {
            const ok = await showCustomConfirm({
                title: "Delete Staff Member?",
                message: `Are you sure you want to delete staff account <strong>'${name}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">This account will permanently lose portal access.</span>`,
                confirmText: "Yes, Delete",
                icon: "fa-user-xmark"
            });
            if (!ok) return;

            const res = await fetch('api/admin.php?action=delete_staff', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        // --- CATEGORY ACTIONS ---
        function autoSlug(val) {
            document.getElementById('cat-slug').value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        }

        function openCategoryModal() {
            document.getElementById('cat-id').value = '';
            document.getElementById('cat-name').value = '';
            document.getElementById('cat-name-bn').value = '';
            document.getElementById('cat-slug').value = '';
            document.getElementById('cat-img').value = 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300';
            document.getElementById('cat-modal-title').innerText = 'Add New Category';
            document.getElementById('category-modal').style.display = 'flex';
        }

        function openEditCategoryModal(c) {
            document.getElementById('cat-id').value = c.id;
            document.getElementById('cat-name').value = c.name;
            document.getElementById('cat-name-bn').value = c.name_bn;
            document.getElementById('cat-slug').value = c.slug;
            document.getElementById('cat-img').value = c.img;
            document.getElementById('cat-modal-title').innerText = 'Edit Category #' + c.id;
            document.getElementById('category-modal').style.display = 'flex';
        }

        async function handleCategorySubmit(e) {
            e.preventDefault();
            const id = document.getElementById('cat-id').value;
            const action = id ? 'edit_category' : 'add_category';
            const payload = {
                id: id,
                name: document.getElementById('cat-name').value,
                name_bn: document.getElementById('cat-name-bn').value,
                slug: document.getElementById('cat-slug').value,
                img: document.getElementById('cat-img').value
            };

            const res = await fetch('api/admin.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('category-modal');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function uploadCategoryFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'categories');

            showToast('Uploading category image...');
            const res = await fetch('api/upload-file.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('cat-img').value = data.file_url;
                showToast('Category image uploaded successfully!');
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteCategory(id, name) {
            const ok = await showCustomConfirm({
                title: "Delete Category?",
                message: `Delete category <strong>'${name}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">All attached products will be unassigned.</span>`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/admin.php?action=delete_category', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        // --- PRODUCT ACTIONS ---
        function filterProductsTable() {
            const query = (document.getElementById('prod-search-input')?.value || '').toLowerCase().trim();
            const catId = document.getElementById('prod-cat-filter')?.value || '';
            const rows = document.querySelectorAll('.product-table-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const title = (row.getAttribute('data-title') || '').toLowerCase();
                const titleBn = (row.getAttribute('data-title-bn') || '').toLowerCase();
                const code = (row.getAttribute('data-code') || '').toLowerCase();
                const rowCatId = row.getAttribute('data-cat-id') || '';
                const rowCatName = (row.getAttribute('data-cat-name') || '').toLowerCase();

                const matchesQuery = !query || title.includes(query) || titleBn.includes(query) || code.includes(query) || rowCatName.includes(query);
                const matchesCat = !catId || rowCatId === catId;

                if (matchesQuery && matchesCat) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noMatchRow = document.getElementById('prod-no-match-row');
            if (noMatchRow) {
                noMatchRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        function openProductModal() {
            document.getElementById('prod-id').value = '';
            document.getElementById('prod-title').value = '';
            document.getElementById('prod-title-bn').value = '';
            document.getElementById('prod-desc').value = '';
            document.getElementById('prod-desc-bn').value = '';
            document.getElementById('prod-poriman').value = '1 kg';
            document.getElementById('prod-poriman-bn').value = '১ কেজি';
            document.getElementById('prod-image').value = 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=500';
            document.getElementById('prod-modal-title').innerText = 'Add New Product';
            document.getElementById('product-modal').style.display = 'flex';
        }

        function openEditProductModal(p) {
            document.getElementById('prod-id').value = p.id;
            document.getElementById('prod-category').value = p.category_id;
            document.getElementById('prod-title').value = p.title;
            document.getElementById('prod-title-bn').value = p.title_bn;
            document.getElementById('prod-desc').value = p.description || '';
            document.getElementById('prod-desc-bn').value = p.description_bn || '';
            document.getElementById('prod-poriman').value = p.poriman;
            document.getElementById('prod-poriman-bn').value = p.poriman_bn;
            document.getElementById('prod-image').value = p.image;
            document.getElementById('prod-modal-title').innerText = 'Edit Product #' + p.id;
            document.getElementById('product-modal').style.display = 'flex';
        }

        async function uploadProductFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'products');

            showToast('Uploading image...');
            const res = await fetch('api/upload-file.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('prod-image').value = data.file_url;
                showToast('Image uploaded successfully!');
            } else {
                showToast(data.message, false);
            }
        }

        async function handleProductSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('prod-id').value;
            const action = id ? 'edit_product' : 'add_product';
            const payload = {
                id: id,
                category_id: document.getElementById('prod-category').value,
                title: document.getElementById('prod-title').value,
                title_bn: document.getElementById('prod-title-bn').value,
                description: document.getElementById('prod-desc').value,
                description_bn: document.getElementById('prod-desc-bn').value,
                poriman: document.getElementById('prod-poriman').value,
                poriman_bn: document.getElementById('prod-poriman-bn').value,
                image: document.getElementById('prod-image').value
            };

            const res = await fetch('api/manager.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('product-modal');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteProduct(id, title) {
            const ok = await showCustomConfirm({
                title: "Delete Product?",
                message: `Are you sure you want to delete product <strong>'${title}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">It will be removed from the online customer catalog.</span>`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/manager.php?action=delete_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function toggleProductStatus(id) {
            try {
                const res = await fetch('api/manager.php?action=toggle_product_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    showToast(data.message || 'Failed to update status', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        // --- ORDER ACTIONS ---
        async function updatePaymentStatus(orderId, paymentStatus, selectEl) {
            try {
                const res = await fetch('api/update-order-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_payment_status', order_id: orderId, payment_status: paymentStatus })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message);
                    if (window.allOrdersMap && window.allOrdersMap[orderId]) {
                        window.allOrdersMap[orderId].payment_status = paymentStatus;
                    }
                    if (selectEl) {
                        if (paymentStatus === 'paid') {
                            selectEl.style.background = '#f0fdf4';
                            selectEl.style.color = '#15803d';
                            selectEl.style.borderColor = '#86efac';
                        } else if (paymentStatus === 'refunded') {
                            selectEl.style.background = '#fef2f2';
                            selectEl.style.color = '#b91c1c';
                            selectEl.style.borderColor = '#fca5a5';
                        } else {
                            selectEl.style.background = '#fffbeb';
                            selectEl.style.color = '#b45309';
                            selectEl.style.borderColor = '#fde68a';
                        }
                    }
                } else {
                    showToast(data.message || 'Failed to update payment status', false);
                }
            } catch (err) {
                showToast('Network error while updating payment status', false);
            }
        }

        async function updateOrderStatus(orderId, status) {
            const res = await fetch('api/update-order-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: status })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
            } else {
                showToast(data.message, false);
            }
        }

        async function assignRider(orderId, riderId) {
            const res = await fetch('api/admin.php?action=assign_rider', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, rider_id: riderId })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteOrder(id, num) {
            const ok = await showCustomConfirm({
                title: "Delete Order?",
                message: `Are you sure you want to delete order <strong>${num}</strong>?<br><span style="color: #ef4444; font-size: 13px;">This permanent action cannot be undone.</span>`,
                confirmText: "Yes, Delete Order",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/manager.php?action=delete_order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        function viewOrderModal(orderData) {
            const o = (typeof orderData === 'object' && orderData !== null) ? orderData : (window.allOrdersMap && window.allOrdersMap[orderData] ? window.allOrdersMap[orderData] : null);
            if (!o) return;
            document.getElementById('modal-order-number').innerText = 'Invoice ' + (o.order_number || ('#' + o.id));

            const pMethod = (o.payment_method || 'cod').toLowerCase();
            const pStatus = (o.payment_status || 'pending').toLowerCase();
            let paymentBadge = '<span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 11.5px;"><i class="fa-solid fa-hand-holding-dollar"></i> Cash on Delivery (COD)</span>';
            if (pMethod === 'bkash') {
                paymentBadge = '<span style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 11.5px;"><i class="fa-solid fa-mobile-screen"></i> bKash (Personal)</span>';
            } else if (pMethod === 'nagad') {
                paymentBadge = '<span style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 3px 8px; border-radius: 6px; font-weight: 800; font-size: 11.5px;"><i class="fa-solid fa-mobile-screen"></i> Nagad (Personal)</span>';
            }

            const pStatusBadge = pStatus === 'paid' 
                ? '<span style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;">✅ Paid</span>' 
                : '<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px;">⏳ Pending</span>';

            let html = `
                <div style="background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13.5px;">
                        <div><strong>Customer:</strong> ${o.customer_name || 'N/A'} (${o.customer_phone || 'N/A'})</div>
                        <div><strong>Status:</strong> <span style="text-transform: capitalize; font-weight: bold; color: #7c3aed;">${o.status || 'Pending'}</span></div>
                        <div><strong>Address:</strong> ${o.customer_address || o.police_station || 'N/A'}</div>
                        <div><strong>Total Amount:</strong> <strong style="color: #16a34a; font-size: 15px;">৳${parseFloat(o.total_amount || 0).toFixed(2)}</strong></div>
                        <div style="grid-column: span 2; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding-top: 10px; border-top: 1px dashed #cbd5e1; margin-top: 4px;">
                            <div><strong>Payment Method:</strong> ${paymentBadge}</div>
                            <div><strong>Payment Status:</strong> ${pStatusBadge}</div>
                            ${o.payment_sender_phone ? `<div><strong>Sender Mobile:</strong> <code style="font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 6px; border-radius: 4px;">${o.payment_sender_phone}</code></div>` : ''}
                            ${o.transaction_id ? `<div><strong>TrxID:</strong> <code style="background: #fdf2f8; color: #be185d; padding: 2px 6px; border-radius: 4px; font-weight: 800; border: 1px solid #fbcfe8;">${o.transaction_id}</code></div>` : ''}
                        </div>
                    </div>
                </div>
            `;

            if (o.custom_image_urls) {
                try {
                    const imgs = typeof o.custom_image_urls === 'string' ? JSON.parse(o.custom_image_urls) : o.custom_image_urls;
                    if (Array.isArray(imgs) && imgs.length > 0) {
                        html += `<h4 style="margin: 10px 0 6px 0;">Uploaded Grocery List Photos:</h4><div style="display: flex; gap: 10px; flex-wrap: wrap;">`;
                        imgs.forEach(src => {
                            html += `<a href="${src}" target="_blank"><img src="${src}" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc;"></a>`;
                        });
                        html += `</div>`;
                    }
                } catch(e) {}
            }

            if (o.html_content) {
                html += `<div style="margin-top: 15px;">${o.html_content}</div>`;
            }

            document.getElementById('modal-order-content').innerHTML = html;
            document.getElementById('order-modal').style.display = 'flex';
        }

        // --- ORDER STATUS TABS FILTER ---
        function filterOrdersByStatus(status, btn) {
            const rows = document.querySelectorAll('.order-table-row');
            let visibleCount = 0;
            rows.forEach(r => {
                const rowStatus = (r.getAttribute('data-order-status') || '').toLowerCase();
                let show = false;
                if (status === 'all') {
                    show = true;
                } else if (status === 'pending') {
                    show = (rowStatus === 'pending');
                } else if (status === 'received') {
                    show = ['processing', 'customer_confirmed', 'received', 'delivering', 'confirmed'].includes(rowStatus);
                } else if (status === 'delivered') {
                    show = ['completed', 'delivered'].includes(rowStatus);
                }
                r.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            let noRow = document.getElementById('no-orders-filter-row');
            if (visibleCount === 0) {
                if (!noRow) {
                    const tbody = document.querySelector('#orders-table-tbody');
                    if (tbody) {
                        noRow = document.createElement('tr');
                        noRow.id = 'no-orders-filter-row';
                        noRow.innerHTML = `<td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px; font-size: 14px;">No ${status} orders found.</td>`;
                        tbody.appendChild(noRow);
                    }
                } else {
                    noRow.style.display = '';
                    noRow.innerHTML = `<td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px; font-size: 14px;">No ${status} orders found.</td>`;
                }
            } else if (noRow) {
                noRow.style.display = 'none';
            }

            const btns = document.querySelectorAll('.order-status-btn');
            btns.forEach(b => {
                b.classList.remove('active');
                b.style.background = '#f8fafc';
                b.style.color = '#334155';
                b.style.borderColor = '#e2e8f0';
                b.style.boxShadow = 'none';
                const icon = b.querySelector('i');
                if (icon) {
                    if (b.id === 'order-btn-all') icon.style.color = '#2563eb';
                    if (b.id === 'order-btn-pending') icon.style.color = '#d97706';
                    if (b.id === 'order-btn-received') icon.style.color = '#6366f1';
                    if (b.id === 'order-btn-delivered') icon.style.color = '#16a34a';
                }
                const badge = b.querySelector('.subtab-badge');
                if (badge) {
                    badge.style.background = '#e2e8f0';
                    badge.style.color = '#475569';
                }
            });

            if (btn) {
                btn.classList.add('active');
                let activeColor = '#2563eb';
                if (status === 'pending') activeColor = '#d97706';
                if (status === 'received') activeColor = '#6366f1';
                if (status === 'delivered') activeColor = '#16a34a';

                btn.style.background = activeColor;
                btn.style.color = 'white';
                btn.style.borderColor = activeColor;
                btn.style.boxShadow = `0 6px 18px ${activeColor}40`;
                const icon = btn.querySelector('i');
                if (icon) icon.style.color = 'white';
                const badge = btn.querySelector('.subtab-badge');
                if (badge) {
                    badge.style.background = 'rgba(255,255,255,0.25)';
                    badge.style.color = 'white';
                }
            }

            localStorage.setItem('admin_order_status_filter', status);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'order');
            url.searchParams.set('order_status', status);
            window.history.replaceState({}, '', url.toString());
        }

        // --- DELIVERY RULES & PORTAL SETTINGS ---
        function updateRuleDynamicFields() {
            const basedOn = document.getElementById('del-rule-based-on').value;
            const operator = document.getElementById('del-rule-operator').value;
            const minLabel = document.getElementById('del-rule-min-label');
            const minInput = document.getElementById('del-rule-min');
            const maxWrapper = document.getElementById('del-rule-max-wrapper');
            const maxLabel = document.getElementById('del-rule-max-label');
            const maxInput = document.getElementById('del-rule-max');

            if (operator === 'between') {
                maxWrapper.style.display = 'block';
                if (basedOn === 'order_amount') {
                    minLabel.innerText = 'Order Amount / Min (৳)';
                    minInput.placeholder = '500';
                    maxLabel.innerText = 'Order Amount / Max (৳)';
                    maxInput.placeholder = '1000';
                } else if (basedOn === 'weight') {
                    minLabel.innerText = 'Weight / Min (KG)';
                    minInput.placeholder = '1';
                    maxLabel.innerText = 'Weight / Max (KG)';
                    maxInput.placeholder = '5';
                } else if (basedOn === 'distance') {
                    minLabel.innerText = 'Distance / Min (KM)';
                    minInput.placeholder = '1';
                    maxLabel.innerText = 'Distance / Max (KM)';
                    maxInput.placeholder = '10';
                } else if (basedOn === 'zone') {
                    minLabel.innerText = 'Min Zone Code / ID';
                    minInput.placeholder = '1';
                    maxLabel.innerText = 'Max Zone Code / ID';
                    maxInput.placeholder = '5';
                } else if (basedOn === 'category') {
                    minLabel.innerText = 'Min Category ID / Spend';
                    minInput.placeholder = '1';
                    maxLabel.innerText = 'Max Category ID / Spend';
                    maxInput.placeholder = '10';
                } else if (basedOn === 'amount_weight') {
                    minLabel.innerText = 'Min Amount / Weight Value';
                    minInput.placeholder = '500';
                    maxLabel.innerText = 'Max Amount / Weight Value';
                    maxInput.placeholder = '1000';
                } else if (basedOn === 'distance_weight') {
                    minLabel.innerText = 'Min Distance / Weight Value';
                    minInput.placeholder = '5';
                    maxLabel.innerText = 'Max Distance / Weight Value';
                    maxInput.placeholder = '15';
                } else {
                    minLabel.innerText = 'Min Value';
                    minInput.placeholder = '0';
                    maxLabel.innerText = 'Max Value';
                    maxInput.placeholder = '100';
                }
            } else {
                maxWrapper.style.display = 'none';
                if (basedOn === 'order_amount') {
                    minLabel.innerText = 'Order Amount / Min (৳)';
                    minInput.placeholder = '1000';
                } else if (basedOn === 'weight') {
                    minLabel.innerText = 'Weight / Min (KG)';
                    minInput.placeholder = '5';
                } else if (basedOn === 'distance') {
                    minLabel.innerText = 'Distance / Min (KM)';
                    minInput.placeholder = '5';
                } else if (basedOn === 'zone') {
                    minLabel.innerText = 'Delivery Zone / Area ID';
                    minInput.placeholder = 'Zone ID or Code';
                } else if (basedOn === 'category') {
                    minLabel.innerText = 'Product Category ID';
                    minInput.placeholder = 'Category ID';
                } else if (basedOn === 'amount_weight') {
                    minLabel.innerText = 'Amount + Weight Criteria';
                    minInput.placeholder = '500';
                } else if (basedOn === 'distance_weight') {
                    minLabel.innerText = 'Distance + Weight Criteria';
                    minInput.placeholder = '5';
                } else {
                    minLabel.innerText = 'Custom Rule Value';
                    minInput.placeholder = 'Value';
                }
            }
        }

        function updateCalcMethodFields() {
            const calcMethod = document.getElementById('del-rule-calc-method').value;
            const singleWrapper = document.getElementById('del-rule-single-wrapper');
            const dualWrapper = document.getElementById('del-rule-dual-wrapper');
            const chargeLabel = document.getElementById('del-rule-charge-label');
            const chargeInput = document.getElementById('del-rule-charge');
            const perUnitLabel = document.getElementById('del-rule-per-unit-label');
            const baseChargeInput = document.getElementById('del-rule-base-charge');
            const perUnitInput = document.getElementById('del-rule-per-unit-rate');

            if (calcMethod === 'base_per_kg' || calcMethod === 'base_per_km') {
                singleWrapper.style.display = 'none';
                dualWrapper.style.display = 'grid';
                chargeInput.required = false;
                baseChargeInput.required = true;
                perUnitInput.required = true;

                if (calcMethod === 'base_per_kg') {
                    perUnitLabel.innerText = 'Rate Per KG (৳)';
                    if (!perUnitInput.value || perUnitInput.value === '0.00' || perUnitInput.value === '0') perUnitInput.value = '10.00';
                    if (!baseChargeInput.value || baseChargeInput.value === '0.00' || baseChargeInput.value === '0') baseChargeInput.value = '40.00';
                } else {
                    perUnitLabel.innerText = 'Rate Per KM (৳)';
                    if (!perUnitInput.value || perUnitInput.value === '0.00' || perUnitInput.value === '0') perUnitInput.value = '15.00';
                    if (!baseChargeInput.value || baseChargeInput.value === '0.00' || baseChargeInput.value === '0') baseChargeInput.value = '40.00';
                }
            } else {
                singleWrapper.style.display = 'block';
                dualWrapper.style.display = 'none';
                chargeInput.required = true;
                baseChargeInput.required = false;
                perUnitInput.required = false;

                if (calcMethod === 'free') {
                    chargeLabel.innerText = 'Charge Amount (৳)';
                    chargeInput.value = '0.00';
                    chargeInput.disabled = true;
                } else if (calcMethod === 'fixed') {
                    chargeLabel.innerText = 'Fixed Amount (৳)';
                    chargeInput.disabled = false;
                    if (!chargeInput.value || chargeInput.value === '0.00' || chargeInput.value === '0') chargeInput.value = '60.00';
                } else if (calcMethod === 'percentage') {
                    chargeLabel.innerText = 'Percentage of Order (%)';
                    chargeInput.disabled = false;
                    if (!chargeInput.value || chargeInput.value === '0.00') chargeInput.value = '5.00';
                } else if (calcMethod === 'per_kg') {
                    chargeLabel.innerText = 'Rate Per KG (৳)';
                    chargeInput.disabled = false;
                    if (!chargeInput.value || chargeInput.value === '0.00') chargeInput.value = '15.00';
                } else if (calcMethod === 'per_km') {
                    chargeLabel.innerText = 'Rate Per KM (৳)';
                    chargeInput.disabled = false;
                    if (!chargeInput.value || chargeInput.value === '0.00') chargeInput.value = '20.00';
                } else if (calcMethod === 'additional_charge') {
                    chargeLabel.innerText = 'Additional Charge (৳)';
                    chargeInput.disabled = false;
                    if (!chargeInput.value || chargeInput.value === '0.00') chargeInput.value = '30.00';
                }
            }
        }

        function openCreateDeliveryRuleModal(defaultTarget = 'custom_list') {
            document.getElementById('del-rule-id').value = '';
            document.getElementById('del-rule-name').value = '';
            document.getElementById('del-rule-target').value = defaultTarget;
            document.getElementById('del-rule-based-on').value = 'order_amount';
            document.getElementById('del-rule-operator').value = 'greater_than_or_equal';
            document.getElementById('del-rule-min').value = '';
            document.getElementById('del-rule-max').value = '';
            document.getElementById('del-rule-calc-method').value = 'fixed';
            document.getElementById('del-rule-charge').value = '60.00';
            document.getElementById('del-rule-charge').disabled = false;
            document.getElementById('del-rule-base-charge').value = '40.00';
            document.getElementById('del-rule-per-unit-rate').value = '10.00';
            document.getElementById('del-rule-priority').value = '1';
            document.getElementById('del-rule-active-checkbox').checked = true;
            document.getElementById('del-rule-min-limit').value = '';
            document.getElementById('del-rule-max-limit').value = '';
            document.getElementById('del-rule-effective-from').value = '';
            document.getElementById('del-rule-effective-to').value = '';
            
            updateRuleDynamicFields();
            updateCalcMethodFields();

            document.getElementById('delivery-rule-modal-title').innerHTML = '<i class="fa-solid fa-sliders" style="color: #2563eb;"></i> Create Delivery Charge Rule';
            document.getElementById('delivery-rule-modal').style.display = 'flex';
        }

        function editDeliveryRule(r) {
            document.getElementById('del-rule-id').value = r.id;
            document.getElementById('del-rule-name').value = r.rule_name || '';
            document.getElementById('del-rule-target').value = r.target_type || 'custom_list';
            document.getElementById('del-rule-based-on').value = r.based_on || 'order_amount';
            document.getElementById('del-rule-operator').value = r.operator || 'greater_than_or_equal';
            document.getElementById('del-rule-min').value = r.min_value !== null && r.min_value !== undefined ? r.min_value : '';
            document.getElementById('del-rule-max').value = r.max_value !== null && r.max_value !== undefined ? r.max_value : '';
            document.getElementById('del-rule-calc-method').value = r.calculation_method || 'fixed';
            document.getElementById('del-rule-charge').value = (r.fixed_amount !== null && r.fixed_amount !== undefined) ? r.fixed_amount : (r.charge || '60.00');
            document.getElementById('del-rule-base-charge').value = (r.base_charge !== null && r.base_charge !== undefined && r.base_charge != 0) ? r.base_charge : ((r.fixed_amount !== null && r.fixed_amount !== undefined) ? r.fixed_amount : '40.00');
            document.getElementById('del-rule-per-unit-rate').value = (r.per_unit_rate !== null && r.per_unit_rate !== undefined && r.per_unit_rate != 0) ? r.per_unit_rate : '10.00';
            document.getElementById('del-rule-priority').value = r.priority || '1';
            document.getElementById('del-rule-active-checkbox').checked = (parseInt(r.is_active) === 1);
            document.getElementById('del-rule-min-limit').value = r.min_charge_limit || '';
            document.getElementById('del-rule-max-limit').value = r.max_charge_limit || '';
            document.getElementById('del-rule-effective-from').value = r.effective_from || '';
            document.getElementById('del-rule-effective-to').value = r.effective_to || '';

            updateRuleDynamicFields();
            updateCalcMethodFields();

            document.getElementById('delivery-rule-modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: #2563eb;"></i> Edit Delivery Charge Rule #' + r.id;
            document.getElementById('delivery-rule-modal').style.display = 'flex';
        }

        async function handleDeliveryRuleSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('del-rule-id').value;
            const action = id ? 'edit_delivery_rule' : 'add_delivery_rule';
            const calcMethod = document.getElementById('del-rule-calc-method').value;
            const isDual = (calcMethod === 'base_per_kg' || calcMethod === 'base_per_km');
            const fixedAmt = isDual ? document.getElementById('del-rule-base-charge').value : document.getElementById('del-rule-charge').value;

            const payload = {
                id: id,
                rule_name: document.getElementById('del-rule-name').value,
                target_type: document.getElementById('del-rule-target').value,
                based_on: document.getElementById('del-rule-based-on').value,
                operator: document.getElementById('del-rule-operator').value,
                min_value: document.getElementById('del-rule-min').value,
                max_value: document.getElementById('del-rule-max').value,
                calculation_method: calcMethod,
                fixed_amount: fixedAmt,
                base_charge: document.getElementById('del-rule-base-charge').value,
                per_unit_rate: document.getElementById('del-rule-per-unit-rate').value,
                priority: document.getElementById('del-rule-priority').value,
                is_active: document.getElementById('del-rule-active-checkbox').checked ? 1 : 0,
                min_charge_limit: document.getElementById('del-rule-min-limit').value,
                max_charge_limit: document.getElementById('del-rule-max-limit').value,
                effective_from: document.getElementById('del-rule-effective-from').value,
                effective_to: document.getElementById('del-rule-effective-to').value
            };

            const res = await fetch('api/admin.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('delivery-rule-modal');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function toggleDeliveryRule(id) {
            const res = await fetch('api/admin.php?action=toggle_delivery_rule', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function duplicateDeliveryRule(id) {
            const res = await fetch('api/admin.php?action=duplicate_delivery_rule', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteDeliveryRule(id) {
            const ok = await showCustomConfirm({
                title: "Delete Delivery Rule?",
                message: `Are you sure you want to delete delivery rule <strong>#${id}</strong>?`,
                confirmText: "Yes, Delete Rule",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/admin.php?action=delete_delivery_rule', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message, false);
            }
        }

        // --- DC & DISCOUNT SUB-TAB & DISCOUNT MANAGER ---
        function switchDcSubtab(subtab, btn) {
            const panels = document.querySelectorAll('.dc-subtab-panel');
            panels.forEach(p => p.style.display = 'none');

            const targetPanel = document.getElementById('dc-panel-' + subtab);
            if (targetPanel) {
                targetPanel.style.display = 'block';
            }

            const btns = document.querySelectorAll('.dc-subtab-btn');
            btns.forEach(b => {
                b.classList.remove('active');
                b.style.background = '#f8fafc';
                b.style.color = '#334155';
                b.style.borderColor = '#e2e8f0';
                b.style.boxShadow = 'none';
                const icon = b.querySelector('i');
                if (icon) {
                    if (b.id === 'dc-subtab-btn-delivery') icon.style.color = '#6366f1';
                    if (b.id === 'dc-subtab-btn-discount') icon.style.color = '#f59e0b';
                }
                const badge = b.querySelector('.subtab-badge');
                if (badge) {
                    badge.style.background = '#e2e8f0';
                    badge.style.color = '#475569';
                }
            });

            if (btn) {
                btn.classList.add('active');
                btn.style.background = '#6366f1';
                btn.style.color = 'white';
                btn.style.borderColor = '#6366f1';
                btn.style.boxShadow = '0 6px 18px rgba(99, 102, 241, 0.28)';
                const icon = btn.querySelector('i');
                if (icon) icon.style.color = 'white';
                const badge = btn.querySelector('.subtab-badge');
                if (badge) {
                    badge.style.background = 'rgba(255,255,255,0.25)';
                    badge.style.color = 'white';
                }
            }

            localStorage.setItem('admin_dc_subtab', subtab);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'delivery_charge');
            url.searchParams.set('dc_subtab', subtab);
            window.history.replaceState({}, '', url.toString());
        }

        function filterDiscComboProducts() {
            const query = (document.getElementById('disc-combo-search')?.value || '').toLowerCase().trim();
            document.querySelectorAll('.disc-combo-item').forEach(item => {
                const search = (item.getAttribute('data-search') || '').toLowerCase();
                item.style.display = (!query || search.includes(query)) ? 'flex' : 'none';
            });
        }

        function updateDiscComboCount() {
            const checked = document.querySelectorAll('.disc-combo-check:checked');
            const countEl = document.getElementById('disc-combo-selected-count');
            if (countEl) {
                countEl.innerText = `${checked.length} items selected`;
            }
        }

        function handleRewardLimitModeChange() {
            const mode = document.getElementById('disc-reward-limit-mode')?.value || 'unlimited';
            const wrap = document.getElementById('disc-custom-limit-wrap');
            if (wrap) wrap.style.display = (mode === 'custom') ? 'block' : 'none';
        }

        function handleRewardTypeChange() {
            const rType = document.getElementById('disc-reward-type').value;
            const pWrap = document.getElementById('disc-reward-product-wrapper');
            const qWrap = document.getElementById('disc-reward-qty-wrapper');
            const lCard = document.getElementById('disc-reward-limit-card');
            if (rType === 'free_product') {
                if (pWrap) pWrap.style.display = 'block';
                if (qWrap) qWrap.style.display = 'block';
                if (lCard) lCard.style.display = 'block';
            } else if (rType === 'free_delivery') {
                if (pWrap) pWrap.style.display = 'none';
                if (qWrap) qWrap.style.display = 'none';
                if (lCard) lCard.style.display = 'none';
            } else {
                if (pWrap) pWrap.style.display = 'none';
                if (qWrap) qWrap.style.display = 'none';
                if (lCard) lCard.style.display = 'none';
            }
        }

        function handleDiscountTypeChange() {
            const type = document.getElementById('disc-type').value;
            const valLabel = document.getElementById('disc-val-label');
            const valInput = document.getElementById('disc-value');
            const valWrapper = document.getElementById('disc-val-wrapper');
            const rewardSection = document.getElementById('disc-reward-section');
            const rewardTypeSelect = document.getElementById('disc-reward-type');
            const targetTypeSelect = document.getElementById('disc-target-type');

            if (type === 'combo_bundle') {
                targetTypeSelect.value = 'multi_products';
                handleTargetTypeChange();
                if (rewardSection) rewardSection.style.display = 'block';
                if (rewardTypeSelect) rewardTypeSelect.value = 'free_product';
                handleRewardTypeChange();
                valLabel.innerText = 'Additional Cash Discount (Optional ৳)';
                valInput.placeholder = '0';
            } else if (type === 'free_gift') {
                if (rewardSection) rewardSection.style.display = 'block';
                if (rewardTypeSelect) rewardTypeSelect.value = 'free_product';
                handleRewardTypeChange();
                valLabel.innerText = 'Additional Discount (৳)';
                valInput.placeholder = '0';
            } else if (type === 'free_delivery') {
                if (rewardSection) rewardSection.style.display = 'block';
                if (rewardTypeSelect) rewardTypeSelect.value = 'free_delivery';
                handleRewardTypeChange();
                valLabel.innerText = 'Discount Amount (৳)';
                valInput.placeholder = '0';
            } else if (type === 'percentage') {
                if (rewardSection) rewardSection.style.display = 'none';
                valLabel.innerText = 'Discount Percentage (%)';
                valInput.placeholder = '10';
                valWrapper.style.display = 'block';
            } else if (type === 'fixed_amount') {
                if (rewardSection) rewardSection.style.display = 'none';
                valLabel.innerText = 'Discount Amount (৳)';
                valInput.placeholder = '50';
                valWrapper.style.display = 'block';
            } else if (type === 'bogo') {
                if (rewardSection) rewardSection.style.display = 'block';
                if (rewardTypeSelect) rewardTypeSelect.value = 'free_product';
                handleRewardTypeChange();
                valLabel.innerText = 'Free Item Discount (%)';
                valInput.placeholder = '100';
                valInput.value = '100';
                valWrapper.style.display = 'block';
            } else if (type === 'cart_spend') {
                if (rewardSection) rewardSection.style.display = 'none';
                valLabel.innerText = 'Discount Amount (৳)';
                valInput.placeholder = '100';
                valWrapper.style.display = 'block';
            } else if (type === 'coupon') {
                if (rewardSection) rewardSection.style.display = 'none';
                valLabel.innerText = 'Coupon Discount (৳ or %)';
                valInput.placeholder = '50';
                valWrapper.style.display = 'block';
            }
        }

        function handleTargetTypeChange() {
            const targetType = document.getElementById('disc-target-type').value;
            const prodWrapper = document.getElementById('disc-product-wrapper');
            const catWrapper = document.getElementById('disc-category-wrapper');
            const multiWrapper = document.getElementById('disc-multi-products-wrapper');

            if (prodWrapper) prodWrapper.style.display = (targetType === 'single_product') ? 'block' : 'none';
            if (catWrapper) catWrapper.style.display = (targetType === 'category') ? 'block' : 'none';
            if (multiWrapper) multiWrapper.style.display = (targetType === 'multi_products') ? 'block' : 'none';
        }

        function openAddDiscountModal() {
            document.getElementById('disc-rule-id').value = '';
            document.getElementById('disc-title').value = '';
            document.getElementById('disc-title-bn').value = '';
            document.getElementById('disc-badge-text').value = '';
            document.getElementById('disc-type').value = 'combo_bundle';
            document.getElementById('disc-target-type').value = 'multi_products';
            document.getElementById('disc-target-product').value = '';
            document.getElementById('disc-target-category').value = '';
            document.getElementById('disc-reward-type').value = 'free_product';
            document.getElementById('disc-reward-product').value = '';
            document.getElementById('disc-reward-qty').value = '1';
            
            const limitMode = document.getElementById('disc-reward-limit-mode');
            if (limitMode) limitMode.value = 'unlimited';
            const customLimit = document.getElementById('disc-custom-max-limit');
            if (customLimit) customLimit.value = '1';
            handleRewardLimitModeChange();

            document.getElementById('disc-value').value = '0';
            document.getElementById('disc-min-cart').value = '0';
            document.getElementById('disc-min-qty').value = '1';
            document.getElementById('disc-customer-target').value = 'all';
            document.getElementById('disc-payment-method-target').value = 'all';
            document.getElementById('disc-coupon-code').value = '';
            document.getElementById('disc-priority').value = '1';
            document.getElementById('disc-active-checkbox').checked = true;
            document.getElementById('disc-start-date').value = '';
            document.getElementById('disc-end-date').value = '';

            // Uncheck all combo products and reset quantities
            document.querySelectorAll('.disc-combo-check').forEach(cb => cb.checked = false);
            document.querySelectorAll('.disc-combo-item-qty').forEach(qi => qi.value = '1');
            updateDiscComboCount();

            handleDiscountTypeChange();
            handleTargetTypeChange();
            handleRewardTypeChange();

            document.getElementById('discount-modal-title').innerHTML = '<i class="fa-solid fa-tags" style="color: #f59e0b;"></i> Create Discount / Offer Rule';
            document.getElementById('discount-modal').style.display = 'flex';
        }

        function openEditDiscountModal(r) {
            document.getElementById('disc-rule-id').value = r.id;
            document.getElementById('disc-title').value = r.title || '';
            document.getElementById('disc-title-bn').value = r.title_bn || '';
            document.getElementById('disc-badge-text').value = r.badge_text || '';
            document.getElementById('disc-type').value = r.discount_type || 'fixed_amount';
            document.getElementById('disc-target-type').value = r.target_type || 'entire_cart';
            
            if (r.target_type === 'single_product') {
                document.getElementById('disc-target-product').value = r.target_id || '';
            } else if (r.target_type === 'category') {
                document.getElementById('disc-target-category').value = r.target_id || '';
            }

            // Set Combo checked products and quantities
            document.querySelectorAll('.disc-combo-check').forEach(cb => cb.checked = false);
            document.querySelectorAll('.disc-combo-item-qty').forEach(qi => qi.value = '1');
            if (r.target_ids_json) {
                try {
                    const ids = typeof r.target_ids_json === 'string' ? JSON.parse(r.target_ids_json) : r.target_ids_json;
                    if (Array.isArray(ids)) {
                        ids.forEach(it => {
                            const pId = typeof it === 'object' ? it.id : it;
                            const pQty = typeof it === 'object' ? (it.qty || 1) : 1;
                            const cb = document.querySelector(`.disc-combo-check[value="${pId}"]`);
                            if (cb) cb.checked = true;
                            const qi = document.querySelector(`.disc-combo-item-qty[data-prod-id="${pId}"]`);
                            if (qi) qi.value = pQty;
                        });
                    }
                } catch(e) {}
            }
            updateDiscComboCount();

            // Set Reward fields
            document.getElementById('disc-reward-type').value = r.reward_type || 'free_product';
            document.getElementById('disc-reward-product').value = r.reward_product_id || '';
            document.getElementById('disc-reward-qty').value = r.reward_qty || '1';

            const limitMode = document.getElementById('disc-reward-limit-mode');
            const customLimit = document.getElementById('disc-custom-max-limit');
            const maxLimit = parseInt(r.max_reward_limit);
            if (!maxLimit || maxLimit <= 0 || isNaN(maxLimit)) {
                if (limitMode) limitMode.value = 'unlimited';
            } else if (maxLimit === 1) {
                if (limitMode) limitMode.value = 'max_1';
            } else {
                if (limitMode) limitMode.value = 'custom';
                if (customLimit) customLimit.value = maxLimit;
            }
            handleRewardLimitModeChange();

            document.getElementById('disc-value').value = r.discount_value || '0';
            document.getElementById('disc-min-cart').value = r.min_cart_amount || '0';
            document.getElementById('disc-min-qty').value = r.min_qty_required || '1';
            document.getElementById('disc-customer-target').value = r.customer_target || 'all';
            document.getElementById('disc-payment-method-target').value = r.payment_method_target || 'all';
            document.getElementById('disc-coupon-code').value = r.coupon_code || '';
            document.getElementById('disc-priority').value = r.priority || '1';
            document.getElementById('disc-active-checkbox').checked = (parseInt(r.is_active) === 1);
            document.getElementById('disc-start-date').value = r.start_datetime ? r.start_datetime.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('disc-end-date').value = r.end_datetime ? r.end_datetime.replace(' ', 'T').substring(0, 16) : '';

            handleDiscountTypeChange();
            handleTargetTypeChange();
            handleRewardTypeChange();

            document.getElementById('discount-modal-title').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: #f59e0b;"></i> Edit Discount Rule #' + r.id;
            document.getElementById('discount-modal').style.display = 'flex';
        }

        async function submitSaveDiscount(e) {
            e.preventDefault();
            const id = document.getElementById('disc-rule-id').value;
            const targetType = document.getElementById('disc-target-type').value;
            const discountType = document.getElementById('disc-type').value;
            let targetId = null;
            let targetIds = [];

            if (targetType === 'single_product') {
                targetId = document.getElementById('disc-target-product').value;
            } else if (targetType === 'category') {
                targetId = document.getElementById('disc-target-category').value;
            } else if (targetType === 'multi_products') {
                document.querySelectorAll('.disc-combo-check:checked').forEach(cb => {
                    const pId = parseInt(cb.value);
                    const qi = document.querySelector(`.disc-combo-item-qty[data-prod-id="${pId}"]`);
                    const reqQty = qi ? (parseInt(qi.value) || 1) : 1;
                    targetIds.push({ id: pId, qty: reqQty });
                });
                if (targetIds.length < 2 && discountType === 'combo_bundle') {
                    showToast('Please select at least 2 products for a combo bundle!', false);
                    return;
                }
            }

            let maxRewardLimit = null;
            const limitMode = document.getElementById('disc-reward-limit-mode')?.value || 'unlimited';
            if (limitMode === 'max_1') {
                maxRewardLimit = 1;
            } else if (limitMode === 'custom') {
                maxRewardLimit = parseInt(document.getElementById('disc-custom-max-limit')?.value) || 1;
            }

            const payload = {
                id: id,
                title: document.getElementById('disc-title').value.trim(),
                title_bn: document.getElementById('disc-title-bn').value.trim(),
                badge_text: document.getElementById('disc-badge-text').value.trim(),
                discount_type: discountType,
                target_type: targetType,
                target_id: targetId,
                target_ids: targetIds,
                reward_type: document.getElementById('disc-reward-type').value,
                reward_product_id: document.getElementById('disc-reward-product').value || null,
                reward_qty: document.getElementById('disc-reward-qty').value || 1,
                max_reward_limit: maxRewardLimit,
                discount_value: document.getElementById('disc-value').value || 0,
                min_cart_amount: document.getElementById('disc-min-cart').value || 0,
                min_qty_required: document.getElementById('disc-min-qty').value || 1,
                customer_target: document.getElementById('disc-customer-target').value,
                payment_method_target: document.getElementById('disc-payment-method-target').value,
                coupon_code: document.getElementById('disc-coupon-code').value.trim(),
                priority: document.getElementById('disc-priority').value || 1,
                is_active: document.getElementById('disc-active-checkbox').checked ? 1 : 0,
                start_datetime: document.getElementById('disc-start-date').value || null,
                end_datetime: document.getElementById('disc-end-date').value || null
            };

            try {
                const res = await fetch('api/discount-manager.php?action=save_rule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message);
                    closeModal('discount-modal');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    showToast(data.message || 'Failed to save discount rule', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            }
        }

        async function toggleDiscountStatus(id, newStatus) {
            const res = await fetch('api/discount-manager.php?action=toggle_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: newStatus })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message || 'Status updated');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message || 'Error updating status', false);
            }
        }

        async function deleteDiscountRule(id, title) {
            const ok = await showCustomConfirm({
                title: "Delete Discount Rule?",
                message: `Are you sure you want to delete discount rule <strong>"${title}"</strong> (#${id})?`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/discount-manager.php?action=delete_rule', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message || 'Discount rule deleted');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast(data.message || 'Error deleting rule', false);
            }
        }

        function filterDiscountsTable() {
            const val = document.getElementById('discount-search-input').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.discount-table-row');
            rows.forEach(r => {
                const text = r.getAttribute('data-search') || '';
                r.style.display = text.includes(val) ? '' : 'none';
            });
        }

        // Rich Text Editor Functions
        function formatDoc(id, cmd, val = null) {
            const editor = document.getElementById('editor-' + id);
            if (!editor) return;
            editor.focus();
            document.execCommand(cmd, false, val);
            syncEditor(id);
        }

        function formatHeading(id) {
            const editor = document.getElementById('editor-' + id);
            if (!editor) return;
            editor.focus();
            document.execCommand('formatBlock', false, '<h3>');
            syncEditor(id);
        }

        function formatQuote(id) {
            const editor = document.getElementById('editor-' + id);
            if (!editor) return;
            editor.focus();
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount || sel.isCollapsed) {
                document.execCommand('insertHTML', false, '<blockquote>💡 Important Note or Quote here...</blockquote><p><br></p>');
            } else {
                const text = sel.toString();
                document.execCommand('insertHTML', false, `<blockquote>“${text}”</blockquote><p><br></p>`);
            }
            syncEditor(id);
        }

        function formatNotice(id) {
            const editor = document.getElementById('editor-' + id);
            if (!editor) return;
            editor.focus();
            const sel = window.getSelection();
            const selectedText = (sel && sel.rangeCount && !sel.isCollapsed) ? sel.toString() : 'Notice: Please read this policy carefully before ordering.';
            document.execCommand('insertHTML', false, `<div class="editor-notice"><i class="fa-solid fa-circle-info"></i> <span><strong>Notice:</strong> ${selectedText}</span></div><p><br></p>`);
            syncEditor(id);
        }

        function formatHighlight(id) {
            const editor = document.getElementById('editor-' + id);
            if (!editor) return;
            editor.focus();
            document.execCommand('hiliteColor', false, '#fef08a');
            syncEditor(id);
        }

        function toggleHtmlView(id) {
            const editor = document.getElementById('editor-' + id);
            const textarea = document.getElementById(id);
            if (!editor || !textarea) return;

            if (textarea.style.display === 'none') {
                textarea.value = editor.innerHTML;
                editor.style.display = 'none';
                textarea.style.display = 'block';
                textarea.focus();
            } else {
                editor.innerHTML = textarea.value;
                textarea.style.display = 'none';
                editor.style.display = 'block';
                editor.focus();
            }
        }

        function syncEditor(id) {
            const editor = document.getElementById('editor-' + id);
            const textarea = document.getElementById(id);
            if (editor && textarea && textarea.style.display === 'none') {
                textarea.value = editor.innerHTML;
            }
        }

        function syncFromTextarea(id) {
            const editor = document.getElementById('editor-' + id);
            const textarea = document.getElementById(id);
            if (editor && textarea && textarea.style.display !== 'none') {
                editor.innerHTML = textarea.value;
            }
        }

        function syncAllRichEditors() {
            document.querySelectorAll('.rich-editor-content').forEach(ed => {
                const id = ed.id.replace('editor-', '');
                const ta = document.getElementById(id);
                if (ta) {
                    if (ta.style.display === 'none') {
                        ta.value = ed.innerHTML;
                    } else {
                        ed.innerHTML = ta.value;
                    }
                }
            });
        }

        async function savePortalSettings(e) {
            e.preventDefault();
            syncAllRichEditors();
            const form = e.target;
            const payload = {};
            
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(f => {
                if (f.id && f.id.startsWith('set-')) {
                    const key = f.id.replace('set-', '').replace(/-/g, '_');
                    if (f.type === 'checkbox') {
                        payload[key] = f.checked ? '1' : '0';
                    } else {
                        payload[key] = f.value;
                    }
                }
            });

            // Compatibility mappings
            if (payload.hotline_phone) payload.support_phone = payload.hotline_phone;
            if (payload.support_hours_en) payload.order_hours_en = payload.support_hours_en;
            if (payload.support_hours_bn) payload.order_hours_bn = payload.support_hours_bn;
            if (payload.office_address_en) payload.office_address = payload.office_address_en;

            const res = await fetch('api/admin.php?action=save_portal_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
            } else {
                showToast(data.message, false);
            }
        }

        function handleProviderChange(val) {
            const customContainer = document.getElementById('custom-url-container');
            if (customContainer) {
                customContainer.style.display = (val === 'custom') ? 'block' : 'none';
            }

            const lblApiKey = document.getElementById('lbl-sms-api-key');
            const hintApiKey = document.getElementById('hint-sms-api-key');
            const lblSender = document.getElementById('lbl-sms-sender-id');
            const hintSender = document.getElementById('hint-sms-sender-id');
            const inputSender = document.getElementById('set-sms-gateway-sender-id');
            const guideTitle = document.getElementById('sms-guide-title');
            const guideContent = document.getElementById('sms-guide-content');

            if (val === 'httpsms') {
                if (lblApiKey) lblApiKey.innerText = 'httpSMS API Key *';
                if (hintApiKey) hintApiKey.innerText = 'Generate or copy from your httpsms.com account dashboard (Settings -> API Keys).';
                if (lblSender) lblSender.innerText = 'Android SIM Mobile Number (Sender) *';
                if (hintSender) hintSender.innerText = 'Your Android phone SIM number (e.g. 017XXXXXXXX or +88017XXXXXXXX) registered in the httpSMS app.';
                if (inputSender) inputSender.placeholder = 'e.g. 017XXXXXXXX';
                if (guideTitle) guideTitle.innerText = 'httpSMS (Free Android Gateway) Setup Guide:';
                if (guideContent) {
                    guideContent.innerHTML = 
                        '1. <strong>Download App:</strong> Install the <strong>httpSMS</strong> app from Google Play Store or <a href="https://httpsms.com" target="_blank" style="color:#2563eb;text-decoration:underline;font-weight:700;">httpsms.com</a> on your Android phone.<br>' +
                        '2. <strong>Log in:</strong> Open the app, log in with your Google account, and grant SMS permissions.<br>' +
                        '3. <strong>Copy API Key:</strong> From the httpsms.com website dashboard, go to Settings &rarr; API Keys, copy your Key, and paste above.<br>' +
                        '4. <strong>SIM Number:</strong> Type your Android phone\'s SIM number above in <em>Android SIM Mobile Number</em>.<br>' +
                        '5. Check <strong>Live Gateway Active</strong> &rarr; click <strong>Save Gateway Settings</strong> &rarr; Test with your mobile below!';
                }
            } else {
                if (lblApiKey) lblApiKey.innerText = 'API Key / Auth Token *';
                if (hintApiKey) hintApiKey.innerText = 'Secret API Token from your SMS provider\'s portal dashboard.';
                if (lblSender) lblSender.innerText = 'Sender ID / Masking (Optional)';
                if (hintSender) hintSender.innerText = 'Approved sender Masking Name or Non-Masking numeric ID.';
                if (inputSender) inputSender.placeholder = 'e.g. BazarKinun.com or 8809612...';
                if (guideTitle) guideTitle.innerText = 'SMS Gateway Integration:';
                if (guideContent) {
                    guideContent.innerHTML = 
                        '1. <strong>httpSMS (Android Phone):</strong> Use your Android phone with any SIM card pack to send 100% free SMS/OTP.<br>' +
                        '2. <strong>Commercial Gateways:</strong> Whenever you buy SMS credits from Greenweb BD, BulkSMSBD, or SSL Wireless, paste the <strong>API Key</strong> here.<br>' +
                        '3. Instant Customer OTP, Order Confirmation, and Broadcast SMS will automatically send via your active gateway.';
                }
            }
        }
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const currentProv = document.getElementById('set-sms-gateway-provider')?.value;
            if (currentProv) handleProviderChange(currentProv);
        });

        async function testSmsGateway() {
            const phoneInput = document.getElementById('test-sms-phone');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const btn = document.getElementById('btn-test-sms');
            const resDiv = document.getElementById('test-sms-result');
            
            if (!phone) {
                showToast('Please enter a valid mobile number for the SMS test.', false);
                return;
            }
            
            btn.disabled = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            resDiv.style.display = 'none';
            
            try {
                const res = await fetch('api/admin.php?action=test_sms', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone: phone })
                });
                const data = await res.json();
                resDiv.style.display = 'block';
                if (data.status === 'success') {
                    resDiv.style.background = '#ecfdf5';
                    resDiv.style.border = '1.5px solid #a7f3d0';
                    resDiv.style.color = '#065f46';
                    resDiv.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 6px;"></i> ' + data.message;
                    showToast(data.message);
                } else {
                    resDiv.style.background = '#fef2f2';
                    resDiv.style.border = '1.5px solid #fecaca';
                    resDiv.style.color = '#991b1b';
                    resDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; margin-right: 6px;"></i> ' + (data.message || 'SMS send failed.');
                    showToast(data.message, false);
                }
            } catch (err) {
                resDiv.style.display = 'block';
                resDiv.style.background = '#fef2f2';
                resDiv.style.border = '1.5px solid #fecaca';
                resDiv.style.color = '#991b1b';
                resDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; margin-right: 6px;"></i> Network error: ' + err.message;
                showToast('Network error while sending test SMS', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }

        // --- HAPPY CUSTOMERS (OHC) ACTIONS ---
        function openAddOhcModal() {
            document.getElementById('ohc-id').value = '';
            document.getElementById('ohc-modal-title').innerText = 'Add Customer Review';
            document.getElementById('ohc-btn-text').innerText = 'Save Review';
            document.getElementById('ohc-name').value = '';
            document.getElementById('ohc-name-bn').value = '';
            document.getElementById('ohc-rating').value = '5';
            document.getElementById('ohc-comment').value = '';
            document.getElementById('ohc-comment-bn').value = '';
            document.getElementById('ohc-is-active').checked = true;
            document.getElementById('ohc-modal').style.display = 'flex';
        }

        function openEditOhcModal(r) {
            document.getElementById('ohc-id').value = r.id;
            document.getElementById('ohc-modal-title').innerText = 'Edit Customer Review';
            document.getElementById('ohc-btn-text').innerText = 'Save Changes';
            document.getElementById('ohc-name').value = r.name || '';
            document.getElementById('ohc-name-bn').value = r.name_bn || '';
            document.getElementById('ohc-rating').value = r.rating || 5;
            document.getElementById('ohc-comment').value = r.comment || '';
            document.getElementById('ohc-comment-bn').value = r.comment_bn || '';
            document.getElementById('ohc-is-active').checked = parseInt(r.is_active) === 1;
            document.getElementById('ohc-modal').style.display = 'flex';
        }

        async function handleOhcSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('ohc-id').value;
            const action = id ? 'edit_ohc' : 'add_ohc';
            const payload = {
                id: id,
                name: document.getElementById('ohc-name').value,
                name_bn: document.getElementById('ohc-name-bn').value,
                rating: document.getElementById('ohc-rating').value,
                comment: document.getElementById('ohc-comment').value,
                comment_bn: document.getElementById('ohc-comment-bn').value,
                is_active: document.getElementById('ohc-is-active').checked ? 1 : 0
            };

            const res = await fetch('api/admin.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                closeModal('ohc-modal');
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(data.message, false);
            }
        }

        async function toggleOhcStatus(id) {
            const res = await fetch('api/admin.php?action=toggle_ohc_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 400);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteOhc(id, name) {
            const ok = await showCustomConfirm({
                title: "Delete Review?",
                message: `Are you sure you want to delete the testimonial from <strong>'${name}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">This will remove the review from the website.</span>`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/admin.php?action=delete_ohc', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(data.message, false);
            }
        }

        // --- BROADCAST NOTICE ACTIONS ---
        function handleBroadcastTargetChange(select) {
            const grp = document.getElementById('bc-custom-numbers-group');
            if (select.value === 'custom') {
                grp.style.display = 'block';
                document.getElementById('bc-custom-numbers').required = true;
            } else {
                grp.style.display = 'none';
                document.getElementById('bc-custom-numbers').required = false;
            }
        }

        async function handleBroadcastSubmit(e) {
            e.preventDefault();
            const payload = {
                title: document.getElementById('bc-title').value,
                target_audience: document.getElementById('bc-target-audience').value,
                notice_type: document.getElementById('bc-notice-type').value,
                custom_numbers: document.getElementById('bc-custom-numbers').value,
                message: document.getElementById('bc-message').value,
                send_sms: document.getElementById('bc-send-sms').checked ? 1 : 0
            };

            const res = await fetch('api/admin.php?action=create_broadcast_notice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.message, false);
            }
        }

        async function toggleBroadcastNotice(id) {
            const res = await fetch('api/admin.php?action=toggle_broadcast_notice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 400);
            } else {
                showToast(data.message, false);
            }
        }

        async function deleteBroadcastNotice(id, title) {
            const ok = await showCustomConfirm({
                title: "Delete Notice?",
                message: `Are you sure you want to delete broadcast notice <strong>'${title}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">This will remove the announcement from history.</span>`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            const res = await fetch('api/admin.php?action=delete_broadcast_notice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast(data.message, false);
            }
        }
    </script>
        <!-- ROLE PERMISSION CONFIGURATION MODAL -->
    <div id="rolePermissionModal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 760px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 22px 28px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #faf5ff; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #1e1b4b;" id="roleModalTitle">Configure Role Permissions</h3>
                        <span style="font-size: 12.5px; color: #6d28d9; font-weight: 600;" id="roleModalSubtitle">Set active modules and privileges for this role</span>
                    </div>
                </div>
                <button type="button" onclick="closeRolePermissionModal()" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <form id="rolePermissionForm" onsubmit="saveRolePermissions(event)" style="padding: 24px 28px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1.5fr; gap: 14px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">Role Title *</label>
                        <input type="text" id="roleConfigTitle" name="role_title" required placeholder="Role Title (Accountant)" oninput="autoFillRoleKey(this.value)" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 700; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">Role Key (Slug) *</label>
                        <input type="text" id="roleConfigKey" name="role_key" required placeholder="accountant" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 700; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">Role Description</label>
                        <input type="text" id="roleConfigDesc" name="description" placeholder="Brief summary of duties..." style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <!-- Search & Quick Select Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; background: #f8fafc; padding: 10px 16px; border-radius: 10px; border: 1px solid #e2e8f0; flex-wrap: wrap; gap: 10px;">
                    <div style="position: relative; flex: 1; min-width: 240px;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 13px;"></i>
                        <input type="text" id="rolePermSearchInput" onkeyup="filterRolePermGrid()" placeholder="Filter permission, description, or slug (e.g. products.create)..." style="width: 100%; padding: 7px 12px 7px 32px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; outline: none; box-sizing: border-box;">
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="selectAllRolePerms(true)" style="background: white; border: 1px solid #cbd5e1; color: #4338ca; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">Select All</button>
                        <button type="button" onclick="selectAllRolePerms(false)" style="background: white; border: 1px solid #cbd5e1; color: #dc2626; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">Deselect All</button>
                    </div>
                </div>

                <!-- Module Categories Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <?php foreach ($systemModules as $modTitle => $modInfo): ?>
                        <div class="role-perm-mod-box" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #fcfcfc;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                                <h4 style="margin: 0; font-size: 13.5px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid <?= $modInfo['icon'] ?? 'fa-cube' ?>" style="color: <?= $modInfo['color'] ?? '#2563eb' ?>;"></i>
                                    <?= htmlspecialchars($modTitle) ?>
                                </h4>
                                <button type="button" onclick="toggleModulePerms(this)" style="background: none; border: none; color: #6366f1; font-size: 11px; font-weight: 700; cursor: pointer;">Toggle All</button>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($modInfo['permissions'] as $pKey => $pInfo): ?>
                                    <?php 
                                        $pName = is_array($pInfo) ? $pInfo['name'] : $pInfo;
                                        $pLegacy = is_array($pInfo) ? ($pInfo['legacy_slug'] ?? '') : '';
                                        $pDesc = is_array($pInfo) ? ($pInfo['description'] ?? '') : '';
                                    ?>
                                    <label class="role-perm-item" style="display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; font-weight: 600; cursor: pointer; color: #334155;">
                                        <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($pKey) ?>" data-legacy="<?= htmlspecialchars($pLegacy) ?>" class="role-perm-check" style="margin-top: 3px;">
                                        <div>
                                            <span style="font-weight: 700; color: #0f172a; display: block; font-size: 12.5px;"><?= htmlspecialchars($pName) ?></span>
                                            <code style="font-size: 10.5px; color: #6366f1; background: #ede9fe; padding: 1px 4px; border-radius: 3px;"><?= htmlspecialchars($pKey) ?></code>
                                            <?php if (!empty($pDesc)): ?>
                                                <small style="color: #64748b; display: block; font-size: 11px; margin-top: 2px; line-height: 1.3;"><?= htmlspecialchars($pDesc) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 18px;">
                    <button type="button" onclick="closeRolePermissionModal()" style="padding: 10px 18px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" id="saveRolePermBtn" style="padding: 10px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);">
                        <i class="fa-solid fa-check"></i> Save Role Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT FOR RBAC ROLE PERMISSIONS -->
    <script>
        let isCreatingNewRole = false;

        function autoFillRoleKey(val) {
            if (!isCreatingNewRole) return;
            const keyInput = document.getElementById('roleConfigKey');
            keyInput.value = val.toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
        }

        function filterRolePermGrid() {
            const q = (document.getElementById('rolePermSearchInput')?.value || '').toLowerCase().trim();
            document.querySelectorAll('.role-perm-mod-box').forEach(box => {
                let hasMatch = false;
                box.querySelectorAll('.role-perm-item').forEach(item => {
                    const txt = item.innerText.toLowerCase();
                    const match = !q || txt.includes(q);
                    item.style.display = match ? 'flex' : 'none';
                    if (match) hasMatch = true;
                });
                box.style.display = hasMatch ? 'block' : 'none';
            });
        }

        function toggleModulePerms(btn) {
            const box = btn.closest('.role-perm-mod-box');
            if (!box) return;
            const cbs = box.querySelectorAll('.role-perm-check');
            const anyUnchecked = Array.from(cbs).some(c => !c.checked);
            cbs.forEach(c => c.checked = anyUnchecked);
        }

        function openRolePermissionModal(role) {
            isCreatingNewRole = !role;
            const keyInput = document.getElementById('roleConfigKey');
            
            if (role) {
                keyInput.value = role.role_key;
                keyInput.readOnly = true;
                keyInput.style.background = '#f1f5f9';
                keyInput.style.color = '#64748b';
                keyInput.style.cursor = 'not-allowed';
                document.getElementById('roleConfigTitle').value = role.role_title;
                document.getElementById('roleConfigDesc').value = role.description || '';
                document.getElementById('roleModalTitle').innerText = `Configure Role: ${role.role_title}`;
                document.getElementById('roleModalSubtitle').innerText = `Role identifier: '${role.role_key}'`;
            } else {
                keyInput.value = '';
                keyInput.readOnly = false;
                keyInput.style.background = 'white';
                keyInput.style.color = '#0f172a';
                keyInput.style.cursor = 'text';
                document.getElementById('roleConfigTitle').value = '';
                document.getElementById('roleConfigDesc').value = '';
                document.getElementById('roleModalTitle').innerText = 'Create New System Role';
                document.getElementById('roleModalSubtitle').innerText = 'Define unique key, title, and module permissions';
            }

            let perms = [];
            if (role && role.permissions) {
                try {
                    perms = typeof role.permissions === 'string' ? JSON.parse(role.permissions) : role.permissions;
                } catch(e) { perms = []; }
            }

            document.querySelectorAll('.role-perm-check').forEach(cb => {
                const val = cb.value;
                const leg = cb.getAttribute('data-legacy') || '';
                cb.checked = Array.isArray(perms) && (perms.includes(val) || (leg && perms.includes(leg)));
            });

            const searchInp = document.getElementById('rolePermSearchInput');
            if (searchInp) {
                searchInp.value = '';
                filterRolePermGrid();
            }

            document.getElementById('rolePermissionModal').style.display = 'flex';
            setTimeout(() => {
                if (!role) document.getElementById('roleConfigTitle').focus();
            }, 50);
        }

        function openAddRoleModal() {
            openRolePermissionModal(null);
        }

        function closeRolePermissionModal() {
            document.getElementById('rolePermissionModal').style.display = 'none';
        }

        function selectAllRolePerms(state) {
            document.querySelectorAll('.role-perm-check').forEach(cb => cb.checked = state);
        }

        async function saveRolePermissions(e) {
            e.preventDefault();
            const btn = document.getElementById('saveRolePermBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            const roleKey = document.getElementById('roleConfigKey').value.trim().toLowerCase();
            const roleTitle = document.getElementById('roleConfigTitle').value.trim();
            const desc = document.getElementById('roleConfigDesc').value.trim();
            const selectedPerms = [];
            document.querySelectorAll('.role-perm-check:checked').forEach(cb => selectedPerms.push(cb.value));

            if (!roleKey) {
                showToast('Please specify a valid unique Role Key.', false);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Save Role Permissions';
                return;
            }

            try {
                const res = await fetch('api/admin.php?action=save_role_permissions', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken || ''
                    },
                    body: JSON.stringify({
                        role_key: roleKey,
                        role_title: roleTitle,
                        description: desc,
                        permissions: selectedPerms
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Role permissions saved!');
                    closeRolePermissionModal();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to save', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Save Role Permissions';
            }
        }

        async function changeUserRole(userId, newRole, userName) {
            try {
                showToast(`Updating role for ${userName}...`);
                const res = await fetch('api/admin.php?action=assign_user_role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId,
                        role: newRole
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(`Role updated to ${newRole} for ${userName}!`);
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Failed to update role', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        async function deleteCustomRole(roleKey, roleTitle) {
            const ok = await showCustomConfirm({
                title: "Delete Custom Role?",
                message: `Are you sure you want to delete custom role <strong>'${roleTitle}'</strong> (<code>${roleKey}</code>)?<br><span style="color: #ef4444; font-size: 13px;">Users assigned to this role will need a new role assigned.</span>`,
                confirmText: "Yes, Delete Role",
                icon: "fa-trash-can",
                confirmBtnBg: "#dc2626"
            });
            if (!ok) return;

            try {
                const res = await fetch('api/admin.php?action=delete_role', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken || ''
                    },
                    body: JSON.stringify({ role_key: roleKey })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Role deleted successfully');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to delete role', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        function filterUserRoleTable() {
            const q = document.getElementById('userRoleSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#userRoleTable tbody tr');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = text.includes(q) ? '' : 'none';
            });
        }
    </script>
    <!-- UNIFIED ADD / EDIT USER MODAL -->
    <div id="unifiedUserModal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 580px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 20px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #ede9fe; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;" id="unifiedUserModalTitle">Add New User</h3>
                        <span style="font-size: 12.5px; color: #64748b;" id="unifiedUserModalSub">Enter user details and access level</span>
                    </div>
                </div>
                <button type="button" onclick="closeUnifiedUserModal()" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <form id="unifiedUserForm" onsubmit="handleUnifiedUserSubmit(event)" style="padding: 24px 26px;">
                <input type="hidden" id="uniUserId" name="id" value="">

                <!-- 1. Full Name -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Full Name *</label>
                    <input type="text" id="uniFullName" name="name" required placeholder="Enter full name" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 600; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                </div>

                <!-- 2. Username & Password Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Username *</label>
                        <input type="text" id="uniUsername" name="username" required placeholder="Enter username" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Password * <span id="pwdHint" style="color: #94a3b8; font-weight: normal; font-size: 11px;"></span></label>
                        <input type="password" id="uniPassword" name="password" required placeholder="Enter password" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                    </div>
                </div>

                <!-- 3. Phone Number & Email Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Phone Number *</label>
                        <input type="text" id="uniPhone" name="phone" required placeholder="Enter phone number" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Email Address</label>
                        <input type="email" id="uniEmail" name="email" placeholder="Enter email address" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                    </div>
                </div>

                <!-- 4. Access Level (Role) & Account Status Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Access Level (Role) *</label>
                        <select id="uniRole" name="role" required style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 700; background: white; color: #0f172a; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                            <option value="operation">Operation</option>
                            <option value="manager">Store Manager</option>
                            <option value="rider">Delivery Rider</option>
                            <option value="management">Management Board</option>
                            <option value="admin">Super Admin</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Account Status *</label>
                        <select id="uniStatus" name="status" required style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 700; background: white; color: #0f172a; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                </div>

                <!-- 5. Default Location / Area -->
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Location / Area</label>
                    <input type="text" id="uniLocation" name="location" placeholder="Enter location or area" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='#cbd5e1';">
                </div>

                <!-- 6. Individual Custom Permissions Overrides -->
                <?php if (hasPermission('manage_roles_permissions')): ?>
                <div style="margin-bottom: 24px; border: 1.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #f8fafc;">
                    <div onclick="toggleUserCustomPerms()" style="padding: 12px 16px; background: #f1f5f9; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                        <span style="font-size: 13px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-key" style="color: #6366f1;"></i> Custom User Privileges (ব্যক্তিগত অতিরিক্ত পারমিশন)
                        </span>
                        <span style="font-size: 12px; color: #6366f1; font-weight: 700;" id="userCustomPermsToggleTxt">Expand +</span>
                    </div>
                    <div id="userCustomPermsBody" style="display: none; padding: 16px; max-height: 240px; overflow-y: auto;">
                        <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 10px;">Select extra privileges granted specifically to this individual user beyond their base role:</span>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 8px;">
                            <?php foreach ($systemModules as $mTitle => $mInfo): ?>
                                <?php foreach ($mInfo['permissions'] as $pKey => $pInfo): ?>
                                    <?php 
                                        $pName = is_array($pInfo) ? $pInfo['name'] : $pInfo;
                                        $pLegacy = is_array($pInfo) ? ($pInfo['legacy_slug'] ?? '') : '';
                                    ?>
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; cursor: pointer; color: #334155;">
                                        <input type="checkbox" name="user_permissions[]" value="<?= htmlspecialchars($pKey) ?>" data-legacy="<?= htmlspecialchars($pLegacy) ?>" class="user-custom-perm-check">
                                        <span><?= htmlspecialchars($pName) ?> <code style="font-size: 10px; color: #6366f1; background: #ede9fe; padding: 1px 4px; border-radius: 3px;"><?= htmlspecialchars($pKey) ?></code></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 18px;">
                    <button type="button" onclick="closeUnifiedUserModal()" style="padding: 10px 18px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" id="saveUniUserBtn" style="padding: 10px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS FOR UNIFIED USER MODAL -->
    <script>
        function toggleUserCustomPerms() {
            const el = document.getElementById('userCustomPermsBody');
            const txt = document.getElementById('userCustomPermsToggleTxt');
            if (!el) return;
            if (el.style.display === 'none') {
                el.style.display = 'block';
                if (txt) txt.innerText = 'Collapse -';
            } else {
                el.style.display = 'none';
                if (txt) txt.innerText = 'Expand +';
            }
        }

        function openAddUserModal() {
            document.getElementById('uniUserId').value = '';
            document.getElementById('uniFullName').value = '';
            document.getElementById('uniUsername').value = '';
            document.getElementById('uniPassword').value = '';
            document.getElementById('uniPassword').required = true;
            document.getElementById('uniPhone').value = '';
            document.getElementById('uniEmail').value = '';
            document.getElementById('uniRole').value = 'operation';
            document.getElementById('uniStatus').value = 'active';
            document.getElementById('uniLocation').value = '';

            document.querySelectorAll('.user-custom-perm-check').forEach(cb => cb.checked = false);

            document.getElementById('unifiedUserModalTitle').innerText = 'Add New User';
            document.getElementById('unifiedUserModalSub').innerText = 'Create a new internal user or staff member';
            document.getElementById('pwdHint').innerText = '';

            document.getElementById('unifiedUserModal').style.display = 'flex';
        }

        function openEditUserModal(user) {
            document.getElementById('uniUserId').value = user.id;
            document.getElementById('uniFullName').value = user.name || '';
            document.getElementById('uniUsername').value = user.username || '';
            document.getElementById('uniPassword').value = '';
            document.getElementById('uniPassword').required = false;
            document.getElementById('uniPhone').value = user.phone || '';
            document.getElementById('uniEmail').value = user.email || '';
            document.getElementById('uniRole').value = user.role || 'operation';
            document.getElementById('uniStatus').value = user.status || 'active';
            document.getElementById('uniLocation').value = user.default_location || '';

            let uPerms = [];
            if (user.permissions) {
                try {
                    uPerms = typeof user.permissions === 'string' ? JSON.parse(user.permissions) : user.permissions;
                } catch(e) { uPerms = []; }
            }
            document.querySelectorAll('.user-custom-perm-check').forEach(cb => {
                const val = cb.value;
                const leg = cb.getAttribute('data-legacy') || '';
                cb.checked = Array.isArray(uPerms) && (uPerms.includes(val) || (leg && uPerms.includes(leg)));
            });

            document.getElementById('unifiedUserModalTitle').innerText = `Edit User: ${user.name}`;
            document.getElementById('unifiedUserModalSub').innerText = `User ID: #${user.id} | Update credentials & access level`;
            document.getElementById('pwdHint').innerText = '(leave blank to keep unchanged)';

            document.getElementById('unifiedUserModal').style.display = 'flex';
        }

        function closeUnifiedUserModal() {
            document.getElementById('unifiedUserModal').style.display = 'none';
        }

        async function handleUnifiedUserSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('saveUniUserBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            const userId = document.getElementById('uniUserId').value;
            const action = userId ? 'edit_user' : 'add_user';

            const customPerms = [];
            document.querySelectorAll('.user-custom-perm-check:checked').forEach(cb => customPerms.push(cb.value));

            const payload = {
                id: userId,
                name: document.getElementById('uniFullName').value.trim(),
                username: document.getElementById('uniUsername').value.trim(),
                password: document.getElementById('uniPassword').value.trim(),
                phone: document.getElementById('uniPhone').value.trim(),
                email: document.getElementById('uniEmail').value.trim(),
                role: document.getElementById('uniRole').value,
                status: document.getElementById('uniStatus').value,
                location: document.getElementById('uniLocation').value.trim(),
                permissions: customPerms
            };

            try {
                const res = await fetch(`api/admin.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Saved successfully!');
                    closeUnifiedUserModal();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Error occurred', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Save User';
            }
        }
    </script>
    <!-- MODAL: ADD / EDIT PRODUCT BATCH -->
    <div id="batch-modal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 540px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 20px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #eff6ff; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-barcode"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #1e3a8a;" id="batchModalTitle">Create Product Batch</h3>
                        <span style="font-size: 12.5px; color: #2563eb;" id="batchModalSubtitle">Manage lot tracking, expiry and supplier</span>
                    </div>
                </div>
                <button type="button" onclick="closeModal('batch-modal')" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <form id="batchForm" onsubmit="submitSaveBatch(event)" style="padding: 24px 26px;">
                <input type="hidden" id="batch-id" value="">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Select Product *</label>
                    <select id="batch-prod-id" required style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; background: white;">
                        <?php foreach ($productList as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['product_code'] ?? ('PRD-' . str_pad($p['id'], 5, '0', STR_PAD_LEFT))) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Batch Number *</label>
                        <input type="text" id="batch-number" required placeholder="BAT-2026-001" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Supplier Name</label>
                        <input type="text" id="batch-supplier" placeholder="Direct Farm Mart" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Manufacturing Date</label>
                        <input type="date" id="batch-mfg-date" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Expiry Date</label>
                        <input type="date" id="batch-exp-date" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Batch Status</label>
                    <select id="batch-status" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 700; background: white;">
                        <option value="active">Active (Available for sales)</option>
                        <option value="expired">Expired</option>
                        <option value="depleted">Depleted</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 18px;">
                    <button type="button" onclick="closeModal('batch-modal')" style="padding: 10px 18px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="button" id="saveAsNewBatchBtn" onclick="submitSaveBatch(event, true)" style="display: none; padding: 10px 18px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); align-items: center; gap: 6px;">
                        <i class="fa-solid fa-clone"></i> Save as New
                    </button>
                    <button type="submit" id="saveBatchBtn" style="padding: 10px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check"></i> Save Batch
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: SET / EDIT MULTI-TIER PRICING -->
    <div id="pricing-modal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 580px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 20px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ecfdf5; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #065f46;" id="pricingModalTitle">Set Pricing Entry</h3>
                        <span style="font-size: 12.5px; color: #059669;">Configure CP, DP, MRP, and Web Selling Price</span>
                    </div>
                </div>
                <button type="button" onclick="closeModal('pricing-modal')" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <form id="pricingForm" onsubmit="submitSavePricing(event)" style="padding: 24px 26px;">
                <input type="hidden" id="pricing-id" value="">

                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Product *</label>
                        <select id="pricing-prod-id" required onchange="onPricingProductChange()" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; background: white;">
                            <?php foreach ($productList as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['product_code'] ?? ('PRD-' . str_pad($p['id'], 5, '0', STR_PAD_LEFT))) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Batch Number</label>
                        <select id="pricing-batch-id" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; background: white;">
                            <option value="">All Batches / Default</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">District</label>
                        <select id="pricing-district" onchange="onPricingDistrictChange()" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; background: white;">
                            <option value="All Bangladesh">Default / All Bangladesh</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Police Station / Thana</label>
                        <select id="pricing-police-station" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; background: white;">
                            <option value="All Police Stations">All Police Stations</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="pricing-zone-id" value="1">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Purchase Cost (CP) ৳</label>
                        <input type="number" step="0.5" id="pricing-cp" placeholder="80" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Dealer Rate (DP) ৳</label>
                        <input type="number" step="0.5" id="pricing-dp" placeholder="90" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">MRP ৳</label>
                        <input type="number" step="0.5" id="pricing-mrp" placeholder="120" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 600; outline: none; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Customer Selling Price ৳ *</label>
                        <input type="number" step="0.5" id="pricing-sale" required placeholder="105" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 800; color: #047857; outline: none; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 18px;">
                    <button type="button" onclick="closeModal('pricing-modal')" style="padding: 10px 18px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="button" id="saveAsNewPricingBtn" onclick="submitSavePricing(event, true)" style="display: none; padding: 10px 18px; background: #0d9488; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); align-items: center; gap: 6px;">
                        <i class="fa-solid fa-clone"></i> Save as New
                    </button>
                    <button type="submit" id="savePricingBtn" style="padding: 10px 24px; background: #059669; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check"></i> Save Price Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: QUICK STOCK ADJUSTMENT -->
    <div id="stock-op-modal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 20px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fffbeb; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #78350f;" id="stockOpModalTitle">Stock Inventory Action</h3>
                        <span style="font-size: 12.5px; color: #b45309;" id="stockOpSubtitle">Execute stock IN, OUT, or damage logging</span>
                    </div>
                </div>
                <button type="button" onclick="closeModal('stock-op-modal')" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <form id="stockOpForm" onsubmit="submitStockOp(event)" style="padding: 24px 26px;">
                <input type="hidden" id="stock-op-id" value="">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Operation Type *</label>
                    <select id="stock-op-type" required style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-weight: 700; background: white;">
                        <option value="stock_in">➕ Stock IN (Add Inventory)</option>
                        <option value="stock_out">➖ Stock OUT (Manual Reduction)</option>
                        <option value="damage">⚠️ Mark as Damaged / Lost</option>
                        <option value="adjustment">🔄 Stock Adjustment (Set Exact Value)</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Quantity *</label>
                    <input type="number" step="0.5" id="stock-op-qty" required placeholder="50" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 800; outline: none; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Notes / Reference Reason</label>
                    <input type="text" id="stock-op-notes" placeholder="Purchase shipment #7741 or broken jar" style="width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; box-sizing: border-box;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 18px;">
                    <button type="button" onclick="closeModal('stock-op-modal')" style="padding: 10px 18px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancel</button>
                    <button type="submit" id="execStockBtn" style="padding: 10px 24px; background: #d97706; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25); display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check"></i> Execute Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: STOCK MOVEMENT AUDIT LOGS -->
    <div id="stock-logs-modal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 900px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 20px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #e2e8f0; color: #334155; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Stock Movement Audit Trail</h3>
                        <span style="font-size: 12.5px; color: #64748b;">Historical stock additions, manual reductions, and damage reports</span>
                    </div>
                </div>
                <button type="button" onclick="closeModal('stock-logs-modal')" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <div style="padding: 20px 26px;">
                <div class="table-responsive" style="max-height: 55vh; overflow-y: auto;">
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Location</th>
                                <th>Action</th>
                                <th>Qty</th>
                                <th>Prev Stock</th>
                                <th>New Stock</th>
                                <th>Operator / Notes</th>
                            </tr>
                        </thead>
                        <tbody id="stock-logs-tbody">
                            <tr><td colspan="9" style="text-align: center; padding: 30px; color: #94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Loading stock logs...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
                    <button type="button" onclick="closeModal('stock-logs-modal')" style="padding: 9px 20px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT CONTROLLERS FOR 4 PRODUCT SUBTABS -->
    <script>
        function switchProductSubtab(tabName, btn) {
            if (!tabName) tabName = 'catalog';
            if (!btn) btn = document.getElementById('prod-subtab-btn-' + tabName);

            document.querySelectorAll('.prod-subtab-panel').forEach(p => p.style.display = 'none');
            document.querySelectorAll('.prod-subtab-btn').forEach(b => {
                b.style.background = '#f8fafc';
                b.style.color = '#334155';
                b.style.borderColor = '#e2e8f0';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
                const badge = b.querySelector('.subtab-badge');
                if (badge) {
                    badge.style.background = '#e2e8f0';
                    badge.style.color = '#475569';
                }
                const icon = b.querySelector('i');
                if (icon) {
                    if (b.id === 'prod-subtab-btn-catalog') icon.style.color = '#6366f1';
                    else if (b.id === 'prod-subtab-btn-batches') icon.style.color = '#2563eb';
                    else if (b.id === 'prod-subtab-btn-pricing') icon.style.color = '#059669';
                    else if (b.id === 'prod-subtab-btn-stocks') icon.style.color = '#d97706';
                }
            });

            const panel = document.getElementById('prod-panel-' + tabName);
            if (panel) panel.style.display = 'block';

            if (btn) {
                btn.style.background = '#6366f1';
                btn.style.color = 'white';
                btn.style.borderColor = '#6366f1';
                btn.style.boxShadow = '0 6px 18px rgba(99, 102, 241, 0.28)';
                btn.classList.add('active');
                const badge = btn.querySelector('.subtab-badge');
                if (badge) {
                    badge.style.background = 'rgba(255,255,255,0.25)';
                    badge.style.color = 'white';
                }
                const icon = btn.querySelector('i');
                if (icon) icon.style.color = 'white';
            }

            try {
                localStorage.setItem('admin_product_subtab', tabName);
            } catch(e) {}

            try {
                const url = new URL(window.location.href);
                if (url.searchParams.get('tab') === 'product' || !url.searchParams.has('tab')) {
                    url.searchParams.set('tab', 'product');
                    url.searchParams.set('subtab', tabName);
                    window.history.replaceState({ subtab: tabName }, '', url.toString());
                }
            } catch(e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('tab');
            if (currentTab === 'product') {
                const urlSubtab = urlParams.get('subtab');
                const savedSubtab = localStorage.getItem('admin_product_subtab');
                const targetSubtab = urlSubtab || savedSubtab;
                const validSubtabs = ['catalog', 'batches', 'pricing', 'stocks'];
                if (targetSubtab && validSubtabs.includes(targetSubtab) && targetSubtab !== 'catalog') {
                    switchProductSubtab(targetSubtab);
                }
            }
        });

        // Live Filters
        function filterBatchesTable() {
            const q = (document.getElementById('batch-search-input')?.value || '').toLowerCase().trim();
            document.querySelectorAll('.batch-table-row').forEach(r => {
                const text = (r.getAttribute('data-search') || '').toLowerCase();
                r.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function filterPricingTable() {
            const q = (document.getElementById('pricing-search-input')?.value || '').toLowerCase().trim();
            document.querySelectorAll('.pricing-table-row').forEach(r => {
                const text = (r.getAttribute('data-search') || '').toLowerCase();
                r.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        function filterStocksTable() {
            const q = (document.getElementById('stock-search-input')?.value || '').toLowerCase().trim();
            document.querySelectorAll('.stock-table-row').forEach(r => {
                const text = (r.getAttribute('data-search') || '').toLowerCase();
                r.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }

        // --- BATCH MODAL ACTIONS ---
        function openAddBatchModal() {
            document.getElementById('batch-id').value = '';
            document.getElementById('batch-number').value = '';
            document.getElementById('batch-supplier').value = '';
            document.getElementById('batch-mfg-date').value = '';
            document.getElementById('batch-exp-date').value = '';
            document.getElementById('batch-status').value = 'active';

            document.getElementById('batchModalTitle').innerText = 'Create Product Batch';
            document.getElementById('batchModalSubtitle').innerText = 'Manage lot tracking, expiry and supplier';
            const saveAsNewBtn = document.getElementById('saveAsNewBatchBtn');
            if (saveAsNewBtn) saveAsNewBtn.style.display = 'none';
            document.getElementById('batch-modal').style.display = 'flex';
        }

        function openEditBatchModal(b) {
            document.getElementById('batch-id').value = b.id;
            document.getElementById('batch-prod-id').value = b.product_id;
            document.getElementById('batch-number').value = b.batch_number;
            document.getElementById('batch-supplier').value = b.supplier_name || '';
            document.getElementById('batch-mfg-date').value = b.mfg_date || '';
            document.getElementById('batch-exp-date').value = b.exp_date || '';
            document.getElementById('batch-status').value = b.status || 'active';

            document.getElementById('batchModalTitle').innerText = `Edit Batch: ${b.batch_number}`;
            document.getElementById('batchModalSubtitle').innerText = `Product: ${b.product_title}`;
            const saveAsNewBtn = document.getElementById('saveAsNewBatchBtn');
            if (saveAsNewBtn) saveAsNewBtn.style.display = 'inline-flex';
            document.getElementById('batch-modal').style.display = 'flex';
        }

        async function submitSaveBatch(e, isSaveAsNew = false) {
            if (e) e.preventDefault();
            const btn = isSaveAsNew ? document.getElementById('saveAsNewBatchBtn') : document.getElementById('saveBatchBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            const batchId = isSaveAsNew ? '' : document.getElementById('batch-id').value;
            const batchNum = document.getElementById('batch-number').value.trim();
            const prodId = document.getElementById('batch-prod-id').value;

            if (!prodId || !batchNum) {
                showToast('Product and Batch Number are required!', false);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = isSaveAsNew ? '<i class="fa-solid fa-clone"></i> Save as New' : '<i class="fa-solid fa-check"></i> Save Batch';
                }
                return;
            }

            const payload = {
                id: batchId,
                product_id: prodId,
                batch_number: batchNum,
                supplier_name: document.getElementById('batch-supplier').value.trim(),
                mfg_date: document.getElementById('batch-mfg-date').value,
                exp_date: document.getElementById('batch-exp-date').value,
                status: document.getElementById('batch-status').value
            };

            try {
                const res = await fetch('api/inventory-manager.php?action=save_batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || (isSaveAsNew ? 'New batch created successfully!' : 'Batch saved successfully!'));
                    closeModal('batch-modal');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to save batch', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = isSaveAsNew ? '<i class="fa-solid fa-clone"></i> Save as New' : '<i class="fa-solid fa-check"></i> Save Batch';
                }
            }
        }

        async function deleteBatch(id, num) {
            const ok = await showCustomConfirm({
                title: "Delete Batch?",
                message: `Are you sure you want to delete batch <strong>'${num}'</strong>?<br><span style="color: #ef4444; font-size: 13px;">Associated pricing and stock ledger entries may be affected.</span>`,
                confirmText: "Yes, Delete Batch",
                icon: "fa-trash-can",
                confirmBtnBg: "#dc2626"
            });
            if (!ok) return;

            try {
                const res = await fetch('api/inventory-manager.php?action=delete_batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Batch deleted successfully');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to delete batch', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        // --- PRICING MODAL ACTIONS ---
        function initPricingLocationDropdowns(defaultDistrict = 'All Bangladesh') {
            const dSelect = document.getElementById('pricing-district');
            if (!dSelect) return;
            
            dSelect.innerHTML = '<option value="All Bangladesh">Default / All Bangladesh</option>';
            
            if (typeof BD_DISTRICTS !== 'undefined' && BD_DISTRICTS.length > 0) {
                BD_DISTRICTS.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.textContent = d;
                    if (d.toLowerCase() === defaultDistrict.toLowerCase()) {
                        opt.selected = true;
                    }
                    dSelect.appendChild(opt);
                });
            }
        }

        const ALL_PRODUCT_BATCHES = <?= json_encode($batchesList) ?>;

        function onPricingProductChange(selectedBatchId = '') {
            const prodSelect = document.getElementById('pricing-prod-id');
            const batchSelect = document.getElementById('pricing-batch-id');
            if (!prodSelect || !batchSelect) return;

            const prodId = prodSelect.value;
            batchSelect.innerHTML = '<option value="">All Batches / Default</option>';

            if (prodId && typeof ALL_PRODUCT_BATCHES !== 'undefined' && Array.isArray(ALL_PRODUCT_BATCHES)) {
                const batches = ALL_PRODUCT_BATCHES.filter(b => parseInt(b.product_id) === parseInt(prodId));
                batches.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    const exp = b.exp_date ? ` (Exp: ${b.exp_date})` : '';
                    opt.textContent = `${b.batch_number}${exp} - ${b.status}`;
                    if (selectedBatchId && parseInt(selectedBatchId) === parseInt(b.id)) {
                        opt.selected = true;
                    }
                    batchSelect.appendChild(opt);
                });
            }
        }

        function onPricingDistrictChange(selectedThana = '') {
            const dSelect = document.getElementById('pricing-district');
            const tSelect = document.getElementById('pricing-police-station');
            if (!dSelect || !tSelect) return;

            const district = dSelect.value;
            tSelect.innerHTML = '<option value="All Police Stations">All Police Stations</option>';

            if (district && district !== 'All Bangladesh' && typeof BD_LOCATIONS !== 'undefined' && BD_LOCATIONS[district]) {
                const thanas = BD_LOCATIONS[district];
                thanas.forEach(th => {
                    const opt = document.createElement('option');
                    opt.value = th;
                    opt.textContent = th;
                    if (selectedThana && selectedThana.toLowerCase() === th.toLowerCase()) {
                        opt.selected = true;
                    }
                    tSelect.appendChild(opt);
                });
            }
        }

        function openEditPricingModal(pr = null) {
            const saveAsNewBtn = document.getElementById('saveAsNewPricingBtn');
            if (pr) {
                const district = pr.district || 'All Bangladesh';
                const policeStation = pr.police_station || 'All Police Stations';
                initPricingLocationDropdowns(district);
                onPricingDistrictChange(policeStation);

                document.getElementById('pricing-id').value = pr.id;
                document.getElementById('pricing-prod-id').value = pr.product_id;
                onPricingProductChange(pr.batch_id || '');

                document.getElementById('pricing-zone-id').value = pr.zone_id || 1;
                document.getElementById('pricing-district').value = district;
                document.getElementById('pricing-police-station').value = policeStation;
                document.getElementById('pricing-cp').value = pr.cost_price || '';
                document.getElementById('pricing-dp').value = pr.dp_price || '';
                document.getElementById('pricing-mrp').value = pr.mrp_price || '';
                document.getElementById('pricing-sale').value = pr.selling_price || '';
                document.getElementById('pricingModalTitle').innerText = `Edit Pricing: ${pr.product_title}`;
                if (saveAsNewBtn) saveAsNewBtn.style.display = 'inline-flex';
            } else {
                initPricingLocationDropdowns('All Bangladesh');
                onPricingDistrictChange();
                document.getElementById('pricing-id').value = '';
                const prodSelect = document.getElementById('pricing-prod-id');
                if (prodSelect) {
                    prodSelect.selectedIndex = 0;
                    onPricingProductChange('');
                }
                document.getElementById('pricing-zone-id').value = 1;
                document.getElementById('pricing-cp').value = '';
                document.getElementById('pricing-dp').value = '';
                document.getElementById('pricing-mrp').value = '';
                document.getElementById('pricing-sale').value = '';
                document.getElementById('pricingModalTitle').innerText = 'Set New Pricing Entry';
                if (saveAsNewBtn) saveAsNewBtn.style.display = 'none';
            }
            document.getElementById('pricing-modal').style.display = 'flex';
        }

        async function submitSavePricing(e, isSaveAsNew = false) {
            if (e) e.preventDefault();
            const btn = isSaveAsNew ? document.getElementById('saveAsNewPricingBtn') : document.getElementById('savePricingBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            const payload = {
                id: isSaveAsNew ? '' : document.getElementById('pricing-id').value,
                product_id: document.getElementById('pricing-prod-id').value,
                batch_id: document.getElementById('pricing-batch-id').value || null,
                zone_id: document.getElementById('pricing-zone-id').value || 1,
                district: document.getElementById('pricing-district').value || 'All Bangladesh',
                police_station: document.getElementById('pricing-police-station').value || 'All Police Stations',
                cost_price: document.getElementById('pricing-cp').value,
                dp_price: document.getElementById('pricing-dp').value,
                mrp_price: document.getElementById('pricing-mrp').value,
                selling_price: document.getElementById('pricing-sale').value,
                is_active: 1
            };

            try {
                const res = await fetch('api/inventory-manager.php?action=save_pricing', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || (isSaveAsNew ? 'New pricing entry created!' : 'Pricing updated successfully!'));
                    closeModal('pricing-modal');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to update pricing', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = isSaveAsNew ? '<i class="fa-solid fa-clone"></i> Save as New' : '<i class="fa-solid fa-check"></i> Save Price Entry';
                }
            }
        }

        async function deletePricingEntry(id, productTitle, locationName) {
            const ok = await showCustomConfirm({
                title: "Delete Pricing Entry?",
                message: `Are you sure you want to delete pricing entry for <strong>'${productTitle}'</strong> (${locationName})?`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            try {
                const res = await fetch('api/inventory-manager.php?action=delete_pricing', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Pricing entry deleted successfully');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to delete pricing entry', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        // --- STOCK OP MODAL ACTIONS ---
        function openStockOpModal(stockId, prodTitle, batchNum, locationName, currentStock, defaultOp = 'stock_in') {
            document.getElementById('stock-op-id').value = stockId;
            document.getElementById('stock-op-type').value = defaultOp;
            document.getElementById('stock-op-qty').value = '';
            document.getElementById('stock-op-notes').value = '';
            document.getElementById('stockOpModalTitle').innerText = `Stock Action: ${prodTitle}`;
            document.getElementById('stockOpSubtitle').innerText = `Batch: ${batchNum} | Location: ${locationName || 'Central Hub'} | Current Inventory: ${currentStock} pcs`;
            document.getElementById('stock-op-modal').style.display = 'flex';
        }

        async function submitStockOp(e) {
            e.preventDefault();
            const btn = document.getElementById('execStockBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

            const payload = {
                stock_id: document.getElementById('stock-op-id').value,
                operation: document.getElementById('stock-op-type').value,
                quantity: document.getElementById('stock-op-qty').value,
                notes: document.getElementById('stock-op-notes').value.trim()
            };

            try {
                const res = await fetch('api/inventory-manager.php?action=update_stock_operation', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Stock updated successfully!');
                    closeModal('stock-op-modal');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to execute stock update', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Execute Update';
            }
        }

        // --- STOCK AUDIT LOGS MODAL ---
        async function openStockLogsModal() {
            document.getElementById('stock-logs-modal').style.display = 'flex';
            const tbody = document.getElementById('stock-logs-tbody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Loading stock logs...</td></tr>';

            try {
                const res = await fetch('api/inventory-manager.php?action=get_stock_logs');
                const data = await res.json();
                if (data.status === 'success' && data.logs && data.logs.length > 0) {
                    let html = '';
                    data.logs.forEach(l => {
                        const isAdd = (l.action_type === 'stock_in');
                        const isDmg = (l.action_type === 'damage');
                        const actColor = isAdd ? '#15803d' : (isDmg ? '#b45309' : '#dc2626');
                        const actBg = isAdd ? '#dcfce7' : (isDmg ? '#fef3c7' : '#fee2e2');
                        const locText = (!l.district || l.district === 'All Bangladesh') ? 'Central Hub' : `${l.district}${l.police_station && l.police_station !== 'All Police Stations' ? ' ('+l.police_station+')' : ''}`;

                        html += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="font-size: 12px; color: #64748b;">${l.created_at}</td>
                                <td><strong>${l.product_title || 'Product'}</strong></td>
                                <td><span style="color: #2563eb; font-weight: 700;">${l.batch_number || 'Default'}</span></td>
                                <td><span style="background: #f8fafc; color: #0f172a; padding: 2px 7px; border-radius: 4px; font-weight: 700; font-size: 11px; border: 1px solid #e2e8f0;"><i class="fa-solid fa-location-dot" style="color: #ea580c; font-size: 10px;"></i> ${locText}</span></td>
                                <td><span style="background: ${actBg}; color: ${actColor}; padding: 2px 7px; border-radius: 4px; font-weight: 800; font-size: 11px;">${l.action_type.toUpperCase()}</span></td>
                                <td><strong style="font-size: 13.5px;">${l.quantity}</strong></td>
                                <td style="color: #64748b;">${l.previous_stock}</td>
                                <td><strong style="color: #0f172a;">${l.new_stock}</strong></td>
                                <td style="font-size: 12px; color: #475569;">${l.notes || (l.created_by ? 'By ' + l.created_by : '-')}</td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 35px; color: #94a3b8;">No stock movement history recorded yet.</td></tr>';
                }
            } catch(e) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 35px; color: #ef4444;">Failed to load logs.</td></tr>';
            }
        }

        // --- DP SALES ACTIONS ---
        const ALL_PRODUCTS_DATA = <?= json_encode($productList) ?>;
        const ALL_PRICING_DATA = <?= json_encode($pricingList) ?>;
        let dpRowCounter = 0;

        function switchDpSubtab(subtabName) {
            document.querySelectorAll('.dp-subpanel').forEach(p => p.style.display = 'none');
            document.querySelectorAll('.dp-nav-btn').forEach(b => {
                b.style.background = '#f8fafc';
                b.style.color = '#334155';
                b.style.border = '1.5px solid #e2e8f0';
                b.style.boxShadow = 'none';
                b.classList.remove('active');
            });

            const targetBtn = document.getElementById('dp-tab-btn-' + subtabName);
            if (targetBtn) {
                targetBtn.style.background = '#0284c7';
                targetBtn.style.color = 'white';
                targetBtn.style.border = '1.5px solid #0284c7';
                targetBtn.style.boxShadow = '0 4px 14px rgba(2, 132, 199, 0.28)';
                targetBtn.classList.add('active');
            }

            const targetPanel = document.getElementById('dp-panel-' + subtabName);
            if (targetPanel) {
                targetPanel.style.display = 'block';
            }

            if (subtabName === 'pos') {
                initDpPos();
            }
        }

        function initDpPos() {
            const tbody = document.getElementById('dpItemsBody');
            if (tbody && tbody.children.length === 0) {
                addDpSaleRow();
            }
        }

        function addDpSaleRow() {
            dpRowCounter++;
            const rowId = dpRowCounter;
            const tbody = document.getElementById('dpItemsBody');
            if (!tbody) return;

            let prodOptions = '<option value="">-- Select Product --</option>';
            if (typeof ALL_PRODUCTS_DATA !== 'undefined') {
                ALL_PRODUCTS_DATA.forEach(p => {
                    prodOptions += `<option value="${p.id}">${p.title} (${p.product_code || 'PRD-' + p.id})</option>`;
                });
            }

            const tr = document.createElement('tr');
            tr.id = `dp-row-${rowId}`;
            tr.style.borderBottom = '1px solid #f1f5f9';
            tr.innerHTML = `
                <td style="padding: 10px 8px;">
                    <select id="dp-prod-${rowId}" onchange="onDpProductSelect(${rowId})" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 600; outline: none; background: white;">
                        ${prodOptions}
                    </select>
                </td>
                <td style="padding: 10px 8px;">
                    <select id="dp-batch-${rowId}" onchange="onDpBatchSelect(${rowId})" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; font-weight: 600; outline: none; background: white;">
                        <option value="">Default Batch</option>
                    </select>
                </td>
                <td style="padding: 10px 8px;">
                    <input type="number" id="dp-rate-${rowId}" step="0.5" value="0" onkeyup="calcDpLineTotal(${rowId})" onchange="calcDpLineTotal(${rowId})" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 800; color: #0284c7; outline: none; box-sizing: border-box;">
                    <input type="hidden" id="dp-cp-${rowId}" value="0">
                </td>
                <td style="padding: 10px 8px;">
                    <input type="number" id="dp-qty-${rowId}" step="1" min="1" value="1" onkeyup="calcDpLineTotal(${rowId})" onchange="calcDpLineTotal(${rowId})" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 700; outline: none; box-sizing: border-box;">
                </td>
                <td style="padding: 10px 8px; font-weight: 900; color: #0f172a; font-size: 14px;" id="dp-linetotal-${rowId}">
                    ৳0.00
                </td>
                <td style="padding: 10px 8px; text-align: center;">
                    <button type="button" onclick="removeDpSaleRow(${rowId})" style="background: none; border: none; color: #ef4444; font-size: 16px; cursor: pointer; padding: 4px 8px;" title="Remove Item">&times;</button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        function removeDpSaleRow(rowId) {
            const tr = document.getElementById(`dp-row-${rowId}`);
            if (tr) tr.remove();
            calcDpSummary();
        }

        function onDpProductSelect(rowId) {
            const pId = document.getElementById(`dp-prod-${rowId}`)?.value;
            const batchSelect = document.getElementById(`dp-batch-${rowId}`);
            if (!batchSelect) return;

            batchSelect.innerHTML = '<option value="">Default Batch</option>';

            let defaultCp = 0;
            let defaultDp = 0;

            if (pId) {
                if (typeof ALL_PRODUCT_BATCHES !== 'undefined') {
                    const batches = ALL_PRODUCT_BATCHES.filter(b => parseInt(b.product_id) === parseInt(pId));
                    batches.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = `${b.batch_number} - ${b.status}`;
                        batchSelect.appendChild(opt);
                    });
                }

                if (typeof ALL_PRICING_DATA !== 'undefined') {
                    const pr = ALL_PRICING_DATA.find(x => parseInt(x.product_id) === parseInt(pId));
                    if (pr) {
                        defaultCp = parseFloat(pr.cost_price || 0);
                        defaultDp = parseFloat(pr.dp_price || 0);
                    }
                }

                if (!defaultDp && typeof ALL_PRODUCTS_DATA !== 'undefined') {
                    const prod = ALL_PRODUCTS_DATA.find(x => parseInt(x.id) === parseInt(pId));
                    if (prod) {
                        const basePrice = parseFloat(prod.price || 0);
                        defaultDp = Math.round(basePrice * 0.9);
                        defaultCp = Math.round(basePrice * 0.8);
                    }
                }
            }

            document.getElementById(`dp-rate-${rowId}`).value = defaultDp;
            document.getElementById(`dp-cp-${rowId}`).value = defaultCp;
            calcDpLineTotal(rowId);
        }

        function onDpBatchSelect(rowId) {
            const pId = document.getElementById(`dp-prod-${rowId}`)?.value;
            const bId = document.getElementById(`dp-batch-${rowId}`)?.value;
            if (pId && bId && typeof ALL_PRICING_DATA !== 'undefined') {
                const pr = ALL_PRICING_DATA.find(x => parseInt(x.product_id) === parseInt(pId) && parseInt(x.batch_id) === parseInt(bId));
                if (pr) {
                    document.getElementById(`dp-rate-${rowId}`).value = parseFloat(pr.dp_price || 0);
                    document.getElementById(`dp-cp-${rowId}`).value = parseFloat(pr.cost_price || 0);
                }
            }
            calcDpLineTotal(rowId);
        }

        function calcDpLineTotal(rowId) {
            const rate = parseFloat(document.getElementById(`dp-rate-${rowId}`)?.value || 0);
            const qty = parseFloat(document.getElementById(`dp-qty-${rowId}`)?.value || 0);
            const total = Math.max(0, rate * qty);
            const cell = document.getElementById(`dp-linetotal-${rowId}`);
            if (cell) cell.innerText = '৳' + total.toFixed(2);
            calcDpSummary();
        }

        function calcDpSummary() {
            let subtotal = 0;
            const tbody = document.getElementById('dpItemsBody');
            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(r => {
                    const rowId = r.id.replace('dp-row-', '');
                    const rate = parseFloat(document.getElementById(`dp-rate-${rowId}`)?.value || 0);
                    const qty = parseFloat(document.getElementById(`dp-qty-${rowId}`)?.value || 0);
                    subtotal += (rate * qty);
                });
            }

            const discount = parseFloat(document.getElementById('dp-discount')?.value || 0);
            const delCharge = parseFloat(document.getElementById('dp-delivery-charge')?.value || 0);
            const paid = parseFloat(document.getElementById('dp-paid-amount')?.value || 0);

            const total = Math.max(0, (subtotal - discount) + delCharge);
            const due = Math.max(0, total - paid);

            document.getElementById('dp-summary-subtotal').innerText = '৳' + subtotal.toFixed(2);
            document.getElementById('dp-summary-total').innerText = '৳' + total.toFixed(2);
            document.getElementById('dp-summary-due').innerText = '৳' + due.toFixed(2);
        }

        async function submitSaveDpSale(e) {
            e.preventDefault();
            const btn = document.getElementById('saveDpSaleBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

            const dealerName = document.getElementById('dp-dealer-name').value.trim();
            const dealerPhone = document.getElementById('dp-dealer-phone').value.trim();
            const dealerAddress = document.getElementById('dp-dealer-address').value.trim();
            const paymentMethod = document.getElementById('dp-payment-method').value;
            const discount = parseFloat(document.getElementById('dp-discount').value || 0);
            const deliveryCharge = parseFloat(document.getElementById('dp-delivery-charge').value || 0);
            const paidAmount = parseFloat(document.getElementById('dp-paid-amount').value || 0);
            const notes = document.getElementById('dp-notes').value.trim();

            const items = [];
            const tbody = document.getElementById('dpItemsBody');
            const rows = tbody ? tbody.querySelectorAll('tr') : [];

            for (let r of rows) {
                const rowId = r.id.replace('dp-row-', '');
                const pId = document.getElementById(`dp-prod-${rowId}`)?.value;
                const bId = document.getElementById(`dp-batch-${rowId}`)?.value;
                const dpRate = parseFloat(document.getElementById(`dp-rate-${rowId}`)?.value || 0);
                const cp = parseFloat(document.getElementById(`dp-cp-${rowId}`)?.value || 0);
                const qty = parseFloat(document.getElementById(`dp-qty-${rowId}`)?.value || 0);

                if (pId && qty > 0) {
                    items.push({
                        product_id: pId,
                        batch_id: bId || null,
                        dp_price: dpRate,
                        cost_price: cp,
                        quantity: qty
                    });
                }
            }

            if (items.length === 0) {
                showToast('Please select at least one product item!', false);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i> Confirm & Save DP Invoice';
                return;
            }

            const payload = {
                dealer_name: dealerName,
                dealer_phone: dealerPhone,
                dealer_address: dealerAddress,
                payment_method: paymentMethod,
                discount: discount,
                delivery_charge: deliveryCharge,
                paid_amount: paidAmount,
                notes: notes,
                items: items,
                created_by: '<?= htmlspecialchars(addslashes($adminName)) ?>'
            };

            try {
                const res = await fetch('api/dp-sales-manager.php?action=save_dp_sale', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'DP Sale created successfully!');
                    if (data.sale_id) {
                        viewDpInvoice(data.sale_id);
                    }
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to save DP sale', false);
                }
            } catch(err) {
                showToast('Connection error', false);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i> Confirm & Save DP Invoice';
            }
        }

        async function viewDpInvoice(id) {
            const printArea = document.getElementById('dpInvoicePrintArea');
            printArea.innerHTML = '<div style="text-align: center; padding: 30px; color: #94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Loading invoice details...</div>';
            document.getElementById('dp-invoice-modal').style.display = 'flex';

            try {
                const res = await fetch(`api/dp-sales-manager.php?action=get_dp_sale_detail&id=${id}`);
                const data = await res.json();
                if (data.status === 'success' && data.sale) {
                    const s = data.sale;
                    const items = data.items || [];

                    let itemRows = '';
                    items.forEach((it, idx) => {
                        itemRows += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 8px; font-weight: 700;">${idx + 1}</td>
                                <td style="padding: 10px 8px;">
                                    <strong>${it.product_title}</strong>
                                    <div style="font-size: 11px; color: #64748b;">${it.product_code || ''} ${it.batch_number ? '| Batch: ' + it.batch_number : ''}</div>
                                </td>
                                <td style="padding: 10px 8px; font-weight: 700; color: #0284c7;">৳${parseFloat(it.dp_price).toFixed(2)}</td>
                                <td style="padding: 10px 8px; font-weight: 800; text-align: center;">${it.quantity}</td>
                                <td style="padding: 10px 8px; font-weight: 900; text-align: right; color: #0f172a;">৳${parseFloat(it.total_price).toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    printArea.innerHTML = `
                        <div id="printableDpInvoiceDoc" style="font-family: inherit;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0284c7; padding-bottom: 16px; margin-bottom: 18px;">
                                <div>
                                    <h2 style="margin: 0; color: #0369a1; font-size: 22px; font-weight: 900;">BazarKinun.com</h2>
                                    <span style="font-size: 12px; font-weight: 800; color: #0284c7; text-transform: uppercase;">Wholesale &amp; Dealer Sales Invoice</span>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Hotline: <?= htmlspecialchars($portalPhone) ?> | <?= htmlspecialchars($officeAddressEn) ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 16px; font-weight: 900; color: #0f172a;">${s.invoice_no}</div>
                                    <div style="font-size: 12px; color: #64748b;">Date: ${s.created_at}</div>
                                    <span style="background: ${s.payment_status === 'Paid' ? '#dcfce7' : '#fee2e2'}; color: ${s.payment_status === 'Paid' ? '#15803d' : '#b91c1c'}; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 11px;">${s.payment_status.toUpperCase()}</span>
                                </div>
                            </div>

                            <div style="background: #f8fafc; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px;">
                                <strong style="color: #334155; font-size: 14px;">Dealer: ${s.dealer_name}</strong>
                                ${s.dealer_phone ? `<div style="color: #64748b; font-size: 12px;">Phone: ${s.dealer_phone}</div>` : ''}
                                ${s.dealer_address ? `<div style="color: #64748b; font-size: 12px;">Address: ${s.dealer_address}</div>` : ''}
                            </div>

                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 13px;">
                                <thead>
                                    <tr style="background: #f1f5f9; text-align: left; font-size: 12px; color: #475569; font-weight: 800;">
                                        <th style="padding: 8px;">#</th>
                                        <th style="padding: 8px;">Item Description</th>
                                        <th style="padding: 8px;">DP Rate</th>
                                        <th style="padding: 8px; text-align: center;">Qty</th>
                                        <th style="padding: 8px; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemRows}
                                </tbody>
                            </table>

                            <div style="display: flex; justify-content: flex-end;">
                                <div style="width: 250px; display: flex; flex-direction: column; gap: 6px; font-size: 13px;">
                                    <div style="display: flex; justify-content: space-between; color: #64748b;">
                                        <span>Subtotal:</span>
                                        <strong>৳${parseFloat(s.subtotal).toFixed(2)}</strong>
                                    </div>
                                    ${parseFloat(s.discount) > 0 ? `
                                        <div style="display: flex; justify-content: space-between; color: #dc2626;">
                                            <span>Discount:</span>
                                            <strong>-৳${parseFloat(s.discount).toFixed(2)}</strong>
                                        </div>
                                    ` : ''}
                                    ${parseFloat(s.delivery_charge) > 0 ? `
                                        <div style="display: flex; justify-content: space-between; color: #64748b;">
                                            <span>Transport:</span>
                                            <strong>+৳${parseFloat(s.delivery_charge).toFixed(2)}</strong>
                                        </div>
                                    ` : ''}
                                    <div style="border-top: 1.5px solid #cbd5e1; padding-top: 6px; display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; color: #0284c7;">
                                        <span>Total:</span>
                                        <span>৳${parseFloat(s.total_amount).toFixed(2)}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; color: #059669; font-weight: 800;">
                                        <span>Paid (${s.payment_method || 'Cash'}):</span>
                                        <span>৳${parseFloat(s.paid_amount).toFixed(2)}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; color: ${parseFloat(s.due_amount) > 0 ? '#dc2626' : '#64748b'}; font-weight: 800;">
                                        <span>Due:</span>
                                        <span>৳${parseFloat(s.due_amount).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } catch(e) {
                printArea.innerHTML = '<div style="color: #ef4444; padding: 20px; text-align: center;">Failed to load invoice.</div>';
            }
        }

        function printDpInvoice() {
            const printContent = document.getElementById('printableDpInvoiceDoc');
            if (!printContent) return;
            const w = window.open('', '_blank');
            w.document.write(`
                <html>
                <head>
                    <title>DP Wholesale Invoice</title>
                    <style>
                        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; padding: 25px; color: #0f172a; margin: 0; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
                        th { background: #f8fafc; }
                    </style>
                </head>
                <body>
                    ${printContent.innerHTML}
                </body>
                </html>
            `);
            w.document.close();
            w.focus();
            setTimeout(() => { w.print(); w.close(); }, 300);
        }

        async function deleteDpSale(id, invoiceNo) {
            const ok = await showCustomConfirm({
                title: "Delete DP Sale?",
                message: `Are you sure you want to delete invoice <strong>'${invoiceNo}'</strong>?`,
                confirmText: "Yes, Delete",
                icon: "fa-trash-can"
            });
            if (!ok) return;

            try {
                const res = await fetch('api/dp-sales-manager.php?action=delete_dp_sale', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Invoice deleted successfully');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || 'Failed to delete invoice', false);
                }
            } catch(e) {
                showToast('Connection error', false);
            }
        }

        function filterDpSalesTable() {
            const q = (document.getElementById('dp-search-input')?.value || '').toLowerCase().trim();
            document.querySelectorAll('.dp-table-row').forEach(r => {
                const text = (r.getAttribute('data-search') || '').toLowerCase();
                r.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        }
    </script>

    <!-- MODAL: DP SALE INVOICE VIEWER -->
    <div id="dp-invoice-modal" class="custom-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; width: 95%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; animation: modalPop 0.2s ease;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #e0f2fe; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #bae6fd; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0369a1;" id="dpInvoiceModalTitle">Wholesale DP Invoice</h3>
                        <span style="font-size: 12.5px; color: #0284c7;" id="dpInvoiceModalSubtitle">Official B2B Dealer Invoice Receipt</span>
                    </div>
                </div>
                <button type="button" onclick="closeModal('dp-invoice-modal')" style="background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">&times;</button>
            </div>

            <div id="dpInvoicePrintArea" style="padding: 24px 26px;">
                <!-- Dynamically loaded invoice receipt -->
            </div>

            <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" onclick="closeModal('dp-invoice-modal')" style="padding: 9px 18px; border: 1.5px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 700; cursor: pointer;">Close</button>
                <button type="button" onclick="printDpInvoice()" style="padding: 9px 20px; background: #0284c7; color: white; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-print"></i> Print Invoice
                </button>
            </div>
        </div>
    </div>
</body>
</html>