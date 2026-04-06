<?php
// logout.php
define('ACCESS_ALLOWED', true);
session_start();

// Clear all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
