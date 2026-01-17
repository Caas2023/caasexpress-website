/**
 * Posts Controller
 * Handles all posts-related business logic
 */

const db = require('../models/database');

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

// GET /wp-json/wp/v2/posts
exports.list = (req, res) => {
    const { per_page = 10, page = 1, status, categories, tags, search, type } = req.query;

    const filters = { per_page, page, status, search, type };
    let posts = db.posts.getAll(filters);

    // Filter by categories/tags (JSON arrays in SQLite)
    if (categories) {
        const catId = parseInt(categories);
        posts = posts.filter(p => p.categories.includes(catId));
    }
    if (tags) {
        const tagId = parseInt(tags);
        posts = posts.filter(p => p.tags.includes(tagId));
    }

    const total = db.posts.count({ status, type });
    const totalPages = Math.ceil(total / per_page);

    res.set({
        'X-WP-Total': total,
        'X-WP-TotalPages': totalPages
    });

    res.json(posts.map(p => formatPost(p, req)));
};

// GET /wp-json/wp/v2/posts/:id
exports.get = (req, res) => {
    const post = db.posts.getById(req.params.id);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }
    res.json(formatPost(post, req));
};

// POST /wp-json/wp/v2/posts
exports.create = (req, res) => {
    const { title, content, excerpt, status, categories, tags, featured_media, author, date, slug, type } = req.body;

    const post = db.posts.create({
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
};

// PUT/POST /wp-json/wp/v2/posts/:id
exports.update = (req, res) => {
    const post = db.posts.update(req.params.id, req.body);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }
    console.log(`[PUT] Atualizado: "${post.title}" (ID: ${post.id})`);
    res.json(formatPost(post, req));
};

// DELETE /wp-json/wp/v2/posts/:id
exports.remove = (req, res) => {
    const post = db.posts.getById(req.params.id);
    if (!post) {
        return res.status(404).json({
            code: 'rest_post_invalid_id',
            message: 'ID de post inválido.',
            data: { status: 404 }
        });
    }

    db.posts.delete(req.params.id);
    console.log(`[DELETE] Deletado: "${post.title}" (ID: ${post.id})`);
    res.json({ deleted: true, previous: formatPost(post, req) });
};
