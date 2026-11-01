<?php
// index.php
$pageTitle = 'BazarKinun.com - Fast & Fresh Grocery Delivery';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .home-options-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 68vh;
        padding: 50px 20px;
        gap: 28px;
        background: radial-gradient(circle at 50% 30%, rgba(16, 185, 129, 0.05) 0%, rgba(248, 250, 252, 0) 70%);
    }

    .home-option-card {
        background: #ffffff;
        border: 2px solid transparent;
        border-radius: 18px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
        padding: 40px 44px;
        width: 100%;
        max-width: 620px;
        min-height: 150px;
        text-align: left;
        text-decoration: none;
        color: #0f172a;
        transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        gap: 28px;
        cursor: pointer;
        box-sizing: border-box;
    }

    .home-option-card:hover {
        transform: translateY(-5px) scale(1.015);
        box-shadow: 0 14px 35px rgba(0, 198, 117, 0.18);
        border-color: #00c675;
        background: #ffffff;
    }

    .home-option-icon-wrapper {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 54px;
        background: transparent;
        border: none;
        transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        flex-shrink: 0;
    }

    .home-option-icon {
        display: inline-block;
        font-size: 54px;
        line-height: 1;
        transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .home-option-card:hover .home-option-icon-wrapper,
    .home-option-card:hover .home-option-icon {
        transform: rotate(4deg) scale(1.08);
    }

    .home-option-text {
        font-size: 1.75rem;
        font-weight: 800;
        font-family: var(--font-family);
        color: #1e293b;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }

    /* Mobile adjustments */
    @media (max-width: 640px) {
        .home-option-card {
            padding: 22px 20px;
            gap: 16px;
            border-radius: 16px;
            min-height: 100px;
            max-width: 100%;
        }
        .home-option-icon-wrapper {
            width: 60px;
            height: 60px;
            font-size: 40px;
        }
        .home-option-icon {
            font-size: 40px;
        }
        .home-option-text {
            font-size: 1.35rem;
        }
    }

    @media (max-width: 374px) {
        .home-options-container {
            padding: 28px 12px;
            gap: 16px;
        }
        .home-option-card {
            padding: 16px 12px;
            gap: 12px;
            border-radius: 14px;
            min-height: 85px;
        }
        .home-option-icon-wrapper {
            width: 44px;
            height: 44px;
        }
        .home-option-icon {
            font-size: 30px;
        }
        .home-option-text {
            font-size: 1.12rem;
        }
    }

    @media (min-width: 2560px) {
        .home-options-container {
            min-height: 75vh;
            gap: 36px;
            padding: 80px 40px;
        }
        .home-option-card {
            max-width: 800px;
            padding: 48px 54px;
            border-radius: 24px;
            min-height: 180px;
            gap: 34px;
        }
        .home-option-icon-wrapper {
            width: 88px;
            height: 88px;
        }
        .home-option-icon {
            font-size: 68px;
        }
        .home-option-text {
            font-size: 2.2rem;
        }
    }
</style>

<main class="home-options-container">
    <!-- 1. Write Your Grocery List -->
    <a href="write_list.php" class="home-option-card" title="Write Your Grocery List">
        <div class="home-option-icon-wrapper">
            <span class="home-option-icon">📝</span>
        </div>
        <span class="home-option-text dynamic-lang" 
              data-en="Write Your Grocery List" 
              data-bn="আপনার বাজারের লিস্ট লিখুন">
            Write Your Grocery List
        </span>
    </a>

    <!-- 2. Upload Your Grocery List -->
    <a href="upload_list.php" class="home-option-card" title="Upload Your Grocery List">
        <div class="home-option-icon-wrapper">
            <span class="home-option-icon">📷</span>
        </div>
        <span class="home-option-text dynamic-lang" 
              data-en="Upload Your Grocery List" 
              data-bn="আপনার বাজারের লিস্ট আপলোড করুন">
            Upload Your Grocery List
        </span>
    </a>

    <!-- 3. Order from Website -->
    <a href="shop.php" class="home-option-card" title="Order from Website">
        <div class="home-option-icon-wrapper">
            <span class="home-option-icon">🛍️</span>
        </div>
        <span class="home-option-text dynamic-lang" 
              data-en="Order from Website" 
              data-bn="ওয়েবসাইট থেকে অর্ডার করুন">
            Order from Website
        </span>
    </a>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
