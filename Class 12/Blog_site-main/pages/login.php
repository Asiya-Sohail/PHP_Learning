<?php
// pages/login.php - User Login Page
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

startSession();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$username_email = '';
$remember_me = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate input
    if (empty($username_email)) {
        $errors['username_email'] = 'Username or email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // Attempt login if no validation errors
    if (empty($errors)) {
        $result = $auth->login($username_email, $password, $remember_me);
        
        if ($result['success']) {
            error_log("Login successful for user: " . $username_email);
    error_log("Session ID: " . session_id());
    error_log("Session data: " . print_r($_SESSION, true));
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                jsonResponse([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect' => 'dashboard.php'
                ]);
            } else {
                setFlashMessage('success', $result['message']);
                redirect('dashboard.php');
            }
        } else {
            $errors['general'] = $result['message'];
            
            // AJAX response for errors
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                jsonResponse([
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $errors
                ], 400);
            }
        }
    } else {
        // AJAX response for validation errors
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse([
                'success' => false,
                'message' => 'Please correct the errors below',
                'errors' => $errors
            ], 400);
        }
    }
}

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BlogCMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">BlogCMS</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div style="max-width: 450px; margin: 0 auto;">
                <!-- Flash Messages -->
                <?php foreach ($flashMessages as $message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo sanitize($message['message']); ?>
                    </div>
                <?php endforeach; ?>

                <!-- Login Card -->
                <div class="card fade-in">
                    <div class="card-header text-center">
                        <h2 class="card-title">Welcome Back</h2>
                        <p style="color: #6c757d; margin: 0;">Sign in to your account to continue</p>
                    </div>
                    <div class="card-body">
                        <!-- General Error -->
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-error">
                                <?php echo sanitize($errors['general']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" class="login-form" data-ajax="true">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-group">
                                <label for="username_email" class="form-label">Username or Email</label>
                                <input 
                                    type="text" 
                                    id="username_email" 
                                    name="username_email" 
                                    class="form-control <?php echo isset($errors['username_email']) ? 'error' : ''; ?>"
                                    value="<?php echo sanitize($username_email); ?>"
                                    placeholder="Enter your username or email"
                                    required
                                    autocomplete="username"
                                >
                                <?php if (isset($errors['username_email'])): ?>
                                    <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                        <?php echo sanitize($errors['username_email']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                                    placeholder="Enter your password"
                                    
                                    autocomplete="current-password"
                                >
                                <?php if (isset($errors['password'])): ?>
                                    <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                        <?php echo sanitize($errors['password']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="remember_me" 
                                        <?php echo $remember_me ? 'checked' : ''; ?>
                                    >
                                    Remember me for 30 days
                                </label>
                            </div>

                            <button 
                                type="submit" 
                                class="btn btn-primary btn-block btn-lg"
                                data-original-text="Sign In"
                            >
                                Sign In
                            </button>
                        </form>

                        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #dee2e6;">
                            <p style="margin: 0;">
                                Don't have an account? 
                                <a href="signup.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                                    Sign up here
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Demo Credentials (for testing) -->
                <div class="card" style="margin-top: 1rem;">
                    <div class="card-body">
                        <h6 style="color: #667eea; margin-bottom: 0.5rem;">Demo Credentials</h6>
                        <p style="margin: 0; font-size: 0.875rem; color: #6c757d;">
                            <strong>Admin:</strong> admin@example.com / password123<br>
                            <strong>User:</strong> user@example.com / password123
                        </p>
                        <button 
                            class="btn btn-secondary btn-sm" 
                            style="margin-top: 0.5rem;"
                            onclick="fillDemoCredentials()"
                        >
                            Use Demo Credentials
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="background: #2c3e50; color: white; padding: 2rem 0; margin-top: 4rem;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> BlogCMS. Secure login system with session management.</p>
        </div>
    </footer>

    <script src="../assets/js/app.js"></script>
    <script>
        // Demo credentials helper function
        function fillDemoCredentials() {
            document.getElementById('username_email').value = 'admin@example.com';
            document.getElementById('password').value = 'password123';
        }

        // Focus on first input when page loads
        document.addEventListener('DOMContentLoaded', () => {
            const firstInput = document.getElementById('username_email');
            if (firstInput && !firstInput.value) {
                firstInput.focus();
            }
        });

        // Real-time validation
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('.login-form');
            const inputs = form.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    if (input.value.trim()) {
                        input.classList.remove('error');
                        const errorMsg = input.parentNode.querySelector('.field-error');
                        if (errorMsg) {
                            errorMsg.remove();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
