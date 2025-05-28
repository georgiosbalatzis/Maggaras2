<?php
// ========== User.php ==========
/**
 * @file User.php
 * @brief Μοντέλο χρήστη.
 *
 * Διαχειρίζεται τις λειτουργίες που σχετίζονται με τους χρήστες,
 * όπως η δημιουργία, η εύρεση και η επαλήθευση.
 */

class User {
    private $pdo;

    /**
     * Κατασκευαστής του μοντέλου User.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Δημιουργεί έναν νέο χρήστη στη βάση δεδομένων.
     * @param string $username Το όνομα χρήστη.
     * @param string $email Η διεύθυνση email.
     * @param string $password Ο απλός κωδικός πρόσβασης.
     * @return bool True αν η δημιουργία ήταν επιτυχής, false αλλιώς.
     */
    public function createUser(string $username, string $email, string $password): bool {
        try {
            // Hash password with secure algorithm
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            return $stmt->execute([$username, $email, $passwordHash]);
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Βρίσκει έναν χρήστη με βάση το όνομα χρήστη.
     * @param string $username Το όνομα χρήστη προς αναζήτηση.
     * @return array|false Τα δεδομένα του χρήστη αν βρεθεί, false αλλιώς.
     */
    public function findUserByUsername(string $username) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Βρίσκει έναν χρήστη με βάση το email.
     * @param string $email Το email προς αναζήτηση.
     * @return array|false Τα δεδομένα του χρήστη αν βρεθεί, false αλλιώς.
     */
    public function findUserByEmail(string $email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Επαληθεύει έναν κωδικό πρόσβασης έναντι ενός κατακερματισμένου κωδικού.
     * @param string $password Ο απλός κωδικός πρόσβασης.
     * @param string $hash Ο κατακερματισμένος κωδικός πρόσβασης από τη βάση δεδομένων.
     * @return bool True αν οι κωδικοί ταιριάζουν, false αλλιώς.
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}