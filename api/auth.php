<?php
require_once __DIR__ . '/../config.php';

if (!headers_sent()) {
    @header('Content-Type: application/json; charset=utf-8');
}

$action = $_GET['action'] ?? '';

if ($action === 'status') {
    $token = getRequestToken();
    echo json_encode([
        'authRequired' => isAuthRequired(),
        'isAuthenticated' => !isAuthRequired() || validateToken($token)
    ]);
    exit;
}

if ($action === 'login' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $password = $input['password'] ?? ($_POST['password'] ?? '');

    if (!isAuthRequired()) {
        echo json_encode(['success' => true, 'token' => 'no-auth-required']);
        exit;
    }

    if (verifyPassword($password)) {
        $token = generateToken();
        setcookie('term_token', $token, [
            'expires' => time() + 7 * 86400,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
        echo json_encode(['success' => true, 'token' => $token]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid auth request']);
