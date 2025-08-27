<?php
// pages/signup.php - User Registration Page
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

startSession();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'password' => '',
    'confirm_password' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Validate input
    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required';
    } elseif (!validateUsername($formData['username'])) {
        $errors['username'] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!validateEmail($formData['email'])) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($formData['full_name']) < 2) {
        $errors['full_name'] = 'Full name must be at least 2 characters long';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (!validatePassword($formData['password'])) {
        $errors['password'] = 'Password must be at least 6 characters long';
    }
    
    if (empty($formData['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Terms acceptance
    if (!isset($_POST['accept_terms'])) {
        $errors['accept_terms'] = 'You must accept the terms and conditions';
    }
    
    // Attempt registration if no validation errors
    if (empty($errors)) {
        $result = $auth->register(
            $formData['username'],
            $formData['email'],
            $formData['password'],
            $formData['full_name']
        );
        
        if ($result['success']) {
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                jsonResponse([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect' => 'login.php'
                ]);
            } else {
                setFlashMessage('success', $result['message'] . ' Please log in to continue.');
                redirect('login.php');
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
    <title>Sign Up - BlogCMS</title>
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
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div style="max-width: 500px; margin: 0 auto;">
                <!-- Flash Messages -->
                <?php foreach ($flashMessages as $message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo sanitize($message['message']); ?>
                    </div>
                <?php endforeach; ?>

                <!-- Registration Card -->
                <div class="card fade-in">
                    <div class="card-header text-center">
                        <h2 class="card-title">Create Account</h2>
                        <p style="color: #6c757d; margin: 0;">Join BlogCMS and start creating amazing content</p>
                    </div>
                    <div class="card-body">
                        <!-- General Error -->
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-error">
                                <?php echo sanitize($errors['general']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="signup.php" class="signup-form" data-ajax="true">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username" class="form-label">Username</label>
                                    <input 
                                        type="text" 
                                        id="username" 
                                        name="username" 
                                        class="form-control <?php echo isset($errors['username']) ? 'error' : ''; ?>"
                                        value="<?php echo sanitize($formData['username']); ?>"
                                        placeholder="Choose a username"
                                        required
                                        autocomplete="username"
                                        pattern="[a-zA-Z0-9_]{3,20}"
                                        title="3-20 characters, letters, numbers, and underscores only"
                                    >
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                            <?php echo sanitize($errors['username']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                                        value="<?php echo sanitize($formData['email']); ?>"
                                        placeholder="Enter your email"
                                        required
                                        autocomplete="email"
                                    >
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                            <?php echo sanitize($errors['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    id="full_name" 
                                    name="full_name" 
                                    class="form-control <?php echo isset($errors['full_name']) ? 'error' : ''; ?>"
                                    value="<?php echo sanitize($formData['full_name']); ?>"
                                    placeholder="Enter your full name"
                                    required
                                    autocomplete="name"
                                >
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                        <?php echo sanitize($errors['full_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                                        placeholder="Choose a password"
                                        required
                                        autocomplete="new-password"
                                        minlength="6"
                                    >
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                            <?php echo sanitize($errors['password']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>"
                                        placeholder="Confirm your password"
                                        required
                                        autocomplete="new-password"
                                        minlength="6"
                                    >
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                            <?php echo sanitize($errors['confirm_password']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Password Requirements -->
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                <h6 style="margin: 0 0 0.5rem 0; color: #2c3e50;">Password Requirements:</h6>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.875rem; color: #6c757d;">
                                    <li>At least 6 characters long</li>
                                    <li>Mix of letters, numbers, and special characters recommended</li>
                                    <li>Avoid common passwords and personal information</li>
                                </ul>
                            </div>

                            <div class="form-group">
                                <label style="display: flex; align-items: flex-start; gap: 0.5rem; font-weight: normal; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="accept_terms" 
                                        required
                                        style="margin-top: 0.25rem;"
                                    >
                                    <span style="font-size: 0.875rem;">
                                        I agree to the 
                                        <a href="#terms" style="color: #667eea; text-decoration: none;">Terms and Conditions</a> 
                                        and 
                                        <a href="#privacy" style="color: #667eea; text-decoration: none;">Privacy Policy</a>
                                    </span>
                                </label>
                                <?php if (isset($errors['accept_terms'])): ?>
                                    <div class="field-error" style="color: #e74c3c; font-size: 0.875rem; margin-top: 0.25rem;">
                                        <?php echo sanitize($errors['accept_terms']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button 
                                type="submit" 
                                class="btn btn-primary btn-block btn-lg"
                                data-original-text="Create Account"
                            >
                                Create Account
                            </button>
                        </form>

                        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #dee2e6;">
                            <p style="margin: 0;">
                                Already have an account? 
                                <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                                    Sign in here
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Features Preview -->
                <div class="card" style="margin-top: 1rem;">
                    <div class="card-body">
                        <h6 style="color: #667eea; margin-bottom: 0.5rem;">What you'll get:</h6>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.875rem; color: #6c757d;">
                            <div>✓ Personal dashboard</div>
                            <div>✓ Create unlimited posts</div>
                            <div>✓ Comment system</div>
                            <div>✓ Profile management</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="background: #2c3e50; color: white; padding: 2rem 0; margin-top: 4rem;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> BlogCMS. Secure registration with data protection.</p>
        </div>
    </footer>

    <script src="../assets/js/app.js"></script>
    <script>
        // Real-time validation
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('.signup-form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const username = document.getElementById('username');
            
            // Password confirmation validation
            function validatePasswordMatch() {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('error');
                    showFieldError(confirmPassword, 'Passwords do not match');
                } else {
                    confirmPassword.classList.remove('error');
                    removeFieldError(confirmPassword);
                }
            }
            
            // Username validation
            function validateUsernameFormat() {
                const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
                if (username.value && !usernameRegex.test(username.value)) {
                    username.classList.add('error');
                    showFieldError(username, '3-20 characters, letters, numbers, and underscores only');
                } else {
                    username.classList.remove('error');
                    removeFieldError(username);
                }
            }
            
            // Helper functions
            function showFieldError(field, message) {
                removeFieldError(field);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.style.color = '#e74c3c';
                errorDiv.style.fontSize = '0.875rem';
                errorDiv.style.marginTop = '0.25rem';
                errorDiv.textContent = message;
                field.parentNode.appendChild(errorDiv);
            }
            
            function removeFieldError(field) {
                const existingError = field.parentNode.querySelector('.field-error');
                if (existingError) {
                    existingError.remove();
                }
            }
            
            // Event listeners
            password.addEventListener('input', validatePasswordMatch);
            confirmPassword.addEventListener('input', validatePasswordMatch);
            username.addEventListener('input', validateUsernameFormat);
            
            // Focus on first input
            username.focus();
        });

        // Password strength indicator
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            let strengthIndicator = null;
            
            passwordInput.addEventListener('focus', () => {
                if (!strengthIndicator) {
                    strengthIndicator = document.createElement('div');
                    strengthIndicator.style.marginTop = '0.5rem';
                    strengthIndicator.style.fontSize = '0.875rem';
                    passwordInput.parentNode.appendChild(strengthIndicator);
                }
            });
            
            passwordInput.addEventListener('input', () => {
                if (!strengthIndicator) return;
                
                const password = passwordInput.value;
                let strength = 0;
                let feedback = [];
                
                if (password.length >= 6) strength++;
                else feedback.push('At least 6 characters');
                
                if (/[A-Z]/.test(password)) strength++;
                else feedback.push('Uppercase letter');
                
                if (/[a-z]/.test(password)) strength++;
                else feedback.push('Lowercase letter');
                
                if (/[0-9]/.test(password)) strength++;
                else feedback.push('Number');
                
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                else feedback.push('Special character');
                
                const colors = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#2ecc71'];
                const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
                
                strengthIndicator.style.color = colors[strength - 1] || '#e74c3c';
                strengthIndicator.textContent = password ? 
                    `Strength: ${labels[strength - 1] || 'Very Weak'}` : '';
            });
        });
    </script>
</body>
</html>
