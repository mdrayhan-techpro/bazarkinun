<?php
// write_list.php
$pageTitle = 'Write Your Grocery List - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>



<style>
    @media (max-width: 640px) {
        .write-list-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .write-list-title {
            font-size: 22px !important;
        }
        #preview-actions {
            justify-content: center !important;
        }
        #preview-actions button {
            flex: 1 1 calc(50% - 6px) !important;
            padding: 10px 12px !important;
            font-size: 13px !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }
    @media (max-width: 420px) {
        .write-list-title {
            font-size: 19px !important;
        }
        #preview-actions button {
            flex: 1 1 100% !important;
        }
    }
</style>

<main style="padding: 30px 0; background-color: #f8fafc; min-height: 75vh;">
    <div class="container">
        <!-- Input Form View -->
        <div id="input-view" style="display: block;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; flex-wrap: wrap; gap: 12px;">
                <div class="write-list-header" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <a href="javascript:void(0)" onclick="goBackToPreviousPage()" style="text-decoration: none; color: var(--primary-color); font-weight: 700; font-size: 13px; padding: 8px 16px; border: 1px solid var(--primary-color); border-radius: 8px; transition: 0.3s; background: white; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                        <i class="fa-solid fa-arrow-left"></i> <span class="dynamic-lang" data-en="Go Back" data-bn="ফিরে যান">Go Back</span>
                    </a>
                    <h1 class="dynamic-lang write-list-title" data-en="Write Your Grocery List" data-bn="আপনার বাজারের লিস্ট লিখুন" style="font-size: 28px; color: #0f172a; margin: 0; font-weight: 900;">Write Your Grocery List</h1>
                </div>
            </div>

            <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; margin-bottom: 25px;">
                <div style="margin-bottom: 20px; color: #64748b; font-size: 14px; line-height: 1.5;" class="dynamic-lang" data-en="Type your grocery items one by one below. Enter product name, brand/details, and quantity. A new row will appear automatically as you type." data-bn="নিচে একটি একটি করে আপনার বাজারের আইটেমগুলো লিখুন। পণ্যের নাম, ব্রান্ড বা বিবরণ এবং পরিমাণ উল্লেখ করুন। লিখতে থাকলে স্বয়ংক্রিয়ভাবে নতুন রো যুক্ত হবে।">
                    Type your grocery items one by one below. Enter product name, brand/details, and quantity. A new row will appear automatically as you type.
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 600px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #0f172a; background: #f8fafc;">
                                <th style="padding: 12px 14px; color: #0f172a; width: 50px; font-size: 13px;">SL</th>
                                <th class="dynamic-lang" data-en="Item Name" data-bn="পণ্যের নাম" style="padding: 12px 14px; color: #0f172a; font-size: 13px;">Item Name</th>
                                <th class="dynamic-lang" data-en="Specification / Brand" data-bn="বিবরণ / ব্র্যান্ড" style="padding: 12px 14px; color: #0f172a; font-size: 13px;">Specification / Brand</th>
                                <th class="dynamic-lang" data-en="Quantity / Unit" data-bn="পরিমাণ / একক" style="padding: 12px 14px; color: #0f172a; font-size: 13px;">Quantity / Unit</th>
                            </tr>
                        </thead>
                        <tbody id="grocery-tbody">
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 14px; font-weight: 700; color: #94a3b8;">1</td>
                                <td style="padding: 10px 14px;"><input type="text" class="item-input" oninput="checkRow(this)" placeholder="Miniket Rice" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                                <td style="padding: 10px 14px;"><input type="text" class="spec-input" placeholder="5kg Bag or ACI Pure" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                                <td style="padding: 10px 14px;"><input type="text" class="qty-input" placeholder="1 Bag" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="previewList()" style="background: var(--primary-color); color: white; border: none; padding: 12px 28px; border-radius: 8px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); display: flex; align-items: center; gap: 8px;">
                        <span class="dynamic-lang" data-en="Preview List" data-bn="লিস্ট প্রিভিউ দেখুন">Preview List</span> <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview & Order Submission View -->
        <div id="preview-wrapper" style="display: none; flex-direction: column; gap: 6px; align-items: center; width: 100%;">
            <div id="pages-container" style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">
                <!-- Generated Document Page -->
            </div>
            
            <div id="preview-actions" class="no-print" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; width: 100%; max-width: 800px; box-sizing: border-box; background: white; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); flex-wrap: wrap;">
                <button id="btn-preview-edit" onclick="editList()" style="background: white; color: #059669; border: 1.5px solid #10b981; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-pen-to-square"></i> <span class="dynamic-lang" data-en="Edit" data-bn="এডিট">Edit</span></button>
                <button id="btn-preview-new" onclick="resetList()" style="background: white; color: #dc2626; border: 1.5px solid #fca5a5; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-trash-can"></i> <span class="dynamic-lang" data-en="New List" data-bn="নতুন লিস্ট">New List</span></button>
                <button id="btn-preview-save" onclick="saveListForLater()" style="background: white; color: var(--primary-color); border: 1.5px solid var(--primary-color); padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-regular fa-bookmark"></i> <span class="dynamic-lang" data-en="Save List" data-bn="সংরক্ষণ করুন">Save List</span></button>
                <button id="btn-preview-order" onclick="placeCustomOrder()" style="background: linear-gradient(135deg, #00c675, #059669); color: white; border: none; padding: 9px 22px; border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.35); display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm & Place Order" data-bn="অর্ডার কনফার্ম করুন">Confirm & Place Order</span></button>
                <button id="btn-preview-print" onclick="window.print()" style="background: #334155; color: white; border: none; padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-print"></i> <span class="dynamic-lang" data-en="Print" data-bn="প্রিন্ট">Print</span></button>
            </div>
        </div>
    </div>
</main>

<script>
    let slCounter = 1;

    function goBackToPreviousPage() {
        if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) {
            window.location.href = document.referrer;
        } else if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = 'index.php';
        }
    }

    function checkRow(input) {
        const row = input.closest('tr');
        const tbody = document.getElementById('grocery-tbody');
        if (row === tbody.lastElementChild && input.value.trim() !== '') {
            slCounter++;
            const newRow = document.createElement('tr');
            newRow.style.borderBottom = "1px solid #f1f5f9";
            newRow.innerHTML = `
                <td style="padding: 10px 14px; font-weight: 700; color: #94a3b8;">${slCounter}</td>
                <td style="padding: 10px 14px;"><input type="text" class="item-input" oninput="checkRow(this)" placeholder="" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                <td style="padding: 10px 14px;"><input type="text" class="spec-input" placeholder="" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                <td style="padding: 10px 14px;"><input type="text" class="qty-input" placeholder="" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
            `;
            tbody.appendChild(newRow);
        }
    }

    function previewList() {
        const tbody = document.getElementById('grocery-tbody');
        const rows = tbody.querySelectorAll('tr');
        const validItems = [];

        rows.forEach(r => {
            const item = r.querySelector('.item-input')?.value.trim();
            const spec = r.querySelector('.spec-input')?.value.trim();
            const qty = r.querySelector('.qty-input')?.value.trim();
            if (item) {
                validItems.push({ item, spec, qty });
            }
        });

        if (validItems.length === 0) {
            showToast(isBangla ? "অনুগ্রহ করে অন্তত একটি পণ্যের নাম লিখুন" : "Please enter at least one item", "error");
            return;
        }

        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        const userPhone = localStorage.getItem('userPhone');
        const userName = localStorage.getItem('userName') || 'Customer';

        if (!isLoggedIn || !userPhone) {
            showToast(isBangla ? "অর্ডার বা ফর্দ সংরক্ষণ করতে অনুগ্রহ করে প্রথমে লগইন করুন।" : "Please login first to place order or save list.", "error");
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

        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });

        const cleanPhone = userPhone.replace(/\D/g, '');
        const last6 = cleanPhone.length >= 6 ? cleanPhone.slice(-6) : cleanPhone.padStart(6, '0');
        const cleanName = userName.replace(/[^A-Za-z]/g, '').toUpperCase();
        const namePrefix = cleanName.length >= 2 ? cleanName.slice(0, 2) : cleanName.padEnd(2, 'X');
        const editingCode = localStorage.getItem('editing_order_code') || localStorage.getItem('editing_list_code');
        const listCode = editingCode || `LST-${last6}-${namePrefix}-1`;
        const isExistingOrder = !!localStorage.getItem('editing_order_code') || listCode.startsWith('ORD-');

        // Pagination Logic (Page 1: 14 items, Page 2+: 20 items)
        const pageChunks = [];
        let idx = 0;
        while (idx < validItems.length) {
            const limit = (pageChunks.length === 0) ? 14 : 20;
            pageChunks.push(validItems.slice(idx, idx + limit));
            idx += limit;
        }

        const totalPages = pageChunks.length || 1;
        let fullPagesHtml = '';

        pageChunks.forEach((chunk, pageIndex) => {
            const isFirst = (pageIndex === 0);
            const isLast = (pageIndex === totalPages - 1);
            let rowsHtml = '';

            chunk.forEach((it, cIdx) => {
                const globalIndex = (pageIndex === 0) ? (cIdx + 1) : (14 + (pageIndex - 1) * 22 + cIdx + 1);
                const specText = it.spec ? ` <span style="color: #64748b; font-weight: 500;">(${it.spec})</span>` : '';
                rowsHtml += `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 11px 10px; color: #0f172a; font-size: 13.5px; font-weight: 700;">${globalIndex}</td>
                        <td style="padding: 11px 10px; color: #0f172a; font-weight: 700; font-size: 13.5px;">${it.item}${specText}</td>
                        <td style="padding: 11px 10px; color: #0f172a; text-align: right; font-weight: 800; font-size: 13.5px;">${it.qty || '1'}</td>
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
                        <p style="margin: -4px 0 0 0; font-size: 14px; color: #475569; font-weight: 700; font-family: Outfit, sans-serif; line-height: 1.2;">
                            বাজারে নয়, বাজার আসবে আপনার ঘরে।
                        </p>
                    </div>

                    <hr style="border: none; border-top: 1.5px solid #e2e8f0; margin: 0 0 20px 0;">

                    <!-- Customer Details & Order Metadata Row -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;">
                        <div style="max-width: 58%;">
                            <div><strong style="color: #0f172a;">Name:</strong> <span style="color: #334155; font-weight: 600;">${userName}</span></div>
                            <div><strong style="color: #0f172a;">Phone:</strong> <span style="color: #334155; font-weight: 600;">${userPhone}</span></div>
                            <div><strong style="color: #0f172a;">Location:</strong> <span style="color: #334155; font-weight: 600;">${userLocation}</span></div>
                        </div>
                        <div style="text-align: right;">
                            <div><strong style="color: #0f172a;">${isExistingOrder ? 'Order No:' : 'List Code:'}</strong> <span style="color: #00c675; font-weight: 800;">${listCode}</span></div>
                            <div><strong style="color: #0f172a;">Date & Time:</strong> <span style="color: #334155; font-weight: 600;">${dateStr}</span></div>
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
                            <strong>${isExistingOrder ? 'Order No:' : 'List Code:'}</strong> <span style="font-weight: 800; color: #00c675;">${listCode}</span> &nbsp;|&nbsp; 
                            <strong>Page:</strong> <span style="font-weight: 700; color: #334155;">${pageIndex + 1} of ${totalPages}</span>
                        </div>
                    </div>
                `;
            }

            fullPagesHtml += `
                <div class="a4-page" style="background: white; width: 100%; max-width: 800px; height: 1123px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.08); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: 'Outfit', sans-serif; overflow: hidden; align-self: stretch;">
                    
                    <!-- Faded Full Page Center Watermark -->
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;">
                        <img src="assets/bazarkinundotcom_logo.png" alt="Watermark" style="width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                        <img src="assets/bazarkinundotcom_logo2.png" alt="Watermark Logo 2" style="width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;">
                    </div>

                    <div style="position: relative; z-index: 2; flex: 1;">
                        ${headerContent}

                        <!-- Items Table -->
                        <div style="position: relative; margin-bottom: 20px; z-index: 2;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; position: relative; z-index: 2;">
                                <thead>
                                    <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a;">
                                        <th style="padding: 11px 10px; color: #0f172a; font-size: 13px; font-weight: 800; width: 50px;">SL</th>
                                        <th style="padding: 11px 10px; color: #0f172a; font-size: 13px; font-weight: 800;">Item Description</th>
                                        <th style="padding: 11px 10px; color: #0f172a; text-align: right; font-size: 13px; font-weight: 800; width: 120px;">Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bottom Footer with Dashed Line -->
                    <div style="border-top: 1.5px dashed #cbd5e1; padding-top: 16px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;">
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

        document.getElementById('pages-container').innerHTML = fullPagesHtml;
        document.getElementById('input-view').style.display = 'none';
        document.getElementById('preview-wrapper').style.display = 'flex';

        const btnSave = document.getElementById('btn-preview-save');
        const btnNew = document.getElementById('btn-preview-new');
        const btnOrder = document.getElementById('btn-preview-order');

        if (isExistingOrder) {
            if (btnSave) btnSave.style.display = 'none';
            if (btnNew) btnNew.style.display = 'none';
            if (btnOrder) {
                btnOrder.innerHTML = `<i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Update Order" data-bn="অর্ডার আপডেট করুন">${isBangla ? 'অর্ডার আপডেট করুন' : 'Update Order'}</span>`;
            }
        } else {
            if (btnSave) btnSave.style.display = 'inline-flex';
            if (btnNew) btnNew.style.display = 'inline-flex';
            if (btnOrder) {
                btnOrder.innerHTML = `<i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm & Place Order" data-bn="অর্ডার কনফার্ম করুন">${isBangla ? 'অর্ডার কনফার্ম করুন' : 'Confirm & Place Order'}</span>`;
            }
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function editList() {
        document.getElementById('preview-wrapper').style.display = 'none';
        document.getElementById('input-view').style.display = 'block';
    }

    function resetList() {
        const msg = isBangla ? "আপনি কি নতুন লিস্ট শুরু করতে চান? বর্তমান এন্ট্রিগুলো মুছে যাবে।" : "Do you want to start a new list? Current entries will be cleared.";
        showConfirm(msg, () => {
            localStorage.removeItem('editListDraft');
            localStorage.removeItem('editing_list_id');
            localStorage.removeItem('editing_list_code');
            localStorage.removeItem('editing_order_id');
            localStorage.removeItem('editing_order_code');
            location.reload();
        }, {
            title: isBangla ? "নতুন লিস্ট শুরু করবেন?" : "Start Fresh List?",
            confirmText: isBangla ? "হ্যাঁ, নতুন শুরু করুন" : "Yes, Start Fresh",
            cancelText: isBangla ? "বাতিল" : "Cancel",
            type: "primary",
            icon: "fa-solid fa-arrows-rotate"
        });
    }

    function saveListForLater() {
        const userLocation = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || '').trim();
        if (!userLocation || (typeof isValidLocationString === 'function' && !isValidLocationString(userLocation))) {
            showToast(isBangla ? "অনুগ্রহ করে প্রথমে আপনার ডেলিভারি ঠিকানা যুক্ত করুন।" : "Please add your delivery address first.", "error");
            if (typeof openLocationModal === 'function') {
                openLocationModal();
            }
            return;
        }

        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        const userPhone = localStorage.getItem('userPhone');
        const userName = localStorage.getItem('userName') || 'Customer';

        if (!isLoggedIn || !userPhone) {
            showToast(isBangla ? "ফর্দ সংরক্ষণ করতে অনুগ্রহ করে প্রথমে লগইন করুন।" : "Please login first to save list.", "error");
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 800);
            return;
        }

        const pagesContainer = document.getElementById('pages-container');
        const contentHtml = pagesContainer.innerHTML;
        const editingListId = localStorage.getItem('editing_list_id') || '';
        const editingListCode = localStorage.getItem('editing_list_code') || '';

        fetch('api/submit-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_list',
                customer_name: userName,
                customer_phone: userPhone,
                customer_address: userLocation,
                police_station: userLocation,
                content_text: contentHtml,
                order_type: 'written',
                status: 'Draft',
                list_id: editingListId,
                list_code: editingListCode
            })
        })
        .then(res => res.json())
        .then(data => {
            localStorage.removeItem('editing_list_id');
            localStorage.removeItem('editing_list_code');
            localStorage.removeItem('editing_order_id');
            localStorage.removeItem('editing_order_code');
            const listCode = data.list_code || editingListCode || 'LST-Saved';
            showToast(isBangla ? `বাজারের ফর্দ সংরক্ষিত হয়েছে (${listCode})!` : `Grocery list saved (${listCode})!`, "success");
            setTimeout(() => {
                window.location.href = 'profile.php?tab=list';
            }, 1000);
        })
        .catch(() => {
            showToast(isBangla ? "ফর্দ সংরক্ষিত হয়েছে!" : "List saved!", "success");
            setTimeout(() => {
                window.location.href = 'profile.php?tab=list';
            }, 1000);
        });
    }

    function placeCustomOrder() {
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        const userPhone = localStorage.getItem('userPhone');
        const userName = localStorage.getItem('userName') || 'Customer';

        if (!isLoggedIn || !userPhone) {
            showToast(isBangla ? "অর্ডার সম্পন্ন করতে অনুগ্রহ করে প্রথমে লগইন করুন।" : "Please login first to place order.", "error");
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

        const pagesContainer = document.getElementById('pages-container');
        const contentHtml = pagesContainer.innerHTML;
        const editingOrderId = localStorage.getItem('editing_order_id') || '';
        const editingOrderCode = localStorage.getItem('editing_order_code') || '';
        const editingListId = localStorage.getItem('editing_list_id') || '';
        const editingListCode = localStorage.getItem('editing_list_code') || '';

        fetch('api/submit-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'order_list',
                customer_name: userName,
                customer_phone: userPhone,
                customer_address: userLocation,
                police_station: userLocation,
                content_text: contentHtml,
                order_type: 'text_list',
                edit_order_id: editingOrderId,
                edit_order_number: editingOrderCode,
                list_id: editingListId,
                list_code: editingListCode
            })
        })
        .then(res => res.json())
        .then(data => {
            localStorage.removeItem('editing_order_id');
            localStorage.removeItem('editing_order_code');
            localStorage.removeItem('editing_list_id');
            localStorage.removeItem('editing_list_code');
            if (data.status === 'success') {
                showToast((isBangla ? "বাজারের ফর্দটি অর্ডারে জমা হয়েছে! অর্ডার নম্বর: " : "Your grocery list order has been placed! Order: ") + (data.order_number || editingOrderCode));
                setTimeout(() => {
                    window.location.href = 'profile.php?tab=order';
                }, 1200);
            } else {
                alert("Submission failed: " + (data.message || 'Error'));
            }
        })
        .catch(err => {
            console.error("Order submission failed:", err);
            alert("Database connection error");
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const isEditMode = window.location.search.includes('edit=1');
        if (!isEditMode) {
            localStorage.removeItem('editing_list_id');
            localStorage.removeItem('editing_list_code');
            localStorage.removeItem('editing_order_id');
            localStorage.removeItem('editing_order_code');
            localStorage.removeItem('editListDraft');
            sessionStorage.removeItem('editListDraft');
        } else {
            const editDraft = localStorage.getItem('editListDraft') || sessionStorage.getItem('editListDraft');
            if (editDraft) {
                try {
                    const items = JSON.parse(editDraft);
                    if (Array.isArray(items) && items.length > 0) {
                        const tbody = document.getElementById('grocery-tbody');
                        tbody.innerHTML = '';
                        slCounter = 0;
                        items.forEach(it => {
                            const itemName = it.item || it.name || it.product || '';
                            const itemSpec = it.spec || it.brand || it.poriman || '';
                            const itemQty = it.qty || it.quantity || '1';
                            if (!itemName && !itemSpec) return;

                            slCounter++;
                            const tr = document.createElement('tr');
                            tr.style.borderBottom = "1px solid #f1f5f9";
                            tr.innerHTML = `
                                <td style="padding: 10px 14px; font-weight: 700; color: #94a3b8;">${slCounter}</td>
                                <td style="padding: 10px 14px;"><input type="text" class="item-input" value="${itemName.replace(/"/g, '&quot;')}" oninput="checkRow(this)" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                                <td style="padding: 10px 14px;"><input type="text" class="spec-input" value="${itemSpec.replace(/"/g, '&quot;')}" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                                <td style="padding: 10px 14px;"><input type="text" class="qty-input" value="${itemQty.replace(/"/g, '&quot;')}" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                            `;
                            tbody.appendChild(tr);
                        });
                        // Add 1 blank row at end for adding new items
                        slCounter++;
                        const blankTr = document.createElement('tr');
                        blankTr.style.borderBottom = "1px solid #f1f5f9";
                        blankTr.innerHTML = `
                            <td style="padding: 10px 14px; font-weight: 700; color: #94a3b8;">${slCounter}</td>
                            <td style="padding: 10px 14px;"><input type="text" class="item-input" oninput="checkRow(this)" placeholder="Miniket Rice" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                            <td style="padding: 10px 14px;"><input type="text" class="spec-input" placeholder="5kg Bag or ACI Pure" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                            <td style="padding: 10px 14px;"><input type="text" class="qty-input" placeholder="1 Bag" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;"></td>
                        `;
                        tbody.appendChild(blankTr);
                    }
                } catch (e) {
                    console.error(e);
                }
                localStorage.removeItem('editListDraft');
                sessionStorage.removeItem('editListDraft');
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
