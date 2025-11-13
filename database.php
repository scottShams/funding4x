<?php
/**
 * Database Configuration File
 * Centralized database connection for the referral system
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'funding4x');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Create and return PDO database connection
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        // Log the error (in production, you might want to log to a file)
        error_log("Database connection failed: " . $e->getMessage());
        
        // Re-throw the exception so the calling code can handle it
        throw $e;
    }
}

/**
 * Get database connection (singleton pattern for request)
 * @return PDO Database connection object
 */
function getPDO() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = getDBConnection();
        } catch (PDOException $e) {
            // In a production environment, you might want to redirect to an error page
            // For now, we'll display a generic error message
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                // API request - return JSON error
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Database connection failed'
                ]);
            } else {
                // Web request - display HTML error
                die('Database connection failed. Please try again later.');
            }
            exit;
        }
    }
    
    return $pdo;
}
?>