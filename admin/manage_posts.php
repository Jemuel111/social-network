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

    // Fetch all posts, including shared posts and their original info
    $posts_stmt = $conn->prepare("
        SELECT 
            p.post_id, p.content, p.created_at, p.shared_post_id,
            u.username AS sharer_username, u.profile_pic AS sharer_profile_pic,
            orig.content AS orig_content, orig.created_at AS orig_created_at,
            orig_user.username AS orig_username, orig_user.profile_pic AS orig_profile_pic
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN posts orig ON p.shared_post_id = orig.post_id
        LEFT JOIN users orig_user ON orig.user_id = orig_user.user_id
        ORDER BY p.created_at DESC
    ");
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>

        <div class="content">
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
                            <?php foreach ($posts as $post): ?>
                                <tr class="post-row">
                                    <td><?php echo htmlspecialchars($post['post_id']); ?></td>
                                    <td>
                                        <?php 
                                        $profile_pic = !empty($post['sharer_profile_pic']) ? $post['sharer_profile_pic'] : 'default.jpg';
                                        ?>
                                        <img src="../assets/images/<?php echo htmlspecialchars($profile_pic); ?>" class="profile-pic">
                                        <?php echo htmlspecialchars($post['sharer_username']); ?>
                                        <?php if (!empty($post['shared_post_id'])): ?>
                                            <br><span style="font-size:12px;color:#F187EA;">shared</span>
                                            <?php if (!empty($post['orig_username'])): ?>
                                                <img src="../assets/images/<?php echo htmlspecialchars($post['orig_profile_pic'] ?: 'default.jpg'); ?>" class="profile-pic" style="width:20px;height:20px;"> <?php echo htmlspecialchars($post['orig_username']); ?>
                                            <?php else: ?>
                                                <span style="color:#aaa;">[Original deleted]</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($post['shared_post_id'])): ?>
                                            <span style="color:#F187EA;font-size:12px;">Shared post:</span><br>
                                            <?php echo htmlspecialchars($post['orig_content'] ?? '[Original post deleted]'); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(shortenText($post['content'], 60)); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('n/j/y', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" action="delete_post.php">
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
        // Search functionality for posts table
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
        document.addEventListener('DOMContentLoaded', function() {
            setupTableSearch('posts-table', 'posts-search-input');
        });
        </script>

        

        <?php require_once '../includes/admin_footer.php'; // Include the common footer ?>