<?php
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Models/TestModel.php';

class DashboardController
{
    private $pdo;
    private $testModel;

    public function __construct()
    {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->testModel = new TestModel($this->pdo);
    }

    public function processRequest()
    {
        $action = $_GET['action'] ?? 'index';

        switch ($action) {
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    private function index()
    {
        // Determine if the current user is an employee (SSO) or applicant (local login)
        $isApplicant = isset($_SESSION['user']['is_applicant']) && $_SESSION['user']['is_applicant'] === true;
        $isEmployee = isset($_SESSION['user_id']) && !$isApplicant;

        if (!$isApplicant && !$isEmployee) {
            // Not logged in at all — redirect to applicant login as default
            header("Location: /Modules/IGA/?action=login");
            exit;
        }

        // Fetch available tests
        $tests = $this->testModel->getAllTests('', 0, 50);
        $totalTests = $this->testModel->getTestCount();

        $page_title = "IGA Dashboard";
        $user = $_SESSION['user'] ?? [];

        include __DIR__ . '/../Views/dashboard.php';
    }
}
