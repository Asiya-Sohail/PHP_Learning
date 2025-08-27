<?php
// includes/functions.php - Utility Functions
require_once __DIR__ . '/../config/database.php';

// Start session if not already started
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT id, username, email, full_name, profile_image, created_at, last_login 
            FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Sanitize output
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Validate username
function validateUsername($username) {
    if (empty($username)) return false;
    if (strlen($username) < 3 || strlen($username) > 20) return false;
    return preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password
function validatePassword($password) {
    if (empty($password)) return false;
    if (strlen($password) < 6) return false;
    return true;
}

// Generate secure token
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Log user activity
function logUserActivity($userId, $action, $details = '', $pdo = null) {
    try {
        if (!$pdo) {
            $database = new Database();
            $pdo = $database->getConnection();
        }
        
        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

// Format date for display
function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

// Format time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

// Generate excerpt from text
function generateExcerpt($text, $length = 150) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $excerpt = substr($text, 0, $length);
    $lastSpace = strrpos($excerpt, ' ');
    if ($lastSpace !== false) {
        $excerpt = substr($excerpt, 0, $lastSpace);
    }
    
    return $excerpt . '...';
}

// Check CSRF token
function checkCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token
function generateCSRFToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

// Flash messages
function setFlashMessage($type, $message) {
    startSession();
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages() {
    startSession();
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// Require authentication
function requireAuth() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to access this page.');
        redirect('pages/login.php');
    }
}

// Check if user owns content
function checkOwnership($userId, $contentUserId) {
    return $userId == $contentUserId;
}

// Upload file helper
function uploadFile($file, $uploadDir = 'assets/images/uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Check file size (5MB max)
    if ($fileSize > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large (max 5MB)'];
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '.' . $fileType;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($fileTmp, $targetPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $targetPath];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Pagination helper
function paginate($currentPage, $totalRecords, $recordsPerPage = 10) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Get base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    return $protocol . '://' . $host . ($path === '/' ? '' : $path);
}
?>
