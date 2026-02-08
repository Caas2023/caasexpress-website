/**
 * Web Stories Controller (AMP Stories)
 * Handles Web Stories CRUD operations
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

// GET /wp-json/wp/v2/web-story
exports.list = async (req, res) => {
    try {
        const stories = await db.webStories.getAll();
        res.json(stories.map(story => formatStory(story, req)));
    } catch (error) {
        console.error('Error listing web stories:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// GET /wp-json/wp/v2/web-story/:id
exports.get = async (req, res) => {
    try {
        const story = await db.webStories.getById(req.params.id);
        if (!story) {
            return res.status(404).json({
                code: 'rest_post_invalid_id',
                message: 'ID de web story inválido.',
                data: { status: 404 }
            });
        }
        res.json(formatStory(story, req));
    } catch (error) {
        console.error('Error getting web story:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// POST /wp-json/wp/v2/web-story
exports.create = async (req, res) => {
    try {
        const { title, content, slug, status, poster_portrait, poster_square, poster_landscape, publisher_logo, story_data, date } = req.body;

        const story = await db.webStories.create({
            title: title || '',
            content: content || '',
            slug: slug || slugify(title || `story-${Date.now()}`),
            status: status || 'publish',
            poster_portrait: poster_portrait || '',
            poster_square: poster_square || '',
            poster_landscape: poster_landscape || '',
            publisher_logo: publisher_logo || '',
            story_data: story_data || {},
            date: date || new Date().toISOString()
        });

        console.log(`[WEB STORY] Criado: "${story.title}" (ID: ${story.id})`);
        res.status(201).json(formatStory(story, req));
    } catch (error) {
        console.error('Error creating web story:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// DELETE /wp-json/wp/v2/web-story/:id
exports.remove = async (req, res) => {
    try {
        const story = await db.webStories.getById(req.params.id);
        if (!story) {
            return res.status(404).json({
                code: 'rest_post_invalid_id',
                message: 'ID de web story inválido.',
                data: { status: 404 }
            });
        }

        await db.webStories.delete(req.params.id);
        console.log(`[WEB STORY] Deletado: "${story.title}" (ID: ${story.id})`);
        res.json({ deleted: true, previous: formatStory(story, req) });
    } catch (error) {
        console.error('Error deleting web story:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
};

// GET /web-stories/:slug (Render AMP Story)
exports.render = async (req, res) => {
    try {
        // Find story by slug
        const stories = await db.webStories.getAll();
        const story = stories.find(s => s.slug === req.params.slug);

        if (!story) {
            return res.status(404).send('Web Story not found');
        }

        // If content is already AMP HTML, serve directly
        if (story.content && story.content.includes('<!doctype html>')) {
            res.type('text/html').send(story.content);
        } else {
            // Generate basic AMP story wrapper
            const ampHtml = generateAmpStory(story, req);
            res.type('text/html').send(ampHtml);
        }
    } catch (error) {
        console.error('Error rendering web story:', error);
        res.status(500).send('Error loading story');
    }
};

function formatStory(story, req) {
    const baseUrl = `${req.protocol}://${req.get('host')}`;
    return {
        id: story.id,
        date: story.date,
        date_gmt: story.date_gmt,
        modified: story.modified,
        modified_gmt: story.modified_gmt,
        slug: story.slug,
        status: story.status,
        type: 'web-story',
        link: `${baseUrl}/web-stories/${story.slug}/`,
        title: { rendered: story.title, raw: story.title },
        content: { rendered: story.content, raw: story.content },
        author: story.author,
        story_poster: {
            portrait: story.poster_portrait,
            square: story.poster_square,
            landscape: story.poster_landscape
        },
        publisher_logo_url: story.publisher_logo,
        story_data: story.story_data,
        _links: {
            self: [{ href: `${baseUrl}/wp-json/wp/v2/web-story/${story.id}` }]
        }
    };
}

function generateAmpStory(story, req) {
    const baseUrl = `${req.protocol}://${req.get('host')}`;

    return `<!doctype html>
<html ⚡>
<head>
    <meta charset="utf-8">
    <title>${story.title}</title>
    <link rel="canonical" href="${baseUrl}/web-stories/${story.slug}/">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-story" src="https://cdn.ampproject.org/v0/amp-story-1.0.js"></script>
</head>
<body>
    <amp-story standalone
        title="${story.title}"
        publisher="Caas Express"
        publisher-logo-src="${story.publisher_logo || baseUrl + '/assets/logo.png'}"
        poster-portrait-src="${story.poster_portrait || 'https://via.placeholder.com/720x1280'}"
        poster-square-src="${story.poster_square || 'https://via.placeholder.com/720x720'}"
        poster-landscape-src="${story.poster_landscape || 'https://via.placeholder.com/1280x720'}">
        
        <amp-story-page id="cover">
            <amp-story-grid-layer template="fill">
                <amp-img src="${story.poster_portrait || 'https://via.placeholder.com/720x1280'}"
                    width="720" height="1280"
                    layout="responsive">
                </amp-img>
            </amp-story-grid-layer>
            <amp-story-grid-layer template="vertical">
                <h1 style="color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">${story.title}</h1>
            </amp-story-grid-layer>
        </amp-story-page>
        
    </amp-story>
</body>
</html>`;
}
