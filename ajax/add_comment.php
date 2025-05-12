<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if required fields are set
if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];
$content = clean_input($_POST['content']);
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

// Insert comment (support replies)
if ($parent_id) {
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
} else {
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $content);
}

if ($stmt->execute()) {
    // Notify the post owner
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_owner = $result->fetch_assoc()['user_id'];

    if ($post_owner != $user_id) { // Avoid notifying the commenter themselves
        // Get the commenter's full name
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $commenter_name = $result->fetch_assoc()['full_name'];

        $notification_message = $commenter_name . " commented on your post.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $post_owner, $notification_message);
        $stmt->execute();
    }

    echo json_encode(['status' => 'success', 'message' => 'Comment added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add comment']);
}
?>