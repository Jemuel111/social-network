<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo-white.png" alt="" class="img-fluid" style="max-height: 50px; margin-right: 15px;">
            <h5 class="brand-text">ZYNTRA</h5>
        </a>

        <!-- Centered icons for mobile -->
        <div class="d-lg-none d-flex justify-content-center w-100">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-home"></i>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'friends.php' ? 'active' : ''; ?>" href="friends.php">
                <i class="fas fa-users"></i>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                <i class="fas fa-envelope"></i>
                <?php
                $unread_messages = get_unread_messages_count($_SESSION['user_id']);
                if ($unread_messages > 0):
                ?>
                <span class="badge bg-danger rounded-pill" id="navbarMessageBadge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                <i class="fas fa-bell"></i>
                <?php
                $unread_count = get_unread_notifications_count($_SESSION['user_id']);
                if ($unread_count > 0):
                ?>
                <span class="badge bg-danger rounded-pill" id="navbarNotificationBadge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : ''; ?>" href="search.php">
                <i class="fas fa-search"></i>
            </a>
        </div>

        <!-- Desktop navbar -->
        <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'friends.php' ? 'active' : ''; ?>" href="friends.php">
                        <i class="fas fa-users"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                        <i class="fas fa-envelope"></i>
                        <?php
                        if ($unread_messages > 0):
                        ?>
                        <span class="badge bg-danger rounded-pill" id="navbarMessageBadge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php
                        if ($unread_count > 0):
                        ?>
                        <span class="badge bg-danger rounded-pill" id="navbarNotificationBadge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <div class="notification-list">
                            <?php
                            $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $notifications = $result->fetch_all(MYSQLI_ASSOC);
                            
                            if (empty($notifications)):
                            ?>
                            <li><div class="dropdown-item text-muted">No notifications</div></li>
                            <?php else:
                                foreach ($notifications as $notif):
                            ?>
                            <li>
                                <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" href="notifications.php">
                                    <div class="notification-content">
                                        <div class="notification-text"><?php echo htmlspecialchars($notif['message']); ?></div>
                                        <small class="text-muted"><?php echo format_date($notif['created_at']); ?></small>
                                    </div>
                                </a>
                            </li>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                    </ul>
                </li>
            </ul>

            <form action="search.php" method="GET" class="search-container">
                <div class="search">
                    <input class="form-control" type="search" name="q" id="searchInput" placeholder="Search users..." aria-label="Search" autocomplete="off">
                    <div id="searchResults" class="search-results-dropdown"></div>
                </div>
            </form>

            <div class="profile-container">
                <?php
                $user_id = $_SESSION['user_id'];
                $query = "SELECT profile_pic FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_pic = $result->fetch_assoc()['profile_pic'];
                ?>
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="assets/images/<?php echo $user_pic; ?>" class="rounded-circle me-2" width="40" height="40" alt="Profile">
                    <span class="d-none d-sm-inline"><?php echo $_SESSION['username']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin: 10px;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    font-weight: 700;
    font-size: 24px;
    letter-spacing: -0.5px;
}

.nav-link {
    font-weight: 500;
}

.dropdown-item:active {
    background-color: var(--primary-color);
}
    
.custom-navbar {
    background: linear-gradient(135deg, var(--color-4) 0%, var(--color-5) 100%);
    border-radius: 15px;
    padding: 12px 20px;
    box-shadow: 0 8px 20px rgba(26, 19, 71, 0.3);
    margin-bottom: 30px;
    position: sticky;
    top: 0;
    z-index: 1000;
}

/* Mobile Navigation */
@media (max-width: 992px) {
    .custom-navbar {
        margin-bottom: 15px;
    }

    .navbar-brand {
        margin: 0 auto;
    }

    .d-lg-none {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, var(--color-4) 0%, var(--color-5) 100%);
        padding: 10px 0;
        z-index: 1000;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 15px 15px 0 0;
    }

    .navbar .nav-link {
        padding: 8px 15px;
        color: white;
        font-size: 1.25rem;
    }
}

/* Add these styles to your existing CSS */
.search-container {
    position: relative;
}

.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--card-bg);
    border: 1px solid #3C3273;
    border-radius: 10px;
    margin-top: 5px;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #3C3273;
    cursor: pointer;
    transition: background-color 0.2s;
    text-decoration: none;
    color: white;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background-color: var(--hover-bg);
    text-decoration: none;
    color: white;
}

.search-result-item img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid var(--accent);
}

.search-result-info {
    flex: 1;
}

.search-result-name {
    font-weight: 600;
    margin-bottom: 2px;
}

.search-result-username {
    font-size: 0.85rem;
    color: var(--accent);
}

.no-results {
    padding: 15px;
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
}

/* Notification dropdown styles */
.notification-dropdown {
    background: var(--card-bg);
    border: 1px solid #3C3273;
    border-radius: 10px;
    padding: 0;
}

.notification-dropdown .dropdown-item {
    color: white;
    padding: 10px 15px;
}

.notification-dropdown .dropdown-item:hover {
    background-color: var(--hover-bg);
}

.notification-dropdown .notification-content {
    color: white;
}

.notification-dropdown .notification-content small {
    color: #888 !important;
}

.notification-dropdown .dropdown-header {
    color: #888;
    font-weight: 600;
}

.notification-dropdown .dropdown-divider {
    border-color: #3C3273;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`ajax/search_users.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.users && data.users.length > 0) {
                        searchResults.innerHTML = data.users.map(user => `
                            <a href="profile.php?id=${user.user_id}" class="search-result-item">
                                <img src="assets/images/${user.profile_pic}" alt="${user.full_name}">
                                <div class="search-result-info">
                                    <div class="search-result-name">${user.full_name}</div>
                                    <div class="search-result-username">@${user.username}</div>
                                </div>
                            </a>
                        `).join('');
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="no-results">No users found</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 300);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
        }
    });
});
</script>