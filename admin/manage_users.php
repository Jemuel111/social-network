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

// Fetch all users *EXCLUDING* admins
// Assuming your users table has a 'role' column to distinguish admins from others.
// Adjust the WHERE clause if your role column name or value for non-admins is different.
$users_stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin'");
$users_stmt->execute();
$users = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

    <div class="content">
        <h2 class="text-white">Manage Users</h2>
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
                                    <form method="POST" action="delete_user.php" class="d-inline delete-user-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="button" class="btn btn-danger btn-sm delete-user-btn">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Search functionality for users table (Existing code)
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
                    // Check cell content for a match
                    if (cells[i].textContent.toLowerCase().includes(searchTerm)) {
                        rowMatches = true;
                        break;
                    }
                     // Additionally, check the profile pic alt text or src if needed for search (optional)
                     // For simplicity, we'll stick to text content for now.
                }

                row.style.display = rowMatches ? '' : 'none';
            });
        });
    }

    // Delete Confirmation functionality (NEW)
    function setupDeleteConfirmation(tableId, buttonClass) {
        const table = document.getElementById(tableId);

        if (!table) return;

        // Use event delegation on the table body for efficiency
        table.querySelector('tbody').addEventListener('click', function(event) {
            // Find the closest button with the target class that was clicked
            const clickedButton = event.target.closest(`.${buttonClass}`);

            // If a delete button was clicked
            if (clickedButton) {
                // Prevent the default form submission (though button type="button" already does this)
                event.preventDefault();

                // Find the parent form of the clicked button
                const form = clickedButton.closest('form');

                if (form) {
                     // Display the confirmation dialog
                     const confirmation = confirm("Are you sure you want to delete this user? This action cannot be undone.");

                     // If the user confirms, submit the form
                     if (confirmation) {
                         form.submit();
                     }
                     // If the user cancels, the function simply exits, preventing submission
                }
            }
        });
    }


    // Initialize search and delete confirmation on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('users-table', 'users-search-input');
        // Initialize the delete confirmation for buttons with class 'delete-user-btn' within the table
        setupDeleteConfirmation('users-table', 'delete-user-btn');
    });
    </script>

<?php require_once '../includes/admin_footer.php'; ?>