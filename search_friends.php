<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$search = '';
$friends = [];

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);

    $stmt = $conn->prepare("
        SELECT u.* FROM users u
        WHERE (u.full_name LIKE CONCAT('%', ?, '%') OR u.username LIKE CONCAT('%', ?, '%'))
        AND u.user_id != ?
        AND u.role != 'admin'
        AND u.user_id NOT IN (
            SELECT friend_id FROM friendships 
            WHERE user_id = ? AND status = 'accepted'
            UNION
            SELECT user_id FROM friendships 
            WHERE friend_id = ? AND status = 'accepted'
        )
        AND u.user_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = ?)
        AND u.user_id NOT IN (SELECT blocker_id FROM blocked_users WHERE blocked_id = ?)
        GROUP BY u.user_id
        LIMIT 10
    ");
    $stmt->bind_param("ssiiiii", $search, $search, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $friends = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Friends - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h4>Search Friends</h4>

        <form method="GET" class="input-group mb-3">
            <input type="text" name="search" class="form-control" placeholder="Search by name or username" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>

        <div class="list-group">
            <?php if (!empty($search) && $friends->num_rows > 0): ?>
                <?php while ($friend = $friends->fetch_assoc()): ?>
                    <?php
                    // Calculate mutual friends
                    $mutual_stmt = $conn->prepare("
                        SELECT COUNT(*) as mutual_count
                        FROM friendships f1
                        WHERE f1.status = 'accepted'
                          AND ((f1.user_id = ? AND f1.friend_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'))
                            OR (f1.friend_id = ? AND f1.user_id IN (SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted')))
                    ");
                    $mutual_stmt->bind_param("iiii", $_SESSION['user_id'], $friend['user_id'], $_SESSION['user_id'], $friend['user_id']);
                    $mutual_stmt->execute();
                    $mutual_count = $mutual_stmt->get_result()->fetch_assoc()['mutual_count'];
                    ?>
                    <a href="profile.php?id=<?php echo $friend['user_id']; ?>" class="list-group-item list-group-item-action">
                        <?php echo htmlspecialchars($friend['full_name']); ?> (@<?php echo htmlspecialchars($friend['username']); ?>)
                        <span class="badge bg-light text-dark ms-2"><i class="fas fa-users"></i> <?php echo $mutual_count; ?> mutual friends</span>
                    </a>
                <?php endwhile; ?>
            <?php elseif (!empty($search)): ?>
                <p>No friends found matching your search.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
