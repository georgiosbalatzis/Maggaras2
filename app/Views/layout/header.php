<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Κοινωνικό Δίκτυο Συνταγών</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header>
    <nav>
        <h1><a href="/index.php?page=recipes&action=list">Συνταγές</a></h1>
        <ul>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']):?>
                <li>Καλώς ήρθες, <?php echo htmlspecialchars($_SESSION['username']);?>!</li>
                <li><a href="/index.php?page=recipes&action=create">Δημιουργία Συνταγής</a></li>
                <li><a href="/index.php?page=logout">Αποσύνδεση</a></li>
            <?php else:?>
                <li><a href="/index.php?page=login">Σύνδεση</a></li>
                <li><a href="/index.php?page=register">Εγγραφή</a></li>
            <?php endif;?>
        </ul>
    </nav>
</header>
<main>
    <?php if (isset($error)):?>
        <p class="error-message"><?php echo htmlspecialchars($error);?></p>
    <?php endif;?>
    <?php if (isset($success)):?>
    <p class="success-message"><?php echo htmlspecialchars($success);?></p>
<?php endif;?>