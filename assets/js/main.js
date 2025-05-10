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

    // Messenger mobile navigation
    setupMessengerMobileNav();

    // Event delegation for friend links
    document.getElementById('chatList').addEventListener('click', function(e) {
        const link = e.target.closest('.friend-link');
        if (link) {
            e.preventDefault();
            const url = link.getAttribute('href');
            const params = new URLSearchParams(url.split('?')[1]);
            const friendId = params.get('friend_id');
            loadChat(friendId);
            // Mobile slide-in
            if (window.innerWidth <= 768) {
                document.getElementById('chatWindow').classList.add('active');
                document.getElementById('chatList').classList.add('hide');
            }
        }
    });

    // Back button (event delegation, since chat is loaded dynamically)
    document.getElementById('chatWindow').addEventListener('click', function(e) {
        if (e.target.classList.contains('back-btn-unique')) {
            if (window.innerWidth <= 768) {
                document.getElementById('chatWindow').classList.remove('active');
                document.getElementById('chatList').classList.remove('hide');
            }
        }
    });

    // Initial load: if there's a friend_id in the URL, load that chat
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('friend_id')) {
        loadChat(urlParams.get('friend_id'));
        if (window.innerWidth <= 768) {
            document.getElementById('chatWindow').classList.add('active');
            document.getElementById('chatList').classList.add('hide');
        }
    }
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

// Messenger mobile navigation
function setupMessengerMobileNav() {
    const chatList = document.getElementById('chatList');
    const chatWindow = document.getElementById('chatWindow');
    const backBtn = document.getElementById('backBtn');
    if (!chatList || !chatWindow || !backBtn) return;

    // Use event delegation for friend links
    chatList.addEventListener('click', function(e) {
        const link = e.target.closest('.friend-link');
        if (link && window.innerWidth <= 768) {
            setTimeout(() => {
                chatWindow.classList.add('active');
                chatList.classList.add('hide');
            }, 50); // slight delay to allow navigation
        }
    });
    // Back button
    backBtn.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            chatWindow.classList.remove('active');
            chatList.classList.remove('hide');
        }
    });
}

// AJAX function to load chat
function loadChat(friendId) {
    fetch('ajax/get_chat.php?friend_id=' + friendId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('chatWindow').innerHTML = html;
            scrollChatToBottom();
            setupSendMessageAJAX(friendId);

            // Always add .active to chatWindow and .hide to chatList on mobile
            if (window.innerWidth <= 768) {
                document.getElementById('chatWindow').classList.add('active');
                document.getElementById('chatList').classList.add('hide');
            }
        });
}

// Scroll chat to bottom
function scrollChatToBottom() {
    const chatBox = document.getElementById('chat-box');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// AJAX send message
function setupSendMessageAJAX(friendId) {
    const form = document.getElementById('send-message-form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            loadChat(friendId); // Reload chat after sending
        });
        form.reset();
    });
}
