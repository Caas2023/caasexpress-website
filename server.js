/**
 * Caas Express - WordPress-Compatible REST API
 * Refactored with MVC architecture and SQLite database (sql.js - serverless compatible)
 */

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { v4: uuidv4 } = require('uuid');

// Import MVC components
const { authenticate } = require('./src/middleware/auth.middleware');
const db = require('./src/models/database');
const seoController = require('./src/controllers/seo.controller');

const app = express();
const PORT = process.env.PORT || 3001;

// ============================================
// CONFIGURATION
// ============================================

const CONFIG = {
    API_USER: process.env.API_USER || 'admin',
    API_PASSWORD: process.env.API_PASSWORD,
    BEARER_TOKEN: process.env.BEARER_TOKEN,
    UPLOADS_DIR: path.join(__dirname, 'uploads'),
    DATA_DIR: path.join(__dirname, 'data')
};

if (!process.env.API_PASSWORD || !process.env.BEARER_TOKEN) {
    console.warn('⚠️ AVISO: Credenciais não configuradas no .env. Configure para garantir segurança.');
}

// Create directories if they don't exist (skip on Vercel - read-only filesystem)
if (!process.env.VERCEL) {
    [CONFIG.UPLOADS_DIR, CONFIG.DATA_DIR].forEach(dir => {
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
    });
}

// ============================================
// MIDDLEWARE
// ============================================

app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

// Static files - serve CSS, JS, and assets with correct MIME types
app.use('/uploads', express.static(CONFIG.UPLOADS_DIR));
app.use('/assets', express.static(path.join(__dirname, 'assets')));

// Explicit routes for static files (Vercel compatibility)
app.get('/index.css', (req, res) => {
    res.type('text/css').sendFile(path.join(__dirname, 'index.css'));
});
app.get('/admin.css', (req, res) => {
    res.type('text/css').sendFile(path.join(__dirname, 'admin.css'));
});
app.get('/blog.css', (req, res) => {
    res.type('text/css').sendFile(path.join(__dirname, 'blog.css'));
});
app.get('/main.js', (req, res) => {
    res.type('application/javascript').sendFile(path.join(__dirname, 'main.js'));
});
app.get('/admin.js', (req, res) => {
    res.type('application/javascript').sendFile(path.join(__dirname, 'admin.js'));
});
app.get('/api.js', (req, res) => {
    res.type('application/javascript').sendFile(path.join(__dirname, 'api.js'));
});

// HTML pages
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});
app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, 'admin.html'));
});
app.get('/blog', (req, res) => {
    res.sendFile(path.join(__dirname, 'blog.html'));
});
app.get('/post', (req, res) => {
    res.sendFile(path.join(__dirname, 'post.html'));
});

// Fallback static middleware
app.use(express.static(__dirname));

// Multer configuration
const storage = multer.diskStorage({
    destination: (req, file, cb) => cb(null, CONFIG.UPLOADS_DIR),
    filename: (req, file, cb) => {
        const uniqueName = `${Date.now()}-${uuidv4()}${path.extname(file.originalname)}`;
        cb(null, uniqueName);
    }
});
const upload = multer({ storage, limits: { fileSize: 50 * 1024 * 1024 } });

// Auth middleware instance
const auth = authenticate(CONFIG);

// ============================================
// ROUTES (MVC)
// ============================================

app.use('/wp-json/wp/v2/posts', require('./src/routes/posts.routes')(auth));
app.use('/wp-json/wp/v2/media', require('./src/routes/media.routes')(auth, upload, CONFIG.UPLOADS_DIR));
app.use('/wp-json/wp/v2/categories', require('./src/routes/categories.routes')(auth));
app.use('/wp-json/wp/v2/tags', require('./src/routes/tags.routes')(auth));

// SEO Plugin Endpoint
app.post('/wp-json/robo-seo-api-rest/v1/update-meta', auth, seoController.updateMeta);

// ============================================
// USERS ENDPOINT
// ============================================

app.get('/wp-json/wp/v2/users/me', auth, (req, res) => {
    res.json({
        id: 1,
        name: 'Caas Express',
        slug: 'caas-express',
        email: 'contato@caasexpresss.com',
        roles: ['administrator'],
        capabilities: { administrator: true }
    });
});

app.get('/wp-json/wp/v2/users', auth, (req, res) => {
    res.json([{
        id: 1,
        name: 'Caas Express',
        slug: 'caas-express',
        avatar_urls: {}
    }]);
});

// ============================================
// API INFO
// ============================================

app.get('/wp-json', (req, res) => {
    const baseUrl = `${req.protocol}://${req.get('host')}`;
    res.json({
        name: 'Caas Express Blog',
        description: 'API WordPress-compatible para Caas Express',
        url: baseUrl,
        home: baseUrl,
        gmt_offset: -3,
        timezone_string: 'America/Sao_Paulo',
        namespaces: ['wp/v2', 'robo-seo-api-rest/v1'],
        authentication: {
            'application-passwords': {
                endpoints: {
                    authorization: `${baseUrl}/wp-json/wp/v2/users/me`
                }
            }
        },
        routes: {
            '/wp/v2/posts': { methods: ['GET', 'POST'] },
            '/wp/v2/posts/<id>': { methods: ['GET', 'POST', 'PUT', 'DELETE'] },
            '/wp/v2/media': { methods: ['GET', 'POST'] },
            '/wp/v2/media/<id>': { methods: ['GET', 'POST'] },
            '/wp/v2/categories': { methods: ['GET', 'POST'] },
            '/wp/v2/tags': { methods: ['GET', 'POST'] },
            '/robo-seo-api-rest/v1/update-meta': { methods: ['POST'] }
        }
    });
});

// ============================================
// HEALTH CHECK
// ============================================

app.get('/health', async (req, res) => {
    try {
        const posts = await db.posts.count();
        const media = await db.media.getAll();
        const categories = await db.categories.getAll();
        res.json({
            status: 'ok',
            timestamp: new Date().toISOString(),
            database: 'turso',
            posts: posts,
            media: media.length,
            categories: categories.length
        });
    } catch (e) {
        res.json({
            status: 'initializing',
            timestamp: new Date().toISOString()
        });
    }
});

// ============================================
// START SERVER
// ============================================

async function startServer() {
    // Initialize database first
    await db.initDatabase();

    app.listen(PORT, () => {
        console.log('');
        console.log('╔══════════════════════════════════════════════════════════════╗');
        console.log('║     🏍️  CAAS EXPRESS - WordPress-Compatible REST API        ║');
        console.log('║     📦 SQLite + MVC (Serverless Compatible)                 ║');
        console.log('╠══════════════════════════════════════════════════════════════╣');
        console.log(`║  🌐 API URL:     http://localhost:${PORT}                       ║`);
        console.log(`║  📄 WP REST:     http://localhost:${PORT}/wp-json               ║`);
        console.log('╠══════════════════════════════════════════════════════════════╣');
        console.log('║  🔑 CREDENCIAIS: (configuradas no .env)                     ║');
        console.log('╠══════════════════════════════════════════════════════════════╣');
        console.log('║  📚 ENDPOINTS DISPONÍVEIS:                                   ║');
        console.log('║  • POST /wp-json/wp/v2/posts     - Criar post               ║');
        console.log('║  • POST /wp-json/wp/v2/media     - Upload imagem            ║');
        console.log('║  • POST /wp-json/robo-seo-api-rest/v1/update-meta - SEO     ║');
        console.log('╚══════════════════════════════════════════════════════════════╝');
        console.log('');
    });
}

startServer().catch(console.error);

module.exports = app;
