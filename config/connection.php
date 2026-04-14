<?php
// Detect environment (SERVER_NAME alone misses many local setups; align with HTTP_HOST)
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$host = explode(':', $host)[0];
$isLocal = in_array($host, [
    'localhost',
    '127.0.0.1',
    '::1',
    '192.168.8.107',
], true)
    || (strlen($host) >= 6 && substr($host, -6) === '.local')
    || (strlen($host) >= 5 && substr($host, -5) === '.test')
    || preg_match('/^192\.168\.\d{1,3}\.\d{1,3}$/', $host)
    || preg_match('/^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host);

if ($isLocal) {
    // LOCAL (XAMPP)
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $dbname = 'ejeepdb';
} else {
    // SERVER (Hostinger)
    $dbhost = 'localhost';
    $dbuser = 'u951669574_xmas';
    $dbpass = '?0Gm|uH|n6';
    $dbname = 'u951669574_xmas';
}

class Database {
    private $connection;
    
    public function __construct() {
        global $dbhost, $dbuser, $dbpass, $dbname;
        
        try {
            $this->connection = new PDO(
                "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4",
                $dbuser,
                $dbpass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Create global PDO instance for backward compatibility
try {
    $pdo = new PDO(
        "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
