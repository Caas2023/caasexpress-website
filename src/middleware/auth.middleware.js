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

                if (user === CONFIG.API_USER && normalizedPassword === configPassword) {
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
            if (token === CONFIG.BEARER_TOKEN) {
                req.user = { id: 1, name: 'api', role: 'administrator' };
                return next();
            }
        }

        // Allow GET requests without auth (public read)
        if (req.method === 'GET') {
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
