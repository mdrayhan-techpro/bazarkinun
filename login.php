<?php
// login.php
$pageTitle = 'Login - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .auth-login-page {
        padding: 60px 20px;
        background: #f8fafc;
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-login-card {
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

    .auth-login-left {
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

    .auth-login-right {
        background: #ffffff;
        padding: 56px 52px;
        display: flex;
        flex-direction: column;
        justify-content: center;
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
        padding: 16px 18px;
        color: #94a3b8;
        font-size: 17px;
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
        padding: 16px 18px;
        border: none;
        outline: none;
        font-size: 16.5px;
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
        padding: 24px 26px;
        position: relative;
        margin-top: 20px;
        margin-bottom: 24px;
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
        padding: 17px 22px;
        border-radius: 14px;
        font-size: 17.5px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(0, 198, 117, 0.38);
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 24px;
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
        padding: 17px 22px;
        border-radius: 14px;
        font-size: 17.5px;
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
        .auth-login-card {
            grid-template-columns: 1fr;
            max-width: 500px;
        }
        .auth-login-right {
            padding: 40px 28px;
        }
        .auth-login-left {
            padding: 28px 22px;
        }
    }

    @media (max-width: 640px) {
        .auth-hero-img {
            height: 160px;
            max-width: 240px;
            margin: 10px 0;
        }
        .auth-login-page {
            padding: 30px 12px;
        }
        .auth-login-card {
            border-radius: 20px;
        }
        .auth-login-right {
            padding: 30px 20px;
        }
        .auth-login-left {
            padding: 20px 16px;
        }
        .auth-slogan-pill {
            padding: 10px 16px;
            font-size: 13.5px;
        }
    }

    @media (max-width: 374px) {
        .auth-login-page {
            padding: 16px 8px;
        }
        .auth-login-card {
            border-radius: 16px;
        }
        .auth-login-right {
            padding: 24px 14px;
        }
        .auth-login-left {
            padding: 16px 12px;
        }
        .auth-hero-img {
            height: 120px;
            max-width: 180px;
        }
    }
</style>

<main class="auth-login-page">
    <div class="auth-login-card">
        
        <!-- Left Column: Branding & Slogan -->
        <div class="auth-login-left">
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

        <!-- Right Column: Login Form -->
        <div class="auth-login-right">
            <div style="margin-bottom: 28px;">
                <h1 style="font-size: 34px; font-weight: 900; color: #0f172a; margin: 0 0 10px 0; letter-spacing: -0.5px;">
                    <span class="dynamic-lang" data-en="Welcome Back!" data-bn="স্বাগতম!">Welcome Back!</span> 👋
                </h1>
                <p style="color: #64748b; font-size: 15.5px; margin: 0; line-height: 1.6;" class="dynamic-lang" data-en="Enter your registered phone number to securely login to your account." data-bn="আপনার অ্যাকাউন্টে নিরাপদে লগইন করতে আপনার নিবন্ধিত ফোন নম্বরটি দিন।">
                    Enter your registered phone number to securely login to your account.
                </p>
            </div>

            <form id="login-form" onsubmit="handleLoginSubmit(event)">
                
                <!-- PHONE NUMBER FIELD -->
                <div style="margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label style="font-size: 13.5px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.6px;" class="dynamic-lang" data-en="PHONE NUMBER" data-bn="ফোন নম্বর">
                            PHONE NUMBER
                        </label>
                        <a href="javascript:void(0)" id="link-change-number" onclick="resetToPhoneStep()" style="display: none; font-size: 13.5px; font-weight: 700; color: #00c675; text-decoration: none;">
                            <i class="fa-solid fa-pen"></i> <span class="dynamic-lang" data-en="Edit" data-bn="পরিবর্তন">Edit</span>
                        </a>
                    </div>

                    <div class="auth-input-group" id="phone-input-wrapper">
                        <div class="auth-input-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <input type="tel" 
                               id="login-phone" 
                               class="auth-input-field" 
                               placeholder="01XXX-XXXXXX" 
                               value="" 
                               required>
                    </div>
                </div>

                <!-- STEP 1: Send OTP Button -->
                <div id="step-phone-btn">
                    <button type="button" onclick="sendOtpCode()" id="btn-send-otp" class="auth-submit-btn-green">
                        <span class="dynamic-lang" data-en="Send Secure OTP" data-bn="সিকিউর ওটিপি পাঠান">Send Secure OTP</span>
                        <i class="fa-solid fa-paper-plane" style="transform: rotate(15deg); font-size: 18px;"></i>
                    </button>
                </div>

                <!-- STEP 2: Dashed OTP Box & Verify Button -->
                <div id="step-otp-box" style="display: none;">
                    
                    <!-- Mint Dashed Verification Card -->
                    <div class="otp-verify-card">
                        <i class="fa-solid fa-shield-halved otp-shield-watermark"></i>
                        
                        <h4 style="margin: 0 0 16px 0; font-size: 17px; font-weight: 800; color: #15803d;" class="dynamic-lang" data-en="Verify OTP" data-bn="ওটিপি যাচাই করুন">
                            Verify OTP
                        </h4>

                        <!-- Centered OTP Box -->
                        <div class="otp-box-input">
                            <input type="text" 
                                   id="login-otp" 
                                   class="otp-input-field" 
                                   placeholder="••••" 
                                   maxlength="4" 
                                   value="" 
                                   autocomplete="one-time-code"
                                   required>
                        </div>
                    </div>

                    <!-- Dark Navy Verify & Login Button -->
                    <button type="submit" id="btn-verify-otp" class="auth-submit-btn-dark">
                        <span class="dynamic-lang" data-en="Verify &amp; Login" data-bn="যাচাই ও লগইন করুন">Verify &amp; Login</span>
                        <i class="fa-solid fa-arrow-right-to-bracket" style="font-size: 18px;"></i>
                    </button>
                </div>

            </form>

            <div style="text-align: center; margin-top: 36px; font-size: 15.5px; color: #64748b;">
                <span class="dynamic-lang" data-en="Don't have an account?" data-bn="অ্যাকাউন্ট নেই?">Don't have an account?</span> 
                <a href="signup.php" style="background: #eefbf4; color: #00c675; padding: 7px 18px; border-radius: 10px; font-weight: 800; text-decoration: none; margin-left: 8px; display: inline-block; font-size: 15px;" class="dynamic-lang" data-en="Create one now" data-bn="এখনই তৈরি করুন">
                    Create one now
                </a>
            </div>
        </div>

    </div>
</main>

<script>
    function sendOtpCode() {
        const rawPhone = document.getElementById('login-phone')?.value.trim();
        const phone = (typeof window.normalizeBdPhone === 'function') ? window.normalizeBdPhone(rawPhone) : rawPhone;
        
        if (!phone || (typeof window.isValidBdPhone === 'function' && !window.isValidBdPhone(phone))) {
            const err = isBangla ? "অনুগ্রহ করে সঠিক ১১-সংখ্যার বাংলাদেশি মোবাইল নম্বর দিন।" : "Please enter a valid 11-digit Bangladeshi mobile number";
            if (typeof showToast === 'function') showToast(err, false);
            else alert(err);
            document.getElementById('login-phone')?.focus();
            return;
        }

        const btn = document.getElementById('btn-send-otp');
        const origText = btn.innerHTML;
        btn.innerHTML = '<span>Sending OTP...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('api/send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, type: 'login' })
        })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = origText;
            btn.disabled = false;
            if (res.status === 'success') {
                switchToOtpStep();
                if (res.otp && (location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
                    console.log('Dev OTP:', res.otp);
                    const otpInput = document.getElementById('login-otp');
                    if (otpInput) otpInput.value = res.otp;
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(res.message || "এই নম্বরে কোনো অ্যাকাউন্ট নেই! অনুগ্রহ করে প্রথমে সাইন আপ করুন।", false);
                } else {
                    alert(res.message || "এই নম্বরে কোনো অ্যাকাউন্ট নেই! অনুগ্রহ করে প্রথমে সাইন আপ করুন।");
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

    function switchToOtpStep() {
        const phoneInput = document.getElementById('login-phone');
        const wrapper = document.getElementById('phone-input-wrapper');
        phoneInput.readOnly = true;
        wrapper.classList.add('active-step2');
        document.getElementById('link-change-number').style.display = 'inline-block';

        document.getElementById('step-phone-btn').style.display = 'none';
        document.getElementById('step-otp-box').style.display = 'block';
        
        const otpInput = document.getElementById('login-otp');
        otpInput.focus();
        otpInput.select();
    }

    function resetToPhoneStep() {
        const phoneInput = document.getElementById('login-phone');
        const wrapper = document.getElementById('phone-input-wrapper');
        phoneInput.readOnly = false;
        wrapper.classList.remove('active-step2');
        document.getElementById('link-change-number').style.display = 'none';

        document.getElementById('step-otp-box').style.display = 'none';
        document.getElementById('step-phone-btn').style.display = 'block';
        phoneInput.focus();
    }

    function processLoginSuccess(res, fallbackPhone) {
        if (res.status === 'success') {
            const newPhone = res.user?.phone || fallbackPhone;
            
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userName', res.user?.name || 'Customer');
            localStorage.setItem('userPhone', newPhone);

            if (res.user?.saved_addresses && Array.isArray(res.user.saved_addresses) && res.user.saved_addresses.length > 0) {
                localStorage.setItem('savedLocationsList', JSON.stringify(res.user.saved_addresses));
                const activeId = res.user.active_address_id || res.user.saved_addresses[0].id;
                localStorage.setItem('activeSavedLocationId', activeId);
                const activeLoc = res.user.saved_addresses.find(a => a.id === activeId) || res.user.saved_addresses[0];
                localStorage.setItem('userLocation', activeLoc.title || '');
                localStorage.setItem('userFullAddress', activeLoc.fullAddress || activeLoc.title || '');
                localStorage.setItem('userKeyword', activeLoc.kw || 'Home');
                if (activeLoc.lat) localStorage.setItem('userLat', activeLoc.lat);
                if (activeLoc.lng) localStorage.setItem('userLng', activeLoc.lng);
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

            if (typeof showToast === 'function') showToast("লগইন সফল হয়েছে! (Login Successful)");
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 500);
        } else {
            if (typeof showToast === 'function') showToast(res.message || "Invalid OTP code", false);
        }
    }

    function handleLoginSubmit(e) {
        e.preventDefault();
        const rawPhone = document.getElementById('login-phone')?.value.trim();
        const phone = (typeof window.normalizeBdPhone === 'function') ? window.normalizeBdPhone(rawPhone) : rawPhone;
        const otp = document.getElementById('login-otp')?.value.trim();

        const btn = document.getElementById('btn-verify-otp');
        const origText = btn.innerHTML;
        btn.innerHTML = '<span>Verifying...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, otp })
        })
        .then(r => r.json())
        .then(res => {
            btn.innerHTML = origText;
            btn.disabled = false;
            processLoginSuccess(res, phone);
        })
        .catch(() => {
            btn.innerHTML = origText;
            btn.disabled = false;
            if (typeof showToast === 'function') showToast("লগইন সম্পন্ন হয়েছে!", true);
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 600);
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
