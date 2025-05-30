<?php
// ========== recipes/view.php ==========
?>
<?php require_once __DIR__ . '/../layout/header.php'; ?>

    <section class="recipe-details">
        <h2><?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="recipe-meta">
            Από: <?php echo htmlspecialchars($recipe['username'], ENT_QUOTES, 'UTF-8'); ?> |
            Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($recipe['created_at'])); ?>
        </p>

        <?php if ($recipe['main_image_path']): ?>
            <img src="/index.php?page=recipes&action=serve_image&filename=<?php echo urlencode($recipe['main_image_path']); ?>"
                 alt="<?php echo htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?>"
                 class="recipe-main-image">
        <?php endif; ?>

        <div class="recipe-info">
            <p><strong>Περιγραφή:</strong> <?php echo nl2br(htmlspecialchars($recipe['description'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php if ($recipe['prep_time']): ?>
                <p><strong>Χρόνος Προετοιμασίας:</strong> <?php echo htmlspecialchars($recipe['prep_time'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if ($recipe['cook_time']): ?>
                <p><strong>Χρόνος Μαγειρέματος:</strong> <?php echo htmlspecialchars($recipe['cook_time'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if ($recipe['servings']): ?>
                <p><strong>Μερίδες:</strong> <?php echo htmlspecialchars($recipe['servings'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>

        <h3>Συστατικά:</h3>
        <?php if (!empty($recipe['ingredients'])): ?>
            <ul>
                <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                    <li>
                        <?php echo htmlspecialchars($ingredient['quantity'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php echo htmlspecialchars($ingredient['unit'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php echo htmlspecialchars($ingredient['ingredient_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Δεν έχουν καταχωρηθεί συστατικά.</p>
        <?php endif; ?>

        <h3>Οδηγίες:</h3>
        <?php if (!empty($recipe['directions'])): ?>
            <ol>
                <?php foreach ($recipe['directions'] as $direction): ?>
                    <li><?php echo nl2br(htmlspecialchars($direction['description'], ENT_QUOTES, 'UTF-8')); ?></li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p>Δεν έχουν καταχωρηθεί οδηγίες.</p>
        <?php endif; ?>

        <div class="recipe-actions">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $recipe['user_id']): ?>
                <a href="/index.php?page=recipes&action=edit&id=<?php echo $recipe['recipe_id']; ?>" class="button">Επεξεργασία</a>
                <a href="/index.php?page=recipes&action=delete&id=<?php echo $recipe['recipe_id']; ?>&csrf_token=<?php echo urlencode($_SESSION['csrf_token'] ?? ''); ?>"
                   class="button delete-button"
                   onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή τη συνταγή;');">Διαγραφή</a>
            <?php endif; ?>

            <div class="like-section">
                <button id="like-button"
                        data-recipe-id="<?php echo $recipe['recipe_id']; ?>"
                        class="<?php echo $hasLiked ? 'liked' : ''; ?>">
                    <?php echo $hasLiked ? 'Μου αρέσει!' : 'Μου αρέσει'; ?>
                </button>
                <span id="like-count"><?php echo (int)$likeCount; ?></span> Likes
            </div>
        </div>

        <hr>

        <h3>Σχόλια:</h3>
        <?php if ($isLoggedIn): ?>
            <div class="comment-form">
                <textarea id="comment-text" placeholder="Προσθέστε ένα σχόλιο..." rows="3" maxlength="1000"></textarea>
                <button id="submit-comment" data-recipe-id="<?php echo $recipe['recipe_id']; ?>">Υποβολή Σχολίου</button>
            </div>
        <?php else: ?>
            <p>Παρακαλώ <a href="/index.php?page=login">συνδεθείτε</a> για να σχολιάσετε.</p>
        <?php endif; ?>

        <div id="comments-list" class="comments-list">
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <p class="comment-author">
                            <strong><?php echo htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                        </p>
                        <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p id="no-comments-message">Δεν υπάρχουν σχόλια ακόμα. Γίνετε ο πρώτος που θα σχολιάσει!</p>
            <?php endif; ?>
        </div>
    </section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>