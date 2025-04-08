<?php
session_start();
include './connect.php'; // Database connection file

$error = '';

// Check database connection
if (!$connect) {
    die("Database connection error: " . mysqli_connect_error());
}

// Handle regular login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } else {
        $stmt = $connect->prepare("SELECT UserID, password, role FROM user WHERE Email = ?");
        if (!$stmt) {
            die("SQL query error: " . $connect->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                header('Location: welcome.php');
                exit();
            } else {
                $error = 'Incorrect password!';
            }
        } else {
            $error = 'Email does not exist!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
            font-size: 2rem;
        }

        .success-message {
            color: #28a745;
            background-color: #d4edda;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        label {
            font-size: 1rem;
            color: #333;
        }

        input[type="email"],
        input[type="password"] {
            padding: 0.8rem;
            border: 1px solid #dddfe2;
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
            background-color: #f5f6f7;
        }

        input:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px #e7f3ff;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        button {
            background-color: #1877f2;
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
            background-color: #166fe5;
        }

        .privacy-notice {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #666;
        }

        .privacy-notice a {
            color: #1877f2;
            text-decoration: none;
        }

        .privacy-notice a:hover {
            text-decoration: underline;
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
        }

        .register-link a {
            color: #1877f2;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php
        if (!empty($_SESSION['message'])) {
            echo "<div class='success-message'>" . $_SESSION['message'] . "</div>";
            unset($_SESSION['message']);
        }
        if (!empty($error)) echo "<div class='error-message'>$error</div>";
        ?>
        <form method="post" id="loginForm">
            <label for="email">Email Address</label>
            <input type="email" name="email" required>
            <label for="password">Password</label>
            <input type="password" name="password" required>
            <div class="checkbox-container">
                <input type="checkbox" name="keep_signed_in" id="keep_signed_in">
                <label for="keep_signed_in">Keep me signed in</label>
            </div>
            <button type="submit">Sign In</button>
            <button type="submit" a href="./welcome.php">Back to Home</a></button>
        </form>
        <div class="register-link">
            Don’t have an account? <a href="./register.php">Create an account</a>
        </div>
        <div class="privacy-notice">
            Personal information which you give us may be used by us to process your order process. For further details please see our <a href="https://youtu.be/Jqr1KIS5iTc?si=oL_xzXHmUW4pnO4l">Privacy Policy</a>.
        </div>
    </div>
</body>
</html>
