<?php
session_start();

// Remove all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect user to login page
header("Location: login.php");
exit;
?>