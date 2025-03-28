<?php
namespace App\Core;

use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
use App\Helpers\Config;

class App {
    private static $instance;
    private $db;
    private $router;
    private $session;
    
    private function __construct() {
        // Çevresel ayarları yükle
        $this->loadEnvironment();
        
        // Veritabanı bağlantısı
        $this->db = Database::getInstance();
        
        // Oturum yönetimi
        $this->session = Session::getInstance();
        
        // Rotaları yükle
        $this->router = require '../routes/web.php';
        $apiRouter = require '../routes/api.php';
        
        // API rotalarını ana rotalara ekle
        $this->router->merge($apiRouter);
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function run() {
        try {
            $this->router->dispatch();
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    private function loadEnvironment() {
        $envFile = __DIR__ . '/../../.env';
        
        if (!file_exists($envFile)) {
            throw new \RuntimeException('.env dosyası bulunamadı');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
    
    private function handleException(\Exception $e) {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        if (php_sapi_name() === 'cli') {
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        }
        
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            exit;
        }
        
        $errorView = __DIR__ . "/../../resources/views/errors/{$statusCode}.php";
        
        if (file_exists($errorView)) {
            http_response_code($statusCode);
            require $errorView;
            exit;
        }
        
        // Fallback error message
        http_response_code($statusCode);
        echo "<h1>Error {$statusCode}</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    
    public function getDb() {
        return $this->db;
    }
    
    public function getRouter() {
        return $this->router;
    }
    
    public function getSession() {
        return $this->session;
    }
}