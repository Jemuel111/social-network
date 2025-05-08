<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

require_login();

// Check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['post_id']);

    // Delete post from the database
    $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();

    header("Location: manage_posts.php");
    exit;
}
?>