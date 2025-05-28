/**
 * @file main.js
 * @brief Κύριο αρχείο JavaScript για την εφαρμογή.
 *
 * Περιλαμβάνει λογική για επικύρωση φόρμας,
 * λειτουργίες AJAX για likes και σχόλια,
 * και δυναμικές ενημερώσεις UI.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag or session storage
    function getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.content : (window.csrfToken || '');
    }

    // Set CSRF token if available
    if (typeof window.csrfToken !== 'undefined') {
        sessionStorage.setItem('csrf_token', window.csrfToken);
    }

    // Form validation for registration
    const registerForm = document.querySelector('.auth-form form');
    if (registerForm && registerForm.action.includes('page=register')) {
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        // Real-time email validation
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (emailInput.validity.typeMismatch) {
                    emailInput.setCustomValidity('Παρακαλώ εισάγετε μια έγκυρη διεύθυνση email.');
                } else {
                    emailInput.setCustomValidity('');
                }
            });
        }

        // Password validation
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                if (passwordInput.value.length < 8) {
                    passwordInput.setCustomValidity('Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 8 χαρακτήρες.');
                } else {
                    passwordInput.setCustomValidity('');
                }
            });
        }

        // Username validation
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                if (usernameInput.value.length < 3) {
                    usernameInput.setCustomValidity('Το όνομα χρήστη πρέπει να έχει τουλάχιστον 3 χαρακτήρες.');
                } else if (usernameInput.value.length > 50) {
                    usernameInput.setCustomValidity('Το όνομα χρήστη δεν μπορεί να υπερβαίνει τους 50 χαρακτήρες.');
                } else {
                    usernameInput.setCustomValidity('');
                }
            });
        }

        // Form submission validation
        registerForm.addEventListener('submit', function(event) {
            if (!registerForm.checkValidity()) {
                event.preventDefault();
                alert('Παρακαλώ συμπληρώστε σωστά όλα τα υποχρεωτικά πεδία.');
            }
        });
    }

    // Like functionality (AJAX)
    const likeButton = document.getElementById('like-button');
    if (likeButton) {
        likeButton.addEventListener('click', async function() {
            const recipeId = this.dataset.recipeId;
            const currentLikesSpan = document.getElementById('like-count');

            try {
                const csrfToken = sessionStorage.getItem('csrf_token') || '';
                const response = await fetch('/index.php?page=api&action=like', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ recipe_id: parseInt(recipeId) })
                });

                const data = await response.json();

                if (data.success) {
                    currentLikesSpan.textContent = data.count;
                    if (data.action === 'liked') {
                        likeButton.classList.add('liked');
                        likeButton.textContent = 'Μου αρέσει!';
                    } else {
                        likeButton.classList.remove('liked');
                        likeButton.textContent = 'Μου αρέσει';
                    }
                } else {
                    alert(data.message || 'Σφάλμα κατά την επεξεργασία του like.');
                }
            } catch (error) {
                console.error('Σφάλμα AJAX για like:', error);
                alert('Σφάλμα κατά την επεξεργασία του like. Παρακαλώ δοκιμάστε ξανά.');
            }
        });
    }

    // Comment submission (AJAX)
    const submitCommentButton = document.getElementById('submit-comment');
    if (submitCommentButton) {
        submitCommentButton.addEventListener('click', async function() {
            const recipeId = this.dataset.recipeId;
            const commentTextInput = document.getElementById('comment-text');
            const commentText = commentTextInput.value.trim();

            if (commentText === '') {
                alert('Το σχόλιο δεν μπορεί να είναι κενό.');
                return;
            }

            if (commentText.length > 1000) {
                alert('Το σχόλιο είναι πολύ μεγάλο (μέγιστο 1000 χαρακτήρες).');
                return;
            }

            try {
                const csrfToken = sessionStorage.getItem('csrf_token') || '';
                const response = await fetch('/index.php?page=api&action=comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        recipe_id: parseInt(recipeId),
                        comment_text: commentText
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const commentsList = document.getElementById('comments-list');

                    // Remove "no comments" message if exists
                    const noCommentsMessage = document.getElementById('no-comments-message');
                    if (noCommentsMessage) {
                        noCommentsMessage.remove();
                    }

                    // Create new comment element
                    const newCommentDiv = document.createElement('div');
                    newCommentDiv.classList.add('comment-item');

                    // Safely escape and display comment
                    const commentAuthor = document.createElement('p');
                    commentAuthor.className = 'comment-author';

                    const strongElement = document.createElement('strong');
                    strongElement.textContent = data.comment.username;
                    commentAuthor.appendChild(strongElement);

                    const dateSpan = document.createElement('span');
                    dateSpan.className = 'comment-date';
                    dateSpan.textContent = ' ' + data.comment.created_at;
                    commentAuthor.appendChild(dateSpan);

                    const commentTextP = document.createElement('p');
                    commentTextP.className = 'comment-text';
                    commentTextP.textContent = data.comment.comment_text;
                    // Convert newlines to <br> safely
                    commentTextP.innerHTML = commentTextP.innerHTML.replace(/\n/g, '<br>');

                    newCommentDiv.appendChild(commentAuthor);
                    newCommentDiv.appendChild(commentTextP);

                    // Add new comment at the beginning
                    commentsList.insertBefore(newCommentDiv, commentsList.firstChild);

                    // Clear the textarea
                    commentTextInput.value = '';
                } else {
                    alert(data.message || 'Σφάλμα κατά την υποβολή σχολίου.');
                }
            } catch (error) {
                console.error('Σφάλμα AJAX για σχόλιο:', error);
                alert('Σφάλμα κατά την υποβολή σχολίου. Παρακαλώ δοκιμάστε ξανά.');
            }
        });
    }

    // Add CSRF token to page for AJAX requests
    const csrfMeta = document.createElement('meta');
    csrfMeta.name = 'csrf-token';
    csrfMeta.content = sessionStorage.getItem('csrf_token') || '';
    document.head.appendChild(csrfMeta);
});

// Store CSRF token when page loads (to be set by PHP)
window.csrfToken = window.csrfToken || '';