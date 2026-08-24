<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

// BRANCH 1 — POST + _method=DELETE: un-record a headcount
if (($_POST['_method'] ?? '') === 'DELETE') {
    $headcountId = trim($_POST['headcount_id'] ?? '');

    if ($headcountId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing headcount_id.']]);
        exit();
    }

    $deleteResult = supabase_rest('DELETE', 'headcount_attendance', ['headcount_id' => 'eq.' . $headcountId]);

    if (!$deleteResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not remove this attendance record. Please try again.']]);
        exit();
    }

    echo json_encode(['success' => true, 'headcount_id' => $headcountId]);
    exit();
}

// BRANCH 2 / 3 — POST: record (no headcount_id) or update (headcount_id present)
$eventId = trim($_POST['event_id'] ?? '');
$headcountId = trim($_POST['headcount_id'] ?? '');
$attendeeCountRaw = trim($_POST['attendee_count'] ?? '');

$errors = [];

if ($headcountId === '' && $eventId === '') {
    $errors[] = 'Missing event reference.';
}
if ($attendeeCountRaw === '' || !ctype_digit($attendeeCountRaw)) {
    $errors[] = 'Please enter a valid attendee count (0 or greater).';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

$attendeeCount = (int) $attendeeCountRaw;

if ($headcountId !== '') {
    // ---- Update an existing count ---------------------------------------
    $result = supabase_rest('PATCH', 'headcount_attendance', ['headcount_id' => 'eq.' . $headcountId], [
        'attendee_count' => $attendeeCount,
    ]);
} else {
    // ---- Record a new count ----------------------------------------------
    $result = supabase_rest('POST', 'headcount_attendance', [], [
        'event_id'       => $eventId,
        'attendee_count' => $attendeeCount,
        'recorded_by'    => $_SESSION['user_uid'] ?? null,
    ]);
}

if (!$result['ok'] || empty($result['data'][0])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Could not save attendance. Please try again.']]);
    exit();
}

echo json_encode(['success' => true, 'headcount' => $result['data'][0]]);
