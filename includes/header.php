<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';

$pageTitle = $pageTitle ?? 'BazarKinun.com - Online Grocery Shopping';

$settingsRaw = [];
try {
    $settingsRaw = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

$supportPhone = $settingsRaw['hotline_phone'] ?? '01919-668960';
$supportHours = $settingsRaw['support_hours_en'] ?? ($settingsRaw['order_hours_en'] ?? '24/7 Support Center');
$supportHoursBn = $settingsRaw['support_hours_bn'] ?? ($settingsRaw['order_hours_bn'] ?? '২৪/৭ সাপোর্ট সেন্টার');
$portalEmail = $settingsRaw['support_email'] ?? 'support@bazarkinun.com';
$altPhone = $settingsRaw['alt_phone'] ?? '01711-223344';
$whatsappNum = $settingsRaw['whatsapp_number'] ?? '8801919668960';
$facebookUrl = $settingsRaw['facebook_url'] ?? 'https://facebook.com';
$officeAddressEn = $settingsRaw['office_address_en'] ?? ($settingsRaw['office_address'] ?? 'Uttara, Dhaka-1230');
$officeAddressBn = $settingsRaw['office_address_bn'] ?? 'উত্তরা, ঢাকা-১২৩০';
$taglineEn = $settingsRaw['tagline_en'] ?? 'Skip the market hassle and get 100% fresh, healthy groceries';
$taglineBn = $settingsRaw['tagline_bn'] ?? 'বাজারের ঝামেলা ভুলে এক ক্লিকেই ঘরে আনুন সতেজ নিত্যপণ্য; পরিবারের সুস্বাস্থ্যে বাজারের সেরা মান!';
$deliveryTimeEstimateEn = $settingsRaw['delivery_time_estimate_en'] ?? '20-35 mins';
$deliveryTimeEstimateBn = $settingsRaw['delivery_time_estimate_bn'] ?? '২০-৩৫ মিনিট';
$broadcastActive = ($settingsRaw['broadcast_active'] ?? '1') === '1';
$broadcastEn = $settingsRaw['broadcast_notice_en'] ?? 'Welcome to BazarKinun.com! Free delivery on orders above ৳1000!';
$broadcastBn = $settingsRaw['broadcast_notice_bn'] ?? 'স্বাগতম BazarKinun.com এ! ১০০০ টাকার উপরে অর্ডারে ফ্রি ডেলিভারি!';

$customer = getLoggedInCustomer($pdo);
$isLoggedInPhp = ($customer !== null && !empty($customer['phone']));
$customerDisplayName = !empty($customer['name']) ? $customer['name'] : 'Customer';
$customerPhoto = !empty($customer['profile_photo']) ? $customer['profile_photo'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="<?= htmlspecialchars(generateCsrfToken()) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/bazarkinundotcom_logo.png">

    <!-- Google Fonts Outfit & Hind Siliguri -->
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="css/styles.css?v=13">
    
    <!-- Bangladesh Locations Dataset -->
    <script src="js/bd_locations.js"></script>
    <script>
        window.csrfToken = <?= json_encode(generateCsrfToken()) ?>;
        window.isBangla = (localStorage.getItem('appLanguage') === 'bn');
        window.serverCustomer = <?= json_encode($customer) ?>;
        if (window.serverCustomer && window.serverCustomer.phone) {
            localStorage.setItem('isLoggedIn', 'true');
            if (window.serverCustomer.name) localStorage.setItem('userName', window.serverCustomer.name);
            localStorage.setItem('userPhone', window.serverCustomer.phone);
            if (window.serverCustomer.profile_photo) localStorage.setItem('userAvatar', window.serverCustomer.profile_photo);
        }
        window.portalSettings = {
            hotlinePhone: <?= json_encode($supportPhone) ?>,
            altPhone: <?= json_encode($altPhone) ?>,
            whatsappNumber: <?= json_encode($whatsappNum) ?>,
            facebookUrl: <?= json_encode($facebookUrl) ?>,
            supportEmail: <?= json_encode($portalEmail) ?>,
            supportHoursEn: <?= json_encode($supportHours) ?>,
            supportHoursBn: <?= json_encode($supportHoursBn) ?>,
            officeAddressEn: <?= json_encode($officeAddressEn) ?>,
            officeAddressBn: <?= json_encode($officeAddressBn) ?>,
            taglineEn: <?= json_encode($taglineEn) ?>,
            taglineBn: <?= json_encode($taglineBn) ?>,
            deliveryTimeEstimateEn: <?= json_encode($deliveryTimeEstimateEn) ?>,
            deliveryTimeEstimateBn: <?= json_encode($deliveryTimeEstimateBn) ?>
        };

        window.normalizeBdPhone = function(phone) {
            if (!phone) return '';
            const bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            const en = ['0','1','2','3','4','5','6','7','8','9'];
            let str = String(phone).trim();
            for (let i = 0; i < 10; i++) {
                str = str.split(bn[i]).join(en[i]);
            }
            let digits = str.replace(/\D/g, '');
            if (!digits) return '';
            if (digits.startsWith('0088')) digits = digits.slice(4);
            if (digits.length === 13 && digits.startsWith('880')) digits = digits.slice(2);
            else if (digits.length === 14 && digits.startsWith('0880')) digits = digits.slice(3);
            else if (digits.length === 10 && /^[1-9]/.test(digits)) digits = '0' + digits;
            return digits;
        };

        window.isValidBdPhone = function(phone) {
            const clean = window.normalizeBdPhone(phone);
            return /^01[3-9]\d{8}$/.test(clean);
        };
    </script>

    <style>
        #toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .toast {
            background: #10b981;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            animation: slideDownFade 0.4s ease forwards;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        @keyframes slideDownFade {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            font-size: 22px;
            color: var(--text-dark);
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
        }
        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: flex !important;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .header-center-info, .header-auth-desktop, .location-details, .header-track-link span, .action-label, .wishlist-info, .wishlist-title, .cart-title, .cart-price, #profile-text, #profile-btn, #signup-btn-nav {
                display: none !important;
            }
            .header-location-item, .header-track-link {
                display: flex !important;
                align-items: center;
                justify-content: center;
                padding: 4px !important;
            }
            .header-track-link i, .header-location-item i {
                font-size: 20px !important;
                color: #0f172a !important;
            }
            .user-actions {
                gap: 12px !important;
            }
        }
        @media (max-width: 480px) {
            .user-actions {
                gap: 8px !important;
            }
            .user-actions .icon-wrapper, .header-track-link i, .mobile-menu-btn {
                font-size: 19px !important;
            }
        }
        @media (max-width: 374px) {
            .user-actions {
                gap: 6px !important;
            }
            .header-logo-img {
                height: 38px !important;
            }
            .header-logo-title {
                font-size: 19px !important;
            }
            .user-actions .icon-wrapper, .header-track-link i, .mobile-menu-btn {
                font-size: 17px !important;
            }
        }
    </style>
</head>
<body>
    <div id="toast-container"></div>

    <!-- Top Logged Out Sign Up Bar -->
    <div id="signup-notification"
        style="display: none; background: #e2e8f0; color: #475569; text-align: center; padding: 6px 15px; font-weight: 500; font-size: 13px; z-index: 1002; position: relative; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border-bottom: 1px solid #cbd5e1;">
        <span style="flex: 1; text-align: center;" class="dynamic-lang"
              data-en='Please <a href="signup.php" style="color: var(--primary-color); font-weight: bold; text-decoration: underline;">sign up</a> to place an order and unlock more features!'
              data-bn='অর্ডার করতে এবং আরও সুবিধা পেতে অনুগ্রহ করে <a href="signup.php" style="color: var(--primary-color); font-weight: bold; text-decoration: underline;">সাইন আপ</a> করুন!'>
            Please <a href="signup.php" style="color: var(--primary-color); font-weight: bold; text-decoration: underline;">sign up</a> to place an order and unlock more features!
        </span>
        <button type="button" onclick="document.getElementById('signup-notification').style.display='none'"
            style="background: transparent; border: none; cursor: pointer; color: #6b7280; font-size: 14px; padding: 0 5px; margin-left: auto; display: flex; align-items: center;" title="Dismiss">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <?php if ($broadcastActive): ?>
        <!-- Top Live Announcement Ticker -->
        <div id="broadcast-top-banner" style="background: linear-gradient(90deg, #15803d 0%, #16a34a 50%, #15803d 100%); color: white; padding: 7px 16px; font-size: 13px; font-weight: 700; text-align: center; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.06); z-index: 1001; position: relative;">
            <i class="fa-solid fa-bullhorn" style="color: #fef08a; font-size: 12px; animation: pulse 1.5s infinite;"></i>
            <span class="dynamic-lang" data-en="<?= htmlspecialchars($broadcastEn) ?>" data-bn="<?= htmlspecialchars($broadcastBn) ?>"><?= htmlspecialchars($broadcastEn) ?></span>
        </div>
    <?php endif; ?>

    <script>
        let lastKnownHeaderHeight = 0;
        function syncHeaderHeight() {
            const header = document.querySelector('header.header');
            if (header) {
                const height = Math.round(header.getBoundingClientRect().height);
                if (height > 0 && Math.abs(height - lastKnownHeaderHeight) > 1) {
                    lastKnownHeaderHeight = height;
                    document.documentElement.style.setProperty('--header-actual-height', height + 'px');
                }
            }
        }
        window.addEventListener('load', syncHeaderHeight);
        window.addEventListener('resize', syncHeaderHeight);
        document.addEventListener('DOMContentLoaded', syncHeaderHeight);
    </script>

    <!-- Header Section -->
    <header class="header" id="home" style="position: sticky; top: 0; z-index: 1000; background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
        <div class="container header-top" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; gap: 15px;">
            <!-- Brand Logo -->
            <div class="logo" style="cursor: pointer; display: flex; align-items: center; gap: 10px; flex-shrink: 0;" onclick="window.location.href='index.php';">
                <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" class="header-logo-img" style="height: 46px; width: auto;" onerror="this.src='https://placehold.co/120x46?text=BazarKinun.com';">
                <div class="logo-text">
                    <h1 class="header-logo-title" style="font-size: 26px; font-weight: 800; margin: 0; line-height: 1.1; white-space: nowrap;">
                        <span style="color: #15803d;">Bazar</span><span style="color: #f97316;">Kinun</span><span style="color: #15803d;">.com</span>
                    </h1>
                    <span class="logo-subtitle" style="font-size: 11px; letter-spacing: 1.5px; font-weight: 700; color: #64748b; display: block;">GROCERY</span>
                </div>
            </div>

            <!-- Hotline & Language Toggle -->
            <div class="header-center-info" style="flex: 1; display: flex; justify-content: center; align-items: center; gap: 20px; flex-shrink: 0; white-space: nowrap;">
                <div class="contact" style="display: flex; align-items: center; gap: 10px; flex-shrink: 0; white-space: nowrap;">
                    <i class="fa-solid fa-phone" style="font-size: 20px; color: #1e293b;"></i>
                    <div style="display: flex; flex-direction: column; white-space: nowrap;">
                        <span style="font-size: 16px; font-weight: 900; color: var(--primary-color); line-height: 1.1; white-space: nowrap;"><?= htmlspecialchars($supportPhone) ?></span>
                        <small style="font-size: 11px; color: #64748b; font-weight: 600; white-space: nowrap;" class="dynamic-lang" data-en="<?= htmlspecialchars($supportHours) ?>" data-bn="<?= htmlspecialchars($supportHoursBn) ?>"><?= htmlspecialchars($supportHours) ?></small>
                    </div>
                </div>
                <div class="lang-toggle" style="flex-shrink: 0;">
                    <button id="lang-btn" type="button" style="padding: 6px 14px; border-radius: 6px; border: 1px solid var(--primary-color); background: var(--primary-color); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; font-family: var(--font-family); font-size: 13px; white-space: nowrap;">বাংলা</button>
                </div>
            </div>

            <!-- Header Action Items -->
            <div class="user-actions" style="display: flex; align-items: center; gap: 12px; flex-shrink: 0; white-space: nowrap;">
                <!-- Location Selector -->
                <a href="javascript:void(0)" onclick="openLocationModal()" class="action-item header-location-item" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px; padding: 2px 4px;" title="Manage Delivery Address">
                    <div class="icon-wrapper" style="font-size: 20px; color: #0f172a; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="location-details" style="display: flex; flex-direction: column; text-align: left; line-height: 1.15;">
                        <span id="nav-location-keyword" style="font-size: 13.5px; font-weight: 800; color: #0f172a; white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis; letter-spacing: -0.2px;">Delivery</span>
                        <span id="nav-location-text" style="font-size: 11.5px; font-weight: 600; color: #64748b; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Add Location</span>
                    </div>
                </a>

                <!-- Track Order -->
                <a href="javascript:void(0)" onclick="openTrackOrderModal()" class="action-item header-track-link" style="background: transparent; color: var(--text-dark); border: none; padding: 2px 4px; font-size: 14px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 6px;" title="Track Order">
                    <div class="icon-wrapper" style="font-size: 20px; color: #0f172a; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <span class="dynamic-lang" data-en="Track Order" data-bn="অর্ডার ট্র্যাক করুন">Track Order</span>
                </a>

                <!-- Wishlist Icon & Label -->
                <a href="wishlist.php" class="action-item" id="nav-wishlist-link" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px; padding: 4px;" title="Wishlist">
                    <div class="icon-wrapper" style="font-size: 20px; color: #334155; position: relative;">
                        <i class="fa-regular fa-heart"></i>
                        <div class="badge" id="wishlist-badge" style="position: absolute; top: -6px; right: -8px; background: #f97316; color: white; font-size: 11px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800;">0</div>
                    </div>
                    <div class="wishlist-info" style="display: flex; flex-direction: column;">
                        <span class="wishlist-title dynamic-lang" data-en="My wishlist" data-bn="আমার উইশলিস্ট" style="font-size: 11px; color: #64748b; font-weight: 600; white-space: nowrap;">My wishlist</span>
                    </div>
                </a>

                <!-- Cart Icon & Price -->
                <a href="cart.php" class="action-item" id="nav-cart-link" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px; padding: 4px;" title="Cart">
                    <div class="icon-wrapper" style="font-size: 20px; color: var(--primary-color); position: relative;">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <div class="badge" id="cart-badge" style="position: absolute; top: -6px; right: -8px; background: #ef4444; color: white; font-size: 11px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800;">0</div>
                    </div>
                    <div class="cart-info" style="display: flex; flex-direction: column;">
                        <span class="cart-title dynamic-lang" data-en="My cart" data-bn="আমার কার্ট" style="font-size: 11px; color: #64748b; font-weight: 600;">My cart</span>
                        <span class="cart-price" id="cart-total-price" style="font-size: 14px; font-weight: 800; color: var(--primary-color);">৳0</span>
                    </div>
                </a>

                <!-- Auth Buttons Wrapper (Desktop Only) -->
                <div class="header-auth-desktop" style="display: flex; align-items: center;">
                    <!-- Auth Buttons: Logged Out -->
                    <a href="login.php" class="action-item profile" id="signup-btn-nav" style="<?= $isLoggedInPhp ? 'display: none;' : 'display: flex;' ?> background: var(--primary-color); color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 14px;">
                        <i class="fa-solid fa-user" style="margin-right: 6px;"></i> <span class="dynamic-lang" data-en="Login" data-bn="লগইন">Login</span>
                    </a>

                    <!-- Auth Buttons: Logged In User Dropdown -->
                    <div class="action-item profile" id="profile-btn" style="position: relative; cursor: pointer; <?= $isLoggedInPhp ? 'display: flex;' : 'display: none;' ?> align-items: center; gap: 8px;">
                        <div class="avatar" id="profile-avatar" style="width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #e2e8f0;">
                            <?php if (!empty($customerPhoto)): ?>
                                <img src="<?= htmlspecialchars($customerPhoto) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-user" style="color: #64748b; font-size: 18px;"></i>
                            <?php endif; ?>
                        </div>
                        <span id="profile-text" style="font-size: 14px; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($customerDisplayName) ?> <i class="fa-solid fa-chevron-down" style="font-size: 11px; margin-left: 4px;"></i></span>
                        <ul class="profile-dropdown" id="profile-dropdown" style="display: none; position: absolute; top: 120%; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 8px 0; width: 170px; list-style: none; z-index: 1000;">
                            <li>
                                <a href="profile.php?tab=profile" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #1e293b; text-decoration: none; font-size: 13px; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                                    <i class="fa-regular fa-id-badge" style="color: var(--primary-color);"></i>
                                    <span class="dynamic-lang" data-en="View Profile" data-bn="প্রোফাইল দেখুন">View Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" onclick="toggleLoginState()" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600;">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span class="dynamic-lang" data-en="Sign out" data-bn="লগআউট">Sign out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button type="button" id="mobile-menu-toggle" onclick="toggleMobileDrawer()" class="mobile-menu-btn" title="Open Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Overlay & Sidebar -->
    <div id="mobile-drawer-overlay" onclick="toggleMobileDrawer()" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; height: 100dvh; background: rgba(0,0,0,0.5); z-index: 2100; backdrop-filter: blur(2px);"></div>
    <div id="mobile-drawer" style="position: fixed; top: 0; bottom: 0; right: -300px; width: 280px; max-width: 85vw; height: 100vh; height: 100dvh; background: white; z-index: 2200; box-shadow: -5px 0 25px rgba(0,0,0,0.15); transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; overflow-y: auto;">
        <div style="padding: 14px 20px; border-bottom: 1px solid #f1f1f1; background: #fafafa; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="height: 32px; width: auto;" onerror="this.src='https://placehold.co/80x32?text=BazarKinun.com';">
                <span style="font-weight: 800; color: #0f172a; font-size: 16px;"><span style="color: #15803d;">Bazar</span><span style="color: #f97316;">Kinun</span><span style="color: #15803d;">.com</span></span>
            </div>
            <button onclick="toggleMobileDrawer()" style="background: transparent; border: none; font-size: 20px; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <!-- Mobile Drawer Profile Section -->
        <div id="drawer-profile-card" style="padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; <?= $isLoggedInPhp ? 'display: flex;' : 'display: none;' ?> align-items: center; justify-content: space-between; gap: 12px;">
            <a href="profile.php?tab=profile" onclick="toggleMobileDrawer()" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; flex: 1; min-width: 0;">
                <div class="avatar" id="drawer-profile-avatar" style="width: 42px; height: 42px; border-radius: 50%; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid var(--primary-color); flex-shrink: 0;">
                    <?php if (!empty($customerPhoto)): ?>
                        <img src="<?= htmlspecialchars($customerPhoto) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fa-solid fa-user" style="color: #64748b; font-size: 18px;"></i>
                    <?php endif; ?>
                </div>
                <div style="display: flex; flex-direction: column; min-width: 0;">
                    <span id="drawer-profile-name" style="font-size: 14px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($customerDisplayName) ?></span>
                    <span style="font-size: 11.5px; color: var(--primary-color); font-weight: 700;" class="dynamic-lang" data-en="View Profile" data-bn="প্রোফাইল দেখুন">View Profile</span>
                </div>
            </a>
            <a href="javascript:void(0)" onclick="toggleLoginState()" style="color: #ef4444; font-size: 14px; font-weight: 700; text-decoration: none; padding: 6px 8px; border-radius: 6px; display: flex; align-items: center; gap: 4px;" title="Sign out">
                <i class="fa-solid fa-sign-out-alt"></i>
            </a>
        </div>
        <div id="drawer-login-card" style="padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; <?= $isLoggedInPhp ? 'display: none;' : 'display: block;' ?>">
            <a href="login.php" onclick="toggleMobileDrawer()" style="background: var(--primary-color); color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fa-solid fa-user"></i> <span class="dynamic-lang" data-en="Login / Sign Up" data-bn="লগইন / সাইন আপ">Login / Sign Up</span>
            </a>
        </div>

        <!-- Drawer Links -->
        <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px; flex: 1;">
            <button onclick="toggleMobileDrawer(); openLocationModal();" style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 10px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <i class="fa-solid fa-location-dot" style="color: var(--primary-color);"></i>
                <span id="mobile-drawer-loc-text" class="dynamic-lang" data-en="Add Location" data-bn="লোকেশন যুক্ত করুন">Add Location</span>
            </button>
            <button onclick="toggleMobileDrawer(); openTrackOrderModal();" style="background: white; border: 1px solid #e2e8f0; color: #1e293b; padding: 10px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; text-align: left; display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <i class="fa-solid fa-truck-fast" style="color: #3b82f6;"></i>
                <span class="dynamic-lang" data-en="Track Order" data-bn="অর্ডার ট্র্যাক করুন">Track Order</span>
            </button>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0;">
                <span class="dynamic-lang" data-en="Language:" data-bn="ভাষা:" style="font-weight: 700; font-size: 13px; color: #475569;">Language:</span>
                <button id="mobile-lang-btn" type="button" style="padding: 6px 14px; border-radius: 6px; border: 1px solid var(--primary-color); background: var(--primary-color); color: white; font-weight: 700; font-size: 12px; cursor: pointer;">বাংলা</button>
            </div>

            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 6px 0;">

            <?php 
            $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
            if ($currentScript !== 'index.php'): 
            ?>
            <a href="index.php" id="drawer-home-link" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-solid fa-house" style="color: var(--primary-color); width: 18px;"></i> <span class="dynamic-lang" data-en="Home" data-bn="হোম">Home</span>
            </a>
            <?php endif; ?>
            <?php if ($currentScript !== 'index.php'): ?>
            <a href="shop.php" id="drawer-shop-link" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-solid fa-store" style="color: var(--primary-color); width: 18px;"></i> <span class="dynamic-lang" data-en="All Products" data-bn="সকল পণ্য">All Products</span>
            </a>
            <?php endif; ?>
            <a href="write_list.php" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-solid fa-pen-to-square" style="color: var(--primary-color); width: 18px;"></i> <span class="dynamic-lang" data-en="Write Grocery List" data-bn="লিখে বাজার লিস্ট">Write Grocery List</span>
            </a>
            <a href="upload_list.php" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-solid fa-camera" style="color: var(--primary-color); width: 18px;"></i> <span class="dynamic-lang" data-en="Upload List Photo" data-bn="ছবির মাধ্যমে লিস্ট">Upload List Photo</span>
            </a>
            <a href="wishlist.php" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-regular fa-heart" style="color: #ef4444; width: 18px;"></i> <span class="dynamic-lang" data-en="My Wishlist" data-bn="আমার পছন্দের তালিকা">My Wishlist</span>
            </a>
            <a href="cart.php" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-solid fa-cart-shopping" style="color: var(--primary-color); width: 18px;"></i> <span class="dynamic-lang" data-en="My Cart" data-bn="আমার কার্ট">My Cart</span>
            </a>
            <a href="profile.php" onclick="toggleMobileDrawer()" style="color: #1e293b; font-weight: 600; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                <i class="fa-regular fa-id-badge" style="color: var(--primary-color); width: 18px;"></i> <span class="dynamic-lang" data-en="My Account & Orders" data-bn="আমার অ্যাকাউন্ট ও অর্ডার">My Account & Orders</span>
            </a>
        </div>
    </div>
