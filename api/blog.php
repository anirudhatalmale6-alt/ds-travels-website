<?php
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';
$slug = $_GET['slug'] ?? '';

// Public GET — return posts
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = readJSON(BLOG_JSON) ?? ['posts' => []];

    if ($slug) {
        // Single post by slug
        foreach ($data['posts'] as $post) {
            if ($post['slug'] === $slug && ($post['published'] ?? true)) {
                echo json_encode($post);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    // Check if admin wants all posts (including drafts)
    $showAll = isset($_GET['all']) && checkAuth();
    $posts = [];
    foreach ($data['posts'] as $post) {
        if ($showAll || ($post['published'] ?? true)) {
            $posts[] = $post;
        }
    }
    // Sort by date descending
    usort($posts, function($a, $b) {
        return strtotime($b['date'] ?? '2000-01-01') - strtotime($a['date'] ?? '2000-01-01');
    });
    echo json_encode($posts);
    exit;
}

// POST actions require auth
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = readJSON(BLOG_JSON) ?? ['posts' => []];
$input = getInput();

switch ($action) {
    case 'save':
        $id = $input['id'] ?? 'post_' . time();
        $title = trim($input['title'] ?? '');
        if (!$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Title required']);
            exit;
        }

        $slug = $input['slug'] ?? preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $slug = trim($slug, '-');

        $post = [
            'id' => $id,
            'slug' => $slug,
            'title' => $title,
            'content' => $input['content'] ?? '',
            'excerpt' => $input['excerpt'] ?? '',
            'date' => $input['date'] ?? date('Y-m-d'),
            'author' => $input['author'] ?? 'D S Travels',
            'metaTitle' => $input['metaTitle'] ?? $title . ' | D S Travels Blog',
            'metaDescription' => $input['metaDescription'] ?? ($input['excerpt'] ?? ''),
            'published' => $input['published'] ?? true
        ];

        // Update existing or add new
        $found = false;
        foreach ($data['posts'] as &$existing) {
            if ($existing['id'] === $id) {
                $existing = $post;
                $found = true;
                break;
            }
        }
        if (!$found) $data['posts'][] = $post;

        writeJSON(BLOG_JSON, $data);
        echo json_encode(['success' => true, 'post' => $post]);
        break;

    case 'delete':
        $id = $input['id'] ?? $_GET['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID required']);
            exit;
        }
        $data['posts'] = array_values(array_filter($data['posts'], function($p) use ($id) {
            return $p['id'] !== $id;
        }));
        writeJSON(BLOG_JSON, $data);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
