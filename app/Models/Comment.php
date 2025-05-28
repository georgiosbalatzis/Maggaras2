<?php
// ========== Comment.php ==========
/**
 * @file Comment.php
 * @brief Μοντέλο σχολίου.
 *
 * Διαχειρίζεται τις λειτουργίες που σχετίζονται με τα σχόλια.
 */

class Comment {
    private $pdo;

    /**
     * Κατασκευαστής του μοντέλου Comment.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Προσθέτει ένα νέο σχόλιο σε μια συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @param int $userId Το αναγνωριστικό του χρήστη που σχολιάζει.
     * @param string $commentText Το κείμενο του σχολίου.
     * @return bool True αν η προσθήκη ήταν επιτυχής, false αλλιώς.
     */
    public function addComment(int $recipeId, int $userId, string $commentText): bool {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO comments (recipe_id, user_id, comment_text) VALUES (?, ?, ?)");
            return $stmt->execute([$recipeId, $userId, $commentText]);
        } catch (PDOException $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανακτά όλα τα σχόλια για μια συγκεκριμένη συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @return array Ένας πίνακας σχολίων.
     */
    public function getCommentsByRecipeId(int $recipeId): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*, u.username 
                 FROM comments c 
                 JOIN users u ON c.user_id = u.user_id 
                 WHERE c.recipe_id = ? 
                 ORDER BY c.created_at DESC"
            );
            $stmt->execute([$recipeId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting comments: " . $e->getMessage());
            return [];
        }
    }
}