<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once  '/../../includes/config.php';
require_once  '/../../includes/db_connect.php';

// Test database connection
function testDatabaseConnection($conn) {
    echo "<h2>Testing Database Connection</h2>";
    
    // Test connection
    try {
        $result = $conn->query("SELECT 1");
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Get database name
        $dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
        echo "<p>Database: " . htmlspecialchars($dbName) . "</p>";
        
        return true;
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        return false;
    }
}

// Check if tables exist
function checkTables($conn) {
    echo "<h2>Checking Required Tables</h2>";
    $tables = ['sections', 'questions'];
    $allExist = true;
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            
            // Show table structure
            $structure = $conn->query("DESCRIBE $table");
            echo "<div style='margin-left: 20px;'>";
            echo "<p>Structure of '$table':</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show row count
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            echo "<p>Total rows: $count</p>";
            
            // Show first few rows if any
            if ($count > 0) {
                $data = $conn->query("SELECT * FROM $table LIMIT 5")->fetch_all(MYSQLI_ASSOC);
                echo "<p>Sample data (first 5 rows):</p>";
                echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
            }
            
            echo "</div>";
            
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
            $allExist = false;
        }
    }
    
    return $allExist;
}

// Main execution
echo "<html><head><title>Database Test</title></head><body>";

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection not initialized");
    }
    
    $connectionOk = testDatabaseConnection($conn);
    
    if ($connectionOk) {
        $tablesOk = checkTables($conn);
        
        if (!$tablesOk) {
            echo "<h2 style='color: red;'>Error: Some required tables are missing</h2>";
        } else {
            echo "<h2 style='color: green;'>✓ All required tables exist</h2>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>
