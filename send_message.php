<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $message = clean_input($_POST['message']);

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $receiver_id, $message);
        $stmt->execute();
    }
}

// If AJAX, just exit
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    exit;
}

// Otherwise, redirect (for non-AJAX fallback)
header('Location: messages.php?friend_id=' . $receiver_id);
exit;

