<?php

/**
 * IGA Module - Base Controller
 */

require_once __DIR__ . '/../../../core/ModuleController.php';

class IGABaseController extends ModuleController
{
    public function __construct()
    {
        parent::__construct();
        // Module identification is handled by parent
    }

    /**
     * Process incoming API requests
     */
    public function processRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'index';

        $json = json_decode(file_get_contents('php://input'), true) ?? [];
        $input = array_merge($_GET, $_POST, $json);

        if (method_exists($this, $action)) {
            try {
                $response = $this->$action($input);
                if ($response !== null) {
                    header('Content-Type: application/json');
                    echo json_encode($response);
                }
            } catch (Exception $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Action '$action' not found."]);
        }
    }

    protected function requireAuth()
    {
        // Check for Employee Session OR Applicant Session
        if (!isset($_SESSION['user']) && !isset($_SESSION['iga_applicant'])) {
            // Redirect to IGA Login (Applicant Login)
            // Employees can switch to internal login from there
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        return true;
    }

    protected function getCurrentUser()
    {
        if (isset($_SESSION['user'])) return $_SESSION['user'];
        if (isset($_SESSION['iga_applicant'])) return $_SESSION['iga_applicant'];
        return null;
    }

    // Override Permission Check for Applicants
    protected function hasPermission($permission)
    {
        if (isset($_SESSION['iga_applicant'])) {
            // Applicants only have 'view' permission (to take exams)
            return $permission === 'view';
        }
        return parent::hasPermission($permission);
    }

    /**
     * Render a view file within the main layout
     */
    protected function render($viewName, $data = [])
    {
        // Make sure data is available to view
        extract($data);

        // Full path to the view file
        $viewPath = __DIR__ . '/../Views/' . $viewName . '.php';

        if (!file_exists($viewPath)) {
            die("View not found: $viewName");
        }

        // Load Layout which will include the viewPath
        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * JSON Success Response
     */
    protected function success($data = [], $message = 'Success')
    {
        return array_merge(['success' => true, 'message' => $message], $data);
    }

    /**
     * JSON Error Response
     */
    protected function error($message, $code = 400)
    {
        http_response_code($code);
        return ['success' => false, 'message' => $message];
    }
}
