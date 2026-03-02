# Tasks: Implement Cache System

## 1. Prepare Response Utility
- [ ] 1.1 In `src/Utils/Response.php`, add support for caching headers (e.g., `Response::json($data, $status = 200, $ttl = null)`)
- [ ] 1.2 Implement the header output: `Cache-Control: public, s-maxage={ttl}, stale-while-revalidate=86400`

## 2. Develop Application FileCache
- [ ] 2.1 Create `src/Utils/Cache.php`
- [ ] 2.2 Implement `get($key)` that reads from `sys_get_temp_dir()` and checks array/json expiry
- [ ] 2.3 Implement `set($key, $data, $ttl)`
- [ ] 2.4 Implement `clear()` (or clear by prefix)

## 3. Integrate Caching into PHP Controllers
- [ ] 3.1 Unify `PostController.php` index/list method to use `$cacheKey = "posts_" . md5(serialize($_GET))`
- [ ] 3.2 Try `Cache::get` before executing PDO SQL statements
- [ ] 3.3 Call `Response::json` with a `$ttl` of 60 to 300 seconds for public API reads
- [ ] 3.4 Repeat for `MediaController.php` list, Categories, and Tags

## 4. Admin API Fallbacks
- [ ] 4.1 In the React/Vite frontend (if any) or Express backend, when saving or querying fresh data, append a cache-busting timestamp (e.g., `?bypassCache=true`) to bypass Vercel's CDN so the admin always sees fresh data. 
- [ ] 4.2 Allow `Response.php` to bypass cache if `isset($_GET['bypassCache'])` or similar logic.

## 5. Verification
- [ ] 5.1 Run local dev server and hit API multiple times, observe log output to confirm DB is bypassed.
- [ ] 5.2 Deploy to Vercel and verify `X-Vercel-Cache: HIT` headers on subsequent GET requests to `/api/...`.
