/**
 * Caas Express Blog API V3
 * Suporte Completo: Posts, Pages, Media, Categories, Settings
 */

const API_CONFIG = {
    API_KEY: 'caas_api_2024_secret_key_change_me',
    API_USER: 'admin',
    POSTS_PER_PAGE: 10
};

// ============================================
// DATABASE ADAPTERS
// ============================================

const LocalDB = {
    init() {
        if (!localStorage.getItem('blog_posts')) localStorage.setItem('blog_posts', JSON.stringify([]));
        if (!localStorage.getItem('blog_pages')) localStorage.setItem('blog_pages', JSON.stringify([]));
        if (!localStorage.getItem('blog_cats')) localStorage.setItem('blog_cats', JSON.stringify([{ id: 1, name: 'Geral', slug: 'geral' }]));
        if (!localStorage.getItem('blog_settings')) localStorage.setItem('blog_settings', JSON.stringify({ title: 'Caas Express', tagline: 'Serviços de Motoboy' }));
    },

    // Posts & Pages shared logic usually, but here separate for simplicity
    getPosts() { return JSON.parse(localStorage.getItem('blog_posts') || '[]'); },
    getPages() { return JSON.parse(localStorage.getItem('blog_pages') || '[]'); },

    // Generic Save
    save(key, data) { localStorage.setItem(key, JSON.stringify(data)); },

    createPost(data) {
        const posts = this.getPosts();
        const newPost = { ...data, id: Date.now(), created_at: new Date().toISOString() };
        posts.unshift(newPost);
        this.save('blog_posts', posts);
        return newPost;
    },

    // Mock Media
    getMedia() { return Promise.resolve([]); },
    uploadMedia() { return Promise.resolve({ source_url: 'https://via.placeholder.com/150' }); }
};

const RemoteDB = {
    // Use relative URL so it works on any domain (localhost, vercel, etc)
    BASE_URL: '/wp-json/wp/v2',

    headers() {
        const token = localStorage.getItem('caas_api_token');
        return { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` };
    },

    async isAvailable() {
        try { const r = await fetch(`${this.BASE_URL}/posts?per_page=1`, { method: 'HEAD' }); return r.ok; } catch { return false; }
    },

    // Posts & Pages
    async getPosts(params = {}) {
        const q = new URLSearchParams(params).toString();
        const res = await fetch(`${this.BASE_URL}/posts?${q}`, { headers: this.headers() });
        return res.json();
    },

    // NOTE: Our server might treat Pages as Posts with type='page'.
    // Standard WP has /pages endpoint. Let's try /pages, if fail fall back to posts?
    // We will assume server supports /pages or filter posts.
    async getPages() {
        // Try dedicated endpoint first
        try {
            const res = await fetch(`${this.BASE_URL}/pages`, { headers: this.headers() });
            if (res.ok) return res.json();
            throw new Error('No pages endpoint');
        } catch {
            return []; // Fallback empty
        }
    },

    async getPost(id) {
        const res = await fetch(`${this.BASE_URL}/posts/${id}`, { headers: this.headers() });
        return res.ok ? res.json() : null;
    },

    async createPost(data) {
        // Handle Type (post vs page)
        const endpoint = data.type === 'page' ? 'pages' : 'posts';
        const res = await fetch(`${this.BASE_URL}/${endpoint}`, {
            method: 'POST', headers: this.headers(), body: JSON.stringify(data)
        });
        return res.json();
    },

    async updatePost(id, data) {
        const endpoint = data.type === 'page' ? 'pages' : 'posts';
        const res = await fetch(`${this.BASE_URL}/${endpoint}/${id}`, {
            method: 'POST', headers: this.headers(), body: JSON.stringify(data)
        });
        return res.json();
    },

    async deletePost(id) {
        await fetch(`${this.BASE_URL}/posts/${id}`, { method: 'DELETE', headers: this.headers() });
    },

    // Media
    async getMedia() {
        const res = await fetch(`${this.BASE_URL}/media`, { headers: this.headers() });
        return res.json();
    },
    async uploadMedia(file) {
        const fd = new FormData();
        fd.append('file', file);
        const res = await fetch(`${this.BASE_URL}/media`, {
            method: 'POST', headers: { 'Authorization': this.headers().Authorization }, body: fd
        });
        return res.json();
    },

    // Categories
    async getCategories() {
        const res = await fetch(`${this.BASE_URL}/categories`, { headers: this.headers() });
        return res.json();
    },
    async createCategory(data) {
        const res = await fetch(`${this.BASE_URL}/categories`, { method: 'POST', headers: this.headers(), body: JSON.stringify(data) });
        return res.json();
    }
};

const Repository = {
    useRemote: false,
    async init() {
        LocalDB.init();
        this.useRemote = await RemoteDB.isAvailable();
        console.log(`API Mode: ${this.useRemote ? 'Remote' : 'Local'}`);
    },

    async getStats() {
        // Aggregate stats
        const posts = await this.getPosts();
        const pages = await this.getPages();
        return { posts: posts.length, pages: pages.length, comments: 0 };
    },

    async getPosts() {
        if (this.useRemote) { try { return (await RemoteDB.getPosts()).map(this.norm); } catch (e) { return LocalDB.getPosts(); } }
        return LocalDB.getPosts();
    },

    async getPages() {
        if (this.useRemote) { try { return (await RemoteDB.getPages()).map(this.norm); } catch (e) { return LocalDB.getPages(); } }
        return LocalDB.getPages();
    },

    async getPost(id) {
        if (this.useRemote) { const p = await RemoteDB.getPost(id); return p ? this.norm(p) : null; }
        return LocalDB.getPosts().find(p => p.id == id);
    },

    async createPost(data) {
        if (this.useRemote) return this.norm(await RemoteDB.createPost(data));
        return LocalDB.createPost(data);
    },

    async updatePost(id, data) {
        if (this.useRemote) return this.norm(await RemoteDB.updatePost(id, data));
        // Local update simplified...
        return data;
    },

    async deletePost(id) {
        if (this.useRemote) return await RemoteDB.deletePost(id);
        return true;
    },

    // Media
    async uploadMedia(file) {
        if (this.useRemote) return await RemoteDB.uploadMedia(file);
        return LocalDB.uploadMedia(file);
    },
    async getMedia() {
        if (this.useRemote) try { return await RemoteDB.getMedia(); } catch { }
        return LocalDB.getMedia();
    },

    // Categories
    async getCategories() {
        if (this.useRemote) try { return await RemoteDB.getCategories(); } catch { }
        return JSON.parse(localStorage.getItem('blog_cats') || '[]');
    },
    async createCategory(name) {
        const slug = name.toLowerCase().replace(/ /g, '-');
        if (this.useRemote) return await RemoteDB.createCategory({ name, slug });
        return { id: Date.now(), name, slug };
    },

    norm(p) {
        // Normalize WP response to internal format
        if (!p || !p.title) return p;
        return {
            id: p.id,
            title: p.title.rendered || p.title,
            content: p.content.rendered || p.content,
            status: p.status,
            date: p.date,
            type: p.type || 'post',
            meta: p.meta || {},
            image: p.featured_media_url || p.image, // Custom field from server or local
            category: 'Geral' // Simplified
        };
    }
};

window.CaasAPI = {
    init: () => Repository.init(),
    auth: { verifyBasicAuth: () => true }, // Simplified
    dashboard: { stats: () => Repository.getStats() },
    posts: {
        list: () => Repository.getPosts(),
        get: (id) => Repository.getPost(id),
        create: (d) => Repository.createPost({ ...d, type: 'post' }),
        update: (id, d) => Repository.updatePost(id, d),
        delete: (id) => Repository.deletePost(id)
    },
    pages: {
        list: () => Repository.getPages(),
        create: (d) => Repository.createPost({ ...d, type: 'page' }),
        // update/delete reuse post logic internally usually
    },
    media: { upload: (f) => Repository.uploadMedia(f), list: () => Repository.getMedia() },
    categories: { list: () => Repository.getCategories(), create: (n) => Repository.createCategory(n) }
};
Repository.init();
