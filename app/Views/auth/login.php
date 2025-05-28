<?php require_once __DIR__. '/../layout/header.php';?>

    <section class="auth-form">
        <h2>Σύνδεση</h2>
        <form action="/index.php?page=login" method="POST">
            <label for="username">Όνομα Χρήστη:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Κωδικός Πρόσβασης:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Σύνδεση</button>
        </form>
        <p>Δεν έχετε λογαριασμό; <a href="/index.php?page=register">Εγγραφείτε εδώ</a>.</p>
    </section>

<?php require_once __DIR__. '/../layout/footer.php';?>