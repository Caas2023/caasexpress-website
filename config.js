/**
 * Global Configuration
 * Centralizes environment detection for frontend (Admin, Blog, Post)
 */
const AppConfig = {
    // Detect if we are running on Frontend Port (5500/5000) vs Backend (3001) vs Production (Vercel)
    // Alterado para apontar para a rota /api onde o PHP está processando as requisições no Vercel
    getApiBaseUrl() {
        return '/api';
    }
};

// Expose globally
window.AppConfig = AppConfig;
