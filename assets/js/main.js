document.addEventListener('DOMContentLoaded', function() {
    // Like post functionality
    document.querySelectorAll('.like-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            likePost(postId, this);
        });
    });
    
    // Load comments functionality
    document.querySelectorAll('.load-comments').forEach(function(button) {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            loadComments(postId);
        });
    });
    
    // Submit comment functionality
    document.querySelectorAll('.comment-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            const commentInput = this.querySelector('input');
            const comment = commentInput.value.trim();
            
            if (comment) {
                submitComment(postId, comment, commentInput);
            }
        });
    });

    // Share post functionality
    document.querySelectorAll('.share-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            sharePost(postId, this);
        });
    });

    // Initialize notification system
    initializeNotifications();
});

function likePost(postId, button) {
    fetch('ajax/like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const countElement = button.querySelector('.like-count');
            countElement.textContent = data.likes;
            
            if (data.liked) {
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-primary');
            } else {
                button.classList.remove('btn-primary');
                button.classList.add('btn-outline-primary');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function loadComments(postId) {
    const commentsContainer = document.getElementById('comments-' + postId);
    
    fetch('ajax/get_comments.php?post_id=' + postId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            let html = '';
            
            data.comments.forEach(comment => {
                html += `
                    <div class="comment">
                        <div class="d-flex">
                            <img src="assets/images/${comment.profile_pic}" class="rounded-circle me-2" width="32" alt="Profile">
                            <div>
                                <strong>${comment.full_name}</strong>
                                <small style="color: gray;"> @${comment.username}</small>
                                <p class="mb-0">${comment.content}</p>
                                <small style="color: gray;">${comment.created_at}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            commentsContainer.innerHTML = html;
        }
    })
    .catch(error => console.error('Error:', error));
}

function submitComment(postId, comment, inputElement) {
    fetch('ajax/add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId + '&content=' + encodeURIComponent(comment)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Clear input
            inputElement.value = '';
            
            // Update comment count
            const countElement = document.querySelector(`.comment-btn[data-post-id="${postId}"] .comment-count`);
            countElement.textContent = parseInt(countElement.textContent) + 1;
            
            // Load the comments
            loadComments(postId);
        }
    })
    .catch(error => console.error('Error:', error));
}

function sharePost(postId, button) {
    fetch('ajax/share_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const countElement = button.querySelector('.share-count');
            countElement.textContent = data.shares;
            
            if (data.shared) {
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-primary');
            } else {
                button.classList.remove('btn-primary');
                button.classList.add('btn-outline-primary');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function initializeNotifications() {
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (!notificationDropdown) return;

    // Update notifications every 30 seconds
    setInterval(updateNotifications, 30000);

    // Update notifications when dropdown is shown
    notificationDropdown.addEventListener('show.bs.dropdown', function() {
        updateNotifications();
    });

    // Mark notifications as read when clicking on them
    document.querySelectorAll('.notification-dropdown .dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            if (this.classList.contains('unread')) {
                this.classList.remove('unread');
                updateNotificationBadge();
            }
        });
    });
}

function updateNotifications() {
    fetch('ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const notificationList = document.querySelector('.notification-list');
                if (!notificationList) return;

                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<li><div class="dropdown-item text-muted">No notifications</div></li>';
                } else {
                    let html = '';
                    data.notifications.forEach(notif => {
                        html += `
                            <li>
                                <a class="dropdown-item ${notif.is_read ? '' : 'unread'}" href="notifications.php">
                                    <div class="notification-content">
                                        <div class="notification-text">${notif.message}</div>
                                        <small class="text-muted">${notif.created_at}</small>
                                    </div>
                                </a>
                            </li>
                        `;
                    });
                    notificationList.innerHTML = html;
                }

                // Update notification badge
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('#notificationDropdown .badge');
    if (count === undefined) {
        // If count is not provided, fetch it
        fetch('ajax/get_unread_notifications_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateBadgeVisibility(badge, data.count);
                }
            })
            .catch(error => console.error('Error:', error));
    } else {
        updateBadgeVisibility(badge, count);
    }
}

function updateBadgeVisibility(badge, count) {
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

function updateNavbarNotificationBadge() {
    fetch('ajax/get_unread_notifications_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('navbarNotificationBadge');
            if (!badge) return;
            if (data.status === 'success') {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}
