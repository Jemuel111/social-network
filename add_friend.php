<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_id'])) {
    $user_id = $_SESSION['user_id'];
    $friend_id = (int) $_POST['friend_id'];

    // Prevent sending friend request to self
    if ($user_id === $friend_id) {
        header("Location: profile.php?id=$friend_id");
        exit;
    }

    // Check if request already exists
    $stmt = $conn->prepare("SELECT * FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Send friend request
        $stmt = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $user_id, $friend_id);
        $stmt->execute();

        // Notify the recipient
        $notification_message = "You have a new friend request.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $friend_id, $notification_message);
        $stmt->execute();
    }
    
    header("Location: profile.php?id=$friend_id");
    exit;
}
?>
