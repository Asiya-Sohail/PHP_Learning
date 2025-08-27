<?php
// pages/dashboard.php - Main Dashboard
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

startSession();
requireAuth();

$database = new Database();
$pdo = $database->getConnection();
$currentUser = getCurrentUser($pdo);

// Get dashboard statistics
$stats = [];

// Total posts by current user
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$stats['total_posts'] = $stmt->fetch()['count'];

// Published posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND status = 'published'");
$stmt->execute([$currentUser['id']]);
$stats['published_posts'] = $stmt->fetch()['count'];

// Draft posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND status = 'draft'");
$stmt->execute([$currentUser['id']]);
$stats['draft_posts'] = $stmt->fetch()['count'];

// Total comments on user's posts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?");
$stmt->execute([$currentUser['id']]);
$stats['total_comments'] = $stmt->fetch()['count'];

// Recent posts with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT 
        p.*,
        COUNT(c.id) as comment_count
    FROM posts p
    LEFT JOIN comments c ON p.id = c.post_id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$currentUser['id'], $perPage, $offset]);
$recentPosts = $stmt->fetchAll();

// Get total posts for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$totalPosts = $stmt->fetch()['count'];
$pagination = paginate($page, $totalPosts, $perPage);

// Get flash messages
$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BlogCMS</title>
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

            <!-- Welcome Section -->
            <div class="card fade-in">
                <div class="card-body">
                    <h1>Welcome back, <?php echo sanitize($currentUser['full_name']); ?>!</h1>
                    <p style="color: #6c757d; margin: 0;">
                        Manage your blog posts, view analytics, and engage with your readers from your dashboard.
                    </p>
                </div>
            </div>

            <!-- Dashboard Statistics -->
            <div class="dashboard-stats" id="dashboardStats">
                <div class="stat-card">
                    <div class="stat-number" id="stat-total_posts"><?php echo $stats['total_posts']; ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="stat-published_posts"><?php echo $stats['published_posts']; ?></div>
                    <div class="stat-label">Published</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="stat-draft_posts"><?php echo $stats['draft_posts']; ?></div>
                    <div class="stat-label">Drafts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="stat-total_comments"><?php echo $stats['total_comments']; ?></div>
                    <div class="stat-label">Comments</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button class="btn btn-primary" data-modal="createPostModal">
                            📝 Create New Post
                        </button>
                        <a href="profile.php" class="btn btn-secondary">
                            👤 Edit Profile
                        </a>
                        <button class="btn btn-success" onclick="exportPosts()">
                            📊 Export Posts
                        </button>
                        <button class="btn btn-info" onclick="showSearchModal()">
                            🔍 Search Posts
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">Your Recent Posts</h3>
                    <div>
                        <input 
                            type="text" 
                            id="searchInput" 
                            placeholder="Search posts..." 
                            style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; margin-right: 1rem;"
                            data-search-type="posts"
                        >
                        <button class="btn btn-primary btn-sm" data-modal="createPostModal">
                            Add New Post
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recentPosts)): ?>
                        <div class="text-center" style="padding: 2rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                            <h4>No posts yet</h4>
                            <p style="color: #6c757d; margin-bottom: 1.5rem;">
                                Start creating amazing content for your blog!
                            </p>
                            <button class="btn btn-primary" data-modal="createPostModal">
                                Create Your First Post
                            </button>
                        </div>
                    <?php else: ?>
                        <div id="postsList">
                            <?php foreach ($recentPosts as $post): ?>
                                <div class="post-card">
                                    <div class="card-body">
                                        <div class="post-meta">
                                            <span><?php echo formatDate($post['created_at']); ?></span>
                                            <span class="post-status status-<?php echo $post['status']; ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </div>
                                        <h5 class="card-title"><?php echo sanitize($post['title']); ?></h5>
                                        <p><?php echo generateExcerpt($post['content'], 150); ?></p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                            <small style="color: #6c757d;">
                                                <?php echo $post['comment_count']; ?> comment(s)
                                            </small>
                                            <div class="post-actions" style="display: flex; gap: 0.5rem;">
                                                <a href="#" onclick="viewPost(<?php echo $post['id']; ?>)" class="btn btn-sm btn-primary">View</a>
                                                <a href="#" onclick="editPost(<?php echo $post['id']; ?>)" class="btn btn-sm btn-secondary">Edit</a>
                                                <button 
                                                    class="btn btn-sm btn-danger delete-btn" 
                                                    data-post-id="<?php echo $post['id']; ?>"
                                                    data-url="../api/posts.php"
                                                    data-confirm="Are you sure you want to delete '<?php echo sanitize($post['title']); ?>'?"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <nav>
                                <ul class="pagination">
                                    <?php if ($pagination['has_prev']): ?>
                                        <li class="page-item">
                                            <a href="?page=<?php echo $pagination['current_page'] - 1; ?>" class="page-link">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                                            <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['has_next']): ?>
                                        <li class="page-item">
                                            <a href="?page=<?php echo $pagination['current_page'] + 1; ?>" class="page-link">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search Results -->
            <div id="searchResults"></div>
        </div>
    </main>

    <!-- Create Post Modal -->
    <div id="createPostModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h5 class="modal-title">Create New Post</h5>
                <button class="close modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createPostForm" class="post-form" data-ajax="true" action="../api/posts.php?action=create" method="POST">
                    <!-- <input type="hidden" name="action" value="create"> -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="post_title" class="form-label">Title</label>
                        <input 
                            type="text" 
                            id="post_title" 
                            name="title" 
                            class="form-control" 
                            placeholder="Enter post title"
                            
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="post_content" class="form-label">Content</label>
                        <textarea 
                            id="post_content" 
                            name="content" 
                            class="form-control" 
                            rows="8"
                            placeholder="Write your post content here..."
                            required
                        ></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="post_status" class="form-label">Status</label>
                            <select id="post_status" name="status" class="form-control">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="post_featured_image" class="form-label">Featured Image</label>
                            <input 
                                type="file" 
                                id="post_featured_image" 
                                name="featured_image" 
                                class="form-control"
                                accept="image/*"
                            >
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                <button type="submit" form="createPostForm" class="btn btn-primary" data-original-text="Create Post">
                    Create Post
                </button>
            </div>
        </div>
    </div>

    <!-- View Post Modal -->
    <div id="viewPostModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPostTitle">Post Title</h5>
                <button class="close modal-close">&times;</button>
            </div>
            <div class="modal-body" id="viewPostContent">
                <!-- Post content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Close</button>
                <button type="button" class="btn btn-primary" id="editPostFromView">Edit Post</button>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal -->
    <div id="editPostModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h5 class="modal-title">Edit Post</h5>
                <button class="close modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editPostForm" class="post-form" data-ajax="true" action="../api/posts.php?action=update" method="POST">
                    <!-- <input type="hidden" name="action" value="update"> -->
                    <input type="hidden" name="post_id" id="edit_post_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="edit_post_title" class="form-label">Title</label>
                        <input 
                            type="text" 
                            id="edit_post_title" 
                            name="title" 
                            class="form-control" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_post_content" class="form-label">Content</label>
                        <textarea 
                            id="edit_post_content" 
                            name="content" 
                            class="form-control" 
                            rows="8"
                            required
                        ></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_post_status" class="form-label">Status</label>
                        <select id="edit_post_status" name="status" class="form-control">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                <button type="submit" form="editPostForm" class="btn btn-primary" data-original-text="Update Post">
                    Update Post
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
    <script>
        // Post management functions
        async function viewPost(postId) {
            try {
                const response = await fetch(`../api/posts.php?action=get&id=${postId}`);
                const result = await response.json();
                
                if (result.success) {
                    const post = result.post;
                    document.getElementById('viewPostTitle').textContent = post.title;
                    document.getElementById('viewPostContent').innerHTML = `
                        <div class="post-meta" style="margin-bottom: 1rem;">
                            <strong>Status:</strong> <span class="post-status status-${post.status}">${post.status}</span><br>
                            <strong>Created:</strong> ${post.created_at}<br>
                            <strong>Updated:</strong> ${post.updated_at}<br>
                            <strong>Comments:</strong> ${post.comment_count || 0}
                        </div>
                        <div class="post-content">
                            ${post.content.replace(/\n/g, '<br>')}
                        </div>
                    `;
                    
                    document.getElementById('editPostFromView').onclick = () => {
                        window.blogApp.closeModal();
                        editPost(postId);
                    };
                    
                    window.blogApp.openModal('viewPostModal');
                } else {
                    window.blogApp.showAlert('error', result.message);
                }
            } catch (error) {
                window.blogApp.showAlert('error', 'Failed to load post');
            }
        }

        async function editPost(postId) {
            try {
                const response = await fetch(`../api/posts.php?action=get&id=${postId}`);
                const result = await response.json();
                
                if (result.success) {
                    const post = result.post;
                    document.getElementById('edit_post_id').value = post.id;
                    document.getElementById('edit_post_title').value = post.title;
                    document.getElementById('edit_post_content').value = post.content;
                    document.getElementById('edit_post_status').value = post.status;
                    
                    window.blogApp.openModal('editPostModal');
                } else {
                    window.blogApp.showAlert('error', result.message);
                }
            } catch (error) {
                window.blogApp.showAlert('error', 'Failed to load post for editing');
            }
        }

        function exportPosts() {
            window.open('../api/posts.php?action=export', '_blank');
        }

        function showSearchModal() {
            document.getElementById('searchInput').focus();
        }

        // Auto-save for create post form
        document.addEventListener('DOMContentLoaded', () => {
            window.blogApp.enableAutoSave('#createPostForm');
        });

        // Refresh stats every 30 seconds
        setInterval(() => {
            window.blogApp.loadDashboardStats();
        }, 30000);
    </script>
</body>
</html>
