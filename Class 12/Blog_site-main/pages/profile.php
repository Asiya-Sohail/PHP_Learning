<?php
// pages/profile.php - User Profile Management
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

startSession();
requireAuth();

$database = new Database();
$pdo = $database->getConnection();
$currentUser = getCurrentUser($pdo);

// Get user statistics
$stats = [];

// Total posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$stats['total_posts'] = $stmt->fetch()['count'];

// Total comments
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$stats['total_comments'] = $stmt->fetch()['count'];

// Join date
$stats['member_since'] = formatDate($currentUser['created_at'], 'F Y');

// Last login
$stats['last_login'] = $currentUser['last_login'] ? formatDate($currentUser['last_login']) : 'Never';

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BlogCMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="dashboard.php" class="logo">BlogCMS</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="#" data-modal="createPostModal">New Post</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="../api/auth.php?action=logout">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <!-- Flash Messages -->
            <?php foreach ($flashMessages as $message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo sanitize($message['message']); ?>
                </div>
            <?php endforeach; ?>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">
                <!-- Profile Overview -->
                <div class="card fade-in">
                    <div class="card-header text-center">
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: bold;">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 2)); ?>
                        </div>
                        <h3><?php echo sanitize($currentUser['full_name']); ?></h3>
                        <p style="color: #6c757d; margin: 0;">@<?php echo sanitize($currentUser['username']); ?></p>
                    </div>
                    <div class="card-body">
                        <div style="text-align: center;">
                            <div class="stat-number" style="font-size: 2rem;"><?php echo $stats['total_posts']; ?></div>
                            <div class="stat-label">Posts</div>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div style="text-align: center;">
                            <div class="stat-number" style="font-size: 2rem;"><?php echo $stats['total_comments']; ?></div>
                            <div class="stat-label">Comments</div>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div style="font-size: 0.875rem; color: #6c757d;">
                            <p><strong>Member since:</strong> <?php echo $stats['member_since']; ?></p>
                            <p><strong>Last login:</strong> <?php echo $stats['last_login']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Profile Management -->
                <div>
                    <!-- Basic Information -->
                    <div class="card fade-in">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <form id="profileForm" data-ajax="true" action="../api/auth.php" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username" class="form-label">Username</label>
                                        <input 
                                            type="text" 
                                            id="username" 
                                            name="username" 
                                            class="form-control" 
                                            value="<?php echo sanitize($currentUser['username']); ?>"
                                            disabled
                                            style="background-color: #f8f9fa;"
                                        >
                                        <small style="color: #6c757d;">Username cannot be changed</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="form-control" 
                                            value="<?php echo sanitize($currentUser['email']); ?>"
                                            required
                                        >
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input 
                                        type="text" 
                                        id="full_name" 
                                        name="full_name" 
                                        class="form-control" 
                                        value="<?php echo sanitize($currentUser['full_name']); ?>"
                                        required
                                    >
                                </div>
                                
                                <div style="text-align: right;">
                                    <button 
                                        type="submit" 
                                        class="btn btn-primary"
                                        data-original-text="Update Profile"
                                    >
                                        Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Security Settings</h3>
                        </div>
                        <div class="card-body">
                            <form id="passwordForm" data-ajax="true" action="../api/auth.php" method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input 
                                        type="password" 
                                        id="current_password" 
                                        name="current_password" 
                                        class="form-control" 
                                        required
                                        autocomplete="current-password"
                                    >
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input 
                                            type="password" 
                                            id="new_password" 
                                            name="new_password" 
                                            class="form-control" 
                                            required
                                            minlength="6"
                                            autocomplete="new-password"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input 
                                            type="password" 
                                            id="confirm_password" 
                                            name="confirm_password" 
                                            class="form-control" 
                                            required
                                            minlength="6"
                                            autocomplete="new-password"
                                        >
                                    </div>
                                </div>
                                
                                <!-- Password Requirements -->
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                    <h6 style="margin: 0 0 0.5rem 0; color: #2c3e50;">Password Requirements:</h6>
                                    <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.875rem; color: #6c757d;">
                                        <li>At least 6 characters long</li>
                                        <li>Mix of letters, numbers, and special characters recommended</li>
                                        <li>Different from your current password</li>
                                    </ul>
                                </div>
                                
                                <div style="text-align: right;">
                                    <button 
                                        type="submit" 
                                        class="btn btn-danger"
                                        data-original-text="Change Password"
                                    >
                                        Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                        </div>
                        <div class="card-body">
                            <div id="activityLog">
                                <div style="text-align: center; padding: 2rem; color: #6c757d;">
                                    <div class="spinner"></div>
                                    <p style="margin-top: 1rem;">Loading activity...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Account Actions</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <button class="btn btn-secondary" onclick="exportUserData()">
                                    📊 Export My Data
                                </button>
                                <button class="btn btn-info" onclick="showActivityLog()">
                                    📋 View Full Activity Log
                                </button>
                                <button class="btn btn-warning" onclick="confirmAccountDeactivation()">
                                    ⚠️ Deactivate Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Activity Log Modal -->
    <div id="activityModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h5 class="modal-title">Account Activity Log</h5>
                <button class="close modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="fullActivityLog">
                    <!-- Activity will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Profile management functions
        document.addEventListener('DOMContentLoaded', () => {
            // Load recent activity
            loadRecentActivity();
            
            // Password confirmation validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePasswordMatch() {
                if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                    confirmPassword.classList.add('error');
                    showFieldError(confirmPassword, 'Passwords do not match');
                } else {
                    confirmPassword.classList.remove('error');
                    removeFieldError(confirmPassword);
                }
            }
            
            newPassword.addEventListener('input', validatePasswordMatch);
            confirmPassword.addEventListener('input', validatePasswordMatch);
        });

        async function loadRecentActivity() {
            try {
                const response = await fetch('../api/activity.php?limit=5');
                const result = await response.json();
                
                const container = document.getElementById('activityLog');
                
                if (result.success && result.activities.length > 0) {
                    let html = '';
                    result.activities.forEach(activity => {
                        html += `
                            <div style="border-bottom: 1px solid #dee2e6; padding: 0.75rem 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>${activity.action_label}</strong>
                                        ${activity.details ? `<br><small style="color: #6c757d;">${activity.details}</small>` : ''}
                                    </div>
                                    <small style="color: #6c757d;">${activity.time_ago}</small>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #6c757d;">No recent activity</p>';
                }
            } catch (error) {
                document.getElementById('activityLog').innerHTML = '<p style="text-align: center; color: #e74c3c;">Failed to load activity</p>';
            }
        }

        async function showActivityLog() {
            try {
                const response = await fetch('../api/activity.php');
                const result = await response.json();
                
                const container = document.getElementById('fullActivityLog');
                
                if (result.success && result.activities.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr><th>Action</th><th>Details</th><th>IP Address</th><th>Date</th></tr></thead><tbody>';
                    
                    result.activities.forEach(activity => {
                        html += `
                            <tr>
                                <td><strong>${activity.action_label}</strong></td>
                                <td>${activity.details || '-'}</td>
                                <td><code>${activity.ip_address}</code></td>
                                <td>${activity.created_at}</td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: #6c757d;">No activity found</p>';
                }
                
                window.blogApp.openModal('activityModal');
            } catch (error) {
                window.blogApp.showAlert('error', 'Failed to load activity log');
            }
        }

        function exportUserData() {
            window.open('../api/export.php?type=user_data', '_blank');
        }

        function confirmAccountDeactivation() {
            if (confirm('Are you sure you want to deactivate your account? This action cannot be easily undone.')) {
                if (confirm('This will hide your posts and prevent login. Are you absolutely sure?')) {
                    deactivateAccount();
                }
            }
        }

        async function deactivateAccount() {
            try {
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'deactivate_account',
                        csrf_token: '<?php echo generateCSRFToken(); ?>'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Account deactivated successfully. You will now be logged out.');
                    window.location.href = '../index.php';
                } else {
                    window.blogApp.showAlert('error', result.message);
                }
            } catch (error) {
                window.blogApp.showAlert('error', 'Failed to deactivate account');
            }
        }

        // Helper functions for field validation
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

        // Handle successful form submissions
        document.addEventListener('DOMContentLoaded', () => {
            const profileForm = document.getElementById('profileForm');
            const passwordForm = document.getElementById('passwordForm');
            
            // Override form success handler for password form
            if (passwordForm) {
                passwordForm.addEventListener('submit', async (e) => {
                    if (passwordForm.hasAttribute('data-ajax')) {
                        e.preventDefault();
                        
                        const formData = new FormData(passwordForm);
                        
                        try {
                            const response = await fetch(passwordForm.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                window.blogApp.showAlert('success', result.message);
                                passwordForm.reset(); // Clear password fields
                            } else {
                                window.blogApp.showAlert('error', result.message);
                            }
                        } catch (error) {
                            window.blogApp.showAlert('error', 'Network error occurred');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
