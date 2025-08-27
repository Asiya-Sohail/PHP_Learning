<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$postId) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Invalid post ID.</div></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container mt-5">
    <div id="postContainer">
        <div class="text-center text-muted">Loading post...</div>
    </div>
    <hr>
    <h4>Comments</h4>
    <div id="commentsContainer">
        <div class="text-center text-muted">Loading comments...</div>
    </div>
    <div class="mt-4">
        <form id="commentForm" class="comment-form" data-ajax method="POST" action="../api/comments.php">
            <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
            <div class="form-group">
                <textarea name="content" class="form-control" rows="3" placeholder="Add a comment..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Comment</button>
        </form>
    </div>
</div>
<script>
// Fetch and display the post
document.addEventListener('DOMContentLoaded', function() {
    fetchPost();
    fetchComments();

    // AJAX comment form submission
    document.getElementById('commentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) {
            form.reset();
            fetchComments();
        } else {
            alert(result.message || 'Failed to add comment');
        }
    });
});

function fetchPost() {
    const postId = <?php echo $postId; ?>;
    fetch(`../api/posts.php?action=get&id=${postId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const post = data.post;
                document.getElementById('postContainer').innerHTML = `
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="card-title">${post.title}</h3>
                            <div class="mb-2 text-muted">By ${post.full_name} on ${post.created_at}</div>
                            <div class="post-content">${post.content.replace(/\n/g, '<br>')}</div>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('postContainer').innerHTML = `<div class='alert alert-danger'>${data.message}</div>`;
            }
        })
        .catch(() => {
            document.getElementById('postContainer').innerHTML = `<div class='alert alert-danger'>Failed to load post.</div>`;
        });
}

function fetchComments() {
    const postId = <?php echo $postId; ?>;
    fetch(`../api/comments.php?post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.comments.length > 0) {
                let html = '';
                data.comments.forEach(comment => {
                    html += `
                        <div class="media mb-3">
                            <div class="media-body">
                                <h6 class="mt-0 mb-1">${comment.author}</h6>
                                <small class="text-muted">${comment.date}</small>
                                <p>${comment.content}</p>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('commentsContainer').innerHTML = html;
            } else {
                document.getElementById('commentsContainer').innerHTML = '<div class="text-muted">No comments yet.</div>';
            }
        })
        .catch(() => {
            document.getElementById('commentsContainer').innerHTML = `<div class='alert alert-danger'>Failed to load comments.</div>`;
        });
}
</script>
</body>
</html> 