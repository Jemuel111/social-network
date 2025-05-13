<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get last username and full name change dates
$stmt = $conn->prepare("SELECT * FROM user_changes WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$changes = $stmt->get_result()->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password.";
                }
            } else {
                $error_message = "New passwords do not match.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }

    if (isset($_POST['change_email'])) {
        $new_email = clean_input($_POST['new_email']);
        
        // Check if email is already taken
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email is already taken.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_email, $user_id);
            if ($stmt->execute()) {
                $success_message = "Email changed successfully!";
            } else {
                $error_message = "Error changing email.";
            }
        }
    }

    if (isset($_POST['change_username'])) {
        $new_username = clean_input($_POST['new_username']);
        
        // Check if username is already taken
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Username is already taken.";
        } else {
            // Check if 30 days have passed since last username change
            $last_username_change = $changes['last_username_change'] ?? null;
            if ($last_username_change && (strtotime($last_username_change) > strtotime('-30 days'))) {
                $days_left = ceil((strtotime($last_username_change) + (30 * 24 * 60 * 60) - time()) / (24 * 60 * 60));
                $error_message = "You can change your username again in $days_left days.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_username, $user_id);
                if ($stmt->execute()) {
                    // Update last username change date
                    if ($changes) {
                        $stmt = $conn->prepare("UPDATE user_changes SET last_username_change = NOW() WHERE user_id = ?");
                    } else {
                        $stmt = $conn->prepare("INSERT INTO user_changes (user_id, last_username_change) VALUES (?, NOW())");
                    }
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $success_message = "Username changed successfully!";
                } else {
                    $error_message = "Error changing username.";
                }
            }
        }
    }

    if (isset($_POST['change_fullname'])) {
        $new_fullname = clean_input($_POST['new_fullname']);
        
        // Check if 7 days have passed since last full name change
        $last_fullname_change = $changes['last_fullname_change'] ?? null;
        if ($last_fullname_change && (strtotime($last_fullname_change) > strtotime('-7 days'))) {
            $days_left = ceil((strtotime($last_fullname_change) + (7 * 24 * 60 * 60) - time()) / (24 * 60 * 60));
            $error_message = "You can change your full name again in $days_left days.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_fullname, $user_id);
            if ($stmt->execute()) {
                // Update last full name change date
                if ($changes) {
                    $stmt = $conn->prepare("UPDATE user_changes SET last_fullname_change = NOW() WHERE user_id = ?");
                } else {
                    $stmt = $conn->prepare("INSERT INTO user_changes (user_id, last_fullname_change) VALUES (?, NOW())");
                }
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $success_message = "Full name changed successfully!";
            } else {
                $error_message = "Error changing full name.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--color-4);
            color: white;
            position: relative;
        }
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
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(60,45,87,0.08);
        }
        .settings-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--color-4);
            border-radius: 15px;
        }
        .settings-section h3 {
            color: var(--white);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        .form-label {
            color: var(--white);
        }
        .form-control {
            background: var(--color-1);
            border: 1px solid var(--color-2);
            color: var(--white);
        }
        .form-control:focus {
            background: var(--color-1);
            border-color: var(--color-6);
            color: var(--white);
            box-shadow: 0 0 0 0.2rem rgba(241, 135, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--color-5), var(--color-6));
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(241, 135, 234, 0.3);
        }
        .alert {
            border-radius: 10px;
        }
        .back-to-home-btn {
            background: linear-gradient(90deg, var(--color-5), var(--color-6));
            border: none;
            border-radius: 8px;
            color: white;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-to-home-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(241, 135, 234, 0.3);
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
    <div class="container">
        <div class="settings-container">
            <h2 class="text-center mb-4" style="color: var(--white);">Account Settings</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Change Password Section -->
            <div class="settings-section">
                <h3><i class="fas fa-lock me-2"></i>Change Password</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>

            <!-- Change Email Section -->
            <div class="settings-section">
                <h3><i class="fas fa-envelope me-2"></i>Change Email</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">New Email</label>
                        <input type="email" name="new_email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <button type="submit" name="change_email" class="btn btn-primary">Change Email</button>
                </form>
            </div>

            <!-- Change Username Section -->
            <div class="settings-section">
                <h3><i class="fas fa-user me-2"></i>Change Username</h3>
                <?php
                $last_username_change = $changes['last_username_change'] ?? null;
                $can_change_username = !$last_username_change || (strtotime($last_username_change) <= strtotime('-30 days'));
                ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">New Username</label>
                        <input type="text" name="new_username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        <?php if (!$can_change_username): ?>
                            <small class="text-warning">
                                You can change your username again in <?php echo ceil((strtotime($last_username_change) + (30 * 24 * 60 * 60) - time()) / (24 * 60 * 60)); ?> days.
                            </small>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="change_username" class="btn btn-primary" <?php echo !$can_change_username ? 'disabled' : ''; ?>>Change Username</button>
                </form>
            </div>

            <!-- Change Full Name Section -->
            <div class="settings-section">
                <h3><i class="fas fa-user-circle me-2"></i>Change Full Name</h3>
                <?php
                $last_fullname_change = $changes['last_fullname_change'] ?? null;
                $can_change_fullname = !$last_fullname_change || (strtotime($last_fullname_change) <= strtotime('-7 days'));
                ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">New Full Name</label>
                        <input type="text" name="new_fullname" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        <?php if (!$can_change_fullname): ?>
                            <small class="text-warning">
                                You can change your full name again in <?php echo ceil((strtotime($last_fullname_change) + (7 * 24 * 60 * 60) - time()) / (24 * 60 * 60)); ?> days.
                            </small>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="change_fullname" class="btn btn-primary" <?php echo !$can_change_fullname ? 'disabled' : ''; ?>>Change Full Name</button>
                </form>
            </div>
            <div class="back-button-container">
                <button class="back-to-home-btn" onclick="window.location.href='index.php'">
                    <i style="color: white;" class="bi bi-caret-left-fill"></i> Back to Home
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html> 