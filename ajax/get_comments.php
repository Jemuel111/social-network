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

// Get comments for this post (with replies)
$comments = get_comments_with_replies($post_id);

// Format created_at for all comments and replies
function format_comments_recursive(&$comments) {
    foreach ($comments as &$comment) {
        $comment['created_at'] = format_date($comment['created_at']);
        if (isset($comment['replies']) && is_array($comment['replies'])) {
            format_comments_recursive($comment['replies']);
        }
    }
}
format_comments_recursive($comments);

echo json_encode(['status' => 'success', 'comments' => $comments]);
?>