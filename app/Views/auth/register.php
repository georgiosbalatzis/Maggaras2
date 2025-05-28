<?php require_once __DIR__. '/../layout/header.php';?>

    <section class="auth-form">
        <h2>Εγγραφή</h2>
        <form action="/index.php?page=register" method="POST">
            <label for="username">Όνομα Χρήστη:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Κωδικός Πρόσβασης:</label>
            <input type="password" id="password" name="password" required minlength="6">

            <button type="submit">Εγγραφή</button>
        </form>
        <p>Έχετε ήδη λογαριασμό; <a href="/index.php?page=login">Συνδεθείτε εδώ</a>.</p>
    </section>

<?php require_once __DIR__. '/../layout/footer.php';?>