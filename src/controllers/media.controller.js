/**
 * Media Controller (Turso async version)
 */

const db = require('../models/database');
const path = require('path');
const fs = require('fs');

function slugify(text) {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
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

// GET /wp-json/wp/v2/media
exports.list = async (req, res) => {
    try {
        const media = await db.media.getAll();
        res.json(media.map(m => formatMedia(m, req)));
    } catch (error) {
        console.error('Error listing media:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// GET /wp-json/wp/v2/media/:id
exports.get = async (req, res) => {
    try {
        const media = await db.media.getById(req.params.id);
        if (!media) {
            return res.status(404).json({
                code: 'rest_post_invalid_id',
                message: 'ID de mídia inválido.',
                data: { status: 404 }
            });
        }
        res.json(formatMedia(media, req));
    } catch (error) {
        console.error('Error getting media:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// POST /wp-json/wp/v2/media (Upload via multer)
exports.upload = async (req, res) => {
    try {
        if (!req.file) {
            return res.status(400).json({
                code: 'rest_upload_no_data',
                message: 'Nenhum dado de arquivo foi enviado.',
                data: { status: 400 }
            });
        }

        const baseUrl = `${req.protocol}://${req.get('host')}`;
        const filename = req.file.filename;

        const media = await db.media.create({
            title: req.file.originalname || filename,
            slug: slugify(req.file.originalname || filename),
            source_url: `${baseUrl}/uploads/${filename}`,
            file: filename,
            mime_type: req.file.mimetype || 'image/jpeg',
            alt_text: '',
            caption: '',
            description: '',
            author: 1
        });

        console.log(`[MEDIA] Upload: "${media.title}" (ID: ${media.id})`);
        res.status(201).json(formatMedia(media, req));
    } catch (error) {
        console.error('Error uploading media:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// POST /wp-json/wp/v2/media/:id (Update attributes)
exports.update = async (req, res) => {
    try {
        const { alt_text, title, caption, description } = req.body;

        const media = await db.media.update(req.params.id, {
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
    } catch (error) {
        console.error('Error updating media:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// Binary upload handler (for n8n-style uploads)
exports.uploadBinary = (UPLOADS_DIR) => {
    return async (req, res, next) => {
        if (req.file) return next();

        const contentType = req.headers['content-type'] || '';
        const contentDisposition = req.headers['content-disposition'] || '';

        if (contentType.includes('image/') || contentType.includes('application/octet-stream')) {
            try {
                const filenameMatch = contentDisposition.match(/filename="?(.+?)"?(?:;|$)/);
                const filename = filenameMatch ? filenameMatch[1] : `upload-${Date.now()}.jpg`;
                const filepath = path.join(UPLOADS_DIR, `${Date.now()}-${filename}`);

                const chunks = [];
                req.on('data', chunk => chunks.push(chunk));
                req.on('end', async () => {
                    const buffer = Buffer.concat(chunks);
                    fs.writeFileSync(filepath, buffer);

                    const baseUrl = `${req.protocol}://${req.get('host')}`;
                    const savedFilename = path.basename(filepath);

                    const media = await db.media.create({
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
            } catch (error) {
                console.error('Error uploading binary:', error);
                res.status(500).json({ error: 'Internal server error' });
            }
        } else {
            next();
        }
    };
};
