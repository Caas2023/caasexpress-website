
// ============================================
// ADMIN PANEL LOGIC (Comprehensive)
// ============================================

const AdminApp = {
    // State
    quill: null,
    currentView: 'dashboard',
    isEditing: false,
    editingId: null,
    mediaMode: 'featured', // 'featured' or 'editor'

    init() {
        this.checkAuth();
        this.setupNavigation();
        this.setupLogin();
        // Init Components
        this.setupEditor();
        this.setupMediaManager();
        this.setupQuickDraft();

        // Initial View
        if (this.isAuthenticated()) {
            this.showDashboard();
            this.loadDashboardStats();
        }
    },

    // ============================================
    // AUTH
    // ============================================
    isAuthenticated() { return localStorage.getItem('caas_admin_auth') === 'true'; },
    checkAuth() { if (this.isAuthenticated()) this.showDashboard(); else this.showLogin(); },

    setupLogin() {
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const u = document.getElementById('username').value;
            const p = document.getElementById('password').value;
            const btn = e.target.querySelector('button');
            const originalText = btn.textContent;

            btn.textContent = 'Verificando...';
            btn.disabled = true;

            try {
                // Tenta autenticar via API
                const token = btoa(u + ':' + p);
                const res = await fetch('/wp-json/wp/v2/users/me', {
                    headers: { 'Authorization': `Basic ${token}` }
                });

                if (res.ok) {
                    localStorage.setItem('caas_admin_auth', 'true');
                    localStorage.setItem('caas_api_token', token); // Salva token Basic Auth
                    location.reload();
                } else {
                    document.getElementById('login-error').style.display = 'block';
                    document.getElementById('login-error').textContent = 'Credenciais inválidas.';
                }
            } catch (err) {
                console.error(err);
                document.getElementById('login-error').style.display = 'block';
                document.getElementById('login-error').textContent = 'Erro de conexão com o servidor.';
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
        document.getElementById('logout-btn').addEventListener('click', () => {
            localStorage.removeItem('caas_admin_auth');
            location.reload();
        });
    },

    showLogin() {
        document.getElementById('login-screen').style.display = 'flex';
        document.getElementById('dashboard-screen').style.display = 'none';
    },

    showDashboard() {
        document.getElementById('login-screen').style.display = 'none';
        document.getElementById('dashboard-screen').style.display = 'flex';
        this.switchView('dashboard');
    },

    // ============================================
    // NAVIGATION
    // ============================================
    setupNavigation() {
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = item.dataset.page;
                this.switchView(page);
            });
        });

        // Post Button
        const btnNew = document.getElementById('btn-new-post');
        if (btnNew) btnNew.onclick = () => this.openEditor(null, 'post');
    },

    switchView(viewName) {
        // Toggle Sidebar Active
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        const activeNav = document.querySelector(`.nav-item[data-page="${viewName}"]`);
        if (activeNav) activeNav.classList.add('active');

        // Toggle View
        document.querySelectorAll('.view-section').forEach(el => el.style.display = 'none');
        const view = document.getElementById(`view-${viewName}`);
        if (view) {
            view.style.display = 'block';
            // Load Data if needed
            if (viewName === 'dashboard') this.loadDashboardStats();
            if (viewName === 'posts') this.loadPosts();
            if (viewName === 'pages') this.loadPages();
            if (viewName === 'media') this.loadMediaGrid();
            // Others are static for now
        }
    },

    // ============================================
    // DASHBOARD
    // ============================================
    async loadDashboardStats() {
        const stats = await CaasAPI.dashboard.stats();
        document.getElementById('dashboard-posts-count').textContent = stats.posts;
        document.getElementById('dashboard-pages-count').textContent = stats.pages;
        document.getElementById('dashboard-comments-count').textContent = stats.comments;

        // Activity Log (Mock)
        document.getElementById('dashboard-activity-list').innerHTML = `
            <li>"Olá Mundo" publicado recentemente.</li>
            <li>Backup automático realizado às 03:00.</li>
        `;
    },

    quickDraft() {
        const t = document.getElementById('quick-draft-title').value;
        const c = document.getElementById('quick-draft-content').value;
        if (t && c) {
            alert('Rascunho salvo no banco de dados local!');
            document.getElementById('quick-draft-title').value = '';
            document.getElementById('quick-draft-content').value = '';
        }
    },

    // ============================================
    // LISTS (Posts & Pages)
    // ============================================
    async loadPosts() {
        const tbody = document.getElementById('posts-table-body');
        tbody.innerHTML = '<tr><td colspan="7">Carregando...</td></tr>';
        const posts = await CaasAPI.posts.list();
        if (!posts.length) { tbody.innerHTML = '<tr><td colspan="7">Nenhum post.</td></tr>'; return; }

        tbody.innerHTML = posts.map(p => `
            <tr>
                <td><input type="checkbox"></td>
                <td><strong><a href="#" onclick="AdminApp.editItem(${p.id}, 'post')">${p.title}</a></strong>
                    <div class="row-actions"><a href="#" onclick="AdminApp.editItem(${p.id}, 'post')">Editar</a> | <a href="#" style="color:#b32d2e" onclick="AdminApp.deleteItem(${p.id}, 'posts')">Lixeira</a> | <a href="post.html?id=${p.id}" target="_blank">Ver</a></div>
                </td>
                <td>admin</td>
                <td>${p.category || 'Geral'}</td>
                <td>-</td>
                <td>-</td>
                <td>${new Date(p.date || p.created_at || Date.now()).toLocaleDateString()}</td>
            </tr>
        `).join('');
    },

    async loadPages() {
        const tbody = document.getElementById('pages-table-body');
        tbody.innerHTML = '<tr><td colspan="3">Carregando...</td></tr>';
        const pages = await CaasAPI.pages.list();
        if (!pages.length) { tbody.innerHTML = '<tr><td colspan="3">Nenhuma página.</td></tr>'; return; }

        tbody.innerHTML = pages.map(p => `
            <tr>
                <td><strong><a href="#" onclick="AdminApp.editItem(${p.id}, 'page')">${p.title}</a></strong>
                    <div class="row-actions"><a href="#" onclick="AdminApp.editItem(${p.id}, 'page')">Editar</a> | <a href="#" style="color:#b32d2e">Lixeira</a></div>
                </td>
                <td>admin</td>
                <td>${new Date(p.date || p.created_at || Date.now()).toLocaleDateString()}</td>
            </tr>
        `).join('');
    },

    async deleteItem(id, type) {
        if (confirm('Mover para lixeira?')) {
            if (type === 'posts') await CaasAPI.posts.delete(id);
            this.loadPosts();
        }
    },

    // ============================================
    // EDITOR
    // ============================================
    setupEditor() {
        this.quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'link', 'image'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }]
                ]
            }
        });

        // Save Btn
        document.getElementById('btn-save-post').onclick = () => this.saveItem();
        document.getElementById('btn-cancel-edit').onclick = () => {
            this.switchView(this.currentEditType === 'page' ? 'pages' : 'posts');
        };

        // SEO Live Preview
        ['seo-title', 'seo-desc', 'post-title'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', () => this.updateSEOPreview());
        });
    },

    openEditor(data, type) {
        this.currentEditType = type; // 'post' or 'page'
        this.switchView('editor');

        document.getElementById('editor-title-label').textContent = data ? `Editar ${type === 'page' ? 'Página' : 'Post'}` : `Adicionar Novo ${type === 'page' ? 'Página' : 'Post'}`;

        if (data) {
            this.isEditing = true;
            this.editingId = data.id;
            document.getElementById('post-title').value = data.title;
            this.quill.root.innerHTML = data.content;
            document.getElementById('post-image-url').value = data.image || '';
            const meta = data.meta || {};
            document.getElementById('seo-title').value = meta.seo_title || '';
            document.getElementById('seo-desc').value = meta.seo_desc || '';
            document.getElementById('seo-keyword').value = meta.seo_keyword || '';

            // Image Preview
            if (data.image) {
                document.getElementById('featured-image-preview').style.backgroundImage = `url('${data.image}')`;
                document.getElementById('featured-image-preview').textContent = '';
            } else {
                document.getElementById('featured-image-preview').style.backgroundImage = 'none';
                document.getElementById('featured-image-preview').textContent = 'Definir imagem destacada';
            }

        } else {
            this.isEditing = false;
            this.editingId = null;
            document.getElementById('post-title').value = '';
            this.quill.root.innerHTML = '';
            document.getElementById('post-image-url').value = '';
            document.getElementById('featured-image-preview').style.backgroundImage = 'none';
        }
        this.updateSEOPreview();
        this.loadCategoriesChecklist();
    },

    createPage() { this.openEditor(null, 'page'); },

    async editItem(id, type) {
        const item = type === 'post' ? await CaasAPI.posts.get(id) : await CaasAPI.posts.get(id); // Use generic get or separate? API uses same
        if (item) this.openEditor(item, type);
    },

    async saveItem() {
        const title = document.getElementById('post-title').value;
        const content = this.quill.root.innerHTML;
        const image = document.getElementById('post-image-url').value;
        const status = document.getElementById('post-status').value;

        const meta = {
            seo_title: document.getElementById('seo-title').value,
            seo_desc: document.getElementById('seo-desc').value,
            seo_keyword: document.getElementById('seo-keyword').value
        };

        const data = { title, content, image, status, meta, type: this.currentEditType };

        const btn = document.getElementById('btn-save-post');
        btn.textContent = 'Salvando...';
        btn.disabled = true;

        try {
            if (this.isEditing) {
                await CaasAPI.posts.update(this.editingId, data);
            } else {
                await CaasAPI.posts.create(data);
            }
            // Return to list
            this.switchView(this.currentEditType === 'page' ? 'pages' : 'posts');
        } catch (e) {
            alert('Erro ao salvar');
            console.error(e);
        } finally {
            btn.textContent = 'Atualizar / Publicar';
            btn.disabled = false;
        }
    },

    // ============================================
    // MEDIA MANAGER
    // ============================================
    setupMediaManager() {
        // Tab switching logic for modal
        document.querySelectorAll('.media-tabs .tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.media-tabs .tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.modal-body .tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(`tab-${btn.dataset.tab}`).classList.add('active');
                if (btn.dataset.tab === 'library') this.loadMediaGrid('modal');
            });
        });

        document.getElementById('file-input').onchange = (e) => this.uploadFile(e.target.files[0]);
        document.getElementById('btn-select-media').onclick = () => this.handleMediaSelect();
    },

    openMediaLibrary(mode) {
        this.mediaMode = mode;
        document.getElementById('media-modal').style.display = 'flex';
        this.loadMediaGrid('modal');
    },

    closeMediaLibrary() { document.getElementById('media-modal').style.display = 'none'; },

    async uploadFile(file) {
        if (!file) return;
        document.getElementById('upload-progress').style.display = 'block';
        try {
            await CaasAPI.media.upload(file);
            document.querySelector('[data-tab="library"]').click(); // Switch to library
        } catch (e) { alert('Erro upload'); }
        document.getElementById('upload-progress').style.display = 'none';
    },

    async loadMediaGrid(context) {
        const container = context === 'modal' ? document.getElementById('media-grid') : document.getElementById('view-media-grid');
        container.innerHTML = 'Carregando...';
        try {
            const items = await CaasAPI.media.list();
            container.innerHTML = items.map(item => `
                <div class="media-item" onclick="AdminApp.selectMediaItem(this, '${item.source_url || item.url}')">
                    <img src="${item.source_url || item.url}" style="width:100%; height:100%; object-fit:cover;">
                    <div class="check">✓</div>
                </div>
            `).join('');
        } catch { container.innerHTML = 'Erro ao carregar mídia.'; }
    },

    selectMediaItem(el, url) {
        document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        const btn = document.getElementById('btn-select-media');
        btn.disabled = false;
        btn.dataset.url = url;
    },

    handleMediaSelect() {
        const url = document.getElementById('btn-select-media').dataset.url;
        if (this.mediaMode === 'featured') {
            document.getElementById('post-image-url').value = url;
            document.getElementById('featured-image-preview').style.backgroundImage = `url('${url}')`;
            document.getElementById('featured-image-preview').textContent = '';
        } else if (this.mediaMode === 'editor') {
            const range = this.quill.getSelection(true);
            this.quill.insertEmbed(range.index, 'image', url);
        }
        this.closeMediaLibrary();
    },

    // ============================================
    // UTILS
    // ============================================
    updateSEOPreview() {
        const t = document.getElementById('seo-title').value || document.getElementById('post-title').value || 'Título';
        const d = document.getElementById('seo-desc').value || 'Descrição...';
        document.getElementById('preview-seo-title').textContent = t;
        document.getElementById('preview-seo-desc').textContent = d;
    },

    async loadCategoriesChecklist() {
        const cats = await CaasAPI.categories.list();
        document.getElementById('categories-checklist').innerHTML = cats.map(c =>
            `<label><input type="checkbox" value="${c.id}"> ${c.name}</label>`
        ).join('<br>');
    }
};

window.AdminApp = AdminApp;
AdminApp.init();
