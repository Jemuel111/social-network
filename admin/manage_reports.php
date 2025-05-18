<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_header.php';
require_admin_login();

// Fetch reported posts
$sql = "SELECT r.id, r.post_id, r.reporter_id, r.reason, r.created_at, p.content, p.image, u.full_name AS reporter_name
        FROM reported_posts r
        JOIN posts p ON r.post_id = p.post_id
        JOIN users u ON r.reporter_id = u.user_id
        WHERE r.status = 'pending'
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);

?>

<div class="container mt-4">
    <h3 class="text-white">Reported Posts</h3>
    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-success">No pending reports.</div>
    <?php else: ?>
        <table class="table table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>Post Content</th>
                    <th>Image</th>
                    <th>Reporter</th>
                    <th>Reason</th>
                    <th>Reported At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['content']); ?></td>
                        <td><?php if ($row['image']): ?><img src="../assets/uploads/<?php echo htmlspecialchars($row['image']); ?>" width="80"/><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <form method="POST" action="delete_post.php" style="display:inline-block">
                                <input type="hidden" name="post_id" value="<?php echo $row['post_id']; ?>">
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this post?')">Delete Post</button>
                            </form>
                            <form method="POST" action="dismiss_report.php" style="display:inline-block">
                                <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                <button class="btn btn-secondary btn-sm">Dismiss</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require_once '../includes/admin_footer.php';