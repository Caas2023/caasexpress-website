require('dotenv').config();
const { initDatabase, getDb } = require('./src/models/database');

async function clean() {
    console.log('🧹 Cleaning fake data...');
    try {
        await initDatabase();
        const db = getDb();

        // Delete everything to be sure
        await db.execute('DELETE FROM posts');
        await db.execute('DELETE FROM media');
        await db.execute('DELETE FROM categories');
        await db.execute('DELETE FROM tags');

        // Re-seed default categories
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

        console.log('✨ Database clean and ready for real import.');
    } catch (e) {
        console.error('Error cleaning:', e);
    }
}

clean();
