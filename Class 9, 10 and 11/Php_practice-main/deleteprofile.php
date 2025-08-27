<?php
session_start();
require_once 'config.php'; // Assumes $conn is defined here

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete user from database
$sql = "UPDATE users SET status = '0' WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Set success message, destroy session, and redirect
    $_SESSION['statusp'] = "Profile deleted successfully";
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
} else {
    // Set failure message and redirect
    $_SESSION['statusd'] = "Profile deletion failed";
    header("Location: editprofile.php");
    exit();
}

$stmt->close();
$conn->close();
?>
