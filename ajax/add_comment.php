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

// Insert comment
$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $post_id, $user_id, $content);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Comment added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add comment']);
}
?>