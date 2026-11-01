<?php
// profile.php
// Complete Feature Parity with profile.html
$pageTitle = 'My Account & Orders - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .profile-container {
        max-width: 650px;
        margin: 0 auto;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        padding: 35px;
        border: 1px solid #e2e8f0;
    }
    .profile-header-card {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 25px;
    }
    .profile-avatar-large {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 3px solid var(--primary-color);
        position: relative;
        flex-shrink: 0;
    }
    .avatar-edit-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(0,0,0,0.65);
        color: white;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 0;
        cursor: pointer;
        display: none;
    }
    .profile-avatar-large:hover .avatar-edit-overlay {
        display: block;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-weight: 700;
        font-size: 13.5px;
        margin-bottom: 8px;
        color: #334155;
    }
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
        outline: none;
        transition: 0.25s;
        background: #f8fafc;
        box-sizing: border-box;
        color: #0f172a;
    }
    .form-control:focus {
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
    .form-control:disabled {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        border-color: #e2e8f0;
    }

    .profile-tabs-nav {
        display: flex;
        gap: 10px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 25px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .profile-tab-btn {
        padding: 12px 18px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 15px;
        font-weight: 800;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .profile-tab-btn:hover {
        color: var(--primary-color);
    }
    .profile-tab-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    .badge-count {
        background: #10b981;
        color: white;
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 12px;
        font-weight: 800;
    }

    .list-item-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 24px;
        margin-bottom: 14px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        cursor: pointer;
        transition: 0.2s;
    }
    .list-item-card:hover {
        border-color: #a7f3d0;
        box-shadow: 0 8px 18px rgba(16, 185, 129, 0.08);
        transform: translateY(-2px);
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .status-pending, .status-draft { background: #fef3c7; color: #92400e; }
    .status-processing { background: #e0e7ff; color: #3730a3; }
    .status-delivering { background: #dbeafe; color: #1e40af; }
    .status-completed, .status-delivered, .status-accepted { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }

    @media (max-width: 600px) {
        .profile-container { padding: 20px 16px !important; }
        .list-item-card { padding: 14px !important; }
        .profile-tab-btn { padding: 10px 12px !important; font-size: 13.5px !important; }
        #invoice-view-modal .no-print button {
            flex: 1 1 calc(50% - 6px) !important;
            padding: 10px 12px !important;
            font-size: 13px !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }

    @media (max-width: 480px) {
        .profile-header-card {
            flex-direction: column !important;
            text-align: center !important;
            gap: 14px !important;
            padding-bottom: 18px !important;
        }
        .profile-avatar-large {
            margin: 0 auto;
        }
        .list-item-card {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 12px !important;
        }
        .list-item-card > div:last-child {
            width: 100% !important;
            justify-content: space-between !important;
        }
    }

    @media (max-width: 374px) {
        .profile-tab-btn {
            padding: 8px 10px !important;
            font-size: 12.5px !important;
        }
        #invoice-view-modal .no-print button {
            flex: 1 1 100% !important;
        }
    }
</style>

<main style="padding: 24px 0; background-color: #f8fafc; min-height: 80vh;">
    <div class="container">
        <!-- Tabs Navigation -->
        <div class="profile-tabs-nav">
            <button class="profile-tab-btn active" id="tab-btn-profile" onclick="switchProfileTab('profile')">
                <i class="fa-solid fa-user-circle"></i> <span class="dynamic-lang" data-en="My Profile" data-bn="আমার প্রোফাইল">My Profile</span>
            </button>
            <button class="profile-tab-btn" id="tab-btn-order" onclick="switchProfileTab('order')">
                <i class="fa-solid fa-box-archive"></i> <span class="dynamic-lang" data-en="My Orders" data-bn="আমার অর্ডারসমূহ">My Orders</span>
                <span class="badge-count" id="badge-order" style="display: none;">0</span>
            </button>
            <button class="profile-tab-btn" id="tab-btn-invoice" onclick="switchProfileTab('invoice')">
                <i class="fa-solid fa-file-invoice-dollar"></i> <span class="dynamic-lang" data-en="My Invoices" data-bn="মানি রিসিট ও ভাউচার">My Invoices</span>
                <span class="badge-count" id="badge-invoice" style="display: none;">0</span>
            </button>
            <button class="profile-tab-btn" id="tab-btn-list" onclick="switchProfileTab('list')">
                <i class="fa-solid fa-list-check"></i> <span class="dynamic-lang" data-en="Saved Lists" data-bn="সংরক্ষিত লিস্ট">Saved Lists</span>
                <span class="badge-count" id="badge-list" style="display: none;">0</span>
            </button>
        </div>

        <!-- 1. Profile Tab Content -->
        <div id="content-tab-profile">
            <div class="profile-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; font-size: 22px; font-weight: 900; color: #0f172a;" class="dynamic-lang" data-en="Personal Information" data-bn="ব্যক্তিগত তথ্য">Personal Information</h2>
                    <button id="edit-profile-btn" onclick="enableProfileEdit()" style="background: white; border: 1.5px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-pen-to-square" style="color: var(--primary-color);"></i> <span class="dynamic-lang" data-en="Edit Profile" data-bn="প্রোফাইল এডিট">Edit Profile</span>
                    </button>
                </div>

                <div class="profile-header-card">
                    <div class="profile-avatar-large" id="large-avatar-box">
                        <i class="fa-solid fa-user" style="color: #9ca3af; font-size: 36px;" id="default-user-icon"></i>
                        <div class="avatar-edit-overlay dynamic-lang" data-en="Change" data-bn="পরিবর্তন" onclick="document.getElementById('profile-photo-file').click()">Change</div>
                    </div>
                    <input type="file" id="profile-photo-file" accept="image/*" style="display: none;" onchange="handleCustomerPhotoChange(event)">
                    <div>
                        <h3 id="prof-display-name" style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">User</h3>
                        <p id="prof-display-phone" style="margin: 0; color: #64748b; font-size: 14px; font-weight: 600;">01919668960</p>
                    </div>
                </div>

                <form id="customer-profile-form" onsubmit="saveCustomerProfile(event)">
                    <div class="form-group">
                        <label class="dynamic-lang" data-en="Full Name" data-bn="পুরো নাম">Full Name</label>
                        <input type="text" class="form-control" id="cust-input-name" disabled required>
                    </div>
                    <div class="form-group">
                        <label class="dynamic-lang" data-en="Phone Number" data-bn="ফোন নম্বর">Phone Number</label>
                        <input type="tel" class="form-control" id="cust-input-phone" disabled required>
                    </div>
                    <div class="form-group">
                        <label class="dynamic-lang" data-en="Email Address" data-bn="ইমেইল অ্যাড্রেস">Email Address</label>
                        <input type="email" class="form-control" id="cust-input-email" placeholder="user@example.com" disabled>
                    </div>

                    <div id="profile-edit-actions" style="display: none; gap: 12px; margin-top: 25px;">
                        <button type="button" onclick="cancelProfileEdit()" style="flex: 1; background: white; border: 1.5px solid #cbd5e1; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; color: #475569;"><span class="dynamic-lang" data-en="Cancel" data-bn="বাতিল">Cancel</span></button>
                        <button type="submit" style="flex: 2; background: var(--primary-color); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);"><i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Save Changes" data-bn="পরিবর্তন সংরক্ষণ করুন">Save Changes</span></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Orders Tab Content -->
        <div id="content-tab-order" style="display: none;">
            <div id="orders-list-container">
                <p style="text-align: center; padding: 40px; color: #64748b;" class="dynamic-lang" data-en="Loading your orders..." data-bn="আপনার অর্ডার লোড হচ্ছে...">Loading your orders...</p>
            </div>
        </div>

        <!-- 3. Invoices Tab Content -->
        <div id="content-tab-invoice" style="display: none;">
            <div id="invoices-list-container">
                <p style="text-align: center; padding: 40px; color: #64748b;" class="dynamic-lang" data-en="Loading invoices..." data-bn="ইনভয়েস লোড হচ্ছে...">Loading invoices...</p>
            </div>
        </div>

        <!-- 4. Saved Lists Tab Content -->
        <div id="content-tab-list" style="display: none;">
            <div id="lists-container">
                <p style="text-align: center; padding: 40px; color: #64748b;" class="dynamic-lang" data-en="Loading saved grocery lists..." data-bn="সংরক্ষিত ফর্দ লোড হচ্ছে...">Loading saved grocery lists...</p>
            </div>
        </div>
    </div>
</main>

<!-- A4 Document / Invoice View Modal -->
<div id="invoice-view-modal" onclick="if(event.target===this) this.style.display='none'" style="display: none; position: fixed; inset: 0; background: rgba(28, 25, 23, 0.62); z-index: 5000; overflow-y: auto; padding: 40px 15px 60px 15px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-sizing: border-box;">
    <div style="position: relative; width: 100%; max-width: 800px; margin: 0 auto; height: auto;">
        <!-- Top-Right Floating Close Button -->
        <button onclick="document.getElementById('invoice-view-modal').style.display='none'" style="position: absolute; top: -16px; right: -16px; background: #ffffff; border: 2px solid #e7e5e4; font-size: 22px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 1000; color: #292524; box-shadow: 0 6px 20px rgba(0,0,0,0.15); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="Close">&times;</button>
        
        <!-- Modal Card Content (No inner scrollbar, expands naturally) -->
        <div style="position: relative; height: auto; overflow: visible; display: flex; flex-direction: column; gap: 6px; align-items: center; width: 100%;">
            <div id="invoice-modal-content" style="display: flex; flex-direction: column; gap: 20px; align-items: center; width: 100%;"></div>
            
            <div class="no-print" style="background: white; border: 1px solid #e2e8f0; padding: 12px 20px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); flex-wrap: wrap; width: 100%; max-width: 800px; box-sizing: border-box;">
                <button id="modal-order-now-btn" onclick="handleModalOrderNow()" style="display: none; background: #00c675; color: white; border: none; padding: 9px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; align-items: center; gap: 6px; font-size: 14px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3); transition: all 0.2s;">
                    <i class="fa-solid fa-paper-plane"></i> <span class="dynamic-lang" data-en="Order Now" data-bn="অর্ডার করুন">Order Now</span>
                </button>
                <button id="modal-confirm-btn" onclick="handleModalConfirm()" style="display: none; background: #00c675; color: white; border: none; padding: 9px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; align-items: center; gap: 6px; font-size: 14px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.3);">
                    <i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm Invoice" data-bn="ইনভয়েস কনফার্ম করুন">Confirm Invoice</span>
                </button>
                <button id="modal-cancel-btn" onclick="handleModalCancel()" style="display: none; background: #fff1f2; border: 1.5px solid #fecdd3; color: #e11d48; padding: 9px 18px; border-radius: 8px; font-weight: 800; cursor: pointer; align-items: center; gap: 6px; font-size: 14px; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.1); transition: all 0.2s;">
                    <i class="fa-solid fa-xmark"></i> <span class="dynamic-lang" data-en="Cancel Order" data-bn="অর্ডার বাতিল করুন">Cancel Order</span>
                </button>
                <button id="modal-edit-btn" onclick="handleModalEdit()" style="background: white; border: 1.5px solid #10b981; color: #059669; padding: 9px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.1); transition: all 0.2s;">
                    <i class="fa-solid fa-pen-to-square"></i> <span class="dynamic-lang" data-en="Edit" data-bn="এডিট">Edit</span>
                </button>
                <button id="modal-delete-btn" onclick="handleModalDelete()" style="background: white; border: 1.5px solid #fca5a5; color: #dc2626; padding: 9px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; box-shadow: 0 2px 6px rgba(239, 68, 68, 0.1); transition: all 0.2s;">
                    <i class="fa-solid fa-trash"></i> <span class="dynamic-lang" data-en="Delete" data-bn="মুছুন">Delete</span>
                </button>
                <button onclick="document.getElementById('invoice-view-modal').style.display='none'" style="background: white; border: 1.5px solid #e2e8f0; padding: 9px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; color: #57534e; font-size: 14px; transition: all 0.2s;">
                    <span class="dynamic-lang" data-en="Close" data-bn="বন্ধ করুন">Close</span>
                </button>
                <button onclick="window.print()" style="background: linear-gradient(135deg, #00c675, #059669); color: white; border: none; padding: 9px 22px; border-radius: 8px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.35); transition: all 0.2s;">
                    <i class="fa-solid fa-print"></i> <span class="dynamic-lang" data-en="Print" data-bn="প্রিন্ট">Print</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCustomerData = null;
    let newAvatarBase64 = null;

    function switchProfileTab(tabName) {
        document.querySelectorAll('.profile-tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('content-tab-profile').style.display = 'none';
        document.getElementById('content-tab-order').style.display = 'none';
        document.getElementById('content-tab-invoice').style.display = 'none';
        document.getElementById('content-tab-list').style.display = 'none';

        document.getElementById(`tab-btn-${tabName}`)?.classList.add('active');
        document.getElementById(`content-tab-${tabName}`).style.display = 'block';

        // Update URL query without page reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }

    function loadCustomerProfile() {
        const phone = localStorage.getItem('userPhone') || '<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>';
        const name = localStorage.getItem('userName') || '<?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer') ?>';

        if (!phone) {
            // Not logged in
            window.location.href = 'login.php';
            return;
        }

        if (document.getElementById('profile-name')) document.getElementById('profile-name').innerText = name;
        if (document.getElementById('profile-phone')) document.getElementById('profile-phone').innerText = phone;
        if (document.getElementById('profile-initial')) document.getElementById('profile-initial').innerText = name.charAt(0).toUpperCase();

        if (document.getElementById('cust-input-name')) document.getElementById('cust-input-name').value = name;
        if (document.getElementById('cust-input-phone')) document.getElementById('cust-input-phone').value = phone;
        if (document.getElementById('prof-display-name')) document.getElementById('prof-display-name').innerText = name;
        if (document.getElementById('prof-display-phone')) document.getElementById('prof-display-phone').innerText = phone;

        fetch(`api/customer-data.php?phone=${encodeURIComponent(phone)}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    currentCustomerData = data;
                    if (data.customer) {
                        if (document.getElementById('cust-input-name')) document.getElementById('cust-input-name').value = data.customer.name || name;
                        if (document.getElementById('cust-input-phone')) document.getElementById('cust-input-phone').value = data.customer.phone || phone;
                        if (document.getElementById('cust-input-email')) document.getElementById('cust-input-email').value = data.customer.email || '';
                        if (document.getElementById('cust-input-address')) document.getElementById('cust-input-address').value = data.customer.address || '';
                        
                        if (data.customer.profile_photo) {
                            localStorage.setItem('userAvatar', data.customer.profile_photo);
                            if (document.getElementById('large-avatar-box')) {
                                document.getElementById('large-avatar-box').innerHTML = `
                                    <img src="${data.customer.profile_photo}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <div class="avatar-edit-overlay" onclick="document.getElementById('profile-photo-file').click()">Change</div>
                                `;
                            }
                            if (document.getElementById('banner-avatar-wrap')) {
                                document.getElementById('banner-avatar-wrap').innerHTML = `
                                    <img src="${data.customer.profile_photo}" style="width: 100%; height: 100%; object-fit: cover;">
                                `;
                            }
                            const navAvatar = document.getElementById('profile-avatar');
                            if (navAvatar) {
                                navAvatar.innerHTML = `<img src="${data.customer.profile_photo}" style="width: 100%; height: 100%; object-fit: cover;">`;
                            }
                        }
                    }

                    const orders = data.orders || [];
                    const lists = data.custom_lists || [];
                    const invoices = data.invoices || [];
                    
                    window.currentCustomerOrders = orders;
                    window.currentCustomerLists = lists;
                    window.currentCustomerInvoices = invoices;

                    renderOrders(orders);
                    renderInvoices(invoices);
                    renderCustomLists(lists);

                    // Badges
                    const bOrd = document.getElementById('badge-order');
                    if (bOrd) {
                        bOrd.innerText = orders.length;
                        bOrd.style.display = orders.length > 0 ? 'inline-block' : 'none';
                    }

                    const bInv = document.getElementById('badge-invoice');
                    if (bInv) {
                        bInv.innerText = invoices.length;
                        bInv.style.display = invoices.length > 0 ? 'inline-block' : 'none';
                    }

                    const bList = document.getElementById('badge-list');
                    if (bList) {
                        bList.innerText = lists.length;
                        bList.style.display = lists.length > 0 ? 'inline-block' : 'none';
                    }
                } else {
                    renderOrders([]);
                    renderInvoices([]);
                    renderCustomLists([]);
                }
            })
            .catch(err => {
                console.error("Customer data error:", err);
                renderOrders([]);
                renderInvoices([]);
                renderCustomLists([]);
            });
    }

    function enableProfileEdit() {
        if (document.getElementById('cust-input-name')) document.getElementById('cust-input-name').disabled = false;
        if (document.getElementById('cust-input-email')) document.getElementById('cust-input-email').disabled = false;
        if (document.getElementById('cust-input-address')) document.getElementById('cust-input-address').disabled = false;
        if (document.getElementById('edit-profile-btn')) document.getElementById('edit-profile-btn').style.display = 'none';
        if (document.getElementById('profile-edit-actions')) document.getElementById('profile-edit-actions').style.display = 'flex';
    }

    function cancelProfileEdit() {
        newAvatarBase64 = null;
        if (document.getElementById('cust-input-name')) document.getElementById('cust-input-name').disabled = true;
        if (document.getElementById('cust-input-email')) document.getElementById('cust-input-email').disabled = true;
        if (document.getElementById('cust-input-address')) document.getElementById('cust-input-address').disabled = true;
        if (document.getElementById('edit-profile-btn')) document.getElementById('edit-profile-btn').style.display = 'flex';
        if (document.getElementById('profile-edit-actions')) document.getElementById('profile-edit-actions').style.display = 'none';
        loadCustomerProfile();
    }

    function handleCustomerPhotoChange(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = new Image();
            img.onload = function() {
                const maxDim = 400;
                let w = img.width;
                let h = img.height;
                if (w > maxDim || h > maxDim) {
                    const ratio = Math.min(maxDim / w, maxDim / h);
                    w = Math.round(w * ratio);
                    h = Math.round(h * ratio);
                }
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);
                newAvatarBase64 = canvas.toDataURL('image/jpeg', 0.85);

                document.getElementById('large-avatar-box').innerHTML = `
                    <img src="${newAvatarBase64}" style="width: 100%; height: 100%; object-fit: cover;">
                    <div class="avatar-edit-overlay" onclick="document.getElementById('profile-photo-file').click()">Change</div>
                `;
                enableProfileEdit();
            };
            img.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }

    function saveCustomerProfile(e) {
        e.preventDefault();
        const addressEl = document.getElementById('cust-input-address');
        const payload = {
            name: document.getElementById('cust-input-name').value.trim(),
            phone: document.getElementById('cust-input-phone').value.trim(),
            email: document.getElementById('cust-input-email') ? document.getElementById('cust-input-email').value.trim() : '',
            address: addressEl ? addressEl.value.trim() : (localStorage.getItem('userLocation') || ''),
            profile_photo: newAvatarBase64
        };

        fetch('api/customer-data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                showToast("Profile updated successfully!", "success");
                localStorage.setItem('userName', payload.name);
                if (payload.address) localStorage.setItem('userLocation', payload.address);
                if (res.user && res.user.profile_photo) {
                    localStorage.setItem('userAvatar', res.user.profile_photo);
                    const navAvatar = document.getElementById('profile-avatar');
                    if (navAvatar) {
                        navAvatar.innerHTML = `<img src="${res.user.profile_photo}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    }
                }
                cancelProfileEdit();
            } else {
                alert(res.message || "Failed to update profile");
            }
        });
    }

    function renderOrders(orders) {
        const container = document.getElementById('orders-list-container');
        if (orders.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 50px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b;" class="dynamic-lang" data-en="No active orders. To order from a list, visit the Lists tab." data-bn="কোনো চলমান অর্ডার নেই। ফর্দ দিয়ে অর্ডার করতে &quot;ফর্দসমূহ&quot; ট্যাবে যান।">
                    ${isBangla ? 'কোনো চলমান অর্ডার নেই। ফর্দ দিয়ে অর্ডার করতে "ফর্দসমূহ" ট্যাবে যান।' : 'No active orders. To order from a list, visit the Lists tab.'}
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        orders.forEach(o => {
            const card = document.createElement('div');
            card.className = 'list-item-card';

            let statusEn = o.status || 'Pending';
            let statusBn = o.status || 'পেন্ডিং';
            let badgeBg = '#fef3c7';
            let badgeColor = '#b45309';
            let editBtn = '';
            let actionBtn = '';
            let cancelBtn = '';

            if (o.status === 'pending' || o.status === 'invoice_created') {
                cancelBtn = `
                    <button onclick="cancelOrderById('${o.invoice_number || o.id}')" style="background: white; border: 1.5px solid #fecdd3; padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #e11d48; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Cancel Order">
                        <i class="fa-solid fa-xmark"></i> <span class="dynamic-lang" data-en="Cancel" data-bn="বাতিল">${isBangla ? 'বাতিল' : 'Cancel'}</span>
                    </button>
                `;
            }

            if (o.status === 'invoice_created') {
                if (o.order_type === 'website') {
                    statusEn = 'Confirmed (Processing)';
                    statusBn = 'কনফার্মড (প্রসেসিং চলছে)';
                    badgeBg = '#e0e7ff';
                    badgeColor = '#4338ca';
                } else {
                    statusEn = 'Invoice Created (Confirm)';
                    statusBn = 'ইনভয়েস তৈরি হয়েছে (কনফার্ম করুন)';
                    badgeBg = '#dcfce7';
                    badgeColor = '#15803d';
                    actionBtn = `
                        <button onclick="confirmInvoiceById('${o.invoice_number || o.id}')" style="background: #00c675; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(0, 198, 117, 0.3);">
                            <i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm" data-bn="কনফার্ম করুন">${isBangla ? 'কনফার্ম করুন' : 'Confirm'}</span>
                        </button>
                    `;
                }
            } else if (o.status === 'customer_confirmed' || o.status === 'processing') {
                statusEn = 'Confirmed (Processing)';
                statusBn = 'কনফার্মড (প্রসেসিং চলছে)';
                badgeBg = '#e0e7ff';
                badgeColor = '#4338ca';
            } else if (o.status === 'pending') {
                if (o.order_type === 'website') {
                    statusEn = 'Order Reviewing';
                    statusBn = 'অর্ডার রিভিউ চলছে';
                } else {
                    statusEn = 'Awaiting Rider Pricing';
                    statusBn = 'রাইডার দরদামের অপেক্ষায়';
                }
                badgeBg = '#fef3c7';
                badgeColor = '#b45309';
                editBtn = `
                    <button onclick="editInvoiceById('${o.invoice_number || o.id}')" style="background: white; border: 1.5px solid #cbd5e1; padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #0284c7; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Edit Order">
                        <i class="fa-solid fa-pen-to-square"></i> <span class="dynamic-lang" data-en="Edit" data-bn="এডিট">${isBangla ? 'এডিট' : 'Edit'}</span>
                    </button>
                `;
            } else if (o.status === 'delivering') {
                statusEn = 'Delivering';
                statusBn = 'ডেলিভারি নিয়ে আসছে';
                badgeBg = '#e0f2fe';
                badgeColor = '#0369a1';
            } else if (o.status === 'completed') {
                statusEn = 'Completed';
                statusBn = 'সম্পন্ন';
                badgeBg = '#dcfce7';
                badgeColor = '#15803d';
            } else if (o.status === 'cancelled') {
                statusEn = 'Cancelled';
                statusBn = 'বাতিল';
                badgeBg = '#fee2e2';
                badgeColor = '#991b1b';
            }

            let typeEn = 'Order';
            let typeBn = 'অর্ডার';
            if (o.order_type === 'text_list') {
                typeEn = 'Grocery List Order';
                typeBn = 'ফর্দ অর্ডার';
            } else if (o.order_type === 'uploaded') {
                typeEn = 'Photo List Order';
                typeBn = 'ছবি ফর্দ অর্ডার';
            } else if (o.order_type === 'website') {
                typeEn = 'Online Order';
                typeBn = 'অনলাইন অর্ডার';
            } else if (o.order_type) {
                typeEn = o.order_type;
                typeBn = o.order_type;
            }

            const pMethod = (o.payment_method || 'cod').toLowerCase();
            const pStatus = (o.payment_status || 'pending').toLowerCase();
            let payBadgeHtml = '';
            if (pMethod === 'bkash') {
                payBadgeHtml = `<span style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-mobile-screen"></i> <span class="dynamic-lang" data-en="bKash" data-bn="বিকাশ">${isBangla ? 'বিকাশ' : 'bKash'}</span>${o.transaction_id ? ` (Trx: ${o.transaction_id})` : ''}</span>`;
            } else if (pMethod === 'nagad') {
                payBadgeHtml = `<span style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-mobile-screen"></i> <span class="dynamic-lang" data-en="Nagad" data-bn="নগদ">${isBangla ? 'নগদ' : 'Nagad'}</span>${o.transaction_id ? ` (Trx: ${o.transaction_id})` : ''}</span>`;
            } else {
                payBadgeHtml = `<span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-hand-holding-dollar"></i> <span class="dynamic-lang" data-en="Cash on Delivery" data-bn="ক্যাশ অন ডেলিভারি">${isBangla ? 'ক্যাশ অন ডেলিভারি' : 'Cash on Delivery'}</span></span>`;
            }
            const payStatusHtml = pStatus === 'paid'
                ? `<span style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px;"><span class="dynamic-lang" data-en="✅ Paid" data-bn="✅ পরিশোধিত">${isBangla ? '✅ পরিশোধিত' : '✅ Paid'}</span></span>`
                : `<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px;"><span class="dynamic-lang" data-en="⏳ Pending" data-bn="⏳ বকেয়া">${isBangla ? '⏳ বকেয়া' : '⏳ Pending'}</span></span>`;

            card.innerHTML = `
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                        <span style="font-weight: 900; font-size: 16px; color: #0f172a;">${o.id}</span>
                        <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 3px 10px; border-radius: 9999px; font-size: 12px; font-weight: 800;"><span class="dynamic-lang" data-en="${statusEn}" data-bn="${statusBn}">${isBangla ? statusBn : statusEn}</span></span>
                        <span style="font-size: 12px; color: #64748b;">(<span class="dynamic-lang" data-en="${typeEn}" data-bn="${typeBn}">${isBangla ? typeBn : typeEn}</span>)</span>
                    </div>
                    <div style="font-size: 13px; color: #64748b;">
                        <span><i class="fa-regular fa-clock" style="margin-right: 4px;"></i> ${o.date}</span>
                    </div>
                    <div style="font-size: 13px; color: #334155; margin-top: 4px;">
                        <span><i class="fa-solid fa-location-dot" style="color: var(--primary-color); margin-right: 4px;"></i> ${o.customer_address || (isBangla ? 'ঠিকানা দেওয়া হয়েছে' : 'Address provided')}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                        ${payBadgeHtml}
                        ${payStatusHtml}
                    </div>
                    ${o.rider_name ? `
                    <div style="margin-top: 6px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #1e40af;">
                        <span><i class="fa-solid fa-motorcycle" style="color: #2563eb;"></i> ${isBangla ? 'ডেলিভারি রাইডার: ' : 'Rider: '} <strong>${o.rider_name}</strong></span>
                        ${o.rider_phone ? `<a href="tel:${o.rider_phone}" style="background: #2563eb; color: white; padding: 2px 8px; border-radius: 4px; text-decoration: none; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-phone"></i> ${o.rider_phone}</a>` : ''}
                    </div>` : ''}
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    ${o.total_amount > 0 ? `
                    <div style="font-size: 18px; font-weight: 900; color: #059669; margin-right: 4px;">৳${o.total_amount}</div>` : ''}
                    ${editBtn}
                    ${actionBtn}
                    ${cancelBtn}
                    <button onclick="viewOrderInvoiceById('${o.id}')" style="background: white; border: 1.5px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #1e293b; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-file-invoice"></i> <span class="dynamic-lang" data-en="View Details" data-bn="বিবরণ দেখুন">${isBangla ? 'বিবরণ দেখুন' : 'View Details'}</span>
                    </button>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function renderInvoices(invoices) {
        const container = document.getElementById('invoices-list-container');
        if (invoices.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 50px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b;" class="dynamic-lang" data-en="No invoices found." data-bn="কোনো ইনভয়েস পাওয়া যায়নি।">
                    ${isBangla ? 'কোনো ইনভয়েস পাওয়া যায়নি।' : 'No invoices found.'}
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        invoices.forEach(inv => {
            const card = document.createElement('div');
            card.className = 'list-item-card';

            let statusEn = 'Pending';
            let statusBn = 'পেন্ডিং';
            let badgeBg = '#fef3c7';
            let badgeColor = '#92400e';
            let actionBtn = '';

            if (inv.status === 'pending') {
                statusEn = 'Order Reviewing';
                statusBn = 'অর্ডার রিভিউ চলছে';
                badgeBg = '#fef3c7';
                badgeColor = '#92400e';
            } else if (inv.status === 'invoice_created') {
                if (inv.order_type === 'website') {
                    statusEn = 'Confirmed (Processing)';
                    statusBn = 'কনফার্মড (প্রসেসিং চলছে)';
                    badgeBg = '#e0e7ff';
                    badgeColor = '#4338ca';
                } else {
                    statusEn = 'Invoice Created (Confirm)';
                    statusBn = 'ইনভয়েস তৈরি হয়েছে (কনফার্ম করুন)';
                    badgeBg = '#dcfce7';
                    badgeColor = '#15803d';
                    actionBtn = `
                        <button onclick="confirmInvoiceById('${inv.id}')" style="background: #00c675; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(0, 198, 117, 0.3);">
                            <i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm" data-bn="কনফার্ম করুন">${isBangla ? 'কনফার্ম করুন' : 'Confirm'}</span>
                        </button>
                    `;
                }
            } else if (inv.status === 'customer_confirmed' || inv.status === 'processing') {
                statusEn = 'Confirmed (Processing)';
                statusBn = 'কনফার্মড (প্রসেসিং চলছে)';
                badgeBg = '#e0e7ff';
                badgeColor = '#4338ca';
            } else if (inv.status === 'delivering') {
                statusEn = 'Delivering';
                statusBn = 'ডেলিভারি নিয়ে আসছে';
                badgeBg = '#e0f2fe';
                badgeColor = '#0369a1';
            } else if (inv.status === 'completed') {
                statusEn = 'Completed';
                statusBn = 'সম্পন্ন';
                badgeBg = '#dcfce7';
                badgeColor = '#15803d';
            } else if (inv.status === 'cancelled') {
                statusEn = 'Cancelled';
                statusBn = 'বাতিল';
                badgeBg = '#fee2e2';
                badgeColor = '#991b1b';
            }

            const isPending = (inv.status === 'pending');
            const canCancelInv = (inv.status === 'pending' || inv.status === 'invoice_created');
            const editBtn = isPending ? `
                <button onclick="editInvoiceById('${inv.id}')" style="background: white; border: 1.5px solid #cbd5e1; padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #0284c7; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Edit Invoice">
                    <i class="fa-solid fa-pen-to-square"></i> <span class="dynamic-lang" data-en="Edit" data-bn="এডিট">${isBangla ? 'এডিট' : 'Edit'}</span>
                </button>
            ` : '';
            const cancelBtn = canCancelInv ? `
                <button onclick="cancelOrderById('${inv.id}')" style="background: white; border: 1.5px solid #fecdd3; padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #e11d48; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Cancel Order">
                    <i class="fa-solid fa-xmark"></i> <span class="dynamic-lang" data-en="Cancel" data-bn="বাতিল">${isBangla ? 'বাতিল' : 'Cancel'}</span>
                </button>
            ` : '';

            const custAddress = inv.customer_address || localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || (isBangla ? 'ঠিকানা নির্দিষ্ট নয়' : 'Address not specified');

            const pMethod = (inv.payment_method || 'cod').toLowerCase();
            const pStatus = (inv.payment_status || 'pending').toLowerCase();
            let payBadgeHtml = '';
            if (pMethod === 'bkash') {
                payBadgeHtml = `<span style="background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-mobile-screen"></i> <span class="dynamic-lang" data-en="bKash" data-bn="বিকাশ">${isBangla ? 'বিকাশ' : 'bKash'}</span>${inv.transaction_id ? ` (Trx: ${inv.transaction_id})` : ''}</span>`;
            } else if (pMethod === 'nagad') {
                payBadgeHtml = `<span style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-mobile-screen"></i> <span class="dynamic-lang" data-en="Nagad" data-bn="নগদ">${isBangla ? 'নগদ' : 'Nagad'}</span>${inv.transaction_id ? ` (Trx: ${inv.transaction_id})` : ''}</span>`;
            } else {
                payBadgeHtml = `<span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-hand-holding-dollar"></i> <span class="dynamic-lang" data-en="Cash on Delivery" data-bn="ক্যাশ অন ডেলিভারি">${isBangla ? 'ক্যাশ অন ডেলিভারি' : 'Cash on Delivery'}</span></span>`;
            }
            const payStatusHtml = pStatus === 'paid'
                ? `<span style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px;"><span class="dynamic-lang" data-en="✅ Paid" data-bn="✅ পরিশোধিত">${isBangla ? '✅ পরিশোধিত' : '✅ Paid'}</span></span>`
                : `<span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 2px 7px; border-radius: 6px; font-weight: 800; font-size: 11px;"><span class="dynamic-lang" data-en="⏳ Pending" data-bn="⏳ বকেয়া">${isBangla ? '⏳ বকেয়া' : '⏳ Pending'}</span></span>`;

            card.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; margin-top: 2px;">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                            <span style="font-weight: 900; font-size: 16px; color: #0f172a;">${inv.id}</span>
                            <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 3px 10px; border-radius: 9999px; font-size: 11.5px; font-weight: 800; text-transform: uppercase;"><span class="dynamic-lang" data-en="${statusEn}" data-bn="${statusBn}">${isBangla ? statusBn : statusEn}</span></span>
                        </div>
                        <div style="font-size: 12.5px; color: #64748b; margin-bottom: 3px;">
                            <span><i class="fa-regular fa-clock" style="margin-right: 4px;"></i> ${inv.date}</span>
                        </div>
                        <div style="font-size: 13px; color: #334155;">
                            <span><i class="fa-solid fa-location-dot" style="color: #ef4444; margin-right: 4px;"></i> ${custAddress}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                            ${payBadgeHtml}
                            ${payStatusHtml}
                        </div>
                        ${inv.rider_name ? `
                        <div style="margin-top: 6px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #1e40af;">
                            <span><i class="fa-solid fa-motorcycle" style="color: #2563eb;"></i> ${isBangla ? 'ডেলিভারি রাইডার: ' : 'Rider: '} <strong>${inv.rider_name}</strong></span>
                            ${inv.rider_phone ? `<a href="tel:${inv.rider_phone}" style="background: #2563eb; color: white; padding: 2px 8px; border-radius: 4px; text-decoration: none; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-phone"></i> ${inv.rider_phone}</a>` : ''}
                        </div>` : ''}
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="font-size: 19px; font-weight: 900; color: #059669; margin-right: 4px;">৳${inv.total_amount}</div>
                    ${editBtn}
                    ${actionBtn}
                    ${cancelBtn}
                    <button onclick="viewOrderInvoiceById('${inv.id}')" style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;">
                        <span class="dynamic-lang" data-en="View Details" data-bn="বিবরণ দেখুন">${isBangla ? 'বিবরণ দেখুন' : 'View Details'}</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function renderCustomLists(lists) {
        const container = document.getElementById('lists-container');
        if (lists.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 50px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b;" class="dynamic-lang" data-en="No saved grocery lists. Use &quot;Write Grocery List&quot; or &quot;Upload List Photo&quot; to create one!" data-bn="কোনো সংরক্ষিত ফর্দ নেই। ফর্দ লিখতে &quot;লিখে বাজার লিস্ট&quot; বা &quot;ছবির মাধ্যমে লিস্ট&quot; অপশন ব্যবহার করুন!">
                    ${isBangla ? 'কোনো সংরক্ষিত ফর্দ নেই। ফর্দ লিখতে "লিখে বাজার লিস্ট" বা "ছবির মাধ্যমে লিস্ট" অপশন ব্যবহার করুন!' : 'No saved grocery lists. Use "Write Grocery List" or "Upload List Photo" to create one!'}
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        lists.forEach(l => {
            const card = document.createElement('div');
            card.className = 'list-item-card';
            let icon = l.list_type === 'uploaded' ? 'fa-image' : 'fa-list-ol';
            const listCode = l.list_code || `LST-${l.id}`;
            const custAddress = l.customer_address || localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || (isBangla ? 'ঠিকানা নির্দিষ্ট নয়' : 'Address not specified');

            card.innerHTML = `
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    <div>
                        <div style="font-weight: 900; font-size: 15.5px; color: #0f172a;">${listCode}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            <span><i class="fa-regular fa-clock" style="margin-right: 4px;"></i> ${l.date}</span>
                        </div>
                        <div style="font-size: 13px; color: #334155; margin-top: 3px;">
                            <span><i class="fa-solid fa-location-dot" style="color: var(--primary-color); margin-right: 4px;"></i> ${custAddress}</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button onclick="orderCustomListById('${listCode}', '${l.id}')" style="background: #00c675; color: white; border: none; padding: 7px 16px; border-radius: 8px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(0, 198, 117, 0.25);" title="Order from this List">
                        <i class="fa-solid fa-paper-plane"></i> <span class="dynamic-lang" data-en="Order Now" data-bn="অর্ডার করুন">${isBangla ? 'অর্ডার করুন' : 'Order Now'}</span>
                    </button>
                    <button onclick="viewCustomListDetailsById('${l.id}', '${listCode}')" style="background: white; border: 1.5px solid #cbd5e1; padding: 7px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #1e293b; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Preview List">
                        <i class="fa-solid fa-eye" style="color: #64748b;"></i> <span class="dynamic-lang" data-en="View" data-bn="দেখুন">${isBangla ? 'দেখুন' : 'View'}</span>
                    </button>
                    <button onclick="editCustomListById('${listCode}', '${l.id}')" style="background: white; border: 1.5px solid #cbd5e1; padding: 7px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #0284c7; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Edit List">
                        <i class="fa-solid fa-pen-to-square"></i> <span class="dynamic-lang" data-en="Edit" data-bn="এডিট">${isBangla ? 'এডিট' : 'Edit'}</span>
                    </button>
                    <button onclick="deleteCustomListById('${listCode}', '${l.id}')" style="background: white; border: 1.5px solid #fecaca; padding: 7px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; color: #ef4444; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;" title="Delete List">
                        <i class="fa-solid fa-trash"></i> <span class="dynamic-lang" data-en="Delete" data-bn="মুছুন">${isBangla ? 'মুছুন' : 'Delete'}</span>
                    </button>
                </div>
            `;
            container.appendChild(card);
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
        if (rawContent.includes('<tr')) {
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
                        // SL, Item, Price, Qty, Total
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
                        // SL, Item, Spec, Qty
                        itemText = tds[1].innerText.trim();
                        spec = tds[2].innerText.trim();
                        qty = tds[3].innerText.trim() || '1';
                    } else if (tds.length === 3) {
                        // SL, Item (spec), Qty
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
                        // Item, Qty
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

        // 3. Try plain text line by line
        const lines = rawContent.split('\n').map(l => l.trim()).filter(l => l.length > 0 && !l.includes('<div') && !l.includes('</div') && !l.includes('<p') && !l.includes('</p') && !l.includes('<table') && !l.includes('</table>'));
        lines.forEach(line => {
            const clean = line.replace(/^\d+[\.\-\)]\s*/, '').trim();
            if (clean) {
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
                        const qtyMatch = rest.match(/^[-–:|]\s*(.+)$/);
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

                items.push({ item: itemText, spec: spec, qty: qty, price: '', total: '' });
            }
        });

        return items;
    }

    function editCustomList(list, listCode) {
        if (!list) return;

        let isUploaded = list.list_type === 'uploaded' || list.order_type === 'uploaded' || (list.image_urls && list.image_urls.length > 0) || (list.images && list.images.length > 0);
        let imgs = list.image_urls || list.images || [];
        if (typeof imgs === 'string') {
            try { imgs = JSON.parse(imgs); } catch(e) { imgs = []; }
        }
        if (!Array.isArray(imgs)) imgs = [];

        const contentStr = list.items_summary || list.html_content || list.raw_text || list.content_text || '';
        if (imgs.length === 0 && contentStr && (contentStr.includes('<img') || contentStr.includes('uploads/lists/'))) {
            const temp = document.createElement('div');
            temp.innerHTML = contentStr;
            const foundImgs = temp.querySelectorAll('img');
            foundImgs.forEach(imgEl => {
                const src = imgEl.getAttribute('src');
                if (src && !src.includes('logo') && !src.includes('Watermark') && !src.includes('avatar') && !src.includes('icon')) {
                    imgs.push(src);
                }
            });
            if (imgs.length > 0) {
                isUploaded = true;
            }
        }

        const isOrder = (list.order_type === 'uploaded' || list.order_type === 'text_list' || (list.id && String(list.id).startsWith('ORD-')) || (listCode && String(listCode).startsWith('ORD-')));

        if (isUploaded) {
            if (imgs.length > 0) {
                localStorage.setItem('uploadListDraft', JSON.stringify(imgs));
            }
            if (isOrder) {
                localStorage.setItem('editing_order_code', listCode || list.order_number || list.id);
                localStorage.setItem('editing_order_id', list.db_id || list.id || '');
                localStorage.removeItem('editing_list_code');
                localStorage.removeItem('editing_list_id');
            } else {
                localStorage.setItem('editing_list_code', listCode || list.list_code || list.id);
                localStorage.setItem('editing_list_id', list.id || '');
                localStorage.removeItem('editing_order_code');
                localStorage.removeItem('editing_order_id');
            }
            window.location.href = 'upload_list.php?edit=1';
            return;
        }

        const items = extractListItemsFromContent(contentStr);

        if (items.length > 0) {
            localStorage.setItem('editListDraft', JSON.stringify(items));
        }

        if (isOrder) {
            localStorage.setItem('editing_order_code', listCode || list.order_number || list.id);
            localStorage.setItem('editing_order_id', list.db_id || list.id || '');
            localStorage.removeItem('editing_list_code');
            localStorage.removeItem('editing_list_id');
        } else {
            localStorage.setItem('editing_list_code', listCode || list.list_code || list.id);
            localStorage.setItem('editing_list_id', list.id || '');
            localStorage.removeItem('editing_order_code');
            localStorage.removeItem('editing_order_id');
        }

        window.location.href = 'write_list.php?edit=1';
    }

    function deleteCustomList(list, listCode) {
        const msg = isBangla 
            ? `আপনি কি নিশ্চিত যে তালিকা <strong>"${listCode}"</strong> মুছে ফেলতে চান? এটি পুনরায় উদ্ধার করা যাবে না।` 
            : `Are you sure you want to delete list <strong>"${listCode}"</strong>? This cannot be undone.`;

        showConfirm(msg, function() {
            fetch('api/customer-data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_list',
                    list_id: list.id || list.order_number,
                    phone: localStorage.getItem('userPhone') || '01919668960'
                })
            })
            .then(res => res.json())
            .then(data => {
                try {
                    let localLists = JSON.parse(localStorage.getItem('userLists') || '[]');
                    localLists = localLists.filter(x => x.id !== list.id && x.id !== listCode);
                    localStorage.setItem('userLists', JSON.stringify(localLists));
                } catch(e) {}

                showToast(isBangla ? "তালিকা মুছে ফেলা হয়েছে!" : "List deleted successfully!", "success");
                loadCustomerProfile();
            })
            .catch(err => {
                console.error(err);
                showToast("Failed to delete list", "error");
            });
        }, {
            title: isBangla ? "তালিকা মুছবেন?" : "Delete List?",
            confirmText: isBangla ? "হ্যাঁ, মুছে ফেলুন" : "Yes, Delete",
            cancelText: isBangla ? "বাতিল" : "Cancel",
            type: "danger",
            icon: "fa-solid fa-trash-can"
        });
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
                <!-- Top Center Header -->
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

                <!-- Customer Details & Order Metadata Row -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; font-size: 13.5px; line-height: 1.6;">
                    <div style="max-width: 58%; text-align: left;">
                        <div><strong style="color: #0f172a;">Name:</strong> <span style="color: #334155; font-weight: 600;">${name}</span></div>
                        <div><strong style="color: #0f172a;">Phone:</strong> <span style="color: #334155; font-weight: 600;">${phone}</span></div>
                        <div><strong style="color: #0f172a;">Location:</strong> <span style="color: #334155; font-weight: 600;">${location}</span></div>
                    </div>
                    <div style="text-align: right;">
                        <div><strong style="color: #0f172a;">${orderNo.startsWith('LST-') ? 'Document:' : 'Order Type:'}</strong> <span style="color: #00c675; font-weight: 800;">${orderNo.startsWith('LST-') ? 'Custom Grocery List' : (orderNo.startsWith('ORD-') ? 'Grocery List Order' : 'Online Order')}</span></div>
                        <div><strong style="color: #0f172a;">${orderNo.startsWith('LST-') ? 'List Code:' : (orderNo.startsWith('ORD-') ? 'Order No:' : 'Invoice No:')}</strong> <span style="color: #00c675; font-weight: 800;">${orderNo}</span></div>
                        <div><strong style="color: #0f172a;">Date & Time:</strong> <span style="color: #334155; font-weight: 600;">${dateTime}</span></div>
                        <div><strong style="color: #0f172a;">Page:</strong> <span style="color: #334155; font-weight: 600;">${pageIndex + 1} of ${totalPages}</span></div>
                    </div>
                </div>
            `;
        } else {
            const labelStr = orderNo.startsWith('LST-') ? 'List Code:' : (orderNo.startsWith('ORD-') ? 'Order No:' : 'Invoice No:');
            headerBlock = `
                <!-- Minimal Header for Page 2+ -->
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
            <div class="a4-page" style="background: white; width: 100%; max-width: 800px; height: 1123px; min-height: 1123px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 45px 50px 35px 50px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; font-family: 'Outfit', sans-serif; overflow: hidden; align-self: stretch;">
                
                <!-- Faded Full Page Center Watermark -->
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.055; pointer-events: none; text-align: center; width: 100%; z-index: 0;">
                    <img src="assets/bazarkinundotcom_logo.png" alt="Watermark" style="width: 320px; height: 320px; object-fit: contain; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                    <img src="assets/bazarkinundotcom_logo2.png" alt="Watermark Logo 2" style="width: 340px; max-width: 85%; height: auto; object-fit: contain; display: block; margin-left: auto; margin-right: auto;">
                </div>

                <div style="position: relative; z-index: 2; flex: 1;">
                    ${headerBlock}

                    <!-- Items Content -->
                    <div style="position: relative; margin-bottom: 20px; z-index: 2;">
                        ${contentHtml}
                    </div>
                </div>

                <!-- Bottom Footer with Dashed Line -->
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

    function generateA4PagesHtml({ name, phone, location, orderNo, dateTime, parsedData, footerExtra = '' }) {
        if (!parsedData || !parsedData.items || parsedData.items.length === 0) {
            return generateSingleA4PageHtml({
                name, phone, location, orderNo, dateTime,
                contentHtml: '<p style="text-align: center; color: #64748b; padding: 30px; font-size: 14px;">No items found in this list.</p>',
                pageIndex: 0,
                totalPages: 1,
                footerExtra
            });
        }

        // If images -> 1 image per page
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

        // If items rows -> Chunking
        const items = parsedData.items;
        const pageChunks = [];
        let idx = 0;

        while (idx < items.length) {
            // First page holds max 14 rows, subsequent pages hold max 20 rows
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

    let currentModalItem = null;
    let currentModalCode = '';
    let currentModalType = 'list';

    function handleModalEdit() {
        if (!currentModalItem) return;
        document.getElementById('invoice-view-modal').style.display = 'none';
        if (currentModalType === 'list') {
            editCustomList(currentModalItem, currentModalCode);
        } else {
            const invId = currentModalItem.invoice_number || currentModalItem.id || currentModalItem.order_number || currentModalCode;
            editInvoiceById(invId);
        }
    }

    function handleModalDelete() {
        if (!currentModalItem) return;
        const itemToDelete = currentModalItem;
        const codeToDelete = currentModalCode || itemToDelete.id || 'Item';
        const type = currentModalType;

        if (type === 'list') {
            document.getElementById('invoice-view-modal').style.display = 'none';
            deleteCustomList(itemToDelete, codeToDelete);
        } else {
            showToast(isBangla ? "ইনভয়েস বা অফিসিয়াল অর্ডার মুছে ফেলা সম্ভব নয়।" : "Invoices cannot be deleted.", "warning");
        }
    }

    function orderCustomListById(listCode, listId) {
        const address = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || '').trim();
        if (!address || (typeof isValidLocationString === 'function' && !isValidLocationString(address))) {
            showToast(isBangla ? "অনুগ্রহ করে প্রথমে আপনার ডেলিভারি ঠিকানা যুক্ত করুন।" : "Please add your delivery address first.", "error");
            if (typeof openLocationModal === 'function') {
                openLocationModal();
            }
            return;
        }

        const list = (window.currentCustomerLists || []).find(x => x.id == listId || x.list_code === listCode || x.id === listCode);
        const msg = isBangla
            ? `আপনি কি নিশ্চিত যে তালিকা <strong>"${listCode}"</strong> দিয়ে এখন অর্ডার করতে চান?<br><small style="color: #64748b;">(লিস্টটি ড্রাফট থেকে সরাসরি অর্ডারে জমা হবে এবং রাইডার দরদাম করবেন)</small>`
            : `Are you sure you want to place an order from list <strong>"${listCode}"</strong>?`;

        showConfirm(msg, function() {
            const phone = localStorage.getItem('userPhone') || '<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>';
            const name = localStorage.getItem('userName') || '<?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer') ?>';

            fetch('api/submit-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'order_list',
                    list_id: listId,
                    list_code: listCode,
                    customer_phone: phone,
                    customer_name: name,
                    customer_address: address,
                    html_content: list ? list.raw_text : '',
                    images: list ? list.image_urls : [],
                    order_type: list ? list.list_type : 'text_list'
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message || (isBangla ? "অর্ডার সম্পন্ন হয়েছে!" : "Order placed successfully!"), "success");
                    loadCustomerProfile();
                    switchProfileTab('order');
                } else {
                    alert(res.message || "Failed to place order.");
                }
            })
            .catch(err => {
                console.error(err);
                showToast("Server error placing order", "error");
            });
        }, {
            title: isBangla ? "অর্ডার নিশ্চিতকরণ" : "Confirm Order",
            confirmText: isBangla ? "হ্যাঁ, অর্ডার করুন" : "Yes, Place Order",
            cancelText: isBangla ? "বাতিল" : "Cancel",
            type: "success",
            icon: "fa-solid fa-paper-plane"
        });
    }

    function confirmInvoiceById(invId) {
        const msg = isBangla
            ? `আপনি কি ইনভয়েস <strong>"${invId}"</strong> এর মূল্য ও পণ্যের বিবরণ যাচাই করে কনফার্ম করতে চান?`
            : `Do you want to confirm invoice <strong>"${invId}"</strong>?`;

        showConfirm(msg, function() {
            fetch('api/submit-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'confirm_invoice',
                    order_id: invId,
                    customer_phone: localStorage.getItem('userPhone') || '<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>',
                    customer_name: localStorage.getItem('userName') || '<?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer') ?>'
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message || "ইনভয়েস কনফার্ম করা হয়েছে!", "success");
                    document.getElementById('invoice-view-modal').style.display = 'none';
                    loadCustomerProfile();
                    switchProfileTab('order');
                } else {
                    alert(res.message || "Could not confirm invoice");
                }
            })
            .catch(err => {
                console.error(err);
                showToast("Server error confirming invoice", "error");
            });
        }, {
            title: isBangla ? "ইনভয়েস কনফার্ম করবেন?" : "Confirm Invoice?",
            confirmText: isBangla ? "হ্যাঁ, কনফার্ম করুন" : "Yes, Confirm",
            cancelText: isBangla ? "বাতিল" : "Cancel",
            type: "success",
            icon: "fa-solid fa-circle-check"
        });
    }

    function cancelOrderById(orderId) {
        if (!orderId) return;
        const msg = isBangla
            ? `আপনি কি নিশ্চিত যে <strong>"${orderId}"</strong> অর্ডারটি বাতিল করতে চান?`
            : `Are you sure you want to cancel order <strong>"${orderId}"</strong>?`;

        showConfirm(msg, function() {
            const phone = localStorage.getItem('userPhone') || '<?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?>';
            const name = localStorage.getItem('userName') || '<?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer') ?>';

            fetch('api/submit-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'cancel_order',
                    order_id: orderId,
                    customer_phone: phone,
                    customer_name: name,
                    reason: 'Cancelled by customer'
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message || (isBangla ? "অর্ডারটি সফলভাবে বাতিল করা হয়েছে!" : "Order cancelled successfully!"), "success");
                    document.getElementById('invoice-view-modal').style.display = 'none';
                    loadCustomerProfile();
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

    function handleModalOrderNow() {
        if (!currentModalItem) return;
        const listCode = currentModalCode || currentModalItem.list_code || (currentModalItem.id ? `LST-${currentModalItem.id}` : '');
        const listId = currentModalItem.id || currentModalItem.db_id;
        document.getElementById('invoice-view-modal').style.display = 'none';
        orderCustomListById(listCode, listId);
    }

    function handleModalConfirm() {
        if (!currentModalItem) return;
        const invId = currentModalItem.invoice_number || currentModalItem.id || currentModalCode;
        confirmInvoiceById(invId);
    }

    function handleModalCancel() {
        if (!currentModalItem) return;
        const invId = currentModalItem.invoice_number || currentModalItem.order_number || currentModalItem.id || currentModalCode;
        cancelOrderById(invId);
    }

    function handleModalEdit() {
        if (!currentModalItem) return;
        document.getElementById('invoice-view-modal').style.display = 'none';
        if (currentModalType === 'list') {
            const listCode = currentModalCode || currentModalItem.list_code || (currentModalItem.id ? `LST-${currentModalItem.id}` : '');
            editCustomList(currentModalItem, listCode);
        } else {
            const invId = currentModalItem.invoice_number || currentModalItem.order_number || currentModalItem.id || currentModalCode;
            editInvoiceById(invId);
        }
    }

    function handleModalDelete() {
        if (!currentModalItem) return;
        document.getElementById('invoice-view-modal').style.display = 'none';
        if (currentModalType === 'list') {
            const listCode = currentModalCode || currentModalItem.list_code || (currentModalItem.id ? `LST-${currentModalItem.id}` : '');
            deleteCustomList(currentModalItem, listCode);
        }
    }

    function viewOrderInvoiceById(orderId) {
        const allItems = [...(window.currentCustomerOrders || []), ...(window.currentCustomerInvoices || [])];
        const order = allItems.find(x => x.id === orderId || x.db_id == orderId || x.order_number === orderId || x.invoice_number === orderId);
        if (order) {
            viewOrderInvoice(order);
        }
    }

    function viewCustomListDetailsById(id, listCode) {
        const list = (window.currentCustomerLists || []).find(x => x.id == id || x.order_number == id || x.list_code === listCode || x.id === listCode);
        if (list) {
            viewCustomListDetails(list, listCode);
        }
    }

    function editCustomListById(listCode, id = '') {
        const list = (window.currentCustomerLists || []).find(x => (id && (x.id == id || x.order_number == id)) || x.list_code === listCode || x.id === listCode);
        if (list) {
            editCustomList(list, listCode);
        } else {
            window.location.href = 'write_list.php';
        }
    }

    function deleteCustomListById(listCode, id = '') {
        const list = (window.currentCustomerLists || []).find(x => (id && (x.id == id || x.order_number == id)) || x.list_code === listCode || x.id === listCode);
        if (list) {
            deleteCustomList(list, listCode);
        }
    }

    function editInvoiceById(invId) {
        const allItems = [...(window.currentCustomerInvoices || []), ...(window.currentCustomerOrders || [])];
        let inv = allItems.find(x => x.id === invId || x.db_id == invId || x.order_number === invId || x.invoice_number === invId);
        if (!inv && currentModalItem && (currentModalItem.id === invId || currentModalItem.invoice_number === invId || currentModalItem.order_number === invId || currentModalCode === invId)) {
            inv = currentModalItem;
        }
        if (!inv) return;

        if (inv.status !== 'pending') {
            showToast(isBangla ? "পেন্ডিং ছাড়া অন্য স্ট্যাটাসের ইনভয়েস এডিট করা সম্ভব নয়।" : "Only pending invoices can be edited.", "warning");
            return;
        }

        if (inv.order_type === 'website') {
            showToast(isBangla ? "অর্ডারটি পুনরায় এডিটের জন্য ওপেন করা হচ্ছে..." : "Opening invoice for editing...", "info");
            const parsed = parseListItems(inv.items_summary || inv.html_content || '');
            
            const proceedWithCatalog = () => {
                let catalog = [];
                try {
                    catalog = JSON.parse(sessionStorage.getItem('storeProductsCatalog') || '[]');
                } catch(e) {}

                const findProduct = (name) => {
                    if (!name) return null;
                    const clean = name.trim().toLowerCase();
                    return catalog.find(p => 
                        (p.title && p.title.trim().toLowerCase() === clean) ||
                        (p.title_bn && p.title_bn.trim().toLowerCase() === clean) ||
                        (p.title && clean.includes(p.title.trim().toLowerCase())) ||
                        (p.title_bn && clean.includes(p.title_bn.trim().toLowerCase())) ||
                        (p.title && p.title.trim().toLowerCase().includes(clean)) ||
                        (p.title_bn && p.title_bn.trim().toLowerCase().includes(clean))
                    );
                };

                let cartItems = [];
                if (parsed && parsed.items && parsed.items.length > 0) {
                    cartItems = parsed.items
                        .filter(it => {
                            // Strictly filter out auto-generated free gift items from the base cart
                            const rawName = (it.item || '').toLowerCase();
                            if (rawName.includes('🎁') || rawName.includes('free gift') || rawName.includes('ফ্রি উপহার') || rawName.includes('স্পেশাল গিফট') || rawName.includes('combo free') || rawName.includes('bogo free')) {
                                return false;
                            }
                            if ((parseFloat(it.price) === 0 || it.price === '' || it.price === '0') && (rawName.includes('free') || rawName.includes('gift') || rawName.includes('উপহার') || rawName.includes('ফ্রি'))) {
                                return false;
                            }
                            return true;
                        })
                        .map(it => {
                            let cleanItemName = (it.item || '').replace(/\[.*?\]/g, '').trim();
                            const pInfo = findProduct(cleanItemName) || ((typeof window.getProductInfo === 'function') ? window.getProductInfo(cleanItemName) : null);
                            const realImg = (pInfo && pInfo.image) ? pInfo.image : (it.img || it.image || 'assets/bazarkinundotcom_logo.png');
                            return {
                                name: (pInfo && pInfo.title) ? pInfo.title : cleanItemName,
                                name_bn: pInfo ? (pInfo.title_bn || '') : '',
                                price: parseFloat(it.price || (pInfo ? pInfo.price : 0)),
                                oldPrice: (pInfo && pInfo.old_price) ? parseFloat(pInfo.old_price) : 0,
                                quantity: parseInt(it.qty || 1),
                                img: realImg,
                                image: realImg,
                                poriman: (pInfo && pInfo.poriman) ? pInfo.poriman : (it.spec || ''),
                                poriman_bn: (pInfo && pInfo.poriman_bn) ? pInfo.poriman_bn : '',
                                category_name: (pInfo && pInfo.category_name) ? pInfo.category_name : '',
                                category_name_bn: (pInfo && pInfo.category_name_bn) ? pInfo.category_name_bn : ''
                            };
                        });
                }

                // Save active edit invoice metadata ONLY in sessionStorage (Never touches regular cart)
                sessionStorage.setItem('editing_invoice_id', inv.id || inv.db_id || inv.invoice_number);
                sessionStorage.setItem('editing_invoice_code', inv.invoice_number || inv.order_number || inv.id);
                sessionStorage.setItem('editing_invoice_cart', JSON.stringify(cartItems));

                window.location.href = 'cart.php?mode=edit_invoice';
            };

            if (!sessionStorage.getItem('storeProductsCatalog')) {
                fetch('api/store-data.php')
                    .then(r => r.json())
                    .then(d => {
                        if (d.status === 'success' && d.products) {
                            sessionStorage.setItem('storeProductsCatalog', JSON.stringify(d.products));
                        }
                        proceedWithCatalog();
                    })
                    .catch(() => proceedWithCatalog());
            } else {
                proceedWithCatalog();
            }
        } else {
            editCustomList(inv, inv.id);
        }
    }

    function viewOrderInvoice(order) {
        currentModalItem = order;
        currentModalCode = order.id || order.invoice_number || order.order_number || 'ORD-2026';
        currentModalType = 'order';

        const modal = document.getElementById('invoice-view-modal');
        const content = document.getElementById('invoice-modal-content');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const cancelBtn = document.getElementById('modal-cancel-btn');
        const editBtn = document.getElementById('modal-edit-btn');
        const delBtn = document.getElementById('modal-delete-btn');
        const orderNowBtn = document.getElementById('modal-order-now-btn');

        if (orderNowBtn) {
            orderNowBtn.style.display = 'none';
        }
        if (confirmBtn) {
            confirmBtn.style.display = (order.status === 'invoice_created') ? 'inline-flex' : 'none';
        }
        if (cancelBtn) {
            cancelBtn.style.display = (order.status === 'pending' || order.status === 'invoice_created') ? 'inline-flex' : 'none';
        }
        if (editBtn) {
            editBtn.style.display = (order.status === 'pending') ? 'inline-flex' : 'none';
        }
        if (delBtn) {
            delBtn.style.display = 'none';
        }
        
        let summary = order.items_summary || order.html_content || '';
        const orderCode = order.invoice_number || order.order_number || order.id || currentModalCode;

        // If items_summary is already complete A4 HTML invoice, directly render it with dynamic Order No update!
        if (summary && (summary.includes('class="a4-page"') || summary.includes('a4-page') || summary.includes('Watermark') || summary.includes('bazarkinundotcom_logo'))) {
            if (orderCode) {
                summary = summary.replace(/LST-\d+-[A-Z0-9]+-\d+/gi, orderCode);
                summary = summary.replace(/List Code:/gi, (orderCode.startsWith('INV-') ? 'Invoice No:' : 'Order No:'));
                summary = summary.replace(/Custom Grocery List/gi, (orderCode.startsWith('INV-') ? 'Official Invoice' : 'Grocery List Order'));
                summary = summary.replace(/Document:/gi, (orderCode.startsWith('INV-') ? 'Invoice Type:' : 'Order Type:'));
            }
            content.innerHTML = summary;
            modal.style.display = 'block';
            modal.scrollTop = 0;
            return;
        }

        const parsedData = parseListItems(summary, null);

        const footerExtra = `
            <div style="text-align: right;">
                <span style="font-size: 13px; color: #64748b; font-weight: 700;">Total Paid: </span>
                <strong style="font-size: 20px; font-weight: 900; color: #00c675;">৳${order.total_amount}</strong>
            </div>
        `;

        content.innerHTML = generateA4PagesHtml({
            name: order.customer_name || localStorage.getItem('userName') || 'Customer',
            phone: order.customer_phone || localStorage.getItem('userPhone') || '01919668960',
            location: order.customer_address || localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || 'Uttara, Dhaka',
            orderNo: currentModalCode,
            dateTime: order.date || 'Today',
            parsedData: parsedData,
            footerExtra: footerExtra
        });

        modal.style.display = 'block';
        modal.scrollTop = 0;
    }

    function viewCustomListDetails(list, listCode = '') {
        const code = listCode || list.list_code || list.id || 'LST-668960-DA-1';
        currentModalItem = list;
        currentModalCode = code;
        currentModalType = 'list';

        const modal = document.getElementById('invoice-view-modal');
        const orderNowBtn = document.getElementById('modal-order-now-btn');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const editBtn = document.getElementById('modal-edit-btn');
        const delBtn = document.getElementById('modal-delete-btn');
        if (orderNowBtn) orderNowBtn.style.display = 'inline-flex';
        if (confirmBtn) confirmBtn.style.display = 'none';
        if (editBtn) editBtn.style.display = 'inline-flex';
        if (delBtn) delBtn.style.display = 'inline-flex';
        const content = document.getElementById('invoice-modal-content');
        const userName = localStorage.getItem('userName') || 'Customer';
        const userPhone = localStorage.getItem('userPhone') || '';
        const userLocation = localStorage.getItem('userFullAddress') || localStorage.getItem('userLocation') || '';

        // If raw_text is already full A4 HTML, directly render it with List Code sync!
        let rawHtml = list.raw_text || list.content_text || list.html_content || '';
        if (rawHtml && (rawHtml.includes('class="a4-page"') || rawHtml.includes('a4-page') || rawHtml.includes('Watermark') || rawHtml.includes('bazarkinundotcom_logo'))) {
            if (code) {
                rawHtml = rawHtml.replace(/ORD-\d+-[A-Z0-9]+-\d+/gi, code);
                rawHtml = rawHtml.replace(/Order No:/gi, 'List Code:');
                rawHtml = rawHtml.replace(/Grocery List Order/gi, 'Custom Grocery List');
                rawHtml = rawHtml.replace(/Order Type:/gi, 'Document:');
            }
            content.innerHTML = rawHtml;
            modal.style.display = 'block';
            modal.scrollTop = 0;
            return;
        }

        const parsedData = parseListItems(list.raw_text, list.image_urls);

        content.innerHTML = generateA4PagesHtml({
            name: userName,
            phone: userPhone,
            location: userLocation,
            orderNo: code,
            dateTime: list.date || 'Today',
            parsedData: parsedData
        });

        modal.style.display = 'block';
        modal.scrollTop = 0;
    }

    window.addEventListener('languageChanged', (e) => {
        isBangla = e.detail ? e.detail.isBangla : (localStorage.getItem('appLanguage') === 'bn');
        if (window.currentCustomerOrders) {
            renderOrders(window.currentCustomerOrders);
        }
        if (window.currentCustomerInvoices) {
            renderInvoices(window.currentCustomerInvoices);
        }
        if (window.currentCustomerLists) {
            renderCustomLists(window.currentCustomerLists);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadCustomerProfile();
        // Check URL tab
        const urlParams = new URLSearchParams(window.location.search);
        const reqTab = urlParams.get('tab');
        if (reqTab && ['profile', 'order', 'invoice', 'list'].includes(reqTab)) {
            switchProfileTab(reqTab);
        } else {
            switchProfileTab('profile');
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
