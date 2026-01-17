/**
 * Turso Database Module (Persistent SQLite in the Cloud)
 * Works on Vercel with persistent storage via Turso
 */

const { createClient } = require('@libsql/client');

let db = null;

// Initialize database connection
async function initDatabase() {
    if (db) return db;

    // Check for Turso credentials
    const url = process.env.TURSO_DATABASE_URL;
    const authToken = process.env.TURSO_AUTH_TOKEN;

    if (!url || !authToken) {
        console.warn('⚠️ Turso credentials not configured. Using in-memory database (data will not persist).');
        // Fallback to in-memory for local development
        db = createClient({ url: ':memory:' });
    } else {
        db = createClient({ url, authToken });
        console.log('✅ Connected to Turso database');
    }

    // Create tables
    await db.execute(`
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL DEFAULT '',
            content TEXT DEFAULT '',
            excerpt TEXT DEFAULT '',
            slug TEXT UNIQUE,
            status TEXT DEFAULT 'draft',
            type TEXT DEFAULT 'post',
            author INTEGER DEFAULT 1,
            featured_media INTEGER DEFAULT 0,
            categories TEXT DEFAULT '[]',
            tags TEXT DEFAULT '[]',
            meta TEXT DEFAULT '{}',
            date TEXT DEFAULT CURRENT_TIMESTAMP,
            date_gmt TEXT DEFAULT CURRENT_TIMESTAMP,
            modified TEXT DEFAULT CURRENT_TIMESTAMP,
            modified_gmt TEXT DEFAULT CURRENT_TIMESTAMP
        )
    `);

    await db.execute(`
        CREATE TABLE IF NOT EXISTS media (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT DEFAULT '',
            slug TEXT,
            source_url TEXT,
            file TEXT,
            mime_type TEXT DEFAULT 'image/jpeg',
            alt_text TEXT DEFAULT '',
            caption TEXT DEFAULT '',
            description TEXT DEFAULT '',
            author INTEGER DEFAULT 1,
            width INTEGER DEFAULT 1200,
            height INTEGER DEFAULT 630,
            date TEXT DEFAULT CURRENT_TIMESTAMP,
            date_gmt TEXT DEFAULT CURRENT_TIMESTAMP,
            modified TEXT DEFAULT CURRENT_TIMESTAMP,
            modified_gmt TEXT DEFAULT CURRENT_TIMESTAMP
        )
    `);

    await db.execute(`
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            description TEXT DEFAULT '',
            parent INTEGER DEFAULT 0,
            count INTEGER DEFAULT 0,
            date TEXT DEFAULT CURRENT_TIMESTAMP
        )
    `);

    await db.execute(`
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            description TEXT DEFAULT '',
            count INTEGER DEFAULT 0,
            date TEXT DEFAULT CURRENT_TIMESTAMP
        )
    `);

    // Seed default categories if empty
    const catResult = await db.execute('SELECT COUNT(*) as count FROM categories');
    if (catResult.rows[0].count === 0) {
        const defaultCategories = [
            ['Sem categoria', 'sem-categoria', '', 0, 0],
            ['Dicas', 'dicas', 'Dicas de entregas', 0, 0],
            ['Serviços', 'servicos', 'Nossos serviços', 0, 0],
            ['Logística', 'logistica', 'Logística e transporte', 0, 0],
            ['Negócios', 'negocios', 'Dicas para negócios', 0, 0]
        ];
        for (const cat of defaultCategories) {
            await db.execute({
                sql: 'INSERT INTO categories (name, slug, description, parent, count) VALUES (?, ?, ?, ?, ?)',
                args: cat
            });
        }
    }

    return db;
}

function getDb() {
    if (!db) throw new Error('Database not initialized. Call initDatabase() first.');
    return db;
}

// ============================================
// REPOSITORY CLASSES
// ============================================

class PostsRepository {
    async getAll(filters = {}) {
        let query = 'SELECT * FROM posts WHERE 1=1';
        const args = [];

        if (filters.status) {
            query += ' AND status = ?';
            args.push(filters.status);
        }
        if (filters.type) {
            query += ' AND type = ?';
            args.push(filters.type);
        }
        if (filters.search) {
            query += ' AND (title LIKE ? OR content LIKE ?)';
            args.push(`%${filters.search}%`, `%${filters.search}%`);
        }

        query += ' ORDER BY date DESC';

        if (filters.per_page) {
            query += ' LIMIT ?';
            args.push(parseInt(filters.per_page));
            if (filters.page) {
                query += ' OFFSET ?';
                args.push((parseInt(filters.page) - 1) * parseInt(filters.per_page));
            }
        }

        const result = await getDb().execute({ sql: query, args });
        return result.rows.map(this._parseRow);
    }

    async getById(id) {
        const result = await getDb().execute({
            sql: 'SELECT * FROM posts WHERE id = ?',
            args: [parseInt(id)]
        });
        return result.rows.length > 0 ? this._parseRow(result.rows[0]) : null;
    }

    async count(filters = {}) {
        let query = 'SELECT COUNT(*) as count FROM posts WHERE 1=1';
        const args = [];
        if (filters.status) {
            query += ' AND status = ?';
            args.push(filters.status);
        }
        if (filters.type) {
            query += ' AND type = ?';
            args.push(filters.type);
        }
        const result = await getDb().execute({ sql: query, args });
        return result.rows[0].count;
    }

    async create(data) {
        const now = new Date().toISOString();
        const result = await getDb().execute({
            sql: `INSERT INTO posts (title, content, excerpt, slug, status, type, author, featured_media, categories, tags, meta, date, date_gmt, modified, modified_gmt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            args: [
                data.title || '',
                data.content || '',
                data.excerpt || '',
                data.slug || '',
                data.status || 'draft',
                data.type || 'post',
                data.author || 1,
                data.featured_media || 0,
                JSON.stringify(data.categories || [1]),
                JSON.stringify(data.tags || []),
                JSON.stringify(data.meta || {}),
                data.date || now,
                data.date_gmt || now,
                now,
                now
            ]
        });
        return this.getById(result.lastInsertRowid);
    }

    async update(id, data) {
        const existing = await this.getById(id);
        if (!existing) return null;

        const now = new Date().toISOString();
        const merged = { ...existing, ...data };

        await getDb().execute({
            sql: `UPDATE posts SET
                    title = ?, content = ?, excerpt = ?, slug = ?, status = ?, type = ?,
                    author = ?, featured_media = ?, categories = ?, tags = ?, meta = ?,
                    modified = ?, modified_gmt = ?
                  WHERE id = ?`,
            args: [
                merged.title,
                merged.content,
                merged.excerpt,
                merged.slug,
                merged.status,
                merged.type,
                merged.author,
                merged.featured_media,
                JSON.stringify(merged.categories),
                JSON.stringify(merged.tags),
                JSON.stringify(merged.meta),
                now,
                now,
                parseInt(id)
            ]
        });
        return this.getById(id);
    }

    async delete(id) {
        await getDb().execute({ sql: 'DELETE FROM posts WHERE id = ?', args: [parseInt(id)] });
        return true;
    }

    _parseRow(row) {
        return {
            ...row,
            categories: JSON.parse(row.categories || '[]'),
            tags: JSON.parse(row.tags || '[]'),
            meta: JSON.parse(row.meta || '{}')
        };
    }
}

class MediaRepository {
    async getAll() {
        const result = await getDb().execute('SELECT * FROM media ORDER BY date DESC');
        return result.rows;
    }

    async getById(id) {
        const result = await getDb().execute({
            sql: 'SELECT * FROM media WHERE id = ?',
            args: [parseInt(id)]
        });
        return result.rows.length > 0 ? result.rows[0] : null;
    }

    async create(data) {
        const now = new Date().toISOString();
        const result = await getDb().execute({
            sql: `INSERT INTO media (title, slug, source_url, file, mime_type, alt_text, caption, description, author, date, date_gmt, modified, modified_gmt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            args: [
                data.title || '',
                data.slug || '',
                data.source_url || '',
                data.file || '',
                data.mime_type || 'image/jpeg',
                data.alt_text || '',
                data.caption || '',
                data.description || '',
                data.author || 1,
                now, now, now, now
            ]
        });
        return this.getById(result.lastInsertRowid);
    }

    async update(id, data) {
        const existing = await this.getById(id);
        if (!existing) return null;

        const now = new Date().toISOString();
        await getDb().execute({
            sql: `UPDATE media SET alt_text = ?, title = ?, caption = ?, description = ?, modified = ?, modified_gmt = ?
                  WHERE id = ?`,
            args: [
                data.alt_text ?? existing.alt_text,
                data.title ?? existing.title,
                data.caption ?? existing.caption,
                data.description ?? existing.description,
                now, now, parseInt(id)
            ]
        });
        return this.getById(id);
    }

    async delete(id) {
        await getDb().execute({ sql: 'DELETE FROM media WHERE id = ?', args: [parseInt(id)] });
        return true;
    }
}

class CategoriesRepository {
    async getAll() {
        const result = await getDb().execute('SELECT * FROM categories ORDER BY name');
        return result.rows;
    }

    async getById(id) {
        const result = await getDb().execute({
            sql: 'SELECT * FROM categories WHERE id = ?',
            args: [parseInt(id)]
        });
        return result.rows.length > 0 ? result.rows[0] : null;
    }

    async create(data) {
        const result = await getDb().execute({
            sql: 'INSERT INTO categories (name, slug, description, parent, count) VALUES (?, ?, ?, ?, ?)',
            args: [data.name, data.slug, data.description || '', data.parent || 0, 0]
        });
        return this.getById(result.lastInsertRowid);
    }
}

class TagsRepository {
    async getAll() {
        const result = await getDb().execute('SELECT * FROM tags ORDER BY name');
        return result.rows;
    }

    async getById(id) {
        const result = await getDb().execute({
            sql: 'SELECT * FROM tags WHERE id = ?',
            args: [parseInt(id)]
        });
        return result.rows.length > 0 ? result.rows[0] : null;
    }

    async create(data) {
        const result = await getDb().execute({
            sql: 'INSERT INTO tags (name, slug, description, count) VALUES (?, ?, ?, ?)',
            args: [data.name, data.slug, data.description || '', 0]
        });
        return this.getById(result.lastInsertRowid);
    }
}

// Export
module.exports = {
    initDatabase,
    getDb,
    posts: new PostsRepository(),
    media: new MediaRepository(),
    categories: new CategoriesRepository(),
    tags: new TagsRepository()
};
