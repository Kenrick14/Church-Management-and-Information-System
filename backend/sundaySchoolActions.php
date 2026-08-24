<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/sundaySchoolData.php';

header('Content-Type: application/json');

/**
 * @return string|null  The age_group enum value. Returns null only in
 *   an edge case that shouldn't occur with real data (e.g. a future
 *   dob)
 */
function compute_age_group(string $dob, string $eventDate): ?string
{
    $dobDate = new DateTime($dob);
    $onDate = new DateTime($eventDate);
    $age = $dobDate->diff($onDate)->y;

    if ($age <= 3) return 'ages_3_under';
    if ($age <= 8) return 'ages_4_to_8';
    if ($age <= 11) return 'ages_9_to_11';
    return 'ages_12_above';
}

$method = $_SERVER['REQUEST_METHOD'];

// BRANCH 1 — GET: which children are currently marked present
if ($method === 'GET') {
    $eventId = trim($_GET['event_id'] ?? '');

    if ($eventId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing event_id.']]);
        exit();
    }

    $presentMemIds = fetch_sunday_school_attendance_for_event($eventId);
    echo json_encode(['success' => true, 'mem_ids' => $presentMemIds]);
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

// BRANCH 2 — POST: save attendance for a session
$eventId = trim($_POST['event_id'] ?? '');
$memIds = $_POST['mem_ids'] ?? [];

if ($eventId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Missing event_id.']]);
    exit();
}
if (!is_array($memIds)) {
    $memIds = [];
}

// Need the event's date to compute each child's age_group correctly.
$eventResult = supabase_rest('GET', 'events', ['select' => 'date', 'event_id' => 'eq.' . $eventId]);
if (!$eventResult['ok'] || empty($eventResult['data'][0]['date'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => ['Event not found.']]);
    exit();
}
$eventDate = $eventResult['data'][0]['date'];

// Need each checked child's dob to compute their age_group.
$children = fetch_children_lookup();
$childrenById = [];
foreach ($children as $child) {
    $childrenById[$child['mem_id']] = $child;
}

$warnings = [];
$rowsToInsert = [];

foreach ($memIds as $memId) {
    $child = $childrenById[$memId] ?? null;
    if ($child === null || empty($child['dob'])) {
        continue; // not a known child, or no dob on file — skip silently
    }

    $ageGroup = compute_age_group($child['dob'], $eventDate);

    if ($ageGroup === null) {
        $warnings[] = "{$child['first_name']} {$child['last_name']}'s age group could not be determined (check their date of birth) and was not recorded.";
        continue;
    }

    $rowsToInsert[] = ['event_id' => $eventId, 'mem_id' => $memId, 'age_group' => $ageGroup];
}

// Replace wholesale — same reasoning as Events' subject rows.
$deleteResult = supabase_rest('DELETE', 'sunday_school_attendance', ['event_id' => 'eq.' . $eventId]);
if (!$deleteResult['ok']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Could not save attendance. Please try again.']]);
    exit();
}

if (!empty($rowsToInsert)) {
    $insertResult = supabase_rest('POST', 'sunday_school_attendance', [], $rowsToInsert);
    if (!$insertResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not save attendance. Please try again.']]);
        exit();
    }
}

echo json_encode(['success' => true, 'mem_ids' => array_column($rowsToInsert, 'mem_id'), 'warnings' => $warnings]);
