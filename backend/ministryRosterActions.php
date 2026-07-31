<?php
// ministryRosterActions.php — manages ministry_members (who belongs
// to a ministry, and in what role). Kept separate from
// ministryActions.php on purpose: a ministry's name/description and
// its roster have different lifecycles — someone joins or leaves a
// ministry independently of the ministry itself being edited, the
// same way vestry_hours or attendance would be their own thing rather
// than folded into whatever they're logged against.
//
//   POST with no ministry_member_id                     -> add a member
//   POST with ministry_member_id                        -> change their role
//   POST with ministry_member_id + _method=DELETE        -> remove them
//
// No GET branch — the roster is already embedded in
// fetch_ministry_detail() (used by the View modal), so there's no
// need for a second way to fetch it.

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/ministriesData.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

// =========================================================================
// BRANCH 1 — POST + _method=DELETE: remove a roster entry
// =========================================================================
if (($_POST['_method'] ?? '') === 'DELETE') {
    $ministryMemberId = trim($_POST['ministry_member_id'] ?? '');
    $minId = trim($_POST['min_id'] ?? '');

    if ($ministryMemberId === '' || $minId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing roster reference.']]);
        exit();
    }

    $deleteResult = supabase_rest('DELETE', 'ministry_members', ['ministry_member_id' => 'eq.' . $ministryMemberId]);

    if (!$deleteResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not remove this person. Please try again.']]);
        exit();
    }

    $ministry = fetch_ministry_detail($minId);
    echo json_encode(['success' => true, 'ministry' => $ministry]);
    exit();
}

// =========================================================================
// BRANCH 2 / 3 — POST: add a new roster entry, or change an existing
// entry's role (ministry_member_id present = change role)
// =========================================================================
$minId = trim($_POST['min_id'] ?? '');
$memId = trim($_POST['mem_id'] ?? '');
$roleId = trim($_POST['role_id'] ?? '');
$ministryMemberId = trim($_POST['ministry_member_id'] ?? '');

if ($minId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Missing min_id.']]);
    exit();
}
if ($roleId === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Please select a role.']]);
    exit();
}

if ($ministryMemberId !== '') {
    // ---- Change role on an existing roster entry -----------------------
    $updateResult = supabase_rest('PATCH', 'ministry_members', ['ministry_member_id' => 'eq.' . $ministryMemberId], [
        'role_id' => $roleId,
    ]);

    if (!$updateResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not update their role. Please try again.']]);
        exit();
    }
} else {
    // ---- Add a new roster entry -----------------------------------------
    if ($memId === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => ['Please select a person to add.']]);
        exit();
    }

    $insertResult = supabase_rest('POST', 'ministry_members', [], [
        'min_id'  => $minId,
        'mem_id'  => $memId,
        'role_id' => $roleId,
    ]);

    if (!$insertResult['ok']) {
        // ministry_members has a UNIQUE(mem_id, min_id) constraint —
        // give a friendly message for that specific case rather than
        // a generic failure.
        $isDuplicate = ($insertResult['data']['code'] ?? '') === '23505';
        http_response_code($isDuplicate ? 409 : 500);
        echo json_encode(['success' => false, 'errors' => [
            $isDuplicate ? 'This person is already in this ministry.' : 'Could not add this person. Please try again.',
        ]]);
        exit();
    }
}

$ministry = fetch_ministry_detail($minId);
echo json_encode(['success' => true, 'ministry' => $ministry]);
