<?php
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

require_login();

if (isset($_GET['query'])) {
    $search_query = clean_input($_GET['query']);

    $stmt = $conn->prepare("
        SELECT user_id, full_name, username, profile_pic 
        FROM users 
        WHERE full_name LIKE CONCAT('%', ?, '%') OR username LIKE CONCAT('%', ?, '%')
        LIMIT 10
    ");
    $stmt->bind_param("ss", $search_query, $search_query);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
    exit;
}
?>