<?php
// database/setup.php
// Automated database schema builder & seeder for BazarKinun.com

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'bazarkinun_db';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "[1/4] Creating database `{$db_name}` if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$db_name}`;");

    echo "[2/4] Creating tables...\n";

    // 1. Users Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(25) NOT NULL UNIQUE,
            `email` VARCHAR(150) NULL,
            `password` VARCHAR(255) NULL,
            `role` ENUM('customer', 'rider', 'manager', 'admin') DEFAULT 'customer',
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `default_location` VARCHAR(255) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Categories Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `name_bn` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(100) NOT NULL,
            `img` VARCHAR(255) NULL,
            `status` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Products Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT NOT NULL,
            `title` VARCHAR(200) NOT NULL,
            `title_bn` VARCHAR(250) NOT NULL,
            `description` TEXT NULL,
            `description_bn` TEXT NULL,
            `poriman` VARCHAR(50) NOT NULL DEFAULT '1 kg',
            `poriman_bn` VARCHAR(50) NOT NULL DEFAULT '১ কেজি',
            `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `old_price` DECIMAL(10,2) NULL,
            `discount_percent` INT DEFAULT 0,
            `image` VARCHAR(255) NOT NULL,
            `stock` INT DEFAULT 100,
            `is_featured` TINYINT(1) DEFAULT 0,
            `is_hot` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Orders Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_number` VARCHAR(50) NOT NULL UNIQUE,
            `user_id` INT NULL,
            `customer_name` VARCHAR(150) NOT NULL,
            `customer_phone` VARCHAR(25) NOT NULL,
            `customer_address` TEXT NULL,
            `police_station` VARCHAR(500) NULL,
            `order_type` ENUM('website', 'text_list', 'uploaded') DEFAULT 'website',
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `delivery_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `payment_method` VARCHAR(50) DEFAULT 'Cash on Delivery',
            `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            `status` ENUM('pending', 'processing', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
            `html_content` LONGTEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Order Items Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NULL,
            `product_name` VARCHAR(200) NOT NULL,
            `product_poriman` VARCHAR(50) NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 6. Custom Lists Table (for handwritten or typed lists)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `custom_order_lists` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `list_type` ENUM('text', 'image') DEFAULT 'text',
            `raw_text` LONGTEXT NULL,
            `image_urls` JSON NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 7. Delivery Charges Config
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `delivery_charge_rules` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `min_order_amount` DECIMAL(10,2) DEFAULT 0.00,
            `charge` DECIMAL(10,2) DEFAULT 60.00,
            `is_active` TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 8. Hub Sub Areas Mapping (Zone & Location resolution)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `hub_sub_areas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `zone_id` INT DEFAULT 2,
            `district` VARCHAR(100) DEFAULT 'Dhaka',
            `police_station` VARCHAR(150) NOT NULL,
            `sub_area` VARCHAR(150) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ps` (`police_station`),
            INDEX `idx_sub_area` (`sub_area`),
            INDEX `idx_zone` (`zone_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "[3/4] Seeding initial categories & products...\n";

    // Seed Categories
    $categories = [
        ['Vegetables', 'তাজা শাকসবজি', 'vegetables', 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=300'],
        ['Fresh Fish', 'টাটকা মাছ', 'fresh-fish', 'https://images.unsplash.com/photo-1534043464124-3be32fe000c9?w=300'],
        ['Meat & Poultry', 'মাংস ও পোল্ট্রি', 'meat-poultry', 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=300'],
        ['Rice & Grains', 'চাল ও ডাল', 'rice-grains', 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=300'],
        ['Oil & Ghee', 'তেল ও ঘি', 'oil-ghee', 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=300'],
        ['Spices', 'মসলাপাতি', 'spices', 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=300'],
        ['Eggs & Dairy', 'ডিম ও দুধ', 'eggs-dairy', 'https://images.unsplash.com/photo-1516448620398-c5f44bf9f441?w=300'],
        ['Fruits', 'তাজা ফলমূল', 'fruits', 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=300']
    ];

    $catStmt = $pdo->prepare("INSERT INTO `categories` (`name`, `name_bn`, `slug`, `img`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");
    $catMap = [];
    foreach ($categories as $cat) {
        $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $check->execute([$cat[2]]);
        $existing = $check->fetchColumn();
        if ($existing) {
            $catMap[$cat[2]] = $existing;
        } else {
            $catStmt->execute($cat);
            $catMap[$cat[2]] = $pdo->lastInsertId();
        }
    }

    // Seed Realistic Bangladeshi Products
    $products = [
        // Vegetables
        [$catMap['vegetables'], 'Fresh Red Tomato', 'তাজা পাকা টমেটো', 'Naturally ripened, fresh red tomatoes rich in nutrients.', 'গাছে পাকা সুস্বাদু ও পুষ্টিকর লাল টমেটো।', '1 kg', '১ কেজি', 60.00, 75.00, 20, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=500', 100, 1, 1],
        [$catMap['vegetables'], 'Deshi Potato (Alu)', 'দেশি নতুন আলু', 'Premium quality fresh clean round potatoes.', 'পরিষ্কার ও স্বাস্থ্যসম্মত টাটকা গোল আলু।', '1 kg', '১ কেজি', 40.00, 45.00, 11, 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=500', 150, 1, 0],
        [$catMap['vegetables'], 'Fresh Onion (Peyaj)', 'দেশি লাল পেঁয়াজ', 'Best quality pungent dry deshi onions.', 'রান্নার অপরিহার্য খাঁটি দেশি লাল পেঁয়াজ।', '1 kg', '১ কেজি', 95.00, 110.00, 14, 'https://images.unsplash.com/photo-1618512496248-a07fe83aa8cb?w=500', 120, 1, 1],
        [$catMap['vegetables'], 'Green Chilli (Kacha Morich)', 'তাজা কাঁচা মরিচ', 'Fresh spicy green chillies direct from farm.', 'ঝাল ও ফ্রেশ কাঁচা মরিচ।', '250 gm', '২৫০ গ্রাম', 35.00, 45.00, 22, 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d?w=500', 80, 0, 1],
        
        // Fresh Fish
        [$catMap['fresh-fish'], 'River Rui Fish', 'নদীর তাজা রুই মাছ', 'Fresh sweet river Rui fish, cleaned and scaled.', 'নদীর তাজা ও সুস্বাদু রুই মাছ (কাটা ও পরিষ্কার)।', '1.5 kg', '১.৫ কেজি', 480.00, 550.00, 13, 'https://images.unsplash.com/photo-1534043464124-3be32fe000c9?w=500', 20, 1, 1],
        [$catMap['fresh-fish'], 'Chittagong Hilsha Fish', 'পদ্মার তাজা রূপালী ইলিশ', 'Authentic Padma river silver Hilsha fish with rich aroma.', 'স্বাদে ও গন্ধে অতুলনীয় পদ্মার রূপালী ইলিশ।', '1 kg (Size 1kg+)', '১ কেজি (১ কেজি+ সাইজ)', 1450.00, 1650.00, 12, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=500', 15, 1, 1],
        
        // Meat & Poultry
        [$catMap['meat-poultry'], 'Premium Beef (Bone-in)', 'গরুর খাঁটি মাংস (হাড় সহ)', '100% Halal and fresh local farm beef.', '১০০% হালাল ও টাটকা কচি গরুর মাংস।', '1 kg', '১ কেজি', 750.00, 800.00, 6, 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=500', 40, 1, 1],
        [$catMap['meat-poultry'], 'Broiler Chicken (Dressed)', 'ব্রয়লার মুরগি (চামড়া ছাড়া)', 'Hygienically dressed and thoroughly cleaned fresh chicken.', 'স্বাস্থ্যসম্মতভাবে ড্রেসিং ও পরিষ্কার করা তাজা মুরগি।', '1 kg', '১ কেজি', 190.00, 210.00, 10, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=500', 50, 1, 0],
        
        // Rice & Grains
        [$catMap['rice-grains'], 'Premium Miniket Rice', 'মিনিকেট প্রিমিয়াম চাল', 'Long grain, polished and fragrant Miniket rice.', 'ঝরঝরে ও সুস্বাদু প্রিমিয়াম মিনিকেট চাল।', '5 kg Bag', '৫ কেজি ব্যাগ', 390.00, 425.00, 8, 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500', 60, 1, 0],
        [$catMap['rice-grains'], 'Deshi Mosur Dal', 'দেশি চিকন মসুর ডাল', 'Small grain pure organic deshi red lentils.', 'দ্রুত সিদ্ধ হওয়া খাঁটি দেশি ছোট দানার মসুর ডাল।', '1 kg', '১ কেজি', 140.00, 155.00, 10, 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=500', 70, 0, 0],

        // Oil & Ghee
        [$catMap['oil-ghee'], 'Rupchanda Fortified Soyabean Oil', 'রূপচাঁদা ফর্টিফাইড সয়াবিন তেল', 'Enriched with Vitamin A and D, heart friendly.', 'ভিটামিন এ ও ডি সমৃদ্ধ স্বাস্থ্যসম্মত সয়াবিন তেল।', '5 Liter', '৫ লিটার', 890.00, 950.00, 6, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500', 45, 1, 1],
        [$catMap['oil-ghee'], 'Radhuni Pure Mustard Oil', 'রাঁধুনী খাঁটি সরিষার তেল', 'Traditional cold-pressed mustard oil with sharp pungent flavor.', 'খাঁটি ঝাঁঝালো ঘানিতে ভাঙা সরিষার তেল।', '1 Liter', '১ লিটার', 290.00, 320.00, 9, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500', 35, 0, 1],

        // Eggs & Dairy
        [$catMap['eggs-dairy'], 'Farm Fresh Red Eggs (Dozen)', 'ফার্মের লাল ডিম (১২ টি)', 'Grade A farm fresh healthy red chicken eggs.', 'পুষ্টিকর ও সতেজ গ্রেড-এ লাল মুরগির ডিম।', '12 Pcs', '১২ টি', 145.00, 160.00, 9, 'https://images.unsplash.com/photo-1516448620398-c5f44bf9f441?w=500', 100, 1, 1],
        [$catMap['eggs-dairy'], 'Aarong Dairy Pasteurized Milk', 'আড়ং ডেইরি পাস্তুরিত তরল দুধ', '100% pure fresh cow milk.', 'খাঁটি গরুর স্বাস্থ্যকর তরল দুধ।', '1 Liter', '১ লিটার', 90.00, 95.00, 5, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=500', 50, 0, 0],

        // Fruits
        [$catMap['fruits'], 'Sweet Green Apple', 'রসালো সবুজ আপেল', 'Crisp, sweet and juicy imported green apples.', 'মিষ্টি ও সুস্বাদু তাজা সবুজ আপেল।', '1 kg', '১ কেজি', 320.00, 360.00, 11, 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=500', 30, 1, 1],
        [$catMap['fruits'], 'Fresh Banana (Shobri Kola)', 'তাজা সবরি কলা', 'Naturally ripened sweet aromatic Shobri bananas.', 'বিষমুক্ত ও সুস্বাদু সবরি কলা।', '4 Pcs (Hali)', '৪ টি (হালি)', 45.00, 50.00, 10, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=500', 80, 0, 0]
    ];

    $prodCheck = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($prodCheck == 0) {
        $prodStmt = $pdo->prepare("
            INSERT INTO `products` 
            (`category_id`, `title`, `title_bn`, `description`, `description_bn`, `poriman`, `poriman_bn`, `price`, `old_price`, `discount_percent`, `image`, `stock`, `is_featured`, `is_hot`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($products as $p) {
            $prodStmt->execute($p);
        }
    }

    // Seed Demo User (Daud)
    $userCheck = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $userCheck->execute(['01919668960']);
    if (!$userCheck->fetchColumn()) {
        $pdo->prepare("
            INSERT INTO `users` (`name`, `phone`, `email`, `role`, `default_location`) 
            VALUES (?, ?, ?, ?, ?)
        ")->execute(['Daud', '01919668960', 'daud@bazarkinun.com', 'customer', 'Jahurul Islam City, Aftabnagar, Dhaka']);
    }

    // Seed Delivery Charge Rule
    $delCheck = $pdo->query("SELECT COUNT(*) FROM delivery_charge_rules")->fetchColumn();
    if ($delCheck == 0) {
        $pdo->exec("INSERT INTO `delivery_charge_rules` (`min_order_amount`, `charge`, `is_active`) VALUES (1000.00, 0.00, 1);");
        $pdo->exec("INSERT INTO `delivery_charge_rules` (`min_order_amount`, `charge`, `is_active`) VALUES (0.00, 60.00, 1);");
    }

    echo "[4/4] Database setup & seeding successfully completed!\n";
    echo "====================================================\n";
    echo "Database: {$db_name}\n";
    echo "Categories Count: " . count($categories) . "\n";
    echo "Products Count: " . count($products) . "\n";
    echo "Default User: Daud (01919668960)\n";
    echo "====================================================\n";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
}
