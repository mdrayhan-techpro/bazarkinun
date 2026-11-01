<?php
// cart.php
$pageTitle = 'My Shopping Cart - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';

// Fetch payment gateway settings
$paySettings = [
    'cod_enabled' => '1',
    'bkash_enabled' => '1',
    'bkash_number' => '01332563212',
    'bkash_type' => 'Personal',
    'nagad_enabled' => '1',
    'nagad_number' => '01332563212',
    'nagad_type' => 'Personal'
];
try {
    $psStmt = $pdo->query("SELECT setting_key, setting_value FROM portal_settings WHERE setting_key LIKE 'payment_%'");
    $dbPay = $psStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($dbPay['payment_cod_enabled'])) $paySettings['cod_enabled'] = $dbPay['payment_cod_enabled'];
    if (isset($dbPay['payment_bkash_enabled'])) $paySettings['bkash_enabled'] = $dbPay['payment_bkash_enabled'];
    if (!empty($dbPay['payment_bkash_number'])) $paySettings['bkash_number'] = $dbPay['payment_bkash_number'];
    if (!empty($dbPay['payment_bkash_type'])) $paySettings['bkash_type'] = $dbPay['payment_bkash_type'];
    if (isset($dbPay['payment_nagad_enabled'])) $paySettings['nagad_enabled'] = $dbPay['payment_nagad_enabled'];
    if (!empty($dbPay['payment_nagad_number'])) $paySettings['nagad_number'] = $dbPay['payment_nagad_number'];
    if (!empty($dbPay['payment_nagad_type'])) $paySettings['nagad_type'] = $dbPay['payment_nagad_type'];
} catch (Exception $e) {}
?>

<style>
    .cart-layout-row {
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }
    .cart-items-wrapper {
        flex: 2;
    }
    .cart-summary-box {
        flex: 1;
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        position: sticky;
        top: 90px;
        border: 1px solid #e2e8f0;
    }
    @media (max-width: 900px) {
        .cart-layout-row {
            flex-direction: column;
        }
        .cart-summary-box {
            width: 100%;
            box-sizing: border-box;
            position: static;
        }
    }

    @media (max-width: 640px) {
        #cart-container > div {
            padding: 14px 14px !important;
            flex-wrap: wrap !important;
            gap: 12px !important;
        }
        #cart-container > div > div:last-child {
            width: 100% !important;
            justify-content: space-between !important;
        }
        #preview-actions button, #preview-actions a {
            flex: 1 1 calc(50% - 6px) !important;
            padding: 10px 12px !important;
            font-size: 13px !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }

    @media (max-width: 480px) {
        .cart-summary-box {
            padding: 18px 14px !important;
            border-radius: 14px !important;
        }
        #preview-actions button, #preview-actions a {
            flex: 1 1 100% !important;
        }
    }

    @media (max-width: 374px) {
        #modal-products-list {
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)) !important;
            gap: 10px !important;
            padding: 14px 10px !important;
        }
    }

    @media (min-width: 1920px) {
        .cart-layout-row {
            gap: 36px;
        }
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<main style="padding: 30px 0; background-color: #f8fafc; min-height: 75vh;">
    <div class="container">
        <!-- Edit Invoice Banner (Shown only when in invoice edit session) -->
        <div id="edit-invoice-banner" style="display: none; background: #fefce8; border: 1.5px solid #fef08a; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 12px rgba(234, 179, 8, 0.08);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #f59e0b; color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 15.5px; color: #78350f;">
                        <span class="dynamic-lang" data-en="Editing Order / Invoice:" data-bn="অর্ডার / ইনভয়েস এডিট করা হচ্ছে:">অর্ডার / ইনভয়েস এডিট করা হচ্ছে:</span> 
                        <span id="editing-invoice-code-disp" style="color: #059669; font-weight: 900;">INV-XXXXXX</span>
                    </div>
                    <div style="font-size: 13px; color: #92400e; margin-top: 2px;">
                        <span class="dynamic-lang" data-en="Submitting will update this existing invoice (same invoice number). Cancelling leaves your regular cart untouched." data-bn="এডিট নিশ্চিত করলে এই ইনভয়েসটিই আপডেট হবে (ইনভয়েস নম্বর পরিবর্তন হবে না)। ব্যাক করলে নিয়মিত কার্ট সুরক্ষিত থাকবে।">এডিট নিশ্চিত করলে এই ইনভয়েসটিই আপডেট হবে (ইনভয়েস নম্বর পরিবর্তন হবে না)। ব্যাক করলে নিয়মিত কার্ট সুরক্ষিত থাকবে।</span>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" onclick="cancelInvoiceEdit()" style="background: white; border: 1.5px solid #d97706; color: #b45309; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;">
                    <i class="fa-solid fa-xmark"></i> <span class="dynamic-lang" data-en="Cancel & Return to Profile" data-bn="এডিট বাতিল করে প্রোফাইলে ফিরুন">এডিট বাতিল করে প্রোফাইলে ফিরুন</span>
                </button>
            </div>
        </div>

        <!-- Main Cart View -->
        <div id="cart-main-view" style="display: block;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <a href="shop.php" id="cart-back-btn" onclick="handleCartBack(event)" style="text-decoration: none; color: var(--primary-color); font-weight: 700; font-size: 13px; padding: 8px 16px; border: 1px solid var(--primary-color); border-radius: 8px; transition: 0.3s; background: white; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left"></i> <span id="cart-back-btn-text" class="dynamic-lang" data-en="Continue Shopping" data-bn="কেনাকাটা চালিয়ে যান">Continue Shopping</span>
                    </a>
                    <h1 id="cart-page-title" class="dynamic-lang" data-en="My Cart" data-bn="আমার কার্ট" style="font-size: 28px; color: #0f172a; margin: 0; font-weight: 900;">My Cart</h1>
                </div>
            </div>
            
            <div class="cart-layout-row">
                <!-- Cart Items Section -->
                <div class="cart-items-wrapper">
                    <div id="cart-container">
                        <!-- Cart items injected by JS -->
                    </div>

                    <!-- Add product bottom trigger banner -->
                    <div id="add-more-card-btn" style="display: none; margin-top: 16px;">
                        <button type="button" onclick="openAddProductModal()" style="width: 100%; background: #ffffff; border: 2px dashed #a7f3d0; color: #047857; padding: 16px; border-radius: 14px; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s;" onmouseover="this.style.background='#f0fdf4'; this.style.borderColor='#10b981'" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#a7f3d0'">
                            <i class="fa-solid fa-circle-plus" style="font-size: 20px; color: #10b981;"></i>
                            <span class="dynamic-lang" data-en="Click here to add more products to this order" data-bn="এই অর্ডারে আরও নতুন পণ্য যোগ করতে এখানে ক্লিক করুন">এই অর্ডারে আরও নতুন পণ্য যোগ করতে এখানে ক্লিক করুন</span>
                        </button>
                    </div>
                </div>

                <!-- Summary Section -->
                <div id="cart-summary" class="cart-summary-box" style="display: none;">
                    <h3 class="dynamic-lang" data-en="Order Summary" data-bn="অর্ডার বিবরণ" style="margin: 0 0 20px 0; color: #0f172a; font-size: 18px; font-weight: 800; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">Order Summary</h3>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
                        <span class="dynamic-lang" data-en="Regular Price" data-bn="রেগুলার দাম" style="color: #64748b;">Regular Price</span>
                        <span id="summary-regular-price" style="font-weight: 700; color: #1e293b;">৳0</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
                        <span class="dynamic-lang" data-en="Total Discount" data-bn="মোট ছাড়" style="color: #64748b;">Total Discount</span>
                        <span id="summary-discount" style="font-weight: 700; color: #ef4444;">-৳0</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
                        <span class="dynamic-lang" data-en="Total Price" data-bn="মোট মূল্য" style="color: #64748b;">Total Price</span>
                        <span id="summary-subtotal" style="font-weight: 700; color: #1e293b;">৳0</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
                        <span class="dynamic-lang" data-en="Delivery Charge" data-bn="ডেলিভারি চার্জ" style="color: #64748b;">Delivery Charge</span>
                        <span id="summary-delivery-charge" style="font-weight: 700; color: #1e293b;">৳60</span>
                    </div>

                    <!-- Promo / Coupon Code Section -->
                    <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 12px; margin: 16px 0 12px;">
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="cart-coupon-input" placeholder="COUPON CODE" style="flex: 1; min-width: 0; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; text-transform: uppercase; font-weight: 700; outline: none; font-family: inherit;" onfocus="this.style.borderColor='#10b981'">
                            <button type="button" id="cart-coupon-apply-btn" onclick="applyCartCoupon()" style="background: #0f172a; color: white; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 800; font-size: 13px; cursor: pointer; transition: 0.2s; white-space: nowrap;">
                                <span class="dynamic-lang" data-en="Apply" data-bn="প্রয়োগ">প্রয়োগ</span>
                            </button>
                        </div>
                        <div id="applied-coupon-badge" style="display: none; align-items: center; justify-content: space-between; margin-top: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 8px;">
                            <span style="font-size: 12.5px; font-weight: 700; color: #065f46; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-tags" style="color: #10b981;"></i>
                                <span id="applied-coupon-text">BAZAR5 (-৳0)</span>
                            </span>
                            <button type="button" onclick="removeAppliedCoupon()" style="background: none; border: none; color: #ef4444; font-size: 16px; font-weight: 800; cursor: pointer; padding: 0 4px; line-height: 1;" title="Remove coupon">&times;</button>
                        </div>
                    </div>

                    <div id="summary-special-discount-row" style="display: none; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
                        <span id="summary-special-discount-label" class="dynamic-lang" data-en="Combo / Special Discount" data-bn="কম্বো / স্পেশাল ছাড়" style="color: #ea580c; font-weight: 700;">Combo / Special Discount</span>
                        <span id="summary-special-discount-amount" style="font-weight: 800; color: #ea580c;">-৳0</span>
                    </div>

                    <div id="summary-coupon-row" style="display: none; justify-content: space-between; margin-bottom: 12px; font-size: 14px;">
                        <span class="dynamic-lang" data-en="Coupon Discount" data-bn="কুপন ছাড়" style="color: #059669; font-weight: 700;">Coupon Discount</span>
                        <span id="summary-coupon-discount" style="font-weight: 800; color: #059669;">-৳0</span>
                    </div>
                    
                    <!-- Payment Methods Section -->
                    <div style="margin: 20px 0 16px 0;">
                        <label style="font-weight: 800; font-size: 13.5px; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-wallet" style="color: var(--primary-color);"></i>
                            <span class="dynamic-lang" data-en="Select Payment Method" data-bn="পেমেন্ট মেথড নির্বাচন করুন">পেমেন্ট মেথড নির্বাচন করুন</span>
                        </label>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <!-- Cash on Delivery -->
                            <?php if ($paySettings['cod_enabled'] === '1'): ?>
                            <label class="pay-method-pill" id="pay-pill-cod" onclick="selectPaymentMethod('cod')" style="display: flex; align-items: center; justify-content: space-between; padding: 11px 13px; border: 2px solid #10b981; background: #f0fdf4; border-radius: 10px; cursor: pointer; transition: 0.2s;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" name="payment_method" value="cod" id="pay-opt-cod" checked style="accent-color: #10b981; width: 17px; height: 17px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13.5px; color: #0f172a;">
                                            💵 <span class="dynamic-lang" data-en="Cash on Delivery" data-bn="ক্যাশ অন ডেলিভারি">Cash on Delivery</span>
                                        </div>
                                        <div class="dynamic-lang" data-en="Pay with cash upon delivery" data-bn="পণ্য হাতে পেয়ে টাকা দিন" style="font-size: 11.5px; color: #64748b;">পণ্য হাতে পেয়ে টাকা দিন</div>
                                    </div>
                                </div>
                                <span class="dynamic-lang" data-en="Popular" data-bn="জনপ্রিয়" style="font-size: 11px; background: #dcfce7; color: #15803d; font-weight: 800; padding: 2px 8px; border-radius: 6px;">জনপ্রিয়</span>
                            </label>
                            <?php endif; ?>

                            <!-- bKash -->
                            <?php if ($paySettings['bkash_enabled'] === '1'): ?>
                            <label class="pay-method-pill" id="pay-pill-bkash" onclick="selectPaymentMethod('bkash')" style="display: flex; align-items: center; justify-content: space-between; padding: 11px 13px; border: 1.5px solid #cbd5e1; background: white; border-radius: 10px; cursor: pointer; transition: 0.2s;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" name="payment_method" value="bkash" id="pay-opt-bkash" style="accent-color: #e2136e; width: 17px; height: 17px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13.5px; color: #e2136e; display: flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                            <span class="dynamic-lang" data-en="bKash (Send Money)" data-bn="বিকাশ (bKash Send Money)">বিকাশ (bKash Send Money)</span>
                                        </div>
                                        <div class="dynamic-lang" data-en="Personal Account" data-bn="পার্সোনাল একাউন্ট" style="font-size: 11.5px; color: #64748b;">পার্সোনাল একাউন্ট</div>
                                    </div>
                                </div>
                                <span class="dynamic-lang" data-en="Instant" data-bn="ইনস্ট্যান্ট" style="font-size: 11px; background: #fce7f3; color: #be185d; font-weight: 800; padding: 2px 8px; border-radius: 6px;">ইনস্ট্যান্ট</span>
                            </label>
                            <?php endif; ?>

                            <!-- Nagad -->
                            <?php if ($paySettings['nagad_enabled'] === '1'): ?>
                            <label class="pay-method-pill" id="pay-pill-nagad" onclick="selectPaymentMethod('nagad')" style="display: flex; align-items: center; justify-content: space-between; padding: 11px 13px; border: 1.5px solid #cbd5e1; background: white; border-radius: 10px; cursor: pointer; transition: 0.2s;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" name="payment_method" value="nagad" id="pay-opt-nagad" style="accent-color: #ea580c; width: 17px; height: 17px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 13.5px; color: #ea580c; display: flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                            <span class="dynamic-lang" data-en="Nagad (Send Money)" data-bn="নগদ (Nagad Send Money)">নগদ (Nagad Send Money)</span>
                                        </div>
                                        <div class="dynamic-lang" data-en="Personal Account" data-bn="পার্সোনাল একাউন্ট" style="font-size: 11.5px; color: #64748b;">পার্সোনাল একাউন্ট</div>
                                    </div>
                                </div>
                                <span class="dynamic-lang" data-en="Easy" data-bn="সহজ" style="font-size: 11px; background: #ffedd5; color: #c2410c; font-weight: 800; padding: 2px 8px; border-radius: 6px;">সহজ</span>
                            </label>
                            <?php endif; ?>
                        </div>

                        <!-- Manual Mobile Banking Instructions & Input Box (Slide down) -->
                        <div id="mfs-payment-details-box" style="display: none; margin-top: 12px; background: #fdf2f8; border: 1.5px solid #fbcfe8; border-radius: 12px; padding: 14px; transition: all 0.3s ease;">
                            <div style="font-size: 12.5px; color: #831843; line-height: 1.4; margin-bottom: 12px;">
                                <strong id="mfs-instruction-title" style="display: block; font-size: 13px; margin-bottom: 4px;"></strong>
                                <span class="dynamic-lang" data-en="Send Money to the number below and enter the Transaction ID (TrxID) below:" data-bn="নিচের নম্বরে Send Money করে ট্রানজেকশন আইডি (TrxID) নিচে দিন:">নিচের নম্বরে Send Money করে ট্রানজেকশন আইডি (TrxID) নিচে দিন:</span>
                            </div>

                            <!-- Clickable Copy Phone Badge -->
                            <div style="display: flex; align-items: center; justify-content: space-between; background: white; border: 1.5px dashed #f472b6; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px;">
                                <div>
                                    <span id="mfs-account-type" style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">bKash Personal</span>
                                    <div id="mfs-display-number" style="font-family: monospace; font-size: 15px; font-weight: 900; color: #0f172a;"></div>
                                </div>
                                <button type="button" onclick="copyMfsNumber()" style="background: #e2136e; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <i class="fa-regular fa-copy"></i> <span id="btn-copy-mfs-text" class="dynamic-lang" data-en="Copy" data-bn="কপি করুন">Copy</span>
                                </button>
                            </div>

                            <!-- Sender Phone & TrxID Inputs -->
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <div>
                                    <label class="dynamic-lang" data-en="Sender Mobile Number (from which you sent money) *" data-bn="আপনার নম্বর (যেখান থেকে টাকা পাঠিয়েছেন) *" style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">
                                        আপনার নম্বর (যেখান থেকে টাকা পাঠিয়েছেন) *
                                    </label>
                                    <input type="tel" id="input-payment-sender-phone" placeholder="01XXXXXXXXX" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; box-sizing: border-box; outline: none;">
                                </div>
                                <div>
                                    <label class="dynamic-lang" data-en="Transaction ID (TrxID) *" data-bn="TrxID (ট্রানজেকশন আইডি) *" style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">
                                        TrxID (ট্রানজেকশন আইডি) *
                                    </label>
                                    <input type="text" id="input-payment-trx-id" placeholder="e.g. BL83K9XJ" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; font-family: monospace; text-transform: uppercase; font-weight: 700; box-sizing: border-box; outline: none;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px dashed #e2e8f0; margin: 16px 0;"></div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 24px; align-items: baseline;">
                        <span class="dynamic-lang" data-en="Total to Pay" data-bn="মোট পরিশোধ" style="color: #0f172a; font-weight: 800; font-size: 18px;">Total to Pay</span>
                        <span id="summary-total" style="font-weight: 900; color: var(--primary-color); font-size: 26px;">৳0</span>
                    </div>
                    
                    <button id="cart-submit-btn" onclick="placeOrder()" style="width: 100%; background: var(--primary-color); color: white; border: none; padding: 16px; border-radius: 10px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span id="cart-btn-label" class="dynamic-lang" data-en="Confirm & Place Order" data-bn="অর্ডার কনফার্ম করুন">Confirm & Place Order</span> 
                        <i id="cart-btn-icon" class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Order Success & Invoice Preview View -->
        <div id="preview-wrapper" style="display: none; flex-direction: column; gap: 6px; align-items: center; width: 100%;">
            <div class="no-print" style="background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 12px; padding: 18px 24px; width: 100%; max-width: 800px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <h3 id="order-success-title" class="dynamic-lang" data-en="Order Placed Successfully!" data-bn="আপনার অর্ডার সফলভাবে গৃহীত হয়েছে!" style="margin: 0; color: #065f46; font-size: 18px; font-weight: 800;">Order Placed Successfully!</h3>
                        <p id="placed-order-no-disp" style="margin: 2px 0 0; color: #047857; font-size: 13px; font-weight: 600;">Invoice No: INV-XXXX</p>
                    </div>
                </div>
            </div>

            <div id="pages-container" style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">
                <!-- Printable A4 Invoice page injected here -->
            </div>
            
            <!-- Actions -->
            <div id="preview-actions" class="no-print" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; width: 100%; max-width: 800px; box-sizing: border-box; background: white; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); flex-wrap: wrap;">
                <a href="shop.php" style="background: white; color: #1e293b; border: 1.5px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-store"></i> <span class="dynamic-lang" data-en="Continue Shopping" data-bn="আরও কেনাকাটা করুন">Continue Shopping</span>
                </a>
                <a href="profile.php?tab=invoice" style="background: linear-gradient(135deg, #00c675, #059669); color: white; border: none; padding: 9px 22px; border-radius: 8px; font-size: 14px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.35);">
                    <span class="dynamic-lang" data-en="View in My Invoices" data-bn="আমার ইনভয়েসে দেখুন">View in My Invoices</span> <i class="fa-solid fa-arrow-right"></i>
                </a>
                <button onclick="window.print()" style="background: #334155; color: white; border: none; padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-print"></i> <span class="dynamic-lang" data-en="Print Invoice" data-bn="প্রিন্ট করুন">Print Invoice</span>
                </button>
            </div>
        </div>
    </div>
</main>

<!-- ADD PRODUCT MODAL (Live Catalog Picker) -->
<div id="add-product-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 20px; max-width: 820px; width: 100%; max-height: 88vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.35); animation: modalFadeIn 0.2s ease-out;">
        
        <!-- Modal Header -->
        <div style="padding: 20px 26px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #fafafa;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-cart-plus"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;" class="dynamic-lang" data-en="Add Products to Order" data-bn="অর্ডারে নতুন পণ্য যোগ করুন">অর্ডারে নতুন পণ্য যোগ করুন</h3>
                    <span style="font-size: 13px; color: #64748b;" class="dynamic-lang" data-en="Search products from catalog to add directly to this order" data-bn="ক্যাটালগ থেকে যেকোনো পণ্য সার্চ করে এক ক্লিকে অর্ডারে যুক্ত করুন">ক্যাটালগ থেকে যেকোনো পণ্য সার্চ করে এক ক্লিকে অর্ডারে যুক্ত করুন</span>
                </div>
            </div>
            <button type="button" onclick="closeAddProductModal()" style="background: none; border: none; font-size: 26px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 4px;">&times;</button>
        </div>

        <!-- Search Bar & Categories Filter -->
        <div style="padding: 16px 26px 12px; border-bottom: 1px solid #f1f5f9; background: white; display: flex; flex-direction: column; gap: 12px;">
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text" id="modal-product-search" oninput="filterModalProducts()" placeholder="Search product" data-en-placeholder="Search product" data-bn-placeholder="পণ্য খুঁজুন" style="width: 100%; padding: 12px 16px 12px 42px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14.5px; outline: none; box-sizing: border-box; font-family: inherit; transition: 0.2s;" onfocus="this.style.borderColor='#10b981'">
            </div>
            <div id="modal-category-filters" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;">
                <!-- Category buttons injected here -->
            </div>
        </div>

        <!-- Products Grid List -->
        <div id="modal-products-list" style="padding: 20px 26px; overflow-y: auto; flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(215px, 1fr)); gap: 16px; background: #f8fafc;">
            <!-- Product cards injected by JS -->
        </div>

        <!-- Modal Footer -->
        <div style="padding: 16px 26px; border-top: 1px solid #e2e8f0; background: white; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <span style="font-size: 14px; color: #475569; font-weight: 600;">
                <i class="fa-solid fa-bag-shopping" style="color: #10b981; margin-right: 4px;"></i>
                <span id="modal-items-in-cart-summary">মোট পণ্য: 0 টি</span>
            </span>
            <button type="button" onclick="closeAddProductModal()" style="background: #0f172a; color: white; border: none; padding: 11px 24px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Done / Close" data-bn="সম্পন্ন / বন্ধ করুন">সম্পন্ন / বন্ধ করুন</span>
            </button>
        </div>
    </div>
</div>

<script>
    var isBangla = (localStorage.getItem('appLanguage') === 'bn') || (typeof window.isBangla !== 'undefined' && window.isBangla);
    var currentDeliveryCharge = 60;
    var catalogProducts = [];
    var catalogCategories = [];
    var storeDiscountRules = [];
    var selectedCatId = 'all';
    var appliedCoupon = null;
    var activeCartGifts = [];
    var activeCartSpecialDiscounts = [];

    // Check whether we are in order edit session
    var urlParams = new URLSearchParams(window.location.search);
    var isEditParam = urlParams.get('mode') === 'edit_invoice' || urlParams.get('mode') === 'edit_order';
    var hasEditSession = !!sessionStorage.getItem('editing_invoice_code');
    var isEditMode = isEditParam && hasEditSession;

    function getActiveCart() {
        let raw = [];
        if (isEditMode) {
            raw = JSON.parse(sessionStorage.getItem('editing_invoice_cart') || '[]');
        } else {
            raw = JSON.parse(localStorage.getItem('cart') || '[]');
        }
        if (!Array.isArray(raw)) return [];
        return raw.filter(item => {
            if (!item) return false;
            if (item.is_free_gift === true || item.is_free_gift === 1 || item.is_free_gift === '1') return false;
            const name = (item.name || item.title || '').toLowerCase();
            if (name.includes('🎁') || name.includes('free gift') || name.includes('ফ্রি উপহার') || name.includes('স্পেশাল গিফট') || name.includes('combo free') || name.includes('bogo free')) {
                return false;
            }
            if ((parseFloat(item.price) === 0 || item.price === '' || item.price === '0') && (name.includes('free') || name.includes('gift') || name.includes('উপহার') || name.includes('ফ্রি'))) {
                return false;
            }
            return true;
        });
    }

    function setActiveCart(items) {
        if (isEditMode) {
            sessionStorage.setItem('editing_invoice_cart', JSON.stringify(items));
        } else {
            localStorage.setItem('cart', JSON.stringify(items));
            if (typeof updateCartBadge === 'function') updateCartBadge();
            if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();
        }
    }

    function handleCartBack(e) {
        if (e) e.preventDefault();
        if (isEditMode) {
            sessionStorage.removeItem('editing_invoice_id');
            sessionStorage.removeItem('editing_invoice_code');
            sessionStorage.removeItem('editing_invoice_cart');
            if (document.referrer && (document.referrer.includes('profile.php') || document.referrer.includes('dashboard.php') || document.referrer.includes('auth_dashboard.php'))) {
                window.location.href = document.referrer;
            } else if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = 'profile.php?tab=invoice';
            }
        } else {
            window.location.href = 'shop.php';
        }
    }

    function cancelInvoiceEdit() {
        sessionStorage.removeItem('editing_invoice_id');
        sessionStorage.removeItem('editing_invoice_code');
        sessionStorage.removeItem('editing_invoice_cart');
        window.location.href = 'profile.php?tab=invoice';
    }

    function renderCart() {
        try {
            const isBangla = (localStorage.getItem('appLanguage') === 'bn') || (typeof window.isBangla !== 'undefined' && window.isBangla);
            const container = document.getElementById('cart-container');
            const summaryBox = document.getElementById('cart-summary');
            const addMoreBottomBtn = document.getElementById('add-more-card-btn');
            if (!container) return;
            const cart = getActiveCart();
            
            const editingCode = sessionStorage.getItem('editing_invoice_code');
            const editBanner = document.getElementById('edit-invoice-banner');
            const editCodeDisp = document.getElementById('editing-invoice-code-disp');
            const btnLabel = document.getElementById('cart-btn-label');
            const btnIcon = document.getElementById('cart-btn-icon');
            const pageTitle = document.getElementById('cart-page-title');
            const cartBackBtnText = document.getElementById('cart-back-btn-text');

            if (isEditMode && editBanner) {
                editBanner.style.display = 'flex';
                if (cartBackBtnText) {
                    cartBackBtnText.setAttribute('data-en', 'Go Back');
                    cartBackBtnText.setAttribute('data-bn', 'ফিরে যান');
                    cartBackBtnText.innerText = isBangla ? 'ফিরে যান' : 'Go Back';
                }
                if (editCodeDisp) editCodeDisp.innerText = editingCode;
                if (pageTitle) {
                    pageTitle.setAttribute('data-en', `Edit Order (${editingCode})`);
                    pageTitle.setAttribute('data-bn', `অর্ডার এডিট (${editingCode})`);
                    pageTitle.innerText = isBangla ? `অর্ডার এডিট (${editingCode})` : `Edit Order (${editingCode})`;
                }
                if (btnLabel) {
                    btnLabel.setAttribute('data-en', `Update Invoice (${editingCode})`);
                    btnLabel.setAttribute('data-bn', `ইনভয়েস আপডেট করুন (${editingCode})`);
                    btnLabel.innerText = isBangla ? `ইনভয়েস আপডেট করুন (${editingCode})` : `Update Invoice (${editingCode})`;
                }
                if (btnIcon) {
                    btnIcon.className = 'fa-solid fa-floppy-disk';
                }
            } else if (editBanner) {
                editBanner.style.display = 'none';
                if (cartBackBtnText) {
                    cartBackBtnText.setAttribute('data-en', 'Continue Shopping');
                    cartBackBtnText.setAttribute('data-bn', 'কেনাকাটা চালিয়ে যান');
                    cartBackBtnText.innerText = isBangla ? 'কেনাকাটা চালিয়ে যান' : 'Continue Shopping';
                }
                if (pageTitle) {
                    pageTitle.setAttribute('data-en', 'My Cart');
                    pageTitle.setAttribute('data-bn', 'আমার কার্ট');
                    pageTitle.innerText = isBangla ? 'আমার কার্ট' : 'My Cart';
                }
                if (btnLabel) {
                    btnLabel.setAttribute('data-en', 'Confirm & Place Order');
                    btnLabel.setAttribute('data-bn', 'অর্ডার কনফার্ম করুন');
                    btnLabel.innerText = isBangla ? 'অর্ডার কনফার্ম করুন' : 'Confirm & Place Order';
                }
                if (btnIcon) {
                    btnIcon.className = 'fa-solid fa-arrow-right';
                }
            }

            const couponInput = document.getElementById('cart-coupon-input');
            if (couponInput) {
                couponInput.placeholder = isBangla ? 'কুপন কোড' : 'COUPON CODE';
            }
            
            if (!cart || cart.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0;">
                        <i class="fa-solid fa-cart-shopping" style="font-size: 50px; color: #cbd5e1; margin-bottom: 16px;"></i>
                        <h2 class="dynamic-lang" data-en="Your cart is empty" data-bn="আপনার কার্ট বর্তমানে খালি রয়েছে" style="color: #0f172a; margin: 0 0 8px 0; font-size: 20px; font-weight: 800;">${isBangla ? 'আপনার কার্ট বর্তমানে খালি রয়েছে' : 'Your cart is empty'}</h2>
                        <p class="dynamic-lang" data-en="Add products to your order using the button below or catalog." data-bn="নিচের বাটন থেকে প্রয়োজনীয় পণ্য সার্চ করে কার্টে যুক্ত করুন।" style="color: #64748b; margin: 0 0 20px 0; font-size: 14px;">${isBangla ? 'নিচের বাটন থেকে প্রয়োজনীয় পণ্য সার্চ করে কার্টে যুক্ত করুন।' : 'Add products to your order using the button below or catalog.'}</p>
                        <button type="button" onclick="openAddProductModal()" style="background: var(--primary-color); color: white; border: none; padding: 12px 26px; border-radius: 8px; font-weight: 800; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                            <i class="fa-solid fa-circle-plus"></i> <span class="dynamic-lang" data-en="+ Add Products" data-bn="+ পণ্য যোগ করুন">${isBangla ? '+ পণ্য যোগ করুন' : '+ Add Products'}</span>
                        </button>
                    </div>
                `;
                if (summaryBox) summaryBox.style.display = 'none';
                if (addMoreBottomBtn) addMoreBottomBtn.style.display = 'none';
                return;
            }

            if (summaryBox) summaryBox.style.display = 'block';
            if (addMoreBottomBtn) addMoreBottomBtn.style.display = 'block';
            container.innerHTML = '';

            // Refresh catalog products and discount rules from cache / memory
            if (window.inMemCatalog && window.inMemCatalog.length > 0) {
                catalogProducts = window.inMemCatalog;
            } else {
                try {
                    const cached = sessionStorage.getItem('storeProductsCatalog');
                    if (cached) catalogProducts = JSON.parse(cached);
                } catch(e) {}
            }
            if (window.storeDiscountRules && window.storeDiscountRules.length > 0) {
                storeDiscountRules = window.storeDiscountRules;
            } else {
                try {
                    const cachedRules = sessionStorage.getItem('storeDiscountRules');
                    if (cachedRules) storeDiscountRules = JSON.parse(cachedRules);
                } catch(e) {}
            }

            let totalRegularPrice = 0;
            let totalBasePrice = 0;
            let cartModified = false;

            // Map cart items with their product IDs from catalog and fresh location pricing
            const cartWithMeta = cart.map(item => {
                const itemName = item.name || item.title || 'Product';
                const prodInfo = (typeof window.getProductInfo === 'function') ? window.getProductInfo(itemName) : (catalogProducts.find(p => (p.title && p.title.toLowerCase() === itemName.toLowerCase()) || (p.title_bn && p.title_bn === itemName) || (item.id && p.id == item.id)) || null);
                const prodId = prodInfo ? prodInfo.id : (item.id || null);
                const catId = prodInfo ? prodInfo.category_id : null;
                const qty = parseInt(item.quantity) || 1;
                
                // Get authoritative location price from catalog if available
                let itemPrice = (prodInfo && typeof prodInfo.price !== 'undefined') ? parseFloat(prodInfo.price) : (parseFloat(item.price) || 0);
                let itemOldPrice = (prodInfo && typeof prodInfo.old_price !== 'undefined' && parseFloat(prodInfo.old_price) > 0) ? parseFloat(prodInfo.old_price) : (item.oldPrice ? parseFloat(item.oldPrice) : itemPrice);

                const isAvail = prodInfo ? ((prodInfo.is_available !== false && prodInfo.is_available_in_location !== false) && (prodInfo.stock > 0 || prodInfo.in_stock == 1)) : !(item.is_out_of_stock === true || item.is_unavailable === true);

                if (Math.abs(itemPrice - (parseFloat(item.price) || 0)) >= 0.01) {
                    item.price = itemPrice;
                    cartModified = true;
                }
                if (Math.abs(itemOldPrice - (parseFloat(item.oldPrice) || 0)) >= 0.01) {
                    item.oldPrice = itemOldPrice;
                    cartModified = true;
                }
                if (item.is_out_of_stock !== !isAvail) {
                    item.is_out_of_stock = !isAvail;
                    item.is_unavailable = !isAvail;
                    cartModified = true;
                }

                totalRegularPrice += (itemOldPrice * qty);
                totalBasePrice += (itemPrice * qty);

                return {
                    ...item,
                    prodId: prodId,
                    catId: catId,
                    prodInfo: prodInfo,
                    qty: qty,
                    itemPrice: itemPrice,
                    itemOldPrice: itemOldPrice,
                    is_available: isAvail,
                    is_out_of_stock: !isAvail
                };
            });

            if (cartModified && !isEditMode) {
                localStorage.setItem('cart', JSON.stringify(cart));
            }

            // --- EVALUATE UNIVERSAL DYNAMIC DISCOUNT RULES ---
            activeCartGifts = [];
            activeCartSpecialDiscounts = [];
            let freeDeliveryUnlocked = false;

            const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';

            if (storeDiscountRules && storeDiscountRules.length > 0) {
                storeDiscountRules.forEach(rule => {
                    if (!rule.is_active || rule.is_active == 0) return;

                    // Check audience targeting
                    if (rule.customer_target === 'registered' && !isLoggedIn) return;

                    const now = new Date();
                    if (rule.start_datetime && new Date(rule.start_datetime) > now) return;
                    if (rule.end_datetime && new Date(rule.end_datetime) < now) return;

                    const minCart = parseFloat(rule.min_cart_amount) || 0;

                    // 1. COMBO BUNDLE RULES (Buy Product A + Product B -> Get Free Gift or Discount)
                    if (rule.discount_type === 'combo_bundle' || rule.target_type === 'multi_products') {
                        let requiredIds = [];
                        if (rule.target_ids_json) {
                            try {
                                requiredIds = typeof rule.target_ids_json === 'string' ? JSON.parse(rule.target_ids_json) : rule.target_ids_json;
                            } catch(e) {
                                requiredIds = [];
                            }
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
                                
                                const matchItem = cartWithMeta.find(c => (c.prodId == reqId || (c.prodInfo && c.prodInfo.id == reqId)));
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
                                let totalFreeGiftQty = bundleMultiplier * (parseInt(rule.reward_qty) || 1);
                                if (rule.max_reward_limit && parseInt(rule.max_reward_limit) > 0) {
                                    totalFreeGiftQty = Math.min(totalFreeGiftQty, parseInt(rule.max_reward_limit));
                                }

                                const effectiveMultiplier = (rule.max_reward_limit && parseInt(rule.max_reward_limit) > 0) 
                                    ? Math.min(bundleMultiplier, parseInt(rule.max_reward_limit)) 
                                    : bundleMultiplier;

                                // Combo Condition Satisfied!
                                if (rule.reward_type === 'free_gift' || rule.reward_product_id) {
                                    const rewardProdInfo = catalogProducts.find(p => p.id == rule.reward_product_id);
                                    const rewardItemPrice = rewardProdInfo ? (parseFloat(rewardProdInfo.price) || 0) : parseFloat(rule.reward_product_price || 0);
                                    const rewardItemImg = (rewardProdInfo && rewardProdInfo.image) ? rewardProdInfo.image : (rule.reward_product_image || 'assets/bazarkinundotcom_logo.png');

                                    activeCartGifts.push({
                                        rule_id: rule.id,
                                        rule_title: rule.title,
                                        rule_title_bn: rule.title_bn,
                                        badge_text: rule.badge_text || (isBangla ? 'কম্বো স্পেশাল ফ্রি গিফট' : 'Combo Free Gift'),
                                        id: rule.reward_product_id,
                                        name: rule.reward_product_title || 'Free Gift',
                                        name_bn: rule.reward_product_title_bn || rule.reward_product_title || 'ফ্রি উপহার',
                                        poriman: rule.reward_product_poriman || '',
                                        poriman_bn: rule.reward_product_poriman_bn || '',
                                        image: rewardItemImg,
                                        price: 0,
                                        oldPrice: rewardItemPrice,
                                        quantity: totalFreeGiftQty,
                                        bundle_count: bundleMultiplier,
                                        is_free_gift: true,
                                        is_combo: true
                                    });
                                } else if (rule.reward_type === 'free_delivery') {
                                    freeDeliveryUnlocked = true;
                                } else if (rule.reward_type === 'percentage') {
                                    const discVal = Math.round((totalBasePrice * (parseFloat(rule.reward_value || rule.discount_value) || 0)) / 100);
                                    if (discVal > 0) {
                                        activeCartSpecialDiscounts.push({
                                            label: rule.title || 'Combo Discount',
                                            label_bn: rule.title_bn || rule.title || 'কম্বো অফার ছাড়',
                                            badge: rule.badge_text,
                                            amount: discVal
                                        });
                                    }
                                } else if (rule.reward_type === 'fixed_amount' || rule.discount_type === 'combo_bundle') {
                                    const discVal = (parseFloat(rule.reward_value || rule.discount_value) || 0) * effectiveMultiplier;
                                    if (discVal > 0) {
                                        activeCartSpecialDiscounts.push({
                                            label: rule.title || 'Combo Discount',
                                            label_bn: rule.title_bn || rule.title || 'কম্বো অফার ছাড়',
                                            badge: rule.badge_text,
                                            amount: discVal
                                        });
                                    }
                                }
                            }
                        }
                    }

                    // 2. SPEND TIER FREE GIFT (Spend >= min_cart_amount -> Free Gift)
                    else if (rule.discount_type === 'free_gift') {
                        if (totalBasePrice >= minCart && rule.reward_product_id) {
                            const rewardProdInfo = catalogProducts.find(p => p.id == rule.reward_product_id);
                            const rewardItemPrice = rewardProdInfo ? (parseFloat(rewardProdInfo.price) || 0) : parseFloat(rule.reward_product_price || 0);
                            const rewardItemImg = (rewardProdInfo && rewardProdInfo.image) ? rewardProdInfo.image : (rule.reward_product_image || 'assets/bazarkinundotcom_logo.png');

                            activeCartGifts.push({
                                rule_id: rule.id,
                                rule_title: rule.title,
                                rule_title_bn: rule.title_bn,
                                badge_text: rule.badge_text || (isBangla ? 'স্পেশাল ফ্রি গিফট' : 'Special Free Gift'),
                                id: rule.reward_product_id,
                                name: rule.reward_product_title || 'Free Gift',
                                name_bn: rule.reward_product_title_bn || rule.reward_product_title || 'ফ্রি উপহার',
                                poriman: rule.reward_product_poriman || '',
                                poriman_bn: rule.reward_product_poriman_bn || '',
                                image: rewardItemImg,
                                price: 0,
                                oldPrice: rewardItemPrice,
                                quantity: parseInt(rule.reward_qty || 1),
                                is_free_gift: true,
                                is_combo: false
                            });
                        }
                    }

                    // 3. CART SPEND TIER DISCOUNT (Spend >= min_cart_amount -> Flat or % Discount)
                    else if (rule.discount_type === 'cart_spend') {
                        if (totalBasePrice >= minCart) {
                            let discVal = 0;
                            if (parseFloat(rule.discount_value) > 0) {
                                discVal = parseFloat(rule.discount_value);
                            }
                            if (discVal > 0) {
                                activeCartSpecialDiscounts.push({
                                    label: rule.title || 'Cart Spend Offer',
                                    label_bn: rule.title_bn || rule.title || 'স্পেশাল কার্ট ছাড়',
                                    badge: rule.badge_text,
                                    amount: discVal
                                });
                            }
                        }
                    }

                    // 4. FREE DELIVERY RULE (Spend >= min_cart_amount -> Free Delivery)
                    else if (rule.discount_type === 'free_delivery') {
                        if (totalBasePrice >= minCart) {
                            freeDeliveryUnlocked = true;
                        }
                    }
                });
            }

            // --- RENDER REGULAR CART ITEMS ---
            cartWithMeta.forEach((item) => {
                let itemOldPrice = item.itemOldPrice;
                let itemPrice = item.itemPrice;
                let qty = item.qty;
                
                const itemName = item.name || item.title || 'Product';
                const prodInfo = item.prodInfo;
                let imgSrc = item.img || item.image;
                if (!imgSrc || imgSrc.includes('placehold.co') || imgSrc.includes('placehold')) {
                    imgSrc = (prodInfo && prodInfo.image) ? prodInfo.image : 'assets/bazarkinundotcom_logo.png';
                }

                let safeName = itemName.replace(/'/g, "\\'");

                let displayName = itemName;
                if (isBangla) {
                    displayName = item.name_bn || item.title_bn || (prodInfo ? prodInfo.title_bn : null) || itemName;
                }
                let displayPoriman = item.poriman || (prodInfo ? prodInfo.poriman : '');
                if (isBangla) {
                    displayPoriman = item.poriman_bn || (prodInfo ? prodInfo.poriman_bn : null) || displayPoriman;
                }

                const isUnavailable = (item.is_out_of_stock === true || item.is_unavailable === true);

                const row = document.createElement('div');
                row.style = `display: flex; align-items: center; background: white; padding: 18px 24px; border-radius: 14px; margin-bottom: 14px; border: 1.5px solid ${isUnavailable ? '#fca5a5' : '#e2e8f0'}; justify-content: space-between; flex-wrap: wrap; gap: 15px; position: relative;`;
                row.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 200px;">
                        <img src="${imgSrc}" alt="${displayName}" style="width: 70px; height: 70px; object-fit: contain; background: #f8fafc; border-radius: 8px; border: 1px solid #f1f5f9; padding: 4px;" onerror="this.src='assets/bazarkinundotcom_logo.png';">
                        <div>
                            <h4 style="margin: 0 0 2px 0; color: #0f172a; font-size: 16px; font-weight: 800;">${displayName}</h4>
                            ${displayPoriman ? `<div style="font-size: 12.5px; color: #64748b; font-weight: 600; margin-bottom: 4px;">${displayPoriman}</div>` : ''}
                            <div style="display: flex; gap: 8px; align-items: baseline;">
                                <span style="color: ${isUnavailable ? '#94a3b8' : 'var(--primary-color)'}; font-weight: 900; font-size: 17px;">৳${itemPrice}</span>
                                ${itemOldPrice > itemPrice ? `<span style="color: #94a3b8; text-decoration: line-through; font-size: 12px;">৳${itemOldPrice}</span>` : ''}
                            </div>
                            ${isUnavailable ? `
                                <div style="font-size: 11.5px; color: #dc2626; font-weight: 700; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> <span>${isBangla ? 'এই ডেলিভারি এলাকায় বর্তমানে অনুপলব্ধ' : 'Unavailable in this delivery area'}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="display: flex; align-items: center; border: 1.5px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                            <button onclick="updateCartQuantity('${safeName}', -1)" style="width: 34px; height: 34px; background: #f8fafc; border: none; cursor: pointer; color: #1e293b; font-weight: 800; font-size: 16px;">-</button>
                            <span style="width: 36px; text-align: center; font-weight: 800; color: #0f172a; font-size: 14px;">${qty}</span>
                            <button onclick="updateCartQuantity('${safeName}', 1)" style="width: 34px; height: 34px; background: #f8fafc; border: none; cursor: pointer; color: #1e293b; font-weight: 800; font-size: 16px;">+</button>
                        </div>
                        <span style="font-weight: 900; color: ${isUnavailable ? '#94a3b8' : '#0f172a'}; font-size: 16px; min-width: 70px; text-align: right;">৳${itemPrice * qty}</span>
                        <button onclick="removeFromCart('${safeName}')" style="background: transparent; color: #ef4444; border: none; font-size: 18px; cursor: pointer; padding: 6px;" title="${isBangla ? 'মুছে ফেলুন' : 'Remove'}"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                `;
                container.appendChild(row);
            });

            // --- RENDER UNLOCKED FREE GIFT ITEMS ---
            if (activeCartGifts && activeCartGifts.length > 0) {
                activeCartGifts.forEach(gift => {
                    const gTitle = isBangla ? (gift.name_bn || gift.name) : gift.name;
                    const gPoriman = isBangla ? (gift.poriman_bn || gift.poriman) : gift.poriman;
                    const gBadge = gift.badge_text || (isBangla ? '🎁 কম্বো স্পেশাল গিফট' : '🎁 Combo Free Gift');

                    const giftCard = document.createElement('div');
                    giftCard.style = "display: flex; align-items: center; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); padding: 18px 24px; border-radius: 14px; margin-bottom: 14px; border: 2px solid #86efac; justify-content: space-between; flex-wrap: wrap; gap: 15px; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.12); position: relative; overflow: hidden;";
                    
                    giftCard.innerHTML = `
                        <div style="position: absolute; top: 0; right: 0; background: linear-gradient(135deg, #10b981, #059669); color: white; font-size: 11px; font-weight: 800; padding: 4px 14px; border-bottom-left-radius: 10px; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                            <i class="fa-solid fa-gift"></i> <span>${gBadge}</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 200px;">
                            <div style="position: relative;">
                                <img src="${gift.image}" alt="${gTitle}" style="width: 70px; height: 70px; object-fit: contain; background: white; border-radius: 8px; border: 1.5px solid #bbf7d0; padding: 4px;" onerror="this.src='assets/bazarkinundotcom_logo.png';">
                                <span style="position: absolute; bottom: -4px; right: -4px; background: #059669; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900; border: 2px solid white;">🎁</span>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                    <span style="background: #dcfce7; color: #047857; font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 4px; border: 1px solid #a7f3d0;">
                                        ${isBangla ? 'অটো যুক্ত হয়েছে' : 'AUTO ADDED'}
                                    </span>
                                </div>
                                <h4 style="margin: 0 0 2px 0; color: #065f46; font-size: 16px; font-weight: 800;">${gTitle}</h4>
                                ${gPoriman ? `<div style="font-size: 12.5px; color: #047857; font-weight: 600; margin-bottom: 4px;">${gPoriman}</div>` : ''}
                                <div style="display: flex; gap: 8px; align-items: baseline;">
                                    <span style="color: #059669; font-weight: 900; font-size: 18px;">৳0</span>
                                    ${gift.oldPrice > 0 ? `<span style="color: #94a3b8; text-decoration: line-through; font-size: 12px;">৳${gift.oldPrice}</span>` : ''}
                                    <span style="font-size: 11px; color: #059669; font-weight: 700; background: white; padding: 1px 6px; border-radius: 4px; border: 1px solid #86efac;">100% FREE</span>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="background: white; border: 1.5px solid #86efac; border-radius: 8px; padding: 6px 14px; font-weight: 800; color: #047857; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-check" style="color: #10b981;"></i> <span>${gift.quantity} ${isBangla ? 'টি উপহার' : 'Gift'}</span>
                            </div>
                            <span style="font-weight: 900; color: #059669; font-size: 17px; min-width: 70px; text-align: right;">৳0</span>
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #dcfce7; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 13px;" title="${isBangla ? 'অফার শর্ত পূরণ হওয়ায় ফ্রি উপহার অন্তর্ভুক্ত' : 'Unlocked as an active offer reward'}">
                                <i class="fa-solid fa-lock-open"></i>
                            </div>
                        </div>
                    `;
                    container.appendChild(giftCard);
                });
            }

            // --- CALCULATE SUMMARY DISCOUNTS ---
            let specialDiscountTotal = 0;
            if (activeCartSpecialDiscounts && activeCartSpecialDiscounts.length > 0) {
                activeCartSpecialDiscounts.forEach(d => {
                    specialDiscountTotal += (parseFloat(d.amount) || 0);
                });
            }

            // Update Special / Combo discount row in summary
            const specialRow = document.getElementById('summary-special-discount-row');
            const specialLabel = document.getElementById('summary-special-discount-label');
            const specialAmountElem = document.getElementById('summary-special-discount-amount');

            if (specialDiscountTotal > 0) {
                if (specialRow) specialRow.style.display = 'flex';
                if (specialLabel) {
                    const firstTitle = isBangla ? (activeCartSpecialDiscounts[0].label_bn || activeCartSpecialDiscounts[0].label) : activeCartSpecialDiscounts[0].label;
                    specialLabel.innerText = firstTitle ? `🔥 ${firstTitle}` : (isBangla ? '🔥 কম্বো ও স্পেশাল ছাড়' : '🔥 Combo & Special Deals');
                }
                if (specialAmountElem) specialAmountElem.innerText = '-৳' + specialDiscountTotal;
            } else {
                if (specialRow) specialRow.style.display = 'none';
            }

            const netCartTotal = Math.max(0, totalBasePrice - specialDiscountTotal);

            // Compute delivery charge dynamically
            currentDeliveryCharge = (freeDeliveryUnlocked || netCartTotal >= 1000) ? 0 : 60;
            const delElem = document.getElementById('summary-delivery-charge');
            if (delElem) {
                delElem.innerText = currentDeliveryCharge === 0 ? (isBangla ? 'ফ্রি (FREE)' : 'FREE') : '৳' + currentDeliveryCharge;
                delElem.style.color = currentDeliveryCharge === 0 ? '#10b981' : '#1e293b';
            }

            // Handle applied coupon discount
            let couponDiscount = 0;
            if (appliedCoupon) {
                if (netCartTotal < appliedCoupon.min_cart_amount) {
                    showToast(isBangla ? `সর্বনিম্ন ৳${appliedCoupon.min_cart_amount} এর অর্ডারে এই কুপন প্রযোজ্য!` : `Minimum ৳${appliedCoupon.min_cart_amount} required for this coupon`, 'error');
                    appliedCoupon = null;
                } else {
                    const isPct = (appliedCoupon.discount_type === 'percentage') || 
                                  (appliedCoupon.rule && (appliedCoupon.rule.reward_type === 'discount_percent' || appliedCoupon.rule.discount_type === 'percentage'));
                    if (isPct && appliedCoupon.rule && appliedCoupon.rule.discount_value) {
                        couponDiscount = Math.round((netCartTotal * parseFloat(appliedCoupon.rule.discount_value)) / 100);
                    } else {
                        couponDiscount = appliedCoupon.discount_amount || 0;
                    }
                    couponDiscount = Math.min(couponDiscount, netCartTotal);
                }
            }

            const couponBadge = document.getElementById('applied-coupon-badge');
            const couponText = document.getElementById('applied-coupon-text');
            const couponRow = document.getElementById('summary-coupon-row');
            const couponDiscountElem = document.getElementById('summary-coupon-discount');

            if (appliedCoupon && couponDiscount > 0) {
                if (couponBadge) couponBadge.style.display = 'flex';
                if (couponText) couponText.innerText = `${appliedCoupon.code} (-৳${couponDiscount})`;
                if (couponRow) couponRow.style.display = 'flex';
                if (couponDiscountElem) couponDiscountElem.innerText = '-৳' + couponDiscount;
            } else {
                if (couponBadge) couponBadge.style.display = 'none';
                if (couponRow) couponRow.style.display = 'none';
            }

            const totalSavings = (totalRegularPrice - totalBasePrice) + specialDiscountTotal + couponDiscount;
            const finalPayable = Math.max(0, netCartTotal - couponDiscount) + currentDeliveryCharge;

            const regPriceElem = document.getElementById('summary-regular-price');
            const discElem = document.getElementById('summary-discount');
            const subtotalElem = document.getElementById('summary-subtotal');
            const totalElem = document.getElementById('summary-total');

            if (regPriceElem) regPriceElem.innerText = '৳' + totalRegularPrice;
            if (discElem) discElem.innerText = '-৳' + (totalRegularPrice - totalBasePrice);
            if (subtotalElem) subtotalElem.innerText = '৳' + totalBasePrice;
            if (totalElem) totalElem.innerText = '৳' + finalPayable;

            if (typeof updateCartBadge === 'function') updateCartBadge();
        } catch (err) {
            console.error("Error in renderCart:", err);
        }
    }

    async function applyCartCoupon() {
        const input = document.getElementById('cart-coupon-input');
        const code = (input ? input.value : '').trim().toUpperCase();
        if (!code) {
            showToast(isBangla ? "অনুগ্রহ করে একটি কুপন কোড লিখুন" : "Please enter a coupon code", "error");
            return;
        }

        const cart = getActiveCart();
        let totalPrice = 0;
        cart.forEach(i => totalPrice += (parseFloat(i.price || 0) * parseInt(i.quantity || 1)));

        if (totalPrice <= 0) {
            showToast(isBangla ? "আপনার কার্ট খালি" : "Cart is empty", "error");
            return;
        }

        const btn = document.getElementById('cart-coupon-apply-btn');
        if (btn) { btn.disabled = true; btn.innerText = isBangla ? 'যাচাই...' : 'Checking...'; }

        try {
            const res = await fetch(`api/discount-manager.php?action=validate_coupon&code=${encodeURIComponent(code)}&cart_total=${totalPrice}`);
            const data = await res.json();

            if (data.status === 'success') {
                appliedCoupon = {
                    code: data.coupon_code,
                    discount_amount: parseFloat(data.discount_amount) || 0,
                    discount_type: data.discount_type,
                    min_cart_amount: parseFloat(data.min_cart_amount) || 0,
                    rule: data.rule
                };
                showToast(isBangla ? `কুপন '${code}' সফলভাবে যুক্ত হয়েছে! ৳${appliedCoupon.discount_amount} ছাড় পেয়েছেন!` : `Coupon '${code}' applied! You saved ৳${appliedCoupon.discount_amount}!`, 'success');
                renderCart();
            } else {
                showToast(data.message || (isBangla ? "অকার্যকর কুপন কোড" : "Invalid coupon code"), "error");
            }
        } catch(err) {
            console.error("Coupon error:", err);
            showToast(isBangla ? "কুপন যাচাইয়ে সমস্যা হয়েছে" : "Failed to validate coupon", "error");
        } finally {
            if (btn) { btn.disabled = false; btn.innerText = isBangla ? 'প্রয়োগ' : 'Apply'; }
        }
    }

    function removeAppliedCoupon() {
        appliedCoupon = null;
        const input = document.getElementById('cart-coupon-input');
        if (input) input.value = '';
        renderCart();
        showToast(isBangla ? "কুপন বাতিল করা হয়েছে" : "Coupon removed");
    }

    function updateCartQuantity(name, delta) {
        let cart = getActiveCart();
        let item = cart.find(i => i.name === name);
        if (item) {
            item.quantity = (parseInt(item.quantity) || 1) + delta;
            if (item.quantity <= 0) {
                cart = cart.filter(i => i.name !== name);
            }
            setActiveCart(cart);
            renderCart();
        }
    }

    function removeFromCart(name) {
        let cart = getActiveCart();
        cart = cart.filter(i => i.name !== name);
        setActiveCart(cart);
        renderCart();
        showToast(isBangla ? "পণ্যটি সরানো হয়েছে" : "Item removed from cart");
    }

    // --- ADD PRODUCT MODAL FUNCTIONS ---
    async function openAddProductModal() {
        const modal = document.getElementById('add-product-modal');
        if (!modal) return;
        modal.style.display = 'flex';
        const searchInput = document.getElementById('modal-product-search');
        if (searchInput) {
            searchInput.placeholder = isBangla ? 'পণ্য খুঁজুন' : 'Search product';
            searchInput.value = '';
        }

        if (catalogProducts.length === 0) {
            try {
                const res = await fetch('api/store-data.php');
                const data = await res.json();
                if (data.status === 'success') {
                    catalogProducts = data.products || [];
                    catalogCategories = data.categories || [];
                    storeDiscountRules = data.discount_rules || [];
                    sessionStorage.setItem('storeProductsCatalog', JSON.stringify(catalogProducts));
                    sessionStorage.setItem('storeDiscountRules', JSON.stringify(storeDiscountRules));
                }
            } catch (e) {
                console.error("Error fetching catalog:", e);
            }
        }

        renderModalCategories();
        filterModalProducts();
    }

    function closeAddProductModal() {
        const modal = document.getElementById('add-product-modal');
        if (modal) modal.style.display = 'none';
        renderCart();
    }

    function renderModalCategories() {
        const container = document.getElementById('modal-category-filters');
        if (!container) return;

        let html = `
            <button type="button" onclick="selectModalCategory('all')" style="background: ${selectedCatId === 'all' ? '#10b981' : '#f1f5f9'}; color: ${selectedCatId === 'all' ? 'white' : '#334155'}; border: none; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; cursor: pointer; white-space: nowrap; transition: 0.2s;">
                ${isBangla ? 'সকল পণ্য' : 'All Products'}
            </button>
        `;

        catalogCategories.forEach(c => {
            const isSel = selectedCatId == c.id;
            const catTitle = isBangla ? (c.name_bn || c.name) : c.name;
            html += `
                <button type="button" onclick="selectModalCategory(${c.id})" style="background: ${isSel ? '#10b981' : '#f1f5f9'}; color: ${isSel ? 'white' : '#334155'}; border: none; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; cursor: pointer; white-space: nowrap; transition: 0.2s;">
                    ${catTitle}
                </button>
            `;
        });

        container.innerHTML = html;
    }

    function selectModalCategory(catId) {
        selectedCatId = catId;
        renderModalCategories();
        filterModalProducts();
    }

    function filterModalProducts() {
        const query = (document.getElementById('modal-product-search').value || '').toLowerCase().trim();
        const container = document.getElementById('modal-products-list');
        const cart = getActiveCart();

        let filtered = catalogProducts.filter(p => {
            if (selectedCatId !== 'all' && p.category_id != selectedCatId) {
                return false;
            }
            if (query) {
                const titleEn = (p.title || '').toLowerCase();
                const titleBn = (p.title_bn || '').toLowerCase();
                return titleEn.includes(query) || titleBn.includes(query);
            }
            return true;
        });

        const summaryElem = document.getElementById('modal-items-in-cart-summary');
        if (summaryElem) {
            summaryElem.innerText = isBangla ? `মোট পণ্য: ${cart.length} টি` : `Total items in order: ${cart.length}`;
        }

        if (filtered.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b;">
                    <i class="fa-solid fa-box-open" style="font-size: 38px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    <p style="margin: 0; font-size: 14.5px; font-weight: 600;">${isBangla ? 'কোনো পণ্য পাওয়া যায়নি।' : 'No products found.'}</p>
                </div>
            `;
            return;
        }

        let html = '';
        filtered.forEach(p => {
            const title = isBangla ? (p.title_bn || p.title) : p.title;
            const poriman = isBangla ? (p.poriman_bn || p.poriman || '') : (p.poriman || '');
            const price = parseFloat(p.price) || 0;
            const oldPrice = parseFloat(p.old_price) || 0;
            const img = p.image || 'assets/bazarkinundotcom_logo.png';
            const inCart = cart.find(i => i.name === p.title);
            const inCartQty = inCart ? inCart.quantity : 0;

            html += `
                <div style="background: white; border: 1.5px solid ${inCart ? '#a7f3d0' : '#e2e8f0'}; border-radius: 14px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 10px; overflow: hidden; background: #f8fafc; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                            <img src="${img}" alt="${title}" style="max-width: 90%; max-height: 90px; object-fit: contain;" onerror="this.src='assets/bazarkinundotcom_logo.png';">
                        </div>
                        <h4 style="margin: 0 0 3px 0; font-size: 14.5px; font-weight: 800; color: #0f172a; line-height: 1.3;">${title}</h4>
                        ${poriman ? `<span style="font-size: 12px; color: #64748b; font-weight: 600; display: block; margin-bottom: 6px;">${poriman}</span>` : ''}
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px; padding-top: 8px; border-top: 1px solid #f8fafc;">
                        <div>
                            <span style="font-size: 16px; font-weight: 900; color: var(--primary-color);">৳${price}</span>
                            ${oldPrice > price ? `<span style="font-size: 11.5px; color: #94a3b8; text-decoration: line-through; margin-left: 4px;">৳${oldPrice}</span>` : ''}
                        </div>
                        <button type="button" onclick="addProductFromModal(${p.id})" style="background: ${inCart ? '#10b981' : '#f0fdf4'}; color: ${inCart ? 'white' : '#059669'}; border: 1.5px solid #10b981; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s;">
                            <i class="fa-solid ${inCart ? 'fa-check' : 'fa-plus'}"></i> 
                            <span>${inCart ? (isBangla ? `যুক্ত (${inCartQty})` : `Added (${inCartQty})`) : (isBangla ? 'যোগ করুন' : 'Add')}</span>
                        </button>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function addProductFromModal(prodId) {
        const prod = catalogProducts.find(p => p.id == prodId);
        if (!prod) return;

        let cart = getActiveCart();
        let existing = cart.find(i => i.name === prod.title);

        if (existing) {
            existing.quantity = (parseInt(existing.quantity) || 1) + 1;
        } else {
            cart.push({
                name: prod.title,
                name_bn: prod.title_bn || '',
                price: parseFloat(prod.price) || 0,
                oldPrice: parseFloat(prod.old_price) || 0,
                quantity: 1,
                img: prod.image || 'assets/bazarkinundotcom_logo.png',
                image: prod.image || 'assets/bazarkinundotcom_logo.png',
                poriman: prod.poriman || '',
                poriman_bn: prod.poriman_bn || '',
                category_name: prod.category_name || '',
                category_name_bn: prod.category_name_bn || ''
            });
        }

        setActiveCart(cart);
        filterModalProducts();
        renderCart();
        showToast(isBangla ? `"${prod.title_bn || prod.title}" যোগ করা হয়েছে!` : `"${prod.title}" added to order!`, 'success');
    }

    const MFS_CONFIG = {
        bkash: {
            title_bn: 'বিকাশ পার্সোনাল পেমেন্ট নির্দেশিকা:',
            title_en: 'bKash Personal Payment Guide:',
            type: '<?= htmlspecialchars($paySettings['bkash_type']) ?>',
            number: '<?= htmlspecialchars($paySettings['bkash_number']) ?>',
            themeColor: '#e2136e',
            bgColor: '#fdf2f8',
            borderColor: '#fbcfe8',
            btnColor: '#e2136e'
        },
        nagad: {
            title_bn: 'নগদ পার্সোনাল পেমেন্ট নির্দেশিকা:',
            title_en: 'Nagad Personal Payment Guide:',
            type: '<?= htmlspecialchars($paySettings['nagad_type']) ?>',
            number: '<?= htmlspecialchars($paySettings['nagad_number']) ?>',
            themeColor: '#ea580c',
            bgColor: '#fff7ed',
            borderColor: '#ffedd5',
            btnColor: '#ea580c'
        }
    };

    let currentPaymentMethod = 'cod';

    function selectPaymentMethod(method) {
        currentPaymentMethod = method;
        ['cod', 'bkash', 'nagad'].forEach(m => {
            const pill = document.getElementById('pay-pill-' + m);
            const radio = document.getElementById('pay-opt-' + m);
            if (pill) {
                if (m === method) {
                    pill.style.borderColor = (m === 'bkash') ? '#e2136e' : (m === 'nagad' ? '#ea580c' : '#10b981');
                    pill.style.background = (m === 'bkash') ? '#fdf2f8' : (m === 'nagad' ? '#fff7ed' : '#f0fdf4');
                    pill.style.borderWidth = '2px';
                    if (radio) radio.checked = true;
                } else {
                    pill.style.borderColor = '#cbd5e1';
                    pill.style.background = 'white';
                    pill.style.borderWidth = '1.5px';
                    if (radio) radio.checked = false;
                }
            }
        });

        const detailsBox = document.getElementById('mfs-payment-details-box');
        if (!detailsBox) return;

        if (method === 'bkash' || method === 'nagad') {
            const isBn = (typeof window.isBangla !== 'undefined') ? window.isBangla : (localStorage.getItem('appLanguage') === 'bn');
            const cfg = MFS_CONFIG[method];
            detailsBox.style.display = 'block';
            detailsBox.style.background = cfg.bgColor;
            detailsBox.style.borderColor = cfg.borderColor;
            const titleEl = document.getElementById('mfs-instruction-title');
            if (titleEl) {
                titleEl.innerText = isBn ? (cfg.title_bn || cfg.title_en) : (cfg.title_en || cfg.title_bn);
            }
            document.getElementById('mfs-account-type').innerText = method.toUpperCase() + ' ' + cfg.type;
            document.getElementById('mfs-display-number').innerText = cfg.number;
            const copyBtn = detailsBox.querySelector('button');
            if (copyBtn) copyBtn.style.background = cfg.btnColor;
        } else {
            detailsBox.style.display = 'none';
        }
    }

    function copyMfsNumber() {
        const num = document.getElementById('mfs-display-number')?.innerText;
        const isBn = (typeof window.isBangla !== 'undefined') ? window.isBangla : (localStorage.getItem('appLanguage') === 'bn');
        if (num && navigator.clipboard) {
            navigator.clipboard.writeText(num).then(() => {
                const btnText = document.getElementById('btn-copy-mfs-text');
                if (btnText) {
                    btnText.innerText = isBn ? 'কপি হয়েছে!' : 'Copied!';
                    setTimeout(() => { btnText.innerText = isBn ? 'কপি করুন' : 'Copy'; }, 2000);
                }
                showToast(isBn ? 'নম্বরটি কপি করা হয়েছে!' : 'Number copied to clipboard!');
            });
        }
    }

    window.addEventListener('languageChanged', (e) => {
        const isBn = e.detail ? e.detail.isBangla : (localStorage.getItem('appLanguage') === 'bn');
        if (currentPaymentMethod === 'bkash' || currentPaymentMethod === 'nagad') {
            const cfg = MFS_CONFIG[currentPaymentMethod];
            const titleEl = document.getElementById('mfs-instruction-title');
            if (titleEl && cfg) {
                titleEl.innerText = isBn ? (cfg.title_bn || cfg.title_en) : (cfg.title_en || cfg.title_bn);
            }
        }
    });

    function placeOrder() {
        const baseCart = getActiveCart();
        if (baseCart.length === 0) return;

        // Verify that all items in cart are available in the current delivery area
        const unavailableItems = baseCart.filter(i => (i.is_out_of_stock === true || i.is_unavailable === true));
        if (unavailableItems.length > 0) {
            showToast(isBangla ? "কার্টের কিছু পণ্য আপনার বর্তমান ডেলিভারি এলাকায় পাওয়া যাচ্ছে না। দয়া করে সেগুলো রিমুভ করুন।" : "Some items are not available in your delivery area. Please remove them to proceed.", "error");
            return;
        }

        let isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        let userPhone = localStorage.getItem('userPhone');
        let userName = localStorage.getItem('userName') || 'Customer';

        if (window.serverCustomer && window.serverCustomer.phone) {
            isLoggedIn = true;
            userPhone = window.serverCustomer.phone;
            userName = window.serverCustomer.name || userName;
        }

        if (!isLoggedIn || !userPhone) {
            showToast(isBangla ? "অর্ডার সম্পন্ন করতে অনুগ্রহ করে প্রথমে লগইন করুন।" : "Please login first to place your order.", "error");
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 800);
            return;
        }

        const userLocation = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || '').trim();
        if (!userLocation || (typeof isValidLocationString === 'function' && !isValidLocationString(userLocation))) {
            showToast(isBangla ? "অনুগ্রহ করে প্রথমে আপনার ডেলিভারি ঠিকানা যুক্ত করুন।" : "Please add your delivery address first.", "error");
            if (typeof openLocationModal === 'function') {
                openLocationModal();
            }
            return;
        }

        // Validate manual mobile banking fields
        let transactionId = null;
        let senderPhone = null;

        if (currentPaymentMethod === 'bkash' || currentPaymentMethod === 'nagad') {
            senderPhone = document.getElementById('input-payment-sender-phone')?.value.trim();
            transactionId = document.getElementById('input-payment-trx-id')?.value.trim();

            if (!senderPhone) {
                showToast(isBangla ? "যে নম্বর থেকে টাকা পাঠিয়েছেন সেই মোবাইল নম্বরটি লিখুন।" : "Please enter the sender mobile number.", "error");
                document.getElementById('input-payment-sender-phone')?.focus();
                return;
            }
            if (!transactionId) {
                showToast(isBangla ? "পেমেন্টের TrxID (ট্রানজেকশন আইডি) লিখুন।" : "Please enter the Transaction ID (TrxID).", "error");
                document.getElementById('input-payment-trx-id')?.focus();
                return;
            }
        }

        const editingInvoiceId = sessionStorage.getItem('editing_invoice_id');
        const editingInvoiceCode = sessionStorage.getItem('editing_invoice_code');

        let totalBasePrice = 0;
        baseCart.forEach(i => totalBasePrice += (parseFloat(i.price || 0) * parseInt(i.quantity || 1)));

        let specialDiscountTotal = 0;
        if (activeCartSpecialDiscounts && activeCartSpecialDiscounts.length > 0) {
            activeCartSpecialDiscounts.forEach(d => {
                specialDiscountTotal += (parseFloat(d.amount) || 0);
            });
        }

        const netCartTotal = Math.max(0, totalBasePrice - specialDiscountTotal);

        let couponDiscount = 0;
        if (appliedCoupon) {
            if (appliedCoupon.rule && appliedCoupon.rule.discount_type === 'percentage') {
                couponDiscount = Math.round((netCartTotal * parseFloat(appliedCoupon.rule.discount_value)) / 100);
            } else {
                couponDiscount = appliedCoupon.discount_amount || 0;
            }
            couponDiscount = Math.min(couponDiscount, netCartTotal);
        }

        let totalOrderDiscount = specialDiscountTotal + couponDiscount;
        let grandTotal = Math.max(0, netCartTotal - couponDiscount) + currentDeliveryCharge;

        // Build combined items for submission and invoice (Base items + Unlocked Free Gifts)
        const combinedOrderItems = [...baseCart];
        if (activeCartGifts && activeCartGifts.length > 0) {
            activeCartGifts.forEach(g => {
                combinedOrderItems.push({
                    name: `[🎁 ${g.badge_text || 'Free Gift'}] ${g.name}`,
                    name_bn: `[🎁 ${g.badge_text || 'ফ্রি উপহার'}] ${g.name_bn || g.name}`,
                    price: 0,
                    oldPrice: g.oldPrice,
                    quantity: g.quantity || 1,
                    poriman: g.poriman || '',
                    poriman_bn: g.poriman_bn || '',
                    is_free_gift: 1,
                    gift_badge: g.badge_text
                });
            });
        }

        const now = new Date();
        const dateTimeStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });

        // Multi-page invoice chunking (Page 1: 12 items, Page 2+: 18 items)
        const pageChunks = [];
        let idx = 0;
        while (idx < combinedOrderItems.length) {
            const limit = (pageChunks.length === 0) ? 12 : 18;
            pageChunks.push(combinedOrderItems.slice(idx, idx + limit));
            idx += limit;
        }

        const totalPages = pageChunks.length || 1;
        let invoiceHtml = '';

        pageChunks.forEach((chunk, pageIndex) => {
            const isFirst = (pageIndex === 0);
            const isLast = (pageIndex === totalPages - 1);
            let rowsHtml = '';

            chunk.forEach((i, cIdx) => {
                const globalIdx = (pageIndex === 0) ? (cIdx + 1) : (12 + (pageIndex - 1) * 18 + cIdx + 1);
                const iPrice = parseFloat(i.price || 0);
                const iQty = parseInt(i.quantity || 1);
                const isGift = !!i.is_free_gift;
                rowsHtml += `
                    <tr style="border-bottom: 1px solid #f1f5f9; ${isGift ? 'background: #f0fdf4;' : ''}">
                        <td style="padding: 10px 8px; color: #64748b; font-size: 13px;">${globalIdx}</td>
                        <td style="padding: 10px 8px; color: ${isGift ? '#059669' : '#0f172a'}; font-weight: 700; font-size: 13.5px;">${i.name}</td>
                        <td style="padding: 10px 8px; color: ${isGift ? '#059669' : '#1e293b'}; text-align: center; font-size: 13.5px;">${isGift ? 'FREE' : '৳' + iPrice}</td>
                        <td style="padding: 10px 8px; color: #1e293b; text-align: center; font-size: 13.5px;">${iQty}</td>
                        <td style="padding: 10px 8px; color: ${isGift ? '#059669' : '#0f172a'}; text-align: right; font-weight: 800; font-size: 13.5px;">${isGift ? '৳0' : '৳' + (iPrice * iQty)}</td>
                    </tr>
                `;
            });

            let headerContent = '';
            if (isFirst) {
                headerContent = `
                    <!-- Top Center Header -->
                    <div style="text-align: center; margin-bottom: 12px;">
                        <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="width: 72px; height: 72px; object-fit: contain; margin-bottom: -10px; display: inline-block;">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top: -12px; margin-bottom: -8px;">
                            <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 75px; width: auto; object-fit: contain; display: inline-block;">
                        </div>
                        <p style="margin: -4px 0 0 0; font-size: 14.5px; color: #475569; font-weight: 700; font-family: Outfit, 'Hind Siliguri', sans-serif; line-height: 1.2;">
                             বাজারে নয়, বাজার আসবে আপনার ঘরে।
                        </p>
                    </div>

                    <hr style="border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;">

                    <!-- Customer Details & Order Metadata Row -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;">
                        <div style="max-width: 58%; text-align: left;">
                            <div><strong style="color: #0f172a;">Name:</strong> <span style="color: #334155; font-weight: 600;">${userName}</span></div>
                            <div><strong style="color: #0f172a;">Phone:</strong> <span style="color: #334155; font-weight: 600;">${userPhone}</span></div>
                            <div><strong style="color: #0f172a;">Location:</strong> <span style="color: #334155; font-weight: 600;">${(typeof isValidLocationString === 'function' && isValidLocationString(userLocation)) ? userLocation : ''}</span></div>
                        </div>
                        <div style="text-align: right;">
                            <div><strong style="color: #0f172a;">Order Type:</strong> <span style="color: #00c675; font-weight: 800;">Online Order</span></div>
                            <div><strong style="color: #0f172a;">Invoice No:</strong> <span class="invoice-no-field" style="color: #00c675; font-weight: 800;">${(isEditMode && editingInvoiceCode) ? editingInvoiceCode : '{{INVOICE_NO}}'}</span></div>
                            <div><strong style="color: #0f172a;">Date & Time:</strong> <span style="color: #334155; font-weight: 600;">${dateTimeStr}</span></div>
                            <div><strong style="color: #0f172a;">Page:</strong> <span style="color: #334155; font-weight: 600;">${pageIndex + 1} of ${totalPages}</span></div>
                        </div>
                    </div>
                `;
            } else {
                headerContent = `
                    <!-- Minimal Header for Page 2+ -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 52px; width: auto; object-fit: contain;">
                        <div style="font-size: 13px; color: #0f172a; text-align: right;">
                            <strong>Invoice No:</strong> <span class="invoice-no-field" style="font-weight: 800; color: #00c675;">${(isEditMode && editingInvoiceCode) ? editingInvoiceCode : '{{INVOICE_NO}}'}</span> &nbsp;|&nbsp; 
                            <strong>Page:</strong> <span style="font-weight: 700; color: #334155;">${pageIndex + 1} of ${totalPages}</span>
                        </div>
                    </div>
                `;
            }

            invoiceHtml += `
                <div class="a4-page" style="background: white; width: 100%; max-width: 800px; height: 1123px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: 'Outfit', sans-serif; overflow: hidden; align-self: stretch;">
                    
                    <!-- Faded Full Page Center Watermark -->
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;">
                        <img src="assets/bazarkinundotcom_logo.png" alt="Watermark" style="width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="Watermark Logo 2" style="width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;">
                    </div>

                    <div style="position: relative; z-index: 2; flex: 1; display: flex; flex-direction: column;">
                        ${headerContent}

                        <!-- Items Table -->
                        <div style="position: relative; margin-bottom: 20px; z-index: 2;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2; margin-bottom: 16px;">
                                <thead>
                                    <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a;">
                                        <th style="padding: 10px 8px; color: #0f172a; font-size: 13px; font-weight: 800; width: 45px;">SL</th>
                                        <th style="padding: 10px 8px; color: #0f172a; font-size: 13px; font-weight: 800;">Item Description</th>
                                        <th style="padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13px; font-weight: 800; width: 85px;">Price</th>
                                        <th style="padding: 10px 8px; color: #0f172a; text-align: center; font-size: 13px; font-weight: 800; width: 65px;">Qty</th>
                                        <th style="padding: 10px 8px; color: #0f172a; text-align: right; font-weight: 800; font-size: 13px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>

                            ${isLast ? `
                            <div style="display: flex; justify-content: flex-end; position: relative; z-index: 2; margin-top: 10px;">
                                <div style="width: 260px; font-size: 13.5px;">
                                    <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #64748b;">
                                        <span>Subtotal:</span>
                                        <span style="color: #0f172a; font-weight: 700;">৳${totalBasePrice}</span>
                                    </div>
                                    ${specialDiscountTotal > 0 ? `
                                    <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #ea580c; font-weight: 700;">
                                        <span>Combo/Deals Discount:</span>
                                        <span>-৳${specialDiscountTotal}</span>
                                    </div>` : ''}
                                    ${couponDiscount > 0 ? `
                                    <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #059669; font-weight: 700;">
                                        <span>Coupon (${appliedCoupon.code}):</span>
                                        <span>-৳${couponDiscount}</span>
                                    </div>` : ''}
                                    <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #64748b;">
                                        <span>Delivery Fee:</span>
                                        <span style="color: #0f172a; font-weight: 700;">${currentDeliveryCharge === 0 ? 'FREE' : '৳' + currentDeliveryCharge}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #64748b; font-size: 13px;">
                                        <span>Payment:</span>
                                        <span style="color: #0f172a; font-weight: 800;">${currentPaymentMethod === 'bkash' ? 'bKash (Trx: ' + (transactionId || 'Pending') + ')' : (currentPaymentMethod === 'nagad' ? 'Nagad (Trx: ' + (transactionId || 'Pending') + ')' : 'Cash on Delivery')}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-top: 1.5px solid #e2e8f0; font-size: 17px; font-weight: 900; color: #00c675;">
                                        <span>Total to Pay:</span>
                                        <span>৳${grandTotal}</span>
                                    </div>
                                </div>
                            </div>` : ''}
                        </div>
                    </div>

                    <!-- Bottom Footer with Dashed Line -->
                    <div style="border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; width: 100%;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="assets/bazarkinundotcom_logo2.png" alt="BazarKinun.com" style="height: 66px; width: auto; object-fit: contain;">
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color: #64748b; font-weight: 700; display: block; margin-bottom: 3px;">${(window.portalSettings && (window.isBangla ? window.portalSettings.supportHoursBn : window.portalSettings.supportHoursEn)) || '24/7 Support Center'}</span>
                            <strong style="font-size: 17px; color: #00c675; font-weight: 900; letter-spacing: 0.5px;">${(window.portalSettings && window.portalSettings.hotlinePhone) ? window.portalSettings.hotlinePhone : '01919-668960'}</strong>
                        </div>
                    </div>
                </div>
            `;
        });

        const reqBody = {
            action: (isEditMode && editingInvoiceCode) ? 'update_website_invoice' : 'create_order',
            edit_order_id: isEditMode ? editingInvoiceId : null,
            edit_invoice_number: isEditMode ? editingInvoiceCode : null,
            customer_name: userName,
            customer_phone: userPhone,
            customer_address: userLocation,
            police_station: userLocation,
            total_amount: grandTotal,
            coupon_code: appliedCoupon ? appliedCoupon.code : null,
            discount_amount: totalOrderDiscount,
            payment_method: currentPaymentMethod,
            transaction_id: transactionId,
            payment_sender_phone: senderPhone,
            items: combinedOrderItems,
            html_content: invoiceHtml,
            order_type: 'website'
        };

        // Submit to Backend API
        fetch('api/submit-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reqBody)
        })
        .then(async res => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch(e) {
                console.error("Non-JSON response:", text);
                throw new Error(text || "Invalid server response");
            }
        })
        .then(data => {
            if (data && data.status === 'success') {
                const invNo = data.invoice_number || data.order_number || editingInvoiceCode;
                const finalHtml = invoiceHtml.replaceAll('{{INVOICE_NO}}', invNo);
                document.getElementById('cart-main-view').style.display = 'none';
                const editBanner = document.getElementById('edit-invoice-banner');
                if (editBanner) editBanner.style.display = 'none';
                document.getElementById('preview-wrapper').style.display = 'flex';
                document.getElementById('placed-order-no-disp').innerText = `Invoice No: ${invNo}`;
                document.getElementById('pages-container').innerHTML = finalHtml;

                if (isEditMode) {
                    const titleElem = document.getElementById('order-success-title');
                    if (titleElem) {
                        titleElem.setAttribute('data-en', 'Invoice Updated Successfully!');
                        titleElem.setAttribute('data-bn', 'আপনার ইনভয়েস সফলভাবে আপডেট হয়েছে!');
                        titleElem.innerText = isBangla ? 'আপনার ইনভয়েস সফলভাবে আপডেট হয়েছে!' : 'Invoice Updated Successfully!';
                    }
                    // Clean up edit session
                    sessionStorage.removeItem('editing_invoice_id');
                    sessionStorage.removeItem('editing_invoice_code');
                    sessionStorage.removeItem('editing_invoice_cart');
                    showToast(isBangla ? `ইনভয়েস (${invNo}) সফলভাবে আপডেট হয়েছে!` : `Invoice (${invNo}) updated successfully!`, 'success');
                } else {
                    // Normal order: clear cart
                    localStorage.removeItem('cart');
                    if (typeof updateCartBadge === 'function') updateCartBadge();
                    if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend(true, { force_empty_cart: true });
                    showToast(isBangla ? `আপনার ইনভয়েস (${invNo}) তৈরি হয়েছে!` : `Invoice (${invNo}) created successfully!`, 'success');
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert("Order failed: " + ((data && data.message) ? data.message : 'Error occurred'));
            }
        })
        .catch(err => {
            console.error("Order submit failed:", err);
            const msg = (err && err.message && err.message.length < 150) ? err.message : (isBangla ? "সার্ভার বা ডাটাবেজ সংযোগে সমস্যা হয়েছে।" : "Database or server connection error.");
            alert(msg);
        });
    }

    function initCartPage() {
        const userLoc = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userLocation') || '').trim();

        if (typeof revalidateCartPrices === 'function') {
            revalidateCartPrices(userLoc, false);
        } else {
            // Hydrate from cached rules if available
            let hasCache = false;
            try {
                const cachedCatalog = sessionStorage.getItem('storeProductsCatalog');
                if (cachedCatalog) {
                    catalogProducts = JSON.parse(cachedCatalog);
                    hasCache = true;
                }
                const cachedRules = sessionStorage.getItem('storeDiscountRules');
                if (cachedRules) storeDiscountRules = JSON.parse(cachedRules);
            } catch(e) {}

            renderCart();

            if (!hasCache || !catalogProducts || catalogProducts.length === 0) {
                fetch(`api/store-data.php?location=${encodeURIComponent(userLoc || '')}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.status === 'success') {
                            if (d.products) {
                                catalogProducts = d.products || [];
                                sessionStorage.setItem('storeProductsCatalog', JSON.stringify(d.products));
                            }
                            if (d.categories) catalogCategories = d.categories || [];
                            if (d.discount_rules) {
                                storeDiscountRules = d.discount_rules || [];
                                sessionStorage.setItem('storeDiscountRules', JSON.stringify(storeDiscountRules));
                            }
                            renderCart();
                        }
                    })
                    .catch(() => {});
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCartPage);
    } else {
        initCartPage();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
