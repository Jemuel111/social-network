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

    // Fetch all posts with user information
    $stmt = $conn->prepare("
        SELECT p.*, u.full_name 
        FROM posts p 
        JOIN users u ON p.user_id = u.user_id
    ");
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>

        <div class="content">
            <h2>Manage Posts</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Post ID</th>
                        <th>User ID</th>
                        <th>User Full Name</th>
                        <th>Content</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($post['post_id']); ?></td>
                            <td><?php echo htmlspecialchars($post['user_id']); ?></td>
                            <td><?php echo isset($post['full_name']) ? htmlspecialchars($post['full_name']) : 'N/A'; ?></td> <!-- Check if full_name exists -->
                            <td><?php echo htmlspecialchars($post['content']); ?></td>
                            <td>
                                <form method="POST" action="delete_post.php" class="d-inline">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php require_once '../includes/admin_footer.php'; // Include the common footer ?>