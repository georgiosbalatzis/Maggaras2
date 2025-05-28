<?php
// ========== layout/header.php ==========
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Κοινωνικό Δίκτυο Συνταγών</title>
    <link rel="stylesheet" href="/css/style.css">
    <?php if (isset($_SESSION['csrf_token'])): ?>
    <script>window.csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';</script>
    <?php endif; ?>
</head>
<body>
<header>
    <nav>
        <h1><a href="/index.php?page=recipes&action=list">Συνταγές</a></h1>
        <ul>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <li>Καλώς ήρθες, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?>!</li>
                <li><a href="/index.php?page=recipes&action=create">Δημιουργία Συνταγής</a></li>
                <li><a href="/index.php?page=logout">Αποσύνδεση</a></li>
            <?php else: ?>
                <li><a href="/index.php?page=login">Σύνδεση</a></li>
                <li><a href="/index.php?page=register">Εγγραφή</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main>
    <?php if (isset($error)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p class="success-message"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>