<?php
// ========== Recipe.php ==========
/**
 * @file Recipe.php
 * @brief Μοντέλο συνταγής.
 *
 * Διαχειρίζεται τις λειτουργίες CRUD για τις συνταγές.
 */

class Recipe {
    private $pdo;

    /**
     * Κατασκευαστής του μοντέλου Recipe.
     * @param PDO $pdo Η σύνδεση PDO με τη βάση δεδομένων.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Δημιουργεί μια νέα συνταγή.
     * @param array $data Τα δεδομένα της συνταγής (user_id, title, description, κλπ.).
     * @return int|false Το αναγνωριστικό της νέας συνταγής αν επιτυχής, false αλλιώς.
     */
    public function createRecipe(array $data) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO recipes (user_id, title, description, prep_time, cook_time, servings, main_image_path) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if ($stmt->execute([
                $data['user_id'],
                $data['title'],
                $data['description'],
                $data['prep_time'],
                $data['cook_time'],
                $data['servings'],
                $data['main_image_path']
            ])) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating recipe: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθέτει συστατικά σε μια συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @param array $ingredients Ένας πίνακας συστατικών.
     * @return bool True αν επιτυχής, false αλλιώς.
     */
    public function addIngredients(int $recipeId, array $ingredients): bool {
        $this->pdo->beginTransaction();
        try {
            $checkStmt = $this->pdo->prepare("SELECT ingredient_id FROM ingredients WHERE ingredient_name = ?");
            $insertIngredientStmt = $this->pdo->prepare("INSERT INTO ingredients (ingredient_name) VALUES (?)");
            $insertRecipeIngredientStmt = $this->pdo->prepare(
                "INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)"
            );

            foreach ($ingredients as $ingredient) {
                // Check if ingredient exists
                $checkStmt->execute([$ingredient['name']]);
                $existingIngredient = $checkStmt->fetch();

                if ($existingIngredient) {
                    $ingredientId = $existingIngredient['ingredient_id'];
                } else {
                    // Add new ingredient
                    $insertIngredientStmt->execute([$ingredient['name']]);
                    $ingredientId = $this->pdo->lastInsertId();
                }

                // Add to recipe_ingredients
                $insertRecipeIngredientStmt->execute([
                    $recipeId,
                    $ingredientId,
                    $ingredient['quantity'],
                    $ingredient['unit']
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error adding ingredients: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθέτει οδηγίες σε μια συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @param array $directions Ένας πίνακας οδηγιών.
     * @return bool True αν επιτυχής, false αλλιώς.
     */
    public function addDirections(int $recipeId, array $directions): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO directions (recipe_id, step_number, description) VALUES (?, ?, ?)"
            );

            foreach ($directions as $direction) {
                $stmt->execute([
                    $recipeId,
                    $direction['step_number'],
                    $direction['description']
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error adding directions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανακτά μια συνταγή με βάση το αναγνωριστικό της.
     * @param int $recipeId Το αναγνωριστικό της συνταγής.
     * @return array|false Τα δεδομένα της συνταγής αν βρεθεί, false αλλιώς.
     */
    public function getRecipeById(int $recipeId) {
        try {
            // Get recipe basic info
            $stmt = $this->pdo->prepare(
                "SELECT r.*, u.username 
                 FROM recipes r 
                 JOIN users u ON r.user_id = u.user_id 
                 WHERE r.recipe_id = ?"
            );
            $stmt->execute([$recipeId]);
            $recipe = $stmt->fetch();

            if ($recipe) {
                // Get ingredients
                $stmt = $this->pdo->prepare(
                    "SELECT ri.quantity, ri.unit, i.ingredient_name 
                     FROM recipe_ingredients ri 
                     JOIN ingredients i ON ri.ingredient_id = i.ingredient_id 
                     WHERE ri.recipe_id = ? 
                     ORDER BY i.ingredient_name"
                );
                $stmt->execute([$recipeId]);
                $recipe['ingredients'] = $stmt->fetchAll();

                // Get directions
                $stmt = $this->pdo->prepare(
                    "SELECT step_number, description 
                     FROM directions 
                     WHERE recipe_id = ? 
                     ORDER BY step_number ASC"
                );
                $stmt->execute([$recipeId]);
                $recipe['directions'] = $stmt->fetchAll();
            }

            return $recipe;
        } catch (PDOException $e) {
            error_log("Error getting recipe: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανακτά όλες τις συνταγές.
     * @return array Ένας πίνακας όλων των συνταγών.
     */
    public function getAllRecipes(): array {
        try {
            $stmt = $this->pdo->query(
                "SELECT r.*, u.username 
                 FROM recipes r 
                 JOIN users u ON r.user_id = u.user_id 
                 ORDER BY r.created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all recipes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής προς ενημέρωση.
     * @param array $data Τα νέα δεδομένα της συνταγής.
     * @return bool True αν η ενημέρωση ήταν επιτυχής, false αλλιώς.
     */
    public function updateRecipe(int $recipeId, array $data): bool {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE recipes 
                 SET title = ?, description = ?, prep_time = ?, cook_time = ?, servings = ?, main_image_path = ? 
                 WHERE recipe_id = ?"
            );
            return $stmt->execute([
                $data['title'],
                $data['description'],
                $data['prep_time'],
                $data['cook_time'],
                $data['servings'],
                $data['main_image_path'],
                $recipeId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating recipe: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει μια συνταγή.
     * @param int $recipeId Το αναγνωριστικό της συνταγής προς διαγραφή.
     * @return bool True αν η διαγραφή ήταν επιτυχής, false αλλιώς.
     */
    public function deleteRecipe(int $recipeId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM recipes WHERE recipe_id = ?");
            return $stmt->execute([$recipeId]);
        } catch (PDOException $e) {
            error_log("Error deleting recipe: " . $e->getMessage());
            return false;
        }
    }
}