# Caas Express Website

Este projeto é um sistema "Dual-Stack" que serve conteúdos dinâmicos de um CMS via API. Ele foi recém-reestruturado para facilitar a manutenção.

## 🏗 Arquitetura Dual-Stack

O projeto roda em dois ambientes diferentes:

1. **Produção (Vercel serverless):** PHP 8+ com PDO. Utilizado como API de leitura rápida e roteamento otimizado.
2. **Desenvolvimento / API Admin:** Node.js (Express). Utilizado para operações de escrita, painéis locais e integrações n8n.

### Estrutura de Pastas

```
/
├── src/                  # Código-fonte principal
│   ├── Config/           # Configurações PHP/JS e DB instâncias
│   ├── Utils/            # Utilitários (Auth, Response)
│   ├── models/           # Models JS (SQLite/Turso)
│   ├── routes/           # Rotas do Express JS
│   └── controllers/      # Lógica de négocio
│       ├── php/          # Controllers para Vercel (PostController.php, etc)
│       └── js/           # Controllers Node.js API local (posts.controller.js)
├── api/                  # Roteador Vercel (index.php) compatível com WP REST API
├── data/                 # Banco de dados local (Turso replica/local SQLite)
├── db/                   # Banco de dados PHP
├── tools/                # Scripts utilitários e de debug
│   ├── debug/            # Scripts antigos de validação (.php)
│   ├── import/           # Workflows n8n e setup
│   ├── seo/              # Suite Antigravity SEO
│   └── scripts/          # Scripts Antigravity auxiliares
└── vercel.json           # Roteamento de Produção
```

## 🚀 Como Rodar

### Node.js (API Admin e Rotas Locais)
```bash
npm install
npm run dev
```

### Script SEO
Rodar o auto-gerador de posts e WebStories:
```bash
.\tools\start_seo_robot.bat
```

## 🔒 Variáveis de Ambiente (.env)

Consulte o `.env.example` para as variáveis necessárias:
- `TURSO_DATABASE_URL` (Conexão do banco primário)
- `TURSO_AUTH_TOKEN`
- `API_USER` / `API_PASSWORD` (Basic Auth para endpoints críticos)
- `BEARER_TOKEN` (Autenticação Bearer em rotas Node.js)
