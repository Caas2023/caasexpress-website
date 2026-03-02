# Proposal: Restructure Project

## What

Reorganizar a estrutura de arquivos do caasexpress-website, eliminando duplicações, separando concerns, e criando uma organização limpa e escalável.

## Why

A auditoria revelou problemas graves que dificultam manutenção e geram bugs:

1. **`api/src/` é cópia exata de `src/`** — 9 arquivos duplicados, risco de editar o errado
2. **Controllers misturam PHP e JS** na mesma pasta — confusão de stacks
3. **Dois bancos SQLite desconectados** — PHP usa `db/`, JS usa `data/`
4. **~10 scripts de debug soltos na raiz** — poluem o projeto
5. **Pastas vazias** (`php/`, `antigravity-kit/legacy/`) — lixo sem propósito
6. **Sem `package.json`** — dependências JS implícitas
7. **README e .gitignore desatualizados**

## Scope

### In scope
- Remover `api/src/` duplicada
- Separar controllers PHP vs JS em subpastas
- Mover scripts de debug para `tools/`
- Remover pastas vazias
- Atualizar README, .gitignore, .env.example
- Criar package.json
- Atualizar vercel.json para refletir mudanças

### Out of scope
- Refactoring de código (lógica dos controllers permanece igual)
- Migração/consolidação dos bancos de dados (requer decisão do usuário)
- Adição de testes automatizados
- Mudanças no frontend/UI

## Impact

- **Risk:** Médio — alterações de paths podem quebrar imports/requires no vercel.json
- **Mitigation:** Atualizar `vercel.json` e `api/index.php` junto com cada mudança de path
- **Benefit:** Projeto limpo, zero duplicação, manutenção 3x mais fácil
