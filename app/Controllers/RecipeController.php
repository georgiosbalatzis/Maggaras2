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
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας συνταγής.
     */
    public function showCreateRecipeForm() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }
        $this->generateCSRFToken();
        require_once __DIR__ . '/../Views/recipes/create.php';
    }

    /**
     * Χειρίζεται την υποβολή της φόρμας δημιουργίας συνταγής.
     */
    public function createRecipe() {
        if (!$this->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit();
        }

        if (!$this->validateCSRF()) {
            $error = "Μη έγκυρο CSRF token. Παρακαλώ ανανεώστε τη σελίδα.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/recipes/create.php';
            return;
        }

        $userId = $_SESSION['user_id'];
        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prepTime = filter_var($_POST['prep_time'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cookTime = filter_var($_POST['cook_time'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $servings = filter_var($_POST['servings'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Validate input lengths
        if (strlen($title) > 255) {
            $error = "Ο τίτλος είναι πολύ μεγάλος (μέγιστο 255 χαρακτήρες).";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/recipes/create.php';
            return;
        }

        if (empty($title) || empty($description)) {
            $error = "Ο τίτλος και η περιγραφή είναι υποχρεωτικά.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/recipes/create.php';
            return;
        }

        $imagePath = null;
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['main_image']);
            if (!$imagePath) {
                $error = "Σφάλμα μεταφόρτωσης εικόνας. Παρακαλώ δοκιμάστε ξανά.";
                $this->generateCSRFToken();
                require_once __DIR__ . '/../Views/recipes/create.php';
                return;
            }
        }

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
            $ingredients = [];
            if (isset($_POST['ingredient_name']) && is_array($_POST['ingredient_name'])) {
                foreach ($_POST['ingredient_name'] as $key => $name) {
                    $name = filter_var($name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $quantity = filter_var($_POST['ingredient_quantity'][$key] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $unit = filter_var($_POST['ingredient_unit'][$key] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                    // Validate lengths
                    if (strlen($name) > 100 || strlen($quantity) > 50 || strlen($unit) > 50) {
                        continue; // Skip oversized inputs
                    }

                    if (!empty($name) && !empty($quantity)) {
                        $ingredients[] = ['name' => $name, 'quantity' => $quantity, 'unit' => $unit];
                    }
                }
            }
            if (!empty($ingredients)) {
                $this->recipeModel->addIngredients($recipeId, $ingredients);
            }

            // Χειρισμός οδηγιών
            $directions = [];
            if (isset($_POST['direction_description']) && is_array($_POST['direction_description'])) {
                foreach ($_POST['direction_description'] as $key => $description) {
                    $description = filter_var($description, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    if (!empty($description) && strlen($description) <= 2000) {
                        $directions[] = ['step_number' => $key + 1, 'description' => $description];
                    }
                }
            }
            if (!empty($directions)) {
                $this->recipeModel->addDirections($recipeId, $directions);
            }

            header('Location: index.php?page=recipes&action=view&id=' . $recipeId);
            exit();
        } else {
            $error = "Σφάλμα κατά τη δημιουργία της συνταγής. Παρακαλώ δοκιμάστε ξανά.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/recipes/create.php';
        }
    }

    /**
     * Χειρίζεται τη μεταφόρτωση εικόνας με ασφάλεια.
     * @param array $file Ο πίνακας $_FILES για την εικόνα.
     * @return string|false Η διαδρομή του αποθηκευμένου αρχείου ή false σε περίπτωση σφάλματος.
     */
    private function handleImageUpload(array $file) {
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true); // More restrictive permissions
        }

        // Επικύρωση τύπου αρχείου (MIME type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            error_log("Μη έγκυρος τύπος αρχείου: " . $mimeType);
            return false;
        }

        // Επικύρωση μεγέθους αρχείου
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxFileSize) {
            error_log("Το αρχείο είναι πολύ μεγάλο: " . $file['size'] . " bytes.");
            return false;
        }

        // Ελέγξτε αν είναι πραγματική εικόνα
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            error_log("Το αρχείο δεν είναι έγκυρη εικόνα.");
            return false;
        }

        // Additional security: check actual dimensions
        if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
            error_log("Η εικόνα είναι πολύ μεγάλη σε διαστάσεις.");
            return false;
        }

        // Δημιουργία μοναδικού ονόματος αρχείου
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return false;
        }

        $newFileName = uniqid('recipe_', true) . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;

        // Μετακίνηση του μεταφορτωμένου αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ορισμός περιοριστικών δικαιωμάτων
            chmod($targetPath, 0644);
            return $newFileName;
        }
        error_log("Αποτυχία μετακίνησης μεταφορτωμένου αρχείου.");
        return false;
    }

    /**
     * Εμφανίζει μια λίστα συνταγών.
     */
    public function listRecipes() {
        $recipes = $this->recipeModel->getAllRecipes();
        require_once __DIR__ . '/../Views/recipes/list.php';
    }

    /**
     * Εμφανίζει μια συγκεκριμένη συνταγή.
     * @param int $id Το αναγνωριστικό της συνταγής.
     */
    public function viewRecipe(int $id) {
        $recipe = $this->recipeModel->getRecipeById($id);
        if (!$recipe) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        $comments = $this->commentModel->getCommentsByRecipeId($id);
        $likeCount = $this->likeModel->getLikeCount($id);
        $hasLiked = $this->isLoggedIn() ? $this->likeModel->hasUserLikedRecipe($id, $_SESSION['user_id']) : false;
        $isLoggedIn = $this->isLoggedIn();

        require_once __DIR__ . '/../Views/recipes/view.php';
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
            header('Location: index.php?page=recipes&action=list');
            exit();
        }
        $this->generateCSRFToken();
        require_once __DIR__ . '/../Views/recipes/edit.php';
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

        if (!$this->validateCSRF()) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        $existingRecipe = $this->recipeModel->getRecipeById($id);
        if (!$existingRecipe || $existingRecipe['user_id'] !== $_SESSION['user_id']) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prepTime = filter_var($_POST['prep_time'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cookTime = filter_var($_POST['cook_time'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $servings = filter_var($_POST['servings'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $imagePath = $existingRecipe['main_image_path'];

        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $newImagePath = $this->handleImageUpload($_FILES['main_image']);
            if ($newImagePath) {
                $imagePath = $newImagePath;
                // Delete old image
                if ($existingRecipe['main_image_path'] && file_exists(__DIR__ . '/../../uploads/' . $existingRecipe['main_image_path'])) {
                    unlink(__DIR__ . '/../../uploads/' . $existingRecipe['main_image_path']);
                }
            } else {
                $error = "Σφάλμα μεταφόρτωσης νέας εικόνας. Παρακαλώ δοκιμάστε ξανά.";
                $this->generateCSRFToken();
                require_once __DIR__ . '/../Views/recipes/edit.php';
                return;
            }
        }

        $recipeData = [
            'title' => $title,
            'description' => $description,
            'prep_time' => $prepTime,
            'cook_time' => $cookTime,
            'servings' => $servings,
            'main_image_path' => $imagePath
        ];

        if ($this->recipeModel->updateRecipe($id, $recipeData)) {
            header('Location: index.php?page=recipes&action=view&id=' . $id);
            exit();
        } else {
            $error = "Σφάλμα κατά την ενημέρωση της συνταγής. Παρακαλώ δοκιμάστε ξανά.";
            $this->generateCSRFToken();
            require_once __DIR__ . '/../Views/recipes/edit.php';
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

        // Validate CSRF token from query parameter for delete links
        $csrfToken = $_GET['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        $recipe = $this->recipeModel->getRecipeById($id);
        if (!$recipe || $recipe['user_id'] !== $_SESSION['user_id']) {
            header('Location: index.php?page=recipes&action=list');
            exit();
        }

        if ($this->recipeModel->deleteRecipe($id)) {
            // Delete image file
            if ($recipe['main_image_path'] && file_exists(__DIR__ . '/../../uploads/' . $recipe['main_image_path'])) {
                unlink(__DIR__ . '/../../uploads/' . $recipe['main_image_path']);
            }
            header('Location: index.php?page=recipes&action=list');
            exit();
        } else {
            $error = "Σφάλμα κατά τη διαγραφή της συνταγής.";
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
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filePath = __DIR__ . '/../../uploads/' . $filename;

        if (!file_exists($filePath)) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }

        // Verify it's actually an image
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            header("HTTP/1.0 403 Forbidden");
            exit();
        }

        // Set proper headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day

        // Output the image
        readfile($filePath);
        exit();
    }
}