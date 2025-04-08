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
function processRegistration($connect)
{
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
    <style>
        :root {
            --primary-color: #1877f2;
            --error-color: #dc3545;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(180deg, transparent 20%, #000a);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow-color);
            max-width: 400px;
            width: 100%;
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .error-message {
            color: var(--error-color);
            background: #f8d7da;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        input {
            padding: 0.8rem;
            border: 1px solid #dddfe2;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
            width: 100%;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px #e7f3ff;
        }

        button {
            background: var(--primary-color);
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
            background: #166fe5;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
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
                autocomplete="username">
            <input
                type="email"
                name="email"
                placeholder="Email"
                required
                autocomplete="email">
            <input
                type="password"
                name="password"
                placeholder="Password"
                required
                autocomplete="new-password">
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="./login.php">Login</a>
        </div>
    </div>
</body>

</html>
