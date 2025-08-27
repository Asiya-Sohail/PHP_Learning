<?php
header('Content-Type: application/json');
require_once '../includes/function.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validate input
    $errors = validateUserData($name, $email, $phone, $address);
    
    if (empty($errors)) {
        $userId = addUser($name, $email, $phone, $address);
        
        if ($userId) {
            // Get the newly added user
            $newUser = getUserById($userId);
            
            $response = array(
                'success' => true,
                'message' => 'User added successfully!',
                'user' => $newUser
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'Error adding user. Please try again.'
            );
        }
    } else {
        $response = array(
            'success' => false,
            'message' => implode(', ', $errors)
        );
    }
} else {
    $response = array(
        'success' => false,
        'message' => 'Invalid request method'
    );
}

echo json_encode($response);
?>