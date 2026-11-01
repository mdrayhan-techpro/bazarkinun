<?php
// includes/footer.php
if (!isset($footerSettingsRaw)) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/../config/db.php';
        }
        $footerSettingsRaw = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $footerSettingsRaw = [];
    }
}
$fAboutEn = $footerSettingsRaw['about_us_en'] ?? "Skip the market hassle—let the market come to your home.<br><br>BazarKinun.com is a modern hyper-local grocery platform dedicated to sourcing farm-fresh produce, local fish, quality meat, and essential groceries directly from trusted wholesale markets to your doorstep.";
$fAboutBn = $footerSettingsRaw['about_us_bn'] ?? "বাজারে নয়, বাজার আসবে আপনার ঘরে।<br><br>BazarKinun.com একটি আধুনিক হাইপার-লোকাল গ্রোসারি প্ল্যাটফর্ম। প্রতিদিনের টাটকা শাকসবজি, দেশি মাছ, মাংস ও খাঁটি মুদিপণ্য সরাসরি মাঠ ও নির্ভরযোগ্য পাইকারি বাজার থেকে বাছাই করে দ্রুততম সময়ে আপনার ঘরে পৌঁছে দেওয়াই আমাদের লক্ষ্য।";

$fPrivacyEn = $footerSettingsRaw['privacy_policy_en'] ?? "We value your trust and are committed to protecting your personal information. When you use BazarKinun.com, we only collect essential data required to process and deliver your grocery orders safely.";
$fPrivacyBn = $footerSettingsRaw['privacy_policy_bn'] ?? "আমরা আপনার বিশ্বাসের মূল্য দিই এবং আপনার ব্যক্তিগত তথ্য রক্ষা করতে প্রতিশ্রুতিবদ্ধ। BazarKinun.com ব্যবহার করার সময় আমরা শুধুমাত্র আপনার বাজার সরবরাহ নিশ্চিত করার জন্য প্রয়োজনীয় তথ্য সংগ্রহ করি।";

$fTermsEn = $footerSettingsRaw['terms_conditions_en'] ?? "By placing an order on BazarKinun.com, you agree to our standard terms of service. All orders are subject to product availability and confirmation by our store managers.";
$fTermsBn = $footerSettingsRaw['terms_conditions_bn'] ?? "BazarKinun.com এ অর্ডার করার মাধ্যমে আপনি আমাদের সাধারণ শর্তাবলীতে সম্মতি প্রদান করছেন। সমস্ত অর্ডার পণ্যের প্রাপ্যতা এবং আমাদের স্টোর ম্যানেজারের নিশ্চিতকরণের সাপেক্ষে কার্যকর হবে।";

$fRefundEn = $footerSettingsRaw['refund_policy_en'] ?? "If you receive any damaged or unsatisfactory fresh items, you can inspect and return them immediately to our delivery rider at the doorstep for instant replacement or refund.";
$fRefundBn = $footerSettingsRaw['refund_policy_bn'] ?? "কোনো পণ্য ক্ষতিগ্রস্ত বা মানসম্মত না হলে, আপনি ডেলিভারির সময়ই রাইডারের কাছে তাৎক্ষণিক ফেরত দিয়ে বদল বা রিফান্ড নিতে পারেন।";

$fTaglineEn = $footerSettingsRaw['tagline_en'] ?? "Skip the market hassle and get 100% fresh, healthy groceries delivered straight to your doorstep in record time. Your everyday trusted market: BazarKinun.com";
$fTaglineBn = $footerSettingsRaw['tagline_bn'] ?? "বাজারের ঝামেলা ভুলে এক ক্লিকেই ঘরে আনুন সতেজ নিত্যপণ্য; পরিবারের সুস্বাস্থ্যে বাজারের সেরা মান!";
$fOfficeAddressEn = $footerSettingsRaw['office_address_en'] ?? ($footerSettingsRaw['office_address'] ?? 'Uttara, Dhaka-1230');
$fOfficeAddressBn = $footerSettingsRaw['office_address_bn'] ?? 'উত্তরা, ঢাকা-১২৩০';
$fHotlinePhone = $footerSettingsRaw['hotline_phone'] ?? '01919-668960';
$fWhatsappNum = $footerSettingsRaw['whatsapp_number'] ?? '8801919668960';
$fFacebookUrl = $footerSettingsRaw['facebook_url'] ?? 'https://facebook.com';
$fSupportEmail = $footerSettingsRaw['support_email'] ?? 'support@bazarkinun.com';
$fSupportHoursEn = $footerSettingsRaw['support_hours_en'] ?? ($footerSettingsRaw['order_hours_en'] ?? '24/7 Support Center');
$fSupportHoursBn = $footerSettingsRaw['support_hours_bn'] ?? ($footerSettingsRaw['order_hours_bn'] ?? '২৪/৭ সাপোর্ট সেন্টার');
$fDeliveryTimeEstimateEn = $footerSettingsRaw['delivery_time_estimate_en'] ?? '20-35 mins';
$fDeliveryTimeEstimateBn = $footerSettingsRaw['delivery_time_estimate_bn'] ?? '২০-৩৫ মিনিট';
?>
    <!-- Main Footer -->
    <footer class="footer" style="background: #ffffff; border-top: 1px solid #e2e8f0; margin-top: 0; padding-top: 28px;">
        <div class="container footer-top" style="display: flex; flex-wrap: wrap; gap: 24px; justify-content: space-between; padding-bottom: 16px;">
            <!-- Column 1: Brand & Social -->
            <div class="footer-col" style="flex: 1.2; min-width: 260px;">
                <div class="logo" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="height: 38px; width: auto;" onerror="this.src='https://placehold.co/120x42?text=BazarKinun.com';">
                    <h2 class="footer-logo-title" style="font-size: 24px; font-weight: 800; margin: 0; line-height: 1.1; white-space: nowrap;">
                        <span style="color: #15803d;">Bazar</span><span style="color: #f97316;">Kinun</span><span style="color: #15803d;">.com</span>
                    </h2>
                </div>
                <p class="dynamic-lang" 
                   data-en="<?= htmlspecialchars($fTaglineEn) ?>" 
                   data-bn="<?= htmlspecialchars($fTaglineBn) ?>"
                   style="color: #64748b; font-size: 13px; max-width: 290px; line-height: 1.5; margin-bottom: 10px;">
                    <?= htmlspecialchars($fTaglineEn) ?>
                </p>
                <div style="font-size: 13px; color: #475569; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-location-dot" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="<?= htmlspecialchars($fOfficeAddressEn) ?>" data-bn="<?= htmlspecialchars($fOfficeAddressBn) ?>"><?= htmlspecialchars($fOfficeAddressEn) ?></span>
                </div>
                <div class="footer-social" style="display: flex; gap: 10px;">
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', (strpos($fWhatsappNum, '88') === 0 ? $fWhatsappNum : ('88' . $fWhatsappNum))) ?>?text=Hello%20BazarKinun" target="_blank" title="WhatsApp Support" style="width: 32px; height: 32px; border-radius: 50%; background: #25D366; color: white; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 15px;">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                    <a href="<?= htmlspecialchars($fFacebookUrl) ?>" target="_blank" title="Facebook Page" style="width: 32px; height: 32px; border-radius: 50%; background: #1877F2; color: white; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 14px;">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $fHotlinePhone) ?>" title="Call Hotline: <?= htmlspecialchars($fHotlinePhone) ?>" style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); color: white; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 13px;">
                        <i class="fa-solid fa-phone"></i>
                    </a>
                    <a href="javascript:void(0)" onclick="openLegalModal('about')" title="About Us" style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 13px;">
                        <i class="fa-solid fa-circle-info"></i>
                    </a>
                </div>
            </div>

            <!-- Column 2: Shopping & Services -->
            <div class="footer-col" style="flex: 1; min-width: 170px;">
                <h4 class="dynamic-lang" data-en="Shopping & Services" data-bn="কেনাকাটা ও সেবা" style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Shopping & Services</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="shop.php" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-store" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="All Products" data-bn="সকল পণ্য">All Products</span></a></li>
                    <li><a href="write_list.php" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-pen-to-square" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Write Grocery List" data-bn="লিখে বাজার লিস্ট">Write Grocery List</span></a></li>
                    <li><a href="upload_list.php" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-camera" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Upload List Photo" data-bn="ছবির মাধ্যমে লিস্ট">Upload List Photo</span></a></li>
                    <li><a href="cart.php" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-cart-shopping" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Shopping Cart" data-bn="শপিং কার্ট">Shopping Cart</span></a></li>
                    <li><a href="wishlist.php" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-heart" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Wishlist" data-bn="পছন্দের তালিকা">Wishlist</span></a></li>
                </ul>
            </div>

            <!-- Column 3: Customer Account -->
            <div class="footer-col" style="flex: 1; min-width: 170px;">
                <h4 class="dynamic-lang" data-en="Customer Account" data-bn="কাস্টমার অ্যাকাউন্ট" style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Customer Account</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="profile.php?tab=order" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-box-archive" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="View Orders" data-bn="অর্ডারসমূহ দেখুন">View Orders</span></a></li>
                    <li><a href="javascript:void(0)" onclick="openTrackOrderModal()" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-truck-fast" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Track Order" data-bn="অর্ডার ট্র্যাক করুন">Track Order</span></a></li>
                    <li><a href="profile.php?tab=invoice" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-file-invoice-dollar" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Money Receipts & Invoices" data-bn="মানি রিসিট ও ভাউচার">Money Receipts & Invoices</span></a></li>
                    <li><a href="profile.php?tab=list" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-list-check" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Saved Grocery Lists" data-bn="সংরক্ষিত বাজার লিস্ট">Saved Grocery Lists</span></a></li>
                    <li><a href="javascript:void(0)" onclick="openLocationModal()" style="color: #64748b; text-decoration: none; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-map-location-dot" style="font-size: 12px; color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Add Delivery Address" data-bn="ডেলিভারি ঠিকানা যুক্ত করুন">Add Delivery Address</span></a></li>
                </ul>
            </div>

            <!-- Column 4: Help & Support -->
            <div class="footer-col" style="flex: 1.15; min-width: 210px;">
                <h4 class="dynamic-lang" data-en="Help & Support" data-bn="সাহায্য ও সাপোর্ট" style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Help & Support</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="tel:<?= preg_replace('/[^0-9+]/', '', $fHotlinePhone) ?>" style="color: var(--primary-color); font-weight: 800; font-size: 14.5px; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-headset"></i> <?= htmlspecialchars($fHotlinePhone) ?></a></li>
                    <li style="color: #64748b; font-size: 13px; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-clock" style="font-size: 12px;"></i> <span class="dynamic-lang" data-en="<?= htmlspecialchars($fSupportHoursEn) ?>" data-bn="<?= htmlspecialchars($fSupportHoursBn) ?>"><?= htmlspecialchars($fSupportHoursEn) ?></span></li>
                    <li><a href="mailto:<?= htmlspecialchars($fSupportEmail) ?>" style="color: #64748b; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 8px;"><i class="fa-regular fa-envelope" style="font-size: 12px;"></i> <?= htmlspecialchars($fSupportEmail) ?></a></li>
                    <li style="display: flex; align-items: center; gap: 10px; margin-top: 2px;">
                        <a href="javascript:void(0)" onclick="openLegalModal('about')" style="color: #64748b; font-size: 13px; text-decoration: none;"><span class="dynamic-lang" data-en="About Us" data-bn="আমাদের সম্পর্কে">About Us</span></a>
                        <span style="color: #cbd5e1;">|</span>
                        <a href="javascript:void(0)" onclick="openLegalModal('terms')" style="color: #64748b; font-size: 13px; text-decoration: none;"><span class="dynamic-lang" data-en="Terms" data-bn="শর্তাবলি">Terms</span></a>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px;">
                        <a href="javascript:void(0)" onclick="openLegalModal('privacy')" style="color: #64748b; font-size: 13px; text-decoration: none;"><span class="dynamic-lang" data-en="Privacy Policy" data-bn="প্রাইভেসি পলিসি">Privacy Policy</span></a>
                        <span style="color: #cbd5e1;">|</span>
                        <a href="javascript:void(0)" onclick="openLegalModal('refund')" style="color: #64748b; font-size: 13px; text-decoration: none;"><span class="dynamic-lang" data-en="Refund" data-bn="রিফান্ড নীতি">Refund</span></a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Copyright & Payments Bar -->
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 0;">
            <div class="container footer-bottom" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; padding-top: 0; border-top: none;">
                <div class="footer-copy" style="font-size: 13px; color: #64748b;">
                    <p class="dynamic-lang" data-en="BazarKinun.com © 2026, All Rights Reserved" data-bn="BazarKinun.com © ২০২৬, সর্বস্বত্ব সংরক্ষিত" style="margin: 0;">BazarKinun.com &copy; 2026, All Rights Reserved</p>
                </div>
                <div class="footer-developer" style="font-size: 13px; color: #475569;">
                    <p style="margin: 0;">
                        <span class="dynamic-lang" data-en="Developed by Md. Rayhan" data-bn="ডেভেলপ করেছেন মোঃ রায়হান">Developed by Md. Rayhan</span> | 
                        <a href="portal_login.php" style="color: #64748b; text-decoration: none; font-weight: 600;">
                            <i class="fa-solid fa-shield-halved"></i> <span class="dynamic-lang" data-en="Staff / Portal Login" data-bn="স্টাফ / পোর্টাল লগইন">Staff / Portal Login</span>
                        </a>
                    </p>
                </div>
                <div class="footer-payments" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="pay-tile" title="Cash on Delivery">
                        <img src="assets/payments/cod.svg" alt="Cash on Delivery">
                    </span>
                    <span class="pay-tile" title="bKash">
                        <img src="assets/payments/bkash.png" alt="bKash">
                    </span>
                    <span class="pay-tile" title="Nagad">
                        <img src="assets/payments/nagad.png" alt="Nagad">
                    </span>
                    <span class="pay-tile" title="Rocket">
                        <img src="assets/payments/rocket.png" alt="Rocket">
                    </span>
                    <span class="pay-tile" title="Visa">
                        <i class="fa-brands fa-cc-visa" style="color: #1a1f71; font-size: 24px;"></i>
                    </span>
                    <span class="pay-tile" title="Mastercard">
                        <i class="fa-brands fa-cc-mastercard" style="color: #eb001b; font-size: 24px;"></i>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Location Modal -->
    <!-- Manage Addresses Modal (Updated to match exact screenshot design) -->
    <div id="location-modal" style="display: none; position: fixed; inset: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.45); z-index: 3000; align-items: flex-start; justify-content: center; overflow-y: auto; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); padding: 40px 15px; box-sizing: border-box;">
        <div style="background: white; width: 100%; max-width: 905px; border-radius: 20px; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); display: flex; flex-direction: column; margin: auto; overflow: hidden;">
            
            <!-- Modal Top Header -->
            <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-map-location-dot" style="color: #00c675; font-size: 22px;"></i>
                    <h3 style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;" class="dynamic-lang" data-en="Manage Addresses" data-bn="ঠিকানা পরিচালনা করুন">Manage Addresses</h3>
                </div>
                <button onclick="closeLocationModal()" style="background: transparent; border: none; font-size: 22px; color: #64748b; cursor: pointer; padding: 4px 8px; border-radius: 6px; line-height: 1;">&times;</button>
            </div>

            <!-- Modal Body (Natural Full Height) -->
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
                
                <!-- 1. Saved Locations -->
                <div>
                    <h4 style="margin: 0 0 12px 0; font-size: 14.5px; font-weight: 800; color: #0f172a;" class="dynamic-lang" data-en="Saved Locations" data-bn="সংরক্ষিত ঠিকানাসমূহ">Saved Locations</h4>
                    <div style="display: flex; gap: 10px; width: 100%;" id="saved-locations-cards-container">
                        <!-- Dynamically populated by renderSavedLocations() -->
                    </div>
                </div>

                <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 0;">

                <!-- 2. Save As Chips -->
                <div>
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 800; color: #0f172a;" class="dynamic-lang" data-en="Save As" data-bn="সেভ করুন হিসেবে">Save As</h4>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;" id="save-as-chips-container">
                        <button type="button" class="loc-keyword-btn active" onclick="selectKeyword(this, 'Home')" style="padding: 8px 18px; border-radius: 50px; border: 1.5px solid #00c675; background: #00c675; color: white; cursor: pointer; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-house"></i> Home
                        </button>
                        <button type="button" class="loc-keyword-btn" onclick="selectKeyword(this, 'Work')" style="padding: 8px 18px; border-radius: 50px; border: 1.5px solid #e2e8f0; background: white; color: #334155; cursor: pointer; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-briefcase"></i> Work
                        </button>
                        <button type="button" class="loc-keyword-btn" onclick="selectKeyword(this, 'Partner')" style="padding: 8px 18px; border-radius: 50px; border: 1.5px solid #e2e8f0; background: white; color: #334155; cursor: pointer; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-heart"></i> Partner
                        </button>
                        <button type="button" id="add-custom-tag-btn" onclick="toggleCustomKeywordInput()" title="Add Custom Tag" style="width: 36px; height: 36px; border-radius: 50%; border: none; background: #00c675; color: white; cursor: pointer; font-size: 20px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0, 198, 117, 0.3); transition: transform 0.2s;">
                            +
                        </button>
                    </div>

                    <!-- Inline Custom Name Input Box (appears on clicking + button) -->
                    <div id="custom-keyword-box" style="display: none; margin-top: 10px;">
                        <input type="text" id="custom-keyword-input" placeholder="Enter custom name (e.g. Gym, Mom's House)" oninput="handleCustomKeywordInput(this.value)" onkeydown="handleCustomKeywordEnter(event)" style="width: 100%; box-sizing: border-box; padding: 11px 16px; border: 1.5px solid #cbd5e1; border-radius: 12px; font-size: 14px; outline: none; color: #0f172a; font-weight: 600; transition: border-color 0.2s;">
                    </div>
                </div>

                <!-- 3. Country Dropdown -->
                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 800; color: #0f172a; margin-bottom: 6px;" class="dynamic-lang" data-en="Country" data-bn="দেশ">Country</label>
                    <select id="loc-country" style="width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; color: #0f172a; background: white; outline: none; font-weight: 600;">
                        <option value="Bangladesh" selected>Bangladesh</option>
                    </select>
                </div>

                <!-- 4. Search Location -->
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px;" class="dynamic-lang" data-en="Search Location" data-bn="লোকেশন সার্চ করুন">Search Location</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 14px; color: #94a3b8; font-size: 15px;"></i>
                        <input type="text" id="loc-search-input" value="" onkeyup="handleLocationSearch(event)" placeholder="Search address or landmark..." style="width: 100%; box-sizing: border-box; padding: 11px 16px 11px 44px; border: 1.5px solid #00c675; border-radius: 12px; font-size: 14px; color: #0f172a; outline: none; font-weight: 600;">
                    </div>
                    
                    <!-- Use my current location button -->
                    <button type="button" onclick="useCurrentLocation()" style="width: 100%; margin-top: 10px; background: white; border: 1.5px solid #00c675; color: #00c675; padding: 10px 16px; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s;">
                        <i class="fa-solid fa-crosshairs"></i> <span class="dynamic-lang" data-en="Use my current location" data-bn="আমার বর্তমান অবস্থান ব্যবহার করুন">Use my current location</span>
                    </button>
                </div>

                <!-- 5. Select your Location & Map -->
                <div>
                    <h4 style="margin: 0 0 2px 0; font-size: 14px; font-weight: 800; color: #0f172a;" class="dynamic-lang" data-en="Select your Location" data-bn="আপনার অবস্থান নির্বাচন করুন">Select your Location</h4>
                    <p style="margin: 0 0 10px 0; font-size: 13px; color: #64748b;" class="dynamic-lang" data-en="Pinpoint your exact address on the map below for faster delivery." data-bn="দ্রুত ডেলিভারির জন্য নিচের ম্যাপে সঠিক ঠিকানা পিন করুন।">Pinpoint your exact address on the map below for faster delivery.</p>
                    
                    <div style="border-radius: 14px; overflow: hidden; border: 1.5px solid #e2e8f0; height: 310px; position: relative;">
                        <div id="nav-leaflet-map" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>

                <!-- 6. Selected Address Card -->
                <div style="background: #ffffff; border: 1.5px solid #dcfce7; border-radius: 16px; padding: 18px 20px; box-shadow: 0 4px 15px rgba(0, 198, 117, 0.06);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 14.5px; color: #0f172a;">
                            <i class="fa-solid fa-location-dot" style="color: #00c675;"></i>
                            <span class="dynamic-lang" data-en="Selected Address" data-bn="নির্বাচিত ঠিকানা">Selected Address</span>
                        </div>
                        <button type="button" onclick="clearSelectedAddress()" style="background: none; border: none; color: #ef4444; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            ✕ Clear
                        </button>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: flex-start; margin-bottom: 14px;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; color: #00c675; font-size: 16px; flex-shrink: 0; margin-top: 2px;">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <h4 id="sel-address-title" style="margin: 0 0 3px 0; font-size: 15px; font-weight: 800; color: #0f172a;" class="dynamic-lang" data-en="No Address Selected" data-bn="কোনো ঠিকানা নির্বাচিত হয়নি">No Address Selected</h4>
                            <p id="sel-address-full" style="margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.45;" class="dynamic-lang" data-en="Please search or pinpoint your location on the map." data-bn="ম্যাপে পিন করুন অথবা উপরে লোকেশন সার্চ করুন।">Please search or pinpoint your location on the map.</p>
                        </div>
                    </div>

                    <!-- Lat / Long row -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 16px; display: flex; gap: 28px;">
                        <div>
                            <span style="font-size: 11px; color: #059669; font-weight: 700; display: block;">Latitude</span>
                            <span id="sel-lat-val" style="font-size: 13.5px; font-weight: 800; color: #064e3b;">23.810331</span>
                        </div>
                        <div style="width: 1px; background: #bbf7d0;"></div>
                        <div>
                            <span style="font-size: 11px; color: #059669; font-weight: 700; display: block;">Longitude</span>
                            <span id="sel-lng-val" style="font-size: 13.5px; font-weight: 800; color: #064e3b;">90.412521</span>
                        </div>
                    </div>
                </div>

                <!-- 7. Save Address Button -->
                <button type="button" onclick="saveLocation()" style="width: 100%; background: linear-gradient(135deg, #00c675 0%, #059669 100%); color: white; border: none; padding: 15px; border-radius: 12px; font-size: 16px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 18px rgba(0, 198, 117, 0.35); transition: all 0.2s; margin-top: 4px;">
                    <span class="dynamic-lang" data-en="Save Address" data-bn="ঠিকানা সংরক্ষণ করুন">Save Address</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Live Order Tracker Modal (Modern Multi-Order Tracing Hub) -->
    <style>
        @keyframes trackPulse {
            0% { transform: scale(0.95); opacity: 0.8; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.1); opacity: 1; box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); opacity: 0.8; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        @keyframes bikeVibrate {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        .track-pill-btn {
            padding: 8px 16px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .track-pill-btn:hover {
            border-color: #10b981;
            background: #f0fdf4;
            color: #065f46;
        }
        .track-pill-btn.active {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
    </style>

    <div id="track-order-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); z-index: 4500; align-items: center; justify-content: center; padding: 20px 15px; box-sizing: border-box;">
        <div style="background: #ffffff; border-radius: 24px; width: 100%; max-width: 820px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 25px 65px -12px rgba(0,0,0,0.38); overflow: hidden; border: 1px solid #e2e8f0; position: relative;">
            
            <!-- Modal Header -->
            <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <h3 class="dynamic-lang" data-en="Live Order Tracking" data-bn="লাইভ অর্ডার ট্র্যাকিং" style="margin: 0; font-size: 19px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px;">Live Order Tracking</h3>
                            <span id="track-live-pulse-badge" style="display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px;">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block; animation: trackPulse 1.5s infinite;"></span> LIVE
                            </span>
                        </div>
                        <p class="dynamic-lang" data-en="Real-time delivery progress & rider contact" data-bn="রিয়েল-টাইম ডেলিভারি আপডেট ও রাইডার যোগাযোগ" style="margin: 2px 0 0; font-size: 12.5px; color: #64748b;">Real-time delivery progress & rider contact</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="refreshLiveTrackingData(true)" title="Refresh Status" style="background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                        <i class="fa-solid fa-rotate-right" id="track-refresh-icon"></i>
                    </button>
                    <button type="button" onclick="closeTrackOrderModal()" title="Close" style="background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; width: 36px; height: 36px; border-radius: 50%; font-size: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'; this.style.color='#dc2626'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'">&times;</button>
                </div>
            </div>

            <!-- Top Horizontal Single-Row Order Numbers Selector Bar -->
            <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 20px; display: flex; align-items: center; gap: 10px; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: thin;" id="track-order-pills-row">
                <!-- Dynamically populated with order pills -->
            </div>

            <!-- Modal Scrollable Content Body -->
            <div style="padding: 20px 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 18px;" id="track-modal-body">
                
                <!-- Quick Search Bar -->
                <div id="track-search-box-container" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 6px 14px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                    <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8; font-size: 15px;"></i>
                    <input type="text" id="track-order-input" placeholder="Enter your order / invoice number" data-en-placeholder="Enter your order / invoice number" data-bn-placeholder="আপনার ইনভয়েস বা অর্ডার নম্বরটি লিখুন" style="flex: 1; border: none; outline: none; font-size: 13.5px; font-weight: 600; color: #0f172a; background: transparent; padding: 6px 0;" onkeydown="if(event.key==='Enter') submitTrackSearch(event)">
                    <button type="button" onclick="submitTrackSearch(event)" style="background: #10b981; color: white; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                        <span class="dynamic-lang" data-en="Track" data-bn="খুঁজুন">Track</span>
                    </button>
                </div>

                <!-- Active Order Live Tracking Details Panel -->
                <div id="track-active-order-panel">
                    <!-- Populated by JS -->
                </div>

            </div>
        </div>
    </div>

    <!-- Quantity Popover -->
    <div id="quantityModal" style="display: none; position: absolute; z-index: 2000; background-color: #fff; padding: 10px; border-radius: 8px; width: 140px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); border: 1px solid #eaeaea; flex-direction: column; gap: 5px;">
        <button onclick="addToCart(1)" style="width: 100%; height: 35px; border-radius: 5px; border: 1px solid transparent; background: transparent; text-align: left; padding: 0 15px; cursor: pointer; font-size: 15px; color: var(--text-dark);">1</button>
        <button onclick="addToCart(2)" style="width: 100%; height: 35px; border-radius: 5px; border: 1px solid transparent; background: transparent; text-align: left; padding: 0 15px; cursor: pointer; font-size: 15px; color: var(--text-dark);">2</button>
        <button onclick="addToCart(3)" style="width: 100%; height: 35px; border-radius: 5px; border: 1px solid transparent; background: transparent; text-align: left; padding: 0 15px; cursor: pointer; font-size: 15px; color: var(--text-dark);">3</button>
        <button onclick="addToCart(4)" style="width: 100%; height: 35px; border-radius: 5px; border: 1px solid transparent; background: transparent; text-align: left; padding: 0 15px; cursor: pointer; font-size: 15px; color: var(--text-dark);">4</button>
        <button onclick="addToCart(5)" style="width: 100%; height: 35px; border-radius: 5px; border: 1px solid transparent; background: transparent; text-align: left; padding: 0 15px; cursor: pointer; font-size: 15px; color: var(--text-dark);">5</button>
        <div style="display: flex; gap: 5px; margin-top: 5px; border-top: 1px solid #eaeaea; padding-top: 8px;">
            <input type="number" id="customQty" min="1" placeholder="Qty" style="width: 60px; height: 30px; padding: 0 5px; border: 1px solid #cbd5e1; border-radius: 5px; outline: none; font-size: 13px;">
            <button onclick="addCustomQty()" style="flex: 1; height: 30px; border: none; border-radius: 5px; background: var(--primary-color); color: white; font-weight: bold; cursor: pointer; font-size: 12px;">Add</button>
        </div>
    </div>

    <!-- Premium Custom Confirmation Modal -->
    <div id="custom-confirm-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
        <div id="custom-confirm-box" style="background: #ffffff; border-radius: 22px; width: 100%; max-width: 420px; padding: 32px 26px 26px; text-align: center; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.8); transform: scale(0.92); opacity: 0; transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; box-sizing: border-box;">
            <!-- Icon Badge -->
            <div id="custom-confirm-icon-wrap" style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; border: 3px solid #fecaca; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 18px auto; box-shadow: 0 6px 16px rgba(239, 68, 68, 0.2);">
                <i id="custom-confirm-icon" class="fa-solid fa-trash-can"></i>
            </div>
            
            <!-- Title -->
            <h3 id="custom-confirm-title" style="margin: 0 0 10px 0; font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.3;">আপনি কি নিশ্চিত?</h3>
            
            <!-- Message -->
            <div id="custom-confirm-message" style="margin: 0 0 26px 0; font-size: 14.5px; color: #64748b; line-height: 1.6; word-break: break-word;"></div>
            
            <!-- Actions -->
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" id="custom-confirm-cancel" style="flex: 1; background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 6px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    <i class="fa-solid fa-xmark"></i> <span id="custom-confirm-cancel-text">বাতিল</span>
                </button>
                <button type="button" id="custom-confirm-ok" style="flex: 1; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; padding: 12px 18px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35); transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 6px;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 18px rgba(239, 68, 68, 0.45)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px rgba(239, 68, 68, 0.35)'">
                    <i class="fa-solid fa-check"></i> <span id="custom-confirm-ok-text">হ্যাঁ, নিশ্চিত</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Dynamic Legal Content from Store Settings -->
    <div id="legal-content-holder" style="display: none;">
        <div id="legal-content-about-en"><?= $fAboutEn ?></div>
        <div id="legal-content-about-bn"><?= $fAboutBn ?></div>
        <div id="legal-content-terms-en"><?= $fTermsEn ?></div>
        <div id="legal-content-terms-bn"><?= $fTermsBn ?></div>
        <div id="legal-content-privacy-en"><?= $fPrivacyEn ?></div>
        <div id="legal-content-privacy-bn"><?= $fPrivacyBn ?></div>
        <div id="legal-content-refund-en"><?= $fRefundEn ?></div>
        <div id="legal-content-refund-bn"><?= $fRefundBn ?></div>
    </div>

    <!-- Legal Policies & Store Info Modal -->
    <div id="legal-info-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
        <div style="background: white; border-radius: 20px; width: 100%; max-width: 720px; max-height: 88vh; display: flex; flex-direction: column; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.35); overflow: hidden; border: 1px solid #e2e8f0;">
            <!-- Modal Header -->
            <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div id="legal-modal-icon-wrap" style="width: 38px; height: 38px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 17px;">
                        <i id="legal-modal-icon" class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h3 id="legal-modal-title" style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Store Legal Policy</h3>
                        <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;" class="dynamic-lang" data-en="BazarKinun.com Terms & Policy Documentation" data-bn="BazarKinun.com এর শর্ত ও পলিসি ডকুমেন্টস">BazarKinun.com Terms & Policy Documentation</p>
                    </div>
                </div>
                <button onclick="closeLegalModal()" style="background: #f1f5f9; border: none; font-size: 20px; color: #64748b; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.2s;">&times;</button>
            </div>

            <!-- Tab Pills Navigation -->
            <div style="display: flex; gap: 8px; padding: 12px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; overflow-x: auto;">
                <button type="button" class="legal-tab-btn" id="legal-tab-about" onclick="switchLegalTab('about')" style="padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0; background: white; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: 0.2s;">
                    <i class="fa-solid fa-circle-info"></i> <span class="dynamic-lang" data-en="About Us" data-bn="আমাদের সম্পর্কে">About Us</span>
                </button>
                <button type="button" class="legal-tab-btn" id="legal-tab-terms" onclick="switchLegalTab('terms')" style="padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0; background: white; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: 0.2s;">
                    <i class="fa-solid fa-file-contract"></i> <span class="dynamic-lang" data-en="Terms" data-bn="শর্তাবলী">Terms</span>
                </button>
                <button type="button" class="legal-tab-btn" id="legal-tab-privacy" onclick="switchLegalTab('privacy')" style="padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0; background: white; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: 0.2s;">
                    <i class="fa-solid fa-user-shield"></i> <span class="dynamic-lang" data-en="Privacy Policy" data-bn="প্রাইভেসি পলিসি">Privacy Policy</span>
                </button>
                <button type="button" class="legal-tab-btn" id="legal-tab-refund" onclick="switchLegalTab('refund')" style="padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; border: 1px solid #e2e8f0; background: white; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: 0.2s;">
                    <i class="fa-solid fa-rotate-left"></i> <span class="dynamic-lang" data-en="Refund Policy" data-bn="রিটার্ন ও রিফান্ড">Refund Policy</span>
                </button>
            </div>

            <!-- Content Body -->
            <div id="legal-modal-body" style="padding: 24px 28px; overflow-y: auto; font-size: 14.5px; line-height: 1.75; color: #334155;">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <!-- Master Scripts & Translation Engine -->
    <script>
        // 0. Custom Modal Confirm System
        window.showConfirm = function(message, onConfirmCallback, options = {}) {
            return new Promise((resolve) => {
                const modal = document.getElementById("custom-confirm-modal");
                const box = document.getElementById("custom-confirm-box");
                const msgEl = document.getElementById("custom-confirm-message");
                const titleEl = document.getElementById("custom-confirm-title");
                const okBtn = document.getElementById("custom-confirm-ok");
                const cancelBtn = document.getElementById("custom-confirm-cancel");
                const okText = document.getElementById("custom-confirm-ok-text");
                const cancelText = document.getElementById("custom-confirm-cancel-text");
                const iconWrap = document.getElementById("custom-confirm-icon-wrap");
                const iconEl = document.getElementById("custom-confirm-icon");

                if (!modal || !box) {
                    const res = window.confirm(message.replace(/<[^>]*>?/gm, ''));
                    if (res && onConfirmCallback) onConfirmCallback();
                    resolve(res);
                    return;
                }

                // Language defaults
                const defaultTitle = isBangla ? "আপনি কি নিশ্চিত?" : "Are you sure?";
                const defaultCancel = isBangla ? "বাতিল" : "Cancel";
                const defaultOk = isBangla ? "হ্যাঁ, নিশ্চিত" : "Yes, Confirm";

                titleEl.innerText = options.title || defaultTitle;
                msgEl.innerHTML = message;
                okText.innerText = options.confirmText || defaultOk;
                cancelText.innerText = options.cancelText || defaultCancel;

                const isDanger = (options.type === 'danger' || options.type === 'delete');

                if (isDanger) {
                    iconWrap.style.background = '#fee2e2';
                    iconWrap.style.borderColor = '#fecaca';
                    iconWrap.style.color = '#ef4444';
                    iconWrap.style.boxShadow = '0 6px 16px rgba(239, 68, 68, 0.2)';
                    iconEl.className = options.icon || 'fa-solid fa-trash-can';
                    okBtn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                    okBtn.style.boxShadow = '0 4px 14px rgba(239, 68, 68, 0.35)';
                } else {
                    iconWrap.style.background = '#dcfce7';
                    iconWrap.style.borderColor = '#bbf7d0';
                    iconWrap.style.color = '#00c675';
                    iconWrap.style.boxShadow = '0 6px 16px rgba(0, 198, 117, 0.2)';
                    iconEl.className = options.icon || 'fa-solid fa-circle-question';
                    okBtn.style.background = 'linear-gradient(135deg, #00c675 0%, #059669 100%)';
                    okBtn.style.boxShadow = '0 4px 14px rgba(0, 198, 117, 0.35)';
                }

                modal.style.display = "flex";
                setTimeout(() => {
                    box.style.transform = "scale(1)";
                    box.style.opacity = "1";
                }, 20);

                const closeModal = () => {
                    box.style.transform = "scale(0.92)";
                    box.style.opacity = "0";
                    setTimeout(() => {
                        modal.style.display = "none";
                    }, 200);
                };

                okBtn.onclick = function() {
                    closeModal();
                    if (onConfirmCallback) onConfirmCallback();
                    resolve(true);
                };

                cancelBtn.onclick = function() {
                    closeModal();
                    if (options.onCancel) options.onCancel();
                    resolve(false);
                };

                modal.onclick = function(e) {
                    if (e.target === modal) {
                        closeModal();
                        if (options.onCancel) options.onCancel();
                        resolve(false);
                    }
                };
            });
        };

        // 1. Toast Notification Utility
        function showToast(message, type = "success") {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.background = (type === 'success') ? '#10b981' : '#ef4444';
            toast.innerHTML = (type === 'success' ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') + message;
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3500);
        }

        // 2. Mobile Drawer Toggle
        function toggleMobileDrawer() {
            const overlay = document.getElementById('mobile-drawer-overlay');
            const drawer = document.getElementById('mobile-drawer');
            if (!drawer) return;
            if (drawer.style.right === '0px') {
                drawer.style.right = '-300px';
                overlay.style.display = 'none';
            } else {
                drawer.style.right = '0px';
                overlay.style.display = 'block';
            }
        }

        // 3. Translation Dictionary
        const dict = {
            "Track Order": "অর্ডার ট্র্যাক করুন",
            "Login": "লগইন",
            "Sign out": "লগআউট",
            "All Products": "সকল পণ্য",
            "Home": "হোম",
            "My cart": "আমার কার্ট",
            "My Cart": "আমার কার্ট",
            "Wishlist": "পছন্দের তালিকা",
            "My Wishlist": "আমার পছন্দের তালিকা",
            "Add Location": "লোকেশন যুক্ত করুন",
            "Change Location": "লোকেশন পরিবর্তন করুন",
            "Order from Website": "ওয়েবসাইট থেকে অর্ডার করুন",
            "Write Your Grocery List": "আপনার বাজারের লিস্ট লিখুন",
            "Upload Your Grocery List": "আপনার বাজারের লিস্ট আপলোড করুন",
            "Write Grocery List": "লিখে বাজার লিস্ট",
            "Upload List Photo": "ছবির মাধ্যমে লিস্ট",
            "Shopping Cart": "শপিং কার্ট",
            "Shopping & Services": "কেনাকাটা ও সেবা",
            "Customer Account": "কাস্টমার অ্যাকাউন্ট",
            "Help & Support": "সাহায্য ও সাপোর্ট",
            "View Orders": "অর্ডারসমূহ দেখুন",
            "Money Receipts & Invoices": "মানি রিসিট ও ভাউচার",
            "Saved Grocery Lists": "সংরক্ষিত বাজার লিস্ট",
            "Add Delivery Address": "ডেলিভারি ঠিকানা যুক্ত করুন",
            "About Us": "আমাদের সম্পর্কে",
            "Terms": "শর্তাবলি",
            "Privacy Policy": "প্রাইভেসি পলিসি",
            "Refund": "রিফান্ড নীতি",
            "Developed by Md. Rayhan": "ডেভেলপ করেছেন মোঃ রায়হান",
            "Rider Login": "রাইডার লগইন",
            "BazarKinun.com © 2026, All Rights Reserved": "BazarKinun.com © ২০২৬, সর্বস্বত্ব সংরক্ষিত",
            "24/7 Support Center": "২৪/৭ সাপোর্ট সেন্টার",
            "Continue Shopping": "কেনাকাটা চালিয়ে যান",
            "Browse More": "আরও পণ্য দেখুন",
            "Order Summary": "অর্ডার বিবরণ",
            "Regular Price": "রেগুলার দাম",
            "Total Discount": "মোট ছাড়",
            "Total Price": "মোট মূল্য",
            "Delivery Charge": "ডেলিভারি চার্জ",
            "Total to Pay": "মোট পরিশোধ",
            "Confirm & Place Order": "অর্ডার কনফার্ম করুন",
            "Awaiting Rider Pricing": "রাইডার দরদামের অপেক্ষায়",
            "Invoice Created (Confirm)": "ইনভয়েস তৈরি হয়েছে (কনফার্ম করুন)",
            "Confirmed (Processing)": "কনফার্মড (প্রসেসিং চলছে)",
            "Invoice Created": "ইনভয়েস তৈরি হয়েছে",
            "Processing": "প্রসেসিং চলছে",
            "Delivering": "ডেলিভারি নিয়ে আসছে",
            "Completed": "সম্পন্ন",
            "Cancelled": "বাতিল",
            "Pending": "পেন্ডিং",
            "Grocery List Order": "ফর্দ অর্ডার",
            "Photo List Order": "ছবি ফর্দ অর্ডার",
            "Online Order": "অনলাইন অর্ডার",
            "View Details": "বিবরণ দেখুন",
            "Order Now": "অর্ডার করুন",
            "View": "দেখুন",
            "Edit": "এডিট",
            "Delete": "মুছুন",
            "Personal Information": "ব্যক্তিগত তথ্য",
            "Edit Profile": "প্রোফাইল এডিট",
            "Full Name": "পুরো নাম",
            "Phone Number": "ফোন নম্বর",
            "Email Address": "ইমেইল অ্যাড্রেস",
            "Save Changes": "পরিবর্তন সংরক্ষণ করুন",
            "Cancel": "বাতিল"
        };

        const reverseDict = {};
        for (let k in dict) { reverseDict[dict[k]] = k; }

        var isBangla = localStorage.getItem('appLanguage') === 'bn';
        window.isBangla = isBangla;

        function applyLanguage(targetIsBangla) {
            isBangla = !!targetIsBangla;
            window.isBangla = isBangla;
            localStorage.setItem('appLanguage', isBangla ? 'bn' : 'en');
            
            const btn = document.getElementById('lang-btn');
            if (btn) btn.textContent = isBangla ? 'English' : 'বাংলা';
            const mBtn = document.getElementById('mobile-lang-btn');
            if (mBtn) mBtn.textContent = isBangla ? 'English' : 'বাংলা';

            document.querySelectorAll('.dynamic-lang').forEach(el => {
                const bn = el.getAttribute('data-bn');
                const en = el.getAttribute('data-en');
                const targetText = (isBangla && bn) ? bn : (!isBangla && en ? en : null);
                if (targetText !== null) {
                    if (targetText.includes('<') && targetText.includes('>')) {
                        el.innerHTML = targetText;
                    } else {
                        el.textContent = targetText;
                    }
                }
            });

            document.querySelectorAll('[data-bn-placeholder]').forEach(el => {
                const bn = el.getAttribute('data-bn-placeholder');
                const en = el.getAttribute('data-en-placeholder');
                el.placeholder = isBangla ? bn : en;
            });

            // Update header action titles
            const wishlistLink = document.getElementById('nav-wishlist-link');
            if (wishlistLink) wishlistLink.setAttribute('title', isBangla ? 'পছন্দের তালিকা' : 'Wishlist');
            const cartLink = document.getElementById('nav-cart-link');
            if (cartLink) cartLink.setAttribute('title', isBangla ? 'কার্ট' : 'Cart');

            // Update location text
            updateNavLocationText();

            // Re-render dynamic components if they exist on the current page
            if (typeof renderCart === 'function') {
                renderCart();
            }
            if (typeof renderWishlist === 'function') {
                renderWishlist();
            }
            if (typeof renderProducts === 'function') {
                renderProducts();
            }

            // Dispatch global event for other listeners
            window.dispatchEvent(new CustomEvent('languageChanged', { detail: { isBangla } }));

            // Immediately sync header height to prevent any navbar overlap
            if (typeof syncHeaderHeight === 'function') {
                syncHeaderHeight();
                setTimeout(syncHeaderHeight, 50);
            }
        }

        document.getElementById('lang-btn')?.addEventListener('click', () => applyLanguage(!isBangla));
        document.getElementById('mobile-lang-btn')?.addEventListener('click', () => applyLanguage(!isBangla));

        // Backend Cross-Session User State Sync Helper
        var syncUserStateTimer = null;
        var isUserStateHydrated = false;

        function syncUserStateToBackend(immediate = false, extraFlags = {}) {
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            const phone = localStorage.getItem('userPhone');
            if (!isLoggedIn || !phone) return;

            const doSync = () => {
                // If not yet hydrated and both cart & wishlist are empty, hydrate first to prevent accidental wipe
                const curCart = localStorage.getItem('cart');
                const curWish = localStorage.getItem('wishlist');
                if (!isUserStateHydrated && (!curCart || curCart === '[]') && (!curWish || curWish === '[]') && !immediate) {
                    hydrateUserStateFromBackend(() => doSync());
                    return;
                }

                const savedLocations = localStorage.getItem('savedLocationsList') || '[]';
                const activeLocId = localStorage.getItem('activeSavedLocationId') || '';
                const cartData = localStorage.getItem('cart') || '[]';
                const wishlistData = localStorage.getItem('wishlist') || '[]';
                const userLoc = localStorage.getItem('userLocation') || '';

                const payload = JSON.stringify(Object.assign({
                    action: 'sync_user_state',
                    phone: phone,
                    saved_addresses: savedLocations,
                    active_address_id: activeLocId,
                    cart_data: cartData,
                    wishlist_data: wishlistData,
                    location: userLoc
                }, extraFlags));

                try {
                    if (navigator.sendBeacon && immediate) {
                        const blob = new Blob([payload], { type: 'application/json' });
                        navigator.sendBeacon('api/customer-data.php', blob);
                    } else {
                        fetch('api/customer-data.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: payload,
                            keepalive: immediate
                        }).catch(() => {});
                    }
                } catch(e) {}
            };

            if (immediate) {
                if (syncUserStateTimer) clearTimeout(syncUserStateTimer);
                doSync();
            } else {
                if (syncUserStateTimer) clearTimeout(syncUserStateTimer);
                syncUserStateTimer = setTimeout(doSync, 500);
            }
        }
        window.syncUserStateToBackend = syncUserStateToBackend;

        // 4. Cart & Wishlist Badges
        var cart = JSON.parse(localStorage.getItem('cart') || '[]');
        var wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');

        function updateCartBadge() {
            const badge = document.getElementById('cart-badge');
            const priceLabel = document.getElementById('cart-total-price');

            cart = JSON.parse(localStorage.getItem('cart') || '[]');
            let totalQty = 0;
            let totalPrice = 0;
            cart.forEach(item => {
                const qty = parseInt(item.quantity) || 1;
                const pr = parseFloat(item.price) || 0;
                totalQty += qty;
                totalPrice += (pr * qty);
            });
            if (badge) badge.innerText = totalQty;
            if (priceLabel) priceLabel.innerText = '৳' + totalPrice;
            if (typeof syncAllAddButtons === 'function') syncAllAddButtons();
        }

        function updateWishlistBadge() {
            const badge = document.getElementById('wishlist-badge');
            wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            if (badge) badge.innerText = wishlist.length;
        }

        // Automatic cross-browser state hydration from database
        function hydrateUserStateFromBackend(callback) {
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            const phone = localStorage.getItem('userPhone');
            if (!isLoggedIn || !phone) {
                isUserStateHydrated = true;
                if (typeof callback === 'function') callback();
                return;
            }

            fetch(`api/customer-data.php?phone=${encodeURIComponent(phone)}`)
                .then(r => r.json())
                .then(data => {
                    isUserStateHydrated = true;
                    if (data && data.status === 'success' && data.customer) {
                        const cust = data.customer;

                        // 1. Wishlist: Restore from DB if local is empty
                        const curWishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                        if (curWishlist.length === 0 && Array.isArray(cust.wishlist_data) && cust.wishlist_data.length > 0) {
                            localStorage.setItem('wishlist', JSON.stringify(cust.wishlist_data));
                            updateWishlistBadge();
                            if (typeof renderWishlist === 'function') renderWishlist();
                        }

                        // 2. Cart: Restore from DB if local is empty
                        const curCart = JSON.parse(localStorage.getItem('cart') || '[]');
                        if (curCart.length === 0 && Array.isArray(cust.cart_data) && cust.cart_data.length > 0) {
                            localStorage.setItem('cart', JSON.stringify(cust.cart_data));
                            updateCartBadge();
                            if (typeof renderCart === 'function') renderCart();
                        }

                        // 3. Saved Addresses: Restore from DB if local is empty
                        const curSaved = JSON.parse(localStorage.getItem('savedLocationsList') || '[]');
                        if (curSaved.length === 0) {
                            if (Array.isArray(cust.saved_addresses) && cust.saved_addresses.length > 0) {
                                localStorage.setItem('savedLocationsList', JSON.stringify(cust.saved_addresses));
                                const activeId = cust.active_address_id || cust.saved_addresses[0].id;
                                localStorage.setItem('activeSavedLocationId', activeId);
                                const activeLoc = cust.saved_addresses.find(a => a.id === activeId) || cust.saved_addresses[0];
                                localStorage.setItem('userLocation', activeLoc.title || '');
                                localStorage.setItem('userFullAddress', activeLoc.fullAddress || activeLoc.title || '');
                                localStorage.setItem('userKeyword', activeLoc.kw || 'Home');
                                if (activeLoc.lat) localStorage.setItem('userLat', activeLoc.lat);
                                if (activeLoc.lng) localStorage.setItem('userLng', activeLoc.lng);
                                if (typeof updateNavLocationText === 'function') updateNavLocationText();
                                if (typeof renderSavedLocations === 'function') renderSavedLocations();
                            } else if (cust.location && cust.location.trim() !== '') {
                                const fallbackItem = {
                                    id: 'loc_default_1',
                                    kw: 'Home',
                                    title: cust.location.trim(),
                                    fullAddress: cust.location.trim(),
                                    lat: 23.810331,
                                    lng: 90.412521
                                };
                                localStorage.setItem('savedLocationsList', JSON.stringify([fallbackItem]));
                                localStorage.setItem('activeSavedLocationId', fallbackItem.id);
                                localStorage.setItem('userLocation', fallbackItem.title);
                                localStorage.setItem('userFullAddress', fallbackItem.fullAddress);
                                localStorage.setItem('userKeyword', 'Home');
                                localStorage.setItem('userLat', fallbackItem.lat);
                                localStorage.setItem('userLng', fallbackItem.lng);
                                if (typeof updateNavLocationText === 'function') updateNavLocationText();
                                if (typeof renderSavedLocations === 'function') renderSavedLocations();
                            }
                        }
                    }
                    if (typeof callback === 'function') callback();
                })
                .catch(() => {
                    isUserStateHydrated = true;
                    if (typeof callback === 'function') callback();
                });
        }
        window.hydrateUserStateFromBackend = hydrateUserStateFromBackend;
        hydrateUserStateFromBackend();

        // Global Product Info Lookup & In-Memory Catalog Cache
        var inMemCatalog = null;
        window.inMemCatalog = null;
        window.getProductInfo = function(name) {
            if (!name) return null;
            if (!inMemCatalog || inMemCatalog.length === 0) {
                try {
                    inMemCatalog = JSON.parse(sessionStorage.getItem('storeProductsCatalog') || '[]');
                    window.inMemCatalog = inMemCatalog;
                } catch(e) {
                    inMemCatalog = [];
                    window.inMemCatalog = [];
                }
            }
            const cleanName = String(name).trim().toLowerCase();
            return inMemCatalog.find(p => 
                (p.title && p.title.trim().toLowerCase() === cleanName) || 
                (p.title_bn && p.title_bn.trim() === String(name).trim()) ||
                (p.title && cleanName.length > 2 && (cleanName.includes(p.title.trim().toLowerCase()) || p.title.trim().toLowerCase().includes(cleanName))) ||
                (p.title_bn && String(name).trim().length > 2 && (String(name).trim().includes(p.title_bn.trim()) || p.title_bn.trim().includes(String(name).trim())))
            );
        };

        function ensureProductsCatalog(force = false) {
            const loc = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userLocation') || '').trim();
            const cached = sessionStorage.getItem('storeProductsCatalog');
            if (!force && cached) {
                try {
                    inMemCatalog = JSON.parse(cached);
                    window.inMemCatalog = inMemCatalog;
                } catch(e) {}
                return; // Already cached, no need to fire redundant network request
            }

            fetch(`api/store-data.php?location=${encodeURIComponent(loc || '')}`)
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        if (d.products) {
                            inMemCatalog = d.products;
                            window.inMemCatalog = d.products;
                            sessionStorage.setItem('storeProductsCatalog', JSON.stringify(d.products));
                        }
                        if (d.discount_rules) sessionStorage.setItem('storeDiscountRules', JSON.stringify(d.discount_rules));
                        if (typeof renderCart === 'function') renderCart();
                        if (typeof renderWishlist === 'function') renderWishlist();
                        if (typeof renderFeaturedSections === 'function') renderFeaturedSections();
                        if (typeof renderProducts === 'function') renderProducts();
                        window.dispatchEvent(new CustomEvent('catalogUpdated', { detail: { products: inMemCatalog } }));
                    }
                })
                .catch(() => {});
        }

        // Revalidate Cart Item Prices & Availability for Current / Changed Location
        window.revalidateCartPrices = async function(targetLocationStr = null, notifyUser = true) {
            const loc = targetLocationStr || (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userLocation') || '').trim();
            
            // Invalidate session catalog cache to guarantee fresh location pricing
            sessionStorage.removeItem('storeProductsCatalog');
            sessionStorage.removeItem('storeDiscountRules');
            inMemCatalog = null;
            window.inMemCatalog = null;

            try {
                const res = await fetch(`api/store-data.php?location=${encodeURIComponent(loc || '')}`);
                const data = await res.json();

                if (data.status === 'success' && Array.isArray(data.products)) {
                    inMemCatalog = data.products;
                    window.inMemCatalog = data.products;
                    sessionStorage.setItem('storeProductsCatalog', JSON.stringify(data.products));
                    if (data.discount_rules) sessionStorage.setItem('storeDiscountRules', JSON.stringify(data.discount_rules));

                    let currentCart = JSON.parse(localStorage.getItem('cart') || '[]');
                    let priceChangedCount = 0;
                    let unavailableCount = 0;

                    if (currentCart.length > 0) {
                        currentCart = currentCart.map(item => {
                            const itemName = item.name || item.title || '';
                            const cleanName = itemName.trim().toLowerCase();
                            const liveProd = inMemCatalog.find(p => 
                                (p.title && p.title.trim().toLowerCase() === cleanName) || 
                                (p.title_bn && p.title_bn.trim() === itemName.trim()) ||
                                (item.id && p.id == item.id) ||
                                (p.title && cleanName.length > 2 && (cleanName.includes(p.title.trim().toLowerCase()) || p.title.trim().toLowerCase().includes(cleanName)))
                            );

                            if (liveProd) {
                                const newPrice = parseFloat(liveProd.price) || 0;
                                const newOldPrice = liveProd.old_price ? parseFloat(liveProd.old_price) : newPrice;
                                const oldCartPrice = parseFloat(item.price) || 0;

                                if (Math.abs(newPrice - oldCartPrice) >= 0.01) {
                                    priceChangedCount++;
                                }

                                const isAvail = (liveProd.is_available !== false && liveProd.is_available_in_location !== false) && (liveProd.stock > 0 || liveProd.in_stock == 1);
                                if (!isAvail) {
                                    unavailableCount++;
                                }

                                return {
                                    ...item,
                                    id: liveProd.id,
                                    price: newPrice,
                                    oldPrice: newOldPrice,
                                    is_available: isAvail,
                                    is_out_of_stock: !isAvail,
                                    stock: liveProd.stock
                                };
                            } else {
                                unavailableCount++;
                                return {
                                    ...item,
                                    is_available: false,
                                    is_out_of_stock: true
                                };
                            }
                        });

                        localStorage.setItem('cart', JSON.stringify(currentCart));
                        updateCartBadge();
                        if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();

                        if (notifyUser && (priceChangedCount > 0 || unavailableCount > 0)) {
                            if (priceChangedCount > 0 && unavailableCount > 0) {
                                showToast(isBangla ? `ডেলিভারি লোকেশন পরিবর্তনের কারণে কার্টের পণ্যের দাম ও প্রাপ্যতা আপডেট হয়েছে (${priceChangedCount}টি পণ্যের দাম পরিবর্তিত)।` : `Cart prices & availability updated for selected area (${priceChangedCount} items re-priced).`, 'info');
                            } else if (priceChangedCount > 0) {
                                showToast(isBangla ? `ডেলিভারি লোকেশন পরিবর্তনের কারণে কার্টের পণ্যের দাম আপডেট করা হয়েছে (${priceChangedCount}টি পণ্যের দাম পরিবর্তিত)।` : `Cart prices updated according to selected delivery area.`, 'info');
                            } else if (unavailableCount > 0) {
                                showToast(isBangla ? `সতর্কতা: কার্টের কিছু পণ্য এই ডেলিভারি এলাকায় বর্তমানে পাওয়া যাচ্ছে না।` : `Notice: Some items in your cart are not available in this delivery area.`, 'error');
                            }
                        }
                    }

                    if (typeof renderCart === 'function') renderCart();
                    if (typeof renderWishlist === 'function') renderWishlist();
                    if (typeof renderFeaturedSections === 'function') renderFeaturedSections();
                    if (typeof renderProducts === 'function') renderProducts();
                    window.dispatchEvent(new CustomEvent('catalogUpdated', { detail: { products: inMemCatalog } }));
                }
            } catch(err) {
                console.error("Cart revalidation error:", err);
            }
        };

        function toggleWishlist(btn, name, price, oldPrice, img, name_bn = '', category_name = '', category_name_bn = '', poriman = '', poriman_bn = '') {
            if (typeof event !== 'undefined' && event) event.stopPropagation();
            wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            const icon = btn.querySelector('i');
            const existingIndex = wishlist.findIndex(item => item.name === name);

            const prodInfo = window.getProductInfo(name);
            const bName = name_bn || (prodInfo ? prodInfo.title_bn : name);
            const bCat = category_name || (prodInfo ? prodInfo.category_name : 'Grocery');
            const bCatBn = category_name_bn || (prodInfo ? prodInfo.category_name_bn : bCat);
            const bPoriman = poriman || (prodInfo ? prodInfo.poriman : '');
            const bPorimanBn = poriman_bn || (prodInfo ? prodInfo.poriman_bn : bPoriman);

            if (existingIndex > -1 || btn.classList.contains('active')) {
                // Remove from wishlist
                btn.classList.remove('active');
                btn.style.color = '#94a3b8';
                if (icon) {
                    icon.className = 'fa-regular fa-heart';
                    icon.style.transform = 'scale(1)';
                }
                wishlist = wishlist.filter(item => item.name !== name);
                const toastName = isBangla ? (bName || name) : name;
                showToast(isBangla ? `"${toastName}" পছন্দের তালিকা থেকে সরানো হয়েছে` : `Removed "${name}" from wishlist`);
            } else {
                // Add to wishlist (Turn RED!)
                btn.classList.add('active');
                btn.style.color = '#ef4444';
                if (icon) {
                    icon.className = 'fa-solid fa-heart';
                    icon.style.transform = 'scale(1.25)';
                    setTimeout(() => { if (icon) icon.style.transform = 'scale(1)'; }, 200);
                }
                wishlist.push({ 
                    name, 
                    name_bn: bName,
                    price: parseFloat(price), 
                    oldPrice: parseFloat(oldPrice), 
                    image: img,
                    img: img,
                    category: bCat,
                    category_name: bCat,
                    category_name_bn: bCatBn,
                    poriman: bPoriman,
                    poriman_bn: bPorimanBn
                });
                const toastName = isBangla ? (bName || name) : name;
                showToast(isBangla ? `"${toastName}" পছন্দের তালিকায় যুক্ত হয়েছে!` : `Added "${name}" to wishlist!`);
            }

            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            updateWishlistBadge();
            if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();
        }

        // Helper: Get Product Quantity in Cart
        window.getProductCartQty = function(productName) {
            if (!productName) return 0;
            try {
                const c = JSON.parse(localStorage.getItem('cart') || '[]');
                const found = c.find(i => i.name === productName || (i.name_bn && i.name_bn === productName));
                return found ? (parseInt(found.quantity) || 0) : 0;
            } catch(e) {
                return 0;
            }
        };

        // Helper: Sync All Add Buttons Across the Current Page
        window.syncAllAddButtons = function() {
            try {
                const c = JSON.parse(localStorage.getItem('cart') || '[]');
                document.querySelectorAll('.add-btn[data-product-name]').forEach(btn => {
                    const pName = btn.getAttribute('data-product-name');
                    const found = c.find(i => i.name === pName);
                    const qty = found ? (parseInt(found.quantity) || 0) : 0;
                    if (qty > 0) {
                        btn.innerHTML = `<i class="fa-solid fa-cart-shopping"></i> Add ${qty}`;
                        btn.classList.add('has-items');
                    } else {
                        btn.innerHTML = `<i class="fa-solid fa-cart-shopping"></i> Add`;
                        btn.classList.remove('has-items');
                    }
                });
            } catch(e) {}
        };

        // 5. Direct Quick Add to Cart (Add 1, Add 2, Add 3 ...)
        window.quickAddToCart = function(btn, event, name, price, oldPrice, img, name_bn = '', category_name = '', category_name_bn = '', poriman = '', poriman_bn = '') {
            if (event) event.stopPropagation();

            const prodInfo = (typeof window.getProductInfo === 'function') ? window.getProductInfo(name) : null;
            const bName = name_bn || (prodInfo ? prodInfo.title_bn : name);
            const bCat = category_name || (prodInfo ? prodInfo.category_name : 'Grocery');
            const bCatBn = category_name_bn || (prodInfo ? prodInfo.category_name_bn : bCat);
            const bPoriman = poriman || (prodInfo ? prodInfo.poriman : '');
            const bPorimanBn = poriman_bn || (prodInfo ? prodInfo.poriman_bn : bPoriman);

            let cartList = JSON.parse(localStorage.getItem('cart') || '[]');
            let existing = cartList.find(i => i.name === name);
            let currentQty = 1;

            if (existing) {
                existing.quantity = (parseInt(existing.quantity) || 0) + 1;
                currentQty = existing.quantity;
                if (!existing.name_bn && bName) existing.name_bn = bName;
                if (!existing.poriman && bPoriman) existing.poriman = bPoriman;
                if (!existing.poriman_bn && bPorimanBn) existing.poriman_bn = bPorimanBn;
                if (!existing.category_name && bCat) existing.category_name = bCat;
                if (!existing.category_name_bn && bCatBn) existing.category_name_bn = bCatBn;
                if (!existing.img && img) existing.img = img;
                if (!existing.image && img) existing.image = img;
            } else {
                cartList.push({
                    name: name,
                    name_bn: bName,
                    price: parseFloat(price),
                    oldPrice: parseFloat(oldPrice),
                    image: img,
                    img: img,
                    quantity: 1,
                    category_name: bCat,
                    category_name_bn: bCatBn,
                    poriman: bPoriman,
                    poriman_bn: bPorimanBn
                });
                currentQty = 1;
            }

            localStorage.setItem('cart', JSON.stringify(cartList));
            if (typeof updateCartBadge === 'function') updateCartBadge();
            if (typeof syncAllAddButtons === 'function') syncAllAddButtons();
            if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();

            // Quick bounce animation on button
            if (btn) {
                btn.style.transform = 'scale(0.92)';
                setTimeout(() => {
                    if (btn) btn.style.transform = 'scale(1.08)';
                    setTimeout(() => {
                        if (btn) btn.style.transform = 'scale(1)';
                    }, 120);
                }, 100);
            }

            const toastName = isBangla ? (bName || name) : name;
            showToast(isBangla ? `"${toastName}" (${currentQty} টি) কার্টে যোগ করা হয়েছে!` : `Added "${name}" (${currentQty} in cart)!`, "success");
        };

        // Alias openQuantityModal to quickAddToCart for compatibility
        window.openQuantityModal = function(btn, event, name, price, oldPrice, img, name_bn = '', category_name = '', category_name_bn = '', poriman = '', poriman_bn = '') {
            window.quickAddToCart(btn, event, name, price, oldPrice, img, name_bn, category_name, category_name_bn, poriman, poriman_bn);
        };

        // 6. Manage Addresses Modal & Leaflet Map
        var locMap = null;
        var locMarker = null;
        var selectedKeyword = 'Home';
        var currentSelectedLat = 23.810331;
        var currentSelectedLng = 90.412521;
        var hasSelectedValidLocation = false;

        function isValidLocationString(str) {
            if (!str || typeof str !== 'string') return false;
            const s = str.trim().toLowerCase();
            if (s.length < 3) return false;
            const invalidKeywords = [
                'not provided', 'unknown location', 'undefined', 'null', 
                'no address selected', 'please search or pinpoint', 'pinpoint your location',
                'কোনো ঠিকানা নির্বাচিত হয়নি', 'ঠিকানা নির্দিষ্ট নয়', 'address not specified',
                'ম্যাপে পিন করুন', 'লোকেশন সার্চ করুন', 'লোকেশন পিন', 'ঠিকানা নির্বাচন করুন',
                'নির্বাচিত হয়নি', 'ম্যাপে পিন'
            ];
            return !invalidKeywords.some(k => s.includes(k));
        }

        function getSavedLocations() {
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            if (!isLoggedIn) {
                return [];
            }
            const raw = localStorage.getItem('savedLocationsList');
            let parsed = [];
            if (raw) {
                try {
                    parsed = JSON.parse(raw) || [];
                } catch(e) { parsed = []; }
            }
            if (parsed.length === 0) {
                const curTitle = localStorage.getItem('userLocation');
                const curFull = localStorage.getItem('userFullAddress') || curTitle;
                if (curTitle && isValidLocationString(curTitle)) {
                    const fallbackItem = {
                        id: 'loc_default_1',
                        kw: localStorage.getItem('userKeyword') || 'Home',
                        title: curTitle,
                        fullAddress: curFull,
                        lat: parseFloat(localStorage.getItem('userLat')) || 23.810331,
                        lng: parseFloat(localStorage.getItem('userLng')) || 90.412521
                    };
                    parsed = [fallbackItem];
                    localStorage.setItem('savedLocationsList', JSON.stringify(parsed));
                    if (!localStorage.getItem('activeSavedLocationId')) {
                        localStorage.setItem('activeSavedLocationId', fallbackItem.id);
                    }
                }
            }
            // Automatically filter out any invalid/placeholder addresses
            return parsed.filter(item => {
                const t = item.title || '';
                const f = item.fullAddress || '';
                return (isValidLocationString(t) || isValidLocationString(f));
            });
        }

        var activeSavedLocationId = localStorage.getItem('activeSavedLocationId') || null;

        function renderSavedLocations() {
            const container = document.getElementById('saved-locations-cards-container');
            if (!container) return;
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            const list = getSavedLocations();

            if (!isLoggedIn || list.length === 0) {
                container.innerHTML = `
                    <div style="width: 100%; padding: 16px; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; text-align: center; color: #64748b; font-size: 13px; font-weight: 600;">
                        ${!isLoggedIn 
                            ? (isBangla ? 'কোনো সংরক্ষিত ঠিকানা নেই। ঠিকানা সংরক্ষণ করতে অনুগ্রহ করে লগইন করুন।' : 'No saved locations. Please login to save and manage addresses.')
                            : (isBangla ? 'কোনো সংরক্ষিত ঠিকানা নেই। নিচের ম্যাপ বা সার্চ থেকে নতুন ঠিকানা সংরক্ষণ করুন।' : 'No saved locations yet. Pin or search your address below and click Save Address.')
                        }
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            container.style.display = 'flex';
            container.style.gap = '12px';
            container.style.width = '100%';

            list.forEach(item => {
                const isActive = (item.id === activeSavedLocationId);
                const card = document.createElement('div');
                card.className = `saved-loc-card ${isActive ? 'active' : ''}`;
                card.style.cssText = `
                    position: relative;
                    flex: 1 1 0;
                    min-width: 0;
                    border: 1.5px solid ${isActive ? '#00c675' : '#e2e8f0'};
                    background: ${isActive ? '#f0fdf4' : '#ffffff'};
                    border-radius: 14px;
                    padding: 14px 16px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    cursor: pointer;
                    transition: all 0.2s;
                    box-sizing: border-box;
                `;
                card.onclick = () => selectSavedLocationById(item.id);

                card.innerHTML = `
                    <!-- Corner Small 'x' Delete Button -->
                    <button type="button" 
                            onclick="deleteSavedLocation(event, '${item.id}')" 
                            title="${isBangla ? 'মুছে ফেলুন' : 'Delete'}" 
                            style="position: absolute; top: 6px; right: 8px; width: 18px; height: 18px; background: transparent; color: #94a3b8; border: none; font-size: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: color 0.15s, transform 0.15s; line-height: 1; z-index: 10;"
                            onmouseover="this.style.color='#ef4444'; this.style.transform='scale(1.15)';"
                            onmouseout="this.style.color='#94a3b8'; this.style.transform='scale(1)';">
                        &times;
                    </button>

                    <div style="width: 38px; height: 38px; border-radius: 50%; background: ${isActive ? '#dcfce7' : '#f1f5f9'}; display: flex; align-items: center; justify-content: center; color: ${isActive ? '#00c675' : '#64748b'}; font-size: 16px; flex-shrink: 0;">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div style="overflow: hidden; padding-right: 14px; min-width: 0; flex: 1;">
                        <h5 style="margin: 0 0 2px 0; font-size: 14.5px; font-weight: 800; color: ${isActive ? '#00c675' : '#0f172a'}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.kw}</h5>
                        <p style="margin: 0; font-size: 12.5px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${item.title}">${item.title}</p>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function deleteSavedLocation(event, id) {
            if (event) event.stopPropagation();
            let list = getSavedLocations();
            list = list.filter(item => item.id !== id);
            localStorage.setItem('savedLocationsList', JSON.stringify(list));
            
            if (activeSavedLocationId === id) {
                if (list.length > 0) {
                    selectSavedLocationById(list[0].id);
                } else {
                    activeSavedLocationId = null;
                    localStorage.removeItem('activeSavedLocationId');
                    localStorage.removeItem('userLocation');
                    localStorage.removeItem('userFullAddress');
                    localStorage.removeItem('userLat');
                    localStorage.removeItem('userLng');
                    updateNavLocationText();
                }
            }
            renderSavedLocations();
            if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend(false, list.length === 0 ? { force_empty_addresses: true } : {});
            showToast(isBangla ? "ঠিকানাটি মুছে ফেলা হয়েছে" : "Saved location deleted");
        }

        function selectSavedLocationById(id) {
            const list = getSavedLocations();
            const item = list.find(l => l.id === id);
            if (!item) return;

            const itemTitle = (item.title || '').trim();
            const itemFull = (item.fullAddress || item.title || '').trim();
            if (!isValidLocationString(itemTitle) && !isValidLocationString(itemFull)) {
                return;
            }

            hasSelectedValidLocation = true;
            activeSavedLocationId = id;
            localStorage.setItem('activeSavedLocationId', id);

            // Update user active location in navbar
            const locName = item.title || item.kw;
            localStorage.setItem('userLocation', locName);
            localStorage.setItem('userFullAddress', item.fullAddress || item.title);
            localStorage.setItem('userLat', item.lat);
            localStorage.setItem('userLng', item.lng);
            localStorage.setItem('userKeyword', item.kw);
            updateNavLocationText();

            // Update UI fields
            document.getElementById('sel-address-title').innerText = item.title;
            document.getElementById('sel-address-full').innerText = item.fullAddress || item.title;
            document.getElementById('sel-lat-val').innerText = Number(item.lat).toFixed(6);
            document.getElementById('sel-lng-val').innerText = Number(item.lng).toFixed(6);
            const searchInput = document.getElementById('loc-search-input');
            if (searchInput) searchInput.value = item.title;

            currentSelectedLat = parseFloat(item.lat);
            currentSelectedLng = parseFloat(item.lng);

            // Sync keyword chips without re-triggering location select
            selectedKeyword = item.kw;
            let matchingBtn = Array.from(document.querySelectorAll('.loc-keyword-btn')).find(b => b.innerText.trim().includes(item.kw));
            if (matchingBtn) {
                selectKeyword(matchingBtn, item.kw, false);
            } else {
                document.querySelectorAll('.loc-keyword-btn').forEach(b => {
                    b.style.background = 'white';
                    b.style.color = '#334155';
                    b.style.borderColor = '#e2e8f0';
                    b.classList.remove('active');
                });
            }

            if (locMap && locMarker) {
                locMap.setView([currentSelectedLat, currentSelectedLng], 15);
                locMarker.setLatLng([currentSelectedLat, currentSelectedLng]);
                updateMarkerPopup(item.kw, item.title);
            }

            renderSavedLocations();
            if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();
            if (typeof revalidateCartPrices === 'function') {
                revalidateCartPrices(item.fullAddress || item.title);
            }
        }

        function updateMarkerPopup(kw, title) {
            if (!locMarker) return;
            const displayTitle = title || document.getElementById('sel-address-title')?.innerText || 'Selected Location';
            let popupContent = '';
            if (kw) {
                popupContent = `
                    <div style="font-family: inherit; padding: 2px 4px; min-width: 130px;">
                        <div style="display: flex; align-items: center; gap: 6px; font-weight: 800; color: #00c675; font-size: 13px; margin-bottom: 3px;">
                            <i class="fa-solid fa-location-dot"></i> <span>${kw}</span>
                        </div>
                        <div style="font-size: 12px; color: #334155; font-weight: 600; line-height: 1.3;">
                            ${displayTitle}
                        </div>
                    </div>
                `;
            } else {
                popupContent = `
                    <div style="font-family: inherit; padding: 3px 6px; min-width: 120px;">
                        <div style="font-size: 13px; color: #0f172a; font-weight: 700; line-height: 1.3;">
                            ${displayTitle}
                        </div>
                    </div>
                `;
            }
            locMarker.bindPopup(popupContent).openPopup();
        }

        function selectKeyword(btn, kw, triggerLocationSelect = true) {
            document.querySelectorAll('.loc-keyword-btn').forEach(b => {
                b.style.background = 'white';
                b.style.color = '#334155';
                b.style.borderColor = '#e2e8f0';
                b.classList.remove('active');
            });
            if (btn) {
                btn.style.background = '#00c675';
                btn.style.color = 'white';
                btn.style.borderColor = '#00c675';
                btn.classList.add('active');
            }
            selectedKeyword = kw;

            // Hide custom input box if choosing standard
            const customBox = document.getElementById('custom-keyword-box');
            if (customBox && !btn?.classList.contains('custom-created')) {
                customBox.style.display = 'none';
            }

            // If clicked directly from Save As chips, auto-select matching saved location card
            if (triggerLocationSelect) {
                const list = getSavedLocations();
                const matchedSaved = list.find(l => l.kw && l.kw.trim().toLowerCase() === kw.trim().toLowerCase());
                if (matchedSaved) {
                    selectSavedLocationById(matchedSaved.id);
                } else {
                    activeSavedLocationId = null;
                    renderSavedLocations();
                }
            }
        }

        function toggleCustomKeywordInput() {
            const box = document.getElementById('custom-keyword-box');
            const input = document.getElementById('custom-keyword-input');
            if (!box) return;

            if (box.style.display === 'none' || !box.style.display) {
                box.style.display = 'block';
                input.focus();
                // Deselect other keyword buttons
                document.querySelectorAll('.loc-keyword-btn').forEach(b => {
                    b.style.background = 'white';
                    b.style.color = '#334155';
                    b.style.borderColor = '#e2e8f0';
                    b.classList.remove('active');
                });
                if (input.value.trim()) {
                    selectedKeyword = input.value.trim();
                } else {
                    selectedKeyword = 'Other';
                }
            } else {
                box.style.display = 'none';
            }
        }

        function handleCustomKeywordInput(val) {
            const trimmed = val.trim();
            selectedKeyword = trimmed || 'Other';
        }

        function handleCustomKeywordEnter(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = e.target.value.trim();
                if (val) {
                    selectedKeyword = val;
                    const container = document.getElementById('save-as-chips-container');
                    const addBtn = document.getElementById('add-custom-tag-btn');
                    
                    const newBtn = document.createElement('button');
                    newBtn.type = 'button';
                    newBtn.className = 'loc-keyword-btn custom-created';
                    newBtn.style.cssText = 'padding: 8px 18px; border-radius: 50px; border: 1.5px solid #00c675; background: #00c675; color: white; cursor: pointer; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;';
                    newBtn.innerHTML = `<i class="fa-solid fa-tag"></i> ${val}`;
                    newBtn.onclick = function() { selectKeyword(this, val); };
                    
                    container.insertBefore(newBtn, addBtn);
                    selectKeyword(newBtn, val);
                    document.getElementById('custom-keyword-box').style.display = 'none';
                    showToast(isBangla ? `"${val}" ট্যাগ যুক্ত হয়েছে!` : `Tag "${val}" added!`);
                }
            }
        }

        function onLocationMoved(lat, lng) {
            hasSelectedValidLocation = true;
            currentSelectedLat = lat;
            currentSelectedLng = lng;
            document.getElementById('sel-lat-val').innerText = lat.toFixed(6);
            document.getElementById('sel-lng-val').innerText = lng.toFixed(6);
            if (locMarker) {
                locMarker.setLatLng([lat, lng]);
            }

            // Unselect previous saved card because the user moved to a new location
            activeSavedLocationId = null;
            renderSavedLocations();

            // Reverse geocoding via Nominatim OpenStreetMap
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.display_name) {
                        const shortName = data.address?.road || data.address?.neighbourhood || data.address?.suburb || data.address?.city || 'Selected Location';
                        document.getElementById('sel-address-title').innerText = shortName;
                        document.getElementById('sel-address-full').innerText = data.display_name;
                        const searchInput = document.getElementById('loc-search-input');
                        if (searchInput) searchInput.value = shortName;
                        updateMarkerPopup(null, shortName);
                    }
                })
                .catch(() => {
                    updateMarkerPopup(null, 'Selected Location');
                });
        }

        function openLocationModal() {
            document.getElementById('location-modal').style.display = 'flex';
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            renderSavedLocations();

            const savedTitle = isLoggedIn ? localStorage.getItem('userLocation') : null;
            const savedFull = isLoggedIn ? localStorage.getItem('userFullAddress') : null;
            const savedLat = isLoggedIn ? parseFloat(localStorage.getItem('userLat')) : NaN;
            const savedLng = isLoggedIn ? parseFloat(localStorage.getItem('userLng')) : NaN;
            const effLat = !isNaN(savedLat) ? savedLat : 23.810331;
            const effLng = !isNaN(savedLng) ? savedLng : 90.412521;

            if (isLoggedIn && savedTitle && isValidLocationString(savedTitle) && (!savedFull || isValidLocationString(savedFull))) {
                hasSelectedValidLocation = true;
                currentSelectedLat = effLat;
                currentSelectedLng = effLng;
                document.getElementById('sel-address-title').innerText = savedTitle;
                document.getElementById('sel-address-full').innerText = savedFull || savedTitle;
                document.getElementById('sel-lat-val').innerText = effLat.toFixed(6);
                document.getElementById('sel-lng-val').innerText = effLng.toFixed(6);
                const searchInput = document.getElementById('loc-search-input');
                if (searchInput) searchInput.value = savedTitle;
            } else {
                hasSelectedValidLocation = false;
                currentSelectedLat = 23.810331;
                currentSelectedLng = 90.412521;
                document.getElementById('sel-address-title').innerText = isBangla ? 'কোনো ঠিকানা নির্বাচিত হয়নি' : 'No Address Selected';
                document.getElementById('sel-address-full').innerText = isBangla ? 'ম্যাপে পিন করুন অথবা উপরে লোকেশন সার্চ করুন।' : 'Please search or pinpoint your location on the map.';
                document.getElementById('sel-lat-val').innerText = '23.810331';
                document.getElementById('sel-lng-val').innerText = '90.412521';
                const searchInput = document.getElementById('loc-search-input');
                if (searchInput) searchInput.value = '';
            }

            setTimeout(() => {
                const activeSaved = isLoggedIn ? getSavedLocations().find(l => l.id === activeSavedLocationId) : null;
                const popupTitle = activeSaved ? activeSaved.title : (isLoggedIn && savedTitle ? savedTitle : (isBangla ? 'আপনার লোকেশন' : 'Your Location'));
                const popupKw = activeSaved ? activeSaved.kw : null;

                if (!locMap) {
                    locMap = L.map('nav-leaflet-map').setView([currentSelectedLat, currentSelectedLng], 14);
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(locMap);

                    // Classic Blue Map Pin (exact match to user screenshot)
                    const classicBluePin = L.icon({
                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    locMarker = L.marker([currentSelectedLat, currentSelectedLng], { draggable: true, icon: classicBluePin }).addTo(locMap);
                    updateMarkerPopup(popupKw, popupTitle);

                    // Dragging the pointer
                    locMarker.on('dragend', () => {
                        const pos = locMarker.getLatLng();
                        onLocationMoved(pos.lat, pos.lng);
                    });

                    // Clicking anywhere on the map moves the pointer there!
                    locMap.on('click', (e) => {
                        onLocationMoved(e.latlng.lat, e.latlng.lng);
                    });
                } else {
                    locMap.invalidateSize();
                    locMap.setView([currentSelectedLat, currentSelectedLng], 14);
                    locMarker.setLatLng([currentSelectedLat, currentSelectedLng]);
                    updateMarkerPopup(popupKw, popupTitle);
                }
            }, 250);
        }

        function closeLocationModal() {
            document.getElementById('location-modal').style.display = 'none';
        }

        function useCurrentLocation() {
            if (navigator.geolocation) {
                showToast(isBangla ? "লোকেশন খোঁজা হচ্ছে..." : "Fetching current location...");
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        hasSelectedValidLocation = true;
                        if (locMap) locMap.setView([lat, lng], 16);
                        onLocationMoved(lat, lng);
                        showToast(isBangla ? "বর্তমান লোকেশন সফলভাবে সেট হয়েছে!" : "Current location detected!");
                    },
                    (err) => {
                        showToast(isBangla ? "লোকেশন পাওয়া যায়নি। পারমিশন অন করুন।" : "Could not fetch GPS. Please enable permission.", "error");
                    }
                );
            } else {
                showToast("Geolocation is not supported by your browser.", "error");
            }
        }

        function handleLocationSearch(e) {
            if (e.key === 'Enter') {
                const q = document.getElementById('loc-search-input')?.value.trim();
                if (!q) return;
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q + ', Bangladesh')}`)
                    .then(r => r.json())
                    .then(results => {
                        if (results && results.length > 0) {
                            const first = results[0];
                            const lat = parseFloat(first.lat);
                            const lng = parseFloat(first.lon);
                            hasSelectedValidLocation = true;
                            currentSelectedLat = lat;
                            currentSelectedLng = lng;

                            document.getElementById('sel-address-title').innerText = q;
                            document.getElementById('sel-address-full').innerText = first.display_name;
                            document.getElementById('sel-lat-val').innerText = lat.toFixed(6);
                            document.getElementById('sel-lng-val').innerText = lng.toFixed(6);

                            activeSavedLocationId = null;
                            renderSavedLocations();

                            if (locMap && locMarker) {
                                locMap.setView([lat, lng], 16);
                                locMarker.setLatLng([lat, lng]);
                                updateMarkerPopup(null, q);
                            }
                        } else {
                            showToast(isBangla ? "লোকেশন পাওয়া যায়নি।" : "Location not found.", "error");
                        }
                    })
                    .catch(() => {
                        showToast("Search failed.", "error");
                    });
            }
        }

        function clearSelectedAddress() {
            document.getElementById('sel-address-title').innerText = isBangla ? 'কোনো ঠিকানা নির্বাচিত হয়নি' : 'No Address Selected';
            document.getElementById('sel-address-full').innerText = isBangla ? 'ম্যাপে পিন করুন অথবা উপরে লোকেশন সার্চ করুন।' : 'Please search or pinpoint your location on the map.';
            document.getElementById('sel-lat-val').innerText = '0.000000';
            document.getElementById('sel-lng-val').innerText = '0.000000';
            const searchInput = document.getElementById('loc-search-input');
            if (searchInput) searchInput.value = '';
            activeSavedLocationId = null;
            hasSelectedValidLocation = false;
            renderSavedLocations();
        }

        function getValidUserLocation() {
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            if (isLoggedIn) {
                const list = getSavedLocations();
                const activeId = localStorage.getItem('activeSavedLocationId') || activeSavedLocationId;
                if (activeId && list && list.length > 0) {
                    const matched = list.find(l => l.id === activeId);
                    if (matched && (matched.fullAddress || matched.title)) {
                        const val = (matched.fullAddress || matched.title).trim();
                        if (isValidLocationString(val)) return val;
                    }
                }
                if (list && list.length > 0) {
                    const first = list[0];
                    if (first && (first.fullAddress || first.title)) {
                        const val = (first.fullAddress || first.title).trim();
                        if (isValidLocationString(val)) return val;
                    }
                }
            }

            const full = (localStorage.getItem('userFullAddress') || '').trim();
            if (isValidLocationString(full)) return full;

            const loc = (localStorage.getItem('userLocation') || '').trim();
            if (isValidLocationString(loc)) return loc;

            return null;
        }

        function ensureUserLocation(callback) {
            const loc = getValidUserLocation();
            if (!loc) {
                showToast(isBangla ? "অনুগ্রহ করে প্রথমে আপনার ডেলিভারি ঠিকানা যুক্ত করুন।" : "Please add your delivery address first.", "error");
                if (typeof openLocationModal === 'function') {
                    openLocationModal();
                }
                return null;
            }
            if (typeof callback === 'function') {
                callback(loc);
            }
            return loc;
        }

        window.isValidLocationString = isValidLocationString;
        window.getValidUserLocation = getValidUserLocation;
        window.ensureUserLocation = ensureUserLocation;

        function saveLocation() {
            const title = document.getElementById('sel-address-title')?.innerText?.trim() || '';
            const full = document.getElementById('sel-address-full')?.innerText?.trim() || title;
            const lat = parseFloat(document.getElementById('sel-lat-val')?.innerText || currentSelectedLat);
            const lng = parseFloat(document.getElementById('sel-lng-val')?.innerText || currentSelectedLng);

            if (!hasSelectedValidLocation || !isValidLocationString(full) || !isValidLocationString(title)) {
                showToast(isBangla ? "অনুগ্রহ করে ম্যাপে ক্লিক করে পিন করুন অথবা উপরে লোকেশন সার্চ করে সঠিক ঠিকানা নির্বাচন করুন।" : "Please pinpoint your location on the map or search and select a valid address.", "error");
                return;
            }

            let list = getSavedLocations();
            
            // Check if item with this keyword already exists to update it, or add new
            const existingIndex = list.findIndex(item => item.kw && item.kw.toLowerCase() === selectedKeyword.toLowerCase());
            const newLoc = {
                id: (existingIndex > -1) ? list[existingIndex].id : ('loc_' + Date.now()),
                kw: selectedKeyword,
                title: title,
                fullAddress: full,
                lat: lat,
                lng: lng
            };

            if (existingIndex > -1) {
                list[existingIndex] = newLoc;
            } else {
                list.push(newLoc);
            }

            localStorage.setItem('savedLocationsList', JSON.stringify(list));
            activeSavedLocationId = newLoc.id;
            localStorage.setItem('activeSavedLocationId', newLoc.id);

            localStorage.setItem('userLocation', title);
            localStorage.setItem('userKeyword', selectedKeyword);
            localStorage.setItem('userFullAddress', full);
            localStorage.setItem('userLat', lat);
            localStorage.setItem('userLng', lng);

            renderSavedLocations();
            updateNavLocationText();
            if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();
            if (typeof revalidateCartPrices === 'function') {
                revalidateCartPrices(full || title);
            }
            closeLocationModal();
            showToast(isBangla ? "ডেলিভারি ঠিকানা সেভ করা হয়েছে!" : "Delivery address saved successfully!");
        }

        function updateNavLocationText() {
            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
            const list = isLoggedIn ? getSavedLocations() : [];
            const activeId = isLoggedIn ? (localStorage.getItem('activeSavedLocationId') || activeSavedLocationId) : null;
            let matched = list.find(l => l.id === activeId);
            
            let savedTitle = '';
            let savedKw = '';

            if (isLoggedIn) {
                if (matched) {
                    savedTitle = matched.title;
                    savedKw = matched.kw;
                } else {
                    savedTitle = localStorage.getItem('userLocation');
                    savedKw = localStorage.getItem('userKeyword');
                }
            }

            // Fallback for savedKw if title was formatted as "Keyword - Title"
            if (!savedKw && savedTitle && savedTitle.includes(' - ')) {
                const parts = savedTitle.split(' - ');
                savedKw = parts[0];
                savedTitle = parts.slice(1).join(' - ');
            }

            const kwEl = document.getElementById('nav-location-keyword');
            const el = document.getElementById('nav-location-text');
            const mobEl = document.getElementById('mobile-drawer-loc-text');

            if (isLoggedIn && savedTitle && isValidLocationString(savedTitle)) {
                if (kwEl) {
                    kwEl.innerText = savedKw || 'Location';
                    kwEl.style.display = 'block';
                    kwEl.style.color = '#0f172a'; // Black color as requested
                }
                if (el) {
                    el.innerText = savedTitle;
                    el.title = `${savedKw || 'Location'} - ${savedTitle}`;
                }
                if (mobEl) {
                    mobEl.innerHTML = `<span style="font-weight: 800; color: #0f172a;">${savedKw || 'Location'}:</span> ${savedTitle}`;
                    mobEl.title = `${savedKw || 'Location'} - ${savedTitle}`;
                }
            } else {
                if (kwEl) {
                    kwEl.innerText = isBangla ? "ডেলিভারি" : "Delivery";
                    kwEl.style.display = 'block';
                    kwEl.style.color = '#0f172a';
                }
                if (el) {
                    el.innerText = isBangla ? "লোকেশন যুক্ত করুন" : "Add Location";
                    el.removeAttribute('title');
                }
                if (mobEl) {
                    mobEl.innerText = isBangla ? "লোকেশন যুক্ত করুন" : "Add Location";
                    mobEl.removeAttribute('title');
                }
            }
        }

        // Purge any legacy placeholder strings if previously saved in localStorage
        (function cleanupLegacyPlaceholderLocations() {
            try {
                const curFull = localStorage.getItem('userFullAddress');
                const curLoc = localStorage.getItem('userLocation');
                if ((curFull && !isValidLocationString(curFull)) || (curLoc && !isValidLocationString(curLoc))) {
                    localStorage.removeItem('userFullAddress');
                    localStorage.removeItem('userLocation');
                    localStorage.removeItem('userLat');
                    localStorage.removeItem('userLng');
                    localStorage.removeItem('activeSavedLocationId');
                }
                const rawList = localStorage.getItem('savedLocationsList');
                if (rawList) {
                    const parsed = JSON.parse(rawList) || [];
                    const filtered = parsed.filter(i => isValidLocationString(i.title) || isValidLocationString(i.fullAddress));
                    if (filtered.length !== parsed.length) {
                        localStorage.setItem('savedLocationsList', JSON.stringify(filtered));
                    }
                }
            } catch(e) {}
        })();

        // 7. Live Multi-Order Tracking Engine
        let trackingPollingTimer = null;
        let cachedTrackingOrders = [];
        let activeTrackingOrderId = null;

        function openTrackOrderModal(targetOrderId = null) {
            const modal = document.getElementById('track-order-modal');
            if (modal) {
                modal.style.display = 'flex';
                const trackInput = document.getElementById('track-order-input');
                if (trackInput) {
                    trackInput.placeholder = isBangla ? 'আপনার ইনভয়েস বা অর্ডার নম্বরটি লিখুন' : 'Enter your order / invoice number';
                }
                loadLiveTrackingOrders(targetOrderId);
                
                // Clear any previous interval and start live 15s auto-polling
                if (trackingPollingTimer) clearInterval(trackingPollingTimer);
                trackingPollingTimer = setInterval(() => {
                    refreshLiveTrackingData(false);
                }, 15000);
            }
        }

        function closeTrackOrderModal() {
            const modal = document.getElementById('track-order-modal');
            if (modal) modal.style.display = 'none';
            if (trackingPollingTimer) {
                clearInterval(trackingPollingTimer);
                trackingPollingTimer = null;
            }
        }

        function refreshLiveTrackingData(showIndicator = true) {
            const refreshIcon = document.getElementById('track-refresh-icon');
            if (refreshIcon && showIndicator) refreshIcon.classList.add('fa-spin');
            
            const phone = localStorage.getItem('userPhone') || (window.serverCustomer ? window.serverCustomer.phone : '');
            
            if (phone) {
                fetch(`api/customer-data.php?phone=${encodeURIComponent(phone)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                        if (data.status === 'success') {
                            const rawOrders = data.orders || [];
                            const rawInvoices = data.invoices || [];
                            
                            // Merge and deduplicate by ID/db_id
                            const all = [];
                            const seen = new Set();
                            [...rawOrders, ...rawInvoices].forEach(item => {
                                const key = item.invoice_number || item.order_number || item.id || item.db_id;
                                if (!seen.has(key)) {
                                    seen.add(key);
                                    all.push(item);
                                }
                            });
                            
                            cachedTrackingOrders = all;
                            
                            if (activeTrackingOrderId) {
                                let activeItem = cachedTrackingOrders.find(o => (o.invoice_number === activeTrackingOrderId || o.order_number === activeTrackingOrderId || o.id === activeTrackingOrderId || (o.db_id && o.db_id.toString() === activeTrackingOrderId.toString())));
                                const activeRunning = cachedTrackingOrders.filter(o => ['delivering', 'processing', 'customer_confirmed', 'invoice_created', 'pending'].includes(o.status));
                                if (activeItem && ['cancelled', 'completed'].includes(activeItem.status) && activeRunning.length > 0) {
                                    activeItem = activeRunning[0];
                                    activeTrackingOrderId = activeItem.invoice_number || activeItem.order_number || activeItem.id;
                                    localStorage.setItem('lastTrackOrderId', activeTrackingOrderId);
                                }
                                renderTrackingPillsRow(cachedTrackingOrders, activeTrackingOrderId);
                                if (activeItem) {
                                    renderActiveTrackingView(activeItem);
                                }
                            } else {
                                renderTrackingPillsRow(cachedTrackingOrders, activeTrackingOrderId);
                            }
                        }
                    })
                    .catch(() => {
                        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                    });
            } else if (activeTrackingOrderId) {
                // Fetch single order by track_code
                fetch(`api/customer-data.php?track_code=${encodeURIComponent(activeTrackingOrderId)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                        if (data.status === 'success' && data.orders && data.orders.length > 0) {
                            cachedTrackingOrders = data.orders;
                            renderTrackingPillsRow(cachedTrackingOrders, data.orders[0].id);
                            renderActiveTrackingView(data.orders[0]);
                        }
                    })
                    .catch(() => {
                        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                    });
            } else {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
            }
        }

        function loadLiveTrackingOrders(targetOrderId = null) {
            const pillsRow = document.getElementById('track-order-pills-row');
            const panel = document.getElementById('track-active-order-panel');
            const phone = localStorage.getItem('userPhone') || (window.serverCustomer ? window.serverCustomer.phone : '');
            const lastTrackId = targetOrderId || localStorage.getItem('lastTrackOrderId') || localStorage.getItem('lastOrderCode') || sessionStorage.getItem('lastOrderCode');

            // If we already have cached orders, render immediately for ZERO loading delay!
            if (cachedTrackingOrders && cachedTrackingOrders.length > 0) {
                let initOrd = null;
                if (lastTrackId) {
                    initOrd = cachedTrackingOrders.find(o => (o.invoice_number === lastTrackId || o.order_number === lastTrackId || o.id === lastTrackId || (o.db_id && o.db_id.toString() === lastTrackId.toString())));
                }
                const activeRunningCached = cachedTrackingOrders.filter(o => ['delivering', 'processing', 'customer_confirmed', 'invoice_created', 'pending'].includes(o.status));
                if (!targetOrderId && initOrd && ['cancelled', 'completed'].includes(initOrd.status) && activeRunningCached.length > 0) {
                    initOrd = activeRunningCached[0];
                }
                if (!initOrd) {
                    initOrd = activeRunningCached[0] || cachedTrackingOrders[0];
                }
                if (initOrd) {
                    activeTrackingOrderId = initOrd.invoice_number || initOrd.order_number || initOrd.id;
                    renderTrackingPillsRow(cachedTrackingOrders, activeTrackingOrderId);
                    renderActiveTrackingView(initOrd);
                }
            } else if (pillsRow) {
                pillsRow.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px; padding: 4px 8px;">
                        <i class="fa-solid fa-spinner fa-spin" style="color: #10b981;"></i>
                        <span>${isBangla ? 'আপনার অর্ডারসমূহ লোড হচ্ছে...' : 'Loading active orders...'}</span>
                    </div>
                `;
            }

            if (!phone && !lastTrackId) {
                renderGuestTrackingInitialState();
                return;
            }

            let url = '';
            if (phone) {
                url = `api/customer-data.php?phone=${encodeURIComponent(phone)}`;
            } else {
                url = `api/customer-data.php?track_code=${encodeURIComponent(lastTrackId)}`;
            }

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data && data.status === 'success') {
                        const all = [];
                        const seen = new Set();
                        const list = data.orders || [];
                        const invList = data.invoices || [];
                        [...list, ...invList].forEach(item => {
                            const key = item.invoice_number || item.order_number || item.id || item.db_id;
                            if (key && !seen.has(key)) {
                                seen.add(key);
                                all.push(item);
                            }
                        });

                        cachedTrackingOrders = all;

                        if (cachedTrackingOrders.length === 0) {
                            renderGuestTrackingInitialState(isBangla ? 'আপনার কোনো চলমান অর্ডার পাওয়া যায়নি।' : 'No active orders found.');
                            return;
                        }

                        // Determine active order (prioritize running active orders over cancelled/completed on fresh load/refresh)
                        let initialOrder = null;
                        if (lastTrackId) {
                            initialOrder = cachedTrackingOrders.find(o => (o.invoice_number === lastTrackId || o.order_number === lastTrackId || o.id === lastTrackId || (o.db_id && o.db_id.toString() === lastTrackId.toString())));
                        }
                        const activeRunningList = cachedTrackingOrders.filter(o => ['delivering', 'processing', 'customer_confirmed', 'invoice_created', 'pending'].includes(o.status));
                        if (!targetOrderId && initialOrder && ['cancelled', 'completed'].includes(initialOrder.status) && activeRunningList.length > 0) {
                            initialOrder = activeRunningList[0];
                        }
                        if (!initialOrder) {
                            initialOrder = activeRunningList[0] || cachedTrackingOrders[0];
                        }

                        activeTrackingOrderId = initialOrder.invoice_number || initialOrder.order_number || initialOrder.id;
                        localStorage.setItem('lastTrackOrderId', activeTrackingOrderId);
                        renderTrackingPillsRow(cachedTrackingOrders, activeTrackingOrderId);
                        renderActiveTrackingView(initialOrder);
                    } else {
                        if (!cachedTrackingOrders || cachedTrackingOrders.length === 0) {
                            renderGuestTrackingInitialState(data?.message || (isBangla ? 'অর্ডারের লাইভ তথ্য দেখতে সার্চ করুন' : 'Search to view live tracking details'));
                        }
                    }
                })
                .catch(err => {
                    console.warn('Tracking fetch warning:', err);
                    if (!cachedTrackingOrders || cachedTrackingOrders.length === 0) {
                        renderGuestTrackingInitialState(isBangla ? 'অর্ডারের লাইভ তথ্য দেখতে উপরের বক্সে অর্ডার কোড বা ফোন নম্বর দিন।' : 'Enter your Order Code (e.g. ORD-...) or phone number in the search bar above.', false);
                    }
                });
        }

        function renderTrackingPillsRow(ordersList, activeId) {
            const pillsRow = document.getElementById('track-order-pills-row');
            if (!pillsRow) return;

            // Only show active / running orders in top pills (hide completed / delivered / cancelled)
            const activeOrders = (ordersList || []).filter(ord => {
                const st = (ord.status || '').toLowerCase();
                return st !== 'completed' && st !== 'delivered' && st !== 'cancelled';
            });

            if (activeOrders.length === 0) {
                pillsRow.innerHTML = `
                    <div style="font-size: 13px; color: #64748b; padding: 4px 8px; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                        <span>${isBangla ? 'কোনো রানিং অর্ডার নেই (পূর্বে ডেলিভার্ড অর্ডার দেখতে নিচে সার্চ করুন)' : 'No active running orders (search below to track delivered orders)'}</span>
                    </div>
                `;
                return;
            }

            pillsRow.innerHTML = '';
            activeOrders.forEach(ord => {
                const orderKey = ord.invoice_number || ord.order_number || ord.id;
                const isActive = (orderKey === activeId || (ord.db_id && ord.db_id.toString() === activeId?.toString()));
                
                let icon = 'fa-box';
                let stEn = 'Pending';
                let stBn = 'পেন্ডিং';
                let isPulse = false;

                if (ord.status === 'delivering') {
                    icon = 'fa-motorcycle';
                    stEn = 'On the Way';
                    stBn = 'রাস্তায় আছে';
                    isPulse = true;
                } else if (ord.status === 'processing' || ord.status === 'customer_confirmed') {
                    icon = 'fa-boxes-packing';
                    stEn = 'Processing';
                    stBn = 'প্রসেসিং';
                } else if (ord.status === 'invoice_created') {
                    icon = 'fa-file-invoice';
                    stEn = 'Confirm Req';
                    stBn = 'কনফার্ম করুন';
                }

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `track-pill-btn ${isActive ? 'active' : ''}`;
                btn.onclick = () => selectTrackingOrder(orderKey);

                btn.innerHTML = `
                    <i class="fa-solid ${icon}" style="${isPulse ? 'animation: bikeVibrate 0.8s infinite;' : ''}"></i>
                    <span>${orderKey}</span>
                    <span style="font-size: 11px; opacity: 0.9; padding: 1px 6px; border-radius: 8px; background: ${isActive ? 'rgba(255,255,255,0.25)' : '#f1f5f9'}; color: ${isActive ? '#ffffff' : '#475569'};">
                        ${isBangla ? stBn : stEn}
                    </span>
                `;

                pillsRow.appendChild(btn);
            });
        }

        function selectTrackingOrder(orderKey) {
            activeTrackingOrderId = orderKey;
            const target = cachedTrackingOrders.find(o => (o.invoice_number === orderKey || o.order_number === orderKey || o.id === orderKey || (o.db_id && o.db_id.toString() === orderKey.toString())));
            renderTrackingPillsRow(cachedTrackingOrders, activeTrackingOrderId);
            if (target) {
                renderActiveTrackingView(target);
            }
        }

        function trackerExtractListItemsFromContent(rawContent) {
            if (!rawContent) return [];
            const items = [];
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
            } catch (e) {}

            if (rawContent.includes('<table') || rawContent.includes('<tr')) {
                const temp = document.createElement('div');
                temp.innerHTML = rawContent;
                const trs = temp.querySelectorAll('tbody tr, table tr');
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
                            price = tds[2].innerText.replace('৳', '').trim();
                            qty = tds[3].innerText.trim() || '1';
                            total = tds[4].innerText.replace('৳', '').trim();
                        } else if (tds.length >= 3) {
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

                        if (itemText && !itemText.toLowerCase().includes('item description') && !itemText.toLowerCase().includes('পণ্যের নাম')) {
                            items.push({ item: itemText, spec: spec, qty: qty, price: price, total: total });
                        }
                    }
                });
                if (items.length > 0) return items;
            }

            const lines = rawContent.split('\n').map(l => l.trim()).filter(l => l.length > 0 && !l.includes('<div') && !l.includes('</div') && !l.includes('<p') && !l.includes('</p') && !l.includes('<table') && !l.includes('</table>'));
            lines.forEach(line => {
                const clean = line.replace(/^\d+[\.\-\)]\s*/, '').trim();
                if (clean) {
                    let itemText = clean;
                    let spec = '';
                    let qty = '1';
                    const parenMatch = clean.match(/^(.*?)\s*\((.*?)\)(.*)$/);
                    if (parenMatch) {
                        itemText = parenMatch[1].trim();
                        spec = parenMatch[2].trim();
                        const rest = parenMatch[3].trim();
                        if (rest) {
                            const qtyMatch = rest.match(/^[-–:|]\s*(.+)$/);
                            if (qtyMatch) qty = qtyMatch[1].trim();
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
                    items.push({ item: itemText, spec: spec, qty: qty, price: '', total: '' });
                }
            });
            return items;
        }

        function trackerParseListItems(rawText, imageUrls) {
            if (imageUrls && Array.isArray(imageUrls) && imageUrls.length > 0) {
                return { type: 'images', items: imageUrls };
            }
            if (!rawText) {
                return { type: 'rows', items: [] };
            }
            const items = trackerExtractListItemsFromContent(rawText);
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

        function trackerGenerateSingleA4PageHtml({ name, phone, location, orderNo, dateTime, contentHtml, pageIndex = 0, totalPages = 1, footerExtra = '' }) {
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
                        <p style="margin: -4px 0 0 0; font-size: 14px; color: #475569; font-weight: 700; font-family: Outfit, 'Hind Siliguri', sans-serif; line-height: 1.2;">
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
                            <div><strong style="color: #0f172a;">${orderNo.startsWith('LST-') ? 'Document:' : 'Order Type:'}</strong> <span style="color: #00c675; font-weight: 800;">${orderNo.startsWith('LST-') ? 'Custom Grocery List' : (orderNo.startsWith('ORD-') ? 'Grocery List Order' : 'Official Grocery Invoice')}</span></div>
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
                            <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 66px; width: auto; object-fit: contain;">
                        </div>
                        ${(isLastPage && footerExtra) ? footerExtra : `
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;">${(window.portalSettings && (window.isBangla ? window.portalSettings.supportHoursBn : window.portalSettings.supportHoursEn)) || '24/7 Support Center'}</span>
                            <strong style="font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;">${(window.portalSettings && window.portalSettings.hotlinePhone) ? window.portalSettings.hotlinePhone : '01919-668960'}</strong>
                        </div>`}
                    </div>
                </div>
            `;
        }

        function trackerGenerateA4PagesHtml({ name, phone, location, orderNo, dateTime, parsedData, footerExtra = '' }) {
            if (!parsedData || !parsedData.items || parsedData.items.length === 0) {
                return trackerGenerateSingleA4PageHtml({
                    name, phone, location, orderNo, dateTime,
                    contentHtml: '<p style="text-align: center; color: #64748b; padding: 30px; font-size: 14px;">No items found in this list.</p>',
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

                    fullHtml += trackerGenerateSingleA4PageHtml({
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
                            <td style="padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700;">${row.sl}</td>
                            <td style="padding: 10px 8px; color: #0f172a; font-size: 13.5px; font-weight: 700;">${row.item}${specHtml}</td>
                            ${hasPriceCol ? `<td style="padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13.5px; font-weight: 700;">${row.price ? '৳' + row.price : '-'}</td>` : ''}
                            <td style="padding: 10px 8px; color: #0f172a; text-align: right; font-size: 13.5px; font-weight: 800;">${row.qty || '1'}</td>
                            ${hasPriceCol ? `<td style="padding: 10px 8px; color: #0f172a; text-align: right; font-size: 13.5px; font-weight: 800;">${row.total ? '৳' + row.total : '-'}</td>` : ''}
                        </tr>
                    `;
                });

                const tableHtml = `
                    <table style="width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2;">
                        <thead>
                            <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a;">
                                <th style="padding: 11px 8px; color: #0f172a; font-size: 13px; font-weight: 800; width: 45px;">SL</th>
                                <th style="padding: 11px 8px; color: #0f172a; font-size: 13px; font-weight: 800;">Item Description</th>
                                ${hasPriceCol ? '<th style="padding: 11px 8px; color: #0f172a; text-align: center; font-size: 13px; font-weight: 800; width: 85px;">Price</th>' : ''}
                                <th style="padding: 11px 8px; color: #0f172a; text-align: right; font-size: 13px; font-weight: 800; width: 95px;">Quantity</th>
                                ${hasPriceCol ? '<th style="padding: 11px 8px; color: #0f172a; text-align: right; font-size: 13px; font-weight: 800; width: 95px;">Total</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                `;

                fullHtml += trackerGenerateSingleA4PageHtml({
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

        function renderActiveTrackingView(ord) {
            const panel = document.getElementById('track-active-order-panel');
            if (!panel || !ord) return;

            const orderKey = ord.invoice_number || ord.order_number || ord.id;
            const status = (ord.status || 'pending').toLowerCase();

            // Determine if Order is Custom Grocery List / Uploaded Photo List vs Website Direct Order
            const isCustomList = (ord.order_type === 'text_list' || ord.order_type === 'uploaded' || ord.order_type === 'custom_list' || (ord.order_number && ord.order_number.startsWith('ORD-')) || (ord.image_urls && ord.image_urls.length > 0));

            const getStepClass = (state) => {
                if (state === 'completed') return { circleBg: '#10b981', color: 'white', icon: 'fa-check', border: '#10b981', glow: 'none' };
                if (state === 'active') return { circleBg: '#10b981', color: 'white', icon: 'fa-spinner fa-spin', border: '#059669', glow: '0 0 0 5px rgba(16, 185, 129, 0.25)' };
                if (state === 'confirm_req') return { circleBg: '#f59e0b', color: 'white', icon: 'fa-file-invoice', border: '#d97706', glow: '0 0 0 5px rgba(245, 158, 11, 0.35)' };
                return { circleBg: '#ffffff', color: '#94a3b8', icon: 'fa-circle', border: '#cbd5e1', glow: 'none' };
            };

            let stepperHtml = '';
            let isCancelled = (status === 'cancelled');

            if (isCustomList) {
                // 5-STEP WORKFLOW FOR CUSTOM LIST / PHOTO ORDERS:
                // 1. List Placed -> 2. Pricing / Invoice -> 3. Shopping / Packing -> 4. On The Way -> 5. Delivered
                let s1 = 'completed', s2 = 'pending', s3 = 'pending', s4 = 'pending', s5 = 'pending';
                let stepFraction = 0;

                if (status === 'pending') {
                    s1 = 'active';
                    stepFraction = 0;
                } else if (status === 'invoice_created') {
                    s1 = 'completed';
                    s2 = 'confirm_req';
                    stepFraction = 0.25;
                } else if (status === 'customer_confirmed' || status === 'processing' || status === 'accepted') {
                    s1 = 'completed';
                    s2 = 'completed';
                    s3 = 'active';
                    stepFraction = 0.50;
                } else if (status === 'delivering') {
                    s1 = 'completed';
                    s2 = 'completed';
                    s3 = 'completed';
                    s4 = 'active';
                    stepFraction = 0.75;
                } else if (status === 'completed' || status === 'delivered') {
                    s1 = 'completed';
                    s2 = 'completed';
                    s3 = 'completed';
                    s4 = 'completed';
                    s5 = 'completed';
                    stepFraction = 1;
                }

                const st1Style = getStepClass(s1);
                const st2Style = getStepClass(s2);
                const st3Style = getStepClass(s3);
                const st4Style = getStepClass(s4);
                const st5Style = getStepClass(s5);

                stepperHtml = `
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 18px; padding: 22px 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); position: relative; overflow-x: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; position: relative; min-width: 460px; padding: 0 10px;">
                        <div style="position: absolute; top: 18px; left: 35px; right: 35px; height: 4px; background: #e2e8f0; z-index: 1;"></div>
                        <div style="position: absolute; top: 18px; left: 35px; width: calc((100% - 70px) * ${stepFraction}); max-width: calc(100% - 70px); height: 4px; background: linear-gradient(90deg, #10b981, #059669); z-index: 2; transition: width 0.4s ease;"></div>

                        <!-- Step 1: List Placed -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 75px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st1Style.circleBg}; border: 2.5px solid ${st1Style.border}; color: ${st1Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st1Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${st1Style.icon}"></i>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'ফর্দ জমা' : 'List Placed'}</span>
                            <span style="font-size: 10px; color: #64748b;">${isBangla ? 'পেন্ডিং' : 'Submitted'}</span>
                        </div>

                        <!-- Step 2: Pricing / Invoice -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 75px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st2Style.circleBg}; border: 2.5px solid ${st2Style.border}; color: ${st2Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st2Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${st2Style.icon}"></i>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'দরদাম/ইনভয়েস' : 'Pricing'}</span>
                            <span style="font-size: 10px; color: ${s2 === 'confirm_req' ? '#d97706; font-weight: 800;' : '#64748b;'}">${s2 === 'confirm_req' ? (isBangla ? 'কনফার্ম করুন' : 'Confirm Req') : (isBangla ? 'ইনভয়েস' : 'Invoice')}</span>
                        </div>

                        <!-- Step 3: Processing & Packing -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 75px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st3Style.circleBg}; border: 2.5px solid ${st3Style.border}; color: ${st3Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st3Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${s3 === 'active' ? 'fa-boxes-packing' : st3Style.icon}"></i>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'প্রসেসিং' : 'Processing'}</span>
                            <span style="font-size: 10px; color: #64748b;">${isBangla ? 'প্যাকিং' : 'Packing'}</span>
                        </div>

                        <!-- Step 4: On The Way -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 75px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st4Style.circleBg}; border: 2.5px solid ${st4Style.border}; color: ${st4Style.color}; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: ${st4Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${s4 === 'active' ? 'fa-motorcycle' : st4Style.icon}" style="${s4 === 'active' ? 'animation: bikeVibrate 0.8s infinite;' : ''}"></i>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'অন দ্য ওয়ে' : 'On The Way'}</span>
                            <span style="font-size: 10px; color: #64748b;">${isBangla ? 'ডেলিভারি' : 'Delivery'}</span>
                        </div>

                        <!-- Step 5: Delivered -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 75px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st5Style.circleBg}; border: 2.5px solid ${st5Style.border}; color: ${st5Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st5Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${st5Style.icon}"></i>
                            </div>
                            <span style="font-size: 11.5px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'ডেলিভার্ড' : 'Delivered'}</span>
                            <span style="font-size: 10px; color: #64748b;">${isBangla ? 'সম্পন্ন' : 'Completed'}</span>
                        </div>
                    </div>
                </div>
                `;
            } else {
                // 4-STEP WORKFLOW FOR DIRECT WEBSITE / CATALOG ORDERS:
                // 1. Placed -> 2. Packing -> 3. On The Way -> 4. Delivered
                let s1 = 'completed', s2 = 'pending', s3 = 'pending', s4 = 'pending';
                let stepFraction = 0;

                if (status === 'pending') {
                    s1 = 'active';
                    stepFraction = 0;
                } else if (status === 'processing' || status === 'accepted' || status === 'customer_confirmed' || status === 'invoice_created') {
                    s1 = 'completed';
                    s2 = 'active';
                    stepFraction = 0.3333;
                } else if (status === 'delivering') {
                    s1 = 'completed';
                    s2 = 'completed';
                    s3 = 'active';
                    stepFraction = 0.6666;
                } else if (status === 'completed' || status === 'delivered') {
                    s1 = 'completed';
                    s2 = 'completed';
                    s3 = 'completed';
                    s4 = 'completed';
                    stepFraction = 1;
                }

                const st1Style = getStepClass(s1);
                const st2Style = getStepClass(s2);
                const st3Style = getStepClass(s3);
                const st4Style = getStepClass(s4);

                stepperHtml = `
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 18px; padding: 22px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
                        <div style="position: absolute; top: 18px; left: 40px; right: 40px; height: 4px; background: #e2e8f0; z-index: 1;"></div>
                        <div style="position: absolute; top: 18px; left: 40px; width: calc((100% - 80px) * ${stepFraction}); max-width: calc(100% - 80px); height: 4px; background: linear-gradient(90deg, #10b981, #059669); z-index: 2; transition: width 0.4s ease;"></div>

                        <!-- Step 1: Placed -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 80px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st1Style.circleBg}; border: 2.5px solid ${st1Style.border}; color: ${st1Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st1Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${st1Style.icon}"></i>
                            </div>
                            <span style="font-size: 12px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'Placed' : 'Placed'}</span>
                            <span style="font-size: 10.5px; color: #64748b;">${isBangla ? 'পেন্ডিং' : 'Pending'}</span>
                        </div>

                        <!-- Step 2: Processing & Packing -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 80px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st2Style.circleBg}; border: 2.5px solid ${st2Style.border}; color: ${st2Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st2Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${st2Style.icon}"></i>
                            </div>
                            <span style="font-size: 12px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'প্রসেসিং' : 'Processing'}</span>
                            <span style="font-size: 10.5px; color: #64748b;">${isBangla ? 'প্যাকিং' : 'Packing'}</span>
                        </div>

                        <!-- Step 3: Out for Delivery -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 80px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st3Style.circleBg}; border: 2.5px solid ${st3Style.border}; color: ${st3Style.color}; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: ${st3Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${s3 === 'active' ? 'fa-motorcycle' : st3Style.icon}" style="${s3 === 'active' ? 'animation: bikeVibrate 0.8s infinite;' : ''}"></i>
                            </div>
                            <span style="font-size: 12px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'On The Way' : 'On The Way'}</span>
                            <span style="font-size: 10.5px; color: #64748b;">${isBangla ? 'ডেলিভারি' : 'Delivery'}</span>
                        </div>

                        <!-- Step 4: Delivered -->
                        <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 80px; text-align: center;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${st4Style.circleBg}; border: 2.5px solid ${st4Style.border}; color: ${st4Style.color}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: ${st4Style.glow}; transition: all 0.3s;">
                                <i class="fa-solid ${st4Style.icon}"></i>
                            </div>
                            <span style="font-size: 12px; font-weight: 800; color: #0f172a; margin-top: 8px;">${isBangla ? 'Delivered' : 'Delivered'}</span>
                            <span style="font-size: 10.5px; color: #64748b;">${isBangla ? 'সম্পন্ন' : 'Completed'}</span>
                        </div>
                    </div>
                </div>
                `;
            }

            const customerName = ord.customer_name || localStorage.getItem('userName') || (window.serverCustomer ? window.serverCustomer.name : 'Customer');
            const customerPhone = ord.customer_phone || localStorage.getItem('userPhone') || (window.serverCustomer ? window.serverCustomer.phone : '');
            const customerAddress = ord.customer_address || localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || 'Address provided';
            const orderDate = ord.date || (ord.created_at ? new Date(ord.created_at).toLocaleDateString() : 'Today');

            // Build Authentic Official A4 Document Preview matching My Orders / My Invoices
            let rawHtml = ord.items_summary || ord.html_content || '';
            let renderedDocumentHtml = '';

            if (rawHtml && (rawHtml.includes('class="a4-page"') || rawHtml.includes('a4-page') || rawHtml.includes('Watermark') || rawHtml.includes('bazarkinundotcom_logo'))) {
                let cleanHtml = rawHtml;
                if (orderKey) {
                    cleanHtml = cleanHtml.replace(/LST-\d+-[A-Z0-9]+-\d+/gi, orderKey);
                    cleanHtml = cleanHtml.replace(/List Code:/gi, (orderKey.startsWith('INV-') ? 'Invoice No:' : 'Order No:'));
                    cleanHtml = cleanHtml.replace(/Custom Grocery List/gi, (orderKey.startsWith('INV-') ? 'Official Invoice' : 'Grocery List Order'));
                    cleanHtml = cleanHtml.replace(/Document:/gi, (orderKey.startsWith('INV-') ? 'Invoice Type:' : 'Order Type:'));
                }
                renderedDocumentHtml = cleanHtml;
            } else {
                let parsedData = null;
                if (ord.items && Array.isArray(ord.items) && ord.items.length > 0) {
                    const mappedItems = ord.items.map((it, idx) => {
                        const price = parseFloat(it.unit_price || 0);
                        const qty = parseInt(it.quantity || 1);
                        const lineTot = parseFloat(it.subtotal || (price * qty));
                        return {
                            sl: idx + 1,
                            item: it.product_name || 'Item',
                            spec: it.product_poriman || '',
                            qty: qty.toString(),
                            price: price > 0 ? price.toFixed(0) : '',
                            total: lineTot > 0 ? lineTot.toFixed(0) : ''
                        };
                    });
                    parsedData = { type: 'rows', items: mappedItems };
                } else {
                    parsedData = trackerParseListItems(rawHtml, ord.image_urls);
                }

                const totalAmt = parseFloat(ord.total_amount || 0);
                let footerExtra = '';
                if (totalAmt > 0) {
                    footerExtra = `
                        <div style="text-align: right; line-height: 1.4;">
                            <div style="font-size: 13px; color: #64748b; font-weight: 700;">Grand Total:</div>
                            <strong style="font-size: 20px; font-weight: 900; color: #00c675;">৳${totalAmt.toFixed(0)}</strong>
                        </div>
                    `;
                }

                renderedDocumentHtml = trackerGenerateA4PagesHtml({
                    name: customerName,
                    phone: customerPhone,
                    location: customerAddress,
                    orderNo: orderKey,
                    dateTime: orderDate,
                    parsedData: parsedData,
                    footerExtra: footerExtra
                });
            }

            const showConfirmBanner = (status === 'invoice_created');
            const confirmBannerHtml = showConfirmBanner ? `
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1.5px solid #f59e0b; border-radius: 16px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.15);">
                    <div style="display: flex; align-items: center; gap: 12px; max-width: 60%;">
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #f59e0b; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 14.5px; font-weight: 800; color: #92400e;">${isBangla ? 'রাইডার দরদাম করে ইনভয়েস পাঠিয়েছেন!' : 'Rider created the priced invoice!'}</h4>
                            <p style="margin: 2px 0 0; font-size: 12.5px; color: #78350f;">${isBangla ? 'বাজার কেনাকাটা ও ডেলিভারি শুরু করতে অনুগ্রহ করে ইনভয়েসটি কনফার্ম করুন অথবা না লাগলে বাতিল করুন।' : 'Please review and confirm this invoice so the rider can begin shopping, or cancel if not needed.'}</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" onclick="cancelOrderById('${orderKey}')" style="background: #ffffff; border: 1.5px solid #ef4444; color: #ef4444; padding: 9px 16px; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#ffffff'">
                            <i class="fa-solid fa-xmark"></i> <span>${isBangla ? 'অর্ডার বাতিল' : 'Cancel Order'}</span>
                        </button>
                        <button type="button" onclick="confirmInvoiceById('${orderKey}')" style="background: #00c675; color: white; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 3px 12px rgba(0, 198, 117, 0.35); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                            <i class="fa-solid fa-circle-check"></i> <span>${isBangla ? 'ইনভয়েস কনফার্ম করুন' : 'Confirm Invoice'}</span>
                        </button>
                    </div>
                </div>
            ` : '';

            panel.innerHTML = `
                ${confirmBannerHtml}
                ${isCancelled ? `
                <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 2px 0; font-size: 15px; font-weight: 800; color: #991b1b;">${isBangla ? 'অর্ডারটি বাতিল করা হয়েছে' : 'This order was cancelled'}</h4>
                        <p style="margin: 0; font-size: 12.5px; color: #b91c1c;">${isBangla ? 'বিস্তারিত জানতে আমাদের কাস্টমার কেয়ারে যোগাযোগ করুন।' : 'Please contact customer support for further assistance.'}</p>
                    </div>
                </div>
                ` : stepperHtml}

                <!-- Delivery Rider Card & Destination Address -->
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1.5px solid #a7f3d0; border-radius: 18px; padding: 18px 20px; margin-top: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: #00c675; color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; position: relative; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.35);">
                                <i class="fa-solid fa-motorcycle"></i>
                                <span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #22c55e; border: 2px solid white; border-radius: 50%;"></span>
                            </div>
                            <div>
                                <div style="font-size: 11px; font-weight: 800; color: #00c675; text-transform: uppercase; letter-spacing: 0.5px;">${isBangla ? 'DELIVERY RIDER' : 'DELIVERY RIDER'}</div>
                                <h4 style="margin: 2px 0 0; font-size: 16px; font-weight: 800; color: #0f172a;">${ord.rider_name || (isBangla ? 'রাইডার অ্যাসাইন করা হচ্ছে...' : 'Assigning delivery rider...')}</h4>
                                ${ord.status === 'delivering' ? `<span style="font-size: 11.5px; color: #047857; font-weight: 700;">⏱️ ${isBangla ? 'আনুমানিক সময়: ' + ((window.portalSettings && window.portalSettings.deliveryTimeEstimateBn) ? window.portalSettings.deliveryTimeEstimateBn : '<?= addslashes(htmlspecialchars($fDeliveryTimeEstimateBn)) ?>') : 'Estimated Time: ' + ((window.portalSettings && window.portalSettings.deliveryTimeEstimateEn) ? window.portalSettings.deliveryTimeEstimateEn : '<?= addslashes(htmlspecialchars($fDeliveryTimeEstimateEn)) ?>')}</span>` : ''}
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            ${(status === 'pending' && !isCancelled) ? `
                            <button type="button" onclick="cancelOrderById('${orderKey}')" style="background: #ffffff; border: 1.5px solid #fca5a5; color: #dc2626; padding: 8px 14px; border-radius: 10px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#ffffff'">
                                <i class="fa-solid fa-xmark"></i> <span>${isBangla ? 'বাতিল করুন' : 'Cancel'}</span>
                            </button>
                            ` : ''}

                            ${ord.rider_phone ? `
                            <a href="tel:${ord.rider_phone}" style="background: #00c675; color: white; border: none; padding: 10px 18px; border-radius: 12px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.35); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                                <i class="fa-solid fa-phone-volume"></i> <span>Call Rider</span>
                            </a>
                            ` : `
                            <a href="tel:${window.portalSettings ? window.portalSettings.hotlinePhone : '01919668960'}" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #334155; padding: 8px 14px; border-radius: 10px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                <i class="fa-solid fa-headset" style="color: #00c675;"></i> <span>Helpline</span>
                            </a>
                            `}
                        </div>
                    </div>

                    <!-- Destination address bar -->
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(16, 185, 129, 0.2); display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155;">
                        <i class="fa-solid fa-location-dot" style="color: #00c675; font-size: 15px;"></i>
                        <span><strong>Delivery to:</strong> ${customerAddress}</span>
                    </div>
                </div>

                <!-- Official My Orders / My Invoices Preview Container -->
                <div style="width: 100%; overflow-x: auto; margin-top: 18px; padding-bottom: 6px; -webkit-overflow-scrolling: touch;">
                    <div id="track-invoice-paper" style="width: 100%; max-width: 800px; margin: 0 auto; box-sizing: border-box;">
                        ${renderedDocumentHtml}
                    </div>
                </div>

                <!-- Action Buttons: Confirm Invoice / Print Receipt -->
                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 16px; flex-wrap: wrap;">
                    ${(showConfirmBanner && !isCancelled) ? `
                    <button type="button" onclick="cancelOrderById('${orderKey}')" style="background: #ffffff; border: 1.5px solid #ef4444; color: #ef4444; padding: 10px 18px; border-radius: 12px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#ffffff'">
                        <i class="fa-solid fa-xmark"></i> <span>${isBangla ? 'অর্ডার বাতিল' : 'Cancel Order'}</span>
                    </button>
                    <button type="button" onclick="confirmInvoiceFromTracker('${orderKey}')" style="background: linear-gradient(135deg, #00c675 0%, #059669 100%); color: white; border: none; padding: 10px 22px; border-radius: 12px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.35);">
                        <i class="fa-solid fa-check-double"></i> <span>${isBangla ? 'ইনভয়েস কনফার্ম করুন' : 'Confirm Invoice'}</span>
                    </button>
                    ` : ''}

                    <button type="button" onclick="printTrackerInvoice()" style="background: #ffffff; border: 1.5px solid #00c675; color: #00c675; padding: 9px 18px; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#ffffff'">
                        <i class="fa-solid fa-print"></i> <span>${isBangla ? 'প্রিন্ট / ডাউনলোড ইনভয়েস' : 'Print / Download Invoice'}</span>
                    </button>
                </div>
            `;
        }

        function cancelOrderById(targetId) {
            if (!targetId) return;
            const msg = isBangla
                ? `আপনি কি নিশ্চিত যে <strong>"${targetId}"</strong> অর্ডারটি বাতিল করতে চান?`
                : `Are you sure you want to cancel order <strong>"${targetId}"</strong>?`;

            showConfirm(msg, function() {
                const phone = localStorage.getItem('userPhone') || (window.serverCustomer ? window.serverCustomer.phone : '');
                const name = localStorage.getItem('userName') || (window.serverCustomer ? window.serverCustomer.name : 'Customer');

                fetch('api/submit-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'cancel_order',
                        order_id: targetId,
                        customer_phone: phone,
                        customer_name: name,
                        reason: 'Cancelled by customer'
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        showToast(res.message || (isBangla ? "অর্ডারটি সফলভাবে বাতিল করা হয়েছে!" : "Order cancelled successfully!"), "success");
                        localStorage.removeItem('lastTrackOrderId');
                        if (typeof loadCustomerProfile === 'function') loadCustomerProfile();
                        if (typeof refreshLiveTrackingData === 'function') refreshLiveTrackingData(true);
                        if (document.getElementById('invoice-view-modal')) document.getElementById('invoice-view-modal').style.display = 'none';
                    } else {
                        showToast(res.message || "অর্ডার বাতিল করা সম্ভব হয়নি।", "error");
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast("Server error cancelling order", "error");
                });
            }, {
                title: isBangla ? "অর্ডার বাতিল করবেন?" : "Cancel Order?",
                confirmText: isBangla ? "হ্যাঁ, বাতিল করুন" : "Yes, Cancel",
                cancelText: isBangla ? "না, রাখুন" : "No, Keep",
                type: "danger",
                icon: "fa-solid fa-trash-can"
            });
        }

        window.cancelOrderById = cancelOrderById;

        function confirmInvoiceFromTracker(invId) {
            if (!invId) return;
            const msg = isBangla
                ? `আপনি কি ইনভয়েস <strong>"${invId}"</strong> এর মূল্য ও পণ্যের বিবরণ নিশ্চিত করতে চান?`
                : `Do you want to confirm invoice <strong>"${invId}"</strong>?`;

            showConfirm(msg, function() {
                const phone = localStorage.getItem('userPhone') || (window.serverCustomer ? window.serverCustomer.phone : '');
                const name = localStorage.getItem('userName') || (window.serverCustomer ? window.serverCustomer.name : 'Customer');

                fetch('api/submit-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'confirm_invoice',
                        order_id: invId,
                        customer_phone: phone,
                        customer_name: name
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        showToast(res.message || (isBangla ? "ইনভয়েস সফলভাবে কনফার্ম করা হয়েছে!" : "Invoice confirmed successfully!"), "success");
                        refreshLiveTrackingData(true);
                    } else {
                        alert(res.message || "Could not confirm invoice");
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast("Server error confirming invoice", "error");
                });
            }, {
                title: isBangla ? "ইনভয়েস কনফার্মেশন" : "Confirm Invoice",
                confirmText: isBangla ? "হ্যাঁ, কনফার্ম করুন" : "Yes, Confirm",
                cancelText: isBangla ? "বাতিল" : "Cancel",
                type: "success",
                icon: "fa-solid fa-circle-check"
            });
        }

        if (typeof window.confirmInvoiceById !== 'function') {
            window.confirmInvoiceById = confirmInvoiceFromTracker;
        }

        function printTrackerInvoice() {
            const paper = document.getElementById('track-invoice-paper');
            if (!paper) return;
            const printWin = window.open('', '_blank', 'width=850,height=900');
            printWin.document.write(`
                <html>
                <head>
                    <title>Invoice - BazarKinun.com</title>
                    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
                    <style>
                        body { margin: 0; padding: 20px; font-family: 'Outfit', 'Hind Siliguri', sans-serif; background: #fff; }
                        .a4-page { box-shadow: none !important; margin: 0 auto 20px auto !important; }
                        @media print {
                            body { padding: 0; }
                            @page { margin: 10mm; }
                        }
                    </style>
                </head>
                <body>
                    ${paper.innerHTML}
                    <script>
                        window.onload = function() { window.print(); window.close(); }
                    <\/script>
                </body>
                </html>
            `);
            printWin.document.close();
        }

        function renderGuestTrackingInitialState(customMsg = null, isError = false) {
            const panel = document.getElementById('track-active-order-panel');

            if (panel) {
                panel.innerHTML = `
                    <div style="text-align: center; padding: 35px 20px; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 18px;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: ${isError ? '#fee2e2' : '#ecfdf5'}; color: ${isError ? '#ef4444' : '#10b981'}; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 12px auto;">
                            <i class="fa-solid ${isError ? 'fa-triangle-exclamation' : 'fa-magnifying-glass-location'}"></i>
                        </div>
                        <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a;">${customMsg || (isBangla ? 'অর্ডারের লাইভ তথ্য দেখতে সার্চ করুন' : 'Search to view live tracking details')}</h4>
                        <p style="margin: 0 0 16px 0; font-size: 13px; color: #64748b;">${isBangla ? 'উপরের সার্চ বক্সে আপনার অর্ডার কোড (যেমন: ORD-345678-RA-1) বা ফোন নম্বর দিন।' : 'Enter your Order Code (e.g. ORD-345678-RA-1) or phone number in the search bar above.'}</p>
                        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                            ${isError ? `
                            <button type="button" onclick="loadLiveTrackingOrders()" style="background: #10b981; color: white; border: none; padding: 9px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                                <i class="fa-solid fa-rotate-right"></i> <span>${isBangla ? 'পুনরায় চেষ্টা করুন' : 'Try Again'}</span>
                            </button>
                            ` : ''}
                            ${(!window.serverCustomer && !localStorage.getItem('userPhone')) ? `
                            <a href="login.php" style="background: #10b981; color: white; padding: 9px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> <span>${isBangla ? 'লগইন করে সব অর্ডার দেখুন' : 'Login to view all orders'}</span>
                            </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
        }

        function submitTrackSearch(e) {
            if (e) e.preventDefault();
            const input = document.getElementById('track-order-input');
            const searchKey = input ? input.value.trim() : '';
            if (!searchKey) {
                showToast(isBangla ? 'অনুগ্রহ করে একটি অর্ডার নম্বর বা ফোন নম্বর দিন।' : 'Please enter an order or phone number.', 'error');
                return;
            }

            const panel = document.getElementById('track-active-order-panel');
            if (panel) {
                panel.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #10b981; margin-bottom: 10px;"></i>
                        <p style="margin: 0;">${isBangla ? 'অর্ডার অনুসন্ধান করা হচ্ছে...' : 'Searching order...'}</p>
                    </div>
                `;
            }

            const fetchUrl = `api/customer-data.php?track_code=${encodeURIComponent(searchKey)}`;

            fetch(fetchUrl)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success' && data.orders && data.orders.length > 0) {
                        cachedTrackingOrders = data.orders;
                        activeTrackingOrderId = data.orders[0].invoice_number || data.orders[0].order_number || data.orders[0].id;
                        localStorage.setItem('lastTrackOrderId', activeTrackingOrderId);
                        renderTrackingPillsRow(cachedTrackingOrders, activeTrackingOrderId);
                        renderActiveTrackingView(data.orders[0]);
                    } else {
                        renderGuestTrackingInitialState(data.message || (isBangla ? 'কোনো অর্ডার পাওয়া যায়নি। অনুগ্রহ করে সঠিক কোড দিন।' : 'No order found. Please check the code.'));
                    }
                })
                .catch(() => {
                    renderGuestTrackingInitialState(isBangla ? 'অনুসন্ধান ব্যর্থ হয়েছে। আবার চেষ্টা করুন।' : 'Search failed. Please try again.');
                });
        }

        function confirmInvoiceFromTracker(invId) {
            if (!confirm(isBangla ? 'আপনি কি এই ইনভয়েসটি কনফার্ম করতে চান?' : 'Do you want to confirm this invoice?')) return;
            fetch('api/customer-data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'confirm_invoice', invoice_number: invId })
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message || 'Invoice confirmed successfully!', 'success');
                    refreshLiveTrackingData(true);
                } else {
                    alert(res.message || 'Failed to confirm invoice');
                }
            })
            .catch(() => {
                showToast('Failed to confirm invoice.', 'error');
            });
        }

        function viewInvoiceFromTracker(orderKey) {
            window.location.href = `profile.php?tab=order&focus=${encodeURIComponent(orderKey)}`;
        }

        // 8. User Auth Dropdown State
        function updateLoginUI() {
            let phone = localStorage.getItem('userPhone');
            let name = localStorage.getItem('userName') || 'Customer';
            let photo = localStorage.getItem('userPhoto') || '';
            let isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';

            if (window.serverCustomer && window.serverCustomer.phone) {
                isLoggedIn = true;
                phone = window.serverCustomer.phone;
                name = window.serverCustomer.name || name;
                if (window.serverCustomer.photo) photo = window.serverCustomer.photo;
            }

            const loginBtn = document.getElementById('signup-btn-nav');
            const profileBtn = document.getElementById('profile-btn');
            const profileText = document.getElementById('profile-text');
            const signupNotice = document.getElementById('signup-notification');
            const drawerProfileCard = document.getElementById('drawer-profile-card');
            const drawerLoginCard = document.getElementById('drawer-login-card');
            const drawerProfileName = document.getElementById('drawer-profile-name');
            const drawerProfileAvatar = document.getElementById('drawer-profile-avatar');

            if (isLoggedIn && (phone || (window.serverCustomer && window.serverCustomer.phone))) {
                if (loginBtn) {
                    loginBtn.style.setProperty('display', 'none', 'important');
                }
                if (profileBtn) {
                    profileBtn.style.setProperty('display', 'flex', 'important');
                    if (profileText) profileText.innerHTML = (name || 'Customer') + ' <i class="fa-solid fa-chevron-down" style="font-size: 11px; margin-left: 4px;"></i>';
                }
                if (drawerProfileCard) drawerProfileCard.style.display = 'flex';
                if (drawerLoginCard) drawerLoginCard.style.display = 'none';
                if (drawerProfileName) drawerProfileName.textContent = name || 'Customer';
                if (drawerProfileAvatar && photo) {
                    drawerProfileAvatar.innerHTML = `<img src="${photo}" style="width: 100%; height: 100%; object-fit: cover;">`;
                }
                if (signupNotice) signupNotice.style.display = 'none';
            } else {
                if (loginBtn) {
                    loginBtn.style.setProperty('display', 'flex', 'important');
                }
                if (profileBtn) {
                    profileBtn.style.setProperty('display', 'none', 'important');
                }
                if (drawerProfileCard) drawerProfileCard.style.display = 'none';
                if (drawerLoginCard) drawerLoginCard.style.display = 'block';
                if (signupNotice) signupNotice.style.display = 'flex';
            }

            // Hide Home & All Products link in drawer when on index page
            const drawerHomeLink = document.getElementById('drawer-home-link');
            const drawerShopLink = document.getElementById('drawer-shop-link');
            const path = window.location.pathname.toLowerCase();
            const isIndexPage = path.endsWith('index.php') || path === '/' || path.endsWith('/');
            if (drawerHomeLink && isIndexPage) {
                drawerHomeLink.style.display = 'none';
            }
            if (drawerShopLink && isIndexPage) {
                drawerShopLink.style.display = 'none';
            }

            updateCartBadge();
            updateWishlistBadge();
            updateNavLocationText();
        }

        function toggleLoginState() {
            // First flush any unsaved state immediately to database
            if (typeof syncUserStateToBackend === 'function') {
                syncUserStateToBackend(true);
            }

            // Destroy server-side session
            try {
                fetch('api/auth.php?action=logout', { method: 'POST' }).catch(() => {});
            } catch(e) {}

            // Clear all user-specific cache from localStorage
            localStorage.setItem('isLoggedIn', 'false');
            localStorage.removeItem('userName');
            localStorage.removeItem('userPhone');
            localStorage.removeItem('userLocation');
            localStorage.removeItem('userFullAddress');
            localStorage.removeItem('userLat');
            localStorage.removeItem('userLng');
            localStorage.removeItem('userKeyword');
            localStorage.removeItem('savedLocationsList');
            localStorage.removeItem('activeSavedLocationId');
            localStorage.removeItem('userCustomLists');
            localStorage.removeItem('cart');
            localStorage.removeItem('wishlist');

            updateLoginUI();
            updateCartBadge();
            updateWishlistBadge();
            updateNavLocationText();
            window.location.href = 'login.php';
        }

        document.getElementById('profile-btn')?.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profile-dropdown');
            if (dropdown) {
                dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
            }
            e.stopPropagation();
        });

        // Close profile dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profile-dropdown');
            const profileBtn = document.getElementById('profile-btn');
            if (dropdown && dropdown.style.display === 'block') {
                if (!profileBtn || !profileBtn.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            }
        });

        // 9. Legal Policy Modal System
        let currentLegalTab = 'about';
        function openLegalModal(tab = 'about') {
            currentLegalTab = tab;
            const modal = document.getElementById('legal-info-modal');
            if (modal) {
                modal.style.display = 'flex';
                switchLegalTab(tab);
            }
        }

        function closeLegalModal() {
            const modal = document.getElementById('legal-info-modal');
            if (modal) modal.style.display = 'none';
        }

        function switchLegalTab(tab) {
            currentLegalTab = tab;
            const titles = {
                about: { en: 'About Us', bn: 'আমাদের সম্পর্কে', icon: 'fa-solid fa-circle-info', color: '#2563eb', bg: '#dbeafe' },
                terms: { en: 'Terms & Conditions', bn: 'শর্তাবলী', icon: 'fa-solid fa-file-contract', color: '#7c3aed', bg: '#ede9fe' },
                privacy: { en: 'Privacy Policy', bn: 'প্রাইভেসি পলিসি', icon: 'fa-solid fa-user-shield', color: '#0d9488', bg: '#ccfbf1' },
                refund: { en: 'Return & Refund Policy', bn: 'রিটার্ন ও রিফান্ড নীতি', icon: 'fa-solid fa-rotate-left', color: '#ea580c', bg: '#ffedd5' }
            };

            const tInfo = titles[tab] || titles.about;
            const isBn = typeof isBangla !== 'undefined' ? isBangla : false;

            const titleEl = document.getElementById('legal-modal-title');
            if (titleEl) titleEl.innerText = isBn ? tInfo.bn : tInfo.en;

            const iconEl = document.getElementById('legal-modal-icon');
            if (iconEl) iconEl.className = tInfo.icon;

            const iconWrap = document.getElementById('legal-modal-icon-wrap');
            if (iconWrap) {
                iconWrap.style.background = tInfo.bg;
                iconWrap.style.color = tInfo.color;
            }

            // Update tab button active states
            ['about', 'terms', 'privacy', 'refund'].forEach(k => {
                const btn = document.getElementById('legal-tab-' + k);
                if (btn) {
                    if (k === tab) {
                        btn.style.background = '#2563eb';
                        btn.style.color = '#ffffff';
                        btn.style.borderColor = '#2563eb';
                        btn.style.boxShadow = '0 2px 8px rgba(37, 99, 235, 0.25)';
                    } else {
                        btn.style.background = '#ffffff';
                        btn.style.color = '#475569';
                        btn.style.borderColor = '#e2e8f0';
                        btn.style.boxShadow = 'none';
                    }
                }
            });

            // Get content from holder
            const langSuffix = isBn ? '-bn' : '-en';
            const holderEl = document.getElementById('legal-content-' + tab + langSuffix);
            const bodyEl = document.getElementById('legal-modal-body');
            if (bodyEl && holderEl) {
                bodyEl.innerHTML = holderEl.innerHTML || '<p style="color: #94a3b8; font-style: italic;">No content available yet.</p>';
            }
        }
        window.openLegalModal = openLegalModal;
        window.closeLegalModal = closeLegalModal;
        window.switchLegalTab = switchLegalTab;

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            updateCartBadge();
            updateWishlistBadge();
            updateNavLocationText();
            updateLoginUI();
            renderSavedLocations();
            ensureProductsCatalog();
            applyLanguage(isBangla);
        });
    </script>
</body>
</html>
