<?php
session_start();

// Check user permissions
$is_logged_in = isset($_SESSION['user_id']);
$content_for_admin = $is_logged_in && ($_SESSION['role'] === 'admin');

// Get product information from query string
$product = $_GET;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Information - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <!-- Add Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, transparent 20%, #000a);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container-wrapper {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        .container:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        h1 {
            color: #1877f2;
            text-align: center;
        }
        .product-img {
            width: 300px;
            height: 400px;
            object-fit: cover;
            border-radius: 5px;
            display: block;
            margin: 0 auto;
            margin-bottom: 20px;
        }
        p {
            margin: 0.5rem 0;
        }
        .price {
            color: #28a745;
            font-weight: bold;
        }
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
        }
        .admin-buttons {
            margin-top: 1rem;
            text-align: center;
        }
        .admin-buttons button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            color: white;
        }
        .edit-btn {
            background-color: #007bff;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
        .rating {
            align-items: center;
        }
        .rating ion-icon {
            color: #f1c40f; /* Yellow color for stars */
            margin-bottom: -1px;
        }
    </style>
</head>
<body>
<a href="welcome.php" class="container-wrapper">
    <div class="container">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
        <p><strong>Price:</strong> 
            <?php 
            if (isset($product['discount']) && $product['discount'] > 0) {
                $original_price = floatval($product['price']);
                $discounted_price = $original_price * (1 - $product['discount'] / 100);
                echo '<span class="original-price">$' . number_format($original_price, 2) . '</span> ';
                echo '<span class="price">$' . number_format($discounted_price, 2) . '</span>';
            } else {
                echo '<span class="price">$' . number_format($product['price'], 2) . '</span>';
            }
            ?>
        </p>
        <?php if (isset($product['discount']) && $product['discount'] > 0): ?>
            <p><strong>Discount:</strong> <?php echo htmlspecialchars($product['discount']); ?>%</p>
        <?php endif; ?>
        <p class="rating"><strong>Rating:</strong> <?php echo isset($product['rating']) ? htmlspecialchars($product['rating']) : 'Not yet rated'; ?>
            <?php if (isset($product['rating'])): ?>
                <ion-icon name="star"></ion-icon>
            <?php endif; ?>
        </p>
        <p><strong>Release Year:</strong> <?php echo isset($product['year']) ? htmlspecialchars($product['year']) : 'Not specified'; ?></p>
    </div>
</a>
</body>
</html>
