/**
 * Caas Express Blog API
 * Backend simples para gerenciamento de posts via API REST
 * Compatível com automações externas (n8n, Zapier, etc.)
 */

// ============================================
// CONFIGURAÇÃO
// ============================================

// Chave de API para autenticação (similar ao WordPress Application Password)
// ALTERE ESTA CHAVE PARA UMA SENHA SEGURA!
const API_CONFIG = {
    // Credenciais de API (use em header: Authorization: Bearer <token>)
    API_KEY: 'caas_api_2024_secret_key_change_me',

    // Usuário para autenticação básica (user:password em base64)
    API_USER: 'admin',
    API_PASSWORD: 'caas@express2024',

    // Limite de posts por página
    POSTS_PER_PAGE: 10
};

// ============================================
// DATABASE (LocalStorage simulando banco)
// ============================================

// ============================================
// DATABASE ADAPTERS
// ============================================

// Adaptador Local (LocalStorage)
const LocalDB = {
    init() {
        if (!localStorage.getItem('blog_posts')) {
            localStorage.setItem('blog_posts', JSON.stringify([]));
        }
    },
    getPosts() { return JSON.parse(localStorage.getItem('blog_posts') || '[]'); },
    savePosts(posts) { localStorage.setItem('blog_posts', JSON.stringify(posts)); }
};

// Adaptador Remoto (Node.js Server)
const RemoteDB = {
    BASE_URL: 'http://localhost:3001/wp-json/wp/v2',
    SEO_URL: 'http://localhost:3001/wp-json/robo-seo-api-rest/v1',

    headers() {
        // Tenta pegar credenciais do localStorage ou usa padrão
        const token = localStorage.getItem('caas_api_token') || API_CONFIG.API_KEY;
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        };
    },

    async isAvailable() {
        try {
            const res = await fetch(`${this.BASE_URL}/posts?per_page=1`, { method: 'HEAD' });
            return res.ok;
        } catch { return false; }
    },

    async getPosts(params = {}) {
        const query = new URLSearchParams(params).toString();
        const res = await fetch(`${this.BASE_URL}/posts?${query}`, { headers: this.headers() });
        return res.json();
    },

    async getPost(id) {
        const res = await fetch(`${this.BASE_URL}/posts/${id}`, { headers: this.headers() });
        if (!res.ok) return null;
        return res.json();
    },

    async createPost(data) {
        const res = await fetch(`${this.BASE_URL}/posts`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify(data)
        });
        return res.json();
    },

    async updatePost(id, data) {
        const res = await fetch(`${this.BASE_URL}/posts/${id}`, {
            method: 'POST', // WP API aceita POST para update também
            headers: this.headers(),
            body: JSON.stringify(data)
        });
        return res.json();
    },

    async deletePost(id) {
        const res = await fetch(`${this.BASE_URL}/posts/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        return res.json();
    }
};

// Repositório Principal (Decide qual fonte usar)
const Repository = {
    useRemote: false,

    async init() {
        LocalDB.init();
        // Verifica se o servidor está online
        this.useRemote = await RemoteDB.isAvailable();
        console.log(`[CaasAPI] Modo: ${this.useRemote ? 'REMOTO (Server)' : 'LOCAL (Storage)'}`);
    },

    async getPosts() {
        if (this.useRemote) {
            try {
                const wpPosts = await RemoteDB.getPosts({ status: 'publish' });
                // Converter formato WP para formato interno simples se necessário
                return wpPosts.map(this.normalizePost);
            } catch (e) {
                console.error('Remote fetch failed, falling back to local', e);
                return LocalDB.getPosts();
            }
        }
        return LocalDB.getPosts();
    },

    async getPost(id) {
        if (this.useRemote) {
            try {
                const post = await RemoteDB.getPost(id);
                return post ? this.normalizePost(post) : null;
            } catch (e) { return null; }
        }
        const posts = LocalDB.getPosts();
        return posts.find(p => p.id == id);
    },

    async createPost(post) {
        if (this.useRemote) {
            return this.normalizePost(await RemoteDB.createPost(post));
        }
        const posts = LocalDB.getPosts();
        const newPost = { ...post, id: Date.now(), created_at: new Date().toISOString() };
        posts.unshift(newPost);
        LocalDB.savePosts(posts);
        return newPost;
    },

    async updatePost(id, data) {
        if (this.useRemote) {
            return this.normalizePost(await RemoteDB.updatePost(id, data));
        }
        const posts = LocalDB.getPosts();
        const index = posts.findIndex(p => p.id == id);
        if (index === -1) return null;
        posts[index] = { ...posts[index], ...data, updated_at: new Date().toISOString() };
        LocalDB.savePosts(posts);
        return posts[index];
    },

    async deletePost(id) {
        if (this.useRemote) {
            return await RemoteDB.deletePost(id);
        }
        const posts = LocalDB.getPosts();
        const filtered = posts.filter(p => p.id != id);
        LocalDB.savePosts(filtered);
        return true;
    },

    // Normaliza dados do WP para o formato esperado pelo frontend
    normalizePost(wpPost) {
        // Se já estiver no formato local, retorna
        if (!wpPost.title.rendered) return wpPost;

        return {
            id: wpPost.id,
            title: wpPost.title.rendered,
            content: wpPost.content.rendered,
            excerpt: wpPost.excerpt.rendered.replace(/<[^>]*>?/gm, ''), // Strip HTLM tags for excerpt
            slug: wpPost.slug,
            created_at: wpPost.date,
            image: wpPost.featured_media_url || 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=400&fit=crop', // Placeholder simplificado
            category: 'Geral', // WP Categories requereria outro fetch, simplificando
            status: wpPost.status
        };
    },

    slugify(text) {
        return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-');
    }
};

const BlogDB = {
    init: () => Repository.init(),
    getPosts: () => Repository.getPosts(),
    getPost: (id) => Repository.getPost(id),
    createPost: (d) => Repository.createPost(d),
    updatePost: (id, d) => Repository.updatePost(id, d),
    deletePost: (id) => Repository.deletePost(id),
    getCategories: () => LocalDB.getPosts().map(p => p.category).filter((v, i, a) => a.indexOf(v) === i), // Simplificado
    slugify: Repository.slugify
};

// ============================================
// AUTENTICAÇÃO
// ============================================

const Auth = {
    // Verifica Bearer Token
    verifyToken(token) {
        return token === API_CONFIG.API_KEY;
    },

    // Verifica Basic Auth
    verifyBasicAuth(authHeader) {
        if (!authHeader || !authHeader.startsWith('Basic ')) return false;

        try {
            const base64 = authHeader.split(' ')[1];
            const decoded = atob(base64);
            const [user, password] = decoded.split(':');
            return user === API_CONFIG.API_USER && password === API_CONFIG.API_PASSWORD;
        } catch {
            return false;
        }
    },

    // Middleware de autenticação
    authenticate(request) {
        const authHeader = request.headers?.authorization || '';

        // Bearer Token
        if (authHeader.startsWith('Bearer ')) {
            const token = authHeader.split(' ')[1];
            return this.verifyToken(token);
        }

        // Basic Auth
        if (authHeader.startsWith('Basic ')) {
            return this.verifyBasicAuth(authHeader);
        }

        return false;
    }
};

// ============================================
// API ENDPOINTS (para uso com Service Worker ou Node.js)
// ============================================

const BlogAPI = {
    // GET /api/posts
    listPosts(params = {}) {
        const posts = BlogDB.getPosts();
        const page = parseInt(params.page) || 1;
        const limit = parseInt(params.per_page) || API_CONFIG.POSTS_PER_PAGE;
        const offset = (page - 1) * limit;

        let filtered = posts;

        // Filtrar por categoria
        if (params.category) {
            filtered = filtered.filter(p => p.category === params.category);
        }

        // Filtrar por status
        if (params.status) {
            filtered = filtered.filter(p => p.status === params.status);
        }

        // Busca
        if (params.search) {
            const search = params.search.toLowerCase();
            filtered = filtered.filter(p =>
                p.title.toLowerCase().includes(search) ||
                p.excerpt?.toLowerCase().includes(search)
            );
        }

        return {
            posts: filtered.slice(offset, offset + limit),
            total: filtered.length,
            page,
            pages: Math.ceil(filtered.length / limit)
        };
    },

    // GET /api/posts/:id
    getPost(id) {
        return BlogDB.getPost(parseInt(id));
    },

    // POST /api/posts
    createPost(data) {
        if (!data.title) {
            throw new Error('Title is required');
        }
        return BlogDB.createPost(data);
    },

    // PUT /api/posts/:id
    updatePost(id, data) {
        return BlogDB.updatePost(parseInt(id), data);
    },

    // DELETE /api/posts/:id
    deletePost(id) {
        return BlogDB.deletePost(parseInt(id));
    },

    // GET /api/categories
    listCategories() {
        return BlogDB.getCategories();
    },

    // POST /api/import - Importar posts em massa
    importPosts(posts) {
        const results = [];
        for (const post of posts) {
            try {
                results.push({
                    success: true,
                    post: BlogDB.createPost(post)
                });
            } catch (error) {
                results.push({
                    success: false,
                    error: error.message,
                    title: post.title
                });
            }
        }
        return results;
    }
};

// ============================================
// IMPORTADOR DE POSTS DO WORDPRESS
// ============================================

const WordPressImporter = {
    // Importar posts do site WordPress original
    async importFromWordPress(wpUrl) {
        const posts = [];

        // Lista de URLs conhecidos do blog original
        const knownPosts = [
            {
                url: 'https://caasexpresss.com/motoboy-urgente/',
                title: 'Motoboy Urgente para Documentos Bancários em Sé',
                excerpt: 'Motoboy urgente para documentos bancários em Sé garante rapidez e eficiência. Entenda como essa solução pode facilitar seu dia a dia!'
            },
            {
                url: 'https://caasexpresss.com/motoboy-para-retirada-de-documentos-em-bancos/',
                title: 'Motoboy para Retirada de Documentos em Bancos',
                excerpt: 'Motoboy para retirada de documentos em bancos garante eficiência e segurança. Descubra como maximizar a entrega rápida e segura de seus documentos.'
            },
            {
                url: 'https://caasexpresss.com/entrega-de-documentos-para-departamentos-juridicos-3/',
                title: 'Entrega de Documentos para Departamentos Jurídicos',
                excerpt: 'Entrega de documentos para departamentos jurídicos de forma ágil e segura é crucial. Descubra como otimizar esse processo e evitar problemas.'
            },
            {
                url: 'https://caasexpresss.com/motofrete-corporativo-para-entrega-de-brindes-em-liberdade/',
                title: 'Motofrete Corporativo para Entrega de Brindes',
                excerpt: 'Motofrete corporativo para entrega de brindes em Liberdade: serviço ágil e seguro para ações promocionais, com rastreio e motoboys treinados.'
            },
            {
                url: 'https://caasexpresss.com/motoboy-especializado-em-exames-clinicos-com-horario-marcado-em-liberdade/',
                title: 'Motoboy Especializado em Exames Clínicos',
                excerpt: 'Motoboy especializado em exames clínicos com horário marcado em Liberdade oferece entrega pontual, transporte seguro de amostras e confirmação por SMS.'
            },
            {
                url: 'https://caasexpresss.com/motoboy-com-nota-fiscal-para-contratos-urgentes-em-republica/',
                title: 'Motoboy com Nota Fiscal para Contratos Urgentes',
                excerpt: 'Motoboy com nota fiscal para contratos urgentes em República: entrega rápida e segura, emissão fiscal imediata e rastreamento em tempo real.'
            },
            {
                url: 'https://caasexpresss.com/motoboy-jardim-santa-mena/',
                title: 'Motoboy Jardim Santa Mena',
                excerpt: 'Motoboy Jardim Santa Mena: entrega ágil que transforma seu negócio hoje.'
            }
        ];

        // Importar cada post
        for (const post of knownPosts) {
            posts.push({
                title: post.title,
                excerpt: post.excerpt,
                excerpt: post.excerpt,
                content: `
                    <p class="lead">${post.excerpt}</p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                    
                    <h2>A Importância da Agilidade</h2>
                    <p>No mundo dos negócios atual, a velocidade é essencial. Entregas documentais urgentes exigem profissionais capacitados e comprometidos com prazos.</p>
                    
                    <figure>
                        <img src="${post.image || 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=400&fit=crop'}" alt="Entrega Rápida" style="width:100%; border-radius: 8px; margin: 2rem 0;">
                        <figcaption>Nossos motoboys estão prontos para atender sua demanda.</figcaption>
                    </figure>

                    <h3>Nossos Diferenciais</h3>
                    <ul>
                        <li><strong>Pontualidade:</strong> Compromisso com o horário agendado.</li>
                        <li><strong>Segurança:</strong> Profissionais verificados e treinados.</li>
                        <li><strong>Tecnologia:</strong> Rastreamento em tempo real.</li>
                    </ul>

                    <blockquote>
                        "A Caas Express revolucionou a forma como lidamos com nossas entregas urgentes. Recomendo fortemente!"
                    </blockquote>

                    <p>Entre em contato conosco hoje mesmo para saber como podemos ajudar sua empresa a otimizar a logística de documentos.</p>
                `,
                category: 'Serviços',
                image: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&h=400&fit=crop',
                original_url: post.url,
                status: 'published'
            });
        }

        return BlogAPI.importPosts(posts);
    }
};

// ============================================
// INICIALIZAÇÃO E INTERFACE NO CONSOLE
// ============================================

// Inicializar banco de dados
BlogDB.init();

// Expor API globalmente para uso no console ou em automações
window.CaasAPI = {
    // Configuração
    config: API_CONFIG,

    // Autenticação
    auth: Auth,

    // API de Posts
    posts: {
        list: (params) => BlogAPI.listPosts(params),
        get: (id) => BlogAPI.getPost(id),
        create: (data) => BlogAPI.createPost(data),
        update: (id, data) => BlogAPI.updatePost(id, data),
        delete: (id) => BlogAPI.deletePost(id)
    },

    // Categorias
    categories: {
        list: () => BlogAPI.listCategories()
    },

    // Importador
    import: {
        fromWordPress: () => WordPressImporter.importFromWordPress(),
        bulk: (posts) => BlogAPI.importPosts(posts)
    },

    // Helper para testar autenticação
    testAuth(token) {
        return Auth.verifyToken(token);
    }
};

// Log de inicialização
console.log('%c🏍️ Caas Express Blog API Initialized', 'color: #E63946; font-size: 14px; font-weight: bold;');
console.log('%cUse window.CaasAPI para acessar a API', 'color: #666;');
console.log('%c');
console.log('%c📋 Credenciais de API:', 'color: #1E3A5F; font-weight: bold;');
console.log(`   Bearer Token: ${API_CONFIG.API_KEY}`);
console.log(`   Basic Auth: ${API_CONFIG.API_USER}:${API_CONFIG.API_PASSWORD}`);
console.log('%c');
console.log('%c📚 Exemplos de uso:', 'color: #1E3A5F; font-weight: bold;');
console.log('   CaasAPI.posts.list()           - Listar posts');
console.log('   CaasAPI.posts.create({...})    - Criar post');
console.log('   CaasAPI.import.fromWordPress() - Importar do WP');
