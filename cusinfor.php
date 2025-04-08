<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
const DB_CONFIG = [
    'host' => '103.75.184.31',
    'username' => 'tovjaghhhosting_NguyenVanTuyen',
    'password' => '123abcD!',
    'database' => 'tovjaghhhosting_sdlcsql'
];

/**
 * Connect to the database
 * @return mysqli|null
 */
function getDBConnection(): ?mysqli
{
    $conn = new mysqli(
        DB_CONFIG['host'],
        DB_CONFIG['username'],
        DB_CONFIG['password'],
        DB_CONFIG['database']
    );
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return null;
    }
    return $conn;
}

/**
 * Generate a random date between 2019 and 2025
 * @return string
 */
function generateRandomDate(): string
{
    $start = strtotime("2019-01-01");
    $end = strtotime("2025-12-31");
    $randomTimestamp = rand($start, $end);
    return date('Y-m-d H:i:s', $randomTimestamp);
}

/**
 * Fetch user information from the database
 * @param int $user_id
 * @return array
 */
function fetchUserInfo($user_id): array
{
    $conn = getDBConnection();
    if (!$conn) {
        return [
            'Username' => 'N/A',
            'Email' => 'Unable to connect to database',
            'role' => 'N/A',
            'CreateDate' => generateRandomDate()
        ];
    }

    $check_table = $conn->query("SHOW TABLES LIKE 'user'");
    if ($check_table->num_rows == 0) {
        $conn->close();
        return [
            'Username' => 'N/A',
            'Email' => 'The user table does not exist in sdlcsql',
            'role' => 'N/A',
            'CreateDate' => generateRandomDate()
        ];
    }

    $stmt = $conn->prepare("SELECT Username, Email, role, CreateDate FROM user WHERE UserID = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return [
            'Username' => 'N/A',
            'Email' => 'Query error: ' . $conn->error,
            'role' => 'N/A',
            'CreateDate' => generateRandomDate()
        ];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc() ?? [];

    $stmt->close();
    $conn->close();

    return $user_info ?: [
        'Username' => 'N/A',
        'Email' => 'User information not found',
        'role' => 'N/A',
        'CreateDate' => generateRandomDate()
    ];
}

// Retrieve user information from session and database
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'N/A';
$role = $_SESSION['role'] ?? 'N/A';
$user_info = fetchUserInfo($user_id);

// Assign values from session if not retrieved from DB
$user_info['Username'] = $user_info['Username'] !== 'N/A' ? $user_info['Username'] : $username;
$user_info['Email'] = $user_info['Email'] !== 'N/A' ? $user_info['Email'] : 'Not updated';
$user_info['role'] = $user_info['role'] !== 'N/A' ? $user_info['role'] : $role;
$user_info['CreateDate'] = $user_info['CreateDate'] !== 'N/A' ? $user_info['CreateDate'] : generateRandomDate();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameStore - Account Information</title>
    <link rel="icon" type="image/png" href="/image-Photoroom.png">
    <link rel="stylesheet" href="/welcome.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 100px auto 50px;
            padding: 30px;
            background: var(--oxford-blue);
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            color: var(--white);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header h1 {
            font-size: 28px;
            font-weight: var(--fw6);
            color: var(--light-azure);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: var(--rich-black-fogra-29);
            border-radius: 10px;
        }

        .info-item label {
            font-weight: var(--fw5);
            font-size: 16px;
        }

        .info-item span {
            font-size: 16px;
            color: var(--light-gray);
        }

        .profile-actions {
            margin-top: 30px;
            text-align: center;
        }

        .profile-actions a {
            background: var(--light-azure);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: var(--fw5);
            transition: background 0.3s ease;
        }

        .profile-actions a:hover {
            background: var(--vermilion);
            color: var(--white);
        }

        /* Navbar styles */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: var(--oxford-blue);
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-brand img {
            width: 50px;
            height: auto;
        }

        .navbar-title {
            flex-grow: 1;
            text-align: center;
            color: white;
            font-size: 20px;
            font-weight: var(--fw6);
        }

        .navbar-actions {
            display: flex;
            justify-content: flex-end;
        }

        .navbar-signin {
            position: relative;
            display: flex;
            align-items: center;
            font-size: var(--font-size-small);
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
            color: var(--off-white);
            text-decoration: none;
            font-weight: var(--fw5);
            font-size: var(--font-size-small);
            cursor: pointer;
            transition: color 0.3s ease;
            margin-right: -52px;
            padding: 5px 10px;
        }

        .username:hover, .username:hover .dropdown-arrow {
            color: var(--light-azure);
        }

        .dropdown-arrow {
            font-size: 16px;
            margin-left: 5px;
            color: var(--off-white);
            transition: color 0.3s ease;
        }

        .username[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--oxford-blue);
            color: var(--off-white);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: var(--font-size-small);
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1000;
        }

        .username:hover[data-tooltip]:not([data-tooltip-hidden])::after {
            opacity: 1;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-65%);
            background: var(--oxford-blue);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            padding: 10px 0;
            z-index: 1000;
            width: 200px;
            min-width: 150px;
        }

        .container{
            padding-top: 80px;
            padding-bottom: 80px;
        }

        .user-menu:hover .dropdown-menu {
            display: block;
        }

        .dropdown-menu li {
            padding: 8px 20px;
            list-style: none;
        }

        .dropdown-menu a {
            font-size: var(--font-size-small);
            color: var(--off-white);
            text-decoration: none;
            display: block;
            transition: color 0.3s ease;
        }

        .dropdown-menu a:hover {
            color: var(--light-azure);
        }

        .dropdown-menu a ion-icon {
            vertical-align: middle;
            font-size: 17px;
            color: #fff;
            margin-right: 5px;
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
                    Account Information
                </div>
                <div class="navbar-actions">
                    <div class="navbar-signin">
                        <div class="user-menu">
                            <span class="username" data-tooltip="Manage Account">
                                <?= htmlspecialchars($username) ?>
                                <ion-icon name="chevron-down-outline" class="dropdown-arrow"></ion-icon>
                            </span>
                            <ul class="dropdown-menu">
                                <li><a href="cusinfor.php"><ion-icon name="person-outline"></ion-icon>Account Infor</a></li>
                                <?php if ($role === 'admin'): ?>
                                    <li><a href="manapro.php"><ion-icon name="settings-outline"></ion-icon>Manage Products</a></li>
                                    <li><a href="manacus.php"><ion-icon name="people-outline"></ion-icon>Manage Users</a></li>
                                <?php endif; ?>
                                <li><a href="login.php"><ion-icon name="swap-horizontal-outline"></ion-icon>Switch Account</a></li>
                                <li><a href="index.php?logout=true"><ion-icon name="log-out-outline"></ion-icon>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="page-content">
        <div class="container">
            <div class="profile-container">
                <div class="profile-info">
                    <div class="info-item">
                        <label>Username:</label>
                        <span><?= htmlspecialchars($user_info['Username']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?= htmlspecialchars($user_info['Email']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Role:</label>
                        <span><?= htmlspecialchars($user_info['role'] === 'customer' ? 'Customer' : ($user_info['role'] === 'admin' ? 'Administrator' : 'Undefined')) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Account Creation Date:</label>
                        <span><?= htmlspecialchars(date('d/m/Y', strtotime($user_info['CreateDate']))) ?></span>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="welcome.php">Back to Main Page</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ionicon Scripts -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>
