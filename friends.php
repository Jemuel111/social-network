<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch friends (exclude blocked, filter by search)
$friends_sql = "
    SELECT u.*,
        (SELECT COUNT(*) FROM friendships f2 WHERE ((f2.user_id = u.user_id AND f2.friend_id != ?) OR (f2.friend_id = u.user_id AND f2.user_id != ?)) AND f2.status = 'accepted') as mutual_friends
    FROM users u
    INNER JOIN friendships f
        ON (f.user_id = ? AND f.friend_id = u.user_id)
        OR (f.friend_id = ? AND f.user_id = u.user_id)
    WHERE f.status = 'accepted' AND u.user_id != ?
      AND u.user_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = ?)
      AND u.user_id NOT IN (SELECT blocker_id FROM blocked_users WHERE blocked_id = ?)
";
$params = [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id];
$types = 'iiiiiii';
if ($search !== '') {
    $friends_sql .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
$friends_sql .= " ORDER BY u.full_name ASC";
$stmt = $conn->prepare($friends_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$friends = $result->fetch_all(MYSQLI_ASSOC);

// Fetch pending friend requests (exclude blocked)
$stmt_pending = $conn->prepare("
    SELECT u.*, f.created_at
    FROM users u
    INNER JOIN friendships f
        ON f.user_id = u.user_id
    WHERE f.friend_id = ? AND f.status = 'pending'
      AND u.user_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = ?)
      AND u.user_id NOT IN (SELECT blocker_id FROM blocked_users WHERE blocked_id = ?)
");
$stmt_pending->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
$pending_requests = $result_pending->fetch_all(MYSQLI_ASSOC);

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$total_friends = count($friends);
$total_pages = max(1, ceil($total_friends / $per_page));
$friends_paginated = array_slice($friends, ($page-1)*$per_page, $per_page);

// Friend Suggestions (up to 5, random, not already friends, not self, not admin, exclude blocked)
$suggestion_query = "SELECT u.* FROM users u 
    WHERE u.user_id != ? 
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
    ORDER BY RAND()
    LIMIT 5";
$suggestion_stmt = $conn->prepare($suggestion_query);
$suggestion_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$suggestion_stmt->execute();
$suggestion_result = $suggestion_stmt->get_result();
$friend_suggestions = [];
while ($row = $suggestion_result->fetch_assoc()) {
    $friend_suggestions[] = $row;
}

// Fetch blocked users
$blocked_query = "
    SELECT u.*, 
        (SELECT COUNT(*) FROM friendships f2 WHERE ((f2.user_id = u.user_id AND f2.friend_id != ?) OR (f2.friend_id = u.user_id AND f2.user_id != ?)) AND f2.status = 'accepted') as mutual_friends
    FROM users u 
    INNER JOIN blocked_users b ON u.user_id = b.blocked_id 
    WHERE b.blocker_id = ?
    ORDER BY u.full_name ASC";
$blocked_stmt = $conn->prepare($blocked_query);
$blocked_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$blocked_stmt->execute();
$blocked_users = $blocked_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Friends - Zyntra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/friend-style.css">
</head>
<body>
    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>
<?php include 'includes/navbar.php'; ?>

<div class="container container-friend py-5">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="friends-container">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-3">
            <h2 class="friends-header mb-3 mb-md-0">Friends</h2>
            <form class="d-flex" style="max-width: 300px;" method="GET">
                <input class="friend-search me-2" type="search" name="search" placeholder="Search friends..." aria-label="Search" value="<?php echo htmlspecialchars($search); ?>">
                <button class="friend-btn friend-btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <ul class="nav nav-tabs mb-4" id="friendsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All Friends</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                    Requests
                    <?php if(count($pending_requests)): ?>
                        <span class="badge request-badge"><?php echo count($pending_requests); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="suggestions-tab" data-bs-toggle="tab" data-bs-target="#suggestions" type="button" role="tab">Suggestions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="blocked-tab" data-bs-toggle="tab" data-bs-target="#blocked" type="button" role="tab">Blocked</button>
            </li>
        </ul>
        <div class="tab-content" id="friendsTabContent">
            <!-- All Friends Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <?php if (empty($friends_paginated)): ?>
                    <p class="text-muted">You have no friends yet.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($friends_paginated as $friend): ?>
                            <div class="col-md-4">
                                <a href="profile.php?id=<?php echo $friend['user_id']; ?>" style="text-decoration:none;color:inherit;">
                                    <div class="friend-card">
                                        <div class="d-flex align-items-center">
                                            <img src="assets/images/<?php echo htmlspecialchars($friend['profile_pic']); ?>" class="friend-profile-pic me-3" alt="">
                                            <div class="flex-grow-1">
                                                <h5 class="friend-name mb-0"><?php echo htmlspecialchars($friend['full_name']); ?></h5>
                                                <small class="friend-username">@<?php echo htmlspecialchars($friend['username']); ?></small><br>
                                                <span class="friend-status status-online mt-1"><i class="fas fa-users"></i> <?php echo $friend['mutual_friends'] ?? 0; ?> mutual friends</span>
                                                <div class="friend-actions">
                                                    <a href="messages.php?friend_id=<?php echo $friend['user_id']; ?>" class="friend-btn friend-btn-primary mb-1"><i class="fas fa-envelope"></i></a>
                                                    <form method="POST" action="block_user.php" class="mb-1">
                                                        <input type="hidden" name="blocked_id" value="<?php echo $friend['user_id']; ?>">
                                                        <button type="submit" class="friend-btn friend-btn-secondary"><i class="fas fa-ban"></i></button>
                                                    </form>
                                                    <form method="POST" action="unfriend.php">
                                                        <input type="hidden" name="friend_id" value="<?php echo $friend['user_id']; ?>">
                                                        <button type="submit" class="friend-btn friend-btn-secondary"><i class="bi bi-person-dash-fill"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"> <?php echo $i; ?> </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
            <!-- Requests Tab -->
            <div class="tab-pane fade" id="requests" role="tabpanel">
                <?php if (empty($pending_requests)): ?>
                    <p class="text-muted">No pending friend requests.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="col-md-4">
                                <div class="friend-card">
                                    <div class="d-flex align-items-center">
                                        <img src="assets/images/<?php echo htmlspecialchars($request['profile_pic']); ?>" class="friend-profile-pic me-3" alt="">
                                        <div class="flex-grow-1">
                                            <h5 class="friend-name mb-0"><?php echo htmlspecialchars($request['full_name']); ?></h5>
                                            <small class="friend-username">@<?php echo htmlspecialchars($request['username']); ?></small>
                                            <div class="friend-actions">
                                                <form method="POST" action="accept_friend.php" class="mb-1">
                                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                    <button type="submit" class="friend-btn friend-btn-primary"><i class="fas fa-check"></i></button>
                                                </form>
                                                <form method="POST" action="decline_friend.php" class="mb-1">
                                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                                    <button type="submit" class="friend-btn friend-btn-secondary"><i class="fas fa-times"></i></button>
                                                </form>
                                                <form method="POST" action="block_user.php">
                                                    <input type="hidden" name="blocked_id" value="<?php echo $request['user_id']; ?>">
                                                    <button type="submit" class="friend-btn friend-btn-secondary"><i class="fas fa-ban"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Suggestions Tab -->
            <div class="tab-pane fade" id="suggestions" role="tabpanel">
                <?php if (empty($friend_suggestions)): ?>
                    <p class="text-muted">No suggestions at the moment.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($friend_suggestions as $suggestion): ?>
                            <?php
                            // Calculate mutual friends
                            $mutual_stmt = $conn->prepare("
                                SELECT COUNT(*) as mutual_count
                                FROM friendships f1
                                WHERE f1.status = 'accepted'
                                  AND ((f1.user_id = ? AND f1.friend_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'))
                                    OR (f1.friend_id = ? AND f1.user_id IN (SELECT user_id FROM friendships WHERE friend_id = ? AND status = 'accepted')))
                            ");
                            $mutual_stmt->bind_param("iiii", $user_id, $suggestion['user_id'], $user_id, $suggestion['user_id']);
                            $mutual_stmt->execute();
                            $mutual_count = $mutual_stmt->get_result()->fetch_assoc()['mutual_count'];
                            ?>
                            <div class="col-md-4">
                                <a href="profile.php?id=<?php echo $suggestion['user_id']; ?>" style="text-decoration:none;color:inherit;">
                                    <div class="friend-card">
                                        <div class="d-flex align-items-center">
                                            <img src="assets/images/<?php echo htmlspecialchars($suggestion['profile_pic']); ?>" class="friend-profile-pic me-3" alt="">
                                            <div class="flex-grow-1">
                                                <h5 class="friend-name mb-0"><?php echo htmlspecialchars($suggestion['full_name']); ?></h5>
                                                <small class="friend-username">@<?php echo htmlspecialchars($suggestion['username']); ?></small><br>
                                                <span class="friend-status status-online mt-1"><i class="fas fa-users"></i> <?php echo $mutual_count; ?> mutual friends</span>
                                                <div class="friend-actions">
                                                    <form method="POST" action="add_friend.php" class="mt-2">
                                                        <input type="hidden" name="friend_id" value="<?php echo $suggestion['user_id']; ?>">
                                                        <button type="submit" class="friend-btn friend-btn-primary"><i class="bi bi-person-plus"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Blocked Tab -->
            <div class="tab-pane fade" id="blocked" role="tabpanel">
                <?php if (empty($blocked_users)): ?>
                    <p class="text-muted">You haven't blocked any users.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($blocked_users as $blocked): ?>
                            <div class="col-md-4">
                                <div class="friend-card">
                                    <div class="d-flex align-items-center">
                                        <img src="assets/images/<?php echo htmlspecialchars($blocked['profile_pic']); ?>" class="friend-profile-pic me-3" alt="">
                                        <div class="flex-grow-1">
                                            <h5 class="friend-name mb-0"><?php echo htmlspecialchars($blocked['full_name']); ?></h5>
                                            <small class="friend-username">@<?php echo htmlspecialchars($blocked['username']); ?></small><br>
                                            <span class="friend-status status-online mt-1"><i class="fas fa-users"></i> <?php echo $blocked['mutual_friends'] ?? 0; ?> mutual friends</span>
                                            <div class="friend-actions">
                                                <form method="POST" action="unblock_user.php">
                                                    <input type="hidden" name="blocked_id" value="<?php echo $blocked['user_id']; ?>">
                                                    <button type="submit" class="friend-btn friend-btn-primary"><i class="fas fa-unlock"></i> Unblock</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
