/**
 * @file main.js
 * @brief Κύριο αρχείο JavaScript για την εφαρμογή.
 *
 * Περιλαμβάνει λογική για επικύρωση φόρμας,
 * λειτουργίες AJAX για likes και σχόλια,
 * και δυναμικές ενημερώσεις UI.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Επικύρωση φόρμας εγγραφής (παράδειγμα) [28, 29, 30]
    const registerForm = document.querySelector('.auth-form form');
    if (registerForm && registerForm.action.includes('page=register')) {
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        // Παράδειγμα επικύρωσης σε πραγματικό χρόνο
        emailInput.addEventListener('input', function() {
            if (emailInput.validity.typeMismatch) {
                emailInput.setCustomValidity('Παρακαλώ εισάγετε μια έγκυρη διεύθυνση email.');
            } else {
                emailInput.setCustomValidity('');
            }
        });

        passwordInput.addEventListener('input', function() {
            // Πιο σύνθετη επικύρωση κωδικού πρόσβασης με regex [30]
            const passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/;
            if (passwordInput.value.length < 6) {
                passwordInput.setCustomValidity('Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 6 χαρακτήρες.');
            } else if (!passwordRegex.test(passwordInput.value)) {
                passwordInput.setCustomValidity('Ο κωδικός πρόσβασης πρέπει να περιέχει τουλάχιστον έναν αριθμό, ένα μικρό γράμμα, ένα κεφαλαίο γράμμα και έναν ειδικό χαρακτήρα.');
            }
            else {
                passwordInput.setCustomValidity('');
            }
        });

        registerForm.addEventListener('submit', function(event) {
            // Εδώ μπορείτε να προσθέσετε πρόσθετη λογική επικύρωσης πριν την υποβολή
            if (!registerForm.checkValidity()) {
                event.preventDefault(); // Σταματήστε την υποβολή αν η φόρμα δεν είναι έγκυρη
                alert('Παρακαλώ συμπληρώστε σωστά όλα τα υποχρεωτικά πεδία.');
            }
        });
    }

    // Λειτουργία Like (AJAX) [28, 7, 31]
    const likeButton = document.getElementById('like-button');
    if (likeButton) {
        likeButton.addEventListener('click', async function() {
            const recipeId = this.dataset.recipeId;
            const currentLikesSpan = document.getElementById('like-count');
            let currentLikes = parseInt(currentLikesSpan.textContent);

            try {
                const response = await fetch('/index.php?page=api&action=like', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ recipe_id: recipeId })
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
                    alert(data.message);
                }
            } catch (error) {
                console.error('Σφάλμα AJAX για like:', error);
                alert('Σφάλμα κατά την επεξεργασία του like. Παρακαλώ δοκιμάστε ξανά.');
            }
        });
    }

    // Υποβολή Σχολίου (AJAX) [28, 7, 31]
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

            try {
                const response = await fetch('/index.php?page=api&action=comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ recipe_id: recipeId, comment_text: commentText })
                });
                const data = await response.json();

                if (data.success) {
                    const commentsList = document.getElementById('comments-list');
                    // Δημιουργία νέου στοιχείου σχολίου
                    const newCommentDiv = document.createElement('div');
                    newCommentDiv.classList.add('comment-item');
                    newCommentDiv.innerHTML = `
                        <p class="comment-author"><strong>${data.comment.username}</strong> <span class="comment-date">${data.comment.created_at}</span></p>
                        <p class="comment-text">${data.comment.comment_text.replace(/\n/g, '<br>')}</p>
                    `;
                    // Προσθήκη του νέου σχολίου στην αρχή της λίστας
                    commentsList.prepend(newCommentDiv);
                    commentTextInput.value = ''; // Καθαρισμός του πεδίου κειμένου
                    // Αφαιρέστε το μήνυμα "Δεν υπάρχουν σχόλια ακόμα" αν υπάρχει
                    const noCommentsMessage = commentsList.querySelector('p:last-child');
                    if (noCommentsMessage && noCommentsMessage.textContent.includes('Δεν υπάρχουν σχόλια ακόμα')) {
                        noCommentsMessage.remove();
                    }
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Σφάλμα AJAX για σχόλιο:', error);
                alert('Σφάλμα κατά την υποβολή σχολίου. Παρακαλώ δοκιμάστε ξανά.');
            }
        });
    }
});