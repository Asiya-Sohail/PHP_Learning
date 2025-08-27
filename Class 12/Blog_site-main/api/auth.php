<?php
// api/auth.php - Authentication API Endpoint
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

startSession();

$database = new Database();
$pdo = $database->getConnection();

// Set content type for API responses
header('Content-Type: application/json');

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle different actions
try {
    switch ($action) {
        case 'logout':
            handleLogout();
            break;
        case 'change_password':
            handleChangePassword();
            break;
        case 'check_session':
            handleCheckSession();
            break;
        case 'update_profile':
            handleUpdateProfile();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}

function handleLogout() {
    global $auth;
    
    if (isLoggedIn()) {
        $result = $auth->logout();
        
        // Check if this is an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'redirect' => '../index.php'
            ]);
        } else {
            setFlashMessage('success', $result['message']);
            redirect('../index.php');
        }
    } else {
        // Check if this is an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse([
                'success' => true,
                'message' => 'Already logged out',
                'redirect' => '../index.php'
            ]);
        } else {
            redirect('../index.php');
        }
    }
}

function handleChangePassword() {
    global $auth;
    
    requireAuth();
    
    // Validate CSRF token
    if (!checkCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($currentPassword)) {
        $errors['current_password'] = 'Current password is required';
    }
    
    if (empty($newPassword)) {
        $errors['new_password'] = 'New password is required';
    } elseif (!validatePassword($newPassword)) {
        $errors['new_password'] = 'New password must be at least 6 characters long';
    }
    
    if (empty($confirmPassword)) {
        $errors['confirm_password'] = 'Please confirm your new password';
    } elseif ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the errors below',
            'errors' => $errors
        ], 400);
    }
    
    $userId = getCurrentUserId();
    $result = $auth->changePassword($userId, $currentPassword, $newPassword);
    
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }
}

function handleCheckSession() {
    global $auth;
    
    if (isLoggedIn() && $auth->validateSession()) {
        $user = getCurrentUser($GLOBALS['pdo']);
        jsonResponse([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ]
        ]);
    } else {
        jsonResponse([
            'success' => true,
            'logged_in' => false
        ]);
    }
}

function handleUpdateProfile() {
    global $auth, $pdo;
    
    requireAuth();
    
    // Validate CSRF token
    if (!checkCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentUserId = getCurrentUserId();
    
    $errors = [];
    
    // Validate input
    if (empty($fullName)) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($fullName) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters long';
    }
    
    if (!empty($email) && !validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // Check if email is already taken by another user
    if ($email) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $currentUserId]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email address is already taken';
        }
    }
    
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the errors below',
            'errors' => $errors
        ], 400);
    }
    
    $result = $auth->updateProfile($currentUserId, $fullName, $email);
    
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }
}
?>
