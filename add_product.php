<?php
session_start();

// Connect to database
$servername = "103.75.184.31";
$username = "tovjaghhhosting_NguyenVanTuyen";
$password = "123abcD!";
$database = "tovjaghhhosting_sdlcsql";

$connect = new mysqli($servername, $username, $password, $database);
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

$message = "";
$edit_mode = false;
$product_data = [];

if (isset($_GET['edit_product_id'])) {
    $edit_mode = true;
    $edit_product_id = $_GET['edit_product_id'];
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $edit_product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product_data = $result->fetch_assoc();
    } else {
        $message = "<p class='error-message'>No products found to edit!</p>";
        $edit_mode = false;
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['product_name']) && !empty($_POST['product_price']) && !empty($_POST['quantity'])) {
        $product_id = $edit_mode ? $_GET['edit_product_id'] : $_POST['product_id'];
        $product_name = trim($_POST['product_name']);
        $product_price = floatval($_POST['product_price']);
        $quantity = intval($_POST['quantity']);
        $year = !empty($_POST['year']) ? intval($_POST['year']) : null;
        $discount = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
        $rating = number_format(rand(60, 90) / 10, 1);
        $discounted_price = $discount > 0 ? $product_price * (1 - $discount / 100) : $product_price;

        $target_dir = "103.75.184.31";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!empty($_FILES['product_img']['name'])) {
            $product_img = basename($_FILES['product_img']['name']);
            $target_file = $target_dir . $product_img;
            if ($_FILES['product_img']['error'] !== UPLOAD_ERR_OK) {
                $message = "<p class='error-message'>Error uploading file! Error code: " . $_FILES['product_img']['error'] . "</p>";
            } elseif (!move_uploaded_file($_FILES['product_img']['tmp_name'], $target_file)) {
                $message = "<p class='error-message'>Unable to move image file!</p>";
            }
        } else {
            $product_img = $edit_mode ? $product_data['product_img'] : '';
        }

        if (empty($message)) {
            $stmt = null;
            $execute = false;

            if ($edit_mode) {
                $sql = "UPDATE products SET product_name = ?, product_price = ?, quantity = ?, product_img = ?, rating = ?, year = ?, discount = ?, discounted_price = ? WHERE product_id = ?";
                $stmt = $connect->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sdissdids", $product_name, $product_price, $quantity, $product_img, $rating, $year, $discount, $discounted_price, $product_id);
                    $execute = true;
                } else {
                    $message = "<p class='error-message'>Error preparing SQL statement!</p>";
                }
            } else {
                $check_sql = "SELECT * FROM products WHERE product_id = ?";
                $check_stmt = $connect->prepare($check_sql);
                $check_stmt->bind_param("s", $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // Display SweetAlert notification when product ID already exists
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Warning',
                                text: 'Product ID already exists! Please choose a different ID.',
                                confirmButtonText: 'OK'
                            });
                        });
                    </script>";
                } else {
                    $sql = "INSERT INTO products (product_id, product_name, product_price, quantity, product_img, rating, year, discount, discounted_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $connect->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ssdissdid", $product_id, $product_name, $product_price, $quantity, $product_img, $rating, $year, $discount, $discounted_price);
                        $execute = true;
                    } else {
                        $message = "<p class='error-message'>Error preparing SQL statement!</p>";
                    }
                }
                $check_stmt->close();
            }

            if ($execute && $stmt && $stmt->execute()) {
                $success_message = $edit_mode ? "Product has been successfully edited!" : "Product has been successfully added!";

                // Update welcome.php
                $welcome_file = "D:/Xampp/htdocs/DemoWebsite/welcome.php";
                $offers = [];
                $main_products = [];

                if (file_exists($welcome_file)) {
                    $welcome_content = file_get_contents($welcome_file);
                    if ($welcome_content !== false) {
                        $offers_start = strpos($welcome_content, '$offers = [');
                        $offers_end = strpos($welcome_content, '];', $offers_start) + 2;
                        $main_start = strpos($welcome_content, '$main_products = [');
                        $main_end = strpos($welcome_content, '];', $main_start) + 2;

                        if ($offers_start !== false && $main_start !== false) {
                            $offers_str = substr($welcome_content, $offers_start, $offers_end - $offers_start);
                            $main_str = substr($welcome_content, $main_start, $main_end - $main_start);
                            eval($offers_str);
                            eval($main_str);
                        }
                    }
                }

                $offers = isset($offers) && is_array($offers) ? $offers : [];
                $main_products = isset($main_products) && is_array($main_products) ? $main_products : [];

                $new_product = [
                    'name' => $product_name,
                    'image' => "/DemoWebsite/uploads/$product_img",
                    'price' => $product_price,
                    'rating' => $rating,
                    'year' => $year ?? 'Not specified'
                ];
                if ($discount > 0) {
                    $new_product['original_price'] = $product_price;
                    $new_product['discounted_price'] = $discounted_price;
                    $new_product['discount'] = $discount;
                }

                foreach ($offers as $key => $offer) {
                    if ($offer['name'] === $product_name) {
                        unset($offers[$key]);
                    }
                }
                foreach ($main_products as $key => $main) {
                    if ($main['name'] === $product_name) {
                        unset($main_products[$key]);
                    }
                }

                if ($discount > 0) {
                    $offers[] = $new_product;
                } else {
                    $main_products[] = $new_product;
                }

                $offers_new = var_export(array_values($offers), true);
                $main_new = var_export(array_values($main_products), true);

                $new_content = "<?php\n\$offers = $offers_new;\n\$main_products = $main_new;\n?>";
                if (file_exists($welcome_file)) {
                    $new_content = substr($welcome_content, 0, $offers_start) .
                        "\$offers = $offers_new;" .
                        substr($welcome_content, $offers_end, $main_start - $offers_end) .
                        "\$main_products = $main_new;" .
                        substr($welcome_content, $main_end);
                }

                file_put_contents($welcome_file, $new_content);

                if ($edit_mode) {
                    $product_data = [
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'product_price' => $product_price,
                        'quantity' => $quantity,
                        'product_img' => $product_img,
                        'rating' => $rating,
                        'year' => $year,
                        'discount' => $discount,
                        'discounted_price' => $discounted_price
                    ];
                }

                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: '$success_message',
                            confirmButtonText: 'OK'
                        });
                    });
                </script>";
            } elseif ($stmt) {
                $message = "<p class='error-message'>Error: " . htmlspecialchars($stmt->error) . "</p>";
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } else {
        $message = "<p class='error-message'>Please fill in all required information!</p>";
    }
}

$connect->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? "Edit product" : "Add product"; ?></title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(180deg, transparent 20%, #000a);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            color: #1877f2;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .error-message, .success-message {
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        input, select {
            padding: 0.8rem;
            border: 1px solid #dddfe2;
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px #e7f3ff;
        }
        button {
            background-color: <?php echo $edit_mode ? "#d32f2f" : "#1877f2"; ?>;
            color: white;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: <?php echo $edit_mode ? "#b71c1c" : "#166fe5"; ?>;
        }
        .back-btn {
            background-color: #1877f2;
            margin-top: 1rem;
            width: 100%;
            max-width: 400px;
        }
        .back-btn:hover {
            background-color: #166fe5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $edit_mode ? "Edit product" : "Add product"; ?></h2>
        <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="text" name="product_id" placeholder="Product code" value="<?php echo $edit_mode ? htmlspecialchars($product_data['product_id']) : ''; ?>" <?php echo $edit_mode ? 'readonly' : 'required'; ?>>
            <input type="text" name="product_name" placeholder="Product Name" value="<?php echo $edit_mode ? htmlspecialchars($product_data['product_name']) : ''; ?>" required>
            <input type="number" name="product_price" step="0.01" placeholder="Product Price" value="<?php echo $edit_mode ? htmlspecialchars($product_data['product_price']) : ''; ?>" required>
            <input type="number" name="quantity" placeholder="Number of products" value="<?php echo $edit_mode ? htmlspecialchars($product_data['quantity']) : ''; ?>" required>
            <input type="file" name="product_img" <?php echo $edit_mode ? '' : 'required'; ?>>
            <?php if ($edit_mode && $product_data['product_img']): ?>
                <p>Current image: <?php echo htmlspecialchars($product_data['product_img']); ?></p>
            <?php endif; ?>
            <input type="number" name="year" placeholder="Release Year" min="1980" max="2025" value="<?php echo $edit_mode ? htmlspecialchars($product_data['year'] ?? '') : ''; ?>">
            <input type="number" name="discount" placeholder="Discount Percentage" min="0" max="100" value="<?php echo $edit_mode ? htmlspecialchars($product_data['discount']) : ''; ?>">
            <button type="submit"><?php echo $edit_mode ? "Edit product" : "Add product"; ?></button>
        </form>
        <a href="manapro.php"><button class="back-btn">Back to management page</button></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
