/**
 * Global Configuration
 * Centralizes environment detection for frontend (Admin, Blog, Post)
 */
const AppConfig = {
    // Detect if we are running on Frontend Port (5500/5000) vs Backend (3001) vs Production (Vercel)
    // Retorna URL base vazia para usar paths relativos no servidor PHP
    getApiBaseUrl() {
        return '';
    }
};

// Expose globally
window.AppConfig = AppConfig;
