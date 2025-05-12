<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$visibility = isset($_POST['visibility']) ? $_POST['visibility'] : '';
$selected_friends = isset($_POST['selected_friends']) ? $_POST['selected_friends'] : '';

if (!$post_id || !$visibility) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check if user owns the post
$stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Update post visibility
    $stmt = $conn->prepare("UPDATE posts SET visibility = ? WHERE post_id = ?");
    $stmt->bind_param("si", $visibility, $post_id);
    $stmt->execute();

    // If specific friends were selected, update the friend list
    if ($visibility === 'specific') {
        // Remove existing friend permissions
        $stmt = $conn->prepare("DELETE FROM post_visibility_friends WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();

        // Add new friend permissions
        if (!empty($selected_friends)) {
            $friend_ids = explode(',', $selected_friends);
            $stmt = $conn->prepare("INSERT INTO post_visibility_friends (post_id, friend_id) VALUES (?, ?)");
            foreach ($friend_ids as $friend_id) {
                $stmt->bind_param("ii", $post_id, $friend_id);
                $stmt->execute();
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 