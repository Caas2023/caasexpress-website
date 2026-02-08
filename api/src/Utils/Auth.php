<?php
namespace Src\Utils;

use Src\Config\Database;

class Auth {
    public static function check() {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="Caas Express Admin"');
            Response::error('Authentication required', 401);
            exit;
        }

        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $user]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($pass, $userData['password'])) {
            return $userData;
        }

        Response::error('Invalid credentials', 403);
        exit;
    }
    
    // Método para validar sem abortar (retorna bool)
    public static function validate() {
        if (!isset($_SERVER['PHP_AUTH_USER'])) return false;
        
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
        
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $user]);
        $userData = $stmt->fetch();
        
        return ($userData && password_verify($pass, $userData['password']));
    }
}
