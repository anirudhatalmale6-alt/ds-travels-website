<?php
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

// Public GET — returns all site content
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === '') {
    $data = readJSON(SITE_JSON);
    if (!$data) {
        echo json_encode(['error' => 'No data']);
        exit;
    }
    echo json_encode($data);
    exit;
}

// All POST actions require auth
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = readJSON(SITE_JSON);
if (!$data) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot read site data']);
    exit;
}

$input = getInput();

switch ($action) {
    case 'sections':
        if (isset($input['sections']) && is_array($input['sections'])) {
            foreach ($input['sections'] as $key => $val) {
                if (isset($data['sections'][$key])) {
                    $data['sections'][$key]['visible'] = (bool)$val;
                }
            }
            writeJSON(SITE_JSON, $data);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid sections data']);
        }
        break;

    case 'offers':
        if (isset($input['offers']) && is_array($input['offers'])) {
            $data['offers'] = $input['offers'];
            writeJSON(SITE_JSON, $data);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid offers data']);
        }
        break;

    case 'testimonials':
        if (isset($input['testimonials']) && is_array($input['testimonials'])) {
            $data['testimonials'] = $input['testimonials'];
            writeJSON(SITE_JSON, $data);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid testimonials data']);
        }
        break;

    case 'about':
        if (isset($input['about']) && is_array($input['about'])) {
            $data['about'] = array_merge($data['about'] ?? [], $input['about']);
            writeJSON(SITE_JSON, $data);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid about data']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
