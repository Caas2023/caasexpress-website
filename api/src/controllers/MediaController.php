<?php
namespace Src\Controllers;

use Src\Config\Database;
use Src\Utils\Response;
use Src\Utils\Auth;

class MediaController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function create() {
        Auth::check();
        if (!isset($_FILES['file'])) {
            Response::error('No file uploaded', 400);
        }

        $file = $_FILES['file'];
        $uploadDir = __DIR__ . '/../../uploads/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        $publicUrl = '/uploads/' . $filename; // URL relativa para salvar no banco

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $this->pdo->prepare("INSERT INTO media (title, file_path, mime_type, alt_text) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $file['name'],
                $publicUrl,
                $file['type'],
                ''
            ]);
            
            $id = $this->pdo->lastInsertId();
            
            Response::json([
                'id' => $id,
                'source_url' => $publicUrl, // WP field
                'title' => ['rendered' => $file['name']],
                'mime_type' => $file['type']
            ], 201);
        } else {
            Response::error('Failed to move uploaded file', 500);
        }
    }

    public function index() {
        // Listar mídias
        $stmt = $this->pdo->prepare("SELECT id, title, file_path as source_url, mime_type FROM media ORDER BY id DESC LIMIT 50");
        $stmt->execute();
        $media = $stmt->fetchAll();
        Response::json($media);
    }
}
