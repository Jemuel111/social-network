<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$search_results = [];
$search_term = '';

if (isset($_GET['q'])) {
    $search_term = clean_input($_GET['q']);
    
    // Search for users
    $stmt = $conn->prepare("
        SELECT user_id, username, full_name, profile_pic, bio 
        FROM users 
        WHERE (username LIKE ? OR full_name LIKE ?) 
        AND user_id != ? 
        AND banned = 0
        LIMIT 20
    ");
    
    $search_param = "%{$search_term}%";
    $stmt->bind_param("ssi", $search_param, $search_param, $_SESSION['user_id']);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Zyntra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-page-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 15px;
        }
        .search-header {
            position: sticky;
            top: 0;
            background: var(--card-bg);
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            z-index: 100;
        }
        .search-input-container {
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            background: var(--input-bg);
            border: 1px solid var(--accent);
            border-radius: 25px;
            color: white;
            font-size: 1rem;
        }
        .search-input:focus {
            outline: none;
            background: var(--hover-bg);
            border-color: var(--accent);
            box-shadow: 0 0 0 0.25rem rgba(241, 135, 234, 0.25);
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
        }
        .user-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
            text-decoration: none;
            color: white;
        }
        .user-card:hover {
            transform: translateY(-2px);
            color: white;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        .user-username {
            color: var(--light);
            font-size: 0.9rem;
        }
        .user-bio {
            color: var(--light);
            font-size: 0.85rem;
            margin-top: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .no-results {
            text-align: center;
            color: var(--light);
            padding: 40px 20px;
        }
        .back-btn {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 1.2rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>

    <div class="search-page-container">
        <div class="search-header">
            <form action="search_page.php" method="GET" class="search-input-container">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       name="q" 
                       class="search-input" 
                       placeholder="Search users..." 
                       value="<?php echo htmlspecialchars($search_term); ?>"
                       autocomplete="off">
            </form>
        </div>

        <?php if (!empty($search_term)): ?>
            <?php if (empty($search_results)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-2x mb-3"></i>
                    <h4>No users found</h4>
                    <p>Try searching with a different term</p>
                </div>
            <?php else: ?>
                <?php foreach ($search_results as $user): ?>
                    <a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>" class="user-card">
                        <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default.jpg'); ?>" 
                             alt="Profile" 
                             class="user-avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                            <?php if (!empty($user['bio'])): ?>
                                <div class="user-bio"><?php echo htmlspecialchars($user['bio']); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html> 