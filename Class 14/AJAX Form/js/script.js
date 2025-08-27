document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');
    const alertContainer = document.getElementById('alertContainer');
    const usersTableBody = document.getElementById('usersTableBody');
    const userCount = document.getElementById('userCount');
    const noUsersMessage = document.getElementById('noUsersMessage');

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        showLoading(true);
        clearAlert();
        
        // Get form data
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch('ajax/add_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                showAlert(data.message, 'success');
                form.reset();
                addUserToTable(data.user);
                updateUserCount();
                scrollToTop();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            showLoading(false);
            showAlert('An error occurred. Please try again.', 'error');
            console.error(error);
        });
    });
    
    // Show/hide loading state
    function showLoading(loading) {
        if (loading) {
            submitBtn.innerHTML = '<span class="loading"></span> Adding User...';
            submitBtn.disabled = true;
        } else {
            submitBtn.innerHTML = 'Add User';
            submitBtn.disabled = false;
        }
    }
    
    // Show alert message
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alertDiv);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 300);
            }, 5000);
        }
    }
    
    // Clear alert message
    function clearAlert() {
        alertContainer.innerHTML = '';
    }
    
    // Add user to table
    function addUserToTable(user) {
        // Hide "no users" message if it exists
        if (noUsersMessage) {
            noUsersMessage.style.display = 'none';
        }
        
        // Create new table row
        const row = document.createElement('tr');
        row.className = 'new-user-row';
        
        // Format date
        const date = new Date(user.created_at);
        const formattedDate = date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.phone)}</td>
            <td>${escapeHtml(user.address)}</td>
            <td>${formattedDate}</td>
        `;
        
        // Add row to top of table
        usersTableBody.insertBefore(row, usersTableBody.firstChild);
        
        // Add highlight animation
        setTimeout(() => {
            row.classList.add('highlight');
        }, 100);
        
        // Remove highlight after animation
        setTimeout(() => {
            row.classList.remove('new-user-row', 'highlight');
        }, 2000);
    }
    
    // Update user count
    function updateUserCount() {
        const currentCount = parseInt(userCount.textContent);
        userCount.textContent = currentCount + 1;
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Smooth scroll to top
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // Add form validation enhancement
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Clear previous error styling
            this.style.borderColor = '#404040';
        });
    });
    
    // Field validation function
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        
        // Check if required field is empty
        if (field.hasAttribute('required') && !value) {
            isValid = false;
        }
        
        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
            }
        }
        
        // Phone validation (basic)
        if (field.name === 'phone' && value) {
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phoneRegex.test(value) || value.length < 10) {
                isValid = false;
            }
        }
        
        // Apply styling based on validation
        if (!isValid) {
            field.style.borderColor = '#ff4444';
        } else {
            field.style.borderColor = '#666666';
        }
        
        return isValid;
    }
    
    // Enhanced form submission validation
    form.addEventListener('submit', function(e) {
        let allValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                allValid = false;
            }
        });
        
        if (!allValid) {
            e.preventDefault();
            showAlert('Please fix the highlighted fields before submitting.', 'error');
            return;
        }
    });
    
    // Auto-resize textarea
    const textarea = document.getElementById('address');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter or Cmd+Enter to submit form
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
                form.dispatchEvent(new Event('submit'));
            }
        }
        
        // Escape to clear form
        if (e.key === 'Escape') {
            form.reset();
            clearAlert();
            inputs.forEach(input => {
                input.style.borderColor = '#404040';
            });
        }
    });
    
    // Add table row hover effects enhancement
    if (usersTableBody) {
        usersTableBody.addEventListener('mouseover', function(e) {
            if (e.target.closest('tr')) {
                const row = e.target.closest('tr');
                row.style.transform = 'scale(1.01)';
                row.style.transition = 'transform 0.2s ease';
            }
        });
        
        usersTableBody.addEventListener('mouseout', function(e) {
            if (e.target.closest('tr')) {
                const row = e.target.closest('tr');
                row.style.transform = 'scale(1)';
            }
        });
    }
    
    // Add CSS for highlight animation
    const style = document.createElement('style');
    style.textContent = `
        .new-user-row {
            background: linear-gradient(90deg, #2a4a2a, #1f3f1f) !important;
            animation: slideIn 0.5s ease-out;
        }
        
        .highlight {
            box-shadow: 0 0 20px rgba(144, 238, 144, 0.3);
            border-left: 4px solid #90ee90;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
});