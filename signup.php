<?php
// signup.php
$pageTitle = 'Create an Account - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .auth-signup-page {
        padding: 60px 20px;
        background: #f8fafc;
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-signup-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 32px;
        box-shadow: 0 35px 70px -15px rgba(0, 0, 0, 0.25), 0 20px 40px -10px rgba(0, 0, 0, 0.15), 0 0 1px 1px rgba(0, 0, 0, 0.03);
        max-width: 1040px;
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1.18fr;
        overflow: hidden;
    }

    .auth-signup-left {
        background: #f0fdf4;
        padding: 36px 32px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        border-right: 1px solid #e2fbe8;
        position: relative;
    }

    .auth-back-btn {
        align-self: flex-start;
        background: #ffffff;
        color: #059669;
        border: 1px solid #d1fae5;
        border-radius: 9999px;
        padding: 8px 20px;
        font-weight: 700;
        font-size: 14.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease;
    }

    .auth-back-btn:hover {
        background: #ecfdf5;
        transform: translateX(-3px);
    }

    .auth-hero-img {
        width: 100%;
        max-width: 350px;
        height: 280px;
        object-fit: cover;
        border-radius: 0;
        margin: 18px 0;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }

    .auth-brand-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        margin-bottom: 14px;
    }

    .auth-slogan-pill {
        background: #ffffff;
        border: 1px solid #bbf7d0;
        border-radius: 9999px;
        padding: 13px 22px;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.09);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        box-sizing: border-box;
    }

    .auth-signup-right {
        background: #ffffff;
        padding: 50px 52px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-field-group {
        margin-bottom: 14px;
    }

    .auth-field-label {
        display: block;
        font-size: 13px;
        font-weight: 800;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 7px;
    }

    .auth-input-group {
        position: relative;
        display: flex;
        align-items: center;
        border: 1.8px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        transition: all 0.2s;
        overflow: hidden;
    }

    .auth-input-group.active-step2 {
        background: #eff6ff;
        border-color: #dbeafe;
    }

    .auth-input-group:focus-within:not(.active-step2) {
        border-color: #00c675;
        box-shadow: 0 0 0 4px rgba(0, 198, 117, 0.16);
    }

    .auth-input-icon {
        padding: 15px 17px;
        color: #94a3b8;
        font-size: 16px;
        border-right: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fafafa;
    }

    .auth-input-group.active-step2 .auth-input-icon {
        background: transparent;
        border-right-color: #e0e7ff;
    }

    .auth-input-field {
        padding: 15px 17px;
        border: none;
        outline: none;
        font-size: 15.5px;
        font-weight: 600;
        color: #0f172a;
        width: 100%;
        background: transparent;
    }

    .auth-input-group.active-step2 .auth-input-field {
        font-weight: 700;
        color: #0f172a;
        cursor: not-allowed;
    }

    /* Dashed OTP Box */
    .otp-verify-card {
        background: #f0fdf4;
        border: 1.8px dashed #86efac;
        border-radius: 20px;
        padding: 22px 24px;
        position: relative;
        margin-top: 16px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .otp-shield-watermark {
        position: absolute;
        right: 14px;
        top: 12px;
        font-size: 60px;
        color: rgba(16, 185, 129, 0.14);
        pointer-events: none;
        user-select: none;
    }

    .otp-box-input {
        background: #ffffff;
        border: 2.2px solid #00c675;
        border-radius: 14px;
        padding: 12px 18px;
        box-shadow: 0 4px 14px rgba(0, 198, 117, 0.14);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .otp-input-field {
        border: none;
        outline: none;
        background: transparent;
        text-align: center;
        font-size: 30px;
        font-weight: 900;
        letter-spacing: 18px;
        color: #1e293b;
        width: 100%;
        caret-color: #00c675;
    }

    /* Action Buttons */
    .auth-submit-btn-green {
        width: 100%;
        background: #00c675;
        color: #ffffff;
        border: none;
        padding: 16px 22px;
        border-radius: 14px;
        font-size: 17px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(0, 198, 117, 0.38);
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 14px;
    }

    .auth-submit-btn-green:hover {
        background: #00b368;
        transform: translateY(-1.5px);
        box-shadow: 0 9px 26px rgba(0, 198, 117, 0.45);
    }

    .auth-submit-btn-dark {
        width: 100%;
        background: #0f172a;
        color: #ffffff;
        border: none;
        padding: 16px 22px;
        border-radius: 14px;
        font-size: 17px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.28);
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .auth-submit-btn-dark:hover {
        background: #1e293b;
        transform: translateY(-1.5px);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.35);
    }

    @media (max-width: 768px) {
        .auth-signup-card {
            grid-template-columns: 1fr;
            max-width: 500px;
        }
        .auth-signup-right {
            padding: 38px 26px;
        }
        .auth-signup-left {
            padding: 28px 22px;
        }
    }

    @media (max-width: 640px) {
        .auth-hero-img {
            height: 160px;
            max-width: 240px;
            margin: 10px 0;
        }
        .auth-signup-page {
            padding: 30px 12px;
        }
        .auth-signup-card {
            border-radius: 20px;
        }
        .auth-signup-right {
            padding: 30px 18px;
        }
        .auth-signup-left {
            padding: 20px 16px;
        }
        .auth-slogan-pill {
            padding: 10px 16px;
            font-size: 13.5px;
        }
    }

    @media (max-width: 374px) {
        .auth-signup-page {
            padding: 16px 8px;
        }
        .auth-signup-card {
            border-radius: 16px;
        }
        .auth-signup-right {
            padding: 22px 12px;
        }
        .auth-signup-left {
            padding: 16px 12px;
        }
        .auth-hero-img {
            height: 120px;
            max-width: 180px;
        }
    }
</style>

<main class="auth-signup-page">
    <div class="auth-signup-card">
        
        <!-- Left Column: Branding & Slogan -->
        <div class="auth-signup-left">
            <a href="index.php" class="auth-back-btn">
                <i class="fa-solid fa-arrow-left"></i> <span class="dynamic-lang" data-en="Back" data-bn="ফিরে যান">Back</span>
            </a>

            <div>
                <img src="assets/hero_groceries.jpg" 
                     onerror="this.src='https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&auto=format&fit=crop&q=80'" 
                     alt="Fresh Groceries BazarKinun.com" 
                     class="auth-hero-img">

                <div class="auth-brand-badge">
                    <img src="assets/bazarkinundotcom_logo.png" alt="BazarKinun.com Logo" style="height: 48px; width: auto; margin-bottom: 4px;" onerror="this.style.display='none'">
                    <div style="font-size: 30px; font-weight: 900; letter-spacing: -0.5px;">
                        <span style="color: #15803d;">Bazar</span><span style="color: #f97316;">Kinun</span><span style="color: #15803d;">.com</span>
                    </div>
                </div>
            </div>

            <div class="auth-slogan-pill">
                <span style="font-size: 18px;">🚚</span>
                <span><strong style="color: #047857;">বাজারে নয়,</strong> <strong style="color: #ea580c;">বাজার আসবে</strong> <strong style="color: #047857;">আপনার ঘরে!</strong></span>
            </div>
        </div>

        <!-- Right Column: Registration Form -->
        <div class="auth-signup-right">
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 32px; font-weight: 900; color: #0f172a; margin: 0 0 8px 0; letter-spacing: -0.5px;">
                    <span class="dynamic-lang" data-en="Create an Account" data-bn="অ্যাকাউন্ট তৈরি করুন">Create an Account</span> ✨
                </h1>
                <p style="color: #64748b; font-size: 15px; margin: 0; line-height: 1.6;" class="dynamic-lang" data-en="Join BazarKinun.com to easily place orders." data-bn="সহজে অর্ডার করতে BazarKinun.com-এ যোগ দিন।">
                    Join BazarKinun.com to easily place orders.
                </p>
            </div>

            <form id="signup-form" onsubmit="handleSignupSubmit(event)">
                
                <!-- 1. FULL NAME -->
                <div class="auth-field-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px;">
                        <label class="auth-field-label dynamic-lang" data-en="FULL NAME" data-bn="পুরো নাম" style="margin-bottom: 0;">FULL NAME</label>
                        <a href="javascript:void(0)" id="signup-edit-link" onclick="resetSignupToInfo()" style="display: none; font-size: 13px; font-weight: 700; color: #00c675; text-decoration: none;">
                            <i class="fa-solid fa-pen"></i> <span class="dynamic-lang" data-en="Edit" data-bn="পরিবর্তন">Edit</span>
                        </a>
                    </div>
                    <div class="auth-input-group" id="wrapper-signup-name">
                        <div class="auth-input-icon">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <input type="text" 
                               id="signup-name" 
                               class="auth-input-field" 
                               placeholder="Enter your full name" 
                               value="" 
                               required>
                    </div>
                </div>

                <!-- 2. PHONE NUMBER -->
                <div class="auth-field-group">
                    <label class="auth-field-label dynamic-lang" data-en="PHONE NUMBER" data-bn="ফোন নম্বর">PHONE NUMBER</label>
                    <div class="auth-input-group" id="wrapper-signup-phone">
                        <div class="auth-input-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <input type="tel" 
                               id="signup-phone" 
                               class="auth-input-field" 
                               placeholder="01XXX-XXXXXX" 
                               value="" 
                               required>
                    </div>
                </div>

                <!-- 3. EMAIL ADDRESS (OPTIONAL) -->
                <div class="auth-field-group">
                    <label class="auth-field-label dynamic-lang" data-en="EMAIL ADDRESS (OPTIONAL)" data-bn="ইমেইল এড্রেস (ঐচ্ছিক)">EMAIL ADDRESS (OPTIONAL)</label>
                    <div class="auth-input-group" id="wrapper-signup-email">
                        <div class="auth-input-icon">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" 
                               id="signup-email" 
                               class="auth-input-field" 
                               placeholder="you@example.com" 
                               value="">
                    </div>
                </div>

                <!-- STEP 1: Send OTP Button -->
                <div id="step-signup-btn">
                    <button type="button" onclick="sendSignupOtp()" id="btn-send-signup-otp" class="auth-submit-btn-green">
                        <span class="dynamic-lang" data-en="Send OTP &amp; Sign Up" data-bn="ওটিপি পাঠান এবং সাইন আপ করুন">Send OTP &amp; Sign Up</span>
                        <i class="fa-solid fa-paper-plane" style="transform: rotate(15deg); font-size: 17px;"></i>
                    </button>
                </div>

                <!-- STEP 2: Dashed OTP Box & Verify Button -->
                <div id="step-signup-otp-box" style="display: none;">
                    
                    <!-- Mint Dashed Verification Card -->
                    <div class="otp-verify-card">
                        <i class="fa-solid fa-shield-halved otp-shield-watermark"></i>
                        
                        <h4 style="margin: 0 0 16px 0; font-size: 16.5px; font-weight: 800; color: #15803d;" class="dynamic-lang" data-en="Verify OTP" data-bn="ওটিপি যাচাই করুন">
                            Verify OTP
                        </h4>

                        <!-- Centered OTP Box -->
                        <div class="otp-box-input">
                            <input type="text" 
                                   id="signup-otp" 
                                   class="otp-input-field" 
                                   placeholder="••••" 
                                   maxlength="4" 
                                   value="" 
                                   autocomplete="one-time-code"
                                   required>
                        </div>
                    </div>

                    <!-- Dark Navy Verify & Complete Button -->
                    <button type="submit" id="btn-verify-signup-otp" class="auth-submit-btn-dark">
                        <span class="dynamic-lang" data-en="Verify &amp; Complete" data-bn="যাচাই ও সম্পন্ন করুন">Verify &amp; Complete</span>
                        <i class="fa-solid fa-arrow-right-to-bracket" style="font-size: 17px;"></i>
                    </button>
                </div>

            </form>

            <div style="text-align: center; margin-top: 32px; font-size: 15px; color: #64748b;">
                <span class="dynamic-lang" data-en="Already have an account?" data-bn="আপনার কি আগে থেকেই অ্যাকাউন্ট আছে?">Already have an account?</span> 
                <a href="login.php" style="background: #eefbf4; color: #00c675; padding: 6px 18px; border-radius: 10px; font-weight: 800; text-decoration: none; margin-left: 8px; display: inline-block; font-size: 15px;" class="dynamic-lang" data-en="Login" data-bn="লগইন">
                    Login
                </a>
            </div>
        </div>

    </div>
</main>

<script>
    function sendSignupOtp() {
        const name = document.getElementById('signup-name')?.value.trim();
        const rawPhone = document.getElementById('signup-phone')?.value.trim();
        const phone = (typeof window.normalizeBdPhone === 'function') ? window.normalizeBdPhone(rawPhone) : rawPhone;

        if (!name) {
            if (typeof showToast === 'function') showToast("Please enter your full name", false);
            else alert("Please enter your full name");
            document.getElementById('signup-name').focus();
            return;
        }

        if (!phone || (typeof window.isValidBdPhone === 'function' && !window.isValidBdPhone(phone))) {
            const err = isBangla ? "অনুগ্রহ করে সঠিক ১১-সংখ্যার বাংলাদেশি মোবাইল নম্বর দিন।" : "Please enter a valid 11-digit Bangladeshi mobile number";
            if (typeof showToast === 'function') showToast(err, false);
            else alert(err);
            document.getElementById('signup-phone').focus();
            return;
        }

        const btn = document.getElementById('btn-send-signup-otp');
        const origText = btn.innerHTML;
        btn.innerHTML = '<span>Sending OTP...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('api/send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, name, type: 'register' })
        })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = origText;
            btn.disabled = false;
            if (res.status === 'success') {
                switchToSignupOtpStep();
                if (res.otp && (location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
                    console.log('Dev OTP:', res.otp);
                    const otpInput = document.getElementById('signup-otp');
                    if (otpInput) otpInput.value = res.otp;
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(res.message || "এই নম্বরে ইতোমধ্যে অ্যাকাউন্ট রয়েছে! লগইন করুন।", false);
                } else {
                    alert(res.message || "এই নম্বরে ইতোমধ্যে অ্যাকাউন্ট রয়েছে! লগইন করুন।");
                }
            }
        })
        .catch(() => {
            btn.innerHTML = origText;
            btn.disabled = false;
            if (typeof showToast === 'function') {
                showToast("সার্ভারে সমস্যা হয়েছে, পুনরায় চেষ্টা করুন।", false);
            }
        });
    }

    function switchToSignupOtpStep() {
        // Lock inputs & add active-step2 styling
        ['signup-name', 'signup-phone', 'signup-email'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.readOnly = true;
        });

        ['wrapper-signup-name', 'wrapper-signup-phone', 'wrapper-signup-email'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('active-step2');
        });

        document.getElementById('signup-edit-link').style.display = 'inline-block';
        document.getElementById('step-signup-btn').style.display = 'none';
        document.getElementById('step-signup-otp-box').style.display = 'block';

        const otpInput = document.getElementById('signup-otp');
        otpInput.focus();
        otpInput.select();
    }

    function resetSignupToInfo() {
        ['signup-name', 'signup-phone', 'signup-email'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.readOnly = false;
        });

        ['wrapper-signup-name', 'wrapper-signup-phone', 'wrapper-signup-email'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active-step2');
        });

        document.getElementById('signup-edit-link').style.display = 'none';
        document.getElementById('step-signup-otp-box').style.display = 'none';
        document.getElementById('step-signup-btn').style.display = 'block';
        document.getElementById('signup-name').focus();
    }

    function processSignupSuccess(res, fallbackPhone, fallbackName) {
        if (res.status === 'success') {
            const newPhone = res.user?.phone || fallbackPhone;
            // Set authenticated customer info
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userName', res.user?.name || fallbackName);
            localStorage.setItem('userPhone', newPhone);

            // Restore or preserve locations
            if (res.user?.saved_addresses && Array.isArray(res.user.saved_addresses) && res.user.saved_addresses.length > 0) {
                localStorage.setItem('savedLocationsList', JSON.stringify(res.user.saved_addresses));
                const activeId = res.user.active_address_id || res.user.saved_addresses[0].id;
                localStorage.setItem('activeSavedLocationId', activeId);
                const activeLoc = res.user.saved_addresses.find(a => a.id === activeId) || res.user.saved_addresses[0];
                localStorage.setItem('userLocation', activeLoc.title || '');
                localStorage.setItem('userFullAddress', activeLoc.fullAddress || activeLoc.title || '');
                localStorage.setItem('userKeyword', activeLoc.kw || 'Home');
            } else if (res.user?.location && res.user.location.trim() !== '') {
                const fallbackLoc = {
                    id: 'loc_default_1',
                    kw: 'Home',
                    title: res.user.location.trim(),
                    fullAddress: res.user.location.trim(),
                    lat: 23.810331,
                    lng: 90.412521
                };
                localStorage.setItem('savedLocationsList', JSON.stringify([fallbackLoc]));
                localStorage.setItem('activeSavedLocationId', fallbackLoc.id);
                localStorage.setItem('userLocation', fallbackLoc.title);
                localStorage.setItem('userFullAddress', fallbackLoc.fullAddress);
                localStorage.setItem('userKeyword', 'Home');
                localStorage.setItem('userLat', fallbackLoc.lat);
                localStorage.setItem('userLng', fallbackLoc.lng);
            } else {
                const guestLoc = localStorage.getItem('userLocation');
                const guestFull = localStorage.getItem('userFullAddress');
                const guestList = localStorage.getItem('savedLocationsList');
                if (!guestLoc && !guestFull && !guestList) {
                    localStorage.removeItem('userLocation');
                    localStorage.removeItem('userFullAddress');
                    localStorage.removeItem('userLat');
                    localStorage.removeItem('userLng');
                    localStorage.removeItem('userKeyword');
                    localStorage.removeItem('savedLocationsList');
                    localStorage.removeItem('activeSavedLocationId');
                }
            }

            if (res.user?.cart_data && Array.isArray(res.user.cart_data) && res.user.cart_data.length > 0) {
                localStorage.setItem('cart', JSON.stringify(res.user.cart_data));
            }
            if (res.user?.wishlist_data && Array.isArray(res.user.wishlist_data) && res.user.wishlist_data.length > 0) {
                localStorage.setItem('wishlist', JSON.stringify(res.user.wishlist_data));
            }

            if (typeof syncUserStateToBackend === 'function') {
                syncUserStateToBackend(true);
            }

            if (typeof showToast === 'function') showToast("রেজিস্ট্রেশন সফল হয়েছে! (Account Created)");
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 500);
        } else {
            if (typeof showToast === 'function') showToast(res.message || "Signup failed", false);
        }
    }

    function handleSignupSubmit(e) {
        e.preventDefault();
        const name = document.getElementById('signup-name')?.value.trim();
        const rawPhone = document.getElementById('signup-phone')?.value.trim();
        const phone = (typeof window.normalizeBdPhone === 'function') ? window.normalizeBdPhone(rawPhone) : rawPhone;
        const email = document.getElementById('signup-email')?.value.trim();
        const otp = document.getElementById('signup-otp')?.value.trim();

        const btn = document.getElementById('btn-verify-signup-otp');
        const origText = btn.innerHTML;
        btn.innerHTML = '<span>Creating Account...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('api/create-customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, phone, email, otp })
        })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = origText;
            btn.disabled = false;
            processSignupSuccess(res, phone, name);
        })
        .catch(() => {
            btn.innerHTML = origText;
            btn.disabled = false;
            if (typeof showToast === 'function') showToast("রেজিস্ট্রেশন সম্পন্ন হয়েছে!", true);
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 600);
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
