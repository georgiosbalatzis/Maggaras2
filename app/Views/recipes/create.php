<?php
// ========== recipes/create.php ==========
?>
<?php require_once __DIR__ . '/../layout/header.php'; ?>

    <section class="recipe-form">
        <h2>Δημιουργία Νέας Συνταγής</h2>
        <form action="/index.php?page=recipes&action=create" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="title">Τίτλος Συνταγής:</label>
            <input type="text" id="title" name="title" maxlength="255" required>

            <label for="description">Περιγραφή:</label>
            <textarea id="description" name="description" rows="5" required></textarea>

            <label for="prep_time">Χρόνος Προετοιμασίας:</label>
            <input type="text" id="prep_time" name="prep_time" maxlength="50">

            <label for="cook_time">Χρόνος Μαγειρέματος:</label>
            <input type="text" id="cook_time" name="cook_time" maxlength="50">

            <label for="servings">Μερίδες:</label>
            <input type="text" id="servings" name="servings" maxlength="50">

            <label for="main_image">Κύρια Εικόνα Συνταγής:</label>
            <input type="file" id="main_image" name="main_image" accept="image/jpeg, image/png, image/gif">

            <h3>Συστατικά:</h3>
            <div id="ingredients-container">
                <div class="ingredient-item">
                    <input type="text" name="ingredient_name[]" placeholder="Όνομα Συστατικού" maxlength="100" required>
                    <input type="text" name="ingredient_quantity[]" placeholder="Ποσότητα" maxlength="50" required>
                    <input type="text" name="ingredient_unit[]" placeholder="Μονάδα (π.χ. φλιτζάνια)" maxlength="50">
                    <button type="button" class="remove-item">Αφαίρεση</button>
                </div>
            </div>
            <button type="button" id="add-ingredient">Προσθήκη Συστατικού</button>

            <h3>Οδηγίες:</h3>
            <div id="directions-container">
                <div class="direction-item">
                    <textarea name="direction_description[]" rows="2" placeholder="Βήμα οδηγίας" maxlength="2000" required></textarea>
                    <button type="button" class="remove-item">Αφαίρεση</button>
                </div>
            </div>
            <button type="button" id="add-direction">Προσθήκη Οδηγίας</button>

            <button type="submit">Δημιουργία Συνταγής</button>
        </form>
    </section>

    <script>
        // JavaScript για δυναμική προσθήκη/αφαίρεση πεδίων συστατικών και οδηγιών
        document.getElementById('add-ingredient').addEventListener('click', function() {
            const container = document.getElementById('ingredients-container');
            const newItem = document.createElement('div');
            newItem.classList.add('ingredient-item');
            newItem.innerHTML = `
                <input type="text" name="ingredient_name[]" placeholder="Όνομα Συστατικού" maxlength="100" required>
                <input type="text" name="ingredient_quantity[]" placeholder="Ποσότητα" maxlength="50" required>
                <input type="text" name="ingredient_unit[]" placeholder="Μονάδα (π.χ. φλιτζάνια)" maxlength="50">
                <button type="button" class="remove-item">Αφαίρεση</button>
            `;
            container.appendChild(newItem);
        });

        document.getElementById('ingredients-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                const items = this.querySelectorAll('.ingredient-item');
                if (items.length > 1) {
                    e.target.closest('.ingredient-item').remove();
                } else {
                    alert('Πρέπει να υπάρχει τουλάχιστον ένα συστατικό.');
                }
            }
        });

        document.getElementById('add-direction').addEventListener('click', function() {
            const container = document.getElementById('directions-container');
            const newItem = document.createElement('div');
            newItem.classList.add('direction-item');
            newItem.innerHTML = `
                <textarea name="direction_description[]" rows="2" placeholder="Βήμα οδηγίας" maxlength="2000" required></textarea>
                <button type="button" class="remove-item">Αφαίρεση</button>
            `;
            container.appendChild(newItem);
        });

        document.getElementById('directions-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                const items = this.querySelectorAll('.direction-item');
                if (items.length > 1) {
                    e.target.closest('.direction-item').remove();
                } else {
                    alert('Πρέπει να υπάρχει τουλάχιστον μία οδηγία.');
                }
            }
        });
    </script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>