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
                                <small class="text-muted"> @${comment.username}</small>
                                <p class="mb-0">${comment.content}</p>
                                <small class="text-muted">${comment.created_at}</small>
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
