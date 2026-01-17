require('dotenv').config();
const { initDatabase, getDb } = require('./src/models/database');

async function seedFull() {
    console.log('🌱 Adding 10 REAL posts to simulate production copy...');

    try {
        await initDatabase();
        const db = getDb();
        const now = new Date().toISOString();

        // Títulos exatos da captura de tela do usuário (Turso)
        const posts = [
            { title: "Motoboy para escritórios: Agilidade e Confiança", slug: "motoboy-para-escritorios" },
            { title: "Motoboy no Pimentas Guarulhos: Rapidez e Eficiência", slug: "motoboy-no-pimentas-guarulhos" },
            { title: "Entrega segura de documentos: Por que contratar um motoboy?", slug: "entrega-segura-de-documentos" },
            { title: "Como reduzir custos com entregas utilizando motoboys", slug: "como-reduzir-custos-com-entregas" },
            { title: "Motoboy em São Paulo: como escolher o melhor serviço", slug: "motoboy-em-sao-paulo" },
            { title: "Motoboy no Jardim Santa Helena: Atendimento Rápido", slug: "motoboy-no-jardim-santa-helena" },
            { title: "Logística para Turismo: Transporte de Malas e Documentos", slug: "logistica-para-turismo" },
            { title: "Terceirização de Motoboy: Vantagens para Empresas", slug: "terceirizacao-de-motoboy" },
            { title: "Logística de Documentos: A importância da agilidade", slug: "logistica-de-documentos" },
            { title: "Motoboy em Guarulhos: Entregas Rápidas e Seguras", slug: "motoboy-em-guarulhos" }
        ];

        // Limpar posts antigos de teste para evitar duplicatas "feias"
        await db.execute({ sql: "DELETE FROM posts WHERE slug LIKE 'teste-%'" });

        let inserted = 0;
        for (const p of posts) {
            // Verificar se já existe
            const exists = await db.execute({ sql: "SELECT id FROM posts WHERE slug = ?", args: [p.slug] });
            if (exists.rows.length > 0) {
                console.log(`   ⚠️  Já existe: ${p.title}`);
                continue;
            }

            await db.execute({
                sql: `INSERT INTO posts (title, content, excerpt, slug, status, type, author, date, date_gmt, modified, modified_gmt)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                args: [
                    p.title,
                    `
                    <p>Este é um conteúdo simulado para o post <strong>${p.title}</strong>.</p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                    <h3>Por que contratar a Caas Express?</h3>
                    <p>Garantimos agilidade, segurança e profissionalismo em cada entrega.</p>
                    `,
                    `Resumo sobre ${p.title}...`,
                    p.slug,
                    'publish',
                    'post',
                    1,
                    now, now, now, now
                ]
            });
            console.log(`   ✅ Inserido: ${p.title}`);
            inserted++;
        }

        console.log(`\n🎉 Operação Concluída. ${inserted} posts novos inseridos.`);
        console.log('👉 Verifique agora em: http://localhost:3001/blog.html');

    } catch (e) {
        console.error('❌ Erro:', e);
    }
}

seedFull();
