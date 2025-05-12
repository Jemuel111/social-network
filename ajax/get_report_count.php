<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

$sql = "SELECT COUNT(*) as count FROM reported_posts WHERE status = 'pending'";
$result = $conn->query($sql);
$count = 0;
if ($row = $result->fetch_assoc()) {
    $count = (int)$row['count'];
}
echo json_encode(['count' => $count]); 