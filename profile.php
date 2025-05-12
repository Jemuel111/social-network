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
        (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
        (SELECT COUNT(*) FROM posts WHERE shared_post_id = p.post_id) as share_count
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
    <title><?php echo htmlspecialchars($user['full_name']); ?> - Zyntra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/profile-style.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional styles specific to profile page */
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        
        .posts-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>

    <div class="container profile-container">
        <!-- Left Column -->
        <div class="left-column">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="profile-pic-large" alt="Profile Picture">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <?php if ($user['bio']): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $friend_count; ?></div>
                        <div class="stat-label">Friends</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($posts); ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-item">
                        <?php 
                        $total_likes = 0;
                        foreach ($posts as $post) {
                            $total_likes += $post['like_count'];
                        }
                        ?>
                        <div class="stat-value"><?php echo $total_likes; ?></div>
                        <div class="stat-label">Likes</div>
                    </div>
                </div>

                <!-- Info -->
                <div class="profile-info">
                    <?php if ($user['location']): ?>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($user['location']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    
                    <?php if (!$is_own_profile && $mutual_count > 0): ?>
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo $mutual_count; ?> Mutual Friends</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="profile-actions">
                    <?php if (!$is_own_profile): ?>
                        <?php if ($friendship_status === 'accepted'): ?>
                            <form method="POST" action="block_user.php">
                                <input type="hidden" name="blocked_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="profile-btn profile-btn-danger"><i class="fas fa-ban"></i> Block User</button>
                            </form>
                            <form method="POST" action="unfriend.php">
                                <input type="hidden" name="friend_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="profile-btn profile-btn-danger"><i class="fas fa-user-minus"></i> Unfriend</button>
                            </form>
                            <a href="messages.php?friend_id=<?php echo $profile_id; ?>" class="profile-btn profile-btn-primary">Send Message</a>
                        <?php elseif ($friendship_status === 'pending'): ?>
                            <button class="profile-btn" disabled>Friend Request Pending</button>
                            <form method="POST" action="block_user.php">
                                <input type="hidden" name="blocked_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="profile-btn profile-btn-danger"><i class="fas fa-ban"></i> Block User</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="add_friend.php">
                                <input type="hidden" name="friend_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="profile-btn profile-btn-primary"><i class="fas fa-user-plus"></i> Add Friend</button>
                            </form>
                            <form method="POST" action="block_user.php">
                                <input type="hidden" name="blocked_id" value="<?php echo $profile_id; ?>">
                                <button type="submit" class="profile-btn profile-btn-danger"><i class="fas fa-ban"></i> Block User</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="edit_profile.php" class="profile-btn profile-btn-primary">Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Friends Section -->
            <div class="friends-section">
                <div class="friends-header">
                    <h3 class="friends-title">Friends</h3>
                    <a href="friends.php?id=<?php echo $profile_id; ?>" class="btn btn-sm" style="color: var(--color-6);">See All</a>
                </div>
                
                <div class="friends-grid">
                    <?php 
                    // Get a few friends to display
                    $stmt = $conn->prepare("
                        SELECT u.user_id, u.username, u.full_name, u.profile_pic
                        FROM users u
                        JOIN friendships f ON (u.user_id = f.friend_id OR u.user_id = f.user_id) 
                        WHERE (f.user_id = ? OR f.friend_id = ?) 
                          AND u.user_id != ? 
                          AND f.status = 'accepted'
                        LIMIT 6
                    ");
                    $stmt->bind_param("iii", $profile_id, $profile_id, $profile_id);
                    $stmt->execute();
                    $friends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (!empty($friends)) {
                        foreach ($friends as $friend) {
                            echo '
                            <a href="profile.php?id='.$friend['user_id'].'" class="friend-card">
                                <img src="assets/images/'.htmlspecialchars($friend['profile_pic']).'" class="friend-avatar" alt="Friend Avatar">
                                <h4 class="friend-name">'.htmlspecialchars($friend['full_name']).'</h4>
                                <p class="friend-username">@'.htmlspecialchars($friend['username']).'</p>
                            </a>';
                        }
                    } else {
                        echo '<p style="color: var(--color-3);">No friends to display</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
            <?php if ($is_own_profile): ?>
                <!-- Create Post Section -->
                <div class="create-post-section">
                    <h3 class="create-post-header">Create Post</h3>
                    <form class="post-form" action="create_post.php" method="POST" enctype="multipart/form-data">
                        <textarea class="post-input" name="content" placeholder="What's on your mind?" required></textarea>
                        <div class="post-actions">
                            <label class="post-upload">
                                <input type="file" name="image" accept="image/*" style="display: none;">
                                <i class="fas fa-image"></i>
                                <span>Photo</span>
                            </label>
                            <button type="submit" class="post-submit">Post</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Posts Section -->
            <div class="posts-container">
                <?php if (empty($posts)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-newspaper fa-3x mb-3 text-white"></i>
                            <h5>No Posts Yet</h5>
                            <p class="text-white">
                                <?php echo $is_own_profile ? "You haven't" : htmlspecialchars($user['full_name']) . " hasn't"; ?> posted anything yet.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card post">
                            <div class="card-body">
                                <div class="post-header d-flex align-items-center">
                                    <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="post-avatar" alt="Profile Picture">
                                    <div class="post-user flex-grow-1">
                                        <h6 class="post-username"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                                        <p class="post-time">@<?php echo htmlspecialchars($user['username']); ?> · <?php echo format_date($post['created_at']); ?></p>
                                    </div>
                                    <?php if ($is_own_profile): ?>
                                        <div class="post-menu ms-auto">
                                            <button class="menu-trigger" type="button" tabindex="0"><i class="fas fa-ellipsis-h"></i></button>
                                            <div class="dropdown-menu">
                                                <button class="dropdown-item edit-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Edit Post</button>
                                                <button class="dropdown-item delete-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Delete Post</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-content">
                                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <?php if ($post['image']): ?>
                                        <img src="assets/uploads/<?php echo htmlspecialchars($post['image']); ?>" class="post-image" alt="Post image">
                                    <?php endif; ?>
                                </div>
                                
                                <div class="post-footer">
                                    <button class="post-action <?php echo has_user_liked_post($_SESSION['user_id'], $post['post_id']) ? 'text-primary' : ''; ?> like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                        <i class="<?php echo has_user_liked_post($_SESSION['user_id'], $post['post_id']) ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                                        <span class="like-count"><?php echo $post['like_count']; ?></span>
                                    </button>
                                    <button class="post-action comment-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                        <i class="bi bi-chat-fill"></i>
                                        <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                    </button>
                                    <button class="share-btn <?php echo has_user_shared_post($_SESSION['user_id'], $post['post_id']) ? 'shared' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                                        <i class="fas fa-share"></i>
                                        <span class="share-count"><?php echo $post['share_count']; ?></span>
                                    </button>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="comment-section" id="comments-<?php echo $post['post_id']; ?>">
                                        <?php if ($post['comment_count'] > 0): ?>
                                            <div class="text-center mb-2">
                                                <button class="btn btn-sm btn-link load-comments" data-post-id="<?php echo $post['post_id']; ?>">
                                                    <i class="fa-solid fa-caret-down" style="color: var(--color-6);"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <form class="comment-form mt-3" data-post-id="<?php echo $post['post_id']; ?>">
                                        <div class="input-group">
                                            <input type="text" class="form-control comment-input" placeholder="Write a comment..." required>
                                            <button class="btn btn-outline" type="submit"><i class="fas fa-paper-plane"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    // Handle image upload preview
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You could display a preview here if needed
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Like button functionality
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const likeCount = this.querySelector('.like-count');
            
            fetch('like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCount.textContent = data.like_count;
                    if (data.action === 'like') {
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary');
                    } else {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline-primary');
                    }
                }
            });
        });
    });
    
    // Load comments
    document.querySelectorAll('.load-comments').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const commentSection = document.getElementById(`comments-${postId}`);
            
            fetch(`get_comments.php?post_id=${postId}`)
            .then(response => response.json())
            .then(comments => {
                let html = '';
                comments.forEach(comment => {
                    html += `
                    <div style="margin-bottom: 1rem; padding: 0.5rem; background: var(--color-4); border-radius: 10px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <img src="assets/images/${comment.profile_pic}" width="30" height="30" style="border-radius: 50%;">
                            <strong style="color: var(--white);">${comment.full_name}</strong>
                            <small style="color: var(--color-3);">${comment.time_ago}</small>
                        </div>
                        <p style="color: var(--white); margin: 0;">${comment.content}</p>
                    </div>`;
                });
                
                commentSection.innerHTML = html;
            });
        });
    });
    
    // Submit comment
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const input = this.querySelector('input');
            const commentContent = input.value;
            const commentSection = document.getElementById(`comments-${postId}`);
            
            fetch('post_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&content=${encodeURIComponent(commentContent)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    const commentCount = document.querySelector(`.comment-btn[data-post-id="${postId}"] .comment-count`);
                    commentCount.textContent = parseInt(commentCount.textContent) + 1;
                    
                    // Add the new comment to the section
                    const commentHtml = `
                    <div style="margin-bottom: 1rem; padding: 0.5rem; background: var(--color-4); border-radius: 10px;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <img src="assets/images/${data.profile_pic}" width="30" height="30" style="border-radius: 50%;">
                            <strong style="color: var(--white);">${data.full_name}</strong>
                            <small style="color: var(--color-3);">Just now</small>
                        </div>
                        <p style="color: var(--white); margin: 0;">${data.content}</p>
                    </div>`;
                    
                    if (commentSection.querySelector('.load-comments')) {
                        commentSection.removeChild(commentSection.querySelector('.load-comments'));
                    }
                    
                    commentSection.insertAdjacentHTML('afterbegin', commentHtml);
                }
            });
        });
    });
    </script>
</body>
</html>
