<?php
// shop.php
$pageTitle = 'Online Grocery Shop - BazarKinun.com';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    body {
        background-color: #f1f5f9 !important;
    }

    /* Sub-navbar Styling */
    .shop-subnav-wrapper {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: var(--header-actual-height, 75px);
        z-index: 990;
        padding: 10px 0;
        transition: top 0.15s ease;
    }

    .shop-subnav-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
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
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
    }

    /* All Categories Dropdown inside Search */
    .browse-cat-dropdown-box {
        position: relative;
        height: 100%;
    }

    .browse-cat-btn {
        background: var(--primary-color);
        color: #ffffff;
        border: none;
        border-radius: 50px 0 0 50px !important;
        padding: 0 18px;
        font-family: var(--font-family);
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        height: 100%;
        white-space: nowrap;
        transition: background 0.2s;
    }

    .browse-cat-btn:hover {
        background: var(--primary-hover);
    }

    .categories-dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        min-width: 220px;
        max-height: 380px;
        overflow-y: auto;
        z-index: 1050;
        padding: 8px 0;
    }

    .categories-dropdown-menu.show {
        display: block;
    }

    .categories-dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 18px;
        color: #334155;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 600;
        transition: background 0.15s, color 0.15s;
    }

    .categories-dropdown-menu a:hover,
    .categories-dropdown-menu a.active {
        background: #ecfdf5;
        color: var(--primary-color);
    }

    .shop-search-input-field {
        flex: 1;
        border: none;
        outline: none;
        padding: 0 16px;
        font-size: 14px;
        font-family: var(--font-family);
        background: transparent;
        color: #0f172a;
    }

    .shop-search-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0 20px;
        border-radius: 0 50px 50px 0;
        cursor: pointer;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .shop-search-btn:hover {
        background: var(--primary-hover);
    }

    /* Subnav Links on right */
    .shop-nav-links {
        display: flex;
        align-items: center;
        gap: 22px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .shop-nav-links a {
        display: flex;
        align-items: center;
        gap: 7px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        transition: color 0.2s;
    }

    .shop-nav-links a:hover,
    .shop-nav-links a.active {
        color: var(--primary-color);
    }

    /* Hero Banner */
    .shop-hero-section {
        margin: 24px 0 36px 0;
    }

    .shop-hero-card {
        background: #a7f3d0;
        border-radius: 30px;
        padding: 0 0 0 80px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 32px rgba(16, 185, 129, 0.14);
        min-height: 430px;
        max-height: 445px;
    }

    .shop-hero-content {
        max-width: 580px;
        padding: 30px 0;
        flex: 1.2;
        z-index: 2;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .shop-hero-title-bn {
        font-size: 44px;
        font-weight: 900;
        color: #0f172a;
        margin: 0 0 10px 0;
        line-height: 1.2;
        letter-spacing: -0.5px;
    }

    .shop-hero-title-en {
        font-size: 44px;
        font-weight: 850;
        color: #064e3b;
        margin: 0 0 28px 0;
        line-height: 1.2;
        letter-spacing: -0.5px;
    }

    .shop-start-shopping-btn {
        background-color: #059669;
        color: #ffffff;
        border: none;
        padding: 14px 32px;
        border-radius: 50px;
        font-family: var(--font-family);
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 6px 20px rgba(5, 150, 105, 0.38);
        transition: transform 0.2s, background 0.2s;
        width: fit-content;
        margin-top: 6px;
    }

    .shop-start-shopping-btn:hover {
        background-color: #047857;
        transform: translateY(-2px);
    }

    .shop-hero-image-wrapper {
        flex: 1.15;
        align-self: stretch;
        height: 100%;
        display: flex;
        align-items: stretch;
        justify-content: flex-end;
        z-index: 1;
        position: relative;
        overflow: hidden;
        padding-bottom: 22px;
    }

    @keyframes autoFloatUpDown {
        0%, 100% {
            transform: translateX(15px) translateY(0);
        }
        50% {
            transform: translateX(15px) translateY(-14px);
        }
    }

    .shop-hero-img {
        width: 100%;
        height: 100%;
        min-height: 100%;
        object-fit: cover;
        object-position: right 100%;
        border-radius: 0 30px 30px 0;
        display: block;
        transform: translateX(15px);
        animation: autoFloatUpDown 5.2s ease-in-out infinite;
        will-change: transform;
    }

    /* Explore Categories Section */
    .explore-categories-section {
        margin-bottom: 35px;
    }

    .categories-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .categories-title {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .cat-arrows-box {
        display: flex;
        gap: 10px;
    }

    .cat-nav-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }

    .cat-nav-btn:hover {
        background: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
    }

    .categories-carousel-track {
        display: flex;
        gap: 18px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 8px 4px 16px 4px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .categories-carousel-track::-webkit-scrollbar {
        display: none;
    }

    .cat-item-card {
        background: #ffffff;
        border: 1.5px solid #f1f5f9;
        border-radius: 20px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
        padding: 20px 12px 18px 12px;
        min-height: 170px;
        flex: 0 0 135px;
        max-width: 145px;
        text-align: center;
        cursor: pointer;
        transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        user-select: none;
    }

    .cat-item-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(16, 185, 129, 0.15);
        border-color: var(--primary-color);
    }

    .cat-item-card.active {
        border-color: var(--primary-color);
        background: #f0fdf4;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.35);
    }

    .cat-item-img {
        width: 92px;
        height: 82px;
        object-fit: contain;
        border-radius: 12px;
        margin-bottom: 12px;
        background: transparent;
    }

    .cat-item-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.3;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Product Grid Styling */
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

    .featured-products-track {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 6px 4px 18px 4px;
        scrollbar-width: none;
    }

    .featured-products-track::-webkit-scrollbar {
        display: none;
    }

    .featured-products-track .product-card {
        flex: 0 0 230px !important;
        width: 230px !important;
        max-width: 230px !important;
        min-width: 230px !important;
    }

    /* Responsive Breakpoints */
    @media (max-width: 992px) {
        .shop-hero-card {
            padding: 0 0 0 36px;
            min-height: 370px;
            max-height: 395px;
        }
        .shop-hero-title-bn {
            font-size: 35px;
        }
        .shop-hero-title-en {
            font-size: 35px;
            margin-bottom: 16px;
        }
        .shop-hero-img {
            min-height: 370px;
            max-height: 395px;
        }
        .shop-subnav-container {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .shop-search-form {
            flex: none;
            width: 100%;
            max-width: 100%;
            height: 48px;
        }
        .shop-nav-links {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 4px;
        }
    }

    @media (max-width: 640px) {
        .shop-hero-card {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px 0 20px;
            min-height: auto;
            max-height: none;
            border-radius: 24px;
        }
        .shop-hero-content {
            padding: 0 0 20px 0;
        }
        .shop-hero-title-bn {
            font-size: 26px;
        }
        .shop-hero-title-en {
            font-size: 26px;
            margin-bottom: 18px;
        }
        .shop-hero-img {
            min-height: 250px;
            max-height: 300px;
            border-radius: 0 0 24px 24px;
        }
        .browse-cat-btn span {
            display: none;
        }
        .browse-cat-btn {
            padding: 0 14px;
        }
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
    }

    @media (max-width: 480px) {
        .shop-search-form {
            height: 48px;
        }
        .browse-cat-btn {
            padding: 0 14px;
        }
        .shop-search-input-field {
            padding: 0 12px;
            font-size: 14px;
        }
        .shop-search-btn {
            padding: 0 16px;
        }
        .featured-products-track .product-card {
            flex: 0 0 170px !important;
            width: 170px !important;
            min-width: 170px !important;
            max-width: 170px !important;
            padding: 12px 10px;
        }
        .shop-hero-card {
            padding: 20px 14px 0 14px;
        }
        .shop-hero-title-bn, .shop-hero-title-en {
            font-size: 22px;
        }
        .shop-hero-img {
            min-height: 180px;
            max-height: 220px;
        }
    }

    @media (max-width: 374px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(134px, 1fr)) !important;
            gap: 8px !important;
        }
        .featured-products-track .product-card {
            flex: 0 0 150px !important;
            width: 150px !important;
            min-width: 150px !important;
            max-width: 150px !important;
            padding: 10px 8px;
        }
        .shop-hero-title-bn, .shop-hero-title-en {
            font-size: 19px;
            margin-bottom: 12px;
        }
        .shop-hero-img {
            min-height: 150px;
            max-height: 180px;
        }
    }

    @media (min-width: 2560px) {
        .shop-hero-card {
            min-height: 480px;
            max-height: 520px;
            padding: 0 0 0 60px;
        }
        .shop-hero-title-bn, .shop-hero-title-en {
            font-size: 48px;
        }
        .shop-hero-img {
            min-height: 480px;
            max-height: 520px;
        }
        .featured-products-track .product-card {
            flex: 0 0 280px !important;
            width: 280px !important;
            min-width: 280px !important;
            max-width: 280px !important;
        }
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)) !important;
            gap: 24px !important;
        }
    }

    /* Customer Reviews Marquee Section */
    .shop-reviews-section {
        margin: 48px 0 15px 0;
        overflow: hidden;
    }

    .shop-reviews-header {
        margin-bottom: 22px;
    }

    .shop-reviews-title {
        font-size: 28px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .reviews-marquee-box {
        position: relative;
        width: 100%;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 8px 4px 24px 4px;
    }

    .reviews-marquee-box::-webkit-scrollbar {
        display: none;
    }

    .reviews-marquee-track {
        display: flex;
        gap: 24px;
        width: max-content;
        transition: transform 0.4s ease;
    }

    .customer-review-card {
        background: #ffffff;
        border-radius: 22px;
        padding: 24px 25px;
        width: 295px;
        min-width: 295px;
        max-width: 295px;
        flex-shrink: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1.5px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        box-sizing: border-box;
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }

    .customer-review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }

    .review-stars-row {
        color: #f59e0b;
        font-size: 18px;
        display: flex;
        gap: 5px;
        margin-bottom: 14px;
    }

    .review-author-name {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 10px 0;
        line-height: 1.25;
    }

    .review-quote-text {
        font-size: 15px;
        color: #475569;
        line-height: 1.6;
        margin: 0;
        font-style: italic;
    }
</style>

<!-- Sub-navbar for Shop Page -->
<header id="shop-subnav" class="shop-subnav-wrapper">
    <div class="container shop-subnav-container">
        <!-- Search with All Categories Dropdown -->
        <form action="products.php" method="GET" onsubmit="handleSubnavSearch(event)" class="shop-search-form">
            <div class="browse-cat-dropdown-box" id="browse-cat-dropdown-box">
                <button type="button" class="browse-cat-btn" id="browse-cat-btn" onclick="toggleCategoriesMenu(event)" title="All Categories">
                    <i class="fa-solid fa-border-all"></i>
                    <span id="browse-text" class="dynamic-lang" data-en="All Categories" data-bn="সকল ক্যাটাগরি">All Categories</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="categories-dropdown-menu" id="categories-dropdown-menu">
                    <a href="products.php" class="active">
                        <i class="fa-solid fa-layer-group" style="color: var(--primary-color);"></i>
                        <span class="dynamic-lang" data-en="All Categories" data-bn="সকল ক্যাটাগরি">All Categories</span>
                    </a>
                    <!-- Categories loaded dynamically -->
                </div>
            </div>
            <input type="text" name="q" id="shop-search-input" class="shop-search-input-field" placeholder="Search for products...">
            <button type="submit" class="shop-search-btn" title="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <!-- Right Sub-nav Links -->
        <ul class="shop-nav-links">
            <li>
                <a href="javascript:void(0)" onclick="scrollToHero()" title="Home">
                    <i class="fa-solid fa-house" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="Home" data-bn="হোম">Home</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="scrollToSection('hot-deals-section')" title="Hot deals">
                    <i class="fa-solid fa-bolt" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="Hot deals" data-bn="হট ডিলস">Hot deals</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="scrollToSection('promotions-section')" title="Promotions">
                    <i class="fa-solid fa-star" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="Promotions" data-bn="প্রমোশন">Promotions</span>
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="scrollToSection('new-products-section')" title="New products">
                    <i class="fa-solid fa-bullhorn" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="New products" data-bn="নতুন পণ্য">New products</span>
                </a>
            </li>
            <li>
                <a href="products.php" title="All products">
                    <i class="fa-solid fa-basket-shopping" style="color: var(--primary-color);"></i>
                    <span class="dynamic-lang" data-en="All products" data-bn="সকল পণ্য">All products</span>
                </a>
            </li>
        </ul>
    </div>
</header>

<main style="background: #f1f5f9; padding-bottom: 50px;">
    <div class="container">
        <!-- Hero Section -->
        <section class="shop-hero-section" id="hero-banner-section">
            <div class="shop-hero-card">
                <div class="shop-hero-content">
                    <h1 class="shop-hero-title-bn">
                        বাজারে নয়, <span style="color: #059669;">বাজার</span><br>আসবে আপনার ঘরে।
                    </h1>
                    <h2 class="shop-hero-title-en">
                        Don't miss our daily<br>amazing deals.
                    </h2>
                    <a href="products.php" class="shop-start-shopping-btn" style="text-decoration: none;">
                        <span class="dynamic-lang" data-en="Start Shopping" data-bn="শপিং শুরু করুন">Start Shopping</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="shop-hero-image-wrapper">
                    <img src="assets/hero_groceries_clean.png" alt="Fresh Grocery Delivery" class="shop-hero-img" onerror="this.src='assets/hero_groceries.jpg';">
                </div>
            </div>
        </section>

        <!-- Explore Categories Section (Immediately after Hero Section) -->
        <section class="explore-categories-section" id="explore-categories-section" style="margin-bottom: 35px; scroll-margin-top: 130px;">
            <div class="categories-section-header">
                <h3 class="categories-title dynamic-lang" data-en="Explore Categories" data-bn="ক্যাটাগরি সমূহ">Explore Categories</h3>
                <div class="cat-arrows-box">
                    <button class="cat-nav-btn" id="cat-prev-btn" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="cat-nav-btn" id="cat-next-btn" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="categories-carousel-track" id="categories-carousel">
                <!-- Dynamically loaded categories with images -->
            </div>
        </section>

        <!-- 1. Hot Deals Section -->
        <section id="hot-deals-section" class="explore-categories-section" style="margin-bottom: 40px; scroll-margin-top: 130px;">
            <div class="categories-section-header">
                <h3 class="categories-title dynamic-lang" data-en="Hot deals" data-bn="হট ডিলস">Hot deals</h3>
                <div class="cat-arrows-box">
                    <button class="cat-nav-btn" onclick="scrollCarousel('hot-deals-track', -300)" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="cat-nav-btn" onclick="scrollCarousel('hot-deals-track', 300)" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="featured-products-track" id="hot-deals-track">
                <!-- Rendered dynamically -->
            </div>
        </section>

        <!-- 2. Promotions Section -->
        <section id="promotions-section" class="explore-categories-section" style="margin-bottom: 40px; scroll-margin-top: 130px;">
            <div class="categories-section-header">
                <h3 class="categories-title dynamic-lang" data-en="Promotions" data-bn="প্রমোশন">Promotions</h3>
                <div class="cat-arrows-box">
                    <button class="cat-nav-btn" onclick="scrollCarousel('promotions-track', -300)" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="cat-nav-btn" onclick="scrollCarousel('promotions-track', 300)" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="featured-products-track" id="promotions-track">
                <!-- Rendered dynamically -->
            </div>
        </section>

        <!-- 3. New Products Section -->
        <section id="new-products-section" class="explore-categories-section" style="margin-bottom: 40px; scroll-margin-top: 130px;">
            <div class="categories-section-header">
                <h3 class="categories-title dynamic-lang" data-en="New products" data-bn="নতুন পণ্য">New products</h3>
                <div class="cat-arrows-box">
                    <button class="cat-nav-btn" onclick="scrollCarousel('new-products-track', -300)" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="cat-nav-btn" onclick="scrollCarousel('new-products-track', 300)" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="featured-products-track" id="new-products-track">
                <!-- Rendered dynamically -->
            </div>
        </section>



        <!-- Our Happy Customers Step-by-Step Auto-Carousel Section -->
        <?php
        $happyReviews = [];
        try {
            $happyReviews = $pdo->query("SELECT * FROM `happy_customers` WHERE `is_active` = 1 ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $happyReviews = [];
        }
        ?>
        <?php if (!empty($happyReviews)): ?>
        <section class="shop-reviews-section explore-categories-section" style="margin-bottom: 40px;">
            <div class="categories-section-header">
                <h3 class="categories-title dynamic-lang" data-en="Our Happy Customers" data-bn="আমাদের সন্তুষ্ট গ্রাহকবৃন্দ">Our Happy Customers</h3>
            </div>
            
            <div class="reviews-marquee-box" id="reviews-carousel-box">
                <div class="reviews-marquee-track" id="reviews-track">
                    <?php foreach ($happyReviews as $rev): 
                        $rating = max(1, min(5, (int)($rev['rating'] ?? 5)));
                        $nameEn = trim($rev['name'] ?? '');
                        $nameBn = trim($rev['name_bn'] ?? '') !== '' ? trim($rev['name_bn']) : $nameEn;
                        $commentEn = trim($rev['comment'] ?? '');
                        $commentBn = trim($rev['comment_bn'] ?? '') !== '' ? trim($rev['comment_bn']) : $commentEn;
                    ?>
                    <div class="customer-review-card">
                        <div class="review-stars-row">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="<?= $s <= $rating ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <h4 class="review-author-name dynamic-lang" data-en="<?= htmlspecialchars($nameEn, ENT_QUOTES) ?>" data-bn="<?= htmlspecialchars($nameBn, ENT_QUOTES) ?>"><?= htmlspecialchars($nameEn) ?></h4>
                        <p class="review-quote-text dynamic-lang" data-en="&quot;<?= htmlspecialchars($commentEn, ENT_QUOTES) ?>&quot;" data-bn="&quot;<?= htmlspecialchars($commentBn, ENT_QUOTES) ?>&quot;">&quot;<?= htmlspecialchars($commentEn) ?>&quot;</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<script>
    let allProducts = [];
    let allCategories = [];
    let currentCategory = '';
    let currentSearch = '';
    let specialFilter = ''; // 'hot', 'promo', 'new'

    function toggleCategoriesMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('categories-dropdown-menu');
        if (menu) menu.classList.toggle('show');
    }

    document.addEventListener('click', () => {
        const menu = document.getElementById('categories-dropdown-menu');
        if (menu) menu.classList.remove('show');
    });

    function scrollToProducts() {
        document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth' });
    }

    function handleSubnavSearch(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('shop-search-input');
        const query = input ? input.value.trim() : '';
        if (query) {
            window.location.href = 'products.php?q=' + encodeURIComponent(query);
        } else {
            window.location.href = 'products.php';
        }
    }

    function selectCategory(catId, nameEn, nameBn) {
        if (catId) {
            window.location.href = 'products.php?cat=' + encodeURIComponent(catId);
        } else {
            window.location.href = 'products.php';
        }
    }

    function filterSpecial(type) {
        specialFilter = type;
        currentCategory = '';
        
        const titleEl = document.getElementById('active-category-title');
        if (titleEl) {
            if (type === 'hot') {
                titleEl.innerText = isBangla ? 'হট ডিলস 🔥' : 'Hot Deals 🔥';
            } else if (type === 'promo') {
                titleEl.innerText = isBangla ? 'বিশেষ ছাড় ও প্রমোশন ⭐' : 'Promotions & Discounts ⭐';
            } else if (type === 'new') {
                titleEl.innerText = isBangla ? 'নতুন পণ্যসমূহ 📢' : 'New Products 📢';
            }
        }

        document.querySelectorAll('.cat-item-card').forEach(c => c.classList.remove('active'));
        const resetBtn = document.getElementById('reset-filter-btn');
        if (resetBtn) resetBtn.style.display = 'inline-flex';

        renderProducts();
        scrollToProducts();
    }

    function scrollCarousel(trackId, distance) {
        const track = document.getElementById(trackId);
        if (track) {
            track.scrollBy({ left: distance, behavior: 'smooth' });
        }
    }

    function scrollToHero() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function scrollToSection(sectionId) {
        const el = document.getElementById(sectionId);
        if (el) {
            const offset = 120;
            const bodyRect = document.body.getBoundingClientRect().top;
            const elementRect = el.getBoundingClientRect().top;
            const elementPosition = elementRect - bodyRect;
            const offsetPosition = elementPosition - offset;
            window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
        }
    }

    function renderFeaturedSections() {
        const hotTrack = document.getElementById('hot-deals-track');
        const promoTrack = document.getElementById('promotions-track');
        const newTrack = document.getElementById('new-products-track');

        if (window.inMemCatalog && window.inMemCatalog.length > 0) {
            allProducts = window.inMemCatalog;
        }

        if (!allProducts || allProducts.length === 0) return;

        // 1. Hot Deals
        if (hotTrack) {
            hotTrack.innerHTML = '';
            allProducts.slice(0, 8).forEach(p => {
                hotTrack.appendChild(createProductCard(p, 'Hot'));
            });
        }

        // 2. Promotions
        if (promoTrack) {
            promoTrack.innerHTML = '';
            allProducts.slice(2, 10).forEach(p => {
                const badgeText = p.discount_percent > 0 ? `-${p.discount_percent}%` : 'Sale';
                promoTrack.appendChild(createProductCard(p, badgeText));
            });
        }

        // 3. New Products
        if (newTrack) {
            newTrack.innerHTML = '';
            [...allProducts].reverse().slice(0, 8).forEach(p => {
                newTrack.appendChild(createProductCard(p, 'New'));
            });
        }
    }

    function createProductCard(prod, customBadge = null) {
        const price = prod.price;
        const oldPrice = prod.old_price ? prod.old_price : (price + Math.round(price * 0.15));

        let badgeHtml = '';
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
                <span class="product-cat dynamic-lang" data-en="${prod.category_name || ''}" data-bn="${prod.category_name_bn || prod.category_name || ''}" style="color: #64748b; font-size: 11px; font-weight: 800; margin-bottom: 4px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">
                    ${isBangla && prod.category_name_bn ? prod.category_name_bn : (prod.category_name || '')}
                </span>
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
            
            let matchesSpecial = true;
            if (specialFilter === 'hot') {
                matchesSpecial = p.is_hot == 1 || p.discount_percent >= 10;
            } else if (specialFilter === 'promo') {
                matchesSpecial = p.discount_percent > 0;
            } else if (specialFilter === 'new') {
                matchesSpecial = true;
            }

            return matchesCategory && matchesSearch && matchesSpecial;
        });

        if (specialFilter === 'promo') {
            filtered.sort((a, b) => b.discount_percent - a.discount_percent);
        }

        const countBadge = document.getElementById('product-count-badge');
        if (countBadge) {
            countBadge.innerText = `${filtered.length} item${filtered.length !== 1 ? 's' : ''}`;
        }

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 60px 20px; background: white; border-radius: 16px; border: 1px dashed #cbd5e1;">
                    <i class="fa-solid fa-basket-shopping" style="font-size: 38px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
                    <p style="font-weight: 700; font-size: 16px; color: #334155; margin: 0 0 6px 0;">No products found</p>
                    <p style="font-size: 13px; margin: 0;">Try searching for something else or explore other categories.</p>
                </div>
            `;
            return;
        }

        filtered.forEach(prod => {
            grid.appendChild(createProductCard(prod));
        });
    }

    function renderShopCategories() {
        const catCarousel = document.getElementById('categories-carousel');
        const dropdownMenu = document.getElementById('categories-dropdown-menu');

        if (catCarousel && allCategories && allCategories.length > 0) {
            catCarousel.innerHTML = '';
            if (dropdownMenu) dropdownMenu.innerHTML = '';

            allCategories.forEach(cat => {
                // Card in Carousel
                const card = document.createElement('div');
                card.className = 'cat-item-card';
                card.setAttribute('data-cat-id', cat.id);
                card.onclick = () => selectCategory(cat.id, cat.name, cat.name_bn);
                
                card.innerHTML = `
                    <img src="${cat.img}" alt="${cat.name}" class="cat-item-img" onerror="this.src='https://placehold.co/100x80?text=Category';">
                    <h4 class="cat-item-title dynamic-lang" data-en="${cat.name}" data-bn="${cat.name_bn || cat.name}">
                        ${isBangla && cat.name_bn ? cat.name_bn : cat.name}
                    </h4>
                `;
                catCarousel.appendChild(card);

                // Item in Dropdown
                if (dropdownMenu) {
                    const a = document.createElement('a');
                    a.href = 'products.php?cat=' + encodeURIComponent(cat.id);
                    a.setAttribute('data-cat-id', cat.id);
                    a.innerHTML = `
                        <span class="dynamic-lang" data-en="${cat.name}" data-bn="${cat.name_bn || cat.name}">
                            ${isBangla && cat.name_bn ? cat.name_bn : cat.name}
                        </span>
                    `;
                    dropdownMenu.appendChild(a);
                }
            });
        }
    }

    function fetchShopData(force = false) {
        const userLoc = (typeof getValidUserLocation === 'function' ? getValidUserLocation() : null) || (localStorage.getItem('userLocation') || '').trim();

        if (!force && window.inMemCatalog && window.inMemCatalog.length > 0) {
            allProducts = window.inMemCatalog;
            renderFeaturedSections();
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

                    renderShopCategories();
                    renderFeaturedSections();
                    renderProducts();
                }
            })
            .catch(err => {
                console.error("Store data error:", err);
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Sticky dynamic position check for subnav
        const topHeader = document.querySelector('header.header:not(#shop-subnav)');
        const shopNav = document.getElementById('shop-subnav');
        if (topHeader && shopNav) {
            shopNav.style.top = topHeader.offsetHeight + 'px';
        }

        // Carousel arrow controls
        const carousel = document.getElementById('categories-carousel');
        const prevBtn = document.getElementById('cat-prev-btn');
        const nextBtn = document.getElementById('cat-next-btn');

        if (prevBtn && carousel) {
            prevBtn.addEventListener('click', () => {
                carousel.scrollBy({ left: -280, behavior: 'smooth' });
            });
        }
        if (nextBtn && carousel) {
            nextBtn.addEventListener('click', () => {
                carousel.scrollBy({ left: 280, behavior: 'smooth' });
            });
        }

        // URL Parameters Redirect Check (if user arrives with ?cat or ?q to shop.php)
        const urlParams = new URLSearchParams(window.location.search);
        const catParam = urlParams.get('cat');
        const qParam = urlParams.get('q');
        if (catParam || qParam) {
            window.location.href = 'products.php' + window.location.search;
            return;
        }

        // Fetch Store Data with Location
        fetchShopData();

        // Step-by-step Testimonial Auto-scroller (Slides one by one with pauses and loops back)
        function initReviewsAutoStep() {
            const carousel = document.getElementById('reviews-carousel-box');
            if (!carousel) return;

            let autoStepInterval = null;
            const stepDelay = 3500; // 3.5 seconds wait per comment card

            function getCardStepWidth() {
                const firstCard = carousel.querySelector('.customer-review-card');
                if (!firstCard) return 364;
                return firstCard.offsetWidth + 24; // card width + gap
            }

            function stepNextReview() {
                const stepWidth = getCardStepWidth();
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;

                if (carousel.scrollLeft >= maxScroll - 20) {
                    // Reached end, loop back to 1st comment smoothly
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    carousel.scrollBy({ left: stepWidth, behavior: 'smooth' });
                }
            }

            function stepPrevReview() {
                const stepWidth = getCardStepWidth();
                if (carousel.scrollLeft <= 20) {
                    const maxScroll = carousel.scrollWidth - carousel.clientWidth;
                    carousel.scrollTo({ left: maxScroll, behavior: 'smooth' });
                } else {
                    carousel.scrollBy({ left: -stepWidth, behavior: 'smooth' });
                }
            }

            function startAutoStep() {
                if (autoStepInterval) clearInterval(autoStepInterval);
                autoStepInterval = setInterval(stepNextReview, stepDelay);
            }

            function stopAutoStep() {
                if (autoStepInterval) {
                    clearInterval(autoStepInterval);
                    autoStepInterval = null;
                }
            }

            carousel.addEventListener('mouseenter', stopAutoStep);
            carousel.addEventListener('mouseleave', startAutoStep);
            carousel.addEventListener('touchstart', stopAutoStep, { passive: true });
            carousel.addEventListener('touchend', startAutoStep, { passive: true });

            window.scrollReviewsPrev = () => { stopAutoStep(); stepPrevReview(); startAutoStep(); };
            window.scrollReviewsNext = () => { stopAutoStep(); stepNextReview(); startAutoStep(); };

            startAutoStep();
        }

        initReviewsAutoStep();

        // Re-render when language toggle button is clicked
        window.addEventListener('languageChanged', () => {
            renderShopCategories();
            renderFeaturedSections();
            renderProducts();
        });
        document.getElementById('lang-btn')?.addEventListener('click', () => {
            setTimeout(() => { renderShopCategories(); renderFeaturedSections(); renderProducts(); }, 50);
        });
        document.getElementById('mobile-lang-btn')?.addEventListener('click', () => {
            setTimeout(() => { renderShopCategories(); renderFeaturedSections(); renderProducts(); }, 50);
        });

        // Re-render when catalog/location pricing is updated
        window.addEventListener('catalogUpdated', (e) => {
            if (e.detail && e.detail.products) {
                allProducts = e.detail.products;
            }
            renderFeaturedSections();
            renderProducts();
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
