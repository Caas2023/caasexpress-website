<?php
namespace Src\Utils;

class Cache {
    private static function getDir() {
        // Usa a pasta temporária do sistema, que é writeable na Vercel
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    }

    private static function getFilePath($key) {
        return self::getDir() . DIRECTORY_SEPARATOR . 'caas_cache_' . md5($key) . '.json';
    }

    public static function get($key) {
        // Se explicitamente pedindo para pular o cache
        if (isset($_GET['bypassCache'])) {
            return null;
        }

        $file = self::getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if (!$content) {
            return null;
        }

        $data = json_decode($content, true);
        
        // Verifica se é um dado estruturado com expiração
        if (isset($data['expiry']) && isset($data['payload'])) {
            if (time() > $data['expiry']) {
                // Expirou, deleta o arquivo e retorna null
                @unlink($file);
                return null;
            }
            return $data['payload'];
        }

        return null;
    }

    public static function set($key, $data, $ttlSeconds = 60) {
        $file = self::getFilePath($key);
        
        $payload = [
            'expiry' => time() + $ttlSeconds,
            'payload' => $data
        ];

        // Usando LOCK_EX para segurança em concorrência
        @file_put_contents($file, json_encode($payload), LOCK_EX);
    }

    public static function clear($prefix = null) {
        $dir = self::getDir();
        $files = glob($dir . DIRECTORY_SEPARATOR . 'caas_cache_*.json');
        
        $count = 0;
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }
}
