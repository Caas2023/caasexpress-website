/**
 * Caas Express - WordPress-Compatible REST API
 * Backend para integração com n8n e automações externas
 * 
 * Endpoints compatíveis com WordPress REST API v2:
 * - POST /wp-json/wp/v2/posts - Criar post
 * - GET /wp-json/wp/v2/posts - Listar posts
 * - GET /wp-json/wp/v2/posts/:id - Obter post
 * - PUT /wp-json/wp/v2/posts/:id - Atualizar post
 * - DELETE /wp-json/wp/v2/posts/:id - Deletar post
 * - POST /wp-json/wp/v2/media - Upload de mídia
 * - POST /wp-json/wp/v2/media/:id - Atualizar mídia
 * - GET /wp-json/wp/v2/categories - Listar categorias
 * - GET /wp-json/wp/v2/tags - Listar tags
 * - POST /wp-json/robo-seo-api-rest/v1/update-meta - Atualizar SEO
 */

const express = require('express');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { v4: uuidv4 } = require('uuid');

const app = express();
const PORT = process.env.PORT || 3001;

// ============================================
// CONFIGURAÇÃO
// ============================================

const CONFIG = {
    // Credenciais de API (Application Password style)
    API_USER: 'admin',
    API_PASSWORD: 'caas express 2024 api key', // Formato WordPress Application Password

    // Token Bearer alternativo
    BEARER_TOKEN: 'caas_api_2024_secret_key',

    // Diretórios
    UPLOADS_DIR: path.join(__dirname, 'uploads'),
    DATA_DIR: path.join(__dirname, 'data')
};

// Criar diretórios se não existirem
[CONFIG.UPLOADS_DIR, CONFIG.DATA_DIR].forEach(dir => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
});

// ============================================
// MIDDLEWARE
// ============================================

app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));
app.use('/uploads', express.static(CONFIG.UPLOADS_DIR));
app.use(express.static(__dirname));

// Configuração do Multer para upload de arquivos
const storage = multer.diskStorage({
    destination: (req, file, cb) => cb(null, CONFIG.UPLOADS_DIR),
    filename: (req, file, cb) => {
        const uniqueName = `${Date.now()}-${uuidv4()}${path.extname(file.originalname)}`;
        cb(null, uniqueName);
    }
});
const upload = multer({ storage, limits: { fileSize: 50 * 1024 * 1024 } });

// ============================================
// AUTENTICAÇÃO (WordPress-compatible)
// ============================================

function authenticate(req, res, next) {
    const authHeader = req.headers.authorization || '';

    // Basic Auth (WordPress Application Password)
    if (authHeader.startsWith('Basic ')) {
        try {
            const base64 = authHeader.split(' ')[1];
            const decoded = Buffer.from(base64, 'base64').toString('utf-8');
            const [user, password] = decoded.split(':');

            // Normalizar password (WordPress usa espaços)
            const normalizedPassword = password.replace(/\s+/g, ' ').trim();
            const configPassword = CONFIG.API_PASSWORD.replace(/\s+/g, ' ').trim();

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

    // Sem autenticação - permitir GET requests
    if (req.method === 'GET') {
        return next();
    }

    return res.status(401).json({
        code: 'rest_not_logged_in',
        message: 'Você não tem permissão para fazer isso.',
        data: { status: 401 }
    });
}

// ============================================
// DATABASE (JSON-based)
// ============================================

class Database {
    constructor(name) {
        this.file = path.join(CONFIG.DATA_DIR, `${name}.json`);
        this.data = this.load();
    }

    load() {
        try {
            if (fs.existsSync(this.file)) {
                return JSON.parse(fs.readFileSync(this.file, 'utf-8'));
            }
        } catch (e) {
            console.error(`Error loading ${this.file}:`, e);
        }
        return [];
    }

    save() {
        fs.writeFileSync(this.file, JSON.stringify(this.data, null, 2));
    }

    getAll() {
        return this.data;
    }

    getById(id) {
        return this.data.find(item => item.id === parseInt(id));
    }

    create(item) {
        const newItem = {
            id: this.data.length > 0 ? Math.max(...this.data.map(i => i.id)) + 1 : 1,
            ...item,
            date: item.date || new Date().toISOString(),
            date_gmt: item.date_gmt || new Date().toISOString(),
            modified: new Date().toISOString(),
            modified_gmt: new Date().toISOString()
        };
        this.data.push(newItem);
        this.save();
        return newItem;
    }

    update(id, updates) {
        const index = this.data.findIndex(item => item.id === parseInt(id));
        if (index === -1) return null;

        this.data[index] = {
            ...this.data[index],
            ...updates,
            modified: new Date().toISOString(),
            modified_gmt: new Date().toISOString()
        };
        this.save();
        return this.data[index];
    }

    delete(id) {
        const index = this.data.findIndex(item => item.id === parseInt(id));
        if (index === -1) return false;

        this.data.splice(index, 1);
        this.save();
        return true;
    }
}

// Inicializar bancos de dados
const postsDB = new Database('posts');
const mediaDB = new Database('media');
const categoriesDB = new Database('categories');
const tagsDB = new Database('tags');

// Inicializar categorias padrão se vazio
if (categoriesDB.getAll().length === 0) {
    const defaultCategories = [
        { id: 1, name: 'Sem categoria', slug: 'sem-categoria', description: '', parent: 0, count: 0 },
        { id: 2, name: 'Dicas', slug: 'dicas', description: 'Dicas de entregas', parent: 0, count: 0 },
        { id: 3, name: 'Serviços', slug: 'servicos', description: 'Nossos serviços', parent: 0, count: 0 },
        { id: 4, name: 'Logística', slug: 'logistica', description: 'Logística e transporte', parent: 0, count: 0 },
        { id: 5, name: 'Negócios', slug: 'negocios', description: 'Dicas para negócios', parent: 0, count: 0 }
    ];
    defaultCategories.forEach(cat => categoriesDB.create(cat));
}

// ============================================
// HELPERS
// ============================================

function slugify(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function formatPost(post, req) {
    const baseUrl = `${req.protocol}://${req.get('host')}`;
    return {
        id: post.id,
        date: post.date,
        date_gmt: post.date_gmt,
        guid: { rendered: `${baseUrl}/?p=${post.id}` },
        modified: post.modified,
        modified_gmt: post.modified_gmt,
        slug: post.slug,
        status: post.status || 'publish',
        type: post.type || 'post',
        link: `${baseUrl}/blog/${post.slug}`,
        title: { rendered: post.title, raw: post.title },
        content: { rendered: post.content, raw: post.content, protected: false },
        excerpt: { rendered: post.excerpt, raw: post.excerpt, protected: false },
        author: post.author || 1,
        featured_media: post.featured_media || 0,
        categories: post.categories || [1],
        tags: post.tags || [],
        meta: post.meta || {},
        _links: {
            self: [{ href: `${baseUrl}/wp-json/wp/v2/posts/${post.id}` }],
            collection: [{ href: `${baseUrl}/wp-json/wp/v2/posts` }]
        }
    };
}

function formatMedia(media, req) {
    const baseUrl = `${req.protocol}://${req.get('host')}`;
    return {
        id: media.id,
        date: media.date,
        date_gmt: media.date_gmt,
        guid: { rendered: media.source_url },
        modified: media.modified,
        modified_gmt: media.modified_gmt,
        slug: media.slug,
        status: 'inherit',
        type: 'attachment',
        link: media.source_url,
        title: { rendered: media.title || '', raw: media.title || '' },
        author: media.author || 1,
        alt_text: media.alt_text || '',
        caption: { rendered: media.caption || '', raw: media.caption || '' },
        description: { rendered: media.description || '', raw: media.description || '' },
        media_type: 'image',
        mime_type: media.mime_type || 'image/jpeg',
        source_url: media.source_url,
        media_details: {
            width: media.width || 1200,
            height: media.height || 630,
            file: media.file,
            sizes: {}
        },
        _links: {
            self: [{ href: `${baseUrl}/wp-json/wp/v2/media/${media.id}` }]
        }
    };
}

// ============================================
// POSTS ENDPOINTS
// ============================================

// GET /wp-json/wp/v2/posts
app.get('/wp-json/wp/v2/posts', authenticate, (req, res) => {
    const { per_page = 10, page = 1, status, categories, tags, search } = req.query;
    let posts = postsDB.getAll();

    // Filtros
    if (status) posts = posts.filter(p => p.status === status);
    if (categories) posts = posts.filter(p => p.categories?.includes(parseInt(categories)));
    if (tags) posts = posts.filter(p => p.tags?.includes(parseInt(tags)));
    if (search) {
        const searchLower = search.toLowerCase();
        posts = posts.filter(p =>
            p.title?.toLowerCase().includes(searchLower) ||
            p.content?.toLowerCase().includes(searchLower)
        );
    }

    // Paginação
    const total = posts.length;
    const totalPages = Math.ceil(total / per_page);
    const start = (page - 1) * per_page;
    posts = posts.slice(start, start + parseInt(per_page));

    res.set({
        'X-WP-Total': total,
        'X-WP-TotalPages': totalPages
    });

    res.json(posts.map(p => formatPost(p, req)));
});

// GET /wp-json/wp/v2/posts/:id
app.get('/wp-json/wp/v2/posts/:id', authenticate, (req, res) => {
    const post = postsDB.getById(req.params.id);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }
    res.json(formatPost(post, req));
});

// POST /wp-json/wp/v2/posts
app.post('/wp-json/wp/v2/posts', authenticate, (req, res) => {
    const { title, content, excerpt, status, categories, tags, featured_media, author, date, slug, type } = req.body;

    const post = postsDB.create({
        title: title || '',
        content: content || '',
        excerpt: excerpt || '',
        slug: slug || slugify(title || `post-${Date.now()}`),
        status: status || 'draft',
        type: type || 'post',
        categories: categories ? (Array.isArray(categories) ? categories.map(Number) : [parseInt(categories)]) : [1],
        tags: tags ? (Array.isArray(tags) ? tags.map(Number) : [parseInt(tags)]) : [],
        featured_media: featured_media ? parseInt(featured_media) : 0,
        author: author ? parseInt(author) : 1,
        date: date || new Date().toISOString(),
        meta: {}
    });

    console.log(`[POST] Criado: "${post.title}" (ID: ${post.id})`);
    res.status(201).json(formatPost(post, req));
});

// PUT/POST /wp-json/wp/v2/posts/:id
app.put('/wp-json/wp/v2/posts/:id', authenticate, (req, res) => {
    const post = postsDB.update(req.params.id, req.body);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }
    console.log(`[PUT] Atualizado: "${post.title}" (ID: ${post.id})`);
    res.json(formatPost(post, req));
});

app.post('/wp-json/wp/v2/posts/:id', authenticate, (req, res) => {
    const post = postsDB.update(req.params.id, req.body);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }
    console.log(`[POST UPDATE] Atualizado: "${post.title}" (ID: ${post.id})`);
    res.json(formatPost(post, req));
});

// DELETE /wp-json/wp/v2/posts/:id
app.delete('/wp-json/wp/v2/posts/:id', authenticate, (req, res) => {
    const post = postsDB.getById(req.params.id);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }

    postsDB.delete(req.params.id);
    console.log(`[DELETE] Deletado: "${post.title}" (ID: ${post.id})`);
    res.json({ deleted: true, previous: formatPost(post, req) });
});

// ============================================
// MEDIA ENDPOINTS
// ============================================

// GET /wp-json/wp/v2/media
app.get('/wp-json/wp/v2/media', authenticate, (req, res) => {
    const media = mediaDB.getAll();
    res.json(media.map(m => formatMedia(m, req)));
});

// GET /wp-json/wp/v2/media/:id
app.get('/wp-json/wp/v2/media/:id', authenticate, (req, res) => {
    const media = mediaDB.getById(req.params.id);
    if (!media) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de mídia inválido.',
            data: { status: 404 }
        });
    }
    res.json(formatMedia(media, req));
});

// POST /wp-json/wp/v2/media (Upload)
app.post('/wp-json/wp/v2/media', authenticate, upload.single('file'), (req, res) => {
    if (!req.file) {
        // Se não houver arquivo, pode ser um upload binário direto
        // Verificar content-disposition header
        const contentDisposition = req.headers['content-disposition'] || '';
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);

        if (req.body && Object.keys(req.body).length === 0) {
            // Upload binário direto - ler do body raw
            return res.status(400).json({
                code: 'rest_upload_no_data',
                message: 'Nenhum dado de arquivo foi enviado.',
                data: { status: 400 }
            });
        }
    }

    const baseUrl = `${req.protocol}://${req.get('host')}`;
    const filename = req.file ? req.file.filename : `upload-${Date.now()}.jpg`;

    const media = mediaDB.create({
        title: req.file?.originalname || filename,
        slug: slugify(req.file?.originalname || filename),
        source_url: `${baseUrl}/uploads/${filename}`,
        file: filename,
        mime_type: req.file?.mimetype || 'image/jpeg',
        alt_text: '',
        caption: '',
        description: '',
        author: 1
    });

    console.log(`[MEDIA] Upload: "${media.title}" (ID: ${media.id})`);
    res.status(201).json(formatMedia(media, req));
});

// POST /wp-json/wp/v2/media/:id (Atualizar atributos)
app.post('/wp-json/wp/v2/media/:id', authenticate, (req, res) => {
    const { alt_text, title, caption, description } = req.body;

    const media = mediaDB.update(req.params.id, {
        alt_text: alt_text || '',
        title: title || '',
        caption: caption || '',
        description: description || ''
    });

    if (!media) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de mídia inválido.',
            data: { status: 404 }
        });
    }

    console.log(`[MEDIA UPDATE] Atualizado: "${media.title}" (ID: ${media.id})`);
    res.json(formatMedia(media, req));
});

// Upload binário direto (como o n8n envia)
app.post('/wp-json/wp/v2/media/', authenticate, (req, res, next) => {
    // Se já foi processado pelo multer, pular
    if (req.file) return next();

    const contentType = req.headers['content-type'] || '';
    const contentDisposition = req.headers['content-disposition'] || '';

    // Verificar se é upload binário
    if (contentType.includes('image/') || contentType.includes('application/octet-stream')) {
        const filenameMatch = contentDisposition.match(/filename="?(.+?)"?(?:;|$)/);
        const filename = filenameMatch ? filenameMatch[1] : `upload-${Date.now()}.jpg`;
        const filepath = path.join(CONFIG.UPLOADS_DIR, `${Date.now()}-${filename}`);

        // Coletar dados binários
        const chunks = [];
        req.on('data', chunk => chunks.push(chunk));
        req.on('end', () => {
            const buffer = Buffer.concat(chunks);
            fs.writeFileSync(filepath, buffer);

            const baseUrl = `${req.protocol}://${req.get('host')}`;
            const savedFilename = path.basename(filepath);

            const media = mediaDB.create({
                title: filename,
                slug: slugify(filename),
                source_url: `${baseUrl}/uploads/${savedFilename}`,
                file: savedFilename,
                mime_type: contentType,
                alt_text: '',
                caption: '',
                description: '',
                author: 1
            });

            console.log(`[MEDIA BINARY] Upload: "${media.title}" (ID: ${media.id})`);
            res.status(201).json(formatMedia(media, req));
        });
    } else {
        next();
    }
});

// ============================================
// CATEGORIES ENDPOINTS
// ============================================

// GET /wp-json/wp/v2/categories
app.get('/wp-json/wp/v2/categories', authenticate, (req, res) => {
    const categories = categoriesDB.getAll();
    res.json(categories.map(cat => ({
        id: cat.id,
        count: cat.count || 0,
        description: cat.description || '',
        link: `${req.protocol}://${req.get('host')}/categoria/${cat.slug}`,
        name: cat.name,
        slug: cat.slug,
        taxonomy: 'category',
        parent: cat.parent || 0,
        meta: []
    })));
});

// POST /wp-json/wp/v2/categories
app.post('/wp-json/wp/v2/categories', authenticate, (req, res) => {
    const { name, slug, description, parent } = req.body;

    const category = categoriesDB.create({
        name: name || '',
        slug: slug || slugify(name || ''),
        description: description || '',
        parent: parent || 0,
        count: 0
    });

    console.log(`[CATEGORY] Criada: "${category.name}" (ID: ${category.id})`);
    res.status(201).json(category);
});

// ============================================
// TAGS ENDPOINTS
// ============================================

// GET /wp-json/wp/v2/tags
app.get('/wp-json/wp/v2/tags', authenticate, (req, res) => {
    const tags = tagsDB.getAll();
    res.json(tags.map(tag => ({
        id: tag.id,
        count: tag.count || 0,
        description: tag.description || '',
        link: `${req.protocol}://${req.get('host')}/tag/${tag.slug}`,
        name: tag.name,
        slug: tag.slug,
        taxonomy: 'post_tag',
        meta: []
    })));
});

// POST /wp-json/wp/v2/tags
app.post('/wp-json/wp/v2/tags', authenticate, (req, res) => {
    const { name, slug, description } = req.body;

    const tag = tagsDB.create({
        name: name || '',
        slug: slug || slugify(name || ''),
        description: description || '',
        count: 0
    });

    console.log(`[TAG] Criada: "${tag.name}" (ID: ${tag.id})`);
    res.status(201).json(tag);
});

// ============================================
// SEO PLUGIN ENDPOINT (Robô SEO)
// ============================================

// POST /wp-json/robo-seo-api-rest/v1/update-meta
app.post('/wp-json/robo-seo-api-rest/v1/update-meta', authenticate, (req, res) => {
    const {
        post_id,
        keyword,
        title,
        description,
        link_internal,
        faq,
        faq_title,
        article_type,
        blog_posting_data
    } = req.body;

    const post = postsDB.getById(post_id);
    if (!post) {
        return res.status(404).json({
            success: false,
            message: 'Post não encontrado'
        });
    }

    // Atualizar meta SEO
    const seoMeta = {
        focus_keyword: keyword,
        seo_title: title,
        seo_description: description,
        link_internal: link_internal,
        faq: faq || [],
        faq_title: faq_title || '',
        article_type: article_type || 'BlogPosting',
        blog_posting_data: blog_posting_data || {},
        updated_at: new Date().toISOString()
    };

    postsDB.update(post_id, { meta: { ...post.meta, seo: seoMeta } });

    console.log(`[SEO] Meta atualizado para post ${post_id}: "${keyword}"`);

    res.json({
        success: true,
        message: 'Meta SEO atualizado com sucesso',
        post_id: parseInt(post_id),
        data: seoMeta
    });
});

// ============================================
// USERS ENDPOINT
// ============================================

// GET /wp-json/wp/v2/users/me
app.get('/wp-json/wp/v2/users/me', authenticate, (req, res) => {
    res.json({
        id: 1,
        name: 'Caas Express',
        slug: 'caas-express',
        email: 'contato@caasexpresss.com',
        roles: ['administrator'],
        capabilities: { administrator: true }
    });
});

// GET /wp-json/wp/v2/users
app.get('/wp-json/wp/v2/users', authenticate, (req, res) => {
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

// GET /wp-json
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

app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        timestamp: new Date().toISOString(),
        posts: postsDB.getAll().length,
        media: mediaDB.getAll().length,
        categories: categoriesDB.getAll().length
    });
});

// ============================================
// INICIAR SERVIDOR
// ============================================

app.listen(PORT, () => {
    console.log('');
    console.log('╔══════════════════════════════════════════════════════════════╗');
    console.log('║     🏍️  CAAS EXPRESS - WordPress-Compatible REST API        ║');
    console.log('╠══════════════════════════════════════════════════════════════╣');
    console.log(`║  🌐 API URL:     http://localhost:${PORT}                       ║`);
    console.log(`║  📄 WP REST:     http://localhost:${PORT}/wp-json               ║`);
    console.log('╠══════════════════════════════════════════════════════════════╣');
    console.log('║  🔑 CREDENCIAIS PARA N8N:                                    ║');
    console.log(`║  • Usuário:      ${CONFIG.API_USER}                                  ║`);
    console.log(`║  • Senha:        ${CONFIG.API_PASSWORD}              ║`);
    console.log(`║  • Bearer:       ${CONFIG.BEARER_TOKEN}            ║`);
    console.log('╠══════════════════════════════════════════════════════════════╣');
    console.log('║  📚 ENDPOINTS DISPONÍVEIS:                                   ║');
    console.log('║  • POST /wp-json/wp/v2/posts     - Criar post               ║');
    console.log('║  • POST /wp-json/wp/v2/media     - Upload imagem            ║');
    console.log('║  • POST /wp-json/robo-seo-api-rest/v1/update-meta - SEO     ║');
    console.log('╚══════════════════════════════════════════════════════════════╝');
    console.log('');
});

module.exports = app;
