<?php
// api/comments.php - Comments API Endpoint
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

startSession();
requireAuth();

$database = new Database();
$pdo = $database->getConnection();
$currentUserId = getCurrentUserId();

// Set content type for API responses
header('Content-Type: application/json');

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'create':
            handleCreateComment();
            break;
        case 'get':
            handleGetComments();
            break;
        case 'update':
            handleUpdateComment();
            break;
        case 'delete':
            handleDeleteComment();
            break;
        default:
            if ($method === 'GET' && isset($_GET['post_id'])) {
                handleGetComments();
            } elseif ($method === 'DELETE') {
                handleDeleteComment();
            } else {
                jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
            }
    }
} catch (Exception $e) {
    error_log("Comments API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}

function handleCreateComment() {
    global $pdo, $currentUserId;
    
    // Validate CSRF token
    if (!checkCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $postId = intval($_POST['post_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    $errors = [];
    
    // Validate input
    if (!$postId) {
        $errors['post_id'] = 'Post ID is required';
    }
    
    if (empty($comment)) {
        $errors['comment'] = 'Comment is required';
    } elseif (strlen($comment) < 3) {
        $errors['comment'] = 'Comment must be at least 3 characters long';
    } elseif (strlen($comment) > 1000) {
        $errors['comment'] = 'Comment must be less than 1000 characters';
    }
    
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the errors below',
            'errors' => $errors
        ], 400);
    }
    
    try {
        // Check if post exists and is published (users can comment on any published post)
        $stmt = $pdo->prepare("SELECT id, title, user_id FROM posts WHERE id = ? AND status = 'published'");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found or not available for comments'], 404);
        }
        
        // Insert comment
        $sql = "INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$postId, $currentUserId, $comment]);
        
        $commentId = $pdo->lastInsertId();
        
        // Log activity
        logUserActivity($currentUserId, 'comment_created', "Commented on post: {$post['title']}", $pdo);
        
        jsonResponse([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment_id' => $commentId,
            'post_id' => $postId
        ]);
        
    } catch (PDOException $e) {
        error_log("Create comment error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to add comment'], 500);
    }
}

function handleGetComments() {
    global $pdo, $currentUserId;
    
    $postId = intval($_GET['post_id'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = intval($_GET['per_page'] ?? 10);
    
    if (!$postId) {
        jsonResponse(['success' => false, 'message' => 'Post ID is required'], 400);
    }
    
    $offset = ($page - 1) * $perPage;
    
    try {
        // Check if post exists
        $stmt = $pdo->prepare("SELECT id, user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found'], 404);
        }
        
        // Check if user can view comments (post owner or public post)
        if ($post['user_id'] != $currentUserId) {
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
            $stmt->execute([$postId]);
            if (!$stmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
        }
        
        // Get comments with user information
        $sql = "SELECT 
                    c.*,
                    u.username,
                    u.full_name
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$postId, $perPage, $offset]);
        $comments = $stmt->fetchAll();
        
        // Get total count
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);
        $total = $stmt->fetch()['total'];
        
        // Format comments
        foreach ($comments as &$comment) {
            $comment['author'] = $comment['full_name'];
            $comment['date'] = formatDate($comment['created_at']);
            $comment['can_delete'] = ($comment['user_id'] == $currentUserId || $post['user_id'] == $currentUserId);
        }
        
        jsonResponse([
            'success' => true,
            'comments' => $comments,
            'pagination' => paginate($page, $total, $perPage)
        ]);
        
    } catch (PDOException $e) {
        error_log("Get comments error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to retrieve comments'], 500);
    }
}

function handleUpdateComment() {
    global $pdo, $currentUserId;
    
    // Validate CSRF token
    if (!checkCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $commentId = intval($_POST['comment_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if (!$commentId) {
        jsonResponse(['success' => false, 'message' => 'Comment ID is required'], 400);
    }
    
    $errors = [];
    
    // Validate input
    if (empty($comment)) {
        $errors['comment'] = 'Comment is required';
    } elseif (strlen($comment) < 3) {
        $errors['comment'] = 'Comment must be at least 3 characters long';
    } elseif (strlen($comment) > 1000) {
        $errors['comment'] = 'Comment must be less than 1000 characters';
    }
    
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the errors below',
            'errors' => $errors
        ], 400);
    }
    
    try {
        // Check if comment belongs to current user
        $stmt = $pdo->prepare("SELECT id, post_id FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $currentUserId]);
        $existingComment = $stmt->fetch();
        
        if (!$existingComment) {
            jsonResponse(['success' => false, 'message' => 'Comment not found or access denied'], 404);
        }
        
        // Update comment
        $sql = "UPDATE comments SET comment = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$comment, $commentId, $currentUserId]);
        
        // Log activity
        logUserActivity($currentUserId, 'comment_updated', "Updated comment ID: $commentId", $pdo);
        
        jsonResponse([
            'success' => true,
            'message' => 'Comment updated successfully',
            'post_id' => $existingComment['post_id']
        ]);
        
    } catch (PDOException $e) {
        error_log("Update comment error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update comment'], 500);
    }
}

function handleDeleteComment() {
    global $pdo, $currentUserId;
    
    // Get comment ID from different sources
    $commentId = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = intval($input['comment_id'] ?? 0);
    } else {
        $commentId = intval($_POST['comment_id'] ?? $_GET['comment_id'] ?? 0);
    }
    
    if (!$commentId) {
        jsonResponse(['success' => false, 'message' => 'Comment ID is required'], 400);
    }
    
    try {
        // Get comment and post information
        $sql = "SELECT c.id, c.user_id, c.post_id, p.user_id as post_owner_id 
                FROM comments c 
                JOIN posts p ON c.post_id = p.id 
                WHERE c.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            jsonResponse(['success' => false, 'message' => 'Comment not found'], 404);
        }
        
        // Check if user can delete comment (comment owner or post owner)
        if ($comment['user_id'] != $currentUserId && $comment['post_owner_id'] != $currentUserId) {
            jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        
        // Delete comment
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        
        if ($stmt->rowCount() > 0) {
            // Log activity
            logUserActivity($currentUserId, 'comment_deleted', "Deleted comment ID: $commentId", $pdo);
            
            jsonResponse([
                'success' => true,
                'message' => 'Comment deleted successfully',
                'post_id' => $comment['post_id']
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
        }
        
    } catch (PDOException $e) {
        error_log("Delete comment error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
    }
}
?>
