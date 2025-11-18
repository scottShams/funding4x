<?php
/**
 * Environment Variables Loader
 * Loads environment variables from .env file
 */

class EnvLoader {
    private static $loaded = false;
    private static $vars = [];
    
    /**
     * Load environment variables from .env file
     */
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        // Try different possible locations for .env file
        $possiblePaths = [
            __DIR__ . '/.env',
            dirname(__DIR__) . '/.env',
            getcwd() . '/.env'
        ];
        
        $envFile = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $envFile = $path;
                break;
            }
        }
        
        if (!$envFile) {
            // No .env file found, use system environment
            self::$loaded = true;
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$vars[$key] = $value;
                
                // Set as PHP environment variable
                if (!isset($_ENV[$key]) && getenv($key) === false) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable value
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        // Check PHP environment variables first
        $value = $_ENV[$key] ?? getenv($key);
        
        // Fall back to loaded .env values
        if ($value === false && array_key_exists($key, self::$vars)) {
            $value = self::$vars[$key];
        }
        
        return $value !== false ? $value : $default;
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset($_ENV[$key]) || getenv($key) !== false || array_key_exists($key, self::$vars);
    }
    
    /**
     * Get all environment variables
     */
    public static function all() {
        if (!self::$loaded) {
            self::load();
        }
        
        $env = array_merge($_ENV, getenv(), self::$vars);
        return $env;
    }
    
    /**
     * Set environment variable
     */
    public static function set($key, $value) {
        self::$vars[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
    
    /**
     * Debug function to check loaded variables
     */
    public static function debug() {
        if (!self::$loaded) {
            self::load();
        }
        
        echo "Loaded .env variables:\n";
        print_r(self::$vars);
        echo "\nPHP ENV variables:\n";
        print_r($_ENV);
        echo "\n";
    }
}

// Load environment variables immediately
EnvLoader::load();