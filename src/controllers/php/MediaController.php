<?php
namespace Src\Controllers;

use Src\Config\Database;
use Src\Utils\Response;
use Src\Utils\Auth;
use Src\Utils\Cache;

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

        // --- SECURITY VALIDATION: FIle Upload RCE Prevention ---
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'pdf', 'csv']; // Strict Whitelist
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'application/pdf', 'text/csv'
        ];

        // 1. Validate real MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($realMimeType, $allowedMimes)) {
            Response::error('Invalid file type (MIME). Upload rejected.', 415);
        }

        // 2. Validate Extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            Response::error('Invalid file extension. Upload rejected.', 415);
        }

        // 3. Prevent PHP/Executable extensions even inside names (e.g. file.php.jpg)
        if (preg_match('/\.(php|phtml|phar|exe|sh|bat)$/i', $file['name'])) {
            Response::error('Suspicious filename. Upload rejected.', 415);
        }

        // 4. Safe Filename
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
            
            Cache::clear(); // Invalidate local gallery cache
            
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
        // --- HYBRID CACHE START ---
        $cacheKey = 'media_list';
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            Response::json($cachedData, 200, 60);
            return;
        }
        // --- HYBRID CACHE END ---

        // Listar mídias
        $stmt = $this->pdo->prepare("SELECT id, title, file_path as source_url, mime_type FROM media ORDER BY id DESC LIMIT 50");
        $stmt->execute();
        $media = $stmt->fetchAll();

        Cache::set($cacheKey, $media, 60);
        Response::json($media, 200, 60);
    }
}
