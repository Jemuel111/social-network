<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

// Fetch notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - SocialConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h2>Your Notifications</h2>

    <?php if (empty($notifications)): ?>
        <p class="text-muted">You have no notifications yet.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($notifications as $notif): ?>
                <li class="list-group-item">
                    <?php echo htmlspecialchars($notif['message']); ?>
                    <br>
                    <small class="text-muted"><?php echo format_date($notif['created_at']); ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
