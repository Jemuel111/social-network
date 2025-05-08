<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_header.php';

require_login();

// Check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch all users
$users_stmt = $conn->prepare("SELECT * FROM users");
$users_stmt->execute();
$users = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

    <div class="content mt-4">
        <h2>Manage Users</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['banned'] ? 'Banned' : 'Active'; ?></td>
                        <td>
                            <form method="POST" action="delete_user.php" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            <?php if (!$user['banned']): ?>
                                <form method="POST" action="ban_user.php" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Ban</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="unban_user.php" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Unban</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php require_once '../includes/admin_footer.php'; // Include the common footer ?>