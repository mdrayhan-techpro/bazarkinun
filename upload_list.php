<?php
// upload_list.php
// Full Feature Parity with upload_list.html
$pageTitle = 'Upload Your Grocery List - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .upload-card-wrapper {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        padding: 35px;
        border: 1px solid #e2e8f0;
    }
    .upload-option {
        background: white;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        padding: 35px 25px;
        text-align: center;
        cursor: pointer;
        transition: 0.3s;
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    .upload-option:hover {
        border-color: var(--primary-color);
        background: #f0fdf4;
        transform: translateY(-3px);
    }
    .upload-icon {
        font-size: 3rem;
        margin-bottom: 12px;
        display: block;
    }
    .upload-text {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
    }



    #camera-view video {
        max-width: 100% !important;
        height: auto !important;
        border-radius: 12px;
    }

    @media (max-width: 600px) {
        .upload-card-wrapper {
            padding: 20px 14px !important;
            border-radius: 12px !important;
        }
        .upload-option {
            padding: 25px 15px !important;
            min-width: 100% !important;
        }
        #preview-actions button {
            flex: 1 1 calc(50% - 8px) !important;
            padding: 10px 12px !important;
            font-size: 13px !important;
            text-align: center !important;
            justify-content: center !important;
        }
    }

    @media (max-width: 400px) {
        #preview-actions button {
            flex: 1 1 100% !important;
        }
    }
</style>

<main style="padding: 25px 0; background-color: #f8fafc; min-height: 70vh;">
    <div class="container">
        <div style="display: flex; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 12px;">
            <a href="javascript:void(0)" onclick="goBackToPreviousPage()" style="text-decoration: none; color: var(--primary-color); font-weight: 700; font-size: 13px; padding: 8px 16px; border: 1px solid var(--primary-color); border-radius: 8px; transition: 0.3s; background: white; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                <i class="fa-solid fa-arrow-left"></i> <span class="dynamic-lang" data-en="Go Back" data-bn="ফিরে যান">Go Back</span>
            </a>
            <h1 style="font-size: 28px; color: #0f172a; margin: 0; font-weight: 900;" class="dynamic-lang" data-en="Upload Your Grocery List" data-bn="আপনার বাজারের লিস্ট আপলোড করুন">Upload Your Grocery List</h1>
        </div>

        <div class="upload-card-wrapper">
            
            <div id="upload-stage">
                <div id="upload-view">
                    <p class="dynamic-lang" data-en="Choose how you want to upload your handwritten grocery list or prescription." data-bn="আপনার হাতে লেখা কাগজের বাজারের ফর্দ বা প্রেসক্রিপশন কীভাবে আপলোড করতে চান তা বেছে নিন।" style="color: #64748b; margin-bottom: 30px; font-size: 15px; text-align: center;">Choose how you want to upload your handwritten grocery list or prescription.</p>
                    
                    <div style="display: flex; gap: 24px; flex-wrap: wrap; justify-content: center; max-width: 800px; margin: 0 auto;">
                        <!-- Camera Option -->
                        <div class="upload-option" onclick="handleCameraClick()">
                            <span class="upload-icon">📸</span>
                            <span class="upload-text dynamic-lang" data-en="Take a Photo" data-bn="ক্যামেরা দিয়ে ছবি তুলুন">Take a Photo</span>
                            <p class="dynamic-lang" data-en="Use your device camera to snap a picture of your paper list." data-bn="আপনার ডিভাইসের ক্যামেরা দিয়ে কাগজের ফর্দের ছবি তুলুন।" style="color: #64748b; font-size: 13px; margin-top: 10px;">Use your device camera to snap a picture of your paper list.</p>
                        </div>

                        <!-- Gallery Option -->
                        <div class="upload-option" onclick="document.getElementById('gallery-input').click()">
                            <span class="upload-icon">🖼️</span>
                            <span class="upload-text dynamic-lang" data-en="Upload from Gallery" data-bn="গ্যালারি থেকে সিলেক্ট করুন">Upload from Gallery</span>
                            <p class="dynamic-lang" data-en="Select saved photos or scans from your phone or computer." data-bn="আপনার ডিভাইস থেকে সংরক্ষিত ছবিগুলো নির্বাচন করুন।" style="color: #64748b; font-size: 13px; margin-top: 10px;">Select saved photos or scans from your phone or computer.</p>
                            <input type="file" id="gallery-input" accept="image/*" multiple style="display: none;" onchange="handleFilesSelected(this.files)">
                        </div>
                    </div>
                </div>

                <!-- Live Webcam View -->
                <div id="camera-view" style="display: none; flex-direction: column; gap: 20px; align-items: center; width: 100%; margin-top: 20px;">
                    <h3 style="margin: 0; color: #0f172a;" class="dynamic-lang" data-en="Position your grocery list in front of the camera" data-bn="ক্যামেরার সামনে আপনার বাজারের ফর্দ রাখুন">Position your grocery list in front of the camera</h3>
                    <div style="position: relative; width: 100%; max-width: 550px; border-radius: 12px; overflow: hidden; background: #000; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                        <video id="webcam" autoplay playsinline style="width: 100%; height: auto; display: block;"></video>
                    </div>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                        <button onclick="stopCamera()" style="background: white; color: #1e293b; border: 1.5px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;">Cancel Camera</button>
                        <button onclick="takeSnapshot()" style="background: var(--primary-color); color: white; border: none; padding: 12px 28px; border-radius: 8px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);"><i class="fa-solid fa-camera"></i> Snap Photo</button>
                    </div>
                </div>

                <hr id="thumbnail-divider" style="display: none; margin: 35px 0; border: none; border-top: 2px dashed #e2e8f0;">
                
                <div id="shared-thumbnail-section" style="display: none; flex-direction: column; align-items: center;">
                    <h3 style="margin-bottom: 16px; color: #0f172a;" class="dynamic-lang" data-en="Selected Photos" data-bn="নির্বাচিত ছবিসমূহ">Selected Photos</h3>
                    <div id="shared-thumbnails" style="display: flex; gap: 15px; overflow-x: auto; max-width: 100%; padding: 10px;"></div>

                    <div style="width: 100%; max-width: 550px; margin-top: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Special Notes / Instructions (Optional)</label>
                        <textarea id="upload-notes" rows="2" placeholder="Please bring small size potatoes and fresh spinach." style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;"></textarea>
                    </div>

                    <button onclick="generatePreview()" style="margin-top: 25px; background: #0f172a; color: white; border: none; padding: 14px 40px; border-radius: 8px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2); display: inline-flex; align-items: center; gap: 8px;">
                        <span>Finish & Preview List</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Preview Wrapper -->
            <div id="preview-wrapper" style="display: none; flex-direction: column; gap: 6px; align-items: center; width: 100%;">
                <div id="pages-container" style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">
                    <!-- A4 Document page injected here -->
                </div>
                
                <!-- Action Buttons -->
                <div id="preview-actions" class="no-print" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; width: 100%; max-width: 800px; box-sizing: border-box; background: white; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); flex-wrap: wrap;">
                    <button id="btn-preview-upload-again" onclick="resetUpload()" style="background: white; color: #1e293b; border: 1.5px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-arrow-rotate-left"></i> <span class="dynamic-lang" data-en="Upload Again" data-bn="আবার আপলোড করুন">Upload Again</span></button>
                    <button id="btn-preview-save" onclick="saveListForLater()" style="background: white; color: var(--primary-color); border: 1.5px solid var(--primary-color); padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-regular fa-bookmark"></i> <span class="dynamic-lang" data-en="Save List" data-bn="সংরক্ষণ করুন">Save List</span></button>
                    <button id="btn-preview-order" onclick="placeCustomOrder()" style="background: linear-gradient(135deg, #00c675, #059669); color: white; border: none; padding: 9px 22px; border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.35); display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm & Order" data-bn="অর্ডার কনফার্ম করুন">Confirm & Order</span></button>
                    <button id="btn-preview-print" onclick="window.print()" style="background: #334155; color: white; border: none; padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-print"></i> <span class="dynamic-lang" data-en="Print" data-bn="প্রিন্ট">Print</span></button>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
    let uploadedImages = [];
    let videoStream = null;

    function goBackToPreviousPage() {
        if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) {
            window.location.href = document.referrer;
        } else if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = 'index.php';
        }
    }

    function compressImageFile(file, maxWidth = 1200, maxHeight = 1600, quality = 0.75) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let w = img.width;
                    let h = img.height;
                    if (w > maxWidth || h > maxHeight) {
                        const ratio = Math.min(maxWidth / w, maxHeight / h);
                        w = Math.round(w * ratio);
                        h = Math.round(h * ratio);
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    const compressed = canvas.toDataURL('image/jpeg', quality);
                    resolve(compressed);
                };
                img.onerror = function() {
                    resolve(e.target.result);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    async function handleFilesSelected(files) {
        if (!files || files.length === 0) return;
        for (const file of Array.from(files)) {
            const compressed = await compressImageFile(file);
            uploadedImages.push(compressed);
            updateThumbnailSection();
        }
    }

    function handleCameraClick() {
        document.getElementById('upload-view').style.display = 'none';
        document.getElementById('camera-view').style.display = 'flex';
        
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                videoStream = stream;
                document.getElementById('webcam').srcObject = stream;
            })
            .catch(err => {
                console.error("Camera access denied:", err);
                alert("Camera permission denied or camera not found on this device.");
                stopCamera();
            });
    }

    function stopCamera() {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }
        document.getElementById('camera-view').style.display = 'none';
        document.getElementById('upload-view').style.display = 'block';
    }

    function takeSnapshot() {
        const video = document.getElementById('webcam');
        const canvas = document.createElement('canvas');
        let w = video.videoWidth || 640;
        let h = video.videoHeight || 480;
        const maxDim = 1200;
        if (w > maxDim || h > maxDim) {
            const ratio = Math.min(maxDim / w, maxDim / h);
            w = Math.round(w * ratio);
            h = Math.round(h * ratio);
        }
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
        uploadedImages.push(dataUrl);
        updateThumbnailSection();
        showToast("Photo captured!", "success");
    }

    function updateThumbnailSection() {
        const section = document.getElementById('shared-thumbnail-section');
        const divider = document.getElementById('thumbnail-divider');
        const container = document.getElementById('shared-thumbnails');

        if (uploadedImages.length > 0) {
            divider.style.display = 'block';
            section.style.display = 'flex';
        } else {
            divider.style.display = 'none';
            section.style.display = 'none';
        }

        container.innerHTML = '';
        uploadedImages.forEach((src, idx) => {
            const wrap = document.createElement('div');
            wrap.style = "position: relative; width: 110px; height: 110px; border-radius: 10px; overflow: hidden; border: 1.5px solid #cbd5e1; flex-shrink: 0;";
            wrap.innerHTML = `
                <img src="${src}" style="width: 100%; height: 100%; object-fit: cover;">
                <button onclick="removeImage(${idx})" style="position: absolute; top: 4px; right: 4px; background: rgba(239, 68, 68, 0.9); color: white; border: none; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; font-size: 11px;">&times;</button>
            `;
            container.appendChild(wrap);
        });
    }

    function removeImage(idx) {
        uploadedImages.splice(idx, 1);
        updateThumbnailSection();
    }

    function generatePreview() {
        if (uploadedImages.length === 0) {
            showToast(isBangla ? "অনুগ্রহ করে অন্তত একটি ছবি সিলেক্ট বা তুলুন।" : "Please select or snap at least one photo.", "error");
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

        if (videoStream) stopCamera();

        const notes = document.getElementById('upload-notes')?.value.trim() || '';
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });

        const cleanPhone = userPhone.replace(/\D/g, '');
        const last6 = cleanPhone.length >= 6 ? cleanPhone.slice(-6) : cleanPhone.padStart(6, '0');
        const cleanName = userName.replace(/[^A-Za-z]/g, '').toUpperCase();
        const namePrefix = cleanName.length >= 2 ? cleanName.slice(0, 2) : cleanName.padEnd(2, 'X');
        const editingCode = localStorage.getItem('editing_order_code') || localStorage.getItem('editing_list_code');
        const listCode = editingCode || `LST-${last6}-${namePrefix}-1`;
        const isExistingOrder = !!localStorage.getItem('editing_order_code') || (editingCode && editingCode.startsWith('ORD-'));

        // 1 uploaded image per page
        const imageChunks = uploadedImages.map(img => [img]);

        const totalPages = imageChunks.length || 1;
        let fullPagesHtml = '';

        imageChunks.forEach((chunk, pageIndex) => {
            const isFirst = (pageIndex === 0);
            const isLast = (pageIndex === totalPages - 1);
            const img = chunk[0];
            const maxImgHeight = isFirst ? (notes ? '560px' : '640px') : '820px';

            let imagesGrid = `
                <div style="display: flex; justify-content: center; align-items: center; margin: 15px 0; position: relative; z-index: 2; width: 100%; flex: 1;">
                    <div style="border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fafafa; width: 100%; display: flex; justify-content: center; align-items: center; padding: 12px; box-sizing: border-box; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                        <img src="${img}" alt="Grocery List Photo ${pageIndex + 1}" style="max-width: 100%; max-height: ${maxImgHeight}; width: auto; height: auto; object-fit: contain; display: block; border-radius: 6px;">
                    </div>
                </div>
            `;

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

                    ${notes ? `<div style="background: #f8fafc; border-left: 4px solid #00c675; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 13.5px; color: #334155;"><strong>Special Notes:</strong> ${notes}</div>` : ''}
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

                        <!-- Uploaded Images Grid -->
                        ${imagesGrid}
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
        document.getElementById('upload-stage').style.display = 'none';
        document.getElementById('preview-wrapper').style.display = 'flex';

        const btnSave = document.getElementById('btn-preview-save');
        const btnOrder = document.getElementById('btn-preview-order');

        if (isExistingOrder) {
            if (btnSave) btnSave.style.display = 'none';
            if (btnOrder) {
                btnOrder.innerHTML = `<i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Update Order" data-bn="অর্ডার আপডেট করুন">${isBangla ? 'অর্ডার আপডেট করুন' : 'Update Order'}</span>`;
            }
        } else {
            if (btnSave) btnSave.style.display = 'inline-flex';
            if (btnOrder) {
                btnOrder.innerHTML = `<i class="fa-solid fa-check"></i> <span class="dynamic-lang" data-en="Confirm & Order" data-bn="অর্ডার কনফার্ম করুন">${isBangla ? 'অর্ডার কনফার্ম করুন' : 'Confirm & Order'}</span>`;
            }
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetUpload() {
        document.getElementById('preview-wrapper').style.display = 'none';
        document.getElementById('upload-stage').style.display = 'block';
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
                images: uploadedImages,
                order_type: 'uploaded',
                status: 'Draft',
                list_id: editingListId,
                list_code: editingListCode
            })
        })
        .then(r => r.json())
        .then(res => {
            localStorage.removeItem('editing_order_id');
            localStorage.removeItem('editing_order_code');
            localStorage.removeItem('editing_list_id');
            localStorage.removeItem('editing_list_code');
            localStorage.removeItem('uploadListDraft');
            const listCode = res.list_code || editingListCode || 'LST-Saved';
            showToast(isBangla ? `ফর্দের ছবি ড্রাফটে সংরক্ষিত হয়েছে (${listCode})!` : `Grocery list photo saved (${listCode})!`, "success");
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
                images: uploadedImages,
                order_type: 'uploaded',
                edit_order_id: editingOrderId,
                edit_order_number: editingOrderCode,
                list_id: editingListId,
                list_code: editingListCode
            })
        })
        .then(r => r.json())
        .then(res => {
            localStorage.removeItem('editing_order_id');
            localStorage.removeItem('editing_order_code');
            localStorage.removeItem('editing_list_id');
            localStorage.removeItem('editing_list_code');
            localStorage.removeItem('uploadListDraft');
            if (res.status === 'success') {
                showToast((isBangla ? "ফর্দ অর্ডারে জমা হয়েছে! অর্ডার নম্বর: " : "Your grocery list order has been submitted! Order: ") + (res.order_number || editingOrderCode), "success");
                setTimeout(() => {
                    window.location.href = 'profile.php?tab=order';
                }, 1200);
            } else {
                alert("Submission failed: " + (res.message || 'Error'));
            }
        })
        .catch(() => {
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
            localStorage.removeItem('uploadListDraft');
            sessionStorage.removeItem('uploadListDraft');
        } else {
            const editDraft = localStorage.getItem('uploadListDraft');
            if (editDraft) {
                try {
                    const imgs = JSON.parse(editDraft);
                    if (Array.isArray(imgs) && imgs.length > 0) {
                        uploadedImages = imgs;
                        updateThumbnailSection();
                    }
                } catch(e) {}
                localStorage.removeItem('uploadListDraft');
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
