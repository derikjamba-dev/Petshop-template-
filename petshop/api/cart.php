<?php
// =====================================================
// PataVerde Pet Shop — api/cart.php
// Carrinho server-side (para usuários logados)
// =====================================================

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':    getCart();              break;
    case 'add':    addItem($body);         break;
    case 'remove': removeItem($body);      break;
    case 'update': updateItem($body);      break;
    case 'clear':  clearCart();            break;
    case 'sync':   syncCart($body);        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida.'], 400);
}

function requireAuth(): int {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Autenticação necessária.'], 401);
    }
    return (int)$_SESSION['user_id'];
}

function getCart(): void {
    $userId = requireAuth();
    $db     = getDB();
    $stmt   = $db->prepare(
        'SELECT c.id, c.produto_id, c.quantidade,
                p.nome, p.preco, p.emoji, p.estoque
         FROM carrinho c
         JOIN produtos p ON p.id = c.produto_id
         WHERE c.usuario_id = ? AND p.ativo = 1'
    );
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    $total = array_sum(array_map(fn($i) => $i['preco'] * $i['quantidade'], $items));
    jsonResponse(['success' => true, 'items' => $items, 'total' => $total]);
}

function addItem(array $body): void {
    $userId    = requireAuth();
    $produtoId = (int)($body['produto_id'] ?? 0);
    $qty       = max(1, (int)($body['quantidade'] ?? 1));

    if (!$produtoId) jsonResponse(['success' => false, 'message' => 'produto_id obrigatório.'], 422);

    $db   = getDB();
    $stmt = $db->prepare('SELECT estoque FROM produtos WHERE id = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$produtoId]);
    $prod = $stmt->fetch();
    if (!$prod) jsonResponse(['success' => false, 'message' => 'Produto não encontrado.'], 404);
    if ($prod['estoque'] < $qty) jsonResponse(['success' => false, 'message' => 'Estoque insuficiente.'], 409);

    // Upsert
    $upsert = $db->prepare(
        'INSERT INTO carrinho (usuario_id, produto_id, quantidade)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)'
    );
    $upsert->execute([$userId, $produtoId, $qty]);
    jsonResponse(['success' => true, 'message' => 'Item adicionado ao carrinho.']);
}

function removeItem(array $body): void {
    $userId    = requireAuth();
    $produtoId = (int)($body['produto_id'] ?? 0);
    $db        = getDB();
    $db->prepare('DELETE FROM carrinho WHERE usuario_id = ? AND produto_id = ?')
       ->execute([$userId, $produtoId]);
    jsonResponse(['success' => true, 'message' => 'Item removido.']);
}

function updateItem(array $body): void {
    $userId    = requireAuth();
    $produtoId = (int)($body['produto_id']  ?? 0);
    $qty       = max(1, (int)($body['quantidade'] ?? 1));
    $db        = getDB();
    $db->prepare('UPDATE carrinho SET quantidade = ? WHERE usuario_id = ? AND produto_id = ?')
       ->execute([$qty, $userId, $produtoId]);
    jsonResponse(['success' => true, 'message' => 'Quantidade atualizada.']);
}

function clearCart(): void {
    $userId = requireAuth();
    getDB()->prepare('DELETE FROM carrinho WHERE usuario_id = ?')->execute([$userId]);
    jsonResponse(['success' => true, 'message' => 'Carrinho limpo.']);
}

// Sincronizar carrinho do localStorage com o banco
function syncCart(array $body): void {
    $userId = requireAuth();
    $items  = $body['items'] ?? [];
    if (!is_array($items)) jsonResponse(['success' => false, 'message' => 'items inválidos.'], 422);

    $db = getDB();
    $db->prepare('DELETE FROM carrinho WHERE usuario_id = ?')->execute([$userId]);

    $stmt = $db->prepare(
        'INSERT INTO carrinho (usuario_id, produto_id, quantidade) VALUES (?, ?, ?)'
    );
    foreach ($items as $item) {
        $pid = (int)($item['id'] ?? 0);
        $qty = max(1, (int)($item['qty'] ?? 1));
        if ($pid > 0) $stmt->execute([$userId, $pid, $qty]);
    }
    jsonResponse(['success' => true, 'message' => 'Carrinho sincronizado.']);
}
