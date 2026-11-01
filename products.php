<?php
// products.php
// Dedicated Category, Search, and All Products Listing Page for BazarKinun.com
$pageTitle = 'All Products - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Sub-nav Styling */
    .shop-subnav-wrapper {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        position: sticky;
        top: var(--header-actual-height, 75px);
        z-index: 99;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        transition: top 0.15s ease;
    }

    .shop-subnav-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        gap: 15px;
    }

    .shop-search-form {
        display: flex;
        align-items: stretch;
        border: 1.5px solid var(--primary-color);
        border-radius: 50px;
        background: #ffffff;
        flex: 1;
        max-width: 680px;
        height: 48px;
        position: relative;
        overflow: visible;
    }

    .browse-cat-dropdown-box {
        position: relative;
        height: 100%;
        display: flex;
        align-items: stretch;
    }

    .browse-cat-btn {
        background: #f0fdf4;
        color: var(--primary-color);
        border: none;
        border-right: 1.5px solid #d1fae5;
        border-radius: 50px 0 0 50px;
        padding: 0 16px;
        font-family: var(--font-family);
        font-weight: 700;
        font-size: 13.5px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        white-space: nowrap;
        height: 100%;
        transition: background 0.2s;
    }

    .browse-cat-btn:hover {
        background: #dcfce7;
    }

    .categories-dropdown-menu {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        background: white;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        border: 1px solid #e2e8f0;
        min-width: 240px;
        max-height: 380px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
        padding: 8px 0;
    }

    .categories-dropdown-menu.show {
        display: block;
        animation: dropFade 0.2s ease forwards;
    }

    .categories-dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 600;
        color: #334155;
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }

    .categories-dropdown-menu a:hover,
    .categories-dropdown-menu a.active {
        background: #f0fdf4;
        color: var(--primary-color);
        font-weight: 700;
    }

    .shop-search-input-field {
        flex: 1;
        border: none;
        padding: 0 18px;
        font-family: var(--font-family);
        font-size: 14px;
        outline: none;
        background: transparent;
        color: #1e293b;
        min-width: 0;
    }

    .shop-search-btn {
        background: var(--primary-color);
        border: none;
        border-radius: 0 50px 50px 0;
        padding: 0 20px;
        color: #ffffff;
        font-size: 15px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .shop-search-btn:hover {
        background: var(--primary-hover);
    }

    .shop-nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .shop-nav-links a {
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        transition: color 0.2s;
    }

    .shop-nav-links a:hover,
    .shop-nav-links a.active {
        color: var(--primary-color);
    }

    /* Catalog Page Header */
    .catalog-header-box {
        background: #ffffff;
        border-radius: 20px;
        padding: 22px 28px;
        margin: 24px 0 20px 0;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }

    .catalog-title-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s;
        margin-bottom: 2px;
    }

    .back-link:hover {
        color: var(--primary-color);
    }

    .catalog-title-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .catalog-main-title {
        font-size: 26px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }

    .catalog-count-badge {
        background: rgba(16, 185, 129, 0.12);
        color: var(--primary-color);
        font-weight: 800;
        font-size: 13px;
        padding: 4px 12px;
        border-radius: 20px;
    }

    /* Category Filter Chips / Pills with Arrows */
    .category-pills-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .cat-pill-nav-btn {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 50%;
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 13px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
        flex-shrink: 0;
        z-index: 5;
    }

    .cat-pill-nav-btn:hover {
        background: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        transform: scale(1.06);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }

    .cat-pill-nav-btn:active {
        transform: scale(0.95);
    }

    .category-pills-scroll {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 4px 2px;
        scrollbar-width: none;
        scroll-behavior: smooth;
        flex: 1;
    }

    .category-pills-scroll::-webkit-scrollbar {
        display: none;
    }

    .cat-pill-btn {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        color: #475569;
        padding: 8px 18px;
        border-radius: 50px;
        font-family: var(--font-family);
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .cat-pill-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: #f0fdf4;
    }

    .cat-pill-btn.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* Active Filter Indicator Bar */
    .active-filters-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-tag {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .filter-tag-close {
        cursor: pointer;
        color: #047857;
        font-weight: 900;
    }

    .clear-all-btn {
        background: transparent;
        border: none;
        color: #ef4444;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: underline;
        padding: 4px;
    }

    /* Sort Control */
    .catalog-sort-box {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sort-select {
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        padding: 8px 14px;
        font-family: var(--font-family);
        font-size: 13.5px;
        font-weight: 700;
        color: #334155;
        background: white;
        outline: none;
        cursor: pointer;
    }

    /* Product Grid */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .product-card {
        background: white;
        border-radius: 20px;
        padding: 14px;
        text-align: center;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 1.5px solid #00c675;
        box-sizing: border-box;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 198, 117, 0.15);
        border-color: #00b067;
    }

    /* Quantity Modal */
    .qty-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 15px;
    }

    .qty-modal-backdrop.show {
        display: flex;
        animation: fadeInModal 0.25s ease forwards;
    }

    .qty-modal-card {
        background: white;
        border-radius: 24px;
        width: 100%;
        max-width: 440px;
        padding: 28px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        position: relative;
    }

    @keyframes fadeInModal {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    @media (max-width: 768px) {
        .shop-subnav-container {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .shop-search-form {
            flex: none;
            width: 100%;
            max-width: 100%;
            height: 48px;
        }
        .catalog-header-box {
            flex-direction: column;
            align-items: flex-start;
        }
        .catalog-sort-box {
            width: 100%;
            justify-content: space-between;
        }
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .browse-cat-btn span {
            display: none;
        }
        .browse-cat-btn {
            padding: 0 12px;
        }
    }

    @media (max-width: 480px) {
        .shop-search-form {
            height: 48px;
        }
        .shop-search-input-field {
            font-size: 14px;
            padding: 0 12px;
        }
    }

    @media (max-width: 374px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(134px, 1fr)) !important;
            gap: 8px !important;
        }
        .catalog-sort-box {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }
    }

    @media (min-width: 2560px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)) !important;
            gap: 24px !important;
        }
    }
</style>

<!-- Sub-Navigation Header -->
<header id="shop-subnav" class="shop-subnav-wrapper">
    <div class="container shop-subnav-container">
        <!-- Search with All Categories Dropdown -->
        <form onsubmit="handleSubnavSearch(event)" class="shop-search-form">
            <div class="browse-cat-dropdown-box" id="browse-cat-dropdown-box">
                <button type="button" class="browse-cat-btn" id="browse-cat-btn" onclick="toggleCategoriesMenu(event)" title="All Categories">
                    <i class="fa-solid fa-border-all"></i>
                    <span id="browse-text" class="dynamic-lang" data-en="All Categories" data-bn="সকল ক্যাটাগরি">All Categories</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="categories-dropdown-menu" id="categories-dropdown-menu">
                    <a href="javascript:void(0)" onclick="selectCategory('', 'All Categories', 'সকল ক্যাটাগরি')" class="active">
                        <i class="fa-solid fa-layer-group" style="color: var(--primary-color);"></i>
                        <span class="dynamic-lang" data-en="All Categories" data-bn="সকল ক্যাটাগরি">All Categories</span>
                    </a>
                    <!-- Categories loaded dynamically -->
                </div>
            </div>
            <input type="text" id="shop-search-input" class="shop-search-input-field" placeholder="Search for products...">
            <button type="submit" class="shop-search-btn" title="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <!-- Right Sub-nav Links -->
        <ul class="shop-nav-links">
            <li>
                <a href="shop.php" title="Home">
                    <i class="fa-solid fa-house" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="Home" data-bn="হোম">Home</span>
                </a>
            </li>
            <li>
                <a href="shop.php#hot-deals-section" title="Hot deals">
                    <i class="fa-solid fa-bolt" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="Hot deals" data-bn="হট ডিলস">Hot deals</span>
                </a>
            </li>
            <li>
                <a href="shop.php#promotions-section" title="Promotions">
                    <i class="fa-solid fa-star" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="Promotions" data-bn="প্রমোশন">Promotions</span>
                </a>
            </li>
            <li>
                <a href="shop.php#new-products-section" title="New products">
                    <i class="fa-solid fa-bullhorn" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="New products" data-bn="নতুন পণ্য">New products</span>
                </a>
            </li>
            <li>
                <a href="products.php" class="active" title="All products">
                    <i class="fa-solid fa-basket-shopping" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="All products" data-bn="সকল পণ্য">All products</span>
                </a>
            </li>
        </ul>
    </div>
</header>

<main style="background: #f8fafc; padding-bottom: 60px; min-height: 75vh;">
    <div class="container">
        
        <!-- Catalog Header & Breadcrumb -->
        <div class="catalog-header-box">
            <div class="catalog-title-group">
                <a href="shop.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> <span class="dynamic-lang" data-en="Back to Shop" data-bn="শপে ফিরে যান">Back to Shop</span>
                </a>
                <div class="catalog-title-wrapper">
                    <h1 id="active-category-title" class="catalog-main-title dynamic-lang" data-en="All Products" data-bn="সকল পণ্য">
                        All Products
                    </h1>
                    <span id="product-count-badge" class="catalog-count-badge">0 items</span>
                </div>
            </div>

            <!-- Sorting Dropdown -->
            <div class="catalog-sort-box">
                <label for="catalog-sort" style="font-size: 13px; font-weight: 700; color: #64748b;" class="dynamic-lang" data-en="Sort by:" data-bn="বাছাই:">Sort by:</label>
                <select id="catalog-sort" class="sort-select" onchange="handleSortChange(this.value)">
                    <option value="default" class="dynamic-lang" data-en="Default" data-bn="ফিচার্ড">Default</option>
                    <option value="price_low" class="dynamic-lang" data-en="Price: Low to High" data-bn="দাম: কম থেকে বেশি">Price: Low to High</option>
                    <option value="price_high" class="dynamic-lang" data-en="Price: High to Low" data-bn="দাম: বেশি থেকে কম">Price: High to Low</option>
                    <option value="discount" class="dynamic-lang" data-en="Biggest Discount" data-bn="সর্বোচ্চ ছাড়">Biggest Discount</option>
                </select>
            </div>
        </div>

        <!-- Category Horizontal Filter Pills with Navigation Arrows -->
        <div class="category-pills-wrapper">
            <button type="button" class="cat-pill-nav-btn prev" id="cat-pills-prev" onclick="scrollCatPills(-240)" title="Previous categories">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="category-pills-scroll" id="category-pills-container">
                <button class="cat-pill-btn active" data-cat-id="" onclick="selectCategory('', 'All Categories', 'সকল ক্যাটাগরি')">
                    <i class="fa-solid fa-layer-group"></i>
                    <span class="dynamic-lang" data-en="All Categories" data-bn="সকল ক্যাটাগরি">All Categories</span>
                </button>
                <!-- Rendered dynamically -->
            </div>
            <button type="button" class="cat-pill-nav-btn next" id="cat-pills-next" onclick="scrollCatPills(240)" title="Next categories">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Active Filter Indicator Tags -->
        <div class="active-filters-row" id="active-filters-row" style="display: none;">
            <div id="filter-tag-container"></div>
            <button class="clear-all-btn dynamic-lang" onclick="clearAllFilters()" data-en="Clear All Filters" data-bn="সব ফিল্টার মুছুন">Clear All</button>
        </div>

        <!-- Products Grid -->
        <div class="product-grid" id="all-products-grid">
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #64748b;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 32px; color: var(--primary-color); margin-bottom: 12px;"></i>
                <p style="font-weight: 600;">Loading products catalog...</p>
            </div>
        </div>

    </div>
</main>

<!-- Quantity & Add to Cart Modal -->
<div class="qty-modal-backdrop" id="quantity-modal" onclick="closeQuantityModal(event)">
    <div class="qty-modal-card" onclick="event.stopPropagation()">
        <button type="button" onclick="closeQuantityModal()" style="position: absolute; top: 18px; right: 18px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #64748b; font-size: 14px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px;">
            <img id="modal-prod-img" src="" alt="Product" style="width: 75px; height: 75px; border-radius: 14px; object-fit: cover; border: 1px solid #e2e8f0;">
            <div>
                <h3 id="modal-prod-title" style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: #0f172a;">Product Title</h3>
                <div style="display: flex; align-items: baseline; gap: 8px;">
                    <span id="modal-prod-price" style="font-size: 20px; font-weight: 900; color: var(--primary-color);">৳0</span>
                    <span id="modal-prod-oldprice" style="font-size: 14px; color: #94a3b8; text-decoration: line-through;">৳0</span>
                </div>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 16px; border-radius: 14px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between;">
            <span style="font-weight: 700; color: #334155; font-size: 14px;" class="dynamic-lang" data-en="Select Quantity:" data-bn="পরিমাণ নির্বাচন করুন:">Select Quantity:</span>
            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button" onclick="adjustModalQty(-1)" style="width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: white; font-size: 16px; font-weight: 700; cursor: pointer; color: #334155;">-</button>
                <span id="modal-qty-val" style="font-size: 18px; font-weight: 900; color: #0f172a; min-width: 24px; text-align: center;">1</span>
                <button type="button" onclick="adjustModalQty(1)" style="width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid var(--primary-color); background: #f0fdf4; font-size: 16px; font-weight: 700; cursor: pointer; color: var(--primary-color);">+</button>
            </div>
        </div>

        <button type="button" onclick="confirmAddToCartFromModal()" style="width: 100%; background: var(--primary-color); color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35); display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="fa-solid fa-cart-shopping"></i> <span class="dynamic-lang" data-en="Add to Cart" data-bn="কার্টে যোগ করুন">Add to Cart</span> • <span id="modal-total-price">৳0</span>
        </button>
    </div>
</div>

<script>
    let allProducts = [];
    let allCategories = [];
    let currentCategory = '';
    let currentSearch = '';
    let currentSort = 'default';

    let activeModalItem = {
        name: '',
        price: 0,
        oldPrice: 0,
        image: '',
        qty: 1
    };

    function toggleCategoriesMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('categories-dropdown-menu');
        menu.classList.toggle('show');
    }

    document.addEventListener('click', (e) => {
        const box = document.getElementById('browse-cat-dropdown-box');
        const menu = document.getElementById('categories-dropdown-menu');
        if (box && !box.contains(e.target)) {
            menu.classList.remove('show');
        }
    });

    function handleSubnavSearch(e) {
        e.preventDefault();
        const input = document.getElementById('shop-search-input');
        currentSearch = input.value.trim().toLowerCase();
        updateUrlParams();
        renderProducts();
    }

    function scrollCatPills(offset) {
        const container = document.getElementById('category-pills-container');
        if (container) {
            container.scrollBy({ left: offset, behavior: 'smooth' });
        }
    }

    function handleSortChange(sortVal) {
        currentSort = sortVal;
        renderProducts();
    }

    function selectCategory(catId, catName, catNameBn) {
        currentCategory = catId ? catId.toString() : '';
        
        // Update pills
        document.querySelectorAll('.cat-pill-btn').forEach(btn => {
            if (btn.getAttribute('data-cat-id') === currentCategory) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update dropdown active
        document.querySelectorAll('.categories-dropdown-menu a').forEach(a => {
            if (a.getAttribute('data-cat-id') === currentCategory) {
                a.classList.add('active');
            } else {
                a.classList.remove('active');
            }
        });

        const browseText = document.getElementById('browse-text');
        if (browseText) {
            browseText.innerText = isBangla && catNameBn ? catNameBn : (catName || 'All Categories');
        }

        const menu = document.getElementById('categories-dropdown-menu');
        if (menu) menu.classList.remove('show');

        updateUrlParams();
        renderProducts();
    }

    function clearAllFilters() {
        currentCategory = '';
        currentSearch = '';
        const searchInput = document.getElementById('shop-search-input');
        if (searchInput) searchInput.value = '';
        selectCategory('', 'All Categories', 'সকল ক্যাটাগরি');
    }

    function updateUrlParams() {
        const params = new URLSearchParams();
        if (currentCategory) params.set('cat', currentCategory);
        if (currentSearch) params.set('q', currentSearch);
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.pushState(null, '', newUrl);
    }

    function createProductCard(prod, customBadge = null) {
        const price = prod.price;
        const oldPrice = prod.old_price ? prod.old_price : (price + Math.round(price * 0.15));

        let badgeHtml = '';
        if (prod.is_bogo) {
            badgeHtml = `<span style="position: absolute; top: 8px; left: 8px; background: #eab308; color: #713f12; font-size: 10px; font-weight: 900; padding: 3px 8px; border-radius: 12px; z-index: 2; box-shadow: 0 2px 6px rgba(234, 179, 8, 0.4);"><i class="fa-solid fa-gift"></i> ${isBangla ? (prod.bogo_text_bn || '১+১ ফ্রি') : (prod.bogo_text || 'BOGO FREE')}</span>`;
        } else if (prod.discount_badge) {
            badgeHtml = `<span style="position: absolute; top: 8px; left: 8px; background: #ff0055; color: white; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; z-index: 2;">${isBangla && prod.discount_badge_bn ? prod.discount_badge_bn : prod.discount_badge}</span>`;
        } else {
            const activeBadge = customBadge || (prod.is_hot == 1 ? 'Hot' : (prod.discount_percent > 0 ? `-${prod.discount_percent}%` : ''));
            if (activeBadge === 'Hot') {
                badgeHtml = `<span style="position: absolute; top: 8px; left: 8px; background: #ff0055; color: white; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; z-index: 2;">Hot</span>`;
            } else if (activeBadge === 'New') {
                badgeHtml = `<span style="position: absolute; top: 8px; left: 8px; background: #10b981; color: white; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; z-index: 2;">New</span>`;
            } else if (activeBadge === 'Sale') {
                badgeHtml = `<span style="position: absolute; top: 8px; left: 8px; background: #f59e0b; color: white; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; z-index: 2;">Sale</span>`;
            } else if (activeBadge) {
                badgeHtml = `<span style="position: absolute; top: 8px; left: 8px; background: #ff0055; color: white; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; z-index: 2;">${activeBadge}</span>`;
            }
        }

        const userWishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        const isWish = userWishlist.some(w => w.name === prod.title);

        const safeTitle = (prod.title || '').replace(/'/g, "\\'");
        const safeTitleBn = (prod.title_bn || prod.title || '').replace(/'/g, "\\'");
        const safeCat = (prod.category_name || '').replace(/'/g, "\\'");
        const safeCatBn = (prod.category_name_bn || prod.category_name || '').replace(/'/g, "\\'");
        const safePoriman = (prod.poriman || '').replace(/'/g, "\\'");
        const safePorimanBn = (prod.poriman_bn || prod.poriman || '').replace(/'/g, "\\'");

        const currentQty = (typeof window.getProductCartQty === 'function') ? window.getProductCartQty(prod.title) : 0;
        const btnText = currentQty > 0 ? `Add ${currentQty}` : `Add`;

        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <div style="position: relative; overflow: hidden; border-radius: 14px; margin-bottom: 10px; background: #f8fafc; height: 155px; width: 100%;">
                <img src="${prod.image}" alt="${prod.title}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 14px; display: block;" onerror="this.src='https://placehold.co/200x155?text=Grocery';">
                ${badgeHtml}
            </div>
            <div style="text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: flex-start;">
                <div style="display: flex; justify-content: center; align-items: center; gap: 6px; margin-bottom: 2px;">
                    <span style="font-size: 10px; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 1px 6px; border-radius: 4px;">${prod.product_code || ''}</span>
                    <span class="product-cat dynamic-lang" data-en="${prod.category_name || ''}" data-bn="${prod.category_name_bn || prod.category_name || ''}" style="color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                        ${isBangla && prod.category_name_bn ? prod.category_name_bn : (prod.category_name || '')}
                    </span>
                </div>
                <h4 class="product-title dynamic-lang" data-en="${prod.title || ''}" data-bn="${prod.title_bn || prod.title || ''}" style="font-size: 15px; font-weight: 900; margin: 0 0 5px 0; color: #0f172a; line-height: 1.3;">
                    ${isBangla && prod.title_bn ? prod.title_bn : (prod.title || '')}
                </h4>
                <div class="product-brand dynamic-lang" data-en="${prod.poriman || ''}" data-bn="${prod.poriman_bn || prod.poriman || ''}" style="font-size: 13px; color: #00c675; font-weight: 800; margin-bottom: 14px;">
                    ${isBangla && prod.poriman_bn ? prod.poriman_bn : (prod.poriman || '')}
                </div>
            </div>
            <div class="product-bottom" style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; gap: 8px;">
                <div class="price" style="text-align: left;">
                    <span class="current-price" style="color: #00c675; font-weight: 900; font-size: 19px; display: block; line-height: 1.1;">৳${price}</span>
                    ${oldPrice > price ? `<span class="old-price" style="color: #94a3b8; text-decoration: line-through; font-size: 12px; font-weight: 600;">৳${oldPrice}</span>` : ''}
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button class="wishlist-btn ${isWish ? 'active' : ''}" data-name="${prod.title}" onclick="toggleWishlist(this, '${safeTitle}', ${price}, ${oldPrice}, '${prod.image}', '${safeTitleBn}', '${safeCat}', '${safeCatBn}', '${safePoriman}', '${safePorimanBn}')" style="background: transparent; border: none; font-size: 22px; cursor: pointer; color: ${isWish ? '#ef4444' : '#94a3b8'}; transition: transform 0.2s, color 0.2s; display: flex; align-items: center; justify-content: center; padding: 4px; outline: none;" title="Wishlist">
                        <i class="fa-${isWish ? 'solid' : 'regular'} fa-heart"></i>
                    </button>
                    <button class="add-btn ${currentQty > 0 ? 'has-items' : ''}" data-product-name="${safeTitle}" onclick="quickAddToCart(this, event, '${safeTitle}', ${price}, ${oldPrice}, '${prod.image}', '${safeTitleBn}', '${safeCat}', '${safeCatBn}', '${safePoriman}', '${safePorimanBn}')" style="background: #00c675; color: white; border: none; padding: 9px 18px; border-radius: 25px; cursor: pointer; transition: 0.2s; font-weight: 800; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0, 198, 117, 0.35);">
                        <i class="fa-solid fa-cart-shopping"></i> ${btnText}
                    </button>
                </div>
            </div>
        `;
        return card;
    }

    function renderProducts() {
        const grid = document.getElementById('all-products-grid');
        if (!grid) return;
        grid.innerHTML = '';

        if (window.inMemCatalog && window.inMemCatalog.length > 0) {
            allProducts = window.inMemCatalog;
        }

        let filtered = allProducts.filter(p => {
            let matchesCategory = !currentCategory || (p.category_id.toString() === currentCategory.toString());
            let matchesSearch = !currentSearch || 
                p.title.toLowerCase().includes(currentSearch) || 
                (p.title_bn && p.title_bn.toLowerCase().includes(currentSearch)) ||
                (p.category_name && p.category_name.toLowerCase().includes(currentSearch));
            return matchesCategory && matchesSearch;
        });

        // Apply sorting
        if (currentSort === 'price_low') {
            filtered.sort((a, b) => a.price - b.price);
        } else if (currentSort === 'price_high') {
            filtered.sort((a, b) => b.price - a.price);
        } else if (currentSort === 'discount') {
            filtered.sort((a, b) => (b.discount_percent || 0) - (a.discount_percent || 0));
        }

        // Update Title & Badge
        const titleEl = document.getElementById('active-category-title');
        const countBadge = document.getElementById('product-count-badge');
        
        let titleText = 'All Products';
        let titleTextBn = 'সকল পণ্য';

        if (currentSearch) {
            titleText = `Search: "${currentSearch}"`;
            titleTextBn = `অনুসন্ধান: "${currentSearch}"`;
        } else if (currentCategory) {
            const catObj = allCategories.find(c => c.id.toString() === currentCategory);
            if (catObj) {
                titleText = catObj.name;
                titleTextBn = catObj.name_bn || catObj.name;
            }
        }

        if (titleEl) {
            titleEl.innerText = isBangla ? titleTextBn : titleText;
        }

        if (countBadge) {
            countBadge.innerText = `${filtered.length} item${filtered.length !== 1 ? 's' : ''}`;
        }

        // Active Filter Indicators
        const filterRow = document.getElementById('active-filters-row');
        const tagContainer = document.getElementById('filter-tag-container');
        if (filterRow && tagContainer) {
            tagContainer.innerHTML = '';
            let hasActive = false;

            if (currentCategory) {
                hasActive = true;
                const catObj = allCategories.find(c => c.id.toString() === currentCategory);
                const tag = document.createElement('span');
                tag.className = 'filter-tag';
                tag.innerHTML = `Category: ${catObj ? (isBangla && catObj.name_bn ? catObj.name_bn : catObj.name) : currentCategory} <span class="filter-tag-close" onclick="selectCategory('', 'All Categories', 'সকল ক্যাটাগরি')">✕</span>`;
                tagContainer.appendChild(tag);
            }

            if (currentSearch) {
                hasActive = true;
                const tag = document.createElement('span');
                tag.className = 'filter-tag';
                tag.innerHTML = `Search: "${currentSearch}" <span class="filter-tag-close" onclick="clearSearchFilter()">✕</span>`;
                tagContainer.appendChild(tag);
            }

            filterRow.style.display = hasActive ? 'flex' : 'none';
        }

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 60px 20px; background: white; border-radius: 20px; border: 1.5px dashed #cbd5e1; margin-top: 10px;">
                    <i class="fa-solid fa-basket-shopping" style="font-size: 42px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
                    <h3 style="font-weight: 800; font-size: 18px; color: #334155; margin: 0 0 6px 0;">No matching products found</h3>
                    <p style="font-size: 14px; margin: 0 0 16px 0; color: #64748b;">Try adjusting your search terms or browse our categories.</p>
                    <button onclick="clearAllFilters()" style="background: var(--primary-color); color: white; border: none; padding: 10px 24px; border-radius: 25px; font-weight: 800; font-size: 13.5px; cursor: pointer;">Show All Products</button>
                </div>
            `;
            return;
        }

        filtered.forEach(prod => {
            grid.appendChild(createProductCard(prod));
        });
    }

    function clearSearchFilter() {
        currentSearch = '';
        const searchInput = document.getElementById('shop-search-input');
        if (searchInput) searchInput.value = '';
        updateUrlParams();
        renderProducts();
    }

    // Direct Add to Cart
    function openQuantityModal(btn, event, title, price, oldPrice, image, titleBn = '', catName = '', catBn = '', poriman = '', porimanBn = '') {
        if (typeof window.quickAddToCart === 'function') {
            window.quickAddToCart(btn, event, title, price, oldPrice, image, titleBn, catName, catBn, poriman, porimanBn);
        }
    }

    function closeQuantityModal(e) {
        if (!e || e.target.id === 'quantity-modal' || e.target.closest('button')) {
            document.getElementById('quantity-modal').classList.remove('show');
        }
    }

    function adjustModalQty(delta) {
        activeModalItem.qty = Math.max(1, activeModalItem.qty + delta);
        document.getElementById('modal-qty-val').innerText = activeModalItem.qty;
        document.getElementById('modal-total-price').innerText = `৳${activeModalItem.qty * activeModalItem.price}`;
    }

    function confirmAddToCartFromModal() {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let ex = cart.find(i => i.name === activeModalItem.name);
        if (ex) {
            ex.quantity += activeModalItem.qty;
            if (!ex.name_bn && activeModalItem.name_bn) ex.name_bn = activeModalItem.name_bn;
            if (!ex.category_name && activeModalItem.category_name) ex.category_name = activeModalItem.category_name;
            if (!ex.category_name_bn && activeModalItem.category_name_bn) ex.category_name_bn = activeModalItem.category_name_bn;
            if (!ex.poriman && activeModalItem.poriman) ex.poriman = activeModalItem.poriman;
            if (!ex.poriman_bn && activeModalItem.poriman_bn) ex.poriman_bn = activeModalItem.poriman_bn;
        } else {
            cart.push({
                name: activeModalItem.name,
                name_bn: activeModalItem.name_bn,
                price: activeModalItem.price,
                oldPrice: activeModalItem.oldPrice,
                image: activeModalItem.image,
                img: activeModalItem.image,
                quantity: activeModalItem.qty,
                category_name: activeModalItem.category_name,
                category_name_bn: activeModalItem.category_name_bn,
                poriman: activeModalItem.poriman,
                poriman_bn: activeModalItem.poriman_bn
            });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        if (typeof updateCartBadge === 'function') updateCartBadge();
        if (typeof syncUserStateToBackend === 'function') syncUserStateToBackend();
        const toastName = isBangla ? (activeModalItem.name_bn || activeModalItem.name) : activeModalItem.name;
        showToast(isBangla ? `"${toastName}" কার্টে যোগ করা হয়েছে!` : `${activeModalItem.qty}x ${activeModalItem.name} added to cart!`);
        document.getElementById('quantity-modal').classList.remove('show');
    }

    function fetchProductsData(force = false) {
        const userLoc = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userLocation') || '').trim();

        if (!force && window.inMemCatalog && window.inMemCatalog.length > 0) {
            allProducts = window.inMemCatalog;
            renderProducts();
            return;
        }

        fetch(`api/store-data.php?location=${encodeURIComponent(userLoc)}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    allProducts = data.products || [];
                    allCategories = data.categories || [];
                    window.inMemCatalog = allProducts;
                    sessionStorage.setItem('storeProductsCatalog', JSON.stringify(allProducts));
                    if (data.discount_rules) sessionStorage.setItem('storeDiscountRules', JSON.stringify(data.discount_rules));

                    const pillsContainer = document.getElementById('category-pills-container');
                    const dropdownMenu = document.getElementById('categories-dropdown-menu');

                    if (pillsContainer) pillsContainer.innerHTML = '';
                    if (dropdownMenu) dropdownMenu.innerHTML = '';

                    allCategories.forEach(cat => {
                        // Horizontal Pill Button
                        if (pillsContainer) {
                            const pill = document.createElement('button');
                            pill.className = 'cat-pill-btn' + (currentCategory === cat.id.toString() ? ' active' : '');
                            pill.setAttribute('data-cat-id', cat.id);
                            pill.onclick = () => selectCategory(cat.id, cat.name, cat.name_bn);
                            pill.innerHTML = `
                                <span class="dynamic-lang" data-en="${cat.name}" data-bn="${cat.name_bn || cat.name}">
                                    ${isBangla && cat.name_bn ? cat.name_bn : cat.name}
                                </span>
                            `;
                            pillsContainer.appendChild(pill);
                        }

                        // Dropdown Item
                        if (dropdownMenu) {
                            const a = document.createElement('a');
                            a.href = 'javascript:void(0)';
                            a.setAttribute('data-cat-id', cat.id);
                            a.onclick = () => selectCategory(cat.id, cat.name, cat.name_bn);
                            a.innerHTML = `
                                <span class="dynamic-lang" data-en="${cat.name}" data-bn="${cat.name_bn || cat.name}">
                                    ${isBangla && cat.name_bn ? cat.name_bn : cat.name}
                                </span>
                            `;
                            dropdownMenu.appendChild(a);
                        }
                    });

                    // Set initial active category
                    if (currentCategory) {
                        const activeCat = allCategories.find(c => c.id.toString() === currentCategory);
                        if (activeCat) {
                            selectCategory(activeCat.id, activeCat.name, activeCat.name_bn);
                        }
                    }

                    renderProducts();
                }
            })
            .catch(err => {
                console.error("Store data error:", err);
                const grid = document.getElementById('all-products-grid');
                if (grid) grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: red;">Failed to load products.</p>';
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Sticky dynamic position check for subnav
        const topHeader = document.querySelector('header.header:not(#shop-subnav)');
        const shopNav = document.getElementById('shop-subnav');
        if (topHeader && shopNav) {
            shopNav.style.top = topHeader.offsetHeight + 'px';
        }

        // Parse URL Parameters
        const urlParams = new URLSearchParams(window.location.search);
        currentCategory = urlParams.get('cat') || '';
        currentSearch = (urlParams.get('q') || '').trim().toLowerCase();

        const searchInput = document.getElementById('shop-search-input');
        if (searchInput && currentSearch) {
            searchInput.value = currentSearch;
        }

        // Fetch Store Data with Location
        fetchProductsData();

        // Live filter while typing
        document.getElementById('shop-search-input')?.addEventListener('input', (e) => {
            currentSearch = e.target.value.trim().toLowerCase();
            updateUrlParams();
            renderProducts();
        });

        // Re-render when language toggle button is clicked
        document.getElementById('lang-btn')?.addEventListener('click', () => {
            setTimeout(() => renderProducts(), 50);
        });
        document.getElementById('mobile-lang-btn')?.addEventListener('click', () => {
            setTimeout(() => renderProducts(), 50);
        });

        // Re-render when catalog/location pricing is updated
        window.addEventListener('catalogUpdated', (e) => {
            if (e.detail && e.detail.products) {
                allProducts = e.detail.products;
            }
            renderProducts();
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
