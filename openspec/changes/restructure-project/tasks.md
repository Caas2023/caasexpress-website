# Tasks: Restructure Project

## 1. Remove Duplicate `api/src/`
- [ ] 1.1 Confirm `api/index.php` uses `__DIR__ . '/src/...'` paths (currently does)
- [ ] 1.2 Update `api/index.php` require paths from `'/src/...'` to `'/../src/...'`
- [ ] 1.3 Delete `api/src/` directory entirely
- [ ] 1.4 Verify `vercel.json` `includeFiles` still resolves correctly (`../src/**/*.php`)

## 2. Separate Controllers by Language
- [ ] 2.1 Create `src/controllers/php/` and `src/controllers/js/`
- [ ] 2.2 Move `PostController.php`, `MediaController.php`, `UserController.php` → `src/controllers/php/`
- [ ] 2.3 Move all `.controller.js` files → `src/controllers/js/`
- [ ] 2.4 Update `api/index.php` require paths to `controllers/php/`
- [ ] 2.5 Update `src/routes/*.routes.js` require paths to `../controllers/js/`

## 3. Organize Root Scripts
- [ ] 3.1 Create `tools/debug/`, `tools/import/`
- [ ] 3.2 Move debug scripts: `check_links.php`, `check_post.php`, `check_real_interlinks.php`, `check_seo_data.php`, `check_status.php`, `debug_links.php`, `debug_links_deep.php`, `test_links.php`, `audit.php`, `audit_json.php` → `tools/debug/`
- [ ] 3.3 Move import scripts: `setup_categories_authors.php`, `n8n-import-wordpress.json` → `tools/import/`
- [ ] 3.4 Move `start_server.bat`, `start_seo_robot.bat`, `server_router.php` → `tools/`

## 4. Clean Empty/Legacy Directories
- [ ] 4.1 Delete `php/` (empty)
- [ ] 4.2 Delete `antigravity-kit/legacy/` (empty)
- [ ] 4.3 Delete `antigravity-kit/legacy-backend/` (legacy server.js)

## 5. Create `package.json`
- [ ] 5.1 Create `package.json` with name, version, scripts, and dependencies (`@libsql/client`, `express`, `multer`)

## 6. Update Project Configs
- [ ] 6.1 Expand `.gitignore` — add `db/*.sqlite*`, `*.bat`, `.vscode/`, etc.
- [ ] 6.2 Update `vercel.json` includeFiles paths if needed
- [ ] 6.3 Update `README.md` with real structure and stack

## 7. Verify
- [ ] 7.1 Confirm all `require()` and `require_once` paths resolve
- [ ] 7.2 Test local dev server starts without errors
- [ ] 7.3 Verify `vercel.json` routing still correct
