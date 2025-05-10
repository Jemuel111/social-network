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

    // Prevent sending friend request if blocked
    $stmt = $conn->prepare("SELECT 1 FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
    $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: profile.php?id=$friend_id&error=blocked");
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

        // Get the sender's full name
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sender_name = $result->fetch_assoc()['full_name'];

        // Notify the recipient
        $notification_message = $sender_name . " sent you a friend request.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $friend_id, $notification_message);
        $stmt->execute();
    }
    
    header("Location: profile.php?id=$friend_id");
    exit;
}
?>
