<?php
require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Config/SessionConfig.php';

class SSOController
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function processRequest()
    {
        $action = $_GET['action'] ?? 'moodle';

        if ($action === 'moodle') {
            $this->moodleSSO();
        } else {
            http_response_code(400);
            echo "Invalid SSO Action";
        }
    }

    private function moodleSSO()
    {
        // 1. Check if user is logged in (HR Portal Session)
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            // Not logged in, redirect to HR Login
            $baseUrl = $this->getBaseUrl();
            header("Location: $baseUrl/login");
            exit;
        }

        $userId = $_SESSION['user']['id'];

        // 2. Generate One-Time Token
        $token = bin2hex(random_bytes(32)); // 64 chars
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 minute'));

        try {
            // 3. Store Token in Shared DB
            $stmt = $this->conn->prepare("INSERT INTO sso_tokens (token, user_id, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$token, $userId, $expiresAt]);

            // 4. Redirect to Moodle Plugin
            // Use configured public URL
            $moodleBase = getenv('MOODLE_PUBLIC_URL') ?: ($this->getBaseUrl() . '/moodle');
            $moodleUrl = $moodleBase . "/auth/myhrauth/login_sso.php?token=$token";

            header("Location: $moodleUrl");
            exit;
        } catch (Exception $e) {
            error_log("SSO Error: " . $e->getMessage());
            die("SSO Facilited. Please contact admin.");
        }
    }

    private function getBaseUrl()
    {
        $protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? "https" : "http";
        // Simple base URL detection
        // If current is /public/routes.php, we want root domain
        return $protocol . "://" . $_SERVER['HTTP_HOST'];
    }
}
