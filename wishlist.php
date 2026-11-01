<?php
// wishlist.php
$pageTitle = 'My Wishlist - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .wishlist-list-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
        max-width: 920px;
        margin: 0 auto;
    }

    .wishlist-row-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        gap: 18px;
        flex-wrap: wrap;
    }

    .wishlist-row-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }

    .wishlist-img-box {
        width: 85px;
        height: 85px;
        border-radius: 14px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding: 6px;
        flex-shrink: 0;
        border: 1px solid #f1f5f9;
    }

    .wishlist-img-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .wishlist-add-btn {
        background: #00c675;
        color: white;
        border: none;
        padding: 11px 26px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(0, 198, 117, 0.3);
        transition: background 0.2s, transform 0.15s;
    }

    .wishlist-add-btn:hover {
        background: #00b067;
        transform: translateY(-1px);
    }

    .wishlist-delete-btn {
        background: #fee2e2;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        color: #ef4444;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s, transform 0.15s;
    }

    .wishlist-delete-btn:hover {
        background: #fecaca;
        transform: scale(1.05);
    }

    @media (max-width: 640px) {
        .wishlist-row-card {
            padding: 14px 16px;
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }
        .wishlist-actions {
            justify-content: space-between;
            width: 100%;
        }
        .wishlist-add-btn {
            flex: 1;
            justify-content: center;
        }
        .wishlist-header-row {
            flex-wrap: wrap !important;
            gap: 12px !important;
        }
    }

    @media (max-width: 480px) {
        .wishlist-title-text {
            font-size: 22px !important;
        }
        .wishlist-img-box {
            width: 70px !important;
            height: 70px !important;
        }
    }

    @media (max-width: 374px) {
        .wishlist-title-text {
            font-size: 19px !important;
        }
        .wishlist-add-btn {
            padding: 9px 16px !important;
            font-size: 13px !important;
        }
    }
</style>

<main style="padding: 35px 0 60px 0; background-color: #f8fafc; min-height: 75vh;">
    <div class="container">
        
        <!-- Header Row -->
        <div class="wishlist-header-row" style="max-width: 920px; margin: 0 auto 24px auto; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1 class="dynamic-lang wishlist-title-text" data-en="My Wishlist" data-bn="আমার পছন্দের তালিকা" style="font-size: 28px; color: #0f172a; margin: 0; font-weight: 900;">My Wishlist</h1>
                <span id="wishlist-count-badge" style="background: rgba(0, 198, 117, 0.12); color: #00c675; font-size: 13px; font-weight: 800; padding: 4px 12px; border-radius: 20px;">0 items</span>
            </div>
            <a href="shop.php" style="text-decoration: none; color: #00c675; font-weight: 700; font-size: 13.5px; padding: 8px 18px; border: 1.5px solid #00c675; border-radius: 50px; background: white; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                <i class="fa-solid fa-arrow-left"></i> <span class="dynamic-lang" data-en="Browse More" data-bn="আরও পণ্য দেখুন">Browse More</span>
            </a>
        </div>

        <!-- Wishlist List Container -->
        <div id="wishlist-container" class="wishlist-list-container">
            <!-- Wishlist cards injected by JS -->
        </div>

    </div>
</main>

<script>
    function renderWishlist() {
        const container = document.getElementById('wishlist-container');
        const badge = document.getElementById('wishlist-count-badge');
        const list = JSON.parse(localStorage.getItem('wishlist') || '[]');

        if (badge) {
            badge.innerText = isBangla ? `${list.length}টি পণ্য` : `${list.length} item${list.length !== 1 ? 's' : ''}`;
        }

        if (list.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1.5px dashed #cbd5e1; max-width: 600px; margin: 0 auto;">
                    <i class="fa-regular fa-heart" style="font-size: 52px; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                    <h3 class="dynamic-lang" data-en="Your wishlist is empty" data-bn="আপনার পছন্দের তালিকা খালি" style="color: #0f172a; margin: 0 0 8px 0; font-size: 20px; font-weight: 800;">${isBangla ? 'আপনার পছন্দের তালিকা খালি' : 'Your wishlist is empty'}</h3>
                    <p class="dynamic-lang" data-en="Explore our store and save items you want to buy later." data-bn="আমাদের স্টোর ব্রাউজ করুন এবং পছন্দের পণ্য সেভ করে রাখুন।" style="color: #64748b; margin: 0 0 22px 0; font-size: 14px;">${isBangla ? 'আমাদের স্টোর ব্রাউজ করুন এবং পছন্দের পণ্য সেভ করে রাখুন।' : 'Explore our store and save items you want to buy later.'}</p>
                    <a href="shop.php" style="background: #00c675; color: white; text-decoration: none; padding: 12px 28px; border-radius: 50px; font-weight: 800; font-size: 14.5px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 198, 117, 0.3);">
                        <i class="fa-solid fa-store"></i> <span class="dynamic-lang" data-en="Go to Shop" data-bn="শপে যান">${isBangla ? 'শপে যান' : 'Go to Shop'}</span>
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        list.forEach(item => {
            const prodInfo = (typeof window.getProductInfo === 'function') ? window.getProductInfo(item.name) : null;
            const price = (prodInfo && typeof prodInfo.price !== 'undefined') ? parseFloat(prodInfo.price) : (parseFloat(item.price) || 0);
            const oldPrice = (prodInfo && typeof prodInfo.old_price !== 'undefined' && parseFloat(prodInfo.old_price) > 0) ? parseFloat(prodInfo.old_price) : (item.oldPrice ? parseFloat(item.oldPrice) : (price + Math.round(price * 0.15)));
            const imgSrc = item.image || item.img || (prodInfo ? prodInfo.image : 'https://placehold.co/85x85?text=Item');
            const safeName = item.name.replace(/'/g, "\\'");

            let displayName = item.name;
            if (isBangla) {
                displayName = item.name_bn || item.title_bn || (prodInfo ? prodInfo.title_bn : null) || item.name;
            }
            let displayCat = item.category || item.category_name || (prodInfo ? prodInfo.category_name : 'Grocery');
            if (isBangla) {
                displayCat = item.category_name_bn || (prodInfo ? prodInfo.category_name_bn : null) || displayCat;
            }
            let displayPoriman = item.poriman || item.description || (prodInfo ? prodInfo.poriman : '');
            if (isBangla) {
                displayPoriman = item.poriman_bn || (prodInfo ? prodInfo.poriman_bn : null) || displayPoriman;
            }

            const card = document.createElement('div');
            card.className = 'wishlist-row-card';
            card.innerHTML = `
                <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 260px;">
                    <div class="wishlist-img-box">
                        <img src="${imgSrc}" alt="${displayName}" onerror="this.src='https://placehold.co/85x85?text=Grocery';">
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: center;">
                        <span style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 3px;">
                            ${displayCat}
                        </span>
                        <h3 style="margin: 0 0 3px 0; font-size: 17px; font-weight: 800; color: #0f172a; line-height: 1.25;">
                            ${displayName}
                        </h3>
                        ${displayPoriman ? `<div style="font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 6px;">${displayPoriman}</div>` : ''}
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                            <span style="color: #00c675; font-size: 19px; font-weight: 900; line-height: 1;">৳${price}</span>
                            ${oldPrice > price ? `<span style="color: #94a3b8; text-decoration: line-through; font-size: 13.5px; font-weight: 600;">৳${oldPrice}</span>` : ''}
                        </div>
                    </div>
                </div>

                <div class="wishlist-actions" style="display: flex; align-items: center; gap: 12px;">
                    <button class="wishlist-add-btn" onclick="moveToCart('${safeName}', ${price}, ${oldPrice}, '${imgSrc}', '${(item.name_bn || (prodInfo ? prodInfo.title_bn : '')).replace(/'/g, "\\'")}', '${(item.category_name || (prodInfo ? prodInfo.category_name : '')).replace(/'/g, "\\'")}', '${(item.category_name_bn || (prodInfo ? prodInfo.category_name_bn : '')).replace(/'/g, "\\'")}', '${(item.poriman || (prodInfo ? prodInfo.poriman : '')).replace(/'/g, "\\'")}', '${(item.poriman_bn || (prodInfo ? prodInfo.poriman_bn : '')).replace(/'/g, "\\'")}')">
                        <i class="fa-solid fa-cart-shopping"></i> <span class="dynamic-lang" data-en="Add to Cart" data-bn="কার্টে যোগ করুন">${isBangla ? 'কার্টে যোগ করুন' : 'Add to Cart'}</span>
                    </button>
                    <button class="wishlist-delete-btn" onclick="removeFromWishlist('${safeName}')" title="${isBangla ? 'পছন্দের তালিকা থেকে মুছুন' : 'Remove from Wishlist'}">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function moveToCart(name, price, oldPrice, img, name_bn = '', category_name = '', category_name_bn = '', poriman = '', poriman_bn = '') {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let ex = cart.find(i => i.name === name);
        const prodInfo = (typeof window.getProductInfo === 'function') ? window.getProductInfo(name) : null;
        const bName = name_bn || (prodInfo ? prodInfo.title_bn : name);
        const bCat = category_name || (prodInfo ? prodInfo.category_name : 'Grocery');
        const bCatBn = category_name_bn || (prodInfo ? prodInfo.category_name_bn : bCat);
        const bPoriman = poriman || (prodInfo ? prodInfo.poriman : '');
        const bPorimanBn = poriman_bn || (prodInfo ? prodInfo.poriman_bn : bPoriman);
        const livePrice = (prodInfo && typeof prodInfo.price !== 'undefined') ? parseFloat(prodInfo.price) : price;
        const liveOldPrice = (prodInfo && typeof prodInfo.old_price !== 'undefined' && parseFloat(prodInfo.old_price) > 0) ? parseFloat(prodInfo.old_price) : oldPrice;

        if (ex) {
            ex.quantity += 1;
            ex.price = livePrice;
            ex.oldPrice = liveOldPrice;
            if (!ex.name_bn && bName) ex.name_bn = bName;
            if (!ex.poriman && bPoriman) ex.poriman = bPoriman;
            if (!ex.poriman_bn && bPorimanBn) ex.poriman_bn = bPorimanBn;
        } else {
            cart.push({ 
                name, 
                name_bn: bName, 
                price: livePrice, 
                oldPrice: liveOldPrice, 
                image: img, 
                img: img,
                quantity: 1,
                category_name: bCat,
                category_name_bn: bCatBn,
                poriman: bPoriman,
                poriman_bn: bPorimanBn
            });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartBadge();
        if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();
        const toastName = isBangla ? (bName || name) : name;
        showToast(isBangla ? `"${toastName}" কার্টে যোগ করা হয়েছে!` : `"${name}" added to cart!`);
    }

    function removeFromWishlist(name) {
        let list = JSON.parse(localStorage.getItem('wishlist') || '[]');
        const item = list.find(i => i.name === name);
        const prodInfo = (typeof window.getProductInfo === 'function') ? window.getProductInfo(name) : null;
        const bName = item ? (item.name_bn || (prodInfo ? prodInfo.title_bn : name)) : name;

        list = list.filter(i => i.name !== name);
        localStorage.setItem('wishlist', JSON.stringify(list));
        updateWishlistBadge();
        if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend(false, list.length === 0 ? { force_empty_wishlist: true } : {});
        renderWishlist();
        const toastName = isBangla ? (bName || name) : name;
        showToast(isBangla ? `"${toastName}" পছন্দের তালিকা থেকে মুছে ফেলা হয়েছে` : `Removed "${name}" from wishlist`);
    }

    document.addEventListener('DOMContentLoaded', renderWishlist);
    window.addEventListener('catalogUpdated', renderWishlist);
    window.addEventListener('languageChanged', renderWishlist);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
