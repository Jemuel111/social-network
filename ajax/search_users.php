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
    WHERE username LIKE ? OR full_name LIKE ?
    LIMIT 5
");
$stmt->bind_param("ss", $search_query, $search_query);
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