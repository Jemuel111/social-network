<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reason = 'Inappropriate content'; // Default reason, can be extended

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid post.']);
    exit;
}

// Prevent duplicate reports by same user
$stmt = $conn->prepare("SELECT id FROM reported_posts WHERE post_id = ? AND reporter_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reported this post.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO reported_posts (post_id, reporter_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param("iis", $post_id, $user_id, $reason);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Post reported. Thank you!']); 