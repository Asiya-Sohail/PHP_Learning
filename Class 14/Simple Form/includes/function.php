<?php
require_once 'config/database.php';

// Add new user
function addUser($name, $email, $phone, $address) {
    $conn = getConnection();
    
    $name = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, $email);
    $phone = mysqli_real_escape_string($conn, $phone);
    $address = mysqli_real_escape_string($conn, $address);
    
    $sql = "INSERT INTO users (name, email, phone, address) VALUES ('$name', '$email', '$phone', '$address')";
    
    if ($conn->query($sql) === TRUE) {
        $conn->close();
        return true;
    } else {
        $conn->close();
        return false;
    }
}

// Get all users
function getAllUsers() {
    $conn = getConnection();
    
    $sql = "SELECT * FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $users = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    return $users;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Check if email exists
function emailExists($email) {
    $conn = getConnection();
    
    $email = mysqli_real_escape_string($conn, $email);
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    $exists = $result->num_rows > 0;
    $conn->close();
    
    return $exists;
}
?>