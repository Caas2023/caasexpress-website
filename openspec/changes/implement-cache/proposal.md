# Proposal: Hybrid Cache System

## What

Implementar um sistema de Cache Híbrido focado em máxima performance para o CMS.
O sistema usará:
1. **Edge Caching (Vercel CDN):** Para entregar respostas em milissegundos sem bater no servidor PHP.
2. **File Caching (Local/PHP):** Como camada secundária para evitar sobrecarga no banco de dados SQLite quando o Edge Cache não estiver disponível ou expirar.

## Why

Atualmente, cada requisição ao `/wp-json/wp/v2/posts` bate no Vercel (invocando a Lambda PHP), que por sua vez lê o arquivo `database.sqlite` do Turso via HTTP ou local. Isso consome processamento, é lento e não escala bem caso o site tenha um pico de acessos. Como um site de conteúdo / CMS é lido dezenas de vezes mais do que escrito, habilitar cache é a melhor otimização possível (ganhos de até 100x na velocidade de resposta).

## Scope

### In scope
- Adicionar headers `Cache-Control` padrão do Vercel (`s-maxage`, `stale-while-revalidate`) nos endpoints de leitura do PHP (`PostController`, `CategoryController`).
- Criar uma classe `Cache.php` no PHP para salvar e ler payloads JSON em disco local (`/tmp/` no Vercel).
- Adicionar mecanismo de "Bust Cache" (invalidação) no back-end JS quando um post for criado/atualizado.

### Out of scope
- Adicionar Redis, Memcached ou infraestrutura externa (mantendo a filosofia simples e grátis).
- Fazer cache de endpoints administrativos ou rotas que requerem autenticação.

## Impact
- **Performance:** Respostas da API cairão de ~200ms para ~10ms (servidas pelo Node da borda).
- **Custo:** Economia drástica nas invocações de Serverless Functions (Vercel).
- **Risco:** Dados desatualizados por alguns segundos/minutos. Mitigado usando `stale-while-revalidate` e gatilhos de invalidação.
