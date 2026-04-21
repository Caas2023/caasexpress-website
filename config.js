/**
 * Global Configuration
 * Centralizes environment detection for frontend (Admin, Blog, Post)
 */
const AppConfig = {
    // Detect if we are running on Frontend Port (5500/5000) vs Backend (3001) vs Production (Vercel)
    // Alterado para apontar para a rota /api onde o PHP está processando as requisições no Vercel
    getApiBaseUrl() {
        const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        // Se estiver local e na porta 8000 (PHP), aponta para a 3001 (Node)
        // Se estiver no Vercel, usa o prefixo /api que é roteado para api/index.php
        if (isLocal && (window.location.port === '8000' || window.location.port === '5500' || window.location.port === '3000')) {
            return `http://${window.location.hostname}:3001`;
        }
        return '/api';
    }
};

// Expose globally
window.AppConfig = AppConfig;
