<?php
// api/dashboard-stats.php - Returns dashboard statistics for the current user (AJAX)
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

startSession();
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$database = new Database();
$pdo = $database->getConnection();
$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    jsonResponse(['error' => 'User not found'], 404);
}

$stats = [];

// Total posts by current user
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$stats['total_posts'] = (int)$stmt->fetch()['count'];

// Published posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND status = 'published'");
$stmt->execute([$currentUser['id']]);
$stats['published_posts'] = (int)$stmt->fetch()['count'];

// Draft posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND status = 'draft'");
$stmt->execute([$currentUser['id']]);
$stats['draft_posts'] = (int)$stmt->fetch()['count'];

// Total comments on user's posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?");
$stmt->execute([$currentUser['id']]);
$stats['total_comments'] = (int)$stmt->fetch()['count'];

jsonResponse(['success' => true, 'stats' => $stats]); 