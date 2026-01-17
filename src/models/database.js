/**
 * SQLite Database Module (sql.js - Serverless Compatible)
 * Works on Vercel, Netlify, and other serverless platforms
 */

const initSqlJs = require('sql.js');
const path = require('path');
const fs = require('fs');

const DATA_DIR = path.join(__dirname, '..', '..', 'data');
const DB_PATH = path.join(DATA_DIR, 'database.sqlite');

let db = null;
let SQL = null;

// Initialize database
async function initDatabase() {
    if (db) return db;

    SQL = await initSqlJs();

    // Create data directory if needed
    if (!fs.existsSync(DATA_DIR)) {
        fs.mkdirSync(DATA_DIR, { recursive: true });
    }

    // Load existing database or create new
    try {
        if (fs.existsSync(DB_PATH)) {
            const buffer = fs.readFileSync(DB_PATH);
            db = new SQL.Database(buffer);
        } else {
            db = new SQL.Database();
        }
    } catch (e) {
        console.log('Creating new database');
        db = new SQL.Database();
    }

    // Create tables
    db.run(`
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

    db.run(`
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

    db.run(`
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

    db.run(`
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            description TEXT DEFAULT '',
            count INTEGER DEFAULT 0,
            date TEXT DEFAULT CURRENT_TIMESTAMP
        )
    `);

    // Seed default categories
    const catCount = db.exec('SELECT COUNT(*) as count FROM categories')[0];
    if (!catCount || catCount.values[0][0] === 0) {
        const defaultCategories = [
            ['Sem categoria', 'sem-categoria', '', 0, 0],
            ['Dicas', 'dicas', 'Dicas de entregas', 0, 0],
            ['Serviços', 'servicos', 'Nossos serviços', 0, 0],
            ['Logística', 'logistica', 'Logística e transporte', 0, 0],
            ['Negócios', 'negocios', 'Dicas para negócios', 0, 0]
        ];
        for (const cat of defaultCategories) {
            db.run('INSERT INTO categories (name, slug, description, parent, count) VALUES (?, ?, ?, ?, ?)', cat);
        }
    }

    saveDatabase();
    return db;
}

function saveDatabase() {
    if (db) {
        const data = db.export();
        const buffer = Buffer.from(data);
        fs.writeFileSync(DB_PATH, buffer);
    }
}

function getDb() {
    if (!db) throw new Error('Database not initialized. Call initDatabase() first.');
    return db;
}

// Helper to convert sql.js result to array of objects
function toObjects(result) {
    if (!result || result.length === 0) return [];
    const [{ columns, values }] = result;
    return values.map(row => {
        const obj = {};
        columns.forEach((col, i) => obj[col] = row[i]);
        return obj;
    });
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

        const stmt = getDb().prepare(query);
        if (params.length) stmt.bind(params);
        const rows = [];
        while (stmt.step()) rows.push(stmt.getAsObject());
        stmt.free();
        return rows.map(this._parseRow);
    }

    getById(id) {
        const stmt = getDb().prepare('SELECT * FROM posts WHERE id = ?');
        stmt.bind([parseInt(id)]);
        if (stmt.step()) {
            const row = stmt.getAsObject();
            stmt.free();
            return this._parseRow(row);
        }
        stmt.free();
        return null;
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
        const result = getDb().exec(query, params);
        return result.length ? result[0].values[0][0] : 0;
    }

    create(data) {
        const now = new Date().toISOString();
        getDb().run(`
            INSERT INTO posts (title, content, excerpt, slug, status, type, author, featured_media, categories, tags, meta, date, date_gmt, modified, modified_gmt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [
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
        ]);
        saveDatabase();
        const result = getDb().exec('SELECT last_insert_rowid() as id');
        const id = result[0].values[0][0];
        return this.getById(id);
    }

    update(id, data) {
        const existing = this.getById(id);
        if (!existing) return null;

        const now = new Date().toISOString();
        const merged = { ...existing, ...data };

        getDb().run(`
            UPDATE posts SET
                title = ?, content = ?, excerpt = ?, slug = ?, status = ?, type = ?,
                author = ?, featured_media = ?, categories = ?, tags = ?, meta = ?,
                modified = ?, modified_gmt = ?
            WHERE id = ?
        `, [
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
        ]);
        saveDatabase();
        return this.getById(id);
    }

    delete(id) {
        getDb().run('DELETE FROM posts WHERE id = ?', [parseInt(id)]);
        saveDatabase();
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
    getAll() {
        const stmt = getDb().prepare('SELECT * FROM media ORDER BY date DESC');
        const rows = [];
        while (stmt.step()) rows.push(stmt.getAsObject());
        stmt.free();
        return rows;
    }

    getById(id) {
        const stmt = getDb().prepare('SELECT * FROM media WHERE id = ?');
        stmt.bind([parseInt(id)]);
        if (stmt.step()) {
            const row = stmt.getAsObject();
            stmt.free();
            return row;
        }
        stmt.free();
        return null;
    }

    create(data) {
        const now = new Date().toISOString();
        getDb().run(`
            INSERT INTO media (title, slug, source_url, file, mime_type, alt_text, caption, description, author, date, date_gmt, modified, modified_gmt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [
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
        ]);
        saveDatabase();
        const result = getDb().exec('SELECT last_insert_rowid() as id');
        const id = result[0].values[0][0];
        return this.getById(id);
    }

    update(id, data) {
        const existing = this.getById(id);
        if (!existing) return null;

        const now = new Date().toISOString();
        getDb().run(`
            UPDATE media SET alt_text = ?, title = ?, caption = ?, description = ?, modified = ?, modified_gmt = ?
            WHERE id = ?
        `, [
            data.alt_text ?? existing.alt_text,
            data.title ?? existing.title,
            data.caption ?? existing.caption,
            data.description ?? existing.description,
            now, now, parseInt(id)
        ]);
        saveDatabase();
        return this.getById(id);
    }

    delete(id) {
        getDb().run('DELETE FROM media WHERE id = ?', [parseInt(id)]);
        saveDatabase();
        return true;
    }
}

class CategoriesRepository {
    getAll() {
        const stmt = getDb().prepare('SELECT * FROM categories ORDER BY name');
        const rows = [];
        while (stmt.step()) rows.push(stmt.getAsObject());
        stmt.free();
        return rows;
    }

    getById(id) {
        const stmt = getDb().prepare('SELECT * FROM categories WHERE id = ?');
        stmt.bind([parseInt(id)]);
        if (stmt.step()) {
            const row = stmt.getAsObject();
            stmt.free();
            return row;
        }
        stmt.free();
        return null;
    }

    create(data) {
        getDb().run('INSERT INTO categories (name, slug, description, parent, count) VALUES (?, ?, ?, ?, ?)', [
            data.name, data.slug, data.description || '', data.parent || 0, 0
        ]);
        saveDatabase();
        const result = getDb().exec('SELECT last_insert_rowid() as id');
        const id = result[0].values[0][0];
        return this.getById(id);
    }
}

class TagsRepository {
    getAll() {
        const stmt = getDb().prepare('SELECT * FROM tags ORDER BY name');
        const rows = [];
        while (stmt.step()) rows.push(stmt.getAsObject());
        stmt.free();
        return rows;
    }

    getById(id) {
        const stmt = getDb().prepare('SELECT * FROM tags WHERE id = ?');
        stmt.bind([parseInt(id)]);
        if (stmt.step()) {
            const row = stmt.getAsObject();
            stmt.free();
            return row;
        }
        stmt.free();
        return null;
    }

    create(data) {
        getDb().run('INSERT INTO tags (name, slug, description, count) VALUES (?, ?, ?, ?)', [
            data.name, data.slug, data.description || '', 0
        ]);
        saveDatabase();
        const result = getDb().exec('SELECT last_insert_rowid() as id');
        const id = result[0].values[0][0];
        return this.getById(id);
    }
}

// Export
module.exports = {
    initDatabase,
    getDb,
    saveDatabase,
    posts: new PostsRepository(),
    media: new MediaRepository(),
    categories: new CategoriesRepository(),
    tags: new TagsRepository()
};
