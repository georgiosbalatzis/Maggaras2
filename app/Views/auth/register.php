
<?php
// ========== auth/register.php ==========
?>
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<section class="auth-form">
    <h2>Εγγραφή</h2>
    <form action="/index.php?page=register" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label for="username">Όνομα Χρήστη:</label>
        <input type="text" id="username" name="username" minlength="3" maxlength="50" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" maxlength="100" required>

        <label for="password">Κωδικός Πρόσβασης:</label>
        <input type="password" id="password" name="password" minlength="8" required>

        <button type="submit">Εγγραφή</button>
    </form>
    <p>Έχετε ήδη λογαριασμό; <a href="/index.php?page=login">Συνδεθείτε εδώ</a>.</p>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>