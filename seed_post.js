require('dotenv').config();
const { initDatabase, getDb } = require('./src/models/database');

async function seed() {
    console.log('🌱 Seeding database with a test post...');

    try {
        await initDatabase();
        const db = getDb();

        const title = 'Teste de Infraestrutura: Post Verificado';
        const content = '<p>Se você está vendo este post, significa que o banco de dados local, a API backend e o frontend estão conectados corretamente. 🚀</p>';
        const date = new Date().toISOString();

        // Insert Post
        const result = await db.execute({
            sql: `INSERT INTO posts (title, content, excerpt, slug, status, type, author, date, date_gmt, modified, modified_gmt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            args: [
                title,
                content,
                'Resumo do teste de infraestrutura.',
                'teste-infraestrutura',
                'publish',
                'post',
                1,
                date, date, date, date
            ]
        });

        console.log(`✅ Post inserido com ID: ${result.lastInsertRowid}`);
        console.log('Agora verifique http://localhost:3001/blog.html');

    } catch (e) {
        console.error('❌ Erro ao semear:', e);
    }
}

seed();
