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
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes

    /**
     * Κατασκευαστής του AuthController.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }

    /**
     * Check if user is locked out due to too many failed attempts
     */
    private function isLockedOut(): bool {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt'] = time();
            return false;
        }

        if ($_SESSION['login_attempts'] >= $this->maxLoginAttempts) {
            if (time() - $_SESSION['last_attempt'] < $this->lockoutTime) {
                return true;
            } else {
                $_SESSION['login_attempts'] = 0;
                return false;
            }
        }
        return false;
    }

    /**
     * Validate CSRF token
     */
    private function validateCSRF(): bool {
        return isset($_POST['csrf_token']) &&
            isset($_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }

    /**
     * Generate new CSRF token
     */
    private function generateCSRFToken(): void {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Εμφανίζει τη φόρμα εγγραφής.
     */
    public function showRegisterForm() {
        $this->generateCSRFToken();
        require_once __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας εγγραφής.
     */
    public function register() {
        if (!$this->validateCSRF()) {
            $error = "Μη έγκυρο CSRF token. Παρακαλώ ανανεώστε τη σελίδα.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
            $error = "Όλα τα πεδία είναι υποχρεωτικά.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];

        // Validate username length
        if (strlen($username) < 3 || strlen($username) > 50) {
            $error = "Το όνομα χρήστη πρέπει να είναι μεταξύ 3 και 50 χαρακτήρων.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        if (!$email) {
            $error = "Μη έγκυρη διεύθυνση email.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        // Validate password strength
        if (strlen($password) < 8) {
            $error = "Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 8 χαρακτήρες.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        // Check if username exists
        if ($this->userModel->findUserByUsername($username)) {
            $error = "Το όνομα χρήστη υπάρχει ήδη.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        // Check if email exists
        if ($this->userModel->findUserByEmail($email)) {
            $error = "Το email υπάρχει ήδη.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
            return;
        }

        if ($this->userModel->createUser($username, $email, $password)) {
            $success = "Η εγγραφή ήταν επιτυχής! Μπορείτε τώρα να συνδεθείτε.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/login.php';
        } else {
            $error = "Σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/register.php';
        }
    }

    /**
     * Εμφανίζει τη φόρμα σύνδεσης.
     */
    public function showLoginForm() {
        $this->generateCSRFToken();
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας σύνδεσης.
     */
    public function login() {
        if ($this->isLockedOut()) {
            $error = "Πολλές αποτυχημένες προσπάθειες. Παρακαλώ δοκιμάστε ξανά σε 15 λεπτά.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/login.php';
            return;
        }

        if (!$this->validateCSRF()) {
            $error = "Μη έγκυρο CSRF token. Παρακαλώ ανανεώστε τη σελίδα.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/login.php';
            return;
        }

        if (empty($_POST['username']) || empty($_POST['password'])) {
            $error = "Παρακαλώ εισάγετε όνομα χρήστη και κωδικό πρόσβασης.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/login.php';
            return;
        }

        $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $_POST['password'];

        $user = $this->userModel->findUserByUsername($username);

        if ($user && $this->userModel->verifyPassword($password, $user['password_hash'])) {
            // Reset login attempts on successful login
            $_SESSION['login_attempts'] = 0;

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['loggedin'] = true;

            // Generate new CSRF token for authenticated session
            $this->generateCSRFToken();

            header('Location: index.php?page=recipes&action=list');
            exit();
        } else {
            // Increment failed login attempts
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();

            $error = "Λάθος όνομα χρήστη ή/και κωδικός πρόσβασης.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/auth/login.php';
        }
    }

    /**
     * Χειρίζεται την αποσύνδεση του χρήστη.
     */
    public function logout() {
        $_SESSION = array();

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        header('Location: index.php?page=login');
        exit();
    }
}