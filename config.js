/**
 * Global Configuration
 * Centralizes environment detection for frontend (Admin, Blog, Post)
 */
const AppConfig = {
    // Detect if we are running on Frontend Port (5500/5000) vs Backend (3001) vs Production (Vercel)
    getApiBaseUrl() {
        if (typeof window === 'undefined') return ''; // Safety for Node

        const port = window.location.port;
        const hostname = window.location.hostname;

        // Local Development (Live Server -> Node Backend)
        if (hostname === '127.0.0.1' || hostname === 'localhost') {
            if (port === '5500' || port === '5000') {
                console.log('🔧 [AppConfig] Dev Environment restricted. Using: http://localhost:3001');
                return 'http://localhost:3001';
            }
        }

        // Production or Same-Origin (Backend serving Frontend)
        return '';
    }
};

// Expose globally
window.AppConfig = AppConfig;
