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
    <title>Messages - SocialConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <form class="mb-3">
                <input type="text" name="search" class="form-control" placeholder="Search friends..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <div class="list-group">
                <?php foreach ($friends as $friend): ?>
                    <a href="?friend_id=<?php echo $friend['user_id']; ?>" class="list-group-item list-group-item-action <?php if(isset($_GET['friend_id']) && $_GET['friend_id'] == $friend['user_id']) echo 'active'; ?>">
                        <img src="assets/images/<?php echo htmlspecialchars($friend['profile_pic']); ?>" class="rounded-circle me-2" width="30" height="30" alt="Profile">
                        <?php echo htmlspecialchars($friend['full_name']); ?>
                        <?php if ($friend['is_online']): ?>
                            <span class="badge bg-success float-end">Online</span>
                        <?php else: ?>
                            <span class="badge bg-secondary float-end">Offline</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (isset($_GET['friend_id'])): 
                $friend_id = intval($_GET['friend_id']);
                $friend_info = get_user_by_id($friend_id);
                
                if (!$friend_info): ?>
                    <div class="alert alert-danger">User not found.</div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            Chatting with <?php echo htmlspecialchars($friend_info['full_name']); ?>
                        </div>
                        <div class="card-body" id="chat-box" style="height:400px; overflow-y: scroll;">
                            <?php foreach ($chat_messages as $msg): ?>
                                <div class="mb-2 text-<?php echo $msg['sender_id'] == $user_id ? 'end' : 'start'; ?>">
                                    <small class="text-muted"><?php echo format_date($msg['created_at']); ?></small><br>
                                    <span class="badge <?php echo $msg['sender_id'] == $user_id ? 'bg-primary' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($msg['content']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <form id="send-message-form" method="POST" action="send_message.php">
                                <div class="input-group">
                                    <input type="hidden" name="receiver_id" value="<?php echo $friend_id; ?>">
                                    <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                    <button class="btn btn-primary" type="submit">Send</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">Select a friend to start chatting.</div>
            <?php endif; ?>
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
