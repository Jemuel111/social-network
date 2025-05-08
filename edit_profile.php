<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if not logged in
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);
    $email = trim($_POST['email']);
    $location = trim($_POST['location']);

    // Handle profile picture upload
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "assets/images/";
        $new_filename = uniqid() . "_" . basename($_FILES["profile_pic"]["name"]);
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update user including profile picture
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ?, email = ?, location = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $full_name, $bio, $email, $location, $new_filename, $user_id);
        } else {
            $error = "Failed to upload profile picture.";
        }
    } else {
        // Update without changing profile picture
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ?, email = ?, location = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $full_name, $bio, $email, $location, $user_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        header("Location: profile.php");
        exit;
    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h3>Edit Profile</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm">
            <div class="mb-3 text-center">
                <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="rounded-circle img-thumbnail mb-2" width="150" alt="Profile Picture">
                <div>
                    <input type="file" name="profile_pic" class="form-control mt-2">
                    <small class="text-muted">Leave empty if you don't want to change picture.</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location']); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="profile.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
