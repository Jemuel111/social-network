
<?php
require_once 'config.php';
require_once 'db_connect.php';

function shortenText($text, $maxLength = 50) {
    if (strlen($text) > $maxLength) {
        return substr($text, 0, $maxLength) . '...';
    }
    return $text;
}
// Clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

// Get user info by ID
function get_user_by_id($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

// Get posts for user feed
function get_feed_posts($user_id, $limit = 10, $offset = 0) {
    global $conn;
    
    // Get posts from user and their friends
    $query = "SELECT p.*, u.username, u.full_name, u.profile_pic, 
                (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.user_id = ? 
                 OR p.user_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted')
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    return $posts;
}

// Format date
function format_date($date) {
    // Convert string date to DateTime object
    $post_date = new DateTime($date);
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    // Calculate the difference
    $interval = $post_date->diff($now);
    
    if ($interval->y > 0) {
        return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    } elseif ($interval->m > 0) {
        return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    } elseif ($interval->d > 0) {
        return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    } elseif ($interval->h > 0) {
        return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    } elseif ($interval->i > 0) {
        return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    } elseif ($interval->s > 0) {
        return $interval->s . ' second' . ($interval->s > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}

// Check if user has liked a post
function has_user_liked_post($user_id, $post_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}


function get_unread_messages_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    return $count;
}

function get_unread_notifications_count($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    return $count;
}

// Get comments for a post including replies
function get_comments_with_replies($post_id) {
    global $conn;
    
    // First get all parent comments (those with no parent_id)
    $query = "SELECT c.*, u.username, u.profile_pic, u.full_name
              FROM comments c
              JOIN users u ON c.user_id = u.user_id
              WHERE c.post_id = ? AND c.parent_id IS NULL
              ORDER BY c.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        // For each parent comment, get all its replies
        $row['replies'] = get_comment_replies($row['comment_id']);
        $comments[] = $row;
    }
    
    return $comments;
}

// Get replies for a specific comment
function get_comment_replies($comment_id) {
    global $conn;
    
    $query = "SELECT c.*, u.username, u.profile_pic, u.full_name
              FROM comments c
              JOIN users u ON c.user_id = u.user_id
              WHERE c.parent_id = ?
              ORDER BY c.created_at ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = $row;
    }
    
    return $replies;
}

// Add a comment (works for both new comments and replies)
function add_comment($user_id, $post_id, $content, $parent_id = null) {
    global $conn;
    
    $current_time = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO comments (user_id, post_id, content, parent_id, created_at) 
              VALUES (?, ?, ?, ?, ?)";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $user_id, $post_id, $content, $parent_id, $current_time);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

?>


