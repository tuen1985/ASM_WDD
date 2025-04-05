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
        // Here you can add logic to save feedback to a database or send an email
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
    <!-- Include Ionicons -->
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
        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        h1 {
            color: #1877f2;
            text-align: center;
        }
        .profile-img {
            width: 250px;
            height: 250px;
            object-fit: cover;
            border-radius: 50%;
            display: block;
            margin: 35px auto 35px;
        }
        p {
            margin: 0.5rem 0;
        }
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        .social-links a {
            color: #1877f2;
            font-size: 24px;
            text-decoration: none;
        }
        .social-links a:hover {
            color: #0056b3;
        }
        .feedback-form {
            margin-top: 35px;
        }
        .feedback-form label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        .feedback-form input,
        .feedback-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .feedback-form textarea {
            height: 100px;
            resize: vertical;
        }
        .feedback-form button {
            background-color: #1877f2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 10px; /* Add spacing between buttons */
        }
        .feedback-form button:hover {
            background-color: rgb(16, 72, 145);
        }
        .feedback-message {
            text-align: center;
            margin-top: 10px;
            color: #28a745;
        }
        .feedback-message.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Personal Information</h1>
        <!-- Your profile picture -->
        <img src="/IMG_5608.JPG" alt="Profile Picture" class="profile-img">
        
        <!-- Personal Information -->
        <p><strong>Full Name:</strong> NGUYEN VAN TUYEN</p>
        <p><strong>Email:</strong> Vantuyenn1985@gmail.com</p>
        <p><strong>Phone Number:</strong> 0986730390</p>
        <p><strong>Introduction:</strong> Hello! I am Nguyen Van Tuyen, the founder of GameStore. Nice to connect with you through this page!</p>

        <!-- Feedback Form -->
        <div class="feedback-form">
            <h2 style="text-align: center; color: #1877f2;">Contact Me</h2>
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