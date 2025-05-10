<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blocked_id'])) {
    $blocker_id = $_SESSION['user_id'];
    $blocked_id = (int)$_POST['blocked_id'];

    // Delete from blocked_users table
    $stmt = $conn->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->bind_param("ii", $blocker_id, $blocked_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User has been unblocked successfully.";
    } else {
        $_SESSION['error'] = "Failed to unblock user. Please try again.";
    }
}

header("Location: friends.php");
exit; 