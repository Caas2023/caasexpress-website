<?php
namespace Src\Utils;

class Response {
    public static function json($data, $status = 200) {
        http_response_code($status);
        
        // Content Type
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS - Restringir em produção
        $allowedOrigins = ['http://localhost:8000', 'https://caasexpresss.com'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header('Access-Control-Allow-Origin: http://localhost:8000'); // Default dev
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400'); // Cache preflight 24h
        
        // Security Headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:;");
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error($message, $status = 400) {
        self::json(['code' => 'error', 'message' => $message, 'data' => ['status' => $status]], $status);
    }
}
