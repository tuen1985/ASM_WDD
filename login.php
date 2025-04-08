<?php
session_start();
include './connect.php'; // Database connection file

$error = '';

// Check database connection
if (!$connect) {
    die("Database connection error: " . mysqli_connect_error());
}

// Handle regular login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all required fields';
    } else {
        $stmt = $connect->prepare("SELECT UserID, password, role FROM user WHERE Username = ?");
        if (!$stmt) {
            die("SQL query error: " . $connect->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                header('Location: welcome.php');
                exit();
            } else {
                $error = 'Incorrect password!';
            }
        } else {
            $error = 'Username does not exist!';
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

        input {
            padding: 0.8rem;
            border: 1px solid #dddfe2;
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px #e7f3ff;
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

        .back-btn {
            background-color: #1877f2;
            color: white;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #166fe5;
            text-decoration: none;
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
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            <a href="welcome.php" class="back-btn">Back to Home Page</a>
        </form>
        <div class="register-link">
            Don’t have an account? <a href="./register.php">Register</a>
        </div>
    </div>
</body>

</html>
