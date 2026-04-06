<?php
// config/database.php
// Set timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'stock_salmonly');

// Function to get database connection
function getConnection() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current logged in user data from database
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return [
            'name' => 'Guest',
            'role' => 'Guest',
            'email' => '',
            'phone' => '',
            'full_name' => 'Guest'
        ];
    }
    
    $conn = getConnection();
    $user_id = $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
    $user = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    
    if ($user) {
        return [
            'name' => $user['full_name'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '-',
            'profile_picture' => $user['profile_picture'] ?? 'default-avatar.png'
        ];
    }
    
    return [
        'name' => $_SESSION['user_name'] ?? 'User',
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'role' => $_SESSION['user_role'] ?? 'Staff',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => '-'
    ];
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?>
