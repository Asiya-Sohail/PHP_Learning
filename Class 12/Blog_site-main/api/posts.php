<?php
// api/posts.php - Posts API Endpoint
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
            handleCreatePost();
            break;
        case 'get':
            handleGetPost();
            break;
        case 'update':
            handleUpdatePost();
            break;
        case 'delete':
            handleDeletePost();
            break;
        case 'list':
            handleListPosts();
            break;
        case 'search':
            handleSearchPosts();
            break;
        case 'export':
            handleExportPosts();
            break;
        default:
            if ($method === 'DELETE') {
                handleDeletePost();
            } else {
                jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
            }
    }
} catch (Exception $e) {
    error_log("Posts API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}

function handleCreatePost() {
    global $pdo, $currentUserId;

    // Ignore auto-save requests for real post creation
    if (isset($_POST['auto_save']) && $_POST['auto_save'] == '1') {
        jsonResponse(['success' => true, 'message' => 'Auto-saved']);
    }
    
    // Validate CSRF token
    if (!checkCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    $errors = [];
    
    // Validate input
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($title) > 200) {
        $errors['title'] = 'Title must be less than 200 characters';
    }
    
    if (empty($content)) {
        $errors['content'] = 'Content is required';
    }
    
    if (!in_array($status, ['draft', 'published'])) {
        $errors['status'] = 'Invalid status';
    }
    
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the errors below',
            'errors' => $errors
        ], 400);
    }
    
    // Handle file upload if present
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['featured_image']);
        if ($uploadResult['success']) {
            $featuredImage = $uploadResult['filename'];
        }
    }
    
    try {
        // Insert post
        $sql = "INSERT INTO posts (user_id, title, content, status, featured_image, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUserId, $title, $content, $status, $featuredImage]);
        
        $postId = $pdo->lastInsertId();
        
        // Log activity
        logUserActivity($currentUserId, 'post_created', "Created post: $title", $pdo);
        
        jsonResponse([
            'success' => true,
            'message' => 'Post created successfully',
            'post_id' => $postId,
            'redirect' => '../pages/dashboard.php'
        ]);
        
    } catch (PDOException $e) {
        error_log("Create post error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to create post'], 500);
    }
}

function handleGetPost() {
    global $pdo, $currentUserId;
    
    $postId = intval($_GET['id'] ?? 0);
    
    if (!$postId) {
        jsonResponse(['success' => false, 'message' => 'Post ID is required'], 400);
    }
    
    try {
        $sql = "SELECT 
                    p.*,
                    u.username,
                    u.full_name,
                    COUNT(c.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN comments c ON p.id = c.post_id
                WHERE p.id = ? AND p.user_id = ?
                GROUP BY p.id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$postId, $currentUserId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found or access denied'], 404);
        }
        
        jsonResponse([
            'success' => true,
            'post' => $post
        ]);
        
    } catch (PDOException $e) {
        error_log("Get post error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to retrieve post'], 500);
    }
}

function handleUpdatePost() {
    global $pdo, $currentUserId;
    
    // Validate CSRF token
    if (!checkCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 400);
    }
    
    $postId = intval($_POST['post_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    if (!$postId) {
        jsonResponse(['success' => false, 'message' => 'Post ID is required'], 400);
    }
    
    $errors = [];
    
    // Validate input
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($title) > 200) {
        $errors['title'] = 'Title must be less than 200 characters';
    }
    
    if (empty($content)) {
        $errors['content'] = 'Content is required';
    }
    
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        $errors['status'] = 'Invalid status';
    }
    
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the errors below',
            'errors' => $errors
        ], 400);
    }
    
    try {
        // Check if post belongs to current user
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $currentUserId]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Post not found or access denied'], 404);
        }
        
        // Update post
        $sql = "UPDATE posts SET title = ?, content = ?, status = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $content, $status, $postId, $currentUserId]);
        
        // Log activity
        logUserActivity($currentUserId, 'post_updated', "Updated post: $title", $pdo);
        
        jsonResponse([
            'success' => true,
            'message' => 'Post updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update post error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update post'], 500);
    }
}

function handleDeletePost() {
    global $pdo, $currentUserId;
    
    // Get post ID from different sources
    $postId = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = intval($input['post_id'] ?? 0);
    } else {
        $postId = intval($_POST['post_id'] ?? $_GET['post_id'] ?? 0);
    }
    
    if (!$postId) {
        jsonResponse(['success' => false, 'message' => 'Post ID is required'], 400);
    }
    
    try {
        // Get post title for logging
        $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $currentUserId]);
        $post = $stmt->fetch();
        
        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found or access denied'], 404);
        }
        
        // Delete post (comments will be deleted due to CASCADE)
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $currentUserId]);
        
        if ($stmt->rowCount() > 0) {
            // Log activity
            logUserActivity($currentUserId, 'post_deleted', "Deleted post: {$post['title']}", $pdo);
            
            jsonResponse([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to delete post'], 500);
        }
        
    } catch (PDOException $e) {
        error_log("Delete post error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to delete post'], 500);
    }
}

function handleListPosts() {
    global $pdo, $currentUserId;
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = intval($_GET['per_page'] ?? 10);
    $status = $_GET['status'] ?? '';
    $search = trim($_GET['search'] ?? '');
    
    $offset = ($page - 1) * $perPage;
    
    try {
        // Build query conditions
        $conditions = ["p.user_id = ?"];
        $params = [$currentUserId];
        
        if ($status && in_array($status, ['draft', 'published', 'archived'])) {
            $conditions[] = "p.status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Get posts with pagination
        $sql = "SELECT 
                    p.*,
                    u.username,
                    u.full_name,
                    COUNT(c.id) as comment_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN comments c ON p.id = c.post_id
                WHERE $whereClause
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([...$params, $perPage, $offset]);
        $posts = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM posts p WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // Add excerpts to posts
        foreach ($posts as &$post) {
            $post['excerpt'] = generateExcerpt($post['content']);
            $post['author'] = $post['full_name'];
            $post['date'] = formatDate($post['created_at']);
        }
        
        jsonResponse([
            'success' => true,
            'posts' => $posts,
            'pagination' => paginate($page, $total, $perPage)
        ]);
        
    } catch (PDOException $e) {
        error_log("List posts error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to retrieve posts'], 500);
    }
}

function handleSearchPosts() {
    global $pdo, $currentUserId;
    
    $query = trim($_GET['q'] ?? '');
    $type = $_GET['type'] ?? 'posts';
    
    if (strlen($query) < 2) {
        jsonResponse(['success' => true, 'results' => []]);
    }
    
    try {
        if ($type === 'posts') {
            $sql = "SELECT 
                        p.id,
                        p.title,
                        p.status,
                        p.created_at,
                        u.full_name as author,
                        LEFT(p.content, 200) as excerpt
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.user_id = ? AND (p.title LIKE ? OR p.content LIKE ?)
                    ORDER BY p.created_at DESC
                    LIMIT 10";
            
            $searchParam = "%$query%";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$currentUserId, $searchParam, $searchParam]);
            $results = $stmt->fetchAll();
            
            // Format results
            foreach ($results as &$result) {
                $result['excerpt'] = generateExcerpt($result['excerpt']);
                $result['date'] = formatDate($result['created_at']);
            }
            
            jsonResponse($results);
        }
        
    } catch (PDOException $e) {
        error_log("Search posts error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Search failed'], 500);
    }
}

function handleExportPosts() {
    global $pdo, $currentUserId;
    
    try {
        $sql = "SELECT 
                    p.title,
                    p.content,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    COUNT(c.id) as comment_count
                FROM posts p
                LEFT JOIN comments c ON p.id = c.post_id
                WHERE p.user_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUserId]);
        $posts = $stmt->fetchAll();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="my_blog_posts_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['Title', 'Content', 'Status', 'Comments', 'Created', 'Updated']);
        
        // CSV data
        foreach ($posts as $post) {
            fputcsv($output, [
                $post['title'],
                strip_tags($post['content']),
                $post['status'],
                $post['comment_count'],
                $post['created_at'],
                $post['updated_at']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (PDOException $e) {
        error_log("Export posts error: " . $e->getMessage());
        header('Content-Type: application/json');
        jsonResponse(['success' => false, 'message' => 'Export failed'], 500);
    }
}
?>
