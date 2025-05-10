<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blocked_id'])) {
    $blocker_id = $_SESSION['user_id'];
    $blocked_id = (int)$_POST['blocked_id'];

    // Prevent blocking self
    if ($blocker_id === $blocked_id) {
        $_SESSION['error'] = "You cannot block yourself.";
        header("Location: friends.php");
        exit;
    }

    // Check if already blocked
    $check_stmt = $conn->prepare("SELECT 1 FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $check_stmt->bind_param("ii", $blocker_id, $blocked_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "User is already blocked.";
        header("Location: friends.php");
        exit;
    }

    // Check if the other user has blocked you
    $check_stmt = $conn->prepare("SELECT 1 FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $check_stmt->bind_param("ii", $blocked_id, $blocker_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "This user has already blocked you.";
        header("Location: friends.php");
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into blocked_users table
        $stmt = $conn->prepare("INSERT INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $blocker_id, $blocked_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to block user.");
        }

        // Remove friendship if exists
        $stmt = $conn->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $blocker_id, $blocked_id, $blocked_id, $blocker_id);
        $stmt->execute();

        // Remove any pending friend requests
        $stmt = $conn->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $blocker_id, $blocked_id, $blocked_id, $blocker_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "User has been blocked successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}

header("Location: friends.php");
exit;