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

// Handle post submission
$post_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_content'])) {
    $content = clean_input($_POST['post_content']);
    $image = '';
    $user_id = $_SESSION['user_id'];

    // Check if upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['post_image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($filetype, $allowed)) {
            $new_filename = uniqid('img_', true) . '.' . $filetype;
            $upload_path = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
            } else {
                $post_message = "Failed to move uploaded file.";
            }
        } else {
            $post_message = "Invalid file type.";
        }
    }

    // Insert post if no file errors
    if (empty($post_message)) {
        $visibility = isset($_POST['post_visibility']) ? $_POST['post_visibility'] : 'public';
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image, visibility) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $content, $image, $visibility);

        if ($stmt->execute()) {
            $post_id = $conn->insert_id;
            
            // If specific friends were selected, add them to post_visibility_friends
            if ($visibility === 'specific' && !empty($_POST['selected_friends'])) {
                $friend_ids = explode(',', $_POST['selected_friends']);
                $stmt = $conn->prepare("INSERT INTO post_visibility_friends (post_id, friend_id) VALUES (?, ?)");
                foreach ($friend_ids as $friend_id) {
                    $stmt->bind_param("ii", $post_id, $friend_id);
                    $stmt->execute();
                }
            }
            
            $post_message = "Post created successfully!";
        } else {
            $post_message = "Error creating post: " . $conn->error;
        }
    }
}
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
        /* Layout styles */
        body {
            height: 100vh;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .page-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .back-button-container {
            padding: 1rem;
            background: var(--navbar-bg);
            z-index: 1000;
        }

        .main-content {
            flex: 1;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            padding: 1rem;
            height: calc(100vh - 60px); /* Subtract back button height */
            overflow: hidden;
        }

        .left-column {
            height: 100%;
            overflow-y: auto;
            padding-right: 1rem;
        }

        .right-column {
            height: 100%;
            overflow-y: auto;
            padding-left: 1rem;
        }

        /* Custom scrollbar */
        .left-column::-webkit-scrollbar,
        .right-column::-webkit-scrollbar {
            width: 6px;
        }

        .left-column::-webkit-scrollbar-track,
        .right-column::-webkit-scrollbar-track {
            background: var(--input-bg);
            border-radius: 3px;
        }

        .left-column::-webkit-scrollbar-thumb,
        .right-column::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 3px;
        }

        /* Responsive design */
        @media (max-width: 992px) {
            body {
                overflow-y: auto;
                height: auto;
            }

            .page-container {
                height: auto;
                min-height: 100vh;
            }

            .main-content {
                grid-template-columns: 1fr;
                gap: 1rem;
                height: auto;
                overflow: visible;
            }

            .left-column,
            .right-column {
                height: auto;
                overflow: visible;
                padding: 0;
            }

            .profile-card,
            .friends-section,
            .create-post-section,
            .posts-container {
                margin-bottom: 1.5rem;
            }
        }

        /* Existing styles */
        .profile-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .posts-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .privacy-indicator {
            margin-left: 8px;
            color: var(--accent);
            font-size: 0.9em;
        }
        .privacy-indicator i {
            transition: transform 0.2s ease;
        }
        .privacy-indicator:hover i {
            transform: scale(1.2);
        }
        .friend-selector {
            position: absolute;
            background: var(--card-bg);
            border: 1px solid #4A3F85;
            border-radius: 8px;
            padding: 10px;
            margin-top: 5px;
            width: 300px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            right: 0;
        }
        .selected-friends {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }
        .selected-friend {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .selected-friend img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .remove-friend {
            margin-left: 5px;
            cursor: pointer;
            color: var(--accent);
        }
        .friend-search {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 4px;
            color: white;
        }
        .friend-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .friend-item {
            display: flex;
            align-items: center;
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
        }
        .friend-item:hover {
            background: var(--hover-bg);
        }
        .friend-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }
        /* Visibility Selector Styles */
        .visibility-select {
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 8px;
            color: white;
            padding: 0.5rem;
            margin-left: 0.5rem;
            margin-right: 50px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .visibility-select:hover {
            background: var(--color-4);
            border-color: var(--color-6);
        }
        
        .visibility-select option {
            background: var(--card-bg);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>

    <div class="page-container">
        <div class="back-button-container">
            <button class="back-to-home-btn" onclick="window.location.href='index.php'">
                <i style="color: white;" class="bi bi-caret-left-fill"></i> Back to Home
            </button>
        </div>
        
        <div class="main-content">
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
                        <?php if ($post_message): ?>
                            <div class="alert alert-success"><?php echo $post_message; ?></div>
                        <?php endif; ?>
                        <form class="post-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            <textarea class="post-input" name="post_content" placeholder="What's on your mind?" required></textarea>
                            <div class="post-actions" style="position:relative;">
                                <label class="post-upload">
                                    <input type="file" name="post_image" accept="image/*" style="display: none;">
                                    <i class="fas fa-image"></i>
                                    <span>Photo</span>
                                </label>
                                <select name="post_visibility" class="form-select visibility-select" id="visibility-select">
                                    <option value="public">Public</option>
                                    <option value="friends">Friends Only</option>
                                    <option value="specific">Specific Friends</option>
                                </select>
                                <div id="friend-selector" class="friend-selector" style="display: none;">
                                    <div class="selected-friends"></div>
                                    <input type="text" class="friend-search" placeholder="Search friends...">
                                    <div class="friend-list"></div>
                                </div>
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
                                            <p class="post-time">@<?php echo htmlspecialchars($user['username']); ?> · <?php echo format_date($post['created_at']); ?>
                                                <span class="privacy-indicator" title="<?php echo ucfirst($post['visibility']); ?>">
                                                    <?php if ($post['visibility'] === 'public'): ?>
                                                        <i class="fas fa-globe"></i>
                                                    <?php elseif ($post['visibility'] === 'friends'): ?>
                                                        <i class="fas fa-user-friends"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-user-lock"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </p>
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
                                            <img src="assets/images/<?php echo htmlspecialchars($post['image']); ?>" class="post-image" alt="Post image">
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

    document.addEventListener('DOMContentLoaded', function() {
        const visibilitySelect = document.getElementById('visibility-select');
        const friendSelector = document.getElementById('friend-selector');
        const friendSearch = document.querySelector('.friend-search');
        const friendList = document.querySelector('.friend-list');
        const selectedFriends = document.querySelector('.selected-friends');
        let selectedFriendIds = new Set();

        if (visibilitySelect) {
            visibilitySelect.addEventListener('change', function() {
                if (this.value === 'specific') {
                    friendSelector.style.display = 'block';
                    loadFriends();
                } else {
                    friendSelector.style.display = 'none';
                }
            });
        }

        document.addEventListener('mousedown', function(event) {
            if (
                friendSelector &&
                friendSelector.style.display === 'block' &&
                !friendSelector.contains(event.target) &&
                event.target !== visibilitySelect
            ) {
                friendSelector.style.display = 'none';
            }
        });

        function loadFriends() {
            fetch('ajax/get_friends.php')
                .then(response => response.json())
                .then(friends => {
                    friendList.innerHTML = friends.map(friend => `
                        <div class="friend-item" data-id="${friend.user_id}">
                            <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                            <span>${friend.username}</span>
                        </div>
                    `).join('');
                    document.querySelectorAll('.friend-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const friendId = this.dataset.id;
                            if (!selectedFriendIds.has(friendId)) {
                                selectedFriendIds.add(friendId);
                                const friend = friends.find(f => f.user_id == friendId);
                                selectedFriends.innerHTML += `
                                    <div class="selected-friend" data-id="${friendId}">
                                        <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                                        ${friend.username}
                                        <span class="remove-friend" onclick="removeFriend(${friendId})">&times;</span>
                                    </div>
                                `;
                            }
                        });
                    });
                });
        }

        if (friendSearch) {
            friendSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.friend-item').forEach(item => {
                    const username = item.querySelector('span').textContent.toLowerCase();
                    item.style.display = username.includes(searchTerm) ? 'flex' : 'none';
                });
            });
        }

        window.removeFriend = function(friendId) {
            selectedFriendIds.delete(friendId.toString());
            const el = document.querySelector(`.selected-friend[data-id="${friendId}"]`);
            if (el) el.remove();
        };

        const postForm = document.querySelector('.post-form');
        if (postForm) {
            postForm.addEventListener('submit', function(e) {
                if (visibilitySelect && visibilitySelect.value === 'specific') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_friends';
                    input.value = Array.from(selectedFriendIds).join(',');
                    this.appendChild(input);
                }
            });
        }
    });
    </script>
</body>
</html>
