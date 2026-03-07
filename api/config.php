<?php
session_start();
header('Content-Type: application/json');

define('ADMIN_PASS_HASH', password_hash('DSTravels@2026', PASSWORD_BCRYPT));
define('DATA_DIR', __DIR__ . '/../data/');
define('SITE_JSON', DATA_DIR . 'site.json');
define('BLOG_JSON', DATA_DIR . 'blog.json');
define('SESSION_TIMEOUT', 7200); // 2 hours

function checkAuth() {
    if (empty($_SESSION['admin']) || empty($_SESSION['last_activity'])) return false;
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireAuth() {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function readJSON($file) {
    if (!file_exists($file)) return null;
    $fp = fopen($file, 'r');
    flock($fp, LOCK_SH);
    $data = json_decode(file_get_contents($file), true);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $data;
}

function writeJSON($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fp = fopen($file, 'c');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
}

function getInput() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
