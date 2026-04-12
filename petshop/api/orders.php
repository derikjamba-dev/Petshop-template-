<?php
// =====================================================
// PataVerde Pet Shop — api/orders.php
// POST   — criar pedido (checkout)
// GET    — listar pedidos do usuário / admin
// PUT    — atualizar status (admin)
// =====================================================

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'POST':   createOrder(getRequestBody()); break;
    case 'GET':    $id ? getOrder($id) : listOrders(); break;
    case 'PUT':    updateOrderStatus($id, getRequestBody()); break;
    default:
        jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
}

// ─── CRIAR PEDIDO ────────────────────────────────────
function createOrder(array $body): void {
    $required = ['nome', 'telefone', 'endereco', 'cidade', 'pagamento', 'items'];
    foreach ($required as $f) {
        if (empty($body[$f])) {
            jsonResponse(['success'=>false,'message'=>"Campo '$f' é obrigatório."], 422);
        }
    }

    $items = $body['items'];
    if (!is_array($items) || count($items) === 0) {
        jsonResponse(['success' => false, 'message' => 'Carrinho vazio.'], 422);
    }

    $db = getDB();

    // Calcular totais e validar estoque
    $subtotal = 0;
    $itemsValidados = [];

    foreach ($items as $item) {
        $itemId = (int)($item['id'] ?? 0);
        $qty    = (int)($item['qty'] ?? 0);
        if ($itemId <= 0 || $qty <= 0) continue;

        $stmt = $db->prepare('SELECT id, nome, preco, estoque FROM produtos WHERE id = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([$itemId]);
        $produto = $stmt->fetch();

        if (!$produto) {
            jsonResponse(['success' => false, 'message' => "Produto ID $itemId não encontrado."], 404);
        }
        if ($produto['estoque'] < $qty) {
            jsonResponse(['success' => false, 'message' => "Estoque insuficiente para: {$produto['nome']}."], 409);
        }

        $subtotal += $produto['preco'] * $qty;
        $itemsValidados[] = ['produto' => $produto, 'qty' => $qty];
    }

    // Desconto por cupom
    $desconto    = 0;
    $couponCode  = sanitize($body['coupon'] ?? '');
    $coupons     = ['PATA15' => 15, 'PET10' => 10, 'WELCOME' => 20];
    if ($couponCode && isset($coupons[$couponCode])) {
        $desconto = round($subtotal * $coupons[$couponCode] / 100, 2);
    }

    $frete = 150.00;
    $total = $subtotal - $desconto + $frete;

    // Inserir pedido
    $stmt = $db->prepare(
        'INSERT INTO pedidos
            (usuario_id, nome_entrega, telefone, endereco, cidade, referencia,
             pagamento, mpesa_num, subtotal, desconto, frete, total,
             cupom, status, criado_em)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pendente", NOW())'
    );
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        sanitize($body['nome']),
        sanitize($body['telefone']),
        sanitize($body['endereco']),
        sanitize($body['cidade']),
        sanitize($body['referencia'] ?? ''),
        sanitize($body['pagamento']),
        sanitize($body['mpesa_num']  ?? ''),
        $subtotal,
        $desconto,
        $frete,
        $total,
        $couponCode ?: null,
    ]);
    $orderId = (int)$db->lastInsertId();

    // Inserir itens e deduzir estoque
    $stmtItem = $db->prepare(
        'INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, preco_unitario, quantidade, total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmtEstoque = $db->prepare(
        'UPDATE produtos SET estoque = estoque - ? WHERE id = ?'
    );

    foreach ($itemsValidados as $i) {
        $stmtItem->execute([
            $orderId,
            $i['produto']['id'],
            $i['produto']['nome'],
            $i['produto']['preco'],
            $i['qty'],
            $i['produto']['preco'] * $i['qty'],
        ]);
        $stmtEstoque->execute([$i['qty'], $i['produto']['id']]);
    }

    // Registrar uso do cupom
    if ($couponCode) {
        $db->prepare('INSERT INTO cupom_usos (cupom, pedido_id, usuario_id, usado_em) VALUES (?, ?, ?, NOW())')
           ->execute([$couponCode, $orderId, $_SESSION['user_id'] ?? null]);
    }

    jsonResponse([
        'success'  => true,
        'message'  => 'Pedido realizado com sucesso!',
        'order_id' => $orderId,
        'total'    => $total,
    ], 201);
}

// ─── LISTAR PEDIDOS ──────────────────────────────────
function listOrders(): void {
    $db = getDB();

    if (isAdmin()) {
        // Admin vê todos
        $stmt = $db->prepare(
            'SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email
             FROM pedidos p
             LEFT JOIN usuarios u ON u.id = p.usuario_id
             ORDER BY p.criado_em DESC
             LIMIT 100'
        );
        $stmt->execute();
    } elseif (!empty($_SESSION['user_id'])) {
        $stmt = $db->prepare(
            'SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 20'
        );
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Autenticação necessária.'], 401);
    }

    $orders = $stmt->fetchAll();
    jsonResponse(['success' => true, 'orders' => $orders]);
}

// ─── DETALHE DO PEDIDO ───────────────────────────────
function getOrder(int $id): void {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM pedidos WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) jsonResponse(['success' => false, 'message' => 'Pedido não encontrado.'], 404);

    // Verificar permissão
    if (!isAdmin() && ($order['usuario_id'] ?? null) !== ($_SESSION['user_id'] ?? null)) {
        jsonResponse(['success' => false, 'message' => 'Acesso negado.'], 403);
    }

    $stmtItems = $db->prepare('SELECT * FROM pedido_itens WHERE pedido_id = ?');
    $stmtItems->execute([$id]);
    $order['itens'] = $stmtItems->fetchAll();

    jsonResponse(['success' => true, 'order' => $order]);
}

// ─── ATUALIZAR STATUS (ADMIN) ────────────────────────
function updateOrderStatus(?int $id, array $body): void {
    if (!isAdmin()) jsonResponse(['success' => false, 'message' => 'Acesso negado.'], 403);
    if (!$id)       jsonResponse(['success' => false, 'message' => 'ID obrigatório.'], 400);

    $statusValidos = ['pendente', 'confirmado', 'em_preparo', 'a_caminho', 'entregue', 'cancelado'];
    $novoStatus    = $body['status'] ?? '';

    if (!in_array($novoStatus, $statusValidos)) {
        jsonResponse(['success' => false, 'message' => 'Status inválido.'], 422);
    }

    $db = getDB();
    $db->prepare('UPDATE pedidos SET status = ?, atualizado_em = NOW() WHERE id = ?')
       ->execute([$novoStatus, $id]);

    jsonResponse(['success' => true, 'message' => 'Status do pedido atualizado.']);
}

// ─── HELPERS ─────────────────────────────────────────
function isAdmin(): bool {
    if (empty($_SESSION['user_id'])) return false;
    $stmt = getDB()->prepare('SELECT admin FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $u && (bool)$u['admin'];
}
