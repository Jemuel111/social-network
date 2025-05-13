<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Redirect if not logged in
require_login();

// Redirect admin users to admin panel
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/admin_panel.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

// Handle post submission
$post_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_content'])) {
    $content = clean_input($_POST['post_content']);
    $image = '';

    // Check if upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['post_image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($filetype, $allowed)) {
            $new_filename = uniqid('img_', true) . '.' . $filetype;
            $upload_path = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
            } else {
                $post_message = "Failed to move uploaded file.";
            }
        } else {
            $post_message = "Invalid file type.";
        }
    }

    // Insert post if no file errors
    if (empty($post_message)) {
        $visibility = isset($_POST['post_visibility']) ? $_POST['post_visibility'] : 'public';
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image, visibility) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $content, $image, $visibility);

        if ($stmt->execute()) {
            $post_id = $conn->insert_id;
            
            // If specific friends were selected, add them to post_visibility_friends
            if ($visibility === 'specific' && !empty($_POST['selected_friends'])) {
                $friend_ids = explode(',', $_POST['selected_friends']);
                $stmt = $conn->prepare("INSERT INTO post_visibility_friends (post_id, friend_id) VALUES (?, ?)");
                foreach ($friend_ids as $friend_id) {
                    $stmt->bind_param("ii", $post_id, $friend_id);
                    $stmt->execute();
                }
            }
            
            $post_message = "Post created successfully!";
        } else {
            $post_message = "Error creating post: " . $conn->error;
        }
    }
}

// Get feed posts (from user and friends)
$posts = get_feed_posts($user_id);

// Get friend suggestions
$query = "SELECT u.* FROM users u 
          WHERE u.user_id != ? 
          AND u.role != 'admin'
          AND u.user_id NOT IN (
              SELECT friend_id FROM friendships 
              WHERE user_id = ? AND status = 'accepted'
              UNION
              SELECT user_id FROM friendships 
              WHERE friend_id = ? AND status = 'accepted'
          )
          ORDER BY RAND()
          LIMIT 4";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$friend_suggestions = [];
while ($row = $result->fetch_assoc()) {
    $friend_suggestions[] = $row;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zyntra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #5D479A;
            --secondary: #694786;
            --accent: #F187EA;
            --dark: #1A1347;
            --light: #A486B0;
            --lighter: #C8B6D8;
            --card-bg: #2A2056;
            --navbar-bg: #231C4D;
            --body-bg: #312768;
            --input-bg: #3C3273;
            --hover-bg: #3F3478;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--body-bg);
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
        
        
        .search-bar {
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            color: white;
            width: 100%;
            max-width: 300px;
        }
        
        .search-bar:focus {
            outline: none;
            background: var(--input-bg);
            border-color: var(--accent);
        }
        
        .search-bar::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Main Content */
        .main-content {
            width: 100%;
            padding: 0;
            margin-top: 0;
            position: relative;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            height: 100%;
        }

        .row {
            margin: 0;
            width: 100%;
            display: flex;
            align-items: flex-start;
            height: 100%;
        }

        /* Column Styles */
        .col-md-3, .col-md-6 {
            padding: 0 10px;
            margin-top: 0;
            height: 100%;
        }

        .col-md-3 {
            width: 25%;
        }

        .col-md-6 {
            width: 50%;
        }

        .scrollable-column {
            /* height: calc(100vh - 76px); */
            /* overflow-y: auto; */
            padding: 10px;
            padding-bottom: 20px; /* Add padding at bottom for better scrolling */
        }

        .scrollable-column::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-column::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .scrollable-column::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .container-fluid {
                padding: 0 10px;
            }

            .col-md-3 {
                width: 25%;
                padding: 0 5px;
            }

            .col-md-6 {
                width: 50%;
                padding: 0 5px;
            }
        }

        @media (max-width: 768px) {
            body {
                overflow-y: auto;
            }

            .main-content {
                height: auto;
                overflow: visible;
            }

            .scrollable-column {
                height: auto;
                overflow: visible;
            }
        }
        
        /* Card Styles */
        .card {
            background: var(--card-bg);
            border: 1px solid #3C3273;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
            overflow: visible; /* Allow overflow for friend-selector */
        }
        
        .card-header {
            background: #332761;
            border-bottom: 1px solid #3C3273;
            padding: 1rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-footer{
            border-top: none;
            padding: 0;
        }
        /* Profile Section */
        .profile-card {
            text-align: center;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 3px solid var(--accent);
            padding: 3px;
            background: var(--dark);
        }
        
        .profile-name {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .profile-username {
            color: var(--accent);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 1rem 0;
            padding: 0.5rem 0;
            border-top: 1px solid #3C3273;
            border-bottom: 1px solid #3C3273;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover {
            background: var(--hover-bg);
            color: var(--accent);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }
        
        /* Post Creation */
        .post-create {
            padding: 1rem;
            overflow: visible; /* Allow overflow for friend-selector */
        }
        
        .post-input {
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 10px;
            padding: 1rem;
            color: white;
            width: 100%;
            resize: none;
            margin-bottom: 1rem;
        }
        
        .post-input:focus {
            outline: none;
            border-color: var(--accent);
        }
        .post-input::placeholder {
            color: var(--lighter);
        }
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .post-attachments {
            display: flex;
            position: relative;
        }
        
        .attachment-btn {
            background: var(--input-bg);
            border: none;
            border-radius: 8px;
            color: white;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .attachment-btn:hover {
            background: var(--hover-bg);
            color: var(--accent);
        }
        
        .post-submit {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border: none;
            border-radius: 8px;
            color: white;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .post-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(241, 135, 234, 0.3);
        }
        
        /* Visibility Selector Styles */
        .visibility-select {
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 8px;
            color: white;
            padding: 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .visibility-select:hover {
            background: var(--hover-bg);
            border-color: var(--accent);
        }
        
        .visibility-select option {
            background: var(--card-bg);
            color: white;
        }
        
        /* Posts */
        .post {
            margin-bottom: 1.5rem;
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 0.75rem;
            border: 2px solid var(--accent);
        }
        
        .post-user {
            flex: 1;
        }
        
        .post-username {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .post-time {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .post-menu {
            color: rgba(255, 255, 255, 0.7);
            background: none;
            border: none;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        
        .post-menu:hover {
            color: white;
        }
        
        .post-content {
            margin-bottom: 1rem;
        }
        
        .post-image {
            width: 100%;
            border-radius: 10px;
            margin: 0.5rem 0 1rem;
            max-height: 500px;
            object-fit: cover;
        }
        
        .post-footer {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #3C3273;
            padding-top: 1rem;
        }
        
        .post-action {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            background: none;
            border: none;
            transition: all 0.3s ease;
        }
        
        .post-action:hover {
            color: var(--accent);
        }
        
        .post-action i {
            margin-right: 0.5rem;
        }
        
        /* Friend Suggestions */
        .friend-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .friend-item:hover {
            background: var(--hover-bg);
        }
        
        .friend-avatar {
            width: 50px !important;
            height: 50px !important;
            border-radius: 35% !important;
            margin-right: 0!important;
        }
        
        .friend-info {
            flex: 1;
        }
        
        .friend-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .friend-mutual {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .friend-add {
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 20px;
            color: white;
            padding: 0.25rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .friend-add:hover {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                position: sticky;
                top: 80px;
            }
        }
        
        @media (max-width: 767px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .search-bar {
                max-width: 100%;
            }
            
            .sidebar {
                position: static;
            }
        }
        
        /* Custom styles for existing elements */
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-color: rgba(40, 167, 69, 0.3);
            color: #fff;
        }
        
        .form-control {
            background-color: var(--input-bg);
            border: 1px solid #4A3F85;
            color: white;
        }
        
        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--accent);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border: none;
            border-radius: 8px;
            color: white;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(241, 135, 234, 0.3);
        }
        
        .custom-file-upload {
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 8px;
            color: white;
            padding: 0.5rem 1rem;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .custom-file-upload:hover {
            background: var(--hover-bg);
        }
        
        #post_image {
            display: none;
        }
        .footer-link {
            text-decoration: none;
            color: white;
            transition: text-decoration 0.2s ease-in-out;
        }

        .footer-link:hover {
            text-decoration: underline;
        }
        /* Hide left and right columns on mobile */
        @media (max-width: 768px) {
            .col-md-3 {
                display: none; /* Hide left and right columns */
            }

            .col-md-6 {
                flex: 1; /* Make the middle column take the full width */
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
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background-color: var(--hover-bg);
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

        .toggle-replies-btn {
            /* Keep your existing styles */
            background: none;
            border: none;
            color: var(--color-6);
            font-size: 0.9rem;
            cursor: pointer;
            margin: 5px 0 5px 35px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 10px;
            /* Add these new properties */
            display: inline-flex;    /* Better than inline-block */
            align-items: center;     /* Vertical centering */
            max-width: 200px;        /* Adjust as needed */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 4px 8px;        /* Add some padding */
            line-height: 1.2;        /* Better than 0 for readability */
        }

        .toggle-replies-btn:hover {
            background: var(--hover-bg);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(241, 135, 234, 0.15);
        }

        .toggle-replies-btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(241, 135, 234, 0.25);
        }
        .reply-input{
            margin-bottom: 10px;
        }
        .reply-form-inner .reply-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .friend-selector {
            position: absolute;
            background: var(--card-bg);
            border: 1px solid #4A3F85;
            border-radius: 8px;
            padding: 10px;
            margin-top: 5px;
            width: 300px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            right: 0;
        }

        .friend-search {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background: var(--input-bg);
            border: 1px solid #4A3F85;
            border-radius: 4px;
            color: white;
        }

        .friend-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .friend-item {
            display: flex;
            align-items: center;
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
        }

        .friend-item:hover {
            background: var(--hover-bg);
        }

        .friend-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .selected-friends {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }

        .selected-friend {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.9rem;
        }

        .selected-friend img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .remove-friend {
            margin-left: 5px;
            cursor: pointer;
            color: var(--accent);
        }

        .privacy-indicator {
            margin-left: 8px;
            color: var(--accent);
            font-size: 0.9em;
        }

        .privacy-indicator i {
            transition: transform 0.2s ease;
        }

        .privacy-indicator:hover i {
            transform: scale(1.2);
        }

        /* Update the visibility select options to include icons */
        .visibility-select option[value="public"]::before {
            content: "\f0ac"; /* globe icon */
            font-family: "Font Awesome 5 Free";
            margin-right: 8px;
        }

        .visibility-select option[value="friends"]::before {
            content: "\f500"; /* user-friends icon */
            font-family: "Font Awesome 5 Free";
            margin-right: 8px;
        }

        .visibility-select option[value="specific"]::before {
            content: "\f502"; /* user-lock icon */
            font-family: "Font Awesome 5 Free";
            margin-right: 8px;
        }

        /* Update the edit privacy modal select options */
        #edit-visibility-select option[value="public"]::before {
            content: "\f0ac";
            font-family: "Font Awesome 5 Free";
            margin-right: 8px;
        }

        #edit-visibility-select option[value="friends"]::before {
            content: "\f500";
            font-family: "Font Awesome 5 Free";
            margin-right: 8px;
        }

        #edit-visibility-select option[value="specific"]::before {
            content: "\f502";
            font-family: "Font Awesome 5 Free";
            margin-right: 8px;
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

    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid px-10 mx-0">
            <div class="row">
                <!-- Left Column - Profile -->
                <div class="col-md-3 scrollable-column">
                    <div class="sidebar">
                        <div class="card profile-card">
                            <div class="card-body">
                                <img src="assets/images/<?php echo $user['profile_pic']; ?>" alt="Profile" class="profile-avatar">
                                <h5 class="profile-name"><?php echo $user['full_name']; ?></h5>
                                <p class="profile-username">@<?php echo $user['username']; ?></p>
                                
                                <?php
                                // Get friends count
                                $stmt = $conn->prepare("
                                    SELECT COUNT(*) as friend_count 
                                    FROM friendships 
                                    WHERE (user_id = ? OR friend_id = ?) 
                                    AND status = 'accepted'
                                ");
                                $stmt->bind_param("ii", $user_id, $user_id);
                                $stmt->execute();
                                $friend_count = $stmt->get_result()->fetch_assoc()['friend_count'];

                                // Get posts count
                                $stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $post_count = $stmt->get_result()->fetch_assoc()['post_count'];

                                // Get total likes received
                                $stmt = $conn->prepare("
                                    SELECT COUNT(*) as like_count 
                                    FROM likes l 
                                    JOIN posts p ON l.post_id = p.post_id 
                                    WHERE p.user_id = ?
                                ");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $like_count = $stmt->get_result()->fetch_assoc()['like_count'];
                                ?>

                                <div class="profile-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $friend_count; ?></div>
                                        <div class="stat-label">Friends</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $post_count; ?></div>
                                        <div class="stat-label">Posts</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $like_count; ?></div>
                                        <div class="stat-label">Likes</div>
                                    </div>
                                </div>

                                <a href="profile.php" class="btn btn-primary w-100 mt-3">View Profile</a>
                            </div>
                        </div>

                        <!-- Friends Card -->
                        <div class="friends-card">
                            <div class="friends-card-header">
                                <h2>Friends <span class="friends-count"><?php echo $friend_count; ?><i class="fas fa-users" style="padding-left: 5px;"></i></span></h2>
                            </div>
                            <div class="friends-grid">
                                <?php
                                // Fetch friends for the current user
                                $friends_query = "SELECT u.*, f.status 
                                                FROM users u 
                                                JOIN friendships f ON (f.user_id = u.user_id OR f.friend_id = u.user_id)
                                                WHERE (f.user_id = ? OR f.friend_id = ?) 
                                                AND f.status = 'accepted'
                                                AND u.user_id != ?
                                                LIMIT 4";
                                $stmt = $conn->prepare($friends_query);
                                $stmt->bind_param("iii", $user_id, $user_id, $user_id);
                                $stmt->execute();
                                $friends_result = $stmt->get_result();

                                while ($friend = $friends_result->fetch_assoc()) {
                                    $friend_image = $friend['profile_pic'] ?: 'assets/images/default-avatar.png';
                                    ?>
                                    <a href="profile.php?id=<?php echo $friend['user_id']; ?>" class="friend-item" style="text-decoration:none; color:inherit;">
                                        <img src="assets/images/<?php echo $friend_image; ?>" alt="<?php echo $friend['username']; ?>" class="friend-avatar">
                                        <h3 class="friend-name"><?php echo $friend['username']; ?></h3>
                                    </a>
                                <?php } ?>
                            </div>
                            <a href="friends.php" class="view-all-friends">View All Friends</a>
                        </div>
                    </div>
                </div>
                
                <!-- Middle Column - Posts -->
                <div class="col-md-6 scrollable-column">
                    <!-- Create Post -->
                    <div class="card">
                        <div class="card-body post-create">
                            <?php if ($post_message): ?>
                                <div class="alert alert-success"><?php echo $post_message; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                                <textarea class="post-input" name="post_content" rows="3" placeholder="What's on your mind, <?php echo explode(' ', $user['full_name'])[0]; ?>?" required></textarea>
                                <div class="post-actions">
                                    <div class="post-attachments">
                                        <label for="post_image" class="attachment-btn">
                                            <i class="bi bi-image"></i>
                                        </label>
                                        <input type="file" id="post_image" name="post_image" accept="image/*">
                                        <select name="post_visibility" class="form-select visibility-select" id="visibility-select">
                                            <option value="public">Public</option>
                                            <option value="friends">Friends Only</option>
                                            <option value="specific">Specific Friends</option>
                                        </select>
                                        <div id="friend-selector" class="friend-selector" style="display: none;">
                                            <div class="selected-friends"></div>
                                            <input type="text" class="friend-search" placeholder="Search friends...">
                                            <div class="friend-list"></div>
                                        </div>
                                    </div>
                                    <button type="submit" class="post-submit">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Posts Feed -->
                    <?php if (empty($posts)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-newspaper fa-3x mb-3 text-white"></i>
                                <h5>No Posts Yet</h5>
                                <p class="text-white">Create your first post or add friends to see their posts here!</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="card post">
                                <div class="card-body">
                                    <?php if (!empty($post['shared_post_id'])): // This is a shared post ?>
                                        <?php
                                        // Fetch the original post and user
                                        $orig_stmt = $conn->prepare("SELECT p.*, u.username, u.full_name, u.profile_pic FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.post_id = ?");
                                        $orig_stmt->bind_param("i", $post['shared_post_id']);
                                        $orig_stmt->execute();
                                        $orig_result = $orig_stmt->get_result();
                                        $original = $orig_result->fetch_assoc();
                                        ?>
                                        <div class="post-header d-flex align-items-center">
                                            <img src="assets/images/<?php echo $post['profile_pic']; ?>" alt="Sharer" class="post-avatar">
                                            <div class="post-user flex-grow-1">
                                                <h6 class="post-username"><?php echo $post['full_name']; ?> <span style='font-weight:400;color:#F187EA;'>shared</span> <?php echo $original ? $original['full_name'] : '[Deleted]'; ?>'s post</h6>
                                                <p class="post-time">
                                                    @<?php echo $post['username']; ?> · 
                                                    <?php echo format_date($post['created_at']); ?>
                                                    <span class="privacy-indicator" title="<?php echo ucfirst($post['visibility']); ?>">
                                                        <?php if ($post['visibility'] === 'public'): ?>
                                                            <i class="fas fa-globe"></i>
                                                        <?php elseif ($post['visibility'] === 'friends'): ?>
                                                            <i class="fas fa-user-friends"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-user-lock"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="post-menu ms-auto">
                                                <button class="menu-trigger" type="button" tabindex="0"><i class="fas fa-ellipsis-h"></i></button>
                                                <div class="dropdown-menu">
                                                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                                        <button class="dropdown-item edit-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Edit Post</button>
                                                        <button class="dropdown-item edit-privacy-btn" data-post-id="<?php echo $post['post_id']; ?>" data-visibility="<?php echo $post['visibility']; ?>">Edit Privacy</button>
                                                        <button class="dropdown-item delete-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Delete Post</button>
                                                    <?php else: ?>
                                                        <button class="dropdown-item report-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Report Post</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="post-content" style="background:rgba(255,255,255,0.05);border-radius:10px;padding:10px;">
                                            <?php if ($original): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <img src="assets/images/<?php echo $original['profile_pic']; ?>" alt="Original User" class="post-avatar" style="width:32px;height:32px;margin-right:8px;">
                                                    <strong><?php echo $original['full_name']; ?></strong> <span style="color:#F187EA;">@<?php echo $original['username']; ?></span>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($original['content'])); ?></p>
                                                <?php if ($original['image']): ?>
                                                    <img src="assets/uploads/<?php echo $original['image']; ?>" alt="Post image" class="post-image">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p class="text-muted">[Original post deleted]</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: // Normal post ?>
                                        <div class="post-header d-flex align-items-center">
                                            <img src="assets/images/<?php echo $post['profile_pic']; ?>" alt="User" class="post-avatar">
                                            <div class="post-user flex-grow-1">
                                                <h6 class="post-username"><?php echo $post['full_name']; ?></h6>
                                                <p class="post-time">
                                                    @<?php echo $post['username']; ?> · 
                                                    <?php echo format_date($post['created_at']); ?>
                                                    <span class="privacy-indicator" title="<?php echo ucfirst($post['visibility']); ?>">
                                                        <?php if ($post['visibility'] === 'public'): ?>
                                                            <i class="fas fa-globe"></i>
                                                        <?php elseif ($post['visibility'] === 'friends'): ?>
                                                            <i class="fas fa-user-friends"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-user-lock"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="post-menu ms-auto">
                                                <button class="menu-trigger" type="button" tabindex="0"><i class="fas fa-ellipsis-h"></i></button>
                                                <div class="dropdown-menu">
                                                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                                        <button class="dropdown-item edit-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Edit Post</button>
                                                        <button class="dropdown-item edit-privacy-btn" data-post-id="<?php echo $post['post_id']; ?>" data-visibility="<?php echo $post['visibility']; ?>">Edit Privacy</button>
                                                        <button class="dropdown-item delete-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Delete Post</button>
                                                    <?php else: ?>
                                                        <button class="dropdown-item report-post-btn" data-post-id="<?php echo $post['post_id']; ?>">Report Post</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="post-content">
                                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                            <?php if ($post['image']): ?>
                                                <img src="assets/uploads/<?php echo $post['image']; ?>" alt="Post image" class="post-image">
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-footer">
                                        <button class="post-action <?php echo has_user_liked_post($user_id, $post['post_id']) ? 'text-primary' : ''; ?> like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                            <i class="<?php echo has_user_liked_post($user_id, $post['post_id']) ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                                            <span class="like-count"><?php echo $post['like_count']; ?></span>
                                        </button>
                                        <button class="post-action comment-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                            <i class="bi bi-chat-fill"></i>
                                            <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                        </button>
                                        <button class="share-btn <?php echo has_user_shared_post($_SESSION['user_id'], $post['post_id']) ? 'shared' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                                            <i class="fas fa-share"></i>
                                            <span class="share-count"><?php echo $post['share_count']; ?></span>
                                        </button>
                                    </div>
                                    <div class="card-footer">
                                        <div class="comment-section" id="comments-<?php echo $post['post_id']; ?>">
                                            <?php if ($post['comment_count'] > 0): ?>
                                                <div class="text-center mb-2">
                                                    <button class="btn btn-sm btn-link load-comments" data-post-id="<?php echo $post['post_id']; ?>">
                                                        <i class="fa-solid fa-caret-down"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <form class="comment-form mt-3" data-post-id="<?php echo $post['post_id']; ?>">
                                            <div class="input-group">
                                                <input type="text" class="form-control comment-input" placeholder="Write a comment..." required>
                                                <button class="btn btn-outline" type="submit"><i class="fas fa-paper-plane"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column - Friend Suggestions -->
                <div class="col-md-3 scrollable-column">
                    <div class="sidebar">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Friend Suggestions</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($friend_suggestions)): ?>
                                    <div class="text-center py-3">
                                        <p class="text-white mb-0">No suggestions at the moment</p>
                                    </div>
                                <?php else: ?>
                                    <div class="friends-suggestion-grid">
                                    <?php foreach (array_slice($friend_suggestions, 0, 4) as $suggestion): ?>
                                        <a href="profile.php?id=<?php echo $suggestion['user_id']; ?>" class="friend-item" style="text-decoration:none; color:inherit;">
                                            <img src="assets/images/<?php echo $suggestion['profile_pic']; ?>" alt="Friend" class="friend-avatar">
                                            <h3 class="friend-name"><?php echo $suggestion['username']; ?></h3>
                                            <form method="POST" action="add_friend.php" style="margin-top: 0.5rem;">
                                                <input type="hidden" name="friend_id" value="<?php echo $suggestion['user_id']; ?>">
                                                <button type="submit" class="friend-add" title="Add Friend"><i class="bi bi-person-plus-fill"></i></button>
                                            </form>
                                        </a>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-center mt-2">
                                    <a href="friends.php" style="color: var(--accent); text-decoration: none;">See More</a>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Zyntra © <?php echo date('Y'); ?></h6>
                                <small class="text-white">
                                <a href="legal-page.php" class="footer-link">Privacy</a>
                                <span style="color: white;">•</span>
                                <a href="legal-page.php" class="footer-link">Terms</a>
                                <span style="color: white;">•</span>
                                <a href="legal-page.php" class="footer-link">Help</a>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Post like functionality
        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.dataset.postId;
                const likeCount = this.querySelector('.like-count');
                const icon = this.querySelector('i');
                
                fetch('like_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${postId}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        likeCount.textContent = data.like_count;
                        if(data.action === 'like') {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            this.classList.add('text-primary');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            this.classList.remove('text-primary');
                        }
                    }
                });
            });
        });
        
        // Friend add button functionality
        document.querySelectorAll('.friend-add').forEach(button => {
            button.addEventListener('click', function() {
                if (this.innerHTML.includes('person-plus')) {
                    this.innerHTML = '<i class="bi bi-check"></i>';
                    this.style.background = 'var(--accent)';
                    this.style.borderColor = 'var(--accent)';
                }
            });
        });

        // Add this to your existing JavaScript
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
                            if (data.users.length > 0) {
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

        // Dropdown menu toggle for post-menu
        document.querySelectorAll('.post-menu .menu-trigger').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.querySelectorAll('.post-menu').forEach(function(menu) { menu.classList.remove('open'); });
                this.closest('.post-menu').classList.toggle('open');
            });
        });
        document.addEventListener('click', function() {
            document.querySelectorAll('.post-menu').forEach(function(menu) { menu.classList.remove('open'); });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const visibilitySelect = document.getElementById('visibility-select');
            const friendSelector = document.getElementById('friend-selector');
            const friendSearch = document.querySelector('.friend-search');
            const friendList = document.querySelector('.friend-list');
            const selectedFriends = document.querySelector('.selected-friends');
            let selectedFriendIds = new Set();

            // Show/hide friend selector based on visibility selection
            visibilitySelect.addEventListener('change', function() {
                if (this.value === 'specific') {
                    friendSelector.style.display = 'block';
                    loadFriends();
                } else {
                    friendSelector.style.display = 'none';
                }
            });

            // Hide friend selector when clicking outside
            document.addEventListener('mousedown', function(event) {
                if (
                    friendSelector.style.display === 'block' &&
                    !friendSelector.contains(event.target) &&
                    event.target !== visibilitySelect
                ) {
                    friendSelector.style.display = 'none';
                }
            });

            // Load friends for selection
            function loadFriends() {
                fetch('ajax/get_friends.php')
                    .then(response => response.json())
                    .then(friends => {
                        friendList.innerHTML = friends.map(friend => `
                            <div class="friend-item" data-id="${friend.user_id}">
                                <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                                <span>${friend.username}</span>
                            </div>
                        `).join('');

                        // Add click handlers
                        document.querySelectorAll('.friend-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const friendId = this.dataset.id;
                                if (!selectedFriendIds.has(friendId)) {
                                    selectedFriendIds.add(friendId);
                                    const friend = friends.find(f => f.user_id == friendId);
                                    selectedFriends.innerHTML += `
                                        <div class="selected-friend" data-id="${friendId}">
                                            <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                                            ${friend.username}
                                            <span class="remove-friend" onclick="removeFriend(${friendId})">&times;</span>
                                        </div>
                                    `;
                                }
                            });
                        });
                    });
            }

            // Search friends
            friendSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.friend-item').forEach(item => {
                    const username = item.querySelector('span').textContent.toLowerCase();
                    item.style.display = username.includes(searchTerm) ? 'flex' : 'none';
                });
            });

            // Remove friend from selection
            window.removeFriend = function(friendId) {
                selectedFriendIds.delete(friendId);
                document.querySelector(`.selected-friend[data-id="${friendId}"]`).remove();
            };

            // Add hidden input for selected friends before form submission
            document.querySelector('form').addEventListener('submit', function(e) {
                if (visibilitySelect.value === 'specific') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_friends';
                    input.value = Array.from(selectedFriendIds).join(',');
                    this.appendChild(input);
                }
            });
        });

        // Edit Privacy Modal
        const editPrivacyModal = `
            <div class="modal fade" id="editPrivacyModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Post Privacy</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <select class="form-select mb-3" id="edit-visibility-select">
                                <option value="public">Public</option>
                                <option value="friends">Friends Only</option>
                                <option value="specific">Specific Friends</option>
                            </select>
                            <div id="edit-friend-selector" class="friend-selector" style="display: none;">
                                <div class="selected-friends"></div>
                                <input type="text" class="friend-search" placeholder="Search friends...">
                                <div class="friend-list"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="save-privacy-btn">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', editPrivacyModal);

        // Edit Privacy functionality
        let currentPostId = null;
        let selectedFriendIds = new Set();

        document.querySelectorAll('.edit-privacy-btn').forEach(button => {
            button.addEventListener('click', function() {
                currentPostId = this.dataset.postId;
                const currentVisibility = this.dataset.visibility;
                
                const modal = new bootstrap.Modal(document.getElementById('editPrivacyModal'));
                const visibilitySelect = document.getElementById('edit-visibility-select');
                const friendSelector = document.getElementById('edit-friend-selector');
                
                visibilitySelect.value = currentVisibility;
                if (currentVisibility === 'specific') {
                    friendSelector.style.display = 'block';
                    loadFriendsForEdit(currentPostId);
                } else {
                    friendSelector.style.display = 'none';
                }
                
                modal.show();
            });
        });

        // Handle visibility change in edit modal
        document.getElementById('edit-visibility-select').addEventListener('change', function() {
            const friendSelector = document.getElementById('edit-friend-selector');
            if (this.value === 'specific') {
                friendSelector.style.display = 'block';
                loadFriendsForEdit(currentPostId);
            } else {
                friendSelector.style.display = 'none';
            }
        });

        // Load friends for edit modal
        function loadFriendsForEdit(postId) {
            fetch('ajax/get_friends.php')
                .then(response => response.json())
                .then(friends => {
                    const friendList = document.querySelector('#edit-friend-selector .friend-list');
                    const selectedFriends = document.querySelector('#edit-friend-selector .selected-friends');
                    
                    // Get currently selected friends for this post
                    fetch(`ajax/get_post_friends.php?post_id=${postId}`)
                        .then(response => response.json())
                        .then(selectedIds => {
                            selectedFriendIds = new Set(selectedIds);
                            
                            friendList.innerHTML = friends.map(friend => `
                                <div class="friend-item ${selectedFriendIds.has(friend.user_id) ? 'selected' : ''}" 
                                     data-id="${friend.user_id}">
                                    <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                                    <span>${friend.username}</span>
                                </div>
                            `).join('');

                            // Add selected friends to the selected-friends div
                            selectedFriends.innerHTML = friends
                                .filter(friend => selectedFriendIds.has(friend.user_id))
                                .map(friend => `
                                    <div class="selected-friend" data-id="${friend.user_id}">
                                        <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                                        ${friend.username}
                                        <span class="remove-friend" onclick="removeFriend(${friend.user_id})">&times;</span>
                                    </div>
                                `).join('');

                            // Add click handlers
                            document.querySelectorAll('#edit-friend-selector .friend-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    const friendId = this.dataset.id;
                                    if (!selectedFriendIds.has(friendId)) {
                                        selectedFriendIds.add(friendId);
                                        const friend = friends.find(f => f.user_id == friendId);
                                        selectedFriends.innerHTML += `
                                            <div class="selected-friend" data-id="${friendId}">
                                                <img src="assets/images/${friend.profile_pic}" alt="${friend.username}">
                                                ${friend.username}
                                                <span class="remove-friend" onclick="removeFriend(${friendId})">&times;</span>
                                            </div>
                                        `;
                                        this.classList.add('selected');
                                    }
                                });
                            });
                        });
                });
        }

        // Save privacy changes
        document.getElementById('save-privacy-btn').addEventListener('click', function() {
            const visibility = document.getElementById('edit-visibility-select').value;
            const data = {
                post_id: currentPostId,
                visibility: visibility
            };

            if (visibility === 'specific') {
                data.selected_friends = Array.from(selectedFriendIds).join(',');
            }

            fetch('ajax/update_post_visibility.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh to show updated visibility
                } else {
                    alert('Failed to update privacy settings');
                }
            });
        });
    </script>
</body>
</html>