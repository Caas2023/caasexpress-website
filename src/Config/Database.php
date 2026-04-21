<?php
namespace Src\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            // Caminho para o banco de dados SQLite
            // No Vercel, o caminho pode ser absoluto baseado em /var/task
            $dbPath = realpath(__DIR__ . '/../../db/database.sqlite');
            
            // Fallback se realpath falhar (ex: arquivo ainda não existe)
            if (!$dbPath) {
                $dbPath = __DIR__ . '/../../db/database.sqlite';
            }
            
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Otimizações para SQLite
            try {
                $this->pdo->exec("PRAGMA journal_mode = WAL;");
                $this->pdo->exec("PRAGMA foreign_keys = ON;");
            } catch (\Throwable $e) {
                // Silencia erro caso Vercel bloqueie PRAGMA
            }

        } catch (\Throwable $e) {
            // Se falhar, emitimos erro JSON ou paramos com erro fatal limpo
            header('Content-Type: application/json');
            http_response_code(500);
            die(json_encode([
                'code' => 'database_connection_failed',
                'message' => 'Erro crítico na conexão com o banco de dados: ' . $e->getMessage()
            ]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        // Garantia absoluta de que se chegamos aqui, pdo não é null
        if (!self::$instance->pdo) {
             header('Content-Type: application/json');
             http_response_code(500);
             die(json_encode(['code' => 'database_unavailable', 'message' => 'PDO is null']));
        }
        
        return self::$instance->pdo;
    }

    // Impede clonagem e unserialize
    private function __clone() {}
    public function __wakeup() {}
}
