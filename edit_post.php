<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id']) && isset($_POST['content'])) {
    $post_id = (int)$_POST['post_id'];
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    // Check if the user owns the post or is admin
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['user_id'] == $user_id || $is_admin) {
            $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE post_id = ?");
            $stmt->bind_param("si", $content, $post_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'content' => $content]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update post.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Not allowed.']);
            exit;
        }
    }
}
// Fallback
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit; 