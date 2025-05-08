<?php
require_once '../includes/db_connect.php'; // Include your database connection
require_once '../includes/functions.php'; // Include any necessary functions

// Prepare admin user data
$admin_username = 'Mellow'; // Replace with your desired username
$admin_email = 'mellow@zyntra.com'; // Replace with your desired email
$admin_password = 'ilovezyntra'; // Replace with your desired password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
$full_name = 'Mellow Admin'; // Store the full name in a variable

// SQL command to insert the admin user
$sql = "INSERT INTO users (username, email, password, full_name, role) 
        VALUES (?, ?, ?, ?, 'admin')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $admin_username, $admin_email, $hashed_password, $full_name); // Corrected to match the number of placeholders

if ($stmt->execute()) {
    echo "Admin user created successfully.";
} else {
    echo "Error creating admin user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>