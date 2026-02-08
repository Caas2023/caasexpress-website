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
            // Ajuste conforme necessário. __DIR__ aponta para src/Config. 
            // Subimos 2 níveis e entramos em db/database.sqlite
            $dbPath = __DIR__ . '/../../db/database.sqlite';
            
            // Cria o diretório se não existir (apenas garantia)
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $this->pdo = new PDO("sqlite:" . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Otimizações para SQLite
            $this->pdo->exec("PRAGMA journal_mode = WAL;");
            $this->pdo->exec("PRAGMA foreign_keys = ON;");

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

    // Impede clonagem e unserialize
    private function __clone() {}
    public function __wakeup() {}
}
