// Main Application JavaScript
class BlogApp {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
    }

    bindEvents() {
        // Form submissions
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // Modal triggers
        document.addEventListener('click', this.handleModalTriggers.bind(this));
        
        // Delete confirmations
        document.addEventListener('click', this.handleDeleteActions.bind(this));
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.handleSearch.bind(this), 300));
        }

        // Auto-dismiss alerts
        this.autoDismissAlerts();
    }

    initializeComponents() {
        // Initialize any components that need setup
        this.loadDashboardStats();
    }

    // Form handling with AJAX
    async handleFormSubmit(e) {
        const form = e.target;
        
        // Only handle forms with data-ajax attribute
        if (!form.hasAttribute('data-ajax')) {
            return;
        }

        e.preventDefault();
        
        const formData = new FormData(form);
        const action = form.action || window.location.href;
        const method = form.method || 'POST';
        console.log(form.action);
        // Show loading state
        this.showFormLoading(form);
        
        try {
            const response = await fetch(action, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.handleFormSuccess(form, result);
            } else {
                this.handleFormError(form, result);
            }
        } catch (error) {
            this.handleFormError(form, { message: 'Network error occurred' });
        } finally {
            this.hideFormLoading(form);
        }
    }

    showFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Loading...';
        }
    }

    hideFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
        }
    }

    handleFormSuccess(form, result) {
        // Show success message
        this.showAlert('success', result.message || 'Operation completed successfully');
        
        // Handle different form types
        if (form.classList.contains('login-form')) {
            window.location.href = result.redirect || 'pages/dashboard.php';
        } else if (form.classList.contains('signup-form')) {
            window.location.href = result.redirect || 'pages/login.php';
        } else if (form.classList.contains('post-form')) {
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                this.refreshPostsList();
            }
        } else if (form.classList.contains('comment-form')) {
            this.refreshComments(result.post_id);
            form.reset();
        }
    }

    handleFormError(form, result) {
        this.showAlert('error', result.message || 'An error occurred');
        
        // Show field-specific errors
        if (result.errors) {
            Object.keys(result.errors).forEach(field => {
                const fieldElement = form.querySelector(`[name="${field}"]`);
                if (fieldElement) {
                    fieldElement.classList.add('error');
                    this.showFieldError(fieldElement, result.errors[field]);
                }
            });
        }
    }

    showFieldError(field, message) {
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    // Modal handling
    handleModalTriggers(e) {
        const trigger = e.target.closest('[data-modal]');
        if (trigger) {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal');
            this.openModal(modalId);
        }

        const closeBtn = e.target.closest('.modal-close, .close');
        if (closeBtn) {
            e.preventDefault();
            this.closeModal();
        }

        // Close modal when clicking overlay
        if (e.target.classList.contains('modal-overlay')) {
            this.closeModal();
        }
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus first input
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal-overlay');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = '';
    }

    // Delete confirmation and handling
    handleDeleteActions(e) {
        const deleteBtn = e.target.closest('.delete-btn, [data-action="delete"]');
        if (deleteBtn) {
            e.preventDefault();
            
            const confirmMessage = deleteBtn.getAttribute('data-confirm') || 
                                 'Are you sure you want to delete this item?';
            
            if (confirm(confirmMessage)) {
                this.performDelete(deleteBtn);
            }
        }
    }

    async performDelete(button) {
        const url = button.href || button.getAttribute('data-url');
        const postId = button.getAttribute('data-post-id');
        const commentId = button.getAttribute('data-comment-id');
        
        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_id: postId, comment_id: commentId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('success', result.message);
                
                // Remove element from DOM or refresh list
                if (postId) {
                    this.refreshPostsList();
                } else if (commentId) {
                    this.refreshComments(button.getAttribute('data-post-id'));
                }
            } else {
                this.showAlert('error', result.message);
            }
        } catch (error) {
            this.showAlert('error', 'Failed to delete item');
        }
    }

    // Search functionality
    async handleSearch(e) {
        const query = e.target.value.trim();
        const searchType = e.target.getAttribute('data-search-type') || 'posts';
        
        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }
        
        try {
            const response = await fetch(`../api/posts.php?action=search&q=${encodeURIComponent(query)}&type=${searchType}`);
            const results = await response.json();
            
            this.displaySearchResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        const container = document.getElementById('searchResults');
        if (!container) return;
        
        if (results.length === 0) {
            container.innerHTML = '<p class="text-center">No results found</p>';
            return;
        }
        
        let html = '<div class="search-results">';
        results.forEach(item => {
            html += `
                <div class="search-result-item">
                    <h5><a href="pages/view-post.php?id=${item.id}">${item.title}</a></h5>
                    <p>${item.excerpt}</p>
                    <small>By ${item.author} on ${item.date}</small>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }

    clearSearchResults() {
        const container = document.getElementById('searchResults');
        if (container) {
            container.innerHTML = '';
        }
    }

    // Dashboard stats loading
    async loadDashboardStats() {
        const statsContainer = document.getElementById('dashboardStats');
        if (!statsContainer) return;
        
        try {
            const response = await fetch('../api/dashboard-stats.php');
            const stats = await response.json();
            
            this.updateDashboardStats(stats);
        } catch (error) {
            console.error('Failed to load dashboard stats:', error);
        }
    }

    updateDashboardStats(stats) {
        Object.keys(stats).forEach(key => {
            const element = document.getElementById(`stat-${key}`);
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    // Refresh posts list
    async refreshPostsList() {
        const postsContainer = document.getElementById('postsList');
        if (!postsContainer) return;
        
        try {
            const response = await fetch('../api/posts.php?action=list');
            const result = await response.json();
            
            if (result.success) {
                this.renderPostsList(result.posts);
            }
        } catch (error) {
            console.error('Failed to refresh posts:', error);
        }
    }

    // Update renderPostsList to use unified renderPostCard
    renderPostsList(posts) {
        const container = document.getElementById('postsList');
        if (!container) return;
        let html = '';
        posts.forEach(post => {
            html += this.renderPostCard(post, 'list');
        });
        container.innerHTML = html;
    }

    // Unified render function for post cards
    renderPostCard(post, context = 'list') {
        if (context === 'view') {
            // Modal view: more details, full content
            return `
                <div class="post-meta" style="margin-bottom: 1rem; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;">
                    <span>${post.created_at ? `Created: ${post.created_at}` : ''}</span>
                    <span class="post-status status-${post.status}">${post.status ? post.status.charAt(0).toUpperCase() + post.status.slice(1) : ''}</span>
                    <span>${post.updated_at ? `Updated: ${post.updated_at}` : ''}</span>
                    <span>${post.comment_count !== undefined ? `${post.comment_count} comment(s)` : ''}</span>
                </div>
                <h5 class="card-title">${post.title}</h5>
                <div class="post-content" style="margin-top: 1rem;">${post.content ? post.content.replace(/\n/g, '<br>') : ''}</div>
            `;
        } else {
            // List view: card style, excerpt, actions
            return `
                <div class="post-card">
                    <div class="card-body">
                        <div class="post-meta">
                            <span>${post.created_at ? post.created_at : ''}</span>
                            <span class="post-status status-${post.status}">${post.status ? post.status.charAt(0).toUpperCase() + post.status.slice(1) : ''}</span>
                        </div>
                        <h5 class="card-title">${post.title}</h5>
                        <p>${post.excerpt || ''}</p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                            <small style="color: #6c757d;">
                                ${post.comment_count !== undefined ? `${post.comment_count} comment(s)` : ''}
                            </small>
                            <div class="post-actions" style="display: flex; gap: 0.5rem;">
                                <a href="#" onclick="viewPost(${post.id})" class="btn btn-sm btn-primary">View</a>
                                <a href="#" onclick="editPost(${post.id})" class="btn btn-sm btn-secondary">Edit</a>
                                <button class="btn btn-sm btn-danger delete-btn" 
                                        data-post-id="${post.id}" 
                                        data-url="api/posts.php"
                                        data-confirm="Are you sure you want to delete '${post.title}'?">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    // Update viewPost to use unified renderPostCard
    async viewPost(postId) {
        try {
            const response = await fetch(`../api/posts.php?action=get&id=${postId}`);
            const result = await response.json();
            if (result.success) {
                const post = result.post;
                document.getElementById('viewPostTitle').textContent = post.title;
                document.getElementById('viewPostContent').innerHTML = this.renderPostCard(post, 'view');
                document.getElementById('editPostFromView').onclick = () => {
                    window.blogApp.closeModal();
                    this.editPost(postId);
                };
                window.blogApp.openModal('viewPostModal');
            } else {
                this.showAlert('error', result.message);
            }
        } catch (error) {
            this.showAlert('error', 'Failed to load post');
        }
    }

    // Comments handling
    async refreshComments(postId) {
        const commentsContainer = document.getElementById('commentsContainer');
        if (!commentsContainer || !postId) return;
        
        try {
            const response = await fetch(`api/comments.php?post_id=${postId}`);
            const result = await response.json();
            
            if (result.success) {
                this.renderComments(result.comments);
            }
        } catch (error) {
            console.error('Failed to refresh comments:', error);
        }
    }

    renderComments(comments) {
        const container = document.getElementById('commentsContainer');
        if (!container) return;
        
        let html = '';
        comments.forEach(comment => {
            html += `
                <div class="comment fade-in">
                    <div class="comment-author">${comment.author}</div>
                    <div class="comment-date">${comment.date}</div>
                    <p>${comment.content}</p>
                    <div class="comment-actions">
                        <button class="btn btn-sm btn-danger delete-btn" 
                                data-comment-id="${comment.id}"
                                data-post-id="${comment.post_id}"
                                data-url="api/comments.php"
                                data-confirm="Delete this comment?">
                            Delete
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    // Alert system
    showAlert(type, message, duration = 5000) {
        const alertsContainer = this.getAlertsContainer();
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} fade-in`;
        alertDiv.innerHTML = `
            <span>${message}</span>
            <button class="close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        alertsContainer.appendChild(alertDiv);
        
        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, duration);
        }
    }

    getAlertsContainer() {
        let container = document.getElementById('alertsContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'alertsContainer';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            container.style.maxWidth = '400px';
            document.body.appendChild(container);
        }
        return container;
    }

    autoDismissAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        });
    }

    // Utility functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Form validation
    validateForm(form) {
        const errors = {};
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                errors[field.name] = 'This field is required';
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });
        
        // Email validation
        const emailFields = form.querySelectorAll('[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                errors[field.name] = 'Please enter a valid email address';
                field.classList.add('error');
            }
        });
        
        // Password validation
        const passwordFields = form.querySelectorAll('[type="password"]');
        passwordFields.forEach(field => {
            if (field.value && field.value.length < 6) {
                errors[field.name] = 'Password must be at least 6 characters long';
                field.classList.add('error');
            }
        });
        
        return Object.keys(errors).length === 0 ? null : errors;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Auto-save functionality for forms
    enableAutoSave(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', this.debounce(() => {
                this.autoSave(form);
            }, 2000));
        });
    }

    async autoSave(form) {
        const formData = new FormData(form);
        formData.append('auto_save', '1');
        
        try {
            await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData
            });
            
            this.showAutoSaveIndicator();
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }

    showAutoSaveIndicator() {
        const indicator = document.getElementById('autoSaveIndicator');
        if (indicator) {
            indicator.textContent = 'Saved';
            indicator.style.opacity = '1';
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        }
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.blogApp = new BlogApp();
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + S for save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const form = document.querySelector('form[data-ajax]');
        if (form) {
            form.dispatchEvent(new Event('submit'));
        }
    }
    
    // ESC to close modals
    if (e.key === 'Escape') {
        window.blogApp.closeModal();
    }
});

// Export for use in other scripts
window.BlogApp = BlogApp;