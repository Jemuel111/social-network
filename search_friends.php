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
        INNER JOIN friendships f ON (f.friend_id = u.user_id OR f.user_id = u.user_id)
        WHERE f.status = 'accepted'
          AND (u.full_name LIKE CONCAT('%', ?, '%') OR u.username LIKE CONCAT('%', ?, '%'))
          AND (f.user_id = ? OR f.friend_id = ?)
          AND u.user_id != ?
        GROUP BY u.user_id
    ");
    $stmt->bind_param("ssiii", $search, $search, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
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
                    <a href="profile.php?id=<?php echo $friend['user_id']; ?>" class="list-group-item list-group-item-action">
                        <?php echo htmlspecialchars($friend['full_name']); ?> (@<?php echo htmlspecialchars($friend['username']); ?>)
                    </a>
                <?php endwhile; ?>
            <?php elseif (!empty($search)): ?>
                <p>No friends found matching your search.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
