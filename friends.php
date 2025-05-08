<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// Fetch friends
$stmt = $conn->prepare("
    SELECT u.*
    FROM users u
    INNER JOIN friendships f
        ON (f.user_id = ? AND f.friend_id = u.user_id)
        OR (f.friend_id = ? AND f.user_id = u.user_id)
    WHERE f.status = 'accepted'
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$friends = $result->fetch_all(MYSQLI_ASSOC);

// Fetch pending friend requests
$stmt_pending = $conn->prepare("
    SELECT u.*, f.created_at
    FROM users u
    INNER JOIN friendships f
        ON f.user_id = u.user_id
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
$pending_requests = $result_pending->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Friends - SocialConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-5">
    <h2>My Friends</h2>

    <?php if (empty($friends)): ?>
        <p class="text-muted">You have no friends yet.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($friends as $friend): ?>
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <img src="assets/images/<?php echo htmlspecialchars($friend['profile_pic']); ?>" class="rounded-circle mb-2" width="80" alt="">
                            <h5><?php echo htmlspecialchars($friend['full_name']); ?></h5>
                            <p class="text-muted">@<?php echo htmlspecialchars($friend['username']); ?></p>
                            <a href="profile.php?id=<?php echo $friend['user_id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <hr class="my-4">

    <h2>Friend Requests</h2>

    <?php if (empty($pending_requests)): ?>
        <p class="text-muted">No pending friend requests.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($pending_requests as $request): ?>
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <img src="assets/images/<?php echo htmlspecialchars($request['profile_pic']); ?>" class="rounded-circle mb-2" width="80" alt="">
                            <h5><?php echo htmlspecialchars($request['full_name']); ?></h5>
                            <p class="text-muted">@<?php echo htmlspecialchars($request['username']); ?></p>
                            <form method="POST" action="accept_friend.php" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">Accept</button>
                            </form>
                            <form method="POST" action="decline_friend.php" class="d-inline">
                                <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>
