<?php
require_once __DIR__ . '/src/Config/Database.php';

use Src\Config\Database;

try {
    $pdo = Database::getInstance();
    
    echo "Iniciando configuração do banco de dados...\n";

    // Tabela Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL, -- Hash da senha
        email TEXT NOT NULL,
        display_name TEXT,
        role TEXT DEFAULT 'author',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Usuário admin padrão (senha: admin123 - alterar em produção!)
    // Hash gerado com password_hash('admin123', PASSWORD_DEFAULT)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, email, display_name, role) 
                    VALUES ('admin', '$pass', 'admin@caasexpress.com', 'Admin', 'administrator')");
        echo "Usuário admin criado.\n";
    }

    // Tabela Posts
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE,
        content TEXT,
        excerpt TEXT,
        status TEXT DEFAULT 'draft', -- publish, draft, private
        type TEXT DEFAULT 'post', -- post, page
        author_id INTEGER,
        featured_media INTEGER,
        comment_status TEXT DEFAULT 'open',
        ping_status TEXT DEFAULT 'open',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id)
    )");

    // Tabela Meta (para armazenar metadados flexíveis dos posts)
    $pdo->exec("CREATE TABLE IF NOT EXISTS postmeta (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        meta_key TEXT NOT NULL,
        meta_value TEXT,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )");

    // Tabela Categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS term_taxonomy (
        term_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE,
        description TEXT,
        taxonomy TEXT DEFAULT 'category', -- category, post_tag
        parent INTEGER DEFAULT 0,
        count INTEGER DEFAULT 0
    )");

    // Relacionamento Post <-> Term
    $pdo->exec("CREATE TABLE IF NOT EXISTS term_relationships (
        object_id INTEGER NOT NULL,
        term_taxonomy_id INTEGER NOT NULL,
        PRIMARY KEY (object_id, term_taxonomy_id),
        FOREIGN KEY (object_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (term_taxonomy_id) REFERENCES term_taxonomy(term_id) ON DELETE CASCADE
    )");

    // Tabela Media
    $pdo->exec("CREATE TABLE IF NOT EXISTS media (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        file_path TEXT NOT NULL,
        mime_type TEXT,
        alt_text TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
     
    // Web Stories (pode ser um post type, mas se quiser separado...)
    // Vamos usar post_type='web-story' na tabela posts, mas se precisar de campos específicos, usamos postmeta.

    // Categorias padrão
    $defaultCats = ['Sem categoria', 'Dicas', 'Serviços', 'Logística', 'Negócios'];
    foreach ($defaultCats as $cat) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat)));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM term_taxonomy WHERE slug = ? AND taxonomy = 'category'");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO term_taxonomy (name, slug, taxonomy) VALUES (?, ?, 'category')");
            $stmt->execute([$cat, $slug]);
        }
    }

    echo "Banco de dados configurado com sucesso!\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
