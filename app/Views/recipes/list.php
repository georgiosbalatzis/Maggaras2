<?php require_once __DIR__. '/../layout/header.php';?>

    <section class="recipe-list">
        <h2>Όλες οι Συνταγές</h2>
        <?php if (empty($recipes)):?>
            <p>Δεν υπάρχουν συνταγές ακόμα. Γίνετε ο πρώτος που θα δημιουργήσει μία!</p>
        <?php else:?>
            <div class="recipes-grid">
                <?php foreach ($recipes as $recipe):?>
                    <div class="recipe-card">
                        <?php if ($recipe['main_image_path']):?>
                            <img src="/index.php?page=recipes&action=serve_image&filename=<?php echo htmlspecialchars($recipe['main_image_path']);?>" alt="<?php echo htmlspecialchars($recipe['title']);?>">
                        <?php else:?>
                            <img src="/img/placeholder.jpg" alt="Χωρίς εικόνα"> <?php endif;?>
                        <h3><a href="/index.php?page=recipes&action=view&id=<?php echo $recipe['recipe_id'];?>"><?php echo htmlspecialchars($recipe['title']);?></a></h3>
                        <p>Από: <?php echo htmlspecialchars($recipe['username']);?></p>
                        <p><?php echo nl2br(htmlspecialchars(substr($recipe['description'], 0, 100))). (strlen($recipe['description']) > 100? '...' : '');?></p>
                        <a href="/index.php?page=recipes&action=view&id=<?php echo $recipe['recipe_id'];?>" class="button">Προβολή Συνταγής</a>
                    </div>
                <?php endforeach;?>
            </div>
        <?php endif;?>
    </section>

<?php require_once __DIR__. '/../layout/footer.php';?>