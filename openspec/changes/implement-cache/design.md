# Design: Hybrid Cache System

## Strategy: Stale-While-Revalidate (SWR)

O Vercel possui um CDN poderoso. Podemos controlar esse CDN enviando headers HTTP na nossa API PHP.
Se enviarmos `Cache-Control: s-maxage=60, stale-while-revalidate=86400`, o Vercel guarda a resposta e a serve instantaneamente para todos os usuários por 60 segundos. Se o tempo passar, o Vercel serve a versão velha instantaneamente e atualiza a nova versão em background.

## Technical Architecture

### 1. Edge Cache Headers (`src/Utils/Response.php`)

Modificar a classe `Response` para aceitar um parâmetro de TTL e setar os headers:
```php
header('Cache-Control: public, s-maxage=60, stale-while-revalidate=86400');
```
Isso será aplicado apenas às rotas públicas (GET de posts, categorias, etc).

### 2. File Cache PHP (`src/Utils/Cache.php`)

Para evitar que a origin (quando o CDN expira) force o banco SQLite atoa, criaremos um FileCache.
Em ambientes Vercel, o disco local é read-only, **exceto** a pasta `/tmp`.
```php
class Cache {
    public static function get($key) {
        $file = sys_get_temp_dir() . '/cache_' . md5($key) . '.json';
        // logica de ttl e filemtime
    }
    public static function set($key, $data, $ttl) { ... }
}
```

### 3. Implementação no `PostController.php`

No método `list()` e `get()`, antes de bater no banco:
1. Puxa do `Cache::get($cacheKey)`. Se existir, retorna.
2. Se não existir, vai no banco, e chama `Cache::set($cacheKey, $dados)`.
3. Retorna usando `Response::json($dados, 200, 60)` para adicionar os headers pro Vercel CDN.

### 4. Invalidação (Backend JS)

Quando um Post é criado, editado ou apagado via Express (`controllers/js/posts.controller.js`), precisamos informar ao Vercel para limpar (ou expirar) o Cache. 
Como o Vercel não fornece invalidação On-Demand (Purge) no plano grátis facilmente, a estratégia de SWR com tempos curtos (60s) ou o controle programático via `Cache.php` é suficiente para a maioria dos casos. No dashboard admin, o painel deve usar querystrings únicas para burlar o cache temporariamente (ex: `?v=timestamp`).

## Risks
* O cache de disco de uma Vercel Lambda não é compartilhado entre instâncias (se o site escalar, cada "worker" tem seu `/tmp`).
* Resolvido: Por isso o CDN Edge (`Cache-Control`) é a linha de defesa principal. O `/tmp` é apenas um fallback otimizado.
