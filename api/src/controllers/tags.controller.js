/**
 * Tags Controller (Turso async version)
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
exports.list = async (req, res) => {
    try {
        const tags = await db.tags.getAll();
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
    } catch (error) {
        console.error('Error listing tags:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// POST /wp-json/wp/v2/tags
exports.create = async (req, res) => {
    try {
        const { name, slug, description } = req.body;

        const tag = await db.tags.create({
            name: name || '',
            slug: slug || slugify(name || ''),
            description: description || ''
        });

        console.log(`[TAG] Criada: "${tag.name}" (ID: ${tag.id})`);
        res.status(201).json(tag);
    } catch (error) {
        console.error('Error creating tag:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};
