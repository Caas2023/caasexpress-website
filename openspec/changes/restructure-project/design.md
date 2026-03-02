# Design: Restructure Project

## Approach

Reorganização conservadora — mover arquivos e atualizar referências, sem alterar lógica de código.

## Architecture Decisions

### 1. Eliminar `api/src/` — manter `src/` como único backend

O `api/index.php` usa `require_once __DIR__ . '/src/...'` (relativo a `api/`), enquanto o `vercel.json` inclui `../src/**/*.php` (relativo a `api/`). Portanto:

- `api/src/` contém os arquivos **efetivamente usados** em produção (Vercel)
- `src/` (raiz) contém os arquivos usados pelo Express local

**Decisão:** Manter `src/` como canônico. Deletar `api/src/`. Atualizar `api/index.php` para fazer `require_once __DIR__ . '/../src/...'` (já é isso que o `vercel.json` inclui via `../src/**/*.php`).

### 2. Separar Controllers por linguagem

```
src/controllers/
├── php/
│   ├── PostController.php
│   ├── MediaController.php
│   └── UserController.php
└── js/
    ├── posts.controller.js
    ├── media.controller.js
    ├── categories.controller.js
    ├── tags.controller.js
    ├── seo.controller.js
    └── webstories.controller.js
```

Atualizar imports em:
- `api/index.php` → `require_once __DIR__ . '/../src/controllers/php/PostController.php'`
- `src/routes/*.routes.js` → `require('../controllers/js/posts.controller')`

### 3. Mover scripts de debug para `tools/`

```
tools/
├── debug/          ← check_*.php, debug_*.php, test_links.php
├── seo/            ← conteúdo do antigravity-kit/ (exceto legacy/)
├── scripts/        ← antigravity-kit/scripts/
└── import/         ← n8n-import-wordpress.json, setup_categories_authors.php
```

### 4. Limpeza na raiz

- Deletar `php/` (vazia)
- Deletar `antigravity-kit/legacy/` (vazia)
- Deletar `antigravity-kit/legacy-backend/` (server.js antigo, já substituído)
- Mover `start_server.bat`, `start_seo_robot.bat` → `tools/`
- Mover `server_router.php` → `tools/`

### 5. Criar `package.json` mínimo

Declarar as dependências implícitas (`@libsql/client`, `express`, `multer`) e scripts de desenvolvimento.

### 6. Atualizar configs

- `vercel.json` — paths de include
- `.gitignore` — adicionar `db/*.sqlite`, `*.bat` opcionais, `.vscode/`
- `README.md` — refletir stack e estrutura real

## Files Changed

| Ação | Arquivo |
|------|---------|
| DELETE | `api/src/` (inteiro, duplicata) |
| MOVE | `src/controllers/*.php` → `src/controllers/php/` |
| MOVE | `src/controllers/*.js` → `src/controllers/js/` |
| MOVE | `check_*.php`, `debug_*.php`, `test_links.php` → `tools/debug/` |
| MOVE | `audit.php`, `audit_json.php` → `tools/debug/` |
| MOVE | `setup_categories_authors.php` → `tools/import/` |
| MOVE | `n8n-import-wordpress.json` → `tools/import/` |
| MOVE | `start_server.bat`, `start_seo_robot.bat` → `tools/` |
| MOVE | `server_router.php` → `tools/` |
| DELETE | `php/` (vazia) |
| DELETE | `antigravity-kit/legacy/` (vazia) |
| DELETE | `antigravity-kit/legacy-backend/` (legacy) |
| MODIFY | `api/index.php` (paths para `../src/controllers/php/`) |
| MODIFY | `src/routes/*.routes.js` (paths para `../controllers/js/`) |
| MODIFY | `vercel.json` (includeFiles path) |
| MODIFY | `.gitignore` (expandir) |
| NEW | `package.json` |
| MODIFY | `README.md` (reescrever) |

## Risks

| Risco | Mitigação |
|-------|-----------|
| Quebrar imports do Vercel | Testar `vercel.json` includeFiles com structure nova |
| Quebrar routes Express | Atualizar todos os `require()` nos routes |
| Deploy falhar | Verificar com `vercel --prod` após mudanças |
