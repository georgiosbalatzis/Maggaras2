<?php
/**
 * @file ApiController.php
 * @brief Ελεγκτής API για ασύγχρονες λειτουργίες.
 *
 * Χειρίζεται αιτήματα AJAX για likes και σχόλια.
 */

class ApiController {
    private $commentModel;
    private $likeModel;
    private $pdo;

    /**
     * Κατασκευαστής του ApiController.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->commentModel = new Comment($pdo);
        $this->likeModel = new Like($pdo);
    }

    /**
     * Ελέγχει αν ο χρήστης είναι συνδεδεμένος.
     * @return bool True αν ο χρήστης είναι συνδεδεμένος, false αλλιώς.
     */
    private function isLoggedIn(): bool {
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }

    /**
     * Χειρίζεται την προσθήκη/αφαίρεση ενός like.
     */
    public function handleLike() {
        if (!$this->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Πρέπει να συνδεθείτε για να κάνετε like.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true); // [1]
        $recipeId = filter_var($input['recipe_id']?? 0, FILTER_VALIDATE_INT);
        $userId = $_SESSION['user_id'];

        if (!$recipeId) {
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρο αναγνωριστικό συνταγής.']);
            return;
        }

        $hasLiked = $this->likeModel->hasUserLikedRecipe($recipeId, $userId);

        if ($hasLiked) {
            // Αφαίρεση like
            if ($this->likeModel->removeLike($recipeId, $userId)) {
                echo json_encode(['success' => true, 'action' => 'unliked', 'count' => $this->likeModel->getLikeCount($recipeId)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την αφαίρεση του like.']);
            }
        } else {
            // Προσθήκη like
            if ($this->likeModel->addLike($recipeId, $userId)) {
                echo json_encode(['success' => true, 'action' => 'liked', 'count' => $this->likeModel->getLikeCount($recipeId)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την προσθήκη του like.']);
            }
        }
    }

    /**
     * Χειρίζεται την υποβολή σχολίου.
     */
    public function handleComment() {
        if (!$this->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Πρέπει να συνδεθείτε για να σχολιάσετε.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $recipeId = filter_var($input['recipe_id']?? 0, FILTER_VALIDATE_INT);
        $commentText = filter_var($input['comment_text']?? '', FILTER_SANITIZE_STRING);
        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username']; // Για άμεση ανατροφοδότηση στο frontend

        if (!$recipeId || empty($commentText)) {
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρα δεδομένα σχολίου.']);
            return;
        }

        if ($this->commentModel->addComment($recipeId, $userId, $commentText)) {
            echo json_encode();
        } else {
            echo json_encode(['success' => false, 'message' => 'Σφάλμα κατά την προσθήκη σχολίου.']);
        }
    }
}