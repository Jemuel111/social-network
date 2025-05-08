<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

// Fetch online users (active within 5 minutes)
$stmt = $conn->query("
    SELECT * FROM users
    WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
      AND user_id != " . (int)$_SESSION['user_id']
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Users - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h4>Online Users</h4>
        <div class="list-group">
            <?php while ($user = $stmt->fetch_assoc()): ?>
                <a href="profile.php?id=<?php echo $user['user_id']; ?>" class="list-group-item list-group-item-action">
                    <?php echo htmlspecialchars($user['full_name']); ?> (@<?php echo htmlspecialchars($user['username']); ?>)
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
