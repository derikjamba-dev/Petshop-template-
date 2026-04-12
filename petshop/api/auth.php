<?php
// =====================================================
// PataVerde Pet Shop — api/auth.php
// Gerencia: login, cadastro, logout, perfil
// =====================================================

require_once __DIR__ . '/../config/db.php';

$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':    handleLogin($body);    break;
    case 'register': handleRegister($body); break;
    case 'logout':   handleLogout();        break;
    case 'me':       handleMe();            break;
    case 'update':   handleUpdate($body);   break;
    default:
        jsonResponse(['success' => false, 'message' => 'Ação inválida.'], 400);
}

// ─── LOGIN ──────────────────────────────────────────
function handleLogin(array $body): void {
    $email    = sanitize($body['email']    ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'E-mail e senha são obrigatórios.'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'E-mail inválido.'], 422);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, nome, sobrenome, email, senha, telefone, ativo FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['senha'])) {
        jsonResponse(['success' => false, 'message' => 'E-mail ou senha incorretos.'], 401);
    }

    if (!$user['ativo']) {
        jsonResponse(['success' => false, 'message' => 'Conta desativada. Entre em contato com o suporte.'], 403);
    }

    // Registrar login
    $db->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$user['id']]);

    $_SESSION['user_id'] = $user['id'];

    jsonResponse([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'user'    => [
            'id'       => $user['id'],
            'nome'     => $user['nome'],
            'sobrenome'=> $user['sobrenome'],
            'email'    => $user['email'],
            'telefone' => $user['telefone'],
        ],
    ]);
}

// ─── REGISTER ────────────────────────────────────────
function handleRegister(array $body): void {
    $nome           = sanitize($body['nome']             ?? '');
    $sobrenome      = sanitize($body['sobrenome']        ?? '');
    $email          = sanitize($body['email']            ?? '');
    $telefone       = sanitize($body['telefone']         ?? '');
    $password       = $body['password']         ?? '';
    $passwordConfirm= $body['password_confirm'] ?? '';

    // Validações
    $errors = [];
    if (!$nome)      $errors[] = 'Nome é obrigatório.';
    if (!$email)     $errors[] = 'E-mail é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
    if (strlen($password) < 8) $errors[] = 'Senha deve ter ao menos 8 caracteres.';
    if ($password !== $passwordConfirm) $errors[] = 'As senhas não coincidem.';

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
    }

    $db = getDB();

    // Verificar duplicidade
    $check = $db->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Este e-mail já está cadastrado.'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare(
        'INSERT INTO usuarios (nome, sobrenome, email, telefone, senha, criado_em)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$nome, $sobrenome, $email, $telefone, $hash]);
    $userId = (int) $db->lastInsertId();

    $_SESSION['user_id'] = $userId;

    jsonResponse([
        'success' => true,
        'message' => 'Conta criada com sucesso!',
        'user'    => [
            'id'       => $userId,
            'nome'     => $nome,
            'sobrenome'=> $sobrenome,
            'email'    => $email,
            'telefone' => $telefone,
        ],
    ], 201);
}

// ─── LOGOUT ──────────────────────────────────────────
function handleLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Sessão encerrada.']);
}

// ─── PERFIL ATUAL ────────────────────────────────────
function handleMe(): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Não autenticado.'], 401);
    }
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, nome, sobrenome, email, telefone, criado_em FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['success' => false, 'message' => 'Utilizador não encontrado.'], 404);
    jsonResponse(['success' => true, 'user' => $user]);
}

// ─── ATUALIZAR PERFIL ────────────────────────────────
function handleUpdate(array $body): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Não autenticado.'], 401);
    }
    $userId   = $_SESSION['user_id'];
    $nome     = sanitize($body['nome']      ?? '');
    $sobrenome= sanitize($body['sobrenome'] ?? '');
    $telefone = sanitize($body['telefone']  ?? '');

    $db   = getDB();
    $stmt = $db->prepare(
        'UPDATE usuarios SET nome = ?, sobrenome = ?, telefone = ?, atualizado_em = NOW() WHERE id = ?'
    );
    $stmt->execute([$nome, $sobrenome, $telefone, $userId]);
    jsonResponse(['success' => true, 'message' => 'Perfil atualizado com sucesso.']);
}
