<?php
/**
 * Script para criar categorias e autores
 */

require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

// Criar tabela de categorias se não existir
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Inserir categorias
$categories = [
    ['Entregas', 'entregas', 'Dicas e informações sobre entregas rápidas'],
    ['Motoboy', 'motoboy', 'Tudo sobre serviços de motoboy'],
    ['Logística', 'logistica', 'Estratégias e soluções de logística'],
    ['Frete', 'frete', 'Informações sobre custos e tipos de frete'],
    ['Negócios', 'negocios', 'Dicas para empresas e empreendedores'],
];

$stmt = $pdo->prepare("INSERT OR IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
foreach ($categories as $cat) {
    $stmt->execute($cat);
}
echo "✅ 5 Categorias criadas!\n";

// Inserir autores (na tabela users)
$authors = [
    ['carlos.silva', 'carlos@caasexpress.com', 'Carlos Silva'],
    ['ana.santos', 'ana@caasexpress.com', 'Ana Santos'],
    ['pedro.costa', 'pedro@caasexpress.com', 'Pedro Costa'],
    ['julia.lima', 'julia@caasexpress.com', 'Julia Lima'],
    ['marcos.oliveira', 'marcos@caasexpress.com', 'Marcos Oliveira'],
];

$stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password, display_name, role) VALUES (?, ?, 'hash123', ?, 'author')");
foreach ($authors as $author) {
    $stmt->execute($author);
}
echo "✅ 5 Autores criados!\n";

// Listar
echo "\n📂 Categorias:\n";
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
foreach ($cats as $c) {
    echo "   - {$c['name']} ({$c['slug']})\n";
}

echo "\n👤 Autores:\n";
$users = $pdo->query("SELECT * FROM users WHERE role = 'author'")->fetchAll();
foreach ($users as $u) {
    echo "   - {$u['display_name']} ({$u['username']})\n";
}

echo "\n🎉 Pronto!\n";
