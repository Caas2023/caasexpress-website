/**
 * Tags Controller
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

// GET /wp-json/wp/v2/tags
exports.list = (req, res) => {
    const tags = db.tags.getAll();
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
};

// POST /wp-json/wp/v2/tags
exports.create = (req, res) => {
    const { name, slug, description } = req.body;

    const tag = db.tags.create({
        name: name || '',
        slug: slug || slugify(name || ''),
        description: description || ''
    });

    console.log(`[TAG] Criada: "${tag.name}" (ID: ${tag.id})`);
    res.status(201).json(tag);
};
