<?php

// Load environment variables
require_once __DIR__ . '/../Config/Env.php';

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $driver;
    private $socket;
    public $conn;

    public function __construct()
    {
        // Load database configuration from .env
        $this->host = Env::get('DB_HOST', 'localhost'); // Default to localhost if missing
        $this->db_name = Env::get('DB_NAME') ?? Env::get('DB_DATABASE');
        $this->username = Env::get('DB_USER') ?? Env::get('DB_USERNAME');
        $this->password = Env::get('DB_PASS') ?? Env::get('DB_PASSWORD');
        // Support optional DB port (default MySQL port 3306)
        $this->port = Env::get('DB_PORT', 3306);
        // DB driver: mysql (default) or pgsql
        $this->driver = Env::get('DB_DRIVER', 'mysql');
        // Optional unix socket (takes precedence over host:port)
        $this->socket = Env::get('DB_SOCKET', null);
    }

    // For SQL Server (Mock/Placeholder)
    private $sqlsrv_host = "sqlsrv_host";
    private $sqlsrv_db = "sqlsrv_db";
    private $sqlsrv_user = "sqlsrv_user";
    private $sqlsrv_pass = "sqlsrv_pass";
    public $sqlsrv_conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            // Build DSN based on driver
            if ($this->driver === 'pgsql') {
                if ($this->socket) {
                    $dsn = sprintf('pgsql:host=%s;dbname=%s;port=%s', $this->host, $this->db_name, $this->port);
                } else {
                    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $this->host, $this->port, $this->db_name);
                }
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Ensure UTF8 for pgsql
                $this->conn->exec("SET NAMES 'utf8'");
            } else {
                // default to mysql
                if ($this->socket) {
                    $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8', $this->socket, $this->db_name);
                } else {
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', $this->host, $this->port, $this->db_name);
                }
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Set database session timezone to match PHP timezone
                $this->conn->exec("SET time_zone = '" . date('P') . "'");
            }
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());

            // Check for CLI
            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, "Database Connection Failed: " . $exception->getMessage() . PHP_EOL);
                exit(1);
            }

            http_response_code(503);

            // Detect if client expects JSON
            $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) ||
                isset($_GET['ajax']);

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "Database Connection Failed: " . $exception->getMessage(),
                    "error_code" => "db_connection_error"
                ]);
            } else {
                // Friendly HTML error
                header('Content-Type: text/html; charset=utf-8');
                echo '<div style="padding: 20px; font-family: sans-serif; text-align: center; color: #333;">';
                echo '<div style="font-size: 48px; margin-bottom: 20px;">🔌</div>';
                echo '<h1>Connecting to Database Failed</h1>';
                echo '<p>Detailed Error: ' . htmlspecialchars($exception->getMessage()) . '</p>';
                echo '<p><a href="#" onclick="window.location.reload()">Reload Page</a></p>';
                echo '</div>';
            }
            exit;
        }

        return $this->conn;
    }

    public function getSqlServerConnection()
    {
        $this->sqlsrv_conn = null;
        // Placeholder for SQL Server connection
        // In a real scenario, you would use pdo_sqlsrv or odbc
        // try {
        //     $this->sqlsrv_conn = new PDO("sqlsrv:Server=" . $this->sqlsrv_host . ";Database=" . $this->sqlsrv_db, $this->sqlsrv_user, $this->sqlsrv_pass);
        //     $this->sqlsrv_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // } catch(PDOException $exception) {
        //     echo "SQL Server Connection error: " . $exception->getMessage();
        // }
        return $this->sqlsrv_conn;
    }
}
