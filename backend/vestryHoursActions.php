<?php
// vestryHoursActions.php — endpoint for the Vestry Hours tab.
//
//   POST with no vestry_id                    -> log new hours
//   POST with vestry_id                       -> update an entry
//   POST with vestry_id + _method=DELETE       -> delete an entry
//
// This is self-service by design (per RBAC): a Pastor/Clergy account
// can only insert/update rows where mem_id matches their own linked
// member (current_user_mem_id()), enforced by RLS, not this file —
// someone submitting hours "for" another minister will simply get
// rejected by Postgres. Admin can manage anyone's.
//
// Also enforced by Postgres, not here: the check_is_minister trigger
// rejects any mem_id that doesn't hold the 'Minister' ministry role.
// The picker only offers qualifying people, but the trigger is the
// real backstop if that ever gets bypassed.

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

/** Translates the check_is_minister trigger's raised exception into a friendly message, if that's what failed. */
function friendly_vestry_error(array $result): string
{
    $message = $result['data']['message'] ?? '';
    if (str_contains($message, 'not recorded as a Minister')) {
        return 'This person isn\'t recorded as a Minister in any ministry yet. Add them via the Ministries page (roster role = Minister) first.';
    }
    return 'Could not save vestry hours. Please try again.';
}

// =========================================================================
// BRANCH 1 — POST + _method=DELETE
// =========================================================================
if (($_POST['_method'] ?? '') === 'DELETE') {
    $vestryId = trim($_POST['vestry_id'] ?? '');

    if ($vestryId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing vestry_id.']]);
        exit();
    }

    $deleteResult = supabase_rest('DELETE', 'vestry_hours', ['vestry_id' => 'eq.' . $vestryId]);

    if (!$deleteResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not delete this entry. Please try again.']]);
        exit();
    }

    echo json_encode(['success' => true, 'vestry_id' => $vestryId]);
    exit();
}

// =========================================================================
// BRANCH 2 / 3 — POST: create (no vestry_id) or update (vestry_id present)
// =========================================================================
$vestryId = trim($_POST['vestry_id'] ?? '');
$memId = trim($_POST['mem_id'] ?? '');
$date = trim($_POST['date'] ?? '');
$hoursRaw = trim($_POST['hours_logged'] ?? '');
$duties = trim($_POST['duties'] ?? '');

$errors = [];
if ($memId === '') $errors[] = 'Please select a minister.';
if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Please enter a valid date.';
if ($hoursRaw === '' || !is_numeric($hoursRaw) || (float) $hoursRaw <= 0) {
    $errors[] = 'Please enter a valid number of hours (greater than 0).';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

$hoursLogged = round((float) $hoursRaw, 2);

if ($vestryId !== '') {
    $result = supabase_rest('PATCH', 'vestry_hours', ['vestry_id' => 'eq.' . $vestryId], [
        'mem_id'       => $memId,
        'date'         => $date,
        'hours_logged' => $hoursLogged,
        'duties'       => $duties ?: null,
    ]);
} else {
    $result = supabase_rest('POST', 'vestry_hours', [], [
        'mem_id'       => $memId,
        'date'         => $date,
        'hours_logged' => $hoursLogged,
        'duties'       => $duties ?: null,
        'recorded_by'  => $_SESSION['user_uid'] ?? null,
    ]);
}

if (!$result['ok'] || empty($result['data'][0])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => [friendly_vestry_error($result)]]);
    exit();
}

// Re-fetch with the member name embedded so the row can render
// without needing a second lookup on the frontend.
$row = $result['data'][0];
$detailResult = supabase_rest('GET', 'vestry_hours', [
    'select'    => '*,members(mem_id,first_name,last_name)',
    'vestry_id' => 'eq.' . $row['vestry_id'],
]);
$vestryHours = ($detailResult['ok'] && !empty($detailResult['data'][0])) ? $detailResult['data'][0] : $row;

echo json_encode(['success' => true, 'vestry_hours' => $vestryHours]);
