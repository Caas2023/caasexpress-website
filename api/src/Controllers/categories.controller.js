/**
 * Categories Controller (Turso async version)
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

// GET /wp-json/wp/v2/categories
exports.list = async (req, res) => {
    try {
        const categories = await db.categories.getAll();
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
    } catch (error) {
        console.error('Error listing categories:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// POST /wp-json/wp/v2/categories
exports.create = async (req, res) => {
    try {
        const { name, slug, description, parent } = req.body;

        const category = await db.categories.create({
            name: name || '',
            slug: slug || slugify(name || ''),
            description: description || '',
            parent: parent || 0
        });

        console.log(`[CATEGORY] Criada: "${category.name}" (ID: ${category.id})`);
        res.status(201).json(category);
    } catch (error) {
        console.error('Error creating category:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};
