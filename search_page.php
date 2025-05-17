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
        :root {
            --color-1: #3C2D57;  /* Dark purple */
            --color-2: #694786;  /* Medium purple */
            --color-3: #A486B0;  /* Light purple/lavender */
            --color-4: #1A1347;  /* Deep purple/indigo */
            --color-5: #5D479A;  /* Bright purple */
            --color-6: #F187EA;  /* Pink/magenta */
            --card-bg: #2A2056;
            --white: #FFFFFF;
            --success: #42B72A;
            --danger: #FF3B30;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--color-4);
            color: white;
            position: relative;
        }
        .custom-container{
            margin: 0 0 0 0;
        }
        /* Background Elements */
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            z-index: -2;
        }
        
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: -1;
            opacity: 0.3;
        }
        
        .blob-1 {
            width: 600px;
            height: 600px;
            background: #8A2BE2;
            top: -200px;
            left: -100px;
        }
        
        .blob-2 {
            width: 500px;
            height: 500px;
            background: #9370DB;
            bottom: -150px;
            right: -100px;
        }
        
        .blob-3 {
            width: 400px;
            height: 400px;
            background: #DA70D6;
            top: 40%;
            left: 60%;
        }
        
        .grid-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            z-index: 0;
            opacity: 0.2;
        }
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
            background: var(--color-4);
            border-color: var(--color-6);
        }
        .search-input::placeholder{
            color: white;
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
    <!-- Background Elements -->
    <div class="background-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="grid-bg"></div>
    </div>
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
                <input type="text" 
                       id="mobileSearchInput"
                       name="q"
                       class="search-input" 
                       placeholder="Search users..." 
                       autocomplete="off"
                       value="<?php echo htmlspecialchars($search_term); ?>">
            </form>
        </div>
        <div id="mobileSearchResults">
            <?php if (!empty($search_term)): ?>
                <?php if (empty($search_results)): ?>
                    <div class="no-results"><i class="fas fa-search fa-2x mb-3"></i><h4>No users found</h4><p>Try searching with a different term</p></div>
                <?php else: ?>
                    <?php foreach ($search_results as $user): ?>
                        <a href="profile.php?username=<?php echo htmlspecialchars($user['username']); ?>" class="user-card">
                            <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default.jpg'); ?>" alt="Profile" class="user-avatar">
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
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('mobileSearchInput');
        const searchResults = document.getElementById('mobileSearchResults');
        let searchTimeout;

        // Only use AJAX if not loaded from a GET search
        if (!searchInput.value) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 2) {
                    searchResults.innerHTML = '';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`ajax/search_users.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.users && data.users.length > 0) {
                                searchResults.innerHTML = data.users.map(user => `
                                    <a href="profile.php?username=${encodeURIComponent(user.username)}" class="user-card">
                                        <img src="assets/images/${user.profile_pic || 'default.jpg'}" alt="Profile" class="user-avatar">
                                        <div class="user-info">
                                            <div class="user-name">${user.full_name}</div>
                                            <div class="user-username">@${user.username}</div>
                                        </div>
                                    </a>
                                `).join('');
                            } else {
                                searchResults.innerHTML = '<div class="no-results"><i class="fas fa-search fa-2x mb-3"></i><h4>No users found</h4><p>Try searching with a different term</p></div>';
                            }
                        })
                        .catch(error => {
                            searchResults.innerHTML = '<div class="no-results">Error searching users</div>';
                        });
                }, 300);
            });
        }
    });
    </script>
</body>
</html> 