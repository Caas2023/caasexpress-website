
// ============================================
// ADMIN PANEL LOGIC (Enhanced)
// ============================================

const AdminApp = {
    // State
    currentView: 'posts',
    isEditing: false,
    editingId: null,
    quill: null,
    mediaMode: 'featured', // 'featured' or 'editor'

    init() {
        this.checkAuth();
        this.setupNavigation();
        this.setupLogin();
        this.setupEditor();
        this.setupMediaManager();
        this.setupCategories();
        this.setupSEO();

        if (this.isAuthenticated()) {
            this.showDashboard();
            this.loadPosts();
        }
    },

    // ============================================
    // AUTH
    // ============================================
    isAuthenticated() { return localStorage.getItem('caas_admin_auth') === 'true'; },

    checkAuth() {
        if (this.isAuthenticated()) this.showDashboard();
        else this.showLogin();
    },

    setupLogin() {
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const user = document.getElementById('username').value;
            const pass = document.getElementById('password').value;

            // Tenta servidor primeiro se disponível
            try {
                const creds = btoa(`${user}:${pass}`);
                // Simple verify endpoint check
                const res = await fetch('http://localhost:3001/wp-json/wp/v2/users/me', {
                    headers: { 'Authorization': `Basic ${creds}` }
                });

                if (res.ok) {
                    this.loginSuccess(creds);
                    return;
                }
            } catch { }

            // Fallback config local
            if (user === 'admin' && pass === 'caas@express2024') {
                this.loginSuccess(btoa(`${user}:${pass}`));
                return;
            }

            document.getElementById('login-error').style.display = 'block';
        });

        document.getElementById('logout-btn').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('caas_admin_auth');
            localStorage.removeItem('caas_api_token');
            location.reload();
        });
    },

    loginSuccess(token) {
        localStorage.setItem('caas_admin_auth', 'true');
        localStorage.setItem('caas_api_token', token);
        location.reload();
    },

    showLogin() {
        document.getElementById('login-screen').style.display = 'flex';
        document.getElementById('dashboard-screen').style.display = 'none';
    },

    showDashboard() {
        document.getElementById('login-screen').style.display = 'none';
        document.getElementById('dashboard-screen').style.display = 'flex';
    },

    // ============================================
    // NAVIGATION
    // ============================================
    setupNavigation() {
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = item.getAttribute('data-page');
                this.switchView(page);
                document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
                item.classList.add('active');
            });
        });
        document.getElementById('btn-new-post').addEventListener('click', () => this.openEditor());
    },

    switchView(viewName) {
        document.querySelectorAll('.view-section').forEach(el => el.style.display = 'none');
        document.getElementById(`view-${viewName}`).style.display = 'block';
        if (viewName === 'posts') this.loadPosts();
        if (viewName === 'categories') this.loadCategoriesView();
        if (viewName === 'media') this.loadMediaView(); // Simple alias to open modal? Or separate view? Separate view content
    },

    // ============================================
    // POSTS LIST
    // ============================================
    async loadPosts() {
        const tbody = document.getElementById('posts-table-body');
        tbody.innerHTML = '<tr><td colspan="5" align="center">Carregando...</td></tr>';

        try {
            const data = await CaasAPI.posts.list({ per_page: 50 });
            const posts = data.posts || [];
            if (posts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" align="center">Nenhum post encontrado.</td></tr>';
                return;
            }
            tbody.innerHTML = posts.map(post => `
                <tr>
                    <td><strong>${post.title}</strong></td>
                    <td>${post.category || 'Geral'}</td>
                    <td>${new Date(post.created_at).toLocaleDateString()}</td>
                    <td><span class="status-badge status-${post.status}">${post.status}</span></td>
                    <td>
                        <button class="action-btn btn-edit" onclick="AdminApp.editPost(${post.id})">Editar</button>
                        <button class="action-btn btn-delete" onclick="AdminApp.deletePost(${post.id})">Apagar</button>
                    </td>
                </tr>
            `).join('');
        } catch {
            tbody.innerHTML = '<tr><td colspan="5" align="center" style="color:red">Erro ao carregar.</td></tr>';
        }
    },

    async deletePost(id) {
        if (confirm('Excluir este post?')) {
            await CaasAPI.posts.delete(id);
            this.loadPosts();
        }
    },

    // ============================================
    // EDITOR (Quill + Logic)
    // ============================================
    setupEditor() {
        // Init Quill
        this.quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image'], // Image here uses default base64, usually needs custom handler for server upload
                    ['clean']
                ]
            }
        });

        // Custom Image Handler (Optional - for inserting into Quill)
        const toolbar = this.quill.getModule('toolbar');
        toolbar.addHandler('image', () => {
            this.openMediaLibrary('editor');
        });

        // Save Actions
        document.getElementById('btn-cancel-edit').onclick = () => this.switchView('posts');
        document.getElementById('btn-save-post').onclick = () => this.savePost();

        // Populate Categories Checklist
        this.refreshEditorCategories();
    },

    async refreshEditorCategories() {
        const cats = await CaasAPI.categories.list();
        const container = document.getElementById('categories-checklist');
        container.innerHTML = cats.map(c => `
            <label style="display:block; margin-bottom:5px;">
                <input type="radio" name="post_category" value="${c.name}"> ${c.name}
            </label>
        `).join('');
        // Add default check
        if (container.querySelector('input')) container.querySelector('input').checked = true;
    },

    openEditor(post = null) {
        this.switchView('editor');
        this.refreshEditorCategories();

        if (post) {
            this.isEditing = true;
            this.editingId = post.id;
            document.getElementById('editor-title-label').textContent = 'Editar Post';
            document.getElementById('post-title').value = post.title;
            // Quill Content
            this.quill.root.innerHTML = post.content;

            // Meta logic
            document.getElementById('post-status').value = post.status;
            // Category check
            const radios = document.getElementsByName('post_category');
            for (let r of radios) { if (r.value === post.category) r.checked = true; }

            // Image
            this.setFeaturedImage(post.image);

            // SEO
            const meta = post.meta || {};
            document.getElementById('seo-keyword').value = meta.seo_keyword || '';
            document.getElementById('seo-title').value = meta.seo_title || '';
            document.getElementById('seo-desc').value = meta.seo_desc || '';
            this.updateSEOPreview();
        } else {
            this.isEditing = false;
            this.editingId = null;
            document.getElementById('editor-title-label').textContent = 'Novo Post';
            document.getElementById('post-title').value = '';
            this.quill.setText('');
            this.setFeaturedImage(null);
            document.getElementById('seo-keyword').value = '';
            document.getElementById('seo-title').value = '';
            document.getElementById('seo-desc').value = '';
            this.updateSEOPreview();
        }
    },

    async editPost(id) {
        const post = await CaasAPI.posts.get(id);
        if (post) this.openEditor(post);
    },

    async savePost() {
        const btn = document.getElementById('btn-save-post');
        btn.disabled = true;
        btn.textContent = 'Salvando...';

        try {
            const title = document.getElementById('post-title').value;
            const content = this.quill.root.innerHTML;
            const status = document.getElementById('post-status').value;
            // Get category
            let category = 'Geral';
            const catEl = document.querySelector('input[name="post_category"]:checked');
            if (catEl) category = catEl.value;

            const image = document.getElementById('post-image-url').value;

            // SEO Data
            const meta = {
                seo_keyword: document.getElementById('seo-keyword').value,
                seo_title: document.getElementById('seo-title').value,
                seo_desc: document.getElementById('seo-desc').value
            };

            const data = { title, content, status, category, image, meta };
            // Note: Server accepts 'meta' for custom fields, and 'featured_media' ID.
            // Simplified: we send image URL in 'image' prop (server might ignore but api.js normalize handles) 
            // OR store in meta too just in case.
            data.meta.image_url = image;

            if (this.isEditing) {
                await CaasAPI.posts.update(this.editingId, data);
            } else {
                await CaasAPI.posts.create(data);
            }
            this.switchView('posts');
        } catch (e) {
            console.error(e);
            alert('Erro ao salvar.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Publicar';
        }
    },

    // ============================================
    // MEDIA MANAGER
    // ============================================
    setupMediaManager() {
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(`tab-${btn.dataset.tab}`).classList.add('active');
                if (btn.dataset.tab === 'library') this.loadMediaLibraryGrid();
            });
        });

        // Upload
        document.getElementById('file-input').onchange = (e) => this.handleUpload(e.target.files[0]);
        // Allow select
        document.getElementById('btn-select-media').onclick = () => this.handleMediaSelection();
    },

    openMediaLibrary(mode = 'featured') {
        this.mediaMode = mode;
        document.getElementById('media-modal').style.display = 'flex';
        this.loadMediaLibraryGrid();
    },

    closeMediaLibrary() {
        document.getElementById('media-modal').style.display = 'none';
    },

    async handleUpload(file) {
        if (!file) return;
        const prog = document.getElementById('upload-progress');
        prog.style.display = 'block';
        try {
            const res = await CaasAPI.media.upload(file);
            console.log('Uploaded', res);
            prog.style.display = 'none';
            // Switch to library tab
            document.querySelector('.tab-btn[data-tab="library"]').click();
        } catch (e) {
            alert('Erro no upload: ' + e.message);
            prog.style.display = 'none';
        }
    },

    async loadMediaLibraryGrid() {
        const grid = document.getElementById('media-grid');
        grid.innerHTML = 'Carregando...';
        try {
            const items = await CaasAPI.media.list();
            grid.innerHTML = items.map(item => `
                <div class="media-item" onclick="AdminApp.selectMediaItem(this, '${item.source_url || item.guid?.rendered || item.url || ''}')" data-url="${item.source_url}">
                    <img src="${item.source_url || item.guid?.rendered || item.url || ''}">
                    <div class="check">✓</div>
                </div>
            `).join('');
        } catch {
            grid.innerHTML = 'Erro ao carregar mídia.';
        }
    },

    selectMediaItem(el, url) {
        document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('btn-select-media').disabled = false;
        document.getElementById('btn-select-media').dataset.selectedUrl = url;
    },

    handleMediaSelection() {
        const url = document.getElementById('btn-select-media').dataset.selectedUrl;
        if (!url) return;

        if (this.mediaMode === 'featured') {
            this.setFeaturedImage(url);
        } else if (this.mediaMode === 'editor') {
            const range = this.quill.getSelection(true);
            this.quill.insertEmbed(range.index, 'image', url);
        }
        this.closeMediaLibrary();
    },

    setFeaturedImage(url) {
        const preview = document.getElementById('featured-image-preview');
        const input = document.getElementById('post-image-url');
        input.value = url || '';
        if (url) {
            preview.style.backgroundImage = `url('${url}')`;
            preview.innerHTML = ''; // remove placeholder text
        } else {
            preview.style.backgroundImage = 'none';
            preview.innerHTML = '<span class="placeholder-text">Definir imagem destacada</span>';
        }
    },

    async loadMediaView() {
        // Just fill the simplified list for now
        this.openMediaLibrary(); // Reuse modal logic is easier
        this.switchView('posts'); // Go back to avoid empty page
    },

    // ============================================
    // CATEGORIES
    // ============================================
    setupCategories() {
        document.getElementById('btn-save-category').onclick = async () => {
            const name = document.getElementById('new-cat-name').value;
            if (!name) return;
            await CaasAPI.categories.create(name);
            document.getElementById('new-cat-name').value = '';
            document.getElementById('new-cat-slug').value = '';
            this.loadCategoriesView();
        };
        document.getElementById('btn-add-cat-quick').onclick = async () => {
            const name = prompt("Nome da nova categoria:");
            if (name) {
                await CaasAPI.categories.create(name);
                this.refreshEditorCategories();
            }
        }
    },

    async loadCategoriesView() {
        const tbody = document.getElementById('categories-table-body');
        const cats = await CaasAPI.categories.list();
        tbody.innerHTML = cats.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>${c.slug}</td>
                <td>-</td>
                <td><button class="action-btn btn-delete" onclick="alert('Funcionalidade em desenvolvimento')">Excluir</button></td>
            </tr>
        `).join('');
    },

    // ============================================
    // SEO
    // ============================================
    setupSEO() {
        const update = () => this.updateSEOPreview();
        document.getElementById('seo-title').addEventListener('input', update);
        document.getElementById('seo-desc').addEventListener('input', update);
        document.getElementById('post-title').addEventListener('input', update);
    },

    updateSEOPreview() {
        const title = document.getElementById('seo-title').value || document.getElementById('post-title').value || 'Título do Post';
        const desc = document.getElementById('seo-desc').value || 'Sua descrição aparecerá aqui nos resultados de busca...';
        document.getElementById('preview-seo-title').textContent = title.substring(0, 60) + (title.length > 60 ? '...' : '');
        document.getElementById('preview-seo-desc').textContent = desc.substring(0, 160) + (desc.length > 160 ? '...' : '');
    }
};

window.AdminApp = AdminApp;
AdminApp.init();
