<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if (!$post_id) {
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

// Get the post's selected friends
$query = "SELECT friend_id FROM post_visibility_friends WHERE post_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

$friend_ids = [];
while ($row = $result->fetch_assoc()) {
    $friend_ids[] = $row['friend_id'];
}

echo json_encode($friend_ids); 