const crypto = require('crypto');

/**
 * Authentication Middleware
 * WordPress-compatible Basic Auth and Bearer Token authentication
 */

function authenticate(CONFIG) {
    return (req, res, next) => {
        const authHeader = req.headers.authorization || '';

        // Basic Auth (WordPress Application Password)
        if (authHeader.startsWith('Basic ')) {
            try {
                const base64 = authHeader.split(' ')[1];
                const decoded = Buffer.from(base64, 'base64').toString('utf-8');
                const [user, password] = decoded.split(':');

                // Normalize password (WordPress uses spaces)
                const normalizedPassword = password.replace(/\s+/g, ' ').trim();
                const configPassword = (CONFIG.API_PASSWORD || '').replace(/\s+/g, ' ').trim();

                // SECURITY: Prevent Timing Attacks
                const bufA = Buffer.from(normalizedPassword);
                const bufB = Buffer.from(configPassword);

                if (user === CONFIG.API_USER && bufA.length === bufB.length && crypto.timingSafeEqual(bufA, bufB)) {
                    req.user = { id: 1, name: user, role: 'administrator' };
                    return next();
                }
            } catch (e) {
                console.error('Auth error:', e);
            }
        }

        // Bearer Token
        if (authHeader.startsWith('Bearer ')) {
            const token = authHeader.split(' ')[1];

            const bufA = Buffer.from(token);
            const bufB = Buffer.from(CONFIG.BEARER_TOKEN || '');

            if (bufA.length === bufB.length && crypto.timingSafeEqual(bufA, bufB)) {
                req.user = { id: 1, name: 'api', role: 'administrator' };
                return next();
            }
        }

        // SECURITY: Allow only specific public GET paths, deny by default
        const publicPaths = ['/wp-json/wp/v2/posts', '/wp-json/wp/v2/categories', '/wp-json/wp/v2/tags', '/wp-json/wp/v2/web-stories'];
        const isPublicRoute = publicPaths.some(p => req.path.startsWith(p));

        if (req.method === 'GET' && isPublicRoute) {
            // Further logic should ideally filter drafts/private posts inside the controller
            return next();
        }

        return res.status(401).json({
            code: 'rest_not_logged_in',
            message: 'Você não tem permissão para fazer isso.',
            data: { status: 401 }
        });
    };
}

module.exports = { authenticate };
