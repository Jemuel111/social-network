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
if (!isset($_GET['post_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Post ID not provided']);
    exit;
}

$post_id = (int)$_GET['post_id'];

// Get comments for this post
$stmt = $conn->prepare("
    SELECT c.*, u.username, u.full_name, u.profile_pic 
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $row['created_at'] = format_date($row['created_at']);
    $comments[] = $row;
}

echo json_encode(['status' => 'success', 'comments' => $comments]);
?>