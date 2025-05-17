<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = isset($_POST['content']) ? clean_input($_POST['content']) : '';
$visibility = isset($_POST['visibility']) ? clean_input($_POST['visibility']) : 'public';

if (!$post_id || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify post ownership
$stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Update post
$stmt = $conn->prepare("UPDATE posts SET content = ?, visibility = ? WHERE post_id = ?");
$stmt->bind_param("ssi", $content, $visibility, $post_id);

if ($stmt->execute()) {
    // If visibility is specific, update friend visibility
    if ($visibility === 'specific' && !empty($_POST['selected_friends'])) {
        // First, remove existing friend visibility
        $stmt = $conn->prepare("DELETE FROM post_visibility_friends WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();

        // Then add new friend visibility
        $friend_ids = explode(',', $_POST['selected_friends']);
        $stmt = $conn->prepare("INSERT INTO post_visibility_friends (post_id, friend_id) VALUES (?, ?)");
        foreach ($friend_ids as $friend_id) {
            $stmt->bind_param("ii", $post_id, $friend_id);
            $stmt->execute();
        }
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update post']);
} 