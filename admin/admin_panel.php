<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_header.php';
require_login(); // Ensure the user is logged in

// Check if the user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Redirect to index if not admin
    exit;
}

// Fetch all users and posts
$users_stmt = $conn->prepare("SELECT * FROM users");
$users_stmt->execute();
$users = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$posts_stmt = $conn->prepare("SELECT * FROM posts");
$posts_stmt->execute();
$posts = $posts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_users = count($users);
$total_posts = count($posts);
$active_users = count(array_filter($users, function($user) {
    return !$user['banned'];
}));
?>


<div class="content">
    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>
    <h3>Admin Panel</h3>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="title">
                    <span>
                        <i class="fas fa-users"></i>
                    </span>
                    <h5 class="title-text">Total Users</h5>
                </div>
                <div class="data">
                    <h2><?php echo $total_users; ?></h2>
                    <div class="range">
                        <div class="fill" style="width: 70%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="title">
                    <span>
                        <i class="fas fa-file-alt"></i>
                    </span>
                    <h5 class="title-text">Total Posts</h5>
                </div>
                <div class="data">
                    <h2><?php echo $total_posts; ?></h2>
                    <div class="range">
                        <div class="fill" style="width: 60%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="title">
                    <span>
                        <i class="fas fa-user-check"></i>
                    </span>
                    <h5 class="title-text">Active Users</h5>
                </div>
                <div class="data">
                    <h2><?php echo $active_users; ?></h2>
                    <div class="range">
                        <div class="fill" style="width: 80%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3>Manage Users</h3>
    <div class="table-container">
        <div class="table-header">
            <div class="input-group">
                <i class="fas fa-search"></i>
                <input type="search" id="users-search-input" placeholder="Search users...">
            </div>
        </div>
        <div class="table-body">
            <table id="users-table">
                <thead>
                    <tr>
                        <th>User ID <i class="fas fa-arrow-down icon-arrow"></i></th>
                        <th>Full Name <i class="fas fa-arrow-down icon-arrow"></i></th>
                        <th>Username <i class="fas fa-arrow-down icon-arrow"></i></th>
                        <th>Email <i class="fas fa-arrow-down icon-arrow"></i></th>
                        <th>Status <i class="fas fa-arrow-down icon-arrow"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td>
                                <?php 
                                $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
                                ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <p class="status <?php echo $user['banned'] ? 'cancelled' : 'delivered'; ?>">
                                    <?php if ($user['banned']): ?>
                                        <i class="fas fa-ban"></i> Banned
                                    <?php else: ?>
                                        <i class="fas fa-check-circle"></i> Active
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <form method="POST" action="delete_user.php" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h3>Manage Posts</h3>
    <div class="table-container">
        <div class="table-header">
            <div class="input-group">
                <i class="fas fa-search"></i>
                <input type="search" id="posts-search-input" placeholder="Search posts...">
            </div>
        </div>
        <div class="table-body">
            <table id="posts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Author</th>
                        <th>Content</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="posts-table-body">
                    <?php 
                    $posts_stmt = $conn->prepare("
                        SELECT p.post_id, p.content, p.created_at, 
                            u.username, u.profile_pic 
                        FROM posts p
                        JOIN users u ON p.user_id = u.user_id
                        ORDER BY p.created_at DESC
                    ");
                    $posts_stmt->execute();
                    $posts = $posts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    foreach ($posts as $post): ?>
                        <tr class="post-row">
                            <td><?php echo htmlspecialchars($post['post_id']); ?></td>
                            <td>
                                <div class="author-cell">
                                    <?php 
                                    $profile_pic = !empty($post['profile_pic']) ? $post['profile_pic'] : 'default.jpg';
                                    ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
                                    <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                                </div>
                            </td>
                            <td class="content-cell"><?php echo htmlspecialchars(shortenText($post['content'], 60)); ?></td>
                            <td><?php echo date('n/j/y', strtotime($post['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="delete_post.php" class="delete-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
// Search functionality for both tables
function setupTableSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let rowMatches = false;
            
            // Skip the last cell (actions column)
            for (let i = 0; i < cells.length - 1; i++) {
                if (cells[i].textContent.toLowerCase().includes(searchTerm)) {
                    rowMatches = true;
                    break;
                }
            }
            
            row.style.display = rowMatches ? '' : 'none';
        });
    });
}

// Initialize search for both tables
document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('users-table', 'users-search-input');
    setupTableSearch('posts-table', 'posts-search-input');
});
document.addEventListener('DOMContentLoaded', function() {
    fetch('ajax/get_report_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('report-count-badge');
            if (badge && data.count > 0) {
                badge.textContent = data.count;
            } else if (badge) {
                badge.style.display = 'none';
            }
        });
});
</script>
<?php
    require_once '../includes/admin_footer.php';
