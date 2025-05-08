<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <div class="logo-container text-center mb-3">
            <img src="../assets/images/zyntra-logo.png" alt="Zyntra Logo" class="logo-img">
        </div>
        <div class="admin-profile text-center mb-3">
            <img src="../assets/images/default.png" alt="Admin Profile" class="profile-img">
        </div>
        <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
        <h5>Admin</h5>
        <a href="admin_panel.php">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="manage_posts.php">Posts</a>
        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>