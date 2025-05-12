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
    if (typeof initializeNotifications === 'function') {
        initializeNotifications();
    }

    // Messenger mobile navigation
    if (typeof setupMessengerMobileNav === 'function') {
        setupMessengerMobileNav();
    }

    // Event delegation for friend links
    var chatList = document.getElementById('chatList');
    if (chatList) {
        chatList.addEventListener('click', function(e) {
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
    }

    // Back button (event delegation, since chat is loaded dynamically)
    var chatWindow = document.getElementById('chatWindow');
    if (chatWindow) {
        chatWindow.addEventListener('click', function(e) {
            if (e.target.classList.contains('back-btn-unique')) {
                if (window.innerWidth <= 768) {
                    document.getElementById('chatWindow').classList.remove('active');
                    document.getElementById('chatList').classList.remove('hide');
                }
            }
        });
    }

    // Initial load: if there's a friend_id in the URL, load that chat
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('friend_id')) {
        loadChat(urlParams.get('friend_id'));
        if (window.innerWidth <= 768) {
            var chatWindow = document.getElementById('chatWindow');
            var chatList = document.getElementById('chatList');
            if (chatWindow && chatList) {
                chatWindow.classList.add('active');
                chatList.classList.add('hide');
            }
        }
    }

    // Dropdown menu toggle for post-menu
    document.querySelectorAll('.post-menu .menu-trigger').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.post-menu').forEach(function(menu) { menu.classList.remove('open'); });
            this.closest('.post-menu').classList.toggle('open');
        });
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.post-menu').forEach(function(menu) { menu.classList.remove('open'); });
    });

    // Post menu dropdown actions
    document.querySelectorAll('.post-menu .dropdown-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            if (this.classList.contains('edit-post-btn')) {
                // Show edit modal (to be implemented)
                alert('Edit post ' + postId);
            } else if (this.classList.contains('delete-post-btn')) {
                if (confirm('Are you sure you want to delete this post?')) {
                    fetch('delete_post.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'post_id=' + postId
                    }).then(() => location.reload());
                }
            } else if (this.classList.contains('report-post-btn')) {
                if (confirm('Report this post for inappropriate content?')) {
                    fetch('ajax/report_post.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'post_id=' + postId
                    })
                    .then(response => response.json())
                    .then(data => alert(data.message));
                }
            }
        });
    });

    // Add edit modal to body if not present
    if (!document.getElementById('editPostModal')) {
        const modalHtml = `
        <div class="modal fade" id="editPostModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Edit Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="editPostForm">
                  <input type="hidden" name="post_id" id="editPostId">
                  <div class="mb-3">
                    <textarea class="form-control" name="content" id="editPostContent" rows="4" required></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
              </div>
            </div>
          </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Edit post button click
    $(document).on('click', '.edit-post-btn', function() {
        const postId = $(this).data('post-id');
        const card = $(this).closest('.card, .post');
        const postContent = card.find('.post-content p').first().text().trim();
        
        $('#editPostId').val(postId);
        $('#editPostContent').val(postContent);
        $('#editPostModal').modal('show');
    });

    // Edit post form submit
    $(document).on('submit', '#editPostForm', function(e) {
        e.preventDefault();
        const postId = $('#editPostId').val();
        const content = $('#editPostContent').val();
        
        $.ajax({
            url: 'edit_post.php',
            method: 'POST',
            data: { post_id: postId, content: content },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the post content in the DOM
                    $(`.edit-post-btn[data-post-id="${postId}"]`).closest('.card, .post')
                        .find('.post-content p').first().text(content);
                    $('#editPostModal').modal('hide');
                } else {
                    alert(response.message || 'Failed to update post.');
                }
            },
            error: function() {
                alert('An error occurred while updating the post.');
            }
        });
    });
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
                button.classList.remove('');
                button.classList.add('btn-primary');
            } else {
                button.classList.remove('btn-primary');
                button.classList.add('');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function renderComments(comments, postId, parent = false) {
    let html = '';
    comments.forEach(comment => {
        html += `<div class="comment${parent ? ' reply' : ''}" data-comment-id="${comment.comment_id}">
            <div class="comment-main">
                <img src="assets/images/${comment.profile_pic}" class="rounded-circle me-2" width="32" alt="Profile">
                <div class="comment-content">
                    <strong>${comment.full_name}</strong>
                    <small class="comment-handle"> @${comment.username}</small>
                    <p class="mb-0">${comment.content}</p>
                    <small class="comment-time">${comment.created_at}</small>
                    <div class="comment-actions">
                        <button class="reply-button" data-post-id="${postId}" data-parent-id="${comment.comment_id}">Reply</button>
                    </div>
                    <div class="reply-form" style="display:none;">
                        <form class="reply-form-inner" data-post-id="${postId}" data-parent-id="${comment.comment_id}">
                            <input type="text" class="form-control reply-input" placeholder="Write a reply..." required>
                            <button class="btn btn-outline btn-sm" type="submit">Send</button>
                        </form>
                    </div>
                </div>
            </div>`;
        if (comment.replies && comment.replies.length > 0) {
            html += `<button class="toggle-replies-btn" data-comment-id="${comment.comment_id}"><i class="fa-solid fa-reply"></i> (${comment.replies.length})</button>`;
            html += `<div class="replies-container" data-replies-for="${comment.comment_id}" style="display:none;">${renderComments(comment.replies, postId, true)}</div>`;
        }
        html += '</div>';
    });
    return html;
}

function loadComments(postId) {
    const commentsContainer = document.getElementById('comments-' + postId);
    fetch('ajax/get_comments.php?post_id=' + postId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            commentsContainer.innerHTML = renderComments(data.comments, postId);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Handle reply button click (show/hide reply form)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('reply-button')) {
        const commentDiv = e.target.closest('.comment');
        const replyForm = commentDiv.querySelector('.reply-form');
        replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
    }
});

// Handle reply form submission
document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('reply-form-inner')) {
        e.preventDefault();
        const form = e.target;
        const postId = form.getAttribute('data-post-id');
        const parentId = form.getAttribute('data-parent-id');
        const input = form.querySelector('.reply-input');
        const content = input.value.trim();
        if (content) {
            submitComment(postId, content, input, parentId);
        }
    }
});

function submitComment(postId, comment, inputElement, parentId = null) {
    let body = 'post_id=' + postId + '&content=' + encodeURIComponent(comment);
    if (parentId) {
        body += '&parent_id=' + parentId;
    }
    fetch('ajax/add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: body
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Clear input
            inputElement.value = '';
            // Hide reply form if it's a reply
            if (parentId) {
                inputElement.closest('.reply-form').style.display = 'none';
            }
            // Update comment count
            const countElement = document.querySelector(`.comment-btn[data-post-id="${postId}"] .comment-count`);
            if (countElement) countElement.textContent = parseInt(countElement.textContent) + 1;
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
    if (!badge) return;
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

function updateNavbarMessageBadge() {
    fetch('ajax/get_unread_messages_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('navbarMessageBadge');
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

// Update both badges periodically
setInterval(() => {
    updateNavbarNotificationBadge();
    updateNavbarMessageBadge();
}, 30000); // Update every 30 seconds

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

// Toggle replies show/hide
// Use event delegation for dynamically loaded comments
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toggle-replies-btn')) {
        const commentId = e.target.getAttribute('data-comment-id');
        const repliesDiv = document.querySelector(`.replies-container[data-replies-for="${commentId}"]`);
        if (repliesDiv) {
            if (repliesDiv.style.display === 'none') {
                repliesDiv.style.display = 'block';
                e.target.textContent = 'Hide replies';
            } else {
                repliesDiv.style.display = 'none';
                e.target.textContent = `View replies (${repliesDiv.children.length})`;
            }
        }
    }
});
