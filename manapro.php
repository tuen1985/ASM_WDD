<?php
session_start();

// Check admin rights
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle logout directly
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Connect to database
$servername = "103.75.184.31";
$username = "tovjaghhhosting_NguyenVanTuyen";
$password = "123abcD!";
$database = "tovjaghhhosting_sdlcsql";

$connect = new mysqli($servername, $username, $password, $database);
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Process product deletion
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];

    // Get product information to check discount
    $stmt = $connect->prepare("SELECT product_name, discount FROM products WHERE product_id = ?");
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product) {
        // Delete product from database
        $stmt = $connect->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("s", $product_id);
        if ($stmt->execute()) {
            // Update welcome.php
            $welcome_file = "welcome.php";
            $welcome_content = file_get_contents($welcome_file);

            if ($product['discount'] > 0) {
                // Remove from $offers
                preg_match('/\$offers\s*=\s*(\[.*?\]);/s', $welcome_content, $matches);
                if ($matches) {
                    $offers_str = $matches[1];
                    eval('$offers = ' . $offers_str);
                    $offers = array_filter($offers, fn($offer) => $offer['name'] !== $product['product_name']);
                    $new_offers_str = var_export($offers, true) . ";";
                    $welcome_content = preg_replace('/\$offers\s*=\s*\[.*?\];/s', "\$offers = $new_offers_str", $welcome_content);
                }
            } else {
                // Delete from $main_products
                preg_match('/\$main_products\s*=\s*array_merge\(fetchProducts\(\),\s*(\[.*?\])\);/s', $welcome_content, $main_matches);
                if ($main_matches) {
                    $main_str = $main_matches[1];
                    eval('$main_products = ' . $main_str . ';');
                    $main_products = array_filter($main_products, fn($p) => $p['name'] !== $product['product_name']);
                    $new_main_str = var_export($main_products, true) . ";";
                    $welcome_content = preg_replace('/\$main_products\s*=\s*array_merge\(fetchProducts\(\),\s*\[.*?\]\);/s', "\$main_products = array_merge(fetchProducts(), $new_main_str);", $welcome_content);
                }
            }

            file_put_contents($welcome_file, $welcome_content);
            header("Location: manapro.php?message=Product+deleted+successfully");
        } else {
            header("Location: manapro.php?message=Error+while+deleting+product");
        }
        $stmt->close();
    } else {
        header("Location: manapro.php?message=Product+does+not+exist");
    }
    exit();
}

// Get the product list
$products = [];
$result = $connect->query("SELECT * FROM products");
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$connect->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Product</title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Navbar styles */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: var(--oxford-blue, rgb(23, 31, 50));
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 15px 30px;
            background-color: rgb(23, 31, 50);
        }

        .navbar-brand img {
            width: 50px;
            height: auto;
            padding-left: 20px;
        }

        .navbar-title {
            flex-grow: 1;
            text-align: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .navbar-actions {
            display: flex;
            justify-content: flex-end;
        }

        .navbar-signin {
            position: relative;
            display: flex;
            align-items: center;
            font-size: 14px;
            padding-right: 50px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            position: relative;
            margin-right: -47px;
        }

        .username {
            display: inline-flex;
            align-items: center;
            color: var(--off-white, #f0f0f0);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.3s ease;
            padding: 5px 20px;
        }

        .username:hover,
        .username:hover .dropdown-arrow {
            color: var(--light-azure, #2a5298);
        }

        .dropdown-arrow {
            font-size: 16px;
            margin-left: 5px;
            color: var(--off-white, #f0f0f0);
            transition: color 0.3s ease;
        }

        .username[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--oxford-blue, rgb(23, 31, 50));
            color: var(--off-white, #f0f0f0);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1000;
        }

        .username:hover[data-tooltip]::after {
            opacity: 1;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-65%);
            background: var(--oxford-blue, rgb(23, 31, 50));
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            padding: 10px 0;
            z-index: 1000;
            width: 200px;
            min-width: 150px;
        }

        .dropdown-menu.active {
            display: block;
        }

        .dropdown-menu li {
            padding: 8px 20px;
            list-style: none;
        }

        .dropdown-menu a {
            font-size: 14px;
            color: var(--off-white, #f0f0f0);
            text-decoration: none;
            display: block;
            transition: color 0.3s ease;
        }

        .dropdown-menu a:hover {
            color: var(--light-azure, #2a5298);
        }

        .dropdown-menu a ion-icon {
            vertical-align: middle;
            font-size: 17px;
            color: #fff;
            margin-right: 5px;
        }

        /* Logout Notification styles */
        .cart-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            width: 300px;
            max-width: 350px;
            text-align: center;
        }

        .cart-notification.show {
            opacity: 1;
        }

        .cart-notification .checkmark {
            width: 60px;
            height: 60px;
            background: #f39c12;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .cart-notification .checkmark ion-icon {
            font-size: 35px;
        }

        .cart-notification .message {
            font-size: 20px;
            color: #333;
            font-weight: 500;
            word-wrap: break-word;
        }

        .notification-buttons {
            display: flex;
            gap: 50px;
        }

        .cart-notification .cart-btn {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 10px 20px;
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

        /* Main styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: rgb(19, 23, 32);
            margin: 0;
            color: #fff;
        }

        .container {
            max-width: 1150px;
            margin: 0 auto;
            background: rgb(19, 23, 32);
            padding: 30px;
            padding-top: 105px;
        }

        h1 {
            text-align: center;
            color: #2a5298;
            margin-bottom: 30px;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .add-btn {
            display: inline-block;
            background: rgb(23, 31, 50);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s, transform 0.2s;
            margin-bottom: 20px;
        }

        .add-btn:hover {
            background:rgb(0, 73, 176);
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgb(189, 205, 242);
            border-radius: 10px;
            border: 20px rgb(23, 31, 50);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            color: rgb(23, 31, 50);
        }

        th {
            background: rgb(23, 31, 50);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background: rgb(214, 227, 255);
        }

        tr:hover {
            background: rgb(255, 255, 255);
            transition: background 0.3s;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            color: white;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s, transform 0.2s;
        }

        .edit-btn {
            background: #007bff;
            margin-right: 10px;
            text-decoration-line: none;
            height: 55px;
            width: 30px;
        }

        .edit-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .delete-btn {
            background: #dc3545;
        }

        .delete-btn:hover {
            background: #b02a37;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #dc3545;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <header>
        <div class="navbar">
            <div class="container">
                <a href="welcome.php" class="navbar-brand">
                    <img src="/image-Photoroom.png" alt="GameStore Logo">
                </a>
                <div class="navbar-title">
                    Manage Product
                </div>
                <div class="navbar-actions">
                    <div class="navbar-signin">
                        <div class="user-menu">
                            <span class="username" data-tooltip="Account Management">
                                <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                                <ion-icon name="chevron-down-outline" class="dropdown-arrow"></ion-icon>
                            </span>
                            <ul class="dropdown-menu">
                                <li><a href="cusinfor.php"><ion-icon name="person-outline"></ion-icon>Account Infor</a></li>
                                <li><a href="manapro.php"><ion-icon name="settings-outline"></ion-icon>Manage Product</a></li>
                                <li><a href="manacus.php"><ion-icon name="people-outline"></ion-icon>Manage User</a></li>
                                <li><a href="login.php"><ion-icon name="swap-horizontal-outline"></ion-icon>Switch Account</a></li>
                                <li><a class="logout-link" style="cursor: pointer;"><ion-icon name="log-out-outline"></ion-icon>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['message'])): ?>
            <div class="message <?php echo strpos($_GET['message'], 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>
        <a href="add_product.php" class="add-btn">Add New Product</a>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Year</th>
                    <th>Discount (%)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><img src="103.75.184.31<?php echo htmlspecialchars($product['product_img']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-img"></td>
                        <td>$<?php echo number_format($product['product_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($product['year'] ?? 'Undefined'); ?></td>
                        <td><?php echo isset($product['discount']) && $product['discount'] > 0 ? htmlspecialchars($product['discount']) . '%' : 'No discount'; ?></td>
                        <td>
                            <a href="add_product.php?edit_product_id=<?php echo htmlspecialchars($product['product_id']); ?>" class="action-btn edit-btn">Edit</a>
                            <button onclick="if(confirm('Are you sure you want to delete this product?')) window.location.href='manapro.php?delete=<?php echo htmlspecialchars($product['product_id']); ?>'" class="action-btn delete-btn">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Ionicon Scripts -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const username = document.querySelector('.username');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            const logoutLink = document.querySelector('.logout-link');

            // Show/hide dropdown on click
            username.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.classList.toggle('active');
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!username.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('active');
                }
            });

            // Handle logout with confirmation notification
            function showLogoutConfirmation() {
                let notification = document.querySelector('.cart-notification');
                if (notification) {
                    notification.classList.remove('show');
                    notification.remove();
                }

                notification = document.createElement('div');
                notification.className = 'cart-notification';
                notification.innerHTML = `
                    <div class="checkmark">
                        <ion-icon name="warning-outline"></ion-icon>
                    </div>
                    <div class="message">Are you sure you want to log out?</div>
                    <div class="notification-buttons">
                        <button class="cart-btn confirm-logout">Yes</button>
                        <button class="cart-btn cancel-logout">Cancel</button>
                    </div>
                `;
                document.body.appendChild(notification);
                notification.classList.add('show');

                document.querySelector('.confirm-logout').addEventListener('click', function() {
                    window.location.href = '?logout=true';
                });

                document.querySelector('.cancel-logout').addEventListener('click', function() {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300); // Remove after animation completes
                });
            }

            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                showLogoutConfirmation();
            });
        });
    </script>
</body>

</html>
