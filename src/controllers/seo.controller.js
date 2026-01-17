/**
 * SEO Controller (Turso async version)
 */

const db = require('../models/database');

// POST /wp-json/robo-seo-api-rest/v1/update-meta
exports.updateMeta = async (req, res) => {
    try {
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

        const post = await db.posts.getById(post_id);
        if (!post) {
            return res.status(404).json({
                success: false,
                message: 'Post não encontrado'
            });
        }

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

        await db.posts.update(post_id, { meta: { ...post.meta, seo: seoMeta } });

        console.log(`[SEO] Meta atualizado para post ${post_id}: "${keyword}"`);

        res.json({
            success: true,
            message: 'Meta SEO atualizado com sucesso',
            post_id: parseInt(post_id),
            data: seoMeta
        });
    } catch (error) {
        console.error('Error updating SEO meta:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};
