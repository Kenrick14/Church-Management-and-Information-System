<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/ministriesData.php';

header('Content-Type: application/json');

function validate_ministry_post(array $post): array
{
    $errors = [];

    $values = [
        'name'        => trim($post['name'] ?? ''),
        'description' => trim($post['description'] ?? ''),
    ];

    if ($values['name'] === '') {
        $errors[] = 'Please enter a ministry name.';
    } elseif (mb_strlen($values['name']) > 100) {
        $errors[] = 'Ministry name must be 100 characters or fewer.';
    }

    return ['errors' => $errors, 'values' => $values];
}

$method = $_SERVER['REQUEST_METHOD'];

// BRANCH 1 — GET: fetch one ministry's detail (View + Edit modals)
if ($method === 'GET') {
    $minId = trim($_GET['min_id'] ?? '');

    if ($minId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing min_id.']]);
        exit();
    }

    $ministry = fetch_ministry_detail($minId);

    if ($ministry === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'errors' => ['Ministry not found.']]);
        exit();
    }

    echo json_encode(['success' => true, 'ministry' => $ministry]);
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

// BRANCH 2 — POST + _method=DEACTIVATE / ACTIVATE: soft toggle
$method_override = $_POST['_method'] ?? '';

if ($method_override === 'DEACTIVATE' || $method_override === 'ACTIVATE') {
    $minId = trim($_POST['min_id'] ?? '');

    if ($minId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing min_id.']]);
        exit();
    }

    $toggleResult = supabase_rest('PATCH', 'ministries', ['min_id' => 'eq.' . $minId], [
        'is_active' => $method_override === 'ACTIVATE',
    ]);

    if (!$toggleResult['ok'] || empty($toggleResult['data'][0])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not update ministry status. Please try again.']]);
        exit();
    }

    $ministry = fetch_ministry_detail($minId);
    echo json_encode(['success' => true, 'ministry' => $ministry]);
    exit();
}

// BRANCH 3 / 4 — POST: create (no min_id) or update (min_id present)
$minId = trim($_POST['min_id'] ?? '');
$isUpdate = $minId !== '';

$validation = validate_ministry_post($_POST);
$v = $validation['values'];

if (!empty($validation['errors'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $validation['errors']]);
    exit();
}

if ($isUpdate) {
    $ministryResult = supabase_rest('PATCH', 'ministries', ['min_id' => 'eq.' . $minId], [
        'name'        => $v['name'],
        'description' => $v['description'] ?: null,
    ]);

    if (!$ministryResult['ok'] || empty($ministryResult['data'][0])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not update ministry. Please try again.']]);
        exit();
    }

    $ministry = fetch_ministry_detail($minId);
} else {
    $ministryResult = supabase_rest('POST', 'ministries', [], [
        'name'        => $v['name'],
        'description' => $v['description'] ?: null,
    ]);

    if (!$ministryResult['ok'] || empty($ministryResult['data'][0]['min_id'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not save ministry. Please try again.']]);
        exit();
    }

    $ministry = fetch_ministry_detail($ministryResult['data'][0]['min_id']);
}

echo json_encode(['success' => true, 'ministry' => $ministry]);
