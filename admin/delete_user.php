<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

require_login();

// Check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);

    // Delete user from the database
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    header("Location: admin_panel.php");
    exit;
}
?>