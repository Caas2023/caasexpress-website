<?php
namespace Src\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dbPath = __DIR__ . '/../../db/database.sqlite';
            
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                // O arroba '@' silencia o erro caso o servidor não permita criar pastas (como na Vercel)
                @mkdir($dir, 0755, true);
            }

            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            try {
                $this->pdo->exec("PRAGMA journal_mode = WAL;");
                $this->pdo->exec("PRAGMA foreign_keys = ON;");
            } catch (PDOException $e) {
                // Silencioso
            }

        } catch (PDOException $e) {
            die("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }

    private function __clone() {}
    public function __wakeup() {}
}
