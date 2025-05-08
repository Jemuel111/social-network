<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user = $_SESSION['user_id'];
    $requester_id = (int)$_POST['user_id'];

    // Accept the friend request
    $stmt = $conn->prepare("
        UPDATE friendships 
        SET status = 'accepted' 
        WHERE user_id = ? AND friend_id = ?
    ");
    $stmt->bind_param("ii", $requester_id, $current_user);
    $stmt->execute();


    

    header("Location: friends.php");
    exit;
}
?>
