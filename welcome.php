<?php
session_start();

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: " . basename($_SERVER['PHP_SELF']));
    exit();
}

// Database configuration
const DB_CONFIG = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'sdlcsql'
];

/**
 * Connect to the database
 * @return mysqli
 */
function getDBConnection(): mysqli
{
    $conn = new mysqli(
        DB_CONFIG['host'],
        DB_CONFIG['username'],
        DB_CONFIG['password'],
        DB_CONFIG['database']
    );
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * Fetch products from database and classify them
 * @return array
 */
function fetchProducts(): array
{
    $conn = getDBConnection();
    $products = [];
    $result = $conn->query("SELECT product_name, product_img, product_price, year, discount, discounted_price, rating FROM products");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product = [
                'name' => $row['product_name'],
                'image' => "103.75.184.31{$row['product_img']}",
                'price' => (float)$row['product_price'],
                'rating' => $row['rating'] ?? number_format(rand(60, 90) / 10, 1),
                'year' => $row['year'] ?? 'Not specified'
            ];
            if ($row['discount'] > 0) {
                $product['original_price'] = (float)$row['product_price'];
                $product['discounted_price'] = (float)$row['discounted_price'];
                $product['discount'] = (int)$row['discount'];
            }
            $products[] = $product;
        }
    } else {
        $products[] = [
            'name' => 'No products available',
            'image' => 'https://via.placeholder.com/150',
            'price' => 0.00,
            'rating' => '0.0',
            'year' => 'Not specified'
        ];
    }
    $conn->close();
    return $products;
}

/**
 * Fetch cart from database
 * @param int $user_id
 * @return array
 */
function fetchCartFromDB($user_id): array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT product_id, product_name, product_img, product_price, quantity FROM cart WHERE user_id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'image' => $row['product_img'],
            'price' => (float)$row['product_price'],
            'quantity' => $row['quantity']
        ];
    }
    $stmt->close();
    $conn->close();
    return $cart;
}

/**
 * Save cart to database
 * @param int $user_id
 * @param array $product
 * @return bool
 */
function saveCartToDB($user_id, $product): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_name = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("is", $user_id, $product['name']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, product_name, product_img, product_price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        $quantity = 1;
        $product_id = isset($product['id']) ? $product['id'] : null;
        $product_price = (float)$product['price'];
        $stmt->bind_param("iissdi", $user_id, $product_id, $product['name'], $product['image'], $product_price, $quantity);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return false;
        }
    }
    $stmt->close();
    $conn->close();
    return true;
}

// Data arrays for static content
$years = ['2025', '2020-2024', '2010-2019', '2000-2009', '1980-1999'];

// Special Offers data (static)
$offers = array(
    0 => array(
        'name' => 'Cyberpunk 2077',
        'image' => 'https://www.gamesrig.com/img/index/cyberpunk-2077-cover.jpg',
        'price' => 47.99,
        'original_price' => 59.99,
        'discounted_price' => 47.99,
        'discount' => 20,
        'rating' => '7.2',
        'year' => '2020',
    ),
    1 => array(
        'name' => 'The Witcher 3: Wild Hunt',
        'image' => 'https://www.gamesrig.com/img/index/the-witcher-3-wild-hunt-cover.jpg',
        'price' => 29.99,
        'original_price' => 39.99,
        'discounted_price' => 29.99,
        'discount' => 25,
        'rating' => '7.4',
        'year' => '2015',
    ),
    2 => array(
        'name' => 'Elden Ring',
        'image' => 'https://www.gamesrig.com/img/index/elden-ring-cover.jpg',
        'price' => 44.99,
        'original_price' => 59.99,
        'discounted_price' => 44.99,
        'discount' => 25,
        'rating' => '7.1',
        'year' => '2022',
    ),
    3 => array(
        'name' => 'Hollow Knight',
        'image' => 'https://www.gamesrig.com/img/index/hollow-knight-cover.jpg',
        'price' => 9.99,
        'original_price' => 14.99,
        'discounted_price' => 9.99,
        'discount' => 33,
        'rating' => '7.8',
        'year' => '2017',
    ),
    4 => array(
        'name' => 'Stardew Valley',
        'image' => 'https://www.gamesrig.com/img/index/stardew-valley-cover.jpg',
        'price' => 11.99,
        'original_price' => 14.99,
        'discounted_price' => 11.99,
        'discount' => 20,
        'rating' => '6.3',
        'year' => '2016',
    ),
    5 => array(
        'name' => 'God of War Ragnarök',
        'image' => 'https://www.gamesrig.com/img/index/god-of-war-ragnarok-cover.jpg',
        'price' => 49.99,
        'original_price' => 69.99,
        'discounted_price' => 49.99,
        'discount' => 29,
        'rating' => '8.7',
        'year' => '2022',
    ),
    6 => array(
        'name' => 'Red Dead Redemption 2',
        'image' => 'https://www.gamesrig.com/img/index/red-dead-redemption-2-cover.jpg',
        'price' => 39.99,
        'original_price' => 59.99,
        'discounted_price' => 39.99,
        'discount' => 33,
        'rating' => '9.0',
        'year' => '2018',
    ),
    7 => array(
        'name' => 'Horizon Forbidden West',
        'image' => 'https://www.gamesrig.com/img/index/elden-ring-cover.jpg',
        'price' => 44.99,
        'original_price' => 59.99,
        'discounted_price' => 44.99,
        'discount' => 25,
        'rating' => '8.5',
        'year' => '2022',
    ),
    8 => array(
        'name' => 'Dark Souls III',
        'image' => 'https://www.gamesrig.com/img/index/dark-souls-iii-cover.jpg',
        'price' => 29.99,
        'original_price' => 39.99,
        'discounted_price' => 29.99,
        'discount' => 25,
        'rating' => '8.8',
        'year' => '2016',
    ),
    9 => array(
        'name' => 'Sekiro: Shadows Die Twice',
        'image' => 'https://www.gamesrig.com/img/index/sekiro-shadows-die-twice-goty-edition-cover.jpg',
        'price' => 34.99,
        'original_price' => 49.99,
        'discounted_price' => 34.99,
        'discount' => 30,
        'rating' => '8.6',
        'year' => '2019',
    ),
    10 => array(
        'name' => 'Resident Evil Village',
        'image' => 'https://www.gamesrig.com/img/index/resident-evil-village-cover.jpg',
        'price' => 24.99,
        'original_price' => 39.99,
        'discounted_price' => 24.99,
        'discount' => 38,
        'rating' => '8.2',
        'year' => '2021',
    ),
    11 => array(
        'name' => 'Ghost of Tsushima',
        'image' => 'https://www.gamesrig.com/img/index/elden-ring-cover.jpg',
        'price' => 39.99,
        'original_price' => 59.99,
        'discounted_price' => 39.99,
        'discount' => 33,
        'rating' => '8.9',
        'year' => '2020',
    ),
    12 => array(
        'name' => 'Assassin\'s Creed Valhalla',
        'image' => 'https://www.gamesrig.com/img/index/assassins-creed-valhalla-cover.jpg',
        'price' => 29.99,
        'original_price' => 49.99,
        'discounted_price' => 29.99,
        'discount' => 40,
        'rating' => '8.3',
        'year' => '2020',
    ),
    13 => array(
        'name' => 'Final Fantasy XVI',
        'image' => 'https://www.gamesrig.com/img/index/final-fantasy-xvi-cover.jpg',
        'price' => 54.99,
        'original_price' => 69.99,
        'discounted_price' => 54.99,
        'discount' => 21,
        'rating' => '8.4',
        'year' => '2023',
    ),
    14 => array(
        'name' => 'The Last of Us Part II',
        'image' => 'https://www.gamesrig.com/img/index/elden-ring-cover.jpg',
        'price' => 34.99,
        'original_price' => 49.99,
        'discounted_price' => 34.99,
        'discount' => 30,
        'rating' => '9.0',
        'year' => '2020',
    ),
);

// Main products data (static)
$main_products_static = array(
    0 => array(
        'image' => 'https://image.api.playstation.com/cdn/UP4781/CUSA11924_00/hOKaBpQx5VBNW3Wr7lL0w8X2yY2S9U1N.png',
        'name' => 'Path Of Exile',
        'rating' => '6.3',
        'price' => 19.99,
        'year' => '2013',
    ),
    1 => array(
        'image' => 'https://howlongtobeat.com/games/154981_Intravenous_2.jpg',
        'name' => 'Intravenous 2',
        'rating' => '9.0',
        'price' => 34.99,
        'year' => '2024',
    ),
    2 => array(
        'image' => 'https://www.gamesrig.com/img/index/grand-theft-auto-iv-cover.jpg',
        'name' => 'Grand Theft Auto IV',
        'rating' => '7.4',
        'price' => 24.99,
        'year' => '2008',
    ),
    3 => array(
        'image' => './dv_web_D1800010021572558.avif',
        'name' => 'Shadow Gambit: The Cursed Crew',
        'rating' => '7.3',
        'price' => 9.99,
        'year' => '2023',
    ),
    4 => array(
        'image' => 'https://i.pinimg.com/474x/2d/2d/f3/2d2df3cde768a6034e42cb27937b6676.jpg',
        'name' => 'Dragon Ball Z: Kakarot',
        'rating' => '8.0',
        'price' => 39.99,
        'year' => '2020',
    ),
    5 => array(
        'image' => 'https://www.gamesrig.com/img/index/beneath-oresa-cover.jpg',
        'name' => 'Beneath Oresa',
        'rating' => '8.4',
        'price' => 44.99,
        'year' => '2023',
    ),
    6 => array(
        'image' => 'https://www.gamesrig.com/img/index/robocop-rogue-city-cover.jpg',
        'name' => 'RoboCop: Rogue City',
        'rating' => '7.4',
        'price' => 29.99,
        'year' => '2023',
    ),
    7 => array(
        'image' => 'https://www.gamesrig.com/img/index/dead-space-cover.jpg',
        'name' => 'Dead Space',
        'rating' => '9.0',
        'price' => 59.99,
        'year' => '2008',
    ),
    8 => array(
        'image' => 'https://www.gamesrig.com/img/index/the-callisto-protocol-cover.jpg',
        'name' => 'The Callisto Protocol',
        'rating' => '7.3',
        'price' => 19.99,
        'year' => '2022',
    ),
    9 => array(
        'image' => 'https://www.gamesrig.com/img/index/days-gone-cover.jpg',
        'name' => 'Days Gone',
        'rating' => '7.6',
        'price' => 29.99,
        'year' => '2019',
    ),
    10 => array(
        'image' => 'https://www.gamesrig.com/img/index/god-of-war-cover.jpg',
        'name' => 'God of War',
        'rating' => '9.0',
        'price' => 49.99,
        'year' => '2018',
    ),
    11 => array(
        'image' => 'https://www.gamesrig.com/img/index/sekiro-shadows-die-twice-goty-edition-cover.jpg',
        'name' => 'Sekiro: Shadows Die Twice',
        'rating' => '8.6',
        'price' => 19.99,
        'year' => '2019',
    ),
    12 => array(
        'image' => 'https://www.gamesrig.com/img/index/marvels-spider-man-remastered-cover.jpg',
        'name' => 'Marvel’s Spider-Man Remastered',
        'rating' => '8.7',
        'price' => 9.99,
        'year' => '2020',
    ),
    13 => array(
        'image' => 'https://www.gamesrig.com/img/index/red-dead-redemption-2-cover.jpg',
        'name' => 'Red Dead Redemption 2',
        'rating' => '8.5',
        'price' => 99.99,
        'year' => '2018',
    ),
    14 => array(
        'image' => 'https://www.gamesrig.com/img/index/assassins-creed-shadows-cover.jpg',
        'name' => 'Assassin\'s Creed Shadows',
        'rating' => '8.5',
        'price' => 119.99,
        'year' => '2024',
    ),
    15 => array(
        'image' => 'https://www.gamesrig.com/img/index/bleach-rebirth-of-souls-cover.jpg',
        'name' => 'BLEACH Rebirth of Souls',
        'rating' => '7.4',
        'price' => 9.99,
        'year' => '2024',
    ),
    16 => array(
        'image' => 'https://www.gamesrig.com/img/index/my-hero-ones-justice-2-ultimate-edition-cover.jpg',
        'name' => 'My Hero One\'s Justice 2',
        'rating' => '6.4',
        'price' => 17.99,
        'year' => '2020',
    ),
    17 => array(
        'image' => 'https://www.gamesrig.com/img/index/rise-of-the-ronin-cover.jpg',
        'name' => 'Rise of the Ronin',
        'rating' => '8.4',
        'price' => 17.99,
        'year' => '2024',
    ),
    18 => array(
        'image' => 'https://www.gamesrig.com/img/index/balatro-cover.jpg',
        'name' => 'Balatro',
        'rating' => '8.2',
        'price' => 49.99,
        'year' => '2024',
    ),
    19 => array(
        'image' => 'https://www.gamesrig.com/img/index/final-fantasy-vii-rebirth-cover.jpg',
        'name' => 'Final Fantasy VII Rebirth',
        'rating' => '7.4',
        'price' => 17.99,
        'year' => '2024',
    ),
    20 => array(
        'image' => 'https://www.gamesrig.com/img/index/kingdom-come-deliverance-cover.jpg',
        'name' => 'Kingdom Come: Deliverance',
        'rating' => '7.5',
        'price' => 17.99,
        'year' => '2018',
    ),
);

// Fetch products from DB and classify
$db_products = fetchProducts();
$main_products = $main_products_static;

foreach ($db_products as $product) {
    // Check if product already exists in $offers or $main_products_static
    $in_offers = in_array($product['name'], array_column($offers, 'name'));
    $in_main_static = in_array($product['name'], array_column($main_products_static, 'name'));

    if (!$in_offers && !$in_main_static) {
        if (isset($product['discount']) && $product['discount'] > 0) {
            $offers[] = $product; // Add to $offers if discounted
        } else {
            $main_products[] = $product; // Add to $main_products if not discounted
        }
    }
}

$footer2_links = ['GameStore' => ['About Me', 'My Profile', 'Pricing Plans', 'Contacts'],];

$footer_links = [
    'Browse' => ['Live Game', 'Live News Game', 'Live Sports Game', 'Streaming Game Library'],
    '' => ['Game Shows', 'Movies', 'Kids', 'Collections'],
    'Help' => ['Account & Billing', 'Plans & Pricing', 'Supported Devices', 'Accessibility']
];

// Handle search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = [];
$all_products = array_merge($main_products, $offers);

if (!empty($search_query)) {
    $combined_products = array_merge($all_products, $offers);
    $search_results = array_filter($combined_products, function ($product) use ($search_query) {
        return stripos(strtolower($product['name']), strtolower($search_query)) !== false;
    });

    $offer_results = [];
    $non_offer_results = [];
    foreach ($search_results as $product) {
        $is_offer = false;
        foreach ($offers as $offer) {
            if ($offer['name'] === $product['name']) {
                $is_offer = true;
                break;
            }
        }
        if ($is_offer) {
            $offer_results[] = $product;
        } else {
            $non_offer_results[] = $product;
        }
    }

    $search_results = array_merge($offer_results, $non_offer_results);
    $search_results = array_values($search_results);

    foreach ($search_results as &$result) {
        $result['image'] = $result['image'] ?? 'https://via.placeholder.com/150';
        $result['name'] = $result['name'] ?? 'Unknown Product';
        $result['rating'] = $result['rating'] ?? 'N/A';
        $result['price'] = $result['price'] ?? 0.00;
        $result['original_price'] = $result['original_price'] ?? null;
        $result['discounted_price'] = $result['discounted_price'] ?? null;
        $result['discount'] = $result['discount'] ?? null;
    }
    unset($result);

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($search_results, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Determine role message and content visibility
$is_logged_in = isset($_SESSION['user_id']);
$role_message = '';
$content_for_customer = false;
$content_for_admin = false;

if ($is_logged_in) {
    $role_message = match ($_SESSION['role'] ?? '') {
        'customer' => 'Logged in as Customer',
        'admin' => 'Logged in as Admin',
        default => 'Invalid Role'
    };
    $content_for_customer = $_SESSION['role'] === 'customer';
    $content_for_admin = $_SESSION['role'] === 'admin';

    $cart_key = 'cart_' . $_SESSION['user_id'];
    if (!isset($_SESSION[$cart_key])) {
        $_SESSION[$cart_key] = fetchCartFromDB($_SESSION['user_id']);
    }
}

// Handle adding product to cart
if (isset($_GET['add_to_cart']) && $is_logged_in && $_SESSION['role'] === 'customer') {
    $product_name = $_GET['add_to_cart'];
    $cart_key = 'cart_' . $_SESSION['user_id'];

    if (!isset($_SESSION[$cart_key])) {
        $_SESSION[$cart_key] = [];
    }

    $response = ['success' => false, 'product_name' => $product_name, 'cart_count' => 0, 'error' => ''];

    $product_exists = false;
    foreach ($_SESSION[$cart_key] as $item) {
        if ($item['name'] === $product_name) {
            $product_exists = true;
            break;
        }
    }

    if (!$product_exists) {
        $product_found = false;
        foreach ($all_products as $product) {
            if ($product['name'] === $product_name) {
                $_SESSION[$cart_key][] = $product;
                if (saveCartToDB($_SESSION['user_id'], $product)) {
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Could not save product to database';
                }
                $product_found = true;
                break;
            }
        }
        if (!$product_found) {
            $response['error'] = 'Product does not exist';
        }
    } else {
        $response['error'] = "Game $product_name is already in the cart!";
    }

    $response['cart_count'] = count($_SESSION[$cart_key]);

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    header("Location: " . basename($_SERVER['PHP_SELF']));
    exit();
}

$cart_count = 0;
if ($is_logged_in) {
    $cart_key = 'cart_' . $_SESSION['user_id'];
    $cart_count = isset($_SESSION[$cart_key]) ? count($_SESSION[$cart_key]) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStore - Welcome</title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <link rel="stylesheet" href="/welcome.css">
    <style>
        .banner-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .dot {
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .dot.active {
            background: var(--light-azure);
        }

        .no-results {
            text-align: center;
            width: 100%;
            padding: 20px;
            font-size: 18px;
            color: #666;
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .search-suggestions .suggestion-item {
            padding: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .search-suggestions .suggestion-item:last-child {
            border-bottom: none;
        }

        .search-suggestions .suggestion-item:hover {
            background-color: #f5f5f5;
        }

        .search-suggestions .suggestion-item img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            object-fit: cover;
        }

        .search-suggestions .suggestion-item span {
            font-size: 14px;
            color: #333;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff4444;
            color: white;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
        }

        .banner {
            position: relative;
            width: 100%;
            height: 500px;
            overflow: hidden;
        }

        .banner-card {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .banner-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1.5s ease-in-out, transform 0.5s ease-in-out;
            transform: scale(1);
        }

        .banner-img.active {
            opacity: 1;
        }

        .banner-img:hover {
            transform: scale(1.1);
        }

        .cart-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .cart-notification.show {
            opacity: 1;
        }

        .cart-notification .checkmark {
            width: 60px;
            height: 60px;
            background: #00c4b4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }

        .cart-notification .checkmark.warning {
            background: #f39c12;
        }

        .cart-notification .checkmark ion-icon {
            font-size: 35px;
            width: 35px;
            height: 35px;
            line-height: 35px;
            display: block;
        }

        .cart-notification .message {
            font-size: 16px;
            color: #333;
        }

        .notification-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .cart-notification .cart-btn {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .cart-notification .cart-btn:hover {
            background: #b71c1c;
        }

        .cart-notification .cancel-logout {
            background: #666;
        }

        .cart-notification .cancel-logout:hover {
            background: #444;
        }

        /* Adjust filter-bar to support new layout */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgb(23, 31, 50);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
        }

        /* Style for Special Offers title */
        .filter-title {
            font-size: var(--font-size-medium);
            font-weight: var(--fw6);
            color: var(--white);
            margin-right: 20px;
        }

        /* Ensure filter-dropdowns and filter-radios are aligned correctly */
        .filter-dropdowns {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Make Special Offers stand out */
        .filter-title {
            background: rgb(19, 23, 32);
            padding: 15px 25px;
            border-radius: 10px;
            color: rgb(255, 255, 255);
        }

        .filter-radios {
            position: relative;
            background: var(--rich-blank-fogra-29);
            padding: 10px;
            border-radius: 15px;
        }

        .filter-radios input {
            display: none;
        }

        .filter-radios label {
            position: relative;
            margin: 0 10px;
            font-size: var(--font-size-small);
            user-select: none;
            cursor: pointer;
            z-index: 10;
        }

        .filter-radios input:checked+label,
        .filter-radios label:hover {
            color: var(--light-azure);
        }

        /* Add style for .checked-radio-bg */
        .filter-radios .checked-radio-bg {
            background: rgb(23, 31, 50);
            position: absolute;
            top: 5px;
            left: 5px;
            bottom: 5px;
            width: 85px;
            border-radius: 10px;
            transition: left 0.3s ease, width 0.3s ease;
            z-index: 5;
        }

        /* Movies */
        .filter-radios input#featured:checked~.checked-radio-bg {
            width: 85px;
            left: 5px;
        }

        .filter-radios input#popular:checked~.checked-radio-bg {
            width: 73px;
            left: 90px;
        }

        .filter-radios input#newest:checked~.checked-radio-bg {
            width: 81px;
            left: 163px;
        }

        /* Offers */
        .filter-radios input#offers-featured:checked~.checked-radio-bg {
            width: 85px;
            left: 5px;
        }

        .filter-radios input#offers-popular:checked~.checked-radio-bg {
            width: 73px;
            left: 90px;
        }

        .filter-radios input#offers-newest:checked~.checked-radio-bg {
            width: 81px;
            left: 163px;
        }

        .navbar-signin {
            display: flex;
            align-items: center;
            position: relative;
            font-size: var(--font-size-small);
            padding-right: 50px;
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Add new CSS for admin username */
        .user-menu.admin .username {
            padding-right: 60px;
        }

        /* Style for admin */
        .meomeo.admin .navbar-nav {
            gap: 45px;
        }

        :root {
            --oxford-blue: rgb(23, 31, 50);
            --light-azure: rgb(62, 122, 220);
            --off-white: #f0f0f0;
            --rich-black-fogra-29: rgb(23, 31, 50);
            --white: #ffffff;
            --light-gray: #d3d3d3;
            --vermilion: #ff4d4d;
            --fw5: 500;
            --fw6: 600;
            --font-size-small: 14px;
        }
    </style>
</head>

<body>
    <?php if ($is_logged_in && $role_message): ?>
        <div class="notification-bar">
            <span class="notification-message"><?= htmlspecialchars($role_message) ?></span>
        </div>
    <?php endif; ?>

    <div class="page-content">
        <div class="container">
            <header>
                <div class="navbar">
                    <button class="navbar-menu-btn"></button>
                    <a href="#" class="navbar-brand">
                        <img src="/image-Photoroom.png" alt="logo">
                    </a>
                    <nav class="meomeo <?php echo $content_for_admin ? 'admin' : ''; ?>">
                        <ul class="navbar-nav">
                            <li><a href="#" class="navbar-link home-link">Home</a></li>
                            <li><a href="#offers" class="navbar-link offers-link">Offers</a></li>
                            <?php if ($content_for_customer): ?>
                                <li><a href="#live" class="navbar-link indicator live-link">LIVE</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="navbar-actions">
                        <form action="#" class="navbar-form">
                            <input type="text" name="search" placeholder="Search here" class="navbar-form-search" autocomplete="off" value="<?= htmlspecialchars($search_query) ?>">
                            <button class="navbar-form-btn">
                                <ion-icon name="search"></ion-icon>
                            </button>
                            <button class="navbar-form-close">
                                <ion-icon name="close-circle-outline"></ion-icon>
                            </button>
                            <div class="search-suggestions" style="display: none;"></div>
                        </form>
                        <button class="navbar-search-btn">
                            <ion-icon name="search"></ion-icon>
                        </button>
                        <div class="navbar-signin">
                            <div class="user-menu <?php echo $content_for_admin ? 'admin' : ''; ?>">
                                <?php if ($is_logged_in): ?>
                                    <span class="username" data-tooltip="Account Management">
                                        <?= htmlspecialchars($_SESSION['username']) ?>
                                        <ion-icon name="chevron-down-outline" class="dropdown-arrow"></ion-icon>
                                    </span>
                                    <?php if (!$content_for_admin): ?>
                                        <a href="cart.php" class="cart-icon" data-tooltip="Cart">
                                            <ion-icon name="cart-outline"></ion-icon>
                                            <?php if ($cart_count > 0): ?>
                                                <span class="cart-count"><?= $cart_count ?></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>
                                    <ul class="dropdown-menu">
                                        <li><a href="cusinfor.php"><ion-icon name="person-outline"></ion-icon>Account Infor</a></li>
                                        <?php if ($content_for_admin): ?>
                                            <li><a href="manapro.php"><ion-icon name="settings-outline"></ion-icon>Manage Games</a></li>
                                            <li><a href="manacus.php"><ion-icon name="people-outline"></ion-icon>Manage Users</a></li>
                                        <?php endif; ?>
                                        <li><a href="login.php"><ion-icon name="swap-horizontal-outline"></ion-icon>Switch Account</a></li>
                                        <li><a class="logout-link" style="cursor: pointer;"><ion-icon name="log-out-outline"></ion-icon>Logout</a></li>
                                    </ul>
                                <?php else: ?>
                                    <a href="login.php" class="username">
                                        Login
                                        <ion-icon name="log-in"></ion-icon>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main>
                <section class="banner">
                    <div class="banner-card">
                        <img src="https://images2.thanhnien.vn/528068263637045248/2023/12/8/monster-hunter-wilds-1702048878670561605042.jpg" alt="Monster Hunter Wilds" class="banner-img active">
                        <img src="https://cdn.akamai.steamstatic.com/apps/csgo/images/csgo_react/social/cs2.jpg" alt="Counter Strike 2" class="banner-img">
                        <img src="/hinh-nen-black-myth-wukong-28.jpeg" alt="bmw" class="banner-img" id="special-image">
                        <img src="https://r4.wallpaperflare.com/wallpaper/944/117/262/call-of-duty-black-ops-call-of-duty-minimalism-video-games-wallpaper-0b665cfd43315f8995e4fba98d4c6c20.jpg" alt="cod" class="banner-img" id="cod">
                        <img src="https://4kwallpapers.com/images/walls/thumbs_3t/7726.jpg" alt="switches" class="banner-img">
                    </div>
                    <div class="banner-dots">
                        <span class="dot" data-index="0"></span>
                        <span class="dot" data-index="1"></span>
                        <span class="dot" data-index="2"></span>
                        <span class="dot" data-index="3"></span>
                        <span class="dot" data-index="4"></span>
                    </div>
                </section>

                <section class="movies" id="movies">
                    <div class="filter-bar">
                        <span class="filter-title">All Games</span>
                        <div class="filter-dropdowns">
                            <select name="year" class="year">
                                <option value="all">All Years</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year ?>"><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="price" class="price">
                                <option value="all">All Prices</option>
                                <option value="under_20">Under $20</option>
                                <option value="20_50">$20 - $50</option>
                                <option value="above_50">Above $50</option>
                            </select>
                        </div>
                        <div class="filter-radios">
                            <input type="radio" name="grade" id="featured" checked>
                            <label for="featured">Featured</label>
                            <input type="radio" name="grade" id="popular">
                            <label for="popular">Popular</label>
                            <input type="radio" name="grade" id="newest">
                            <label for="newest">Newest</label>
                            <div class="checked-radio-bg"></div>
                        </div>
                    </div>

                    <div class="movies-grid" id="movies-grid">
                        <?php
                        $items_per_row = 7;
                        $rows_initial = 2;
                        $items_per_page = $items_per_row * $rows_initial;

                        $display_products = !empty($search_query) ? $search_results : $all_products;
                        $total_products = count($all_products);
                        $initial_products = array_slice($display_products, 0, $items_per_page);

                        if (empty($display_products) && !empty($search_query)) {
                            echo '<div class="no-results">Not Found</div>';
                        } else {
                            foreach ($initial_products as $product): ?>
                                <div class="movie-card">
                                    <div class="card-head">
                                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="card-img">
                                        <?php if (isset($product['discount'])): ?>
                                            <div class="discount-badge">-<?= $product['discount'] ?>%</div>
                                        <?php endif; ?>
                                        <?php if ($is_logged_in && !$content_for_admin): ?>
                                            <div class="card-overlay">
                                                <?php if ($content_for_customer): ?>
                                                    <div class="cart-cart">
                                                        <button class="add-to-cart" data-product-name="<?= htmlspecialchars($product['name']) ?>">
                                                            <ion-icon name="cart"></ion-icon>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="rating">
                                                    <ion-icon name="star-outline"></ion-icon>
                                                    <span><?= $product['rating'] ?></span>
                                                </div>
                                                <div class="cart">
                                                    <ion-icon name="cart-outline"></ion-icon>
                                                    <span class="price">
                                                        <?php if (isset($product['original_price'])): ?>
                                                            <span class="original-price">$<?= number_format($product['original_price'], 2) ?></span>
                                                            <span class="discounted-price">$<?= number_format($product['discounted_price'], 2) ?></span>
                                                        <?php else: ?>
                                                            $<?= number_format($product['price'], 2) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h3 class="card-title"><?= htmlspecialchars($product['name']) ?></h3>
                                    </div>
                                </div>
                        <?php endforeach;
                        }
                        ?>
                    </div>
                    <?php if ($total_products > $items_per_page): ?>
                        <button class="load-more" id="load-more">Load More</button>
                    <?php endif; ?>
                </section>

                <?php if (!$is_logged_in): ?>
                    <div class="welcome-notification">
                        <span class="notification-message">
                            Welcome to GameStore!<br>
                            Please log in to experience all features!
                        </span>
                        <button class="notification-close">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                <?php endif; ?>

                <section class="offers" id="offers">
                    <div class="filter-bar offers-filter">
                        <span class="filter-title">Special Offers</span>
                        <div class="filter-dropdowns">
                            <select name="year" class="year offers-year">
                                <option value="all">All Years</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year ?>"><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="price" class="price offers-price">
                                <option value="all">All Prices</option>
                                <option value="under_20">Under $20</option>
                                <option value="20_50">$20 - $50</option>
                                <option value="above_50">Above $50</option>
                            </select>
                        </div>
                        <div class="filter-radios offers-radios">
                            <input type="radio" name="offers-grade" id="offers-featured" checked>
                            <label for="offers-featured">Featured</label>
                            <input type="radio" name="offers-grade" id="offers-popular">
                            <label for="offers-popular">Popular</label>
                            <input type="radio" name="offers-grade" id="offers-newest">
                            <label for="offers-newest">Newest</label>
                            <div class="checked-radio-bg"></div>
                        </div>
                    </div>

                    <div class="offers-grid" id="offers-grid">
                        <?php foreach ($offers as $index => $offer): ?>
                            <div class="offer-card" <?php if ($index >= 14) echo 'style="display: none;"'; ?> data-year="<?= htmlspecialchars($offer['year']) ?>" data-price="<?= htmlspecialchars($offer['discounted_price']) ?>" data-rating="<?= htmlspecialchars($offer['rating']) ?>">
                                <div class="card-head">
                                    <img src="<?= htmlspecialchars($offer['image']) ?>" alt="<?= htmlspecialchars($offer['name']) ?>" class="card-img">
                                    <div class="discount-badge">-<?= $offer['discount'] ?>%</div>
                                    <?php if ($is_logged_in && !$content_for_admin): ?>
                                        <div class="card-overlay">
                                            <?php if ($content_for_customer): ?>
                                                <div class="cart-cart">
                                                    <button class="add-to-cart" data-product-name="<?= htmlspecialchars($offer['name']) ?>">
                                                        <ion-icon name="cart"></ion-icon>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                            <div class="rating">
                                                <ion-icon name="star-outline"></ion-icon>
                                                <span><?= $offer['rating'] ?></span>
                                            </div>
                                            <div class="cart">
                                                <span class="price">
                                                    <span class="original-price">$<?= number_format($offer['original_price'], 2) ?></span>
                                                    <span class="discounted-price">$<?= number_format($offer['discounted_price'], 2) ?></span>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h3 class="card-title"><?= htmlspecialchars($offer['name']) ?></h3>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="load-more" id="offers-load-more">Load More</button>
                </section>

                <?php if ($content_for_customer): ?>
                    <section class="live" id="live">
                        <h2 class="section-heading">Livestream Game</h2>
                        <div class="live-container">
                            <button class="scroll-btn left" aria-label="Scroll Left"><ion-icon name="chevron-back-outline"></ion-icon></button>
                            <div class="live-grid">
                                <div class="live-card">
                                    <a href="https://www.youtube.com/watch?v=Vn2sHwIM2HQ&pp=ygVlKE5vYm9keSAtIFRoZSBUdXJuYXJvdW5kICMzKSBUaOG6p24gxJHhu5NuZyDEkOG7mSBNaXhpIHRy4buVIHTDoGkgcGjhu5UgbmjhuqFjLCBuaGVuIG5ow7NtIMO9IMSR4buLbmjSBwkJvQCDtaTen9Q%3D" target="_blank" class="live-link">
                                        <div class="card-head">
                                            <img src="https://cafebiz.cafebizcdn.vn/162123310254002176/2021/1/26/photo1611626730757-1611626731057878408616.jpg" alt="image" class="card-img">
                                            <div class="live-badge">LIVE</div>
                                            <div class="total-viewers">3M viewers</div>
                                            <div class="cart">
                                                <ion-icon name="play-sharp"></ion-icon>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <img src="https://upload.wikimedia.org/wikipedia/vi/a/a5/Mixigaming-Logo.jpg" alt="image" class="avatar">
                                            <h3 class="card-title">(Nobody - The Turnaround #3) The genius Do Mixi showcases his music skills, sparking ideas...</h3>
                                        </div>
                                    </a>
                                </div>
                                <div class="live-card">
                                    <a href="https://www.youtube.com/watch?v=tEzJtG1Uvyo&t=2234s" target="_blank" class="live-link">
                                        <div class="card-head">
                                            <img src="https://i.ytimg.com/vi/qN6_T3vL5QM/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGGUgRyhAMA8=&rs=AOn4CLDSJt5LlNQo6K-JB-gnwJFG-xNfgw" alt="image" class="card-img">
                                            <div class="live-badge">LIVE</div>
                                            <div class="total-viewers">1.7M viewers</div>
                                            <div class="cart">
                                                <ion-icon name="play-sharp"></ion-icon>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <img src="https://yt3.googleusercontent.com/a0_cx-_0C__vVw6xb3lP64_Z_vQL55wjZEjayJNX-MKaAgLgneYorFrligf5QAycRYiqBN3hOA=s160-c-k-c0x00ffffff-no-rj" alt="image" class="avatar">
                                            <h3 class="card-title">TRUNG REACTION | YOUR MIXIGAMING COMPANY IS SEVERAL TIMES BIGGER...</h3>
                                        </div>
                                    </a>
                                </div>
                                <div class="live-card">
                                    <a href="https://www.youtube.com/watch?v=oUEcnDYWZ64&t=52s&pp=ygVVUGV3UGV3IMSR4bq3dCBjaMOobiDEkeG6v24gTWl4aSBDaXR5IC08YnI-IELDoGkgaOG7jWM6IMSR4burbmcgYmFvIGdp4budIHRpbiBuZ8aw4budaQ%3D%3D" target="_blank" class="live-link">
                                        <div class="card-head">
                                            <img src="https://newsmd2fr.keeng.vn/tiin/archive/media/20180915/112610255947122.jpg" alt="image" class="card-img">
                                            <div class="live-badge">LIVE</div>
                                            <div class="total-viewers">2.6M viewers</div>
                                            <div class="cart">
                                                <ion-icon name="play-sharp"></ion-icon>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <img src="https://yt3.googleusercontent.com/ykA8H0rRKEtauV-O_bqUtmZbn6rohzDcRrn6TRJ6_tjM3fZQOxZiyJrg2TLHNbNirMGwODd6HIE=s160-c-k-c0x00ffffff-no-rj" alt="image" class="avatar">
                                            <h3 class="card-title">PewPew sets foot in Mixi City -<br> Lesson: never trust people | GTA 5 ROLE PLAY | PewPew</h3>
                                        </div>
                                    </a>
                                </div>
                                <div class="live-card">
                                    <a href="https://www.youtube.com/watch?v=xipfnBVHFBc&pp=ygUGYm9tbWFu" target="_blank" class="live-link">
                                        <div class="card-head">
                                            <img src="https://i.ytimg.com/vi/hmTehfG9m88/maxresdefault.jpg" alt="image" class="card-img">
                                            <div class="live-badge">LIVE</div>
                                            <div class="total-viewers">21K viewers</div>
                                            <div class="cart">
                                                <ion-icon name="play-sharp"></ion-icon>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <img src="https://yt3.ggpht.com/V8TIv129UM7pBcVXrwTv-QBwOwWPryuwe0OO8Mn8sp2ooXy2VJVGoPikI71uiIE0qLPUyyTLi7k=s88-c-k-c0x00ffffff-no-rj" alt="image" class="avatar">
                                            <h3 class="card-title">PLAYING GAMES WITH MINISTERS OF THE MINISTRY OF INFORMATION</h3>
                                        </div>
                                    </a>
                                </div>
                                <!-- New Card 1 -->
                                <div class="live-card">
                                    <a href="https://www.youtube.com/watch?v=rqlKd0RCqeE" target="_blank" class="live-link">
                                        <div class="card-head">
                                            <img src="https://i.ytimg.com/vi/M-cVGkM9tWU/maxresdefault.jpg" alt="image" class="card-img">
                                            <div class="live-badge">LIVE</div>
                                            <div class="total-viewers">1.2M viewers</div>
                                            <div class="cart">
                                                <ion-icon name="play-sharp"></ion-icon>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <img src="https://yt3.googleusercontent.com/ytc/AIdro_lup6wq8MVTto8jQMYXZaXtQHT46ghA_4w2HXmE1mmPSrM=s160-c-k-c0x00ffffff-no-rj" alt="image" class="avatar">
                                            <h3 class="card-title">(Schedule I) THE DURIAN SELLING MISSION OF THE "SPECIAL NEED" TEAM</h3>
                                        </div>
                                    </a>
                                </div>
                                <!-- New Card 2 -->
                                <div class="live-card">
                                    <a href="https://www.youtube.com/watch?v=vFqKirImnIg" target="_blank" class="live-link">
                                        <div class="card-head">
                                            <img src="https://genk.mediacdn.vn/139269124445442048/2023/4/3/d7c66c67d29869b5eba9f2dfdc90f9ff-1680511111746-1680511112298275817864.jpg" alt="image" class="card-img">
                                            <div class="live-badge">LIVE</div>
                                            <div class="total-viewers">900K viewers</div>
                                            <div class="cart">
                                                <ion-icon name="play-sharp"></ion-icon>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <img src="https://yt3.googleusercontent.com/YaAFWY03ER0DfF77HAyMqNlRxmJiSEDq_I7ZF0MlcgRcVzOhIhZfB8QlwNhAuVXZesi2I2zy=s160-c-k-c0x00ffffff-no-rj " alt="image" class="avatar">
                                            <h3 class="card-title">(Dollmare #1) The unstable working days of Do Mixi and his...</h3>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <button class="scroll-btn right" aria-label="Scroll Right"><ion-icon name="chevron-forward-outline"></ion-icon></button>
                        </div>
                    </section>
                <?php endif; ?>
            </main>

            <footer>
                <div class="footer-content">
                    <div class="footer-brand">
                        <img src="/image-Photoroom.png" alt="GameStore Logo" class="footer-logo" style="width: 70px; height: auto;">
                        <p class="slogan">Games Shows, Online Game, Game Database</p>
                        <div class="social-link">
                            <?php foreach (['facebook', 'instagram', 'tiktok', 'youtube'] as $social): ?>
                                <a href="#" aria-label="<?= ucfirst($social) ?>">
                                    <ion-icon name="logo-<?= $social ?>"></ion-icon>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="footer-links">
                        <?php foreach ($footer2_links as $heading => $links): ?>
                            <ul>
                                <?php if ($heading): ?>
                                    <h4 class="link-heading"><?= $heading ?></h4>
                                <?php endif; ?>
                                <?php foreach ($links as $link): ?>
                                    <li class="link-item"><a href="myprofile.php"><?= $link ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                        <?php foreach ($footer_links as $heading => $links): ?>
                            <ul>
                                <?php if ($heading): ?>
                                    <h4 class="link-heading"><?= $heading ?></h4>
                                <?php endif; ?>
                                <?php foreach ($links as $link): ?>
                                    <li class="link-item"><a href="#"><?= $link ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>© Copyright 2025 GameStore</p>
                    <div class="wrapper">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms and Conditions</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="/welcome.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let mainProducts = <?= json_encode($main_products, JSON_UNESCAPED_UNICODE) ?>;
            const offersProducts = <?= json_encode($offers, JSON_UNESCAPED_UNICODE) ?>;
            const allProducts = <?= json_encode(array_merge($main_products, $offers), JSON_UNESCAPED_UNICODE) ?>;
            const itemsPerRow = 7;
            const rowsPerLoad = 2;
            const itemsPerPage = itemsPerRow * rowsPerLoad;
            let currentMainItems = itemsPerPage;

            const updatedProducts = sessionStorage.getItem('updatedProducts');
            if (updatedProducts) {
                mainProducts = JSON.parse(updatedProducts);
                sessionStorage.removeItem('updatedProducts');
            }

            const moviesGrid = document.getElementById('movies-grid');
            const loadMoreBtn = document.getElementById('load-more');
            const yearSelect = document.querySelector('.year');
            const priceSelect = document.querySelector('.price');
            const radioButtons = document.querySelectorAll('input[name="grade"]');

            const offersGrid = document.getElementById('offers-grid');
            const offersLoadMoreBtn = document.getElementById('offers-load-more');
            const offersYearSelect = document.querySelector('.offers-year');
            const offersPriceSelect = document.querySelector('.offers-price');
            const offersRadioButtons = document.querySelectorAll('input[name="offers-grade"]');

            function createProductCard(product) {
                const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
                const isCustomer = <?= $content_for_customer ? 'true' : 'false' ?>;
                const isAdmin = <?= $content_for_admin ? 'true' : 'false' ?>;
                const card = document.createElement('div');
                card.className = 'movie-card';
                card.innerHTML = `
                    <div class="card-head">
                        <img src="${product.image}" alt="${product.name}" class="card-img">
                        ${product.discount ? `<div class="discount-badge">-${product.discount}%</div>` : ''}
                        ${isLoggedIn && !isAdmin ? `
                            <div class="card-overlay">
                                ${isCustomer ? `
                                    <div class="cart-cart">
                                        <button class="add-to-cart" data-product-name="${product.name}">
                                            <ion-icon name="cart"></ion-icon>
                                        </button>
                                    </div>
                                ` : ''}
                                <div class="rating">
                                    <ion-icon name="star-outline"></ion-icon>
                                    <span>${product.rating}</span>
                                </div>
                                <div class="cart">
                                    <ion-icon name="cart-outline"></ion-icon>
                                    <span class="price">
                                        ${product.original_price ? `
                                            <span class="original-price">$${product.original_price.toFixed(2)}</span>
                                            <span class="discounted-price">$${product.discounted_price.toFixed(2)}</span>
                                        ` : `$${product.price.toFixed(2)}`}
                                    </span>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">${product.name}</h3>
                    </div>
                `;

                card.addEventListener('click', function(e) {
                    if (e.target.closest('.add-to-cart') || e.target.closest('.rating')) return;
                    if (!isLoggedIn) {
                        showNotification("Please log in to experience all features!", false);
                    } else {
                        const queryString = new URLSearchParams(product).toString();
                        window.location.href = `proinfor.php?${queryString}`;
                    }
                });

                return card;
            }

            function isYearInRange(year, range) {
                const productYear = parseInt(year);
                if (range === 'all') return true;
                if (range === '2025') return productYear === 2025;
                if (range === '2020-2024') return productYear >= 2020 && productYear <= 2024;
                if (range === '2010-2019') return productYear >= 2010 && productYear <= 2019;
                if (range === '2000-2009') return productYear >= 2000 && productYear <= 2009;
                if (range === '1980-1999') return productYear >= 1980 && productYear <= 1999;
                return false;
            }

            function updateMainProducts() {
                const year = yearSelect.value;
                const price = priceSelect.value;
                const sortOption = document.querySelector('input[name="grade"]:checked').id;

                let filteredProducts = mainProducts.filter(product => {
                    const matchYear = isYearInRange(product.year, year);
                    const productPrice = product.discounted_price || product.price;
                    const matchPrice = price === 'all' ||
                        (price === 'under_20' && productPrice < 20) ||
                        (price === '20_50' && productPrice >= 20 && productPrice <= 50) ||
                        (price === 'above_50' && productPrice > 50);

                    return matchYear && matchPrice;
                });

                if (sortOption === 'popular') {
                    filteredProducts.sort((a, b) => parseFloat(b.rating) - parseFloat(a.rating));
                } else if (sortOption === 'newest') {
                    filteredProducts.sort((a, b) => parseInt(b.year) - parseInt(a.year));
                }

                moviesGrid.innerHTML = '';
                if (filteredProducts.length > 0) {
                    const initialProducts = filteredProducts.slice(0, itemsPerPage);
                    initialProducts.forEach(product => {
                        const card = createProductCard(product);
                        moviesGrid.appendChild(card);
                    });
                    attachAddToCartEvents();
                    currentMainItems = itemsPerPage;

                    if (filteredProducts.length > itemsPerPage) {
                        loadMoreBtn.style.display = 'block';
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                } else {
                    moviesGrid.innerHTML = '';
                    loadMoreBtn.style.display = 'none';
                }

                loadMoreBtn.onclick = function() {
                    const nextItems = filteredProducts.slice(currentMainItems, currentMainItems + itemsPerPage);
                    nextItems.forEach(product => {
                        const card = createProductCard(product);
                        moviesGrid.appendChild(card);
                    });
                    currentMainItems += itemsPerPage;
                    if (currentMainItems >= filteredProducts.length) {
                        loadMoreBtn.style.display = 'none';
                    }
                    attachAddToCartEvents();
                };
            }

            function updateOffers() {
                const year = offersYearSelect.value;
                const price = offersPriceSelect.value;
                const sortOption = document.querySelector('input[name="offers-grade"]:checked').id;

                const offerCards = document.querySelectorAll('.offer-card');
                let filteredOffers = Array.from(offerCards).map(card => ({
                    element: card,
                    year: card.dataset.year,
                    price: parseFloat(card.dataset.price),
                    rating: parseFloat(card.dataset.rating)
                }));

                filteredOffers = filteredOffers.filter(offer => {
                    const matchYear = isYearInRange(offer.year, year);
                    const matchPrice = price === 'all' ||
                        (price === 'under_20' && offer.price < 20) ||
                        (price === '20_50' && offer.price >= 20 && offer.price <= 50) ||
                        (price === 'above_50' && offer.price > 50);
                    return matchYear && matchPrice;
                });

                if (sortOption === 'offers-popular') {
                    filteredOffers.sort((a, b) => b.rating - a.rating);
                } else if (sortOption === 'offers-newest') {
                    filteredOffers.sort((a, b) => parseInt(b.year) - parseInt(a.year));
                }

                offerCards.forEach(card => card.style.display = 'none');
                filteredOffers.slice(0, 14).forEach((offer, index) => {
                    offer.element.style.display = 'block';
                });

                let visibleOffers = 14;
                if (filteredOffers.length > 14) {
                    offersLoadMoreBtn.style.display = 'block';
                    offersLoadMoreBtn.onclick = function() {
                        const nextOffers = filteredOffers.slice(visibleOffers, visibleOffers + 7);
                        nextOffers.forEach(offer => offer.element.style.display = 'block');
                        visibleOffers += 7;
                        if (visibleOffers >= filteredOffers.length) {
                            offersLoadMoreBtn.style.display = 'none';
                        }
                    };
                } else {
                    offersLoadMoreBtn.style.display = 'none';
                }

                offerCards.forEach(card => {
                    card.addEventListener('click', function(e) {
                        if (e.target.closest('.add-to-cart') || e.target.closest('.rating')) return;
                        const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
                        if (!isLoggedIn) {
                            showNotification("Please log in to experience all features!", false);
                        } else {
                            const product = offersProducts.find(p => p.name === card.querySelector('.card-title').textContent);
                            const queryString = new URLSearchParams(product).toString();
                            window.location.href = `proinfor.php?${queryString}`;
                        }
                    });
                });
            }

            yearSelect.addEventListener('change', updateMainProducts);
            priceSelect.addEventListener('change', updateMainProducts);
            radioButtons.forEach(radio => radio.addEventListener('change', updateMainProducts));

            offersYearSelect.addEventListener('change', updateOffers);
            offersPriceSelect.addEventListener('change', updateOffers);
            offersRadioButtons.forEach(radio => radio.addEventListener('change', updateOffers));

            updateMainProducts();
            updateOffers();

            // Handle banner
            const bannerImages = document.querySelectorAll('.banner-img');
            let currentIndex = 0;

            function showNextImage() {
                bannerImages[currentIndex].classList.remove('active');
                currentIndex = (currentIndex + 1) % bannerImages.length;
                bannerImages[currentIndex].classList.add('active');
            }

            if (bannerImages.length > 0) {
                bannerImages[currentIndex].classList.add('active');
                setInterval(showNextImage, 3000);
            }

            // Handle navbar
            const notificationBar = document.querySelector('.notification-bar');
            const pageContent = document.querySelector('.page-content');
            const body = document.body;
            const navbar = document.querySelector('.navbar');
            let lastScrollTop = 0;

            if (notificationBar && pageContent && navbar) {
                body.classList.add('notification-active');
                const notificationHeight = notificationBar.offsetHeight;

                navbar.style.marginTop = `${notificationHeight}px`;
                pageContent.style.marginTop = `${notificationHeight}px`;

                setTimeout(() => {
                    notificationBar.classList.add('hide');
                }, 2000);

                notificationBar.addEventListener('animationend', (event) => {
                    if (event.animationName === 'slideUp') {
                        body.classList.remove('notification-active');
                        navbar.style.marginTop = '0';
                        navbar.style.top = '0';
                        navbar.style.transform = 'translateY(0)';
                        pageContent.style.marginTop = '0';
                        pageContent.style.paddingTop = '0';
                    }
                });
            }

            function updateNavbar() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                if (scrollTop > 120) {
                    navbar.classList.add('fixed');
                    if (body.classList.contains('notification-active')) {
                        navbar.style.marginTop = `${notificationBar.offsetHeight}px`;
                    } else {
                        navbar.style.marginTop = '0';
                    }
                } else {
                    navbar.classList.remove('fixed');
                    navbar.style.transform = 'translateY(0)';
                    navbar.style.marginTop = body.classList.contains('notification-active') ? `${notificationBar.offsetHeight}px` : '0';
                }

                lastScrollTop = scrollTop;
            }

            updateNavbar();
            window.addEventListener('scroll', updateNavbar);

            // Handle adding to cart
            function attachAddToCartEvents() {
                const addToCartButtons = document.querySelectorAll('.add-to-cart');
                addToCartButtons.forEach(button => {
                    button.removeEventListener('click', handleAddToCart);
                    button.addEventListener('click', handleAddToCart);
                });
            }

            function handleAddToCart(e) {
                e.preventDefault();
                const productName = this.getAttribute('data-product-name');
                fetch(`?add_to_cart=${encodeURIComponent(productName)}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showNotification(`Game ${data.product_name} has been added to Cart`);
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            } else if (data.cart_count > 0) {
                                const cartIcon = document.querySelector('.cart-icon');
                                if (cartIcon) cartIcon.insertAdjacentHTML('beforeend', `<span class="cart-count">${data.cart_count}</span>`);
                            }
                        } else {
                            showNotification(data.error || `Game ${data.product_name} is already in the cart!`, false);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification(`An error occurred: ${error.message}`, false);
                    });
            }

            attachAddToCartEvents();

            // Handle search
const searchInput = document.querySelector('.navbar-form-search');
const suggestionsContainer = document.querySelector('.search-suggestions');
const searchForm = document.querySelector('.navbar-form');
const bannerSection = document.querySelector('.banner');
const offersSection = document.querySelector('.offers');
const liveSection = document.querySelector('.live');
const liveLink = document.querySelector('.live-link');
const homeLink = document.querySelector('.home-link');
const offersLink = document.querySelector('.offers-link');
let debounceTimer;

function updateSearchResults(results) {
    moviesGrid.innerHTML = '';
    if (results.length > 0) {
        results.forEach(product => {
            const card = createProductCard(product);
            moviesGrid.appendChild(card);
        });
        attachAddToCartEvents();
    } else {
        moviesGrid.innerHTML = '<div class="no-results">No Results Found</div>';
    }
}

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value.trim();

    if (query === '') {
        // Restore initial state
        bannerSection.style.display = 'block';
        offersSection.style.display = 'block';
        if (liveSection) liveSection.style.display = 'block';
        if (liveLink) liveLink.style.display = 'block';
        loadMoreBtn.style.display = mainProducts.length > itemsPerPage ? 'block' : 'none';
        homeLink.style.display = 'block';
        offersLink.style.display = 'block';

        updateMainProducts();
        updateOffers();
        suggestionsContainer.style.display = 'none';
        return;
    }

    bannerSection.style.display = 'none';
    offersSection.style.display = 'none';
    if (liveSection) liveSection.style.display = 'none';
    if (liveLink) liveLink.style.display = 'none';
    loadMoreBtn.style.display = 'none';
    homeLink.style.display = 'none';
    offersLink.style.display = 'none';

    debounceTimer = setTimeout(() => {
        fetch(`?search=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateSearchResults(data);

            suggestionsContainer.innerHTML = '';
            if (data.length > 0) {
                data.slice(0, 5).forEach(product => {
                    const suggestion = document.createElement('div');
                    suggestion.className = 'suggestion-item';
                    suggestion.innerHTML = `
                        <img src="${product.image}" alt="${product.name}">
                        <span>${product.name}</span>
                    `;
                    suggestion.addEventListener('click', () => {
                        searchInput.value = product.name;
                        suggestionsContainer.style.display = 'none';
                        window.location.href = `?search=${encodeURIComponent(product.name)}`;
                    });
                    suggestionsContainer.appendChild(suggestion);
                });
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            suggestionsContainer.style.display = 'none';
        });
    }, 300);
});

searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const query = searchInput.value.trim();
    if (query) {
        window.location.href = `?search=${encodeURIComponent(query)}`;
    }
});

// Handle notification
function showNotification(message, isSuccess = true) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <div class="checkmark ${isSuccess ? '' : 'warning'}">
            <ion-icon name="${isSuccess ? 'checkmark' : 'warning'}"></ion-icon>
        </div>
        <span class="message">${message}</span>
    `;
    document.body.appendChild(notification);

    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Handle logout confirmation
const logoutLink = document.querySelector('.logout-link');
if (logoutLink) {
    logoutLink.addEventListener('click', function(e) {
        e.preventDefault();
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.innerHTML = `
            <div class="checkmark warning">
                <ion-icon name="warning"></ion-icon>
            </div>
            <span class="message">Are you sure you want to log out?</span>
            <div class="notification-buttons">
                <button class="cart-btn">Yes</button>
                <button class="cart-btn cancel-logout">No</button>
            </div>
        `;
        document.body.appendChild(notification);

        setTimeout(() => notification.classList.add('show'), 10);

        notification.querySelector('.cart-btn').addEventListener('click', () => {
            window.location.href = '?logout=true';
        });

        notification.querySelector('.cancel-logout').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    });
}

// Handle banner dots
const dots = document.querySelectorAll('.dot');
dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        bannerImages[currentIndex].classList.remove('active');
        dots[currentIndex].classList.remove('active');
        currentIndex = index;
        bannerImages[currentIndex].classList.add('active');
        dots[currentIndex].classList.add('active');
    });
});

dots[currentIndex].classList.add('active');

// Handle live section scrolling
const liveGrid = document.querySelector('.live-grid');
const scrollLeftBtn = document.querySelector('.scroll-btn.left');
const scrollRightBtn = document.querySelector('.scroll-btn.right');

if (liveGrid && scrollLeftBtn && scrollRightBtn) {
    scrollLeftBtn.addEventListener('click', () => {
        liveGrid.scrollBy({ left: -300, behavior: 'smooth' });
    });

    scrollRightBtn.addEventListener('click', () => {
        liveGrid.scrollBy({ left: 300, behavior: 'smooth' });
    });
}
});
    </script>
</body>
</html>