<?php

// Start session and include database connection
session_start();
require_once './connect.php';

// Initialize variables
$error = '';

/**
 * Process registration form submission
 * @return void
 */
function processRegistration($connect) {
    global $error;
    
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
    }

    // Sanitize input data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all the information';
        return;
    }

    // Check for existing username or email
    $stmt = $connect->prepare("SELECT UserID FROM user WHERE Username = ? OR Email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = 'Username or Email already exists!';
        $stmt->close();
        return;
    }
    $stmt->close();

    // Hash password and insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $default_role = 'customer';
    
    $insert_stmt = $connect->prepare(
        "INSERT INTO user (Username, Email, password, role) VALUES (?, ?, ?, ?)"
    );
    $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $default_role);

    if ($insert_stmt->execute()) {
        $_SESSION['message'] = 'Registration successful! Please log in.';
        header('Location: login.php');
        exit();
    } else {
        $error = 'An error occurred! Please try again.';
    }
    $insert_stmt->close();
}

// Process form submission
processRegistration($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <link rel="stylesheet" href="/register.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-group">
            <input 
                type="text" 
                name="username" 
                placeholder="Username" 
                required 
                autocomplete="username"
            >
            <input 
                type="email" 
                name="email" 
                placeholder="Email" 
                required 
                autocomplete="email"
            >
            <input 
                type="password" 
                name="password" 
                placeholder="Password" 
                required 
                autocomplete="new-password"
            >
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="./login.php">Login</a>
        </div>
    </div>
</body>
</html>