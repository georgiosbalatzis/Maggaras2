<?php
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
        // Κατακερματισμός κωδικού πρόσβασης για ασφαλή αποθήκευση [17, 9, 14]
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?,?,?)");
        // Χρήση προετοιμασμένων δηλώσεων για αποτροπή SQL injection [18, 11, 19, 13, 9, 16, 14]
        return $stmt->execute([$username, $email, $passwordHash]);
    }

    /**
     * Βρίσκει έναν χρήστη με βάση το όνομα χρήστη.
     * @param string $username Το όνομα χρήστη προς αναζήτηση.
     * @return array|false Τα δεδομένα του χρήστη αν βρεθεί, false αλλιώς.
     */
    public function findUserByUsername(string $username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username =?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /**
     * Επαληθεύει έναν κωδικό πρόσβασης έναντι ενός κατακερματισμένου κωδικού.
     * @param string $password Ο απλός κωδικός πρόσβασης.
     * @param string $hash Ο κατακερματισμένος κωδικός πρόσβασης από τη βάση δεδομένων.
     * @return bool True αν οι κωδικοί ταιριάζουν, false αλλιώς.
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash); [17, 9, 14];
    }
}