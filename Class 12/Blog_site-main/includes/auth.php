<?php
// includes/auth.php - Authentication Functions

require_once '../config/database.php';

class Auth {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Register new user
    public function register($username, $email, $password, $fullName) {
        try {
            // Validate input
            if (!$this->validateRegistrationData($username, $email, $password, $fullName)) {
                return ['success' => false, 'message' => 'Invalid input data'];
            }
            
            // Check if user already exists
            if ($this->userExists($username, $email)) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username, $email, $hashedPassword, $fullName]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Log activity
            logUserActivity($userId, 'user_registered', 'User registered successfully');
            
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    // Login user
    public function login($usernameOrEmail, $password, $rememberMe = false) {
        try {
            // Get user by username or email
            $user = $this->getUserByUsernameOrEmail($usernameOrEmail);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is active
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated'];
            }
            
             // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid passwordsss'];
            }
            
            // Create session
            $this->createUserSession($user['id'], $rememberMe);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log activity
            logUserActivity($user['id'], 'user_login', 'User logged in successfully');
            
            return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    // Logout user
    public function logout() {
        try {
            if (isset($_SESSION['user_id'])) {
                // Log activity
                logUserActivity($_SESSION['user_id'], 'user_logout', 'User logged out');
                
                // Invalidate session token
                if (isset($_SESSION['session_token'])) {
                    $this->invalidateSession($_SESSION['session_token']);
                }
                
                // Clear remember me cookie
                if (isset($_COOKIE['remember_token'])) {
                    setcookie('remember_token', '', time() - 3600, '/');
                    $this->clearRememberToken($_SESSION['user_id']);
                }
            }
            
            // Destroy session
            session_destroy();
            
            return ['success' => true, 'message' => 'Logged out successfully'];
            
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Logout failed'];
        }
    }
    
    // Validate registration data
    private function validateRegistrationData($username, $email, $password, $fullName) {
        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            return false;
        }
        
        if (!validateUsername($username)) {
            return false;
        }
        
        if (!validateEmail($email)) {
            return false;
        }
        
        if (!validatePassword($password)) {
            return false;
        }
        
        if (strlen($fullName) < 2 || strlen($fullName) > 100) {
            return false;
        }
        
        return true;
    }
    
    // Check if user exists
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username, $email]);
        return $stmt->fetch() !== false;
    }
    
    // Get user by username or email
    private function getUserByUsernameOrEmail($usernameOrEmail) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        return $stmt->fetch();
    }
    
    // Create user session
    private function createUserSession($userId, $rememberMe = false) {
        $sessionToken = generateSecureToken();
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store session in database
        $sql = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $sessionToken,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['login_time'] = time();
        
        // Handle remember me
        if ($rememberMe) {
            $rememberToken = generateSecureToken();
            $this->setRememberToken($userId, $rememberToken);
            setcookie('remember_token', $rememberToken, time() + (30 * 24 * 3600), '/'); // 30 days
        }
    }
    
    // Update last login
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    // Invalidate session
    private function invalidateSession($sessionToken) {
        $sql = "UPDATE user_sessions SET is_active = 0 WHERE session_token = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sessionToken]);
    }
    
    // Set remember token
    private function setRememberToken($userId, $token) {
        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token, $userId]);
    }
    
    // Clear remember token
    private function clearRememberToken($userId) {
        $sql = "UPDATE users SET remember_token = NULL WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    // Validate session
    public function validateSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        $sql = "SELECT * FROM user_sessions 
                WHERE session_token = ? AND user_id = ? AND is_active = 1 AND expires_at > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$_SESSION['session_token'], $_SESSION['user_id']]);
        
        return $stmt->fetch() !== false;
    }
    
    // Handle remember me login
    public function handleRememberMe() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $sql = "SELECT id, username, email, full_name FROM users 
                WHERE remember_token = ? AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$_COOKIE['remember_token']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $this->createUserSession($user['id']);
            return true;
        }
        
        // Invalid remember token, clear cookie
        setcookie('remember_token', '', time() - 3600, '/');
        return false;
    }
    
    // Change password
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            if (!validatePassword($newPassword)) {
                return ['success' => false, 'message' => 'New password does not meet requirements'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$hashedPassword, $userId]);
            
            // Log activity
            logUserActivity($userId, 'password_changed', 'Password changed successfully');
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    // Get user profile
    public function getUserProfile($userId) {
        $sql = "SELECT id, username, email, full_name, profile_image, created_at, last_login 
                FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    // Update user profile
    public function updateProfile($userId, $fullName, $email = null) {
        try {
            $sql = "UPDATE users SET full_name = ?";
            $params = [$fullName];
            
            if ($email && validateEmail($email)) {
                $sql .= ", email = ?";
                $params[] = $email;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log activity
            logUserActivity($userId, 'profile_updated', 'Profile updated successfully');
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
}

// Initialize auth instance
$auth = new Auth($pdo);

// Auto-login with remember me token
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $auth->handleRememberMe();
}

// Validate current session
// if (isLoggedIn() && !$auth->validateSession()) {
//     session_destroy();
//     if (isset($_COOKIE['remember_token'])) {
//         setcookie('remember_token', '', time() - 3600, '/');
//     }
//     redirect('login.php');
// }
?>