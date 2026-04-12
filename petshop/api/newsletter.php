<?php
// =====================================================
// PataVerde Pet Shop — api/newsletter.php
// =====================================================

require_once __DIR__ . '/../config/db.php';

$body  = getRequestBody();
$email = sanitize($body['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'E-mail inválido.'], 422);
}

$db    = getDB();
$check = $db->prepare('SELECT id FROM newsletter WHERE email = ? LIMIT 1');
$check->execute([$email]);

if ($check->fetch()) {
    jsonResponse(['success' => true, 'message' => 'E-mail já cadastrado na newsletter.']);
}

$db->prepare('INSERT INTO newsletter (email, inscrito_em) VALUES (?, NOW())')->execute([$email]);
jsonResponse(['success' => true, 'message' => 'Inscrito com sucesso!'], 201);
