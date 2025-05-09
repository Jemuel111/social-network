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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];

    // Get the post owner before deleting
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_owner = null;
    if ($row = $result->fetch_assoc()) {
        $post_owner = $row['user_id'];
    }

    // Delete the post
    $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    // Notify the post owner if found and not the admin themselves
    if ($post_owner && $post_owner != $_SESSION['user_id']) {
        $notification_message = "Your post was deleted by an admin.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $post_owner, $notification_message);
        $stmt->execute();
    }
}

header("Location: manage_posts.php");
exit;
?>