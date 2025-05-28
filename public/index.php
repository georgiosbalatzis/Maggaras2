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

// Start session with secure settings
session_start();

// Session security settings
// Only set secure flag if using HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}
// Ensure session cookie is not accessible via JavaScript
ini_set('session.cookie_httponly', 1);
// CSRF protection: prevent cookie from being sent with cross-site requests
ini_set('session.cookie_samesite', 'Strict');

// Set security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
header("Referrer-Policy: no-referrer-when-downgrade");

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Include model and controller files
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Recipe.php';
require_once __DIR__ . '/../app/Models/Comment.php';
require_once __DIR__ . '/../app/Models/Like.php';

require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/RecipeController.php';
require_once __DIR__ . '/../app/Controllers/ApiController.php';

// Error reporting configuration for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Basic routing logic
$page = filter_var($_GET['page'] ?? 'home', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$action = filter_var($_GET['action'] ?? 'index', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

// Route requests
switch ($page) {
    case 'register':
        $controller = new AuthController($pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->register();
        } else {
            $controller->showRegisterForm();
        }
        break;

    case 'login':
        $controller = new AuthController($pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                if ($id) {
                    $controller->viewRecipe($id);
                } else {
                    header('Location: index.php?page=recipes&action=list');
                    exit();
                }
                break;
            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->createRecipe();
                } else {
                    $controller->showCreateRecipeForm();
                }
                break;
            case 'edit':
                if ($id) {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->editRecipe($id);
                    } else {
                        $controller->showEditRecipeForm($id);
                    }
                } else {
                    header('Location: index.php?page=recipes&action=list');
                    exit();
                }
                break;
            case 'delete':
                if ($id) {
                    $controller->deleteRecipe($id);
                } else {
                    header('Location: index.php?page=recipes&action=list');
                    exit();
                }
                break;
            case 'serve_image':
                $filename = filter_var($_GET['filename'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $controller->serveImage($filename);
                break;
            default:
                $controller->listRecipes();
                break;
        }
        break;

    case 'api':
        $controller = new ApiController($pdo);
        header('Content-Type: application/json');
        switch ($action) {
            case 'like':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->handleLike();
                } else {
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
            case 'comment':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->handleComment();
                } else {
                    echo json_encode(['error' => 'Method not allowed']);
                }
                break;
            default:
                echo json_encode(['error' => 'Unknown API action']);
                break;
        }
        break;

    case 'home':
    default:
        header('Location: index.php?page=recipes&action=list');
        exit();
}