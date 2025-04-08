<?php
session_start();

// Database connection
function getDBConnection(): mysqli
{
    $conn = new mysqli('103.75.184.31', 'tovjaghhhosting_NguyenVanTuyen', '123abcD!', 'tovjaghhhosting_sdlcsql');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $cart_key = 'cart_' . $_SESSION['user_id'];
    $response = [];
    $conn = getDBConnection();

    if (isset($_GET['update_quantity']) && isset($_GET['index'])) {
        $index = (int)$_GET['index'];
        $action = $_GET['update_quantity'];
        if (isset($_SESSION[$cart_key][$index])) {
            if (!isset($_SESSION[$cart_key][$index]['quantity'])) {
                $_SESSION[$cart_key][$index]['quantity'] = 1;
            }

            // Get maximum quantity from the products table
            $product_name = $_SESSION[$cart_key][$index]['name'];
            $stmt = $conn->prepare("SELECT quantity FROM products WHERE product_name = ?");
            $stmt->bind_param("s", $product_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $max_quantity = $result->num_rows > 0 ? $result->fetch_assoc()['quantity'] : 1; // Default to 1 if not found
            $stmt->close();

            $current_quantity = $_SESSION[$cart_key][$index]['quantity'];

            if ($action === 'increase' && $current_quantity < $max_quantity) {
                $_SESSION[$cart_key][$index]['quantity']++;
            } elseif ($action === 'decrease' && $current_quantity > 1) {
                $_SESSION[$cart_key][$index]['quantity']--;
            }

            $item_total = number_format((float)$_SESSION[$cart_key][$index]['price'] * $_SESSION[$cart_key][$index]['quantity'], 2);

            // Update quantity in DB
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_name = ?");
            $stmt->bind_param("iis", $_SESSION[$cart_key][$index]['quantity'], $_SESSION['user_id'], $_SESSION[$cart_key][$index]['name']);
            $stmt->execute();
            $stmt->close();

            $response = [
                'success' => true,
                'quantity' => $_SESSION[$cart_key][$index]['quantity'],
                'item_total' => $item_total,
                'total_price' => calculateTotalPrice($_SESSION[$cart_key]),
                'max_quantity' => $max_quantity // Return for JS use
            ];
        }
    } elseif (isset($_GET['remove_from_cart'])) {
        $index = (int)$_GET['remove_from_cart'];
        if (isset($_SESSION[$cart_key][$index])) {
            // Remove from DB
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_name = ?");
            $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION[$cart_key][$index]['name']);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION[$cart_key][$index]);
            $_SESSION[$cart_key] = array_values($_SESSION[$cart_key]);
            $response = [
                'success' => true,
                'total_price' => calculateTotalPrice($_SESSION[$cart_key]),
                'cart_count' => count($_SESSION[$cart_key])
            ];
        }
    } elseif (isset($_GET['clear_cart'])) {
        // Clear entire cart from DB
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION[$cart_key]);
        $response = [
            'success' => true,
            'total_price' => 0,
            'cart_count' => 0
        ];
    }

    $conn->close();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Function to calculate total price
function calculateTotalPrice($cart_items)
{
    $total_price = 0;
    foreach ($cart_items as $item) {
        $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
        $total_price += (float)$item['price'] * $quantity;
    }
    return number_format($total_price, 2);
}

// Check login status and role
$is_logged_in = isset($_SESSION['user_id']);
$content_for_customer = $is_logged_in && ($_SESSION['role'] === 'customer');

if (!$content_for_customer) {
    header("Location: login.php");
    exit();
}

// Retrieve cart items
$cart_key = 'cart_' . $_SESSION['user_id'];
$cart_items = isset($_SESSION[$cart_key]) ? $_SESSION[$cart_key] : [];
$total_price = calculateTotalPrice($cart_items);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/cart.css">
    <title>Cart - GameStore</title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <style>
        .clear-cart {
            text-decoration: none;
            display: inline-block;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .quantity-btn {
            background: var(--oxford-blue);
            color: var(--off-white);
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            text-decoration: none;
        }

        .quantity-btn:hover {
            background: var(--light-azure);
        }
    </style>
</head>

<body>
    <div class="cart-actions">
        <a href="welcome.php" class="continue-shopping">← Continue Shopping</a>
    </div>

    <h1 class="title-shop">Your Cart</h1>

    <div class="cart-container" id="cart-items">
        <?php if (empty($cart_items)): ?>
            <p class="empty-cart">Your cart is empty</p>
        <?php else: ?>
            <?php foreach ($cart_items as $index => $item): ?>
                <div class="cart-item" data-index="<?= $index ?>">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <div class="item-info">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="price">$<span class="item-total"><?= number_format((float)$item['price'] * (isset($item['quantity']) ? $item['quantity'] : 1), 2) ?></span></p>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="updateQuantity(<?= $index ?>, 'decrease')">-</button>
                            <span class="quantity"><?= isset($item['quantity']) ? $item['quantity'] : 1 ?></span>
                            <button class="quantity-btn" onclick="updateQuantity(<?= $index ?>, 'increase')">+</button>
                        </div>
                    </div>
                    <div class="item-buttons">
                        <button class="remove-btn" onclick="removeFromCart(<?= $index ?>)">Remove</button>
                        <button class="checkout-item-btn" onclick="checkoutItem(<?= $index ?>)">Buy</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-summary">
        <h3>Total: <span id="total-price">$<?= $total_price ?></span></h3>
        <button class="clear-cart" onclick="clearCart()">Clear All</button>
        <button class="checkout-btn" onclick="checkoutAll()">Buy All</button>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
    function updateQuantity(index, action) {
        fetch(`?update_quantity=${action}&index=${index}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartItem = document.querySelector(`.cart-item[data-index="${index}"]`);
                const quantitySpan = cartItem.querySelector('.quantity');
                const itemTotalSpan = cartItem.querySelector('.item-total');
                quantitySpan.textContent = data.quantity;
                itemTotalSpan.textContent = data.item_total;
                document.getElementById('total-price').textContent = `$${data.total_price}`;

                // Notify when limit is reached
                if (action === 'increase' && data.quantity >= data.max_quantity) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit Reached',
                        text: 'The quantity has reached the maximum for this product!',
                        confirmButtonText: 'OK'
                    });
                } else if (action === 'decrease' && data.quantity <= 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit Reached',
                        text: 'The quantity cannot be less than 1!',
                        confirmButtonText: 'OK'
                    });
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function removeFromCart(index) {
        const productName = document.querySelectorAll('.cart-item h3')[index].textContent;
        Swal.fire({
            title: 'Confirm Removal',
            text: `Are you sure you want to remove the product: ${productName} from your cart?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`?remove_from_cart=${index}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartItem = document.querySelector(`.cart-item[data-index="${index}"]`);
                        cartItem.remove();
                        document.getElementById('total-price').textContent = `$${data.total_price}`;
                        if (data.cart_count === 0) {
                            document.getElementById('cart-items').innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed',
                            text: 'The product has been removed from your cart!',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }

    function clearCart() {
        const cartItems = document.querySelectorAll('.cart-item');
        if (cartItems.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Notice',
                text: 'Your cart is already empty!',
                confirmButtonText: 'OK'
            });
            return;
        }
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to clear your entire cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, clear all!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('?clear_cart=true', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-items').innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                        document.getElementById('total-price').textContent = '$0.00';
                        Swal.fire({
                            icon: 'success',
                            title: 'Cleared',
                            text: 'Your cart has been successfully cleared!',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }

    function checkoutItem(index) {
        const productName = document.querySelectorAll('.cart-item h3')[index].textContent;
        Swal.fire({
            title: 'Confirm Checkout',
            text: `Do you want to checkout the product: ${productName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, checkout!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`?remove_from_cart=${index}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartItem = document.querySelector(`.cart-item[data-index="${index}"]`);
                        cartItem.remove();
                        document.getElementById('total-price').textContent = `$${data.total_price}`;
                        if (data.cart_count === 0) {
                            document.getElementById('cart-items').innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Product checkout successful!',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }

    function checkoutAll() {
        const cartItems = document.querySelectorAll('.cart-item');
        if (cartItems.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Notice',
                text: 'Your cart is empty!',
                confirmButtonText: 'OK'
            });
            return;
        }
        Swal.fire({
            title: 'Confirm Checkout',
            text: 'Do you want to checkout your entire cart?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, checkout!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('?clear_cart=true', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-items').innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                        document.getElementById('total-price').textContent = '$0.00';
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Entire cart checkout successful!',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>
