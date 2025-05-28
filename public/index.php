<?php global $pdo;
/**
 * @file index.php
 * @brief Κεντρικό σημείο εισόδου της εφαρμογής (Front Controller).
 *
 * Αυτό το αρχείο χειρίζεται όλα τα εισερχόμενα αιτήματα,
 * περιλαμβάνει τις ρυθμίσεις της βάσης δεδομένων,
 * ξεκινά τη διαχείριση συνόδου και δρομολογεί τα αιτήματα
 * στους κατάλληλους ελεγκτές.
 */

// Ξεκινήστε τη σύνοδο PHP [8, 9, 10]
session_start();

// Ρυθμίσεις ασφαλείας συνόδου [8, 10]
// Εξασφαλίζει ότι το cookie συνόδου μεταδίδεται μόνο μέσω HTTPS
ini_set('session.cookie_secure', 1);
// Εξασφαλίζει ότι το cookie συνόδου δεν είναι προσβάσιμο από JavaScript
ini_set('session.cookie_httponly', 1);
// Προστασία CSRF: αποτρέπει την αποστολή του cookie με αιτήματα από διαφορετικές ιστοσελίδες [11, 12, 10]
ini_set('session.cookie_samesite', 'Strict');

// Συμπεριλάβετε το αρχείο σύνδεσης βάσης δεδομένων
require_once __DIR__. '/../config/database.php';

// Συμπεριλάβετε τα αρχεία των μοντέλων και των ελεγκτών
require_once __DIR__. '/../app/Models/User.php';
require_once __DIR__. '/../app/Models/Recipe.php';
require_once __DIR__. '/../app/Models/Comment.php';
require_once __DIR__. '/../app/Models/Like.php';

require_once __DIR__. '/../app/Controllers/AuthController.php';
require_once __DIR__. '/../app/Controllers/RecipeController.php';
require_once __DIR__. '/../app/Controllers/ApiController.php';

// Απενεργοποίηση εμφάνισης λεπτομερών σφαλμάτων σε περιβάλλον παραγωγής [11, 13, 9, 14]
// Για ανάπτυξη, μπορείτε να το αλλάξετε σε E_ALL
error_reporting(0);
ini_set('display_errors', 0);

// Βασική λογική δρομολόγησης
$page = $_GET['page']?? 'home'; // Προεπιλεγμένη σελίδα 'home'
$action = $_GET['action']?? 'index'; // Προεπιλεγμένη ενέργεια 'index'
$id = $_GET['id']?? null; // Προαιρετικό αναγνωριστικό για προβολή/επεξεργασία

// Δρομολόγηση αιτημάτων
switch ($page) {
    case 'register':
        $controller = new AuthController($pdo);
        if ($_SERVER === 'POST') {
            $controller->register();
        } else {
            $controller->showRegisterForm();
        }
        break;
    case 'login':
        $controller = new AuthController($pdo);
        if ($_SERVER === 'POST') {
            $controller->login();
        } else {
            $controller->showLoginForm();
        }
        break;
    case 'logout':
        $controller = new AuthController($pdo);
        $controller->logout();
        break;
    case 'recipes':
        $controller = new RecipeController($pdo);
        switch ($action) {
            case 'list':
                $controller->listRecipes();
                break;
            case 'view':
                $controller->viewRecipe($id);
                break;
            case 'create':
                if ($_SERVER === 'POST') {
                    $controller->createRecipe();
                } else {
                    $controller->showCreateRecipeForm();
                }
                break;
            case 'edit':
                if ($_SERVER === 'POST') {
                    $controller->editRecipe($id);
                } else {
                    $controller->showEditRecipeForm($id);
                }
                break;
            case 'delete':
                $controller->deleteRecipe($id);
                break;
            case 'serve_image': // Νέα ενέργεια για την παράδοση εικόνων
                $controller->serveImage($_GET['filename']?? '');
                break;
            default:
                $controller->listRecipes(); // Προεπιλεγμένη ενέργεια για συνταγές
                break;
        }
        break;
    case 'api':
        $controller = new ApiController($pdo);
        header('Content-Type: application/json'); // Όλες οι απαντήσεις API είναι JSON [15, 16]
        switch ($action) {
            case 'like':
                $controller->handleLike();
                break;
            case 'comment':
                $controller->handleComment();
                break;
            default:
                echo json_encode(['error' => 'Άγνωστη ενέργεια API']);
                break;
        }
        break;
    case 'home':
    default:
        // Μπορείτε να ανακατευθύνετε στην αρχική σελίδα ή στη λίστα συνταγών
        header('Location: index.php?page=recipes&action=list');
        exit();
}