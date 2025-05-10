<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// Fetch all notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Get unread friend request count
$request_count = 0;
foreach ($notifications as $notif) {
    if (strpos($notif['message'], 'friend request') !== false && !$notif['is_read']) {
        $request_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Zyntra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/notif-style.css">
</head>
<body>
    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>
<div class="container-fluid px-0"> <!-- Full width container -->
    <?php include 'includes/navbar.php'; ?>
    <div class="container container-friend py-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="notification-container">
            <div class="notification-header">
                <h1 class="notification-title">Notifications</h1>
                <?php if ($request_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $request_count; ?> new</span>
                <?php endif; ?>
            </div>
            <?php if (empty($notifications)): ?>
                <div class="empty-notifications">
                    <p>You have no notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-notification-id="<?php echo $notif['notification_id']; ?>">
                            <div class="notification-avatar">
                                <?php
                                $icon = 'fa-bell';
                                if (strpos($notif['message'], 'commented') !== false) {
                                    $icon = 'fa-comment';
                                } elseif (strpos($notif['message'], 'liked') !== false) {
                                    $icon = 'fa-heart';
                                } elseif (strpos($notif['message'], 'friend request') !== false) {
                                    $icon = 'fa-user-plus';
                                }
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
                            <?php 
                            if (strpos($notif['message'], 'friend request') !== false) {
                                preg_match('/user_id=(\d+)/', $notif['message'], $matches);
                                $from_user_id = $matches[1] ?? null;
                                if ($from_user_id) {
                            ?>
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
                            <?php 
                                }
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Mark notification as read when viewed
    $('.notification-item').each(function() {
        const notificationId = $(this).data('notification-id');
        if (!$(this).hasClass('unread')) return;

        // Create an intersection observer
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Mark as read when notification is visible
                    $.post('mark_notification_read.php', {
                        notification_id: notificationId
                    }, function(response) {
                        if (response.success) {
                            // Remove unread styling
                            $(entry.target).removeClass('unread');
                            $(entry.target).find('.notification-dot').remove();
                            
                            // Update the badge count if it's a friend request
                            if ($(entry.target).find('.fa-user-plus').length > 0) {
                                const currentCount = parseInt($('.badge.bg-danger').text()) || 0;
                                if (currentCount > 0) {
                                    const newCount = currentCount - 1;
                                    if (newCount > 0) {
                                        $('.badge.bg-danger').text(newCount + ' new');
                                    } else {
                                        $('.badge.bg-danger').remove();
                                    }
                                }
                            }
                            // Update the navbar notification badge
                            if (typeof updateNavbarNotificationBadge === 'function') {
                                updateNavbarNotificationBadge();
                            }
                        }
                    });
                    // Stop observing after marking as read
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5 // Trigger when 50% of the notification is visible
        });

        // Start observing the notification
        observer.observe(this);
    });

    // Infinite scroll for notifications
    let loading = false;
    let page = 1;
    const initialCount = <?php echo count($notifications); ?>;
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 200 && !loading) {
            loadMoreNotifications();
        }
    });

    function loadMoreNotifications() {
        loading = true;
        page++;
        $.ajax({
            url: 'load_notifications.php',
            type: 'GET',
            data: { page: page },
            success: function(response) {
                if (response.trim() !== '') {
                    $('.notification-list').append(response);
                    loading = false;
                } else {
                    // No more notifications
                    $(window).off('scroll');
                }
            }
        });
    }

    // If we have less than a page of notifications initially, disable scroll loading
    if (initialCount < 15) { // assuming 15 is your page size
        $(window).off('scroll');
    }
});
</script>
</body>
</html>