<?php
// index.php - Main landing page
require_once 'includes/functions.php';
require_once 'config/database.php';

startSession();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('pages/dashboard.php');
}

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">BlogCMS</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="pages/login.php">Login</a></li>
                        <li><a href="pages/signup.php">Sign Up</a></li>
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

            <!-- Hero Section -->
            <section class="hero text-center" style="padding: 4rem 0;">
                <h1 style="font-size: 3rem; margin-bottom: 1rem; color: #2c3e50;">
                    Welcome to BlogCMS
                </h1>
                <p style="font-size: 1.25rem; color: #6c757d; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                    A powerful and intuitive blog management system built with PHP and MySQL. 
                    Create, manage, and publish your content with ease.
                </p>
                <div class="hero-actions" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="pages/signup.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="pages/login.php" class="btn btn-secondary btn-lg">Login</a>
                </div>
            </section>

            <!-- Features Section -->
            <section class="features" style="padding: 4rem 0;">
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                        <h3>Easy Content Creation</h3>
                        <p>Create and edit blog posts with our intuitive interface. Support for rich text formatting and media uploads.</p>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                        <h3>Comment Management</h3>
                        <p>Engage with your readers through a comprehensive comment system with moderation capabilities.</p>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">👤</div>
                        <h3>User Management</h3>
                        <p>Secure user authentication system with profile management and activity tracking.</p>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                        <h3>Dashboard Analytics</h3>
                        <p>Track your blog's performance with detailed statistics and analytics dashboard.</p>
                    </div>
                </div>
            </section>

            <!-- Technology Stack -->
            <section class="tech-stack" style="padding: 4rem 0; background: white; border-radius: 10px; margin: 2rem 0;">
                <div style="padding: 2rem;">
                    <h2 class="text-center" style="margin-bottom: 2rem; color: #2c3e50;">Built with Modern Technologies</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                        <div>
                            <h4 style="color: #667eea; margin-bottom: 0.5rem;">Frontend</h4>
                            <p>HTML5, CSS3, JavaScript (ES6+), AJAX</p>
                        </div>
                        <div>
                            <h4 style="color: #667eea; margin-bottom: 0.5rem;">Backend</h4>
                            <p>PHP 8+, Object-Oriented Programming</p>
                        </div>
                        <div>
                            <h4 style="color: #667eea; margin-bottom: 0.5rem;">Database</h4>
                            <p>MySQL 8+, PDO for secure queries</p>
                        </div>
                        <div>
                            <h4 style="color: #667eea; margin-bottom: 0.5rem;">Security</h4>
                            <p>Password hashing, CSRF protection, Input validation</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Getting Started -->
            <section class="getting-started text-center" style="padding: 4rem 0;">
                <h2 style="margin-bottom: 2rem; color: #2c3e50;">Ready to Get Started?</h2>
                <p style="font-size: 1.125rem; color: #6c757d; margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                    Join thousands of bloggers who trust BlogCMS for their content management needs.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="pages/signup.php" class="btn btn-primary btn-lg">Create Account</a>
                    <a href="#demo" class="btn btn-secondary btn-lg">View Demo</a>
                </div>
            </section>
        </div>
    </main>

    <footer style="background: #2c3e50; color: white; padding: 2rem 0; margin-top: 4rem;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> BlogCMS. Built for educational purposes with PHP and MySQL.</p>
            <p style="margin-top: 0.5rem; opacity: 0.8;">
                <small>Features: User Authentication • CRUD Operations • AJAX • Responsive Design</small>
            </p>
        </div>
    </footer>

    <script src="assets/js/app.js?v=1"></script>
</body>
</html>
