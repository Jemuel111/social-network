<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if notification_id is provided
if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'No notification ID provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = (int)$_POST['notification_id'];

// Update the notification as read
$query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $notification_id, $user_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?> 