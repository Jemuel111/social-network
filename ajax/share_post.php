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
$clicked_post_id = (int)$_POST['post_id'];

// Get the true original post id (if this is a share, use its shared_post_id)
$stmt = $conn->prepare("SELECT shared_post_id FROM posts WHERE post_id = ?");
$stmt->bind_param("i", $clicked_post_id);
$stmt->execute();
$result = $stmt->get_result();
$shared_post_id = null;
if ($row = $result->fetch_assoc()) {
    $shared_post_id = $row['shared_post_id'];
}
$target_post_id = $shared_post_id ? $shared_post_id : $clicked_post_id;

// Check if user has already shared this original post
$stmt = $conn->prepare("SELECT post_id FROM posts WHERE user_id = ? AND shared_post_id = ?");
$stmt->bind_param("ii", $user_id, $target_post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User has already shared the post, so unshare (delete the share post)
    $row = $result->fetch_assoc();
    $shared_post_id = $row['post_id'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $shared_post_id);
    $stmt->execute();
    $shared = false;
} else {
    // User hasn't shared the post, so share it (insert a new post with shared_post_id)
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, shared_post_id) VALUES (?, '', ?)");
    $stmt->bind_param("ii", $user_id, $target_post_id);
    $stmt->execute();
    $shared = true;
}

// Get updated share count (number of posts with shared_post_id = $target_post_id)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE shared_post_id = ?");
$stmt->bind_param("i", $target_post_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$shares = $row['count'];

if ($shared) { // Notify only when the post is shared
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $target_post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post_owner = $result->fetch_assoc()['user_id'];

    if ($post_owner != $user_id) { // Avoid notifying the sharer themselves
        // Get the sharer's full name
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sharer_name = $result->fetch_assoc()['full_name'];

        $notification_message = $sharer_name . " shared your post.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $post_owner, $notification_message);
        $stmt->execute();
    }
}

echo json_encode(['status' => 'success', 'shares' => $shares, 'shared' => $shared]);
?> 