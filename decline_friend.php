<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user = $_SESSION['user_id'];
    $requester_id = (int)$_POST['user_id'];

    // Delete the friend request
    $stmt = $conn->prepare("
        DELETE FROM friendships 
        WHERE user_id = ? AND friend_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $requester_id, $current_user);
    $stmt->execute();

    header("Location: friends.php");
    exit;
}
?>
