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
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                <i class="fas fa-bell"></i>
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
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                        <i class="fas fa-bell"></i>
                    </a>
                </li>
            </ul>

            <form action="search.php" method="GET" class="search-container">
                <div class="search">
                    <input class="form-control" type="search" name="q" placeholder="Search users..." aria-label="Search">
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
                    <img src="assets/images/<?php echo $user_pic; ?>" class="rounded-circle me-2" width="30" height="30" alt="Profile">
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