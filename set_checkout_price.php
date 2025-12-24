<?php
session_start();

if (isset($_POST['price'])) {
    $_SESSION['checkout_price'] = (float) $_POST['price'];
}

// Redirect to checkout
header("Location: checkout.php");
exit;
