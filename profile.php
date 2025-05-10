<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

// Check profile
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_SESSION['user_id'];
$is_own_profile = ($profile_id === (int)$_SESSION['user_id']);

// Get user
$user = get_user_by_id($profile_id);

if (!$user) {
    header("Location: index.php");
    exit;
}

// Blocked logic
$is_blocked = false;
$has_blocked = false;
if (!$is_own_profile) {
    $user_id = $_SESSION['user_id'];
    // Check if current user has blocked this profile
    $stmt = $conn->prepare("SELECT 1 FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->bind_param("ii", $user_id, $profile_id);
    $stmt->execute();
    $has_blocked = $stmt->get_result()->num_rows > 0;
    // Check if this profile has blocked current user
    $stmt = $conn->prepare("SELECT 1 FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->bind_param("ii", $profile_id, $user_id);
    $stmt->execute();
    $is_blocked = $stmt->get_result()->num_rows > 0;
}

// If either user has blocked the other, don't show the profile
if ($is_blocked || $has_blocked) {
    header("Location: friends.php");
    exit;
}

// Get user's posts
$stmt = $conn->prepare("
    SELECT p.*, 
        (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Friendship check
$friendship_status = null;
if (!$is_own_profile) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT user_id, friend_id, status 
        FROM friendships 
        WHERE (user_id = ? AND friend_id = ?) 
           OR (user_id = ? AND friend_id = ?)
    ");
    $stmt->bind_param("iiii", $user_id, $profile_id, $profile_id, $user_id);
    $stmt->execute();
    $friendship = $stmt->get_result()->fetch_assoc();

    if ($friendship) {
        $friendship_status = $friendship['status'];
    }
}

// Friend count
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_friends
    FROM friendships
    WHERE (user_id = ? OR friend_id = ?)
      AND status = 'accepted'
");
$stmt->bind_param("ii", $profile_id, $profile_id);
$stmt->execute();
$friend_count = $stmt->get_result()->fetch_assoc()['total_friends'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['full_name']); ?> - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
    <div class="row">
        <!-- Profile Info -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="rounded-circle img-thumbnail mb-3" width="150" alt="Profile Picture">
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>

                    <?php if ($user['bio']): ?>
                        <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>

                    <?php if ($user['location']): ?>
                        <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($user['location']); ?></p>
                    <?php endif; ?>

                    <p><i class="fas fa-calendar-alt me-2"></i>Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    
                    <p><i class="fas fa-user-friends me-2"></i><?php echo $friend_count; ?> Friends</p>

                    <!-- Action buttons -->
                    <?php if (!$is_own_profile): ?>
                        <?php if ($friendship_status === 'accepted'): ?>
                            <form method="POST" action="block_user.php" class="d-grid mb-2">
                                <input type="hidden" name="blocked_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Block User</button>
                            </form>
                            <form method="POST" action="unfriend.php" class="d-grid mb-2">
                                <input type="hidden" name="friend_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-person-dash-fill"></i> Unfriend</button>
                            </form>
                            <a href="messages.php?friend_id=<?php echo $profile_id; ?>" class="btn btn-outline-primary w-100">Send Message</a>
                        <?php elseif ($friendship_status === 'pending'): ?>
                            <button class="btn btn-secondary w-100 mb-2" disabled>Friend Request Pending</button>
                            <form method="POST" action="block_user.php" class="d-grid mb-2">
                                <input type="hidden" name="blocked_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Block User</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="add_friend.php" class="d-grid mb-2">
                                <input type="hidden" name="friend_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add Friend</button>
                            </form>
                            <form method="POST" action="block_user.php" class="d-grid mb-2">
                                <input type="hidden" name="blocked_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Block User</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="edit_profile.php" class="btn btn-primary w-100 mb-2">Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Posts -->
        <div class="col-lg-8">
            <h4 class="mb-3">Posts</h4>

            <?php if (empty($posts)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <p class="text-muted"><?php echo $is_own_profile ? "You haven't" : htmlspecialchars($user['full_name']) . " hasn't"; ?> posted anything yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <div class="d-flex align-items-center">
                                <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="rounded-circle me-2" width="40" alt="Profile Picture">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?> · <?php echo format_date($post['created_at']); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php if ($post['image']): ?>
                                <img src="assets/uploads/<?php echo htmlspecialchars($post['image']); ?>" class="img-fluid rounded mb-3" alt="Post image">
                            <?php endif; ?>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-sm <?php echo has_user_liked_post($_SESSION['user_id'], $post['post_id']) ? 'btn-primary' : 'btn-outline-primary'; ?> like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                    <i class="far fa-thumbs-up me-1"></i> Like <span class="like-count"><?php echo $post['like_count']; ?></span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary comment-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                    <i class="far fa-comment me-1"></i> Comment <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="far fa-share-square me-1"></i> Share
                                </button>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="comment-section" id="comments-<?php echo $post['post_id']; ?>">
                                <?php if ($post['comment_count'] > 0): ?>
                                    <div class="text-center mb-2">
                                        <button class="btn btn-sm btn-link load-comments" data-post-id="<?php echo $post['post_id']; ?>">
                                            Show comments
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form class="comment-form" data-post-id="<?php echo $post['post_id']; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Write a comment..." required>
                                    <button class="btn btn-outline-primary" type="submit">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<?php if (!$is_own_profile): ?>
    <?php
    $mutual_stmt = $conn->prepare("
        SELECT COUNT(*) as mutual_count
        FROM friendships f1
        WHERE f1.status = 'accepted'
          AND ((f1.user_id = ? AND f1.friend_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'))
            OR (f1.friend_id = ? AND f1.user_id IN (SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted')))
    ");
    $mutual_stmt->bind_param("iiii", $user_id, $profile_id, $user_id, $profile_id);
    $mutual_stmt->execute();
    $mutual_count = $mutual_stmt->get_result()->fetch_assoc()['mutual_count'];
    ?>
    <p><i class="fas fa-users me-2"></i><?php echo $mutual_count; ?> Mutual Friends</p>
<?php endif; ?>
</body>
</html>
