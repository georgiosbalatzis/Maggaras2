<?php
/**
 * @file RecipeController.php
 * @brief Ελεγκτής συνταγών.
 *
 * Χειρίζεται τις λειτουργίες CRUD για τις συνταγές,
 * συμπεριλαμβανομένης της μεταφόρτωσης εικόνων.
 */

class RecipeController {
    private $recipeModel;
    private $commentModel;
    private $likeModel;
    private $pdo;

    /**
     * Κατασκευαστής του RecipeController.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->recipeModel = new Recipe($pdo);
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
     * Εμφανίζει τη φόρμα δημιουργίας συνταγής.
     */
    public function showCreateRecipeForm() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }
        require_once __DIR__. '/../Views/recipes/create.php';
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας δημιουργίας συνταγής.
     */
    public function createRecipe() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
        $prepTime = filter_var($_POST['prep_time'] ?? '', FILTER_SANITIZE_STRING);
        $cookTime = filter_var($_POST['cook_time'] ?? '', FILTER_SANITIZE_STRING);
        $servings = filter_var($_POST['servings'] ?? '', FILTER_SANITIZE_STRING);

        if (empty($title) || empty($description)) {
            $error = "Ο τίτλος και η περιγραφή είναι υποχρεωτικά.";
            require_once __DIR__. '/../Views/recipes/create.php';
            return;
        }

        $imagePath = null;
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['main_image']);
            if (!$imagePath) {
                $error = "Σφάλμα μεταφόρτωσης εικόνας. Παρακαλώ δοκιμάστε ξανά.";
                require_once __DIR__. '/../Views/recipes/create.php';
                return;
            }
        }

        // Διορθώθηκε: Αρχικοποίηση του $recipeData
        $recipeData = [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'prep_time' => $prepTime,
            'cook_time' => $cookTime,
            'servings' => $servings,
            'main_image_path' => $imagePath
        ];

        $recipeId = $this->recipeModel->createRecipe($recipeData);

        if ($recipeId) {
            // Χειρισμός συστατικών
            // Διορθώθηκε: Αρχικοποίηση του $ingredients
            $ingredients = [];
            if (isset($_POST['ingredient_name']) && is_array($_POST['ingredient_name'])) {
                foreach ($_POST['ingredient_name'] as $key => $name) {
                    $name = filter_var($name, FILTER_SANITIZE_STRING);
                    $quantity = filter_var($_POST['ingredient_quantity'][$key] ?? '', FILTER_SANITIZE_STRING);
                    $unit = filter_var($_POST['ingredient_unit'][$key] ?? '', FILTER_SANITIZE_STRING);
                    if (!empty($name) && !empty($quantity)) {
                        $ingredients[] = ['name' => $name, 'quantity' => $quantity, 'unit' => $unit]; // Διορθώθηκε: προσθήκη στοιχείου στον πίνακα
                    }
                }
            }
            if (!empty($ingredients)) {
                $this->recipeModel->addIngredients($recipeId, $ingredients);
            }

            // Χειρισμός οδηγιών
            // Διορθώθηκε: Αρχικοποίηση του $directions
            $directions = [];
            if (isset($_POST['direction_description']) && is_array($_POST['direction_description'])) {
                foreach ($_POST['direction_description'] as $key => $description) {
                    $description = filter_var($description, FILTER_SANITIZE_STRING);
                    if (!empty($description)) {
                        $directions[] = ['step_number' => $key + 1, 'description' => $description]; // Διορθώθηκε: προσθήκη στοιχείου στον πίνακα
                    }
                }
            }
            if (!empty($directions)) {
                $this->recipeModel->addDirections($recipeId, $directions);
            }

            header('Location: index.php?page=recipes&action=view&id='. $recipeId);
            exit();
        } else {
            $error = "Σφάλμα κατά τη δημιουργία της συνταγής. Παρακαλώ δοκιμάστε ξανά.";
            require_once __DIR__. '/../Views/recipes/create.php';
        }
    }

    /**
     * Χειρίζεται τη μεταφόρτωση εικόνας με ασφάλεια.
     * @param array $file Ο πίνακας $_FILES για την εικόνα.
     * @return string|false Η διαδρομή του αποθηκευμένου αρχείου ή false σε περίπτωση σφάλματος.
     */
    private function handleImageUpload(array $file) {
        $uploadDir = __DIR__. '/../../uploads/'; // Φάκελος εκτός web root [18, 4, 23, 24, 25]
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Δημιουργία φακέλου αν δεν υπάρχει
        }

        // Επικύρωση τύπου αρχείου (MIME type) [17, 18, 4, 24, 25]
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            error_log("Μη έγκυρος τύπος αρχείου: ". $mimeType);
            return false;
        }

        // Επικύρωση μεγέθους αρχείου [17, 18, 4, 24]
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxFileSize) {
            error_log("Το αρχείο είναι πολύ μεγάλο: ". $file['size']. " bytes.");
            return false;
        }

        // Ελέγξτε αν είναι πραγματική εικόνα [18, 4, 24]
        if (!getimagesize($file['tmp_name'])) {
            error_log("Το αρχείο δεν είναι έγκυρη εικόνα.");
            return false;
        }

        // Δημιουργία μοναδικού ονόματος αρχείου [17, 18, 4, 26, 25]
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('recipe_'). '.'. $extension;
        $targetPath = $uploadDir. $newFileName;

        // Μετακίνηση του μεταφορτωμένου αρχείου [17, 4]
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ορισμός περιοριστικών δικαιωμάτων [18, 4]
            chmod($targetPath, 0644);
            return $newFileName; // Επιστρέψτε μόνο το όνομα αρχείου, όχι την πλήρη διαδρομή
        }
        error_log("Αποτυχία μετακίνησης μεταφορτωμένου αρχείου.");
        return false;
    }

    /**
     * Εμφανίζει μια λίστα συνταγών.
     */
    public function listRecipes() {
        $recipes = $this->recipeModel->getAllRecipes();
        require_once __DIR__. '/../Views/recipes/list.php';
    }

    /**
     * Εμφανίζει μια συγκεκριμένη συνταγή.
     * @param int $id Το αναγνωριστικό της συνταγής.
     */
    public function viewRecipe(int $id) {
        $recipe = $this->recipeModel->getRecipeById($id);
        if (!$recipe) {
            // Συνταγή δεν βρέθηκε
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        $comments = $this->commentModel->getCommentsByRecipeId($id);
        $likeCount = $this->likeModel->getLikeCount($id);
        $hasLiked = $this->isLoggedIn() ? $this->likeModel->hasUserLikedRecipe($id, $_SESSION['user_id']) : false;

        require_once __DIR__. '/../Views/recipes/view.php';
    }

    /**
     * Εμφανίζει τη φόρμα επεξεργασίας συνταγής.
     * @param int $id Το αναγνωριστικό της συνταγής.
     */
    public function showEditRecipeForm(int $id) {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }
        $recipe = $this->recipeModel->getRecipeById($id);
        if (!$recipe || $recipe['user_id'] !== $_SESSION['user_id']) {
            // Συνταγή δεν βρέθηκε ή ο χρήστης δεν είναι ο ιδιοκτήτης
            header('Location: index.php?page=recipes&action=list');
            exit();
        }
        require_once __DIR__. '/../Views/recipes/edit.php'; // Θα χρειαστεί να δημιουργήσετε αυτό το view
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας επεξεργασίας συνταγής.
     * @param int $id Το αναγνωριστικό της συνταγής.
     */
    public function editRecipe(int $id) {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }
        $existingRecipe = $this->recipeModel->getRecipeById($id);
        if (!$existingRecipe || $existingRecipe['user_id'] !== $_SESSION['user_id']) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
        $prepTime = filter_var($_POST['prep_time'] ?? '', FILTER_SANITIZE_STRING);
        $cookTime = filter_var($_POST['cook_time'] ?? '', FILTER_SANITIZE_STRING);
        $servings = filter_var($_POST['servings'] ?? '', FILTER_SANITIZE_STRING);
        $imagePath = $existingRecipe['main_image_path']; // Διατηρήστε την υπάρχουσα εικόνα

        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $newImagePath = $this->handleImageUpload($_FILES['main_image']);
            if ($newImagePath) {
                $imagePath = $newImagePath;
                // Προαιρετικά: διαγράψτε την παλιά εικόνα από τον φάκελο uploads
                if ($existingRecipe['main_image_path'] && file_exists(__DIR__. '/../../uploads/'. $existingRecipe['main_image_path'])) {
                    unlink(__DIR__. '/../../uploads/'. $existingRecipe['main_image_path']);
                }
            } else {
                $error = "Σφάλμα μεταφόρτωσης νέας εικόνας. Παρακαλώ δοκιμάστε ξανά.";
                require_once __DIR__. '/../Views/recipes/edit.php'; // Θα χρειαστεί να δημιουργήσετε αυτό το view
                return;
            }
        }

        // Διορθώθηκε: Αρχικοποίηση του $recipeData
        $recipeData = [
            'title' => $title,
            'description' => $description,
            'prep_time' => $prepTime,
            'cook_time' => $cookTime,
            'servings' => $servings,
            'main_image_path' => $imagePath
        ];

        if ($this->recipeModel->updateRecipe($id, $recipeData)) {
            // Εδώ θα πρέπει να χειριστείτε την ενημέρωση συστατικών και οδηγιών
            // Για απλότητα, παραλείπεται σε αυτό το παράδειγμα, αλλά θα απαιτούσε
            // διαγραφή των παλιών και εισαγωγή των νέων ή πιο σύνθετη λογική ενημέρωσης.

            header('Location: index.php?page=recipes&action=view&id='. $id);
            exit();
        } else {
            $error = "Σφάλμα κατά την ενημέρωση της συνταγής. Παρακαλώ δοκιμάστε ξανά.";
            require_once __DIR__. '/../Views/recipes/edit.php'; // Θα χρειαστεί να δημιουργήσετε αυτό το view
        }
    }

    /**
     * Διαγράφει μια συνταγή.
     * @param int $id Το αναγνωριστικό της συνταγής.
     */
    public function deleteRecipe(int $id) {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }
        $recipe = $this->recipeModel->getRecipeById($id);
        if (!$recipe || $recipe['user_id'] !== $_SESSION['user_id']) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        if ($this->recipeModel->deleteRecipe($id)) {
            // Προαιρετικά: διαγράψτε την εικόνα από τον φάκελο uploads
            if ($recipe['main_image_path'] && file_exists(__DIR__. '/../../uploads/'. $recipe['main_image_path'])) {
                unlink(__DIR__. '/../../uploads/'. $recipe['main_image_path']);
            }
            header('Location: index.php?page=recipes&action=list');
            exit();
        } else {
            // Χειρισμός σφάλματος διαγραφής
            $error = "Σφάλμα κατά τη διαγραφή της συνταγής.";
            // Εμφάνιση σφάλματος ή ανακατεύθυνση με μήνυμα
            header('Location: index.php?page=recipes&action=list');
            exit();
        }
    }

    /**
     * Εμφανίζει μια εικόνα από τον φάκελο uploads.
     * Αυτή η μέθοδος χρησιμοποιείται για την ασφαλή παράδοση εικόνων
     * που είναι αποθηκευμένες εκτός του web root.
     * @param string $filename Το όνομα αρχείου της εικόνας.
     */
    public function serveImage(string $filename) {
        $filePath = __DIR__. '/../../uploads/'. basename($filename); // basename για αποτροπή path traversal [25]

        if (!file_exists($filePath)) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Ελέγξτε αν ο MIME type είναι ένας επιτρεπόμενος τύπος εικόνας
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            header("HTTP/1.0 403 Forbidden"); // Αποτρέψτε την παράδοση μη-εικόνων
            exit();
        }

        header('Content-Type: '. $mimeType); // [27]
        header('Content-Length: '. filesize($filePath));
        readfile($filePath); // [19, 27]
        exit();
    }
}