<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/validators.php';
require_once __DIR__ . '/eventsData.php';

header('Content-Type: application/json');

const EVENT_TYPES = [
    'church_service', 'ministry_meeting', 'sunday_school', 'vestry',
    'wedding', 'anniversary', 'birthday', 'baptism', 'funeral',
];

const SUBJECT_RULES = [
    'wedding'     => ['min' => 2, 'max' => 2, 'roles' => ['groom', 'bride']],
    'anniversary' => ['min' => 2, 'max' => 2, 'roles' => ['spouse', 'spouse']],
    'birthday'    => ['min' => 1, 'max' => 1, 'roles' => ['celebrant']],
    'funeral'     => ['min' => 1, 'max' => 1, 'roles' => ['deceased']],
    'baptism'     => ['min' => 1, 'max' => null, 'roles' => null], 
];

/**
 * Validates the whole form for CREATE, where event_type is chosen by
 * the user and submitted.
 *
 * @return array{errors: string[], values: array}
 */
function validate_event_post(array $post): array
{
    $errors = [];

    $values = [
        'event_type'      => trim($post['event_type'] ?? ''),
        'date'            => trim($post['date'] ?? ''),
        'description'     => trim($post['description'] ?? ''),
        'min_id'          => trim($post['min_id'] ?? ''),
        'service_type_id' => trim($post['service_type_id'] ?? ''),
    ];

    if (!validate_in_list($values['event_type'], EVENT_TYPES)) {
        $errors[] = 'Invalid event type.';
        return ['errors' => $errors, 'values' => $values]; // nothing else can be checked meaningfully without a valid type
    }

    $errors = array_merge($errors, validate_event_details($values['event_type'], $values)['errors']);

    return ['errors' => $errors, 'values' => $values];
}

/**
 * Validates the fields for UPDATE, where event_type is NOT submitted
 * (it's locked/read-only in the edit form) — the caller must pass in
 * the event's existing type, fetched from the database, since it's
 * the only trustworthy source once editing no longer sends it.
 *
 * @return array{errors: string[], values: array}
 */
function validate_event_details_post(string $eventType, array $post): array
{
    $values = [
        'date'            => trim($post['date'] ?? ''),
        'description'     => trim($post['description'] ?? ''),
        'min_id'          => trim($post['min_id'] ?? ''),
        'service_type_id' => trim($post['service_type_id'] ?? ''),
    ];

    return ['errors' => validate_event_details($eventType, $values)['errors'], 'values' => $values];
}

/** Shared by both validators above: the type-specific field checks. */
function validate_event_details(string $eventType, array $values): array
{
    $errors = [];

    if ($values['date'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['date'])) {
        $errors[] = 'A valid date is required.';
    }

    if ($eventType === 'ministry_meeting' && $values['min_id'] === '') {
        $errors[] = 'Please select a ministry.';
    }

    if ($eventType === 'church_service' && $values['service_type_id'] === '') {
        $errors[] = 'Please select a service type.';
    }

    return ['errors' => $errors];
}

/**
 * Validates the subjects array for life-event types against
 * SUBJECT_RULES. Returns the normalized [['mem_id'=>..,'role'=>..], ...]
 * list alongside any errors.
 */
function validate_subjects(string $eventType, array $post): array
{
    $errors = [];
    $subjects = [];

    $rule = SUBJECT_RULES[$eventType] ?? null;
    if ($rule === null) {
        return ['errors' => [], 'subjects' => []]; 
    }

    $rawSubjects = $post['subjects'] ?? [];
    if (!is_array($rawSubjects)) {
        $rawSubjects = [];
    }

    foreach ($rawSubjects as $i => $s) {
        $memId = trim($s['mem_id'] ?? '');
        if ($memId === '') {
            continue; 
        }
        $role = $rule['roles'][$i] ?? ($eventType === 'baptism' ? 'baptized' : null);
        $subjects[] = ['mem_id' => $memId, 'role' => $role];
    }

    $count = count($subjects);

    if ($count < $rule['min']) {
        $errors[] = ucfirst($eventType) . ' requires at least ' . $rule['min'] . ' ' . ($rule['min'] === 1 ? 'person' : 'people') . '.';
    }
    if ($rule['max'] !== null && $count > $rule['max']) {
        $errors[] = ucfirst($eventType) . ' allows at most ' . $rule['max'] . ' ' . ($rule['max'] === 1 ? 'person' : 'people') . '.';
    }

    return ['errors' => $errors, 'subjects' => $subjects];
}

$method = $_SERVER['REQUEST_METHOD'];

// BRANCH 1 — GET: fetch one event's detail
if ($method === 'GET') {
    $eventId = trim($_GET['event_id'] ?? '');

    if ($eventId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing event_id.']]);
        exit();
    }

    $event = fetch_event_detail($eventId);

    if ($event === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'errors' => ['Event not found.']]);
        exit();
    }

    echo json_encode(['success' => true, 'event' => $event]);
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

// BRANCH — POST + _method=DELETE: delete an event
if (($_POST['_method'] ?? '') === 'DELETE') {
    $eventId = trim($_POST['event_id'] ?? '');

    if ($eventId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing event_id.']]);
        exit();
    }

    $deleteResult = supabase_rest('DELETE', 'events', ['event_id' => 'eq.' . $eventId]);

    if (!$deleteResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not delete event. Please try again.']]);
        exit();
    }

    echo json_encode(['success' => true, 'event_id' => $eventId]);
    exit();
}

// BRANCH 2 / 3 — POST: create (no event_id) or update (event_id present)
$eventId = trim($_POST['event_id'] ?? '');
$isUpdate = $eventId !== '';

if ($isUpdate) {
    // ---- UPDATE --------------------------------------------------------
    $existingEvent = fetch_event_detail($eventId);

    if ($existingEvent === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'errors' => ['Event not found.']]);
        exit();
    }

    $eventType = $existingEvent['event_type'];

    $validation = validate_event_details_post($eventType, $_POST);
    $v = $validation['values'];
    $errors = $validation['errors'];

    $subjectValidation = ['errors' => [], 'subjects' => []];
    if (empty($errors)) {
        $subjectValidation = validate_subjects($eventType, $_POST);
        $errors = array_merge($errors, $subjectValidation['errors']);
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit();
    }

    $eventResult = supabase_rest('PATCH', 'events', ['event_id' => 'eq.' . $eventId], [
        'date'            => $v['date'],
        'description'     => $v['description'] ?: null,
        'min_id'          => $eventType === 'ministry_meeting' ? $v['min_id'] : null,
        'service_type_id' => $eventType === 'church_service' ? $v['service_type_id'] : null,
    ]);

    if (!$eventResult['ok'] || empty($eventResult['data'][0])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not update event. Please try again.']]);
        exit();
    }

    $warnings = [];

    if (array_key_exists($eventType, SUBJECT_RULES)) {
        $deleteResult = supabase_rest('DELETE', 'event_subjects', ['event_id' => 'eq.' . $eventId]);
        if (!$deleteResult['ok']) {
            $warnings[] = 'Event updated, but the people involved could not be refreshed. Please review this event.';
        } else {
            foreach ($subjectValidation['subjects'] as $subject) {
                $subjectResult = supabase_rest('POST', 'event_subjects', [], [
                    'event_id'     => $eventId,
                    'mem_id'       => $subject['mem_id'],
                    'subject_role' => $subject['role'],
                ]);
                if (!$subjectResult['ok']) {
                    $warnings[] = 'Event updated, but one of the people involved could not be linked. Please edit the event to fix this.';
                }
            }
        }
    }

    // Re-fetch so the response has the same fully-embedded shape as
    // fetch_events() gives the table (ministry/service type/subjects names).
    $event = fetch_event_detail($eventId);

    echo json_encode(['success' => true, 'event' => $event, 'warnings' => $warnings]);
    exit();
}

// ---- CREATE -------------------------------------------------------------
$validation = validate_event_post($_POST);
$v = $validation['values'];
$errors = $validation['errors'];

$subjectValidation = ['errors' => [], 'subjects' => []];
if (empty($errors)) {
    $subjectValidation = validate_subjects($v['event_type'], $_POST);
    $errors = array_merge($errors, $subjectValidation['errors']);
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

$eventResult = supabase_rest('POST', 'events', [], [
    'event_type'      => $v['event_type'],
    'date'            => $v['date'],
    'description'     => $v['description'] ?: null,
    'min_id'          => $v['event_type'] === 'ministry_meeting' ? $v['min_id'] : null,
    'service_type_id' => $v['event_type'] === 'church_service' ? $v['service_type_id'] : null,
]);

if (!$eventResult['ok'] || empty($eventResult['data'][0]['event_id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Could not save event. Please try again.']]);
    exit();
}

$newEventId = $eventResult['data'][0]['event_id'];
$warnings = [];

// ---- Insert subjects, if this event type needs them ---------------------
foreach ($subjectValidation['subjects'] as $subject) {
    $subjectResult = supabase_rest('POST', 'event_subjects', [], [
        'event_id'     => $newEventId,
        'mem_id'       => $subject['mem_id'],
        'subject_role' => $subject['role'],
    ]);

    if (!$subjectResult['ok']) {
        $warnings[] = 'Event saved, but one of the people involved could not be linked. Please edit the event to fix this.';
    }
}

$event = fetch_event_detail($newEventId);

echo json_encode(['success' => true, 'event' => $event, 'warnings' => $warnings]);