/**
 * SQLite Database Module
 * Replaces JSON file storage with SQLite for better performance and data integrity
 */

const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');

const DATA_DIR = path.join(__dirname, '..', '..', 'data');
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
}

const db = new Database(path.join(DATA_DIR, 'database.sqlite'));

// Enable WAL mode for better concurrent read performance
db.pragma('journal_mode = WAL');

// ============================================
// SCHEMA INITIALIZATION
// ============================================

db.exec(`
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
    );

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
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE,
        description TEXT DEFAULT '',
        parent INTEGER DEFAULT 0,
        count INTEGER DEFAULT 0,
        date TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE,
        description TEXT DEFAULT '',
        count INTEGER DEFAULT 0,
        date TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug);
    CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
    CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories(slug);
`);

// ============================================
// SEED DEFAULT CATEGORIES
// ============================================

const catCount = db.prepare('SELECT COUNT(*) as count FROM categories').get();
if (catCount.count === 0) {
    const insertCat = db.prepare('INSERT INTO categories (name, slug, description, parent, count) VALUES (?, ?, ?, ?, ?)');
    const defaultCategories = [
        ['Sem categoria', 'sem-categoria', '', 0, 0],
        ['Dicas', 'dicas', 'Dicas de entregas', 0, 0],
        ['Serviços', 'servicos', 'Nossos serviços', 0, 0],
        ['Logística', 'logistica', 'Logística e transporte', 0, 0],
        ['Negócios', 'negocios', 'Dicas para negócios', 0, 0]
    ];
    const insertMany = db.transaction((cats) => {
        for (const cat of cats) insertCat.run(...cat);
    });
    insertMany(defaultCategories);
}

// ============================================
// REPOSITORY CLASSES
// ============================================

class PostsRepository {
    getAll(filters = {}) {
        let query = 'SELECT * FROM posts WHERE 1=1';
        const params = [];

        if (filters.status) {
            query += ' AND status = ?';
            params.push(filters.status);
        }
        if (filters.type) {
            query += ' AND type = ?';
            params.push(filters.type);
        }
        if (filters.search) {
            query += ' AND (title LIKE ? OR content LIKE ?)';
            params.push(`%${filters.search}%`, `%${filters.search}%`);
        }

        query += ' ORDER BY date DESC';

        if (filters.per_page) {
            query += ' LIMIT ?';
            params.push(parseInt(filters.per_page));
            if (filters.page) {
                query += ' OFFSET ?';
                params.push((parseInt(filters.page) - 1) * parseInt(filters.per_page));
            }
        }

        const rows = db.prepare(query).all(...params);
        return rows.map(this._parseRow);
    }

    getById(id) {
        const row = db.prepare('SELECT * FROM posts WHERE id = ?').get(id);
        return row ? this._parseRow(row) : null;
    }

    count(filters = {}) {
        let query = 'SELECT COUNT(*) as count FROM posts WHERE 1=1';
        const params = [];
        if (filters.status) {
            query += ' AND status = ?';
            params.push(filters.status);
        }
        if (filters.type) {
            query += ' AND type = ?';
            params.push(filters.type);
        }
        return db.prepare(query).get(...params).count;
    }

    create(data) {
        const now = new Date().toISOString();
        const stmt = db.prepare(`
            INSERT INTO posts (title, content, excerpt, slug, status, type, author, featured_media, categories, tags, meta, date, date_gmt, modified, modified_gmt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `);
        const result = stmt.run(
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
        );
        return this.getById(result.lastInsertRowid);
    }

    update(id, data) {
        const existing = this.getById(id);
        if (!existing) return null;

        const now = new Date().toISOString();
        const merged = { ...existing, ...data };

        const stmt = db.prepare(`
            UPDATE posts SET
                title = ?, content = ?, excerpt = ?, slug = ?, status = ?, type = ?,
                author = ?, featured_media = ?, categories = ?, tags = ?, meta = ?,
                modified = ?, modified_gmt = ?
            WHERE id = ?
        `);
        stmt.run(
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
            id
        );
        return this.getById(id);
    }

    delete(id) {
        const result = db.prepare('DELETE FROM posts WHERE id = ?').run(id);
        return result.changes > 0;
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
    getAll() {
        return db.prepare('SELECT * FROM media ORDER BY date DESC').all();
    }

    getById(id) {
        return db.prepare('SELECT * FROM media WHERE id = ?').get(id);
    }

    create(data) {
        const now = new Date().toISOString();
        const stmt = db.prepare(`
            INSERT INTO media (title, slug, source_url, file, mime_type, alt_text, caption, description, author, date, date_gmt, modified, modified_gmt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `);
        const result = stmt.run(
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
        );
        return this.getById(result.lastInsertRowid);
    }

    update(id, data) {
        const existing = this.getById(id);
        if (!existing) return null;

        const now = new Date().toISOString();
        const stmt = db.prepare(`
            UPDATE media SET alt_text = ?, title = ?, caption = ?, description = ?, modified = ?, modified_gmt = ?
            WHERE id = ?
        `);
        stmt.run(
            data.alt_text ?? existing.alt_text,
            data.title ?? existing.title,
            data.caption ?? existing.caption,
            data.description ?? existing.description,
            now, now, id
        );
        return this.getById(id);
    }

    delete(id) {
        const result = db.prepare('DELETE FROM media WHERE id = ?').run(id);
        return result.changes > 0;
    }
}

class CategoriesRepository {
    getAll() {
        return db.prepare('SELECT * FROM categories ORDER BY name').all();
    }

    getById(id) {
        return db.prepare('SELECT * FROM categories WHERE id = ?').get(id);
    }

    create(data) {
        const stmt = db.prepare('INSERT INTO categories (name, slug, description, parent, count) VALUES (?, ?, ?, ?, ?)');
        const result = stmt.run(data.name, data.slug, data.description || '', data.parent || 0, 0);
        return this.getById(result.lastInsertRowid);
    }
}

class TagsRepository {
    getAll() {
        return db.prepare('SELECT * FROM tags ORDER BY name').all();
    }

    getById(id) {
        return db.prepare('SELECT * FROM tags WHERE id = ?').get(id);
    }

    create(data) {
        const stmt = db.prepare('INSERT INTO tags (name, slug, description, count) VALUES (?, ?, ?, ?)');
        const result = stmt.run(data.name, data.slug, data.description || '', 0);
        return this.getById(result.lastInsertRowid);
    }
}

// Export singleton instances
module.exports = {
    db,
    posts: new PostsRepository(),
    media: new MediaRepository(),
    categories: new CategoriesRepository(),
    tags: new TagsRepository()
};
