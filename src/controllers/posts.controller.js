/**
 * Posts Controller
 * Handles all posts-related business logic (Turso async version)
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
exports.list = async (req, res) => {
    try {
        const { per_page = 10, page = 1, status, categories, tags, search, type, slug } = req.query;

        const filters = { per_page, page, status, search, type, slug };
        let posts = await db.posts.getAll(filters);

        // Filter by categories/tags (JSON arrays)
        if (categories) {
            const catId = parseInt(categories);
            posts = posts.filter(p => p.categories.includes(catId));
        }
        if (tags) {
            const tagId = parseInt(tags);
            posts = posts.filter(p => p.tags.includes(tagId));
        }

        const total = await db.posts.count({ status, type });
        const totalPages = Math.ceil(total / per_page);

        res.set({
            'X-WP-Total': total,
            'X-WP-TotalPages': totalPages
        });

        res.json(posts.map(p => formatPost(p, req)));
    } catch (error) {
        console.error('Error listing posts:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// GET /wp-json/wp/v2/posts/:id
exports.get = async (req, res) => {
    try {
        const post = await db.posts.getById(req.params.id);
        if (!post) {
            return res.status(404).json({
                code: 'rest_post_invalid_id',
                message: 'ID de post inválido.',
                data: { status: 404 }
            });
        }
        res.json(formatPost(post, req));
    } catch (error) {
        console.error('Error getting post:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// POST /wp-json/wp/v2/posts
exports.create = async (req, res) => {
    try {
        const { title, content, excerpt, status, categories, tags, featured_media, author, date, slug, type } = req.body;

        const post = await db.posts.create({
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
    } catch (error) {
        console.error('Error creating post:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// PUT/POST /wp-json/wp/v2/posts/:id
exports.update = async (req, res) => {
    try {
        const post = await db.posts.update(req.params.id, req.body);
        if (!post) {
            return res.status(404).json({
                code: 'rest_post_invalid_id',
                message: 'ID de post inválido.',
                data: { status: 404 }
            });
        }
        console.log(`[PUT] Atualizado: "${post.title}" (ID: ${post.id})`);
        res.json(formatPost(post, req));
    } catch (error) {
        console.error('Error updating post:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// DELETE /wp-json/wp/v2/posts/:id
exports.remove = async (req, res) => {
    try {
        const post = await db.posts.getById(req.params.id);
        if (!post) {
            return res.status(404).json({
                code: 'rest_post_invalid_id',
                message: 'ID de post inválido.',
                data: { status: 404 }
            });
        }

        await db.posts.delete(req.params.id);
        console.log(`[DELETE] Deletado: "${post.title}" (ID: ${post.id})`);
        res.json({ deleted: true, previous: formatPost(post, req) });
    } catch (error) {
        console.error('Error deleting post:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};
