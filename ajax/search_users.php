<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get search query
$query = isset($_GET['q']) ? clean_input($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['users' => []]);
    exit;
}

// Search for users
$search_query = "%$query%";
$stmt = $conn->prepare("
    SELECT user_id, username, full_name, profile_pic 
    FROM users 
    WHERE (username LIKE ? OR full_name LIKE ?)
    AND user_id != ?
    AND role != 'admin'
    AND user_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = ?)
    AND user_id NOT IN (SELECT blocker_id FROM blocked_users WHERE blocked_id = ?)
    LIMIT 5
");
$stmt->bind_param("sssii", $search_query, $search_query, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'user_id' => $row['user_id'],
        'username' => $row['username'],
        'full_name' => $row['full_name'],
        'profile_pic' => $row['profile_pic']
    ];
}

echo json_encode(['users' => $users]);
?> 