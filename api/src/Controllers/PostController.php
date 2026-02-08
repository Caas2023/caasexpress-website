<?php
namespace Src\Controllers;

use Src\Config\Database;
use Src\Utils\Response;
use Src\Utils\Auth;
use PDO;

class PostController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // GET /wp-json/wp/v2/posts
    public function index() {
        try {
            // Paginação e filtros básicos
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            $offset = ($page - 1) * $perPage;
            
            $type = $_GET['type'] ?? null;
            $whereType = "";
            $params = [];

            if ($type) {
                $whereType = "AND p.type = :type";
                $params[':type'] = $type;
            }

            $query = "SELECT p.*, u.display_name as author_name, m.file_path as featured_media_url 
                      FROM posts p 
                      LEFT JOIN users u ON p.author_id = u.id
                      LEFT JOIN media m ON p.featured_media = m.id
                      WHERE (p.status = 'publish' OR p.status = 'draft') $whereType
                      ORDER BY p.created_at DESC 
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            if ($type) {
                $stmt->bindValue(':type', $type);
            }
            $stmt->execute();
            
            $posts = $stmt->fetchAll();

            // Formatar resposta estilo WP
            $formatted = array_map(function($post) {
                $formattedPost = $this->formatPost($post);
                
                // Contar links de entrada (outros posts que linkam para este)
                $slug = $post['slug'] ?? '';
                if ($slug) {
                    // Buscar posts que contêm o slug deste post em um link
                    $inboundStmt = $this->pdo->prepare("SELECT COUNT(*) FROM posts WHERE content LIKE ? AND id != ?");
                    $inboundStmt->execute(["%caasexpresss.com/$slug%", $post['id']]);
                    $formattedPost['inbound_links'] = (int) $inboundStmt->fetchColumn();
                } else {
                    $formattedPost['inbound_links'] = 0;
                }
                
                // Contar links de saída (links para outros posts do site)
                $content = $post['content'] ?? '';
                // Buscar todos os links internos (caasexpresss.com/qualquer-slug)
                preg_match_all('/href=["\']https?:\/\/caasexpresss\.com\/([a-z0-9\-]+)\/?["\']/', $content, $matches);
                // Remover links para páginas fixas
                $excludePages = ['contato', 'sobre-nos', 'blog', 'servicos', 'home', ''];
                $postLinks = array_filter($matches[1] ?? [], fn($s) => !in_array($s, $excludePages));
                $formattedPost['outbound_links'] = count(array_unique($postLinks));
                
                return $formattedPost;
            }, $posts);

            Response::json($formatted);

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    // GET /wp-json/wp/v2/posts/:id
    public function show($id) {
        $stmt = $this->pdo->prepare("SELECT p.*, m.file_path as featured_media_url 
                                     FROM posts p 
                                     LEFT JOIN media m ON p.featured_media = m.id 
                                     WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();

        if (!$post) {
            Response::error('Post not found', 404);
        }

        Response::json($this->formatPost($post));
    }

    // Helper: Salvar Meta
    private function savePostMeta($postId, $meta) {
        if (!is_array($meta)) return;
        
        foreach ($meta as $key => $value) {
            // Verificar se existe
            $stmt = $this->pdo->prepare("SELECT id FROM postmeta WHERE post_id = :pid AND meta_key = :key");
            $stmt->execute([':pid' => $postId, ':key' => $key]);
            
            if ($stmt->fetch()) {
                $upd = $this->pdo->prepare("UPDATE postmeta SET meta_value = :val WHERE post_id = :pid AND meta_key = :key");
                $upd->execute([':val' => $value, ':pid' => $postId, ':key' => $key]);
            } else {
                $ins = $this->pdo->prepare("INSERT INTO postmeta (post_id, meta_key, meta_value) VALUES (:pid, :key, :val)");
                $ins->execute([':pid' => $postId, ':key' => $key, ':val' => $value]);
            }
        }
    }

    // POST /wp-json/wp/v2/posts
    public function create() {
        $user = Auth::check(); // Exige login
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) Response::error('Title is required');

        try {
            $slug = $this->generateSlug($data['title']);
            $stmt = $this->pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, status, type, author_id) 
                                         VALUES (:title, :slug, :content, :excerpt, :status, :type, :author_id)");
            $stmt->execute([
                ':title' => $data['title'],
                ':slug' => $slug,
                ':content' => $data['content'] ?? '',
                ':excerpt' => $data['excerpt'] ?? '',
                ':status' => $data['status'] ?? 'draft',
                ':type' => $data['type'] ?? 'post',
                ':author_id' => $user['id']
            ]);
            
            $id = $this->pdo->lastInsertId();
            
            // Salvar Meta
            if (isset($data['meta'])) {
                $this->savePostMeta($id, $data['meta']);
            }
            
            $this->show($id);

        } catch (\Exception $e) {
            Response::error('Erro ao criar post: ' . $e->getMessage(), 500);
        }
    }

    public function update($id) {
        Auth::check();
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $fields = [];
            $params = [':id' => $id];

            if (isset($data['title'])) { $fields[] = 'title = :title'; $params[':title'] = $data['title']; }
            if (isset($data['content'])) { $fields[] = 'content = :content'; $params[':content'] = $data['content']; }
            if (isset($data['status'])) { $fields[] = 'status = :status'; $params[':status'] = $data['status']; }
            
            $fields[] = "updated_at = datetime('now', 'localtime')";

            if (!empty($fields)) {
                $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            // Salvar Meta
            if (isset($data['meta'])) {
                $this->savePostMeta($id, $data['meta']);
            }

            $this->show($id);

        } catch (\Exception $e) {
            Response::error('Update failed: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id) {
        Auth::check();
        $stmt = $this->pdo->prepare("UPDATE posts SET status = 'trash' WHERE id = :id"); // Soft delete
        //$stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = :id"); // Hard delete
        $stmt->execute([':id' => $id]);
        Response::json(['id' => $id, 'status' => 'trash']);
    }

    private function formatPost($post) {
        return [
            'id' => $post['id'],
            'date' => $post['created_at'],
            'date_gmt' => $post['created_at'],
            'guid' => ['rendered' => $post['slug']], // Simplificado
            'modified' => $post['updated_at'],
            'modified_gmt' => $post['updated_at'],
            'slug' => $post['slug'],
            'status' => $post['status'],
            'type' => $post['type'],
            'link' => '/post?slug=' . $post['slug'],
            'title' => ['rendered' => $post['title']],
            'content' => ['rendered' => $post['content'], 'protected' => false],
            'excerpt' => ['rendered' => $post['excerpt'], 'protected' => false],
            'author' => (int)$post['author_id'],
            'featured_media' => (int)$post['featured_media'],
            'featured_media_url' => $post['featured_media_url'] ?? null, // Campo customizado para facilitar frontend
            'categories' => [], // Implementar dps
            'tags' => [],
            'meta' => $this->getPostMeta($post['id']) 
        ];
    }
    
    // Helper: Buscar Meta do Post
    private function getPostMeta($postId) {
        $stmt = $this->pdo->prepare("SELECT meta_key, meta_value FROM postmeta WHERE post_id = ?");
        $stmt->execute([$postId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $meta = [];
        foreach ($rows as $row) {
            $key = $row['meta_key'];
            $value = $row['meta_value'];
            
            // Mapear chaves Yoast para nomes simples
            if ($key === '_yoast_wpseo_title') $meta['seo_title'] = $value;
            elseif ($key === '_yoast_wpseo_metadesc') $meta['seo_desc'] = $value;
            elseif ($key === '_yoast_wpseo_focuskw') $meta['seo_keyword'] = $value;
            elseif ($key === '_ai_tags') $meta['ai_tags'] = $value;
            elseif ($key === '_auto_link_keywords') $meta['auto_link_keywords'] = $value;
        }
        
        return $meta;
    }
    
    private function generateSlug($text) {
        // Lógica simples de slug
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        // Adicionar timestamp se existir (evitar duplicatas simples)
        // Em um sistema real verificaria no DB
        return $text . '-' . time();
    }
}
