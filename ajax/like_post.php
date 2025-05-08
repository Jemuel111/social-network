<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if post_id is set
if (!isset($_POST['post_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Post ID not provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];

// Check if user has already liked the post
$stmt = $conn->prepare("SELECT like_id FROM likes WHERE user_id = ? AND post_id = ?");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User has already liked the post, so unlike it
    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $liked = false;
} else {
    // User hasn't liked the post, so like it
    $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $liked = true;
}

// Get updated like count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$likes = $row['count'];

echo json_encode(['status' => 'success', 'likes' => $likes, 'liked' => $liked]);
?>