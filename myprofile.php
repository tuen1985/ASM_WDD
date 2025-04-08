<?php
session_start();

// Check login status
$is_logged_in = isset($_SESSION['user_id']);

// Handle user feedback
$feedback_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    if (!empty($name) && !empty($email) && !empty($message)) {
        $feedback_message = "Thank you for your feedback! I will get back to you soon.";
    } else {
        $feedback_message = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information - GameStore</title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            font-family: Arial, sans-serif;
            background: rgb(19, 23, 32);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background-color: rgb(23, 31, 50);
            padding: 2rem 4rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            height: 80%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            color: white;
            margin-top: 30px;
        }
        .profile-container {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .profile-img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .personal-info {
            flex-grow: 1;
        }
        h1 {
            color: rgb(65, 122, 204);
            margin-bottom: 1rem;
            margin-top: 0;
        }
        p {
            margin: 0.3rem 0;
        }
        .social-links {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        .social-links a {
            color: rgb(65, 122, 204);
            font-size: 24px;
            text-decoration: none;
        }
        .social-links a:hover {
            color: rgb(45, 102, 184);
        }
        .feedback-form {
            display: flex;
            flex-direction: column;
        }
        .feedback-form h2 {
            margin: 0 0 1rem 0;
            text-align: center;
            color: rgb(65, 122, 204);
        }
        .feedback-form label {
            display: block;
            margin: 5px 0 3px;
            font-weight: bold;
        }
        .feedback-form input,
        .feedback-form textarea {
            width: 100%;
            padding: 6px;
            margin-bottom: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            background-color: #fff;
        }
        .feedback-form textarea {
            height: 80px;
            resize: none;
        }
        .feedback-form button {
            background-color: rgb(65, 122, 204);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 5px;
        }
        .feedback-form button:hover {
            background-color: rgb(45, 102, 184);
        }
        .feedback-message {
            text-align: center;
            margin-top: 5px;
            color: #28a745;
        }
        .feedback-message.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <!-- Profile Picture -->
            <img src="/IMG_5608.JPG" alt="Profile Picture" class="profile-img">
            
            <!-- Personal Information -->
            <div class="personal-info">
                <h1>Personal Information</h1>
                <p><strong>Full Name:</strong> NGUYEN VAN TUYEN</p>
                <p><strong>Email:</strong> Vantuyenn1985@gmail.com</p>
                <p><strong>Phone Number:</strong> 0986730390</p>
                <p><strong>Introduction:</strong> Hello! I am Nguyen Van Tuyen, the founder of GameStore. Nice to connect with you through this page!</p>
            </div>
        </div>

        <!-- Feedback Form -->
        <div class="feedback-form">
            <h2>Contact Me</h2>
            <form method="POST" action="">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="message">Message:</label>
                <textarea id="message" name="message" required></textarea>
                
                <button type="submit">Send Feedback</button>
                <button type="button" onclick="window.location.href='welcome.php'">Back to Homepage</button>
            </form>
            <?php if ($feedback_message): ?>
                <p class="feedback-message <?php echo strpos($feedback_message, 'Thank you') === 0 ? '' : 'error'; ?>">
                    <?php echo $feedback_message; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
