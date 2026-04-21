/**
 * Caas Express Local API Server
 * Recretated to run the JS Backend and Admin Endpoints
 */

const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { authenticate } = require('./src/middleware/auth.middleware');

const app = express();
const PORT = process.env.PORT || 3001;
const UPLOADS_DIR = path.join(__dirname, 'uploads');

if (!fs.existsSync(UPLOADS_DIR)) {
    fs.mkdirSync(UPLOADS_DIR, { recursive: true });
}

// Configs for auth middleware
const CONFIG = {
    API_USER: process.env.API_USER || 'admin',
    API_PASSWORD: process.env.API_PASSWORD || 'caas123',
    BEARER_TOKEN: process.env.BEARER_TOKEN || 'caas_system_token'
};

// CORS fallback for local development
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE');
    res.header('Access-Control-Allow-Headers', 'X-Requested-With,content-type,Authorization');
    if (req.method === 'OPTIONS') return res.sendStatus(200);
    next();
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static uploads
app.use('/uploads', express.static(UPLOADS_DIR));

// Setup Authentication and Multer
const auth = authenticate(CONFIG);
const upload = multer({ dest: UPLOADS_DIR });

// Mount Routes
const API_PREFIX = '/wp-json/wp/v2';
app.use(`${API_PREFIX}/posts`, require('./src/routes/posts.routes')(auth));
app.use(`${API_PREFIX}/categories`, require('./src/routes/categories.routes')(auth));
app.use(`${API_PREFIX}/tags`, require('./src/routes/tags.routes')(auth));
app.use(`${API_PREFIX}/media`, require('./src/routes/media.routes')(auth, upload, UPLOADS_DIR));

// Initialize Database and Start Node Server
const { initDatabase } = require('./src/models/database');

initDatabase().then(() => {
    app.listen(PORT, () => {
        console.log(`[🚀 CaaS Express Backend] Servidor Express Ativo!`);
        console.log(`[🔗 API URL] http://localhost:${PORT}${API_PREFIX}`);
    });
}).catch(err => {
    console.error('❌ Falha ao inicializar banco de dados:', err);
    process.exit(1);
});
