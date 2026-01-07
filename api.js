/**
 * Caas Express Blog API
 * Backend simples para gerenciamento de posts via API REST
 */

// ============================================
// CONFIGURAÇÃO
// ============================================

const API_CONFIG = {
    API_KEY: 'caas_api_2024_secret_key_change_me',
    API_USER: 'admin',
    API_PASSWORD: 'caas@express2024',
    POSTS_PER_PAGE: 10
};

// ============================================
// DATABASE ADAPTERS
// ============================================

// Adaptador Local (LocalStorage)
const LocalDB = {
    init() {
        if (!localStorage.getItem('blog_posts')) {
            localStorage.setItem('blog_posts', JSON.stringify([]));
        }
        if (!localStorage.getItem('blog_categories')) {
            localStorage.setItem('blog_categories', JSON.stringify([
                { id: 1, name: 'Dicas', slug: 'dicas' },
                { id: 2, name: 'Logística', slug: 'logistica' },
                { id: 3, name: 'Serviços', slug: 'servicos' },
                { id: 4, name: 'Negócios', slug: 'negocios' }
            ]));
        }
    },
    getPosts() { return JSON.parse(localStorage.getItem('blog_posts') || '[]'); },
    savePosts(posts) { localStorage.setItem('blog_posts', JSON.stringify(posts)); },

    getCategories() { return JSON.parse(localStorage.getItem('blog_categories') || '[]'); },
    saveCategories(cats) { localStorage.setItem('blog_categories', JSON.stringify(cats)); },

    createCategory(name) {
        const cats = this.getCategories();
        const newCat = { id: Date.now(), name, slug: Repository.slugify(name) };
        cats.push(newCat);
        this.saveCategories(cats);
        return newCat;
    },

    deleteCategory(id) {
        let cats = this.getCategories();
        cats = cats.filter(c => c.id != id);
        this.saveCategories(cats);
        return true;
    },

    // Media Mock for Local
    uploadMedia(file) {
        // Em local, não podemos subir arquivo. Retorna placeholder ou base64 simples?
        // Retornaremos um placeholder aleatório para simular
        return Promise.resolve({
            id: Date.now(),
            source_url: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=600&fit=crop'
        });
    },

    getMedia() {
        return Promise.resolve([
            { id: 1, source_url: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64' },
            { id: 2, source_url: 'https://images.unsplash.com/photo-1616432043562-3671ea2e5242' }
        ]);
    }
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
            'Authorization': `Bearer ${token}` // Ou Basic, dependendo do que foi salvo
        };
    },

    authHeader() {
        const token = localStorage.getItem('caas_api_token') || API_CONFIG.API_KEY;
        return { 'Authorization': `Bearer ${token}` };
    },

    async isAvailable() {
        try {
            const res = await fetch(`${this.BASE_URL}/posts?per_page=1`, { method: 'HEAD' });
            return res.ok;
        } catch { return false; }
    },

    // Posts
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
            method: 'POST',
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
    },

    // Categories
    async getCategories() {
        const res = await fetch(`${this.BASE_URL}/categories`, { headers: this.headers() });
        return res.json();
    },

    async createCategory(data) {
        const res = await fetch(`${this.BASE_URL}/categories`, {
            method: 'POST',
            headers: this.headers(),
            body: JSON.stringify(data)
        });
        return res.json();
    },

    async deleteCategory(id) {
        // Server might not support delete yet, but let's try
        const res = await fetch(`${this.BASE_URL}/categories/${id}`, {
            method: 'DELETE',
            headers: this.headers()
        });
        return res.json();
    },

    // Media
    async uploadMedia(file) {
        const formData = new FormData();
        formData.append('file', file);

        const res = await fetch(`${this.BASE_URL}/media`, {
            method: 'POST',
            headers: this.authHeader(), // No Content-Type for FormData!
            body: formData
        });
        return res.json();
    },

    async getMedia() {
        const res = await fetch(`${this.BASE_URL}/media`, { headers: this.headers() });
        return res.json();
    }
};

// Repositório Principal
const Repository = {
    useRemote: false,

    async init() {
        LocalDB.init();
        this.useRemote = await RemoteDB.isAvailable();
        console.log(`[CaasAPI] Modo: ${this.useRemote ? 'REMOTO (Server)' : 'LOCAL (Storage)'}`);
    },

    // Posts
    async getPosts() {
        if (this.useRemote) {
            try {
                const wpPosts = await RemoteDB.getPosts({ status: 'publish' });
                return wpPosts.map(this.normalizePost);
            } catch (e) { return LocalDB.getPosts(); }
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
        if (this.useRemote) return this.normalizePost(await RemoteDB.createPost(post));

        const posts = LocalDB.getPosts();
        const newPost = { ...post, id: Date.now(), created_at: new Date().toISOString() };
        posts.unshift(newPost);
        LocalDB.savePosts(posts);
        return newPost;
    },

    async updatePost(id, data) {
        if (this.useRemote) return this.normalizePost(await RemoteDB.updatePost(id, data));

        const posts = LocalDB.getPosts();
        const index = posts.findIndex(p => p.id == id);
        if (index === -1) return null;
        posts[index] = { ...posts[index], ...data, updated_at: new Date().toISOString() };
        LocalDB.savePosts(posts);
        return posts[index];
    },

    async deletePost(id) {
        if (this.useRemote) return await RemoteDB.deletePost(id);

        const posts = LocalDB.getPosts();
        const filtered = posts.filter(p => p.id != id);
        LocalDB.savePosts(filtered);
        return true;
    },

    // Categories
    async getCategories() {
        if (this.useRemote) {
            try { return await RemoteDB.getCategories(); }
            catch { return LocalDB.getCategories(); }
        }
        return LocalDB.getCategories();
    },

    async createCategory(name) {
        const slug = this.slugify(name);
        if (this.useRemote) return await RemoteDB.createCategory({ name, slug });
        return LocalDB.createCategory(name);
    },

    async deleteCategory(id) {
        if (this.useRemote) return await RemoteDB.deleteCategory(id);
        return LocalDB.deleteCategory(id);
    },

    // Media
    async uploadMedia(file) {
        if (this.useRemote) return await RemoteDB.uploadMedia(file);
        return LocalDB.uploadMedia(file);
    },

    async getMedia() {
        if (this.useRemote) {
            try { return await RemoteDB.getMedia(); }
            catch { return LocalDB.getMedia(); }
        }
        return LocalDB.getMedia();
    },

    normalizePost(wpPost) {
        if (!wpPost.title.rendered) return wpPost;
        return {
            id: wpPost.id,
            title: wpPost.title.rendered,
            content: wpPost.content.rendered,
            excerpt: wpPost.excerpt.rendered.replace(/<[^>]*>?/gm, ''),
            slug: wpPost.slug,
            created_at: wpPost.date,
            image: wpPost.featured_media_url || 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=400&fit=crop',
            category: 'Geral',
            status: wpPost.status,
            meta: wpPost.meta || {}
        };
    },

    slugify(text) {
        return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-');
    }
};

// ============================================
// API PUBLIC INTERFACE
// ============================================

const BlogAPI = {
    // GET /api/posts
    listPosts(params = {}) {
        // Helper wrapper compatible with old calls
        return Repository.getPosts().then(posts => ({ posts, total: posts.length }));
    },
    // ... aliases ...
};

// ============================================
// IMPORTADOR (Legacy Mock)
// ============================================
const WordPressImporter = {
    async importFromWordPress() {
        // ... (Mesmo código de antes, simplificado)
        console.log('Importador chamado');
        return [];
    }
};

// Inicialização
Repository.init();

// Expor API
window.CaasAPI = {
    auth: {
        verifyBasicAuth: (h) => h.startsWith('Basic '), // Simples check
    },
    posts: {
        list: (p) => Repository.getPosts().then(posts => ({ posts })),
        get: (id) => Repository.getPost(id),
        create: (d) => Repository.createPost(d),
        update: (id, d) => Repository.updatePost(id, d),
        delete: (id) => Repository.deletePost(id)
    },
    categories: {
        list: () => Repository.getCategories(),
        create: (n) => Repository.createCategory(n),
        delete: (id) => Repository.deleteCategory(id)
    },
    media: {
        upload: (f) => Repository.uploadMedia(f),
        list: () => Repository.getMedia()
    },
    import: {
        fromWordPress: () => WordPressImporter.importFromWordPress()
    }
};

console.log('Caas API V2 Loaded');
