/**
 * WordPress Import Script
 * Importa posts do WordPress para o banco Turso
 * 
 * Uso: node import-wordpress.js
 */

require('dotenv').config();
const { createClient } = require('@libsql/client');

// Configuração
const WP_URL = 'https://caasexpress.com';
const TURSO_URL = process.env.TURSO_DATABASE_URL;
const TURSO_TOKEN = process.env.TURSO_AUTH_TOKEN;

if (!TURSO_URL || !TURSO_TOKEN) {
    console.error('❌ Configure TURSO_DATABASE_URL e TURSO_AUTH_TOKEN no .env');
    process.exit(1);
}

const db = createClient({ url: TURSO_URL, authToken: TURSO_TOKEN });

async function fetchWordPressPosts() {
    console.log(`📡 Buscando posts de ${WP_URL}...`);

    const allPosts = [];
    let page = 1;
    let hasMore = true;

    while (hasMore) {
        try {
            const response = await fetch(`${WP_URL}/wp-json/wp/v2/posts?per_page=100&page=${page}&_embed`);

            if (!response.ok) {
                if (response.status === 400) {
                    hasMore = false;
                    break;
                }
                throw new Error(`HTTP ${response.status}`);
            }

            const posts = await response.json();

            if (posts.length === 0) {
                hasMore = false;
            } else {
                allPosts.push(...posts);
                console.log(`   Página ${page}: ${posts.length} posts encontrados`);
                page++;
            }
        } catch (error) {
            console.error(`   Erro na página ${page}:`, error.message);
            hasMore = false;
        }
    }

    console.log(`✅ Total: ${allPosts.length} posts encontrados\n`);
    return allPosts;
}

async function importPosts(posts) {
    console.log('📥 Importando posts para Turso...\n');

    let imported = 0;
    let errors = 0;

    for (const post of posts) {
        try {
            // Extrair dados do post WordPress
            const title = post.title?.rendered || post.title || '';
            const content = post.content?.rendered || post.content || '';
            const excerpt = post.excerpt?.rendered || post.excerpt || '';
            const slug = post.slug || '';
            const status = post.status || 'publish';
            const date = post.date || new Date().toISOString();
            const modified = post.modified || date;

            // Extrair imagem destacada
            let featuredMediaId = 0;
            if (post._embedded && post._embedded['wp:featuredmedia']) {
                const media = post._embedded['wp:featuredmedia'][0];
                if (media) {
                    // Inserir mídia primeiro
                    const mediaResult = await db.execute({
                        sql: `INSERT OR IGNORE INTO media (title, slug, source_url, file, mime_type, date, date_gmt, modified, modified_gmt)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                        args: [
                            media.title?.rendered || '',
                            media.slug || '',
                            media.source_url || '',
                            media.source_url?.split('/').pop() || '',
                            media.mime_type || 'image/jpeg',
                            media.date || date,
                            media.date || date,
                            media.modified || date,
                            media.modified || date
                        ]
                    });
                    featuredMediaId = Number(mediaResult.lastInsertRowid) || 0;
                }
            }

            // Extrair categorias
            let categories = [1];
            if (post._embedded && post._embedded['wp:term']) {
                const cats = post._embedded['wp:term'].flat().filter(t => t.taxonomy === 'category');
                if (cats.length > 0) {
                    categories = cats.map(c => c.id);
                }
            }

            // Extrair tags
            let tags = [];
            if (post._embedded && post._embedded['wp:term']) {
                const tagTerms = post._embedded['wp:term'].flat().filter(t => t.taxonomy === 'post_tag');
                tags = tagTerms.map(t => t.id);
            }

            // Inserir post
            await db.execute({
                sql: `INSERT INTO posts (title, content, excerpt, slug, status, type, author, featured_media, categories, tags, meta, date, date_gmt, modified, modified_gmt)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                args: [
                    title,
                    content,
                    excerpt,
                    slug,
                    status,
                    'post',
                    post.author || 1,
                    featuredMediaId,
                    JSON.stringify(categories),
                    JSON.stringify(tags),
                    JSON.stringify({}),
                    date,
                    date,
                    modified,
                    modified
                ]
            });

            imported++;
            console.log(`   ✓ "${title.substring(0, 50)}..."`);

        } catch (error) {
            errors++;
            console.error(`   ✗ Erro: ${error.message}`);
        }
    }

    console.log(`\n📊 Resultado:`);
    console.log(`   ✅ Importados: ${imported}`);
    console.log(`   ❌ Erros: ${errors}`);
}

async function main() {
    console.log('═══════════════════════════════════════════════════');
    console.log('   WordPress → Turso Import Script');
    console.log('═══════════════════════════════════════════════════\n');

    try {
        // Testar conexão com Turso
        console.log('🔗 Conectando ao Turso...');
        await db.execute('SELECT 1');
        console.log('✅ Conexão OK\n');

        // Buscar posts do WordPress
        const posts = await fetchWordPressPosts();

        if (posts.length === 0) {
            console.log('⚠️ Nenhum post encontrado para importar.');
            return;
        }

        // Importar posts
        await importPosts(posts);

        console.log('\n🎉 Importação concluída!');

    } catch (error) {
        console.error('❌ Erro fatal:', error);
    }
}

main();
