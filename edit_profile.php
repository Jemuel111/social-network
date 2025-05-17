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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Zyntra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile-style.css">
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
        .edit-profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .edit-profile-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .profile-pic-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            margin-bottom: 20px;
        }
        .custom-file-upload {
            display: inline-block;
            padding: 8px 20px;
            background: var(--color-5);
            color: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            
        }
        .custom-file-upload:hover {
            background: var(--hover-bg);
            color: var(--color-6);
        }
        .form-control {
            background: var(--input-bg);
            border: 2px solid var(--color-5);
            color: white;
            border-radius: 10px;
            padding: 12px;
        }
        .form-control:focus {
            background: var(--color-2);
            border-color: var(--color-6);
            color: white;
        }
        .form-control::placeholder {
            color: var(--light);
        }
        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .btn-save {
            background: var(--color-5);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(241, 135, 234, 0.3);
            color: var(--color-6);
        }
        .btn-cancel {
            background: var(--danger);
            border: 1px solid var(--accent);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover {
            background: var(--danger);
            transform: translateY(-2px);
            color: white;
        }
        .alert {
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            border-radius: 10px;
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

    <div class="edit-profile-container">
        <div class="edit-profile-card">
            <h3 class="text-center mb-4" style="color: white;">Edit Profile</h3>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-4">
                    <img src="assets/images/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default.jpg'); ?>" 
                         class="profile-pic-preview" 
                         alt="Profile Picture"
                         id="profile-preview">
                    <div class="mt-3">
                        <label for="profile_pic" class="custom-file-upload">
                            <i class="fas fa-camera me-2"></i>Change Photo
                        </label>
                        <input type="file" name="profile_pic" id="profile_pic" class="d-none" accept="image/*">
                        <small class="d-block text-white mt-2">Leave empty if you don't want to change picture.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="3" 
                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" 
                           value="<?php echo htmlspecialchars($user['location']); ?>"
                           placeholder="Where are you from?">
                </div>

                <div class="d-flex gap-3 justify-content-center">
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="profile.php" class="btn btn-cancel">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview profile picture before upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
