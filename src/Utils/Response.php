<?php
namespace Src\Utils;

class Response {
    public static function json($data, $status = 200, $ttl = null) {
        http_response_code($status);
        
        // Content Type
        header('Content-Type: application/json; charset=utf-8');

        // Edge Cache (Vercel) Headers
        if ($ttl !== null && !isset($_GET['bypassCache'])) {
            header("Cache-Control: public, s-maxage={$ttl}, stale-while-revalidate=86400");
        } else {
            header("Cache-Control: no-cache, no-store, must-revalidate");
        }
        
        // CORS - Permitir localhost em qualquer porta para desenvolvimento
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $isLocalhost = preg_match('/^https?:\/\/localhost(:\d+)?$/', $origin) || preg_match('/^https?:\/\/127\.0\.0\.1(:\d+)?$/', $origin);
        
        if ($isLocalhost) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        } else {
            // Produção
            header('Access-Control-Allow-Origin: https://caasexpresss.com');
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
