<?php
// require_once '../config/database.php';
require_once __DIR__ . '/../config/database.php';

// Add new user
function addUser($name, $email, $phone, $address) {
    $conn = getConnection();
    
    $name = mysqli_real_escape_string($conn, $name);
    $email = mysqli_real_escape_string($conn, $email);
    $phone = mysqli_real_escape_string($conn, $phone);
    $address = mysqli_real_escape_string($conn, $address);
    
    $sql = "INSERT INTO users (name, email, phone, address) VALUES ('$name', '$email', '$phone', '$address')";
    
    if ($conn->query($sql) === TRUE) {
        $user_id = $conn->insert_id;
        $conn->close();
        return $user_id;
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

// Get single user by ID
function getUserById($id) {
    $conn = getConnection();
    
    $id = (int)$id;
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = $conn->query($sql);
    
    $user = null;
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    
    $conn->close();
    return $user;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Check if email exists
function emailExists($email, $excludeId = null) {
    $conn = getConnection();
    
    $email = mysqli_real_escape_string($conn, $email);
    $sql = "SELECT id FROM users WHERE email = '$email'";
    
    if ($excludeId) {
        $excludeId = (int)$excludeId;
        $sql .= " AND id != $excludeId";
    }
    
    $result = $conn->query($sql);
    
    $exists = $result->num_rows > 0;
    $conn->close();
    
    return $exists;
}

// Validate user data
function validateUserData($name, $email, $phone, $address, $excludeId = null) {
    $errors = array();
    
    if (empty(trim($name))) {
        $errors[] = "Name is required";
    }
    
    if (empty(trim($email))) {
        $errors[] = "Email is required";
    } elseif (!validateEmail($email)) {
        $errors[] = "Invalid email format";
    } elseif (emailExists($email, $excludeId)) {
        $errors[] = "Email already exists";
    }
    
    if (empty(trim($phone))) {
        $errors[] = "Phone is required";
    }
    
    if (empty(trim($address))) {
        $errors[] = "Address is required";
    }
    
    return $errors;
}
?>