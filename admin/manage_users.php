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

    <div class="content">
        <h2>Manage Users</h2>
        <div class="table-container">
            <div class="table-header">
                <div class="input-group">
                    <i class="fas fa-search"></i>
                    <input type="search" id="users-search-input" placeholder="Search users...">
                </div>
            </div>
            <div class="table-body">
                <table id="users-table">
                    <thead>
                        <tr>
                            <th>User ID <i class="fas fa-arrow-down icon-arrow"></i></th>
                            <th>Full Name <i class="fas fa-arrow-down icon-arrow"></i></th>
                            <th>Username <i class="fas fa-arrow-down icon-arrow"></i></th>
                            <th>Email <i class="fas fa-arrow-down icon-arrow"></i></th>
                            <th>Status <i class="fas fa-arrow-down icon-arrow"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td>
                                    <?php 
                                    $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.jpg';
                                    ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <p class="status <?php echo $user['banned'] ? 'cancelled' : 'delivered'; ?>">
                                        <?php if ($user['banned']): ?>
                                            <i class="fas fa-ban"></i> Banned
                                        <?php else: ?>
                                            <i class="fas fa-check-circle"></i> Active
                                        <?php endif; ?>
                                    </p>
                                </td>
                                <td>
                                    <form method="POST" action="delete_user.php" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    <?php if (!$user['banned']): ?>
                                        <form method="POST" action="ban_user.php" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="unban_user.php" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Search functionality for users table
    function setupTableSearch(tableId, searchInputId) {
        const searchInput = document.getElementById(searchInputId);
        const table = document.getElementById(tableId);
        
        if (!searchInput || !table) return;
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowMatches = false;
                
                // Skip the last cell (actions column)
                for (let i = 0; i < cells.length - 1; i++) {
                    if (cells[i].textContent.toLowerCase().includes(searchTerm)) {
                        rowMatches = true;
                        break;
                    }
                }
                
                row.style.display = rowMatches ? '' : 'none';
            });
        });
    }

    // Initialize search for users table
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('users-table', 'users-search-input');
    });
    </script>

    <?php require_once '../includes/admin_footer.php'; ?>