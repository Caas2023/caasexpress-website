<?php
namespace Src\Controllers;

use Src\Utils\Response;
use Src\Utils\Auth;
use Src\Config\Database;

class UserController {
    
    // GET /wp-json/wp/v2/users/me
    public function me() {
        // middleware check
        $user = Auth::check(); // Se falhar, já retorna 401
        
        // Retorna formato que o admin.js espera
        Response::json([
            'id' => $user['id'],
            'name' => $user['display_name'],
            'slug' => $user['username'],
            'email' => $user['email'],
            'roles' => [$user['role']], // array
            'capabilities' => ['administrator' => true] // mock simples
        ]);
    }
    
    // GET /wp-json/wp/v2/users (Lista simplificada para dropdowns)
    public function index() {
        Auth::check();
        
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, display_name as name, username as slug FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        Response::json($users);
    }
}
