<?php
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    if (!isset($_SESSION['lockout_until'])) $_SESSION['lockout_until'] = 0;

    if (time() < $_SESSION['lockout_until']) {
        $wait = $_SESSION['lockout_until'] - time();
        echo json_encode(['error' => "Too many attempts. Try again in {$wait} seconds."]);
        exit;
    }

    $input = getInput();
    $password = $input['password'] ?? '';

    if (password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['admin'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_attempts'] = 0;
        echo json_encode(['success' => true]);
    } else {
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['lockout_until'] = time() + 900; // 15 min lockout
            $_SESSION['login_attempts'] = 0;
        }
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
    }
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
} elseif ($action === 'check') {
    echo json_encode(['authenticated' => checkAuth()]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
