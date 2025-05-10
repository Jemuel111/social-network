<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// Update user's last activity
$conn->query("UPDATE users SET last_activity = NOW() WHERE user_id = $user_id");

// Handle mark as read BEFORE fetching badges

if (isset($_GET['friend_id'])) {
    $friend_id = intval($_GET['friend_id']);
    
    // Mark all messages from friend as read
    $update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 
                                   WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $update_stmt->bind_param("ii", $friend_id, $user_id);
    $update_stmt->execute();
}

if (isset($_GET['message_id'])) {
    $message_id = intval($_GET['message_id']);
    
    $stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE message_id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
}

if (isset($_GET['conversation_id'])) {
    $conversation_id = intval($_GET['conversation_id']);
    
    $stmt = $conn->prepare("UPDATE messages SET status = 'read' 
                            WHERE conversation_id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $conversation_id, $user_id);
    $stmt->execute();
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = clean_input($_GET['search']);
}

// Fetch friends
$query = "SELECT u.*, 
          (CASE WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) <= 5 THEN 1 ELSE 0 END) AS is_online
          FROM users u
          WHERE u.user_id != ?
          AND (u.full_name LIKE CONCAT('%', ?, '%') OR u.username LIKE CONCAT('%', ?, '%'))";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $user_id, $search, $search);
$stmt->execute();
$result = $stmt->get_result();
$friends = $result->fetch_all(MYSQLI_ASSOC);

// Fetch last message for each friend
$last_messages = [];
foreach ($friends as $friend) {
    $fid = $friend['user_id'];
    $msg_stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1");
    $msg_stmt->bind_param("iiii", $user_id, $fid, $fid, $user_id);
    $msg_stmt->execute();
    $msg_result = $msg_stmt->get_result();
    $last_messages[$fid] = $msg_result->fetch_assoc();
}
// Sort friends by most recent message
usort($friends, function($a, $b) use ($last_messages) {
    $a_msg = $last_messages[$a['user_id']];
    $b_msg = $last_messages[$b['user_id']];
    if ($a_msg && $b_msg) {
        return strtotime($b_msg['created_at']) - strtotime($a_msg['created_at']);
    } elseif ($a_msg) {
        return -1;
    } elseif ($b_msg) {
        return 1;
    } else {
        return 0;
    }
});

// Fetch messages if a friend is selected
$chat_messages = [];
if (isset($_GET['friend_id'])) {
    $stmt = $conn->prepare("SELECT * FROM messages 
                            WHERE (sender_id = ? AND receiver_id = ?) 
                            OR (sender_id = ? AND receiver_id = ?)
                            ORDER BY created_at ASC");
    $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    $stmt->execute();
    $chat_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Zyntra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/message-style.css">
</head>
<body style="overflow: hidden;">
<!-- Background Elements -->
<div class="background-container">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="grid-bg"></div>
</div>
<!-- No navbar here for full-screen chat -->

<div class="messenger-unique-container">
    <div class="chat-list-unique" id="chatList">
        <button class="back-to-home-btn" onclick="window.location.href='index.php'"><i style="color: white;" class="bi bi-caret-left-fill"></i> Back to Home</button>
        <form class="mb-3">
            <input type="text" name="search" class="form-control search" placeholder="Search friends..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
        <div class="list-group">
            <?php foreach ($friends as $friend): ?>
                <a href="?friend_id=<?php echo $friend['user_id']; ?>" class="list-group-item list-group-item-action friend-link <?php if(isset($_GET['friend_id']) && $_GET['friend_id'] == $friend['user_id']) echo 'active'; ?>">
                    <span class="avatar-wrapper">
                        <img src="assets/images/<?php echo htmlspecialchars($friend['profile_pic']); ?>" class="rounded-circle avatar-img" alt="Profile">
                        <span class="online-dot <?php echo $friend['is_online'] ? 'online' : 'offline'; ?>"></span>
                    </span>
                    <span class="friend-info">
                        <span class="friend-name"><?php echo htmlspecialchars($friend['full_name']); ?></span>
                        <span class="last-message-preview">
                            <?php
                            $last = $last_messages[$friend['user_id']];
                            if ($last) {
                                $is_me = $last['sender_id'] == $user_id;
                                $prefix = $is_me ? 'You: ' : '';
                                $msg = htmlspecialchars(mb_strimwidth($last['content'], 0, 30, '...'));
                                echo $prefix . $msg;
                            } else {
                                echo '<span style="color:var(--color-3);">No messages yet</span>';
                            }
                            ?>
                        </span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="chat-window-unique" id="chatWindow">
        <!-- This will be filled by AJAX -->
        <div class="empty-chat-unique">
            <i class="fas fa-comments fa-3x mb-3" style="color:var(--color-6);"></i>
            <h5>Select a friend to start chatting.</h5>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
    const chatBox = document.getElementById('chat-box');
    if(chatBox){
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>
</body>
</html>
