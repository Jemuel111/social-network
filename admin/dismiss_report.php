<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = (int)$_POST['report_id'];
    $stmt = $conn->prepare("UPDATE reported_posts SET status = 'dismissed' WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
}
header('Location: manage_reports.php');
exit; 