<?php
// =====================================================
// PataVerde Pet Shop — api/products.php
// GET  /api/products.php          — listar produtos
// GET  /api/products.php?id=X     — detalhe do produto
// POST /api/products.php          — criar produto (admin)
// PUT  /api/products.php?id=X     — editar produto (admin)
// DELETE /api/products.php?id=X   — remover produto (admin)
// =====================================================

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        $id ? getProduct($id) : listProducts();
        break;
    case 'POST':
        requireAdmin();
        createProduct(getRequestBody());
        break;
    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID obrigatório.'], 400);
        updateProduct($id, getRequestBody());
        break;
    case 'DELETE':
        requireAdmin();
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID obrigatório.'], 400);
        deleteProduct($id);
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
}

// ─── LISTAR ──────────────────────────────────────────
function listProducts(): void {
    $db = getDB();
    $where = '1=1';
    $params = [];

    if (!empty($_GET['categoria'])) {
        $where .= ' AND categoria = ?';
        $params[] = sanitize($_GET['categoria']);
    }
    if (!empty($_GET['q'])) {
        $where .= ' AND (nome LIKE ? OR descricao LIKE ?)';
        $q = '%' . sanitize($_GET['q']) . '%';
        $params[] = $q;
        $params[] = $q;
    }
    if (!empty($_GET['min_preco'])) {
        $where .= ' AND preco >= ?';
        $params[] = (float)$_GET['min_preco'];
    }
    if (!empty($_GET['max_preco'])) {
        $where .= ' AND preco <= ?';
        $params[] = (float)$_GET['max_preco'];
    }

    $order  = in_array($_GET['order'] ?? '', ['preco_asc','preco_desc','nome','novo']) ? $_GET['order'] : 'destaque';
    $orderSQL = match($order) {
        'preco_asc'  => 'preco ASC',
        'preco_desc' => 'preco DESC',
        'nome'       => 'nome ASC',
        'novo'       => 'criado_em DESC',
        default      => 'destaque DESC, criado_em DESC',
    };

    $limit  = min((int)($_GET['limit']  ?? 50), 100);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $stmt = $db->prepare(
        "SELECT p.*, c.nome AS categoria_nome,
                COALESCE(AVG(r.nota), 0) AS avaliacao,
                COUNT(r.id) AS total_avaliacoes
         FROM produtos p
         LEFT JOIN categorias c ON c.slug = p.categoria
         LEFT JOIN reviews r ON r.produto_id = p.id
         WHERE $where AND p.ativo = 1
         GROUP BY p.id
         ORDER BY $orderSQL
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM produtos WHERE $where AND ativo = 1");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    jsonResponse([
        'success'  => true,
        'total'    => $total,
        'limit'    => $limit,
        'offset'   => $offset,
        'products' => $products,
    ]);
}

// ─── DETALHE ─────────────────────────────────────────
function getProduct(int $id): void {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT p.*, c.nome AS categoria_nome,
                COALESCE(AVG(r.nota), 0) AS avaliacao,
                COUNT(r.id) AS total_avaliacoes
         FROM produtos p
         LEFT JOIN categorias c ON c.slug = p.categoria
         LEFT JOIN reviews r ON r.produto_id = p.id
         WHERE p.id = ? AND p.ativo = 1
         GROUP BY p.id'
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) jsonResponse(['success' => false, 'message' => 'Produto não encontrado.'], 404);

    // Reviews
    $revStmt = $db->prepare(
        'SELECT r.nota, r.comentario, r.criado_em, u.nome AS usuario
         FROM reviews r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.produto_id = ?
         ORDER BY r.criado_em DESC LIMIT 10'
    );
    $revStmt->execute([$id]);
    $product['reviews'] = $revStmt->fetchAll();

    jsonResponse(['success' => true, 'product' => $product]);
}

// ─── CRIAR (ADMIN) ───────────────────────────────────
function createProduct(array $body): void {
    $fields = ['nome','descricao','preco','categoria','estoque'];
    foreach ($fields as $f) {
        if (empty($body[$f])) jsonResponse(['success'=>false,'message'=>"Campo '$f' obrigatório."], 422);
    }

    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO produtos (nome, descricao, preco, preco_anterior, categoria, estoque, emoji, badge, destaque, ativo, criado_em)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())'
    );
    $stmt->execute([
        sanitize($body['nome']),
        sanitize($body['descricao']),
        (float)$body['preco'],
        isset($body['preco_anterior']) ? (float)$body['preco_anterior'] : null,
        sanitize($body['categoria']),
        (int)$body['estoque'],
        sanitize($body['emoji'] ?? '📦'),
        sanitize($body['badge'] ?? ''),
        (int)($body['destaque'] ?? 0),
    ]);
    $newId = (int)$db->lastInsertId();
    jsonResponse(['success' => true, 'message' => 'Produto criado.', 'id' => $newId], 201);
}

// ─── EDITAR (ADMIN) ──────────────────────────────────
function updateProduct(int $id, array $body): void {
    $db   = getDB();
    $stmt = $db->prepare(
        'UPDATE produtos
         SET nome=?, descricao=?, preco=?, preco_anterior=?, categoria=?,
             estoque=?, emoji=?, badge=?, destaque=?, atualizado_em=NOW()
         WHERE id=?'
    );
    $stmt->execute([
        sanitize($body['nome']        ?? ''),
        sanitize($body['descricao']   ?? ''),
        (float)($body['preco']         ?? 0),
        isset($body['preco_anterior']) ? (float)$body['preco_anterior'] : null,
        sanitize($body['categoria']   ?? ''),
        (int)($body['estoque']         ?? 0),
        sanitize($body['emoji']        ?? '📦'),
        sanitize($body['badge']        ?? ''),
        (int)($body['destaque']        ?? 0),
        $id,
    ]);
    jsonResponse(['success' => true, 'message' => 'Produto atualizado.']);
}

// ─── REMOVER (ADMIN) ─────────────────────────────────
function deleteProduct(int $id): void {
    $db = getDB();
    $db->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Produto removido.']);
}

// ─── VERIFICAR ADMIN ─────────────────────────────────
function requireAdmin(): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Autenticação necessária.'], 401);
    }
    $db   = getDB();
    $stmt = $db->prepare('SELECT admin FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !$user['admin']) {
        jsonResponse(['success' => false, 'message' => 'Acesso negado.'], 403);
    }
}
