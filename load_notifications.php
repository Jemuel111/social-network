<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

foreach ($notifications as $notif): ?>
    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
        <div class="notification-avatar">
            <?php
            $icon = 'fa-bell';
            if (strpos($notif['message'], 'commented') !== false) $icon = 'fa-comment';
            elseif (strpos($notif['message'], 'liked') !== false) $icon = 'fa-heart';
            elseif (strpos($notif['message'], 'friend request') !== false) $icon = 'fa-user-plus';
            ?>
            <i class="fas <?php echo $icon; ?>"></i>
            <?php if (!$notif['is_read']): ?>
                <div class="notification-dot"></div>
            <?php endif; ?>
        </div>
        <div class="notification-content">
            <p class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></p>
            <p class="notification-time"><?php echo format_date($notif['created_at']); ?></p>
        </div>
        <?php if (strpos($notif['message'], 'friend request') !== false):
            preg_match('/user_id=(\d+)/', $notif['message'], $matches);
            $from_user_id = $matches[1] ?? null;
            if ($from_user_id): ?>
            <div class="notification-actions">
                <form method="POST" action="accept_friend.php" class="d-inline">
                    <input type="hidden" name="user_id" value="<?php echo $from_user_id; ?>">
                    <button type="submit" class="action-btn btn-primary">Accept</button>
                </form>
                <form method="POST" action="decline_friend.php" class="d-inline">
                    <input type="hidden" name="user_id" value="<?php echo $from_user_id; ?>">
                    <button type="submit" class="action-btn btn-secondary">Decline</button>
                </form>
            </div>
        <?php endif; endif; ?>
    </div>
<?php endforeach;