<?php
session_start();

// Destroy session completely
$_SESSION = [];
session_unset();
session_destroy();

// Delete all cookies (recommended)
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, "/");
}

// Redirect to home page
header("Location: home.php");
exit;
?>
