/**
 * Caas Express Blog API V3 (Unified Architecture)
 * Single Source of Truth: Uses AppConfig to talk to Real Backend (Remote or Local 3001)
 * Eliminates "Split Brain" problem by removing LocalStorage data fallback.
 */

const API_CONFIG = {
    // API_KEY is kept for future use if needed, but currently using Basic Auth or Token
    API_KEY: 'caas_api_2024_secret_key_change_me',
    API_USER: 'admin',
    POSTS_PER_PAGE: 10
};

const RemoteDB = {
    // Dynamically retrieve Base URL from global config
    get BASE_URL() {
        // AppConfig must be loaded before this file
        const base = (typeof AppConfig !== 'undefined') ? AppConfig.getApiBaseUrl() : '';
        return `${base}/wp-json/wp/v2`;
    },

    headers() {
        const token = localStorage.getItem('caas_api_token');
        return {
            'Content-Type': 'application/json',
            'Authorization': token ? `Basic ${token}` : ''
        };
    },

    async isAvailable() {
        try {
            const r = await fetch(`${this.BASE_URL}/posts?per_page=1`, { method: 'HEAD' });
            return r.ok;
        } catch {
            return false;
        }
    },

    // Posts & Pages
    async getPosts(params = {}) {
        const q = new URLSearchParams(params).toString();
        const res = await fetch(`${this.BASE_URL}/posts?${q}`, { headers: this.headers() });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    },

    async getPages() {
        try {
            const res = await fetch(`${this.BASE_URL}/pages`, { headers: this.headers() });
            if (res.ok) return res.json();
            throw new Error('No pages endpoint');
        } catch {
            return [];
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
        if (!res.ok) throw new Error(`Erro ao criar post: ${res.statusText}`);
        return res.json();
    },

    async updatePost(id, data) {
        const endpoint = data.type === 'page' ? 'pages' : 'posts';
        const res = await fetch(`${this.BASE_URL}/${endpoint}/${id}`, {
            method: 'POST', headers: this.headers(), body: JSON.stringify(data)
        });
        if (!res.ok) throw new Error(`Erro ao atualizar: ${res.statusText}`);
        return res.json();
    },

    async deletePost(id) {
        // Warning: This is a real delete
        const res = await fetch(`${this.BASE_URL}/posts/${id}`, { method: 'DELETE', headers: this.headers() });
        if (!res.ok) throw new Error(`Erro ao deletar: ${res.statusText}`);
    },

    // Media
    async getMedia() {
        const res = await fetch(`${this.BASE_URL}/media`, { headers: this.headers() });
        if (!res.ok) return [];
        return res.json();
    },

    async uploadMedia(file) {
        const fd = new FormData();
        fd.append('file', file);
        // Do not set Content-Type header manually for FormData, browser does it
        const headers = { 'Authorization': this.headers().Authorization };

        const res = await fetch(`${this.BASE_URL}/media`, {
            method: 'POST', headers: headers, body: fd
        });
        return res.json();
    },

    // Categories
    async getCategories() {
        const res = await fetch(`${this.BASE_URL}/categories`, { headers: this.headers() });
        return res.ok ? res.json() : [];
    },
    async createCategory(data) {
        const res = await fetch(`${this.BASE_URL}/categories`, { method: 'POST', headers: this.headers(), body: JSON.stringify(data) });
        return res.json();
    }
};

const Repository = {
    async init() {
        console.log('Using Unified API Config. Base URL:', RemoteDB.BASE_URL);
    },

    async getStats() {
        // Aggregate stats
        const posts = await this.getPosts();
        const pages = await this.getPages();
        return { posts: posts.length, pages: pages.length, comments: 0 };
    },

    async getPosts() {
        try {
            return (await RemoteDB.getPosts()).map(this.norm);
        } catch (e) {
            console.error('API Error:', e);
            // Return empty array to signal "No Data" instead of fake data
            return [];
        }
    },

    async getPages() {
        try {
            return (await RemoteDB.getPages()).map(this.norm);
        } catch (e) {
            return [];
        }
    },

    async getPost(id) {
        const p = await RemoteDB.getPost(id);
        return p ? this.norm(p) : null;
    },

    async createPost(data) {
        return this.norm(await RemoteDB.createPost(data));
    },

    async updatePost(id, data) {
        return this.norm(await RemoteDB.updatePost(id, data));
    },

    async deletePost(id) {
        return await RemoteDB.deletePost(id);
    },

    // Media
    async uploadMedia(file) {
        return await RemoteDB.uploadMedia(file);
    },
    async getMedia() {
        try { return await RemoteDB.getMedia(); } catch { return []; }
    },

    // Categories
    async getCategories() {
        try { return await RemoteDB.getCategories(); } catch { return []; }
    },
    async createCategory(name) {
        const slug = name.toLowerCase().replace(/ /g, '-');
        return await RemoteDB.createCategory({ name, slug });
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
            image: p.featured_media_url || p.image,
            category: 'Geral'
        };
    }
};

window.CaasAPI = {
    init: () => Repository.init(),
    auth: { verifyBasicAuth: () => true },
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
    },
    media: { upload: (f) => Repository.uploadMedia(f), list: () => Repository.getMedia() },
    categories: { list: () => Repository.getCategories(), create: (n) => Repository.createCategory(n) }
};
Repository.init();
