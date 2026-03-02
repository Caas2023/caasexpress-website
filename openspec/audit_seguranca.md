# Relatório de Auditoria de Segurança - Caas Express

**Data:** Março 2026
**Escopo:** Backend PHP (Produção/Vercel) e Backend JS (Dev/Admin)
**Metodologia:** Análise estática de código focada na OWASP Top 10 (2025).

---

## 🛑 Resumo Executivo

O sistema apresenta uma arquitetura dual-stack que, embora funcional, introduz dois modelos de segurança paralelos e inconsistentes. Foram identificadas **1 vulnerabilidade Crítica** e **2 de severidade Média/Alta**. O risco mais urgente é a capacidade de executar código remoto (RCE) através do upload irrestrito de arquivos no backend PHP.

### Matriz de Prioridades

| ID | Severidade | Vulnerabilidade | Componente |
|---|---|---|---|
| SEC-01 | 🔴 **CRÍTICA** | Arbitrary File Upload (RCE Risk) | `src/controllers/php/MediaController.php` |
| SEC-02 | 🟠 **ALTA** | Bypass de Autenticação em métodos GET | `src/middleware/auth.middleware.js` |
| SEC-03 | 🟡 **MÉDIA** | Validação Simples de Extensões de Upload | `src/controllers/js/media.controller.js` |
| SEC-04 | 🟡 **MÉDIA** | Comparação Plaintext de Senha | `src/middleware/auth.middleware.js` |

---

## 🔍 Detalhamento das Descobertas

### [SEC-01] Arbitrary File Upload (RCE Risk) - 🔴 CRÍTICA
**Onde:** `src/controllers/php/MediaController.php` (linhas 15-53)
**OWASP Category:** A01: Broken Access Control / Insecure Design
**Descrição:** O método `create()` aceita qualquer arquivo enviado e o salva na pasta `/uploads/`. Ele extrai a extensão do nome do arquivo (ex: `.php`) e mantém essa extensão. O sistema **não valida** se a extensão é uma imagem ou vídeo permitidos, nem verifica o conteúdo real (MIME type) do arquivo com segurança.
**Impacto:** Um invasor autenticado (ou caso a auth seja burlada) pode enviar um arquivo `shell.php` ou `.phtml`. Se o diretório `uploads/` onde isso for salvo permitir a execução de scripts PHP, o atacante ganha controle total do servidor (RCE).
**Correção Recomendada:**
1. Usar uma *whitelist* estrita de extensões permitidas (`jpg`, `jpeg`, `png`, `gif`, `webp`, `mp4`).
2. Validar o MIME Type real usando `mime_content_type()`.
3. Renomear o arquivo para não incluir diretamente a extensão do usuário, ou forçar uma extensão extraída do MIME type real verificado.

### [SEC-02] Bypass de Autenticação em métodos GET - 🟠 ALTA
**Onde:** `src/middleware/auth.middleware.js` (linha 40)
**OWASP Category:** A01: Broken Access Control
**Descrição:** O middleware de autenticação do Node.js permite que *qualquer* requisição HTTP `GET` passe sem validação de token (`if (req.method === 'GET') { return next(); }`).
**Impacto:** Qualquer ator externo pode ler todos os dados da API administrativa local/interna que usam método GET (ex: rascunhos de posts, mídias ocultas, configurações), comprometendo completamente a confidencialidade dos dados do CMS.
**Correção Recomendada:**
Implementar rotas públicas separadas das rotas administrativas. Se o endpoint for administrativo, não deve haver bypass para o método GET. Caso os GETs precisem ser públicos para renderizar o frontend, certifique-se de que rascunhos, lixo, e usuários não sejam expostos publicamente.

### [SEC-03] Validação Fraca em Uploads Binários - 🟡 MÉDIA
**Onde:** `src/controllers/js/media.controller.js` (método `uploadBinary`)
**OWASP Category:** A05: Security Misconfiguration
**Descrição:** O endpoint customizado para upload binário extrai a extensão ou aceita extensões passadas via `content-disposition`. Apesar de verificar se o header diz `image/` ou `application/octet-stream`, o header `Content-Type` é facilmente falsificado pelo lado do cliente.
**Impacto:** Permite o upload de arquivos indesejados (HTML, JS, EXE) que podem ser servidos através do endpoint estático `/uploads/`, abrindo brechas para Stored XSS ou distribuição de malware.
**Correção Recomendada:**
Usar bibliotecas como `file-type` no Node.js que lê os "magic bytes" (o cabeçalho binário real do arquivo) para validar o tipo real do arquivo, rejeitando qualquer coisa fora de uma *whitelist* rígida.

### [SEC-04] Comparação de Senha em Plaintext - 🟡 MÉDIA
**Onde:** `src/middleware/auth.middleware.js` (linha 21)
**OWASP Category:** A07: Authentication Failures
**Descrição:** Para a validação de `Basic Auth` no Node.js, a senha envida no payload (`normalizedPassword`) é comparada com a senha contida na variável ambiente `.env` (`API_PASSWORD`) diretamente em plaintext (texto puro).
**Impacto:** Embora seja uma verificação servidor a servidor e dependa de variáveis de ambiente estritas, armazenar no `.env` e checar em plaintext viola as melhores práticas. Idealmente, a comparação deveria envolver hashes protegidos contra "timing attacks" (funções seguras de comparação de strings) para minimizar o risco caso a infraestrutura vaze variáveis de ambiente.
**Correção Recomendada:**
Usar `crypto.timingSafeEqual` ou mudar para que `API_PASSWORD` armazene o hash `bcrypt`, usando `bcrypt.compare` na validação (simulando como o PHP valida senhas no projeto).

---

## ✅ Pontos Positivos (O que você acertou)

1. **Prevenção contra SQL Injection (SQLi):**
   - No backend **PHP**, o PDO está sendo usado implacavelmente com *Prepared Statements* (`$pdo->prepare` e `execute()`). Inputs perigosos como `$page = (int)$_GET['page']` utilizam Type Casting defensivo, mitigando riscos de injeção direta.
   - No backend **Node.js/Turso**, o uso de `libsql/client` é acompanhado da correta segregação entre a *query string* e seus *argumentos* na propriedade `args` (ex: `await getDb().execute({ sql: query, args })`). **O código está seguro contra injeção de SQL padrão.**
2. **Separação de Preocupações:** O isolamento das configurações de DB e as variáveis de acesso não sendo codificadas dentro das lógicas de negócio estão em conformidade com as recomendações de *12-Factor Apps*.
3. **Senhas do PHP:** O backend PHP faz uso apropriado e moderno de `password_verify` no arquivo `Auth.php`.

---

## 📋 Conclusão e Próximos Passos recomendados

A falha principal está na manipulação e aceitação de **uploads de arquivos**. Você tem uma forte segurança contra adulteração do banco (SQLi), mas uma fraca barreira contra arquivos maliciosos (Uploads).

**Plano de Ação Imediato:**
1. Introduzir uma função sanitizadora rígida nos controllers de Mídia (PHP e JS).
2. Revisar a lógica do Auth.js para evitar vazamentos de rascunhos ou dados pelo caminho Bypass do GET.
