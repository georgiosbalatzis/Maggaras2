<?php
/**
 * @file Like.php
 * @brief Μοντέλο Like.
 *
 * Διαχειρίζεται τις λειτουργίες που σχετίζονται με τα likes.
 */

class Like {
    private $pdo;

    /**
     * Κατασκευαστής του μοντέλου Like.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Προσθέτει ένα like σε μια συνταγή από έναν χρήστη.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @param int $userId Το αναγνωριστικό του χρήστη.
     * @return bool True αν η προσθήκη ήταν επιτυχής, false αλλιώς.
     */
    public function addLike(int $recipeId, int $userId): bool {
        // Ο μοναδικός περιορισμός στον πίνακα likes (recipe_id, user_id)
        // θα αποτρέψει διπλά likes από τον ίδιο χρήστη [1]
        try {
            $stmt = $this->pdo->prepare("INSERT INTO likes (recipe_id, user_id) VALUES (?,?)");
            return $stmt->execute([$recipeId, $userId]);
        } catch (PDOException $e) {
            // Εάν υπάρχει ήδη το like (λόγω UNIQUE constraint), απλά επιστρέψτε false
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation
                return false;
            }
            error_log("Σφάλμα προσθήκης like: ". $e->getMessage());
            return false;
        }
    }

    /**
     * Αφαιρεί ένα like από μια συνταγή από έναν χρήστη.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @param int $userId Το αναγνωριστικό του χρήστη.
     * @return bool True αν η αφαίρεση ήταν επιτυχής, false αλλιώς.
     */
    public function removeLike(int $recipeId, int $userId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM likes WHERE recipe_id =? AND user_id =?");
        return $stmt->execute([$recipeId, $userId]);
    }

    /**
     * Ελέγχει αν ένας χρήστης έχει κάνει like σε μια συγκεκριμένη συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @param int $userId Το αναγνωριστικό του χρήστη.
     * @return bool True αν ο χρήστης έχει κάνει like, false αλλιώς.
     */
    public function hasUserLikedRecipe(int $recipeId, int $userId): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipe_id =? AND user_id =?");
        $stmt->execute([$recipeId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Μετρά τον συνολικό αριθμό των likes για μια συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @return int Ο αριθμός των likes.
     */
    public function getLikeCount(int $recipeId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipe_id =?");
        $stmt->execute([$recipeId]);
        return $stmt->fetchColumn();
    }
}