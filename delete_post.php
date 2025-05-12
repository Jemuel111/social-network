<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    // Check if the user owns the post or is admin
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['user_id'] == $user_id || $is_admin) {
            $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                header("Location: index.php");
                exit;
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                echo json_encode(['success' => false, 'message' => 'Not allowed.']);
                exit;
            } else {
                header("Location: index.php?error=notallowed");
                exit;
            }
        }
    }
}
// Fallback
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
} else {
    header("Location: index.php");
    exit;
} 