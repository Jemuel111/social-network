<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo '<div class="alert alert-danger">Not logged in.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = isset($_GET['friend_id']) ? intval($_GET['friend_id']) : 0;

if (!$friend_id) {
    echo '<div class="alert alert-danger">No friend selected.</div>';
    exit;
}

// Fetch friend info
$friend_info = get_user_by_id($friend_id);
if (!$friend_info) {
    echo '<div class="alert alert-danger">User not found.</div>';
    exit;
}

// Fetch chat messages
$stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
$stmt->execute();
$chat_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Output chat header
?>
<div class="chat-header-unique">
    <button class="back-btn-unique" id="backBtn" type="button">&larr;</button>
    <img src="assets/images/<?php echo htmlspecialchars($friend_info['profile_pic']); ?>" class="rounded-circle avatar-img" alt="Profile">
    <span class="chat-header-name"><?php echo htmlspecialchars($friend_info['full_name']); ?></span>
    <span class="online-dot <?php echo ($friend_info['last_activity'] && (strtotime($friend_info['last_activity']) > strtotime('-5 minutes'))) ? 'online' : 'offline'; ?>"></span>
</div>
<div class="chat-body-unique" id="chat-box">
    <?php foreach ($chat_messages as $msg): ?>
        <div class="message-row <?php echo $msg['sender_id'] == $user_id ? 'outgoing' : 'incoming'; ?>">
            <div>
                <div class="message-timestamp"><?php echo format_date($msg['created_at']); ?></div>
                <div class="message-bubble">
                    <?php echo htmlspecialchars($msg['content']); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="chat-footer-unique card-footer">
    <form id="send-message-form" method="POST" action="send_message.php">
        <div class="input-group">
            <input type="hidden" name="receiver_id" value="<?php echo $friend_id; ?>">
            <input type="text" name="message" class="input-message form-control" placeholder="Type your message..." required>
            <button class="send-btn-unique" type="submit"><i class="fas fa-paper-plane"></i></button>
        </div>
    </form>
</div>