<?php
/**
 * @file database.php
 * @brief Ρυθμίσεις σύνδεσης με τη βάση δεδομένων.
 *
 * Αυτό το αρχείο περιέχει τις παραμέτρους για τη σύνδεση με τη βάση δεδομένων MySQL
 * χρησιμοποιώντας PDO. Είναι κρίσιμο για την ασφάλεια να διατηρούνται αυτά τα διαπιστευτήρια
 * εκτός του web root σε ένα πραγματικό περιβάλλον παραγωγής.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'recipe_social_network');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Create new PDO connection with UTF-8 support
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Disable emulation for better security
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    // Log error securely
    error_log("Database connection error: " . $e->getMessage());

    // Display generic error message to user
    header('HTTP/1.1 503 Service Unavailable');
    die("Database connection error. Please try later.");
}