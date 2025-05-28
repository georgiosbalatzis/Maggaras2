<?php
/**
 * @file AuthController.php
 * @brief Ελεγκτής πιστοποίησης χρήστη.
 *
 * Χειρίζεται την εγγραφή, τη σύνδεση και την αποσύνδεση χρηστών.
 */

class AuthController {
    private $userModel;
    private $pdo;

    /**
     * Κατασκευαστής του AuthController.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }

    /**
     * Εμφανίζει τη φόρμα εγγραφής.
     */
    public function showRegisterForm() {
        require_once __DIR__. '/../Views/auth/register.php';
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας εγγραφής.
     */
    public function register() {
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
            $error = "Όλα τα πεδία είναι υποχρεωτικά.";
            require_once __DIR__. '/../Views/auth/register.php';
            return;
        }

        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING); // [13, 22, 14]
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL); // [13, 22, 14]
        $password = $_POST['password'];

        if (!$email) {
            $error = "Μη έγκυρη διεύθυνση email.";
            require_once __DIR__. '/../Views/auth/register.php';
            return;
        }

        // Ελέγξτε αν το όνομα χρήστη ή το email υπάρχει ήδη
        if ($this->userModel->findUserByUsername($username)) {
            $error = "Το όνομα χρήστη υπάρχει ήδη.";
            require_once __DIR__. '/../Views/auth/register.php';
            return;
        }
        // Ελέγξτε για email (πρέπει να προσθέσετε μια μέθοδο findUserByEmail στο μοντέλο User)
        // Για απλότητα, παραλείπεται εδώ, αλλά θα ήταν μια καλή προσθήκη.

        if ($this->userModel->createUser($username, $email, $password)) {
            $success = "Η εγγραφή ήταν επιτυχής! Μπορείτε τώρα να συνδεθείτε.";
            require_once __DIR__. '/../Views/auth/login.php';
        } else {
            $error = "Σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.";
            require_once __DIR__. '/../Views/auth/register.php';
        }
    }

    /**
     * Εμφανίζει τη φόρμα σύνδεσης.
     */
    public function showLoginForm() {
        require_once __DIR__. '/../Views/auth/login.php';
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας σύνδεσης.
     */
    public function login() {

        if (empty($_POST['username']) || empty($_POST['password'])) {
            $error = "Παρακαλώ εισάγετε όνομα χρήστη και κωδικό πρόσβασης.";
            require_once __DIR__. '/../Views/auth/login.php';
            return;
        }

        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $password = $_POST['password'];

        $user = $this->userModel->findUserByUsername($username);

        if ($user && $this->userModel->verifyPassword($password, $user['password_hash'])) {
            // Η σύνδεση ήταν επιτυχής
            session_regenerate_id(true); // Αποτροπή επιθέσεων session fixation [17, 8, 12, 9, 10]

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['loggedin'] = true;

            header('Location: index.php?page=recipes&action=list'); // Ανακατεύθυνση στην αρχική σελίδα ή στη λίστα συνταγών
            exit();
        } else {
            $error = "Λάθος όνομα χρήστη ή/και κωδικός πρόσβασης.";
            require_once __DIR__. '/../Views/auth/login.php';
        }
    }

    /**
     * Χειρίζεται την αποσύνδεση του χρήστη.
     */
    public function logout() {
        $_SESSION = array(); // Καθαρίστε όλες τις μεταβλητές συνόδου
        session_destroy(); // Καταστρέψτε τη σύνοδο [17, 8, 9, 10]
        header('Location: index.php?page=login'); // Ανακατεύθυνση στη σελίδα σύνδεσης
        exit();
    }
}