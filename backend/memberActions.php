<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/validators.php';
require_once __DIR__ . '/membersData.php';

header('Content-Type: application/json');

const VALID_PARISHES = [
    'Kingston',
    'St. Andrew',
    'St. Thomas',
    'Portland',
    'St. Mary',
    'St. Ann',
    'Trelawny',
    'St. James',
    'Hanover',
    'Westmoreland',
    'St. Elizabeth',
    'Manchester',
    'Clarendon',
    'St. Catherine',
];

/**
 * Validates the whole Add/Edit Member form. Used by both the create
 * and update branches below.
 *
 * @return array{errors: string[], values: array}
 */
function validate_member_post(array $post): array
{
    $errors = [];

    $values = [
        'first_name'    => trim($post['first_name'] ?? ''),
        'mid_init'      => trim($post['mid_init'] ?? ''),
        'last_name'     => trim($post['last_name'] ?? ''),
        'dob'           => trim($post['dob'] ?? ''),
        'gender'        => trim($post['gender'] ?? ''),
        'date_joined'   => trim($post['date_joined'] ?? ''),
        'status'        => trim($post['status'] ?? ''),
        'passing_date'  => trim($post['passing_date'] ?? ''),
        'address_1'     => trim($post['address_1'] ?? ''),
        'address_2'     => trim($post['address_2'] ?? ''),
        'parish'        => trim($post['parish'] ?? ''),
        'telephone'     => trim($post['telephone'] ?? ''),
        'email'         => trim($post['email'] ?? ''),
        'nk_first_name' => trim($post['nk_first_name'] ?? ''),
        'nk_last_name'  => trim($post['nk_last_name'] ?? ''),
        'nk_relation'   => trim($post['nk_relation'] ?? ''),
        'nk_address_1'  => trim($post['nk_address_1'] ?? ''),
        'nk_address_2'  => trim($post['nk_address_2'] ?? ''),
        'nk_parish'     => trim($post['nk_parish'] ?? ''),
        'nk_telephone'  => trim($post['nk_telephone'] ?? ''),
        'nk_email'      => trim($post['nk_email'] ?? ''),
    ];

    if (!validate_name($values['first_name'])) $errors[] = 'Invalid first name.';
    if (!validate_mid_init($values['mid_init'])) $errors[] = 'Invalid middle initial.';
    if (!validate_name($values['last_name'])) $errors[] = 'Invalid last name.';
    if (!validate_date_not_future($values['dob'])) $errors[] = 'Invalid date of birth.';
    if (!validate_in_list($values['gender'], ['Male', 'Female'])) $errors[] = 'Invalid gender.';
    if (!validate_date_not_future($values['date_joined'])) $errors[] = 'Invalid date joined.';
    if (!validate_in_list($values['status'], ['Member', 'Adherent', 'Visitor'])) $errors[] = 'Invalid status.';
    if (!validate_optional_date($values['passing_date'])) $errors[] = 'Invalid passing date.';

    if ($values['address_1'] === '') $errors[] = 'Address line 1 is required.';
    if (!validate_in_list($values['parish'], VALID_PARISHES)) $errors[] = 'Invalid parish.';
    if (!validate_phone($values['telephone'])) $errors[] = 'Invalid phone number.';
    if (!validate_email($values['email'])) $errors[] = 'Invalid email address.';

    if (!validate_name($values['nk_first_name'])) $errors[] = 'Invalid next of kin first name.';
    if (!validate_name($values['nk_last_name'])) $errors[] = 'Invalid next of kin last name.';
    if (!validate_in_list($values['nk_relation'], ['Spouse', 'Child', 'Sibling', 'Parent', 'Friend'])) $errors[] = 'Invalid relation.';
    if ($values['nk_address_1'] === '') $errors[] = 'Next of kin address line 1 is required.';
    if (!validate_in_list($values['nk_parish'], VALID_PARISHES)) $errors[] = 'Invalid next of kin parish.';
    if (!validate_phone($values['nk_telephone'])) $errors[] = 'Invalid next of kin phone number.';
    if (!validate_email($values['nk_email'])) $errors[] = 'Invalid next of kin email address.';

    return ['errors' => $errors, 'values' => $values];
}

function upload_member_photo_if_present(string $memId, array &$member, array &$warnings): void
{
    try {
        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $warnings[] = "Photo upload failed. You can add a photo later from the member's profile.";
            return;
        }

        $maxBytes = 5 * 1024 * 1024; // 5MB
        $imageInfo = @getimagesize($_FILES['avatar']['tmp_name']);
        $typeMap = [IMAGETYPE_JPEG => ['image/jpeg', 'jpg'], IMAGETYPE_PNG => ['image/png', 'png']];
        $detected = ($imageInfo !== false && isset($typeMap[$imageInfo[2]])) ? $typeMap[$imageInfo[2]] : null;

        if ($detected === null) {
            $warnings[] = 'Photo skipped: only JPG or PNG images are supported.';
            return;
        }
        if ($_FILES['avatar']['size'] > $maxBytes) {
            $warnings[] = 'Photo skipped: file is larger than 5MB.';
            return;
        }

        [$mimeType, $ext] = $detected;
        $storagePath = "members/{$memId}.{$ext}";
        $uploadResult = supabase_storage_upload('profile-photos', $storagePath, $_FILES['avatar']['tmp_name'], $mimeType);

        if (!$uploadResult['ok']) {
            $warnings[] = "Photo upload failed. You can add a photo later from the member's profile.";
            return;
        }

        $patchResult = supabase_rest('PATCH', 'members', ['mem_id' => 'eq.' . $memId], ['avatar_path' => $storagePath]);

        if ($patchResult['ok']) {
            $member['avatar_path'] = $storagePath;
            $member['avatar_url'] = supabase_public_url('profile-photos', $storagePath);
        } else {
            $warnings[] = 'Photo uploaded but could not be linked to the member record.';
        }
    } catch (\Throwable $e) {
        $warnings[] = "Photo could not be processed. You can add a photo later from the member's profile.";
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// BRANCH 1 — GET: fetch one member's full detail (View + Edit modals)
if ($method === 'GET') {
    $memId = trim($_GET['mem_id'] ?? '');

    if ($memId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing mem_id.']]);
        exit();
    }

    $member = fetch_member_detail($memId);

    if ($member === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'errors' => ['Member not found.']]);
        exit();
    }

    echo json_encode(['success' => true, 'member' => $member]);
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

// BRANCH 2 — POST + _method=DELETE: delete a member
if (($_POST['_method'] ?? '') === 'DELETE') {
    $memId = trim($_POST['mem_id'] ?? '');

    if ($memId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing mem_id.']]);
        exit();
    }

    $member = fetch_member_detail($memId);
    if ($member === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'errors' => ['Member not found.']]);
        exit();
    }
    $nkId = $member['next_of_kin']['nk_id'] ?? null;

    // members is deleted first — it's the referencing side of the FK,
    $deleteResult = supabase_rest('DELETE', 'members', ['mem_id' => 'eq.' . $memId]);

    if (!$deleteResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not delete member. Please try again.']]);
        exit();
    }

    if ($nkId) {
        supabase_rest('DELETE', 'next_of_kin', ['nk_id' => 'eq.' . $nkId]); // best-effort cleanup
    }

    echo json_encode(['success' => true, 'mem_id' => $memId]);
    exit();
}


// BRANCH 3 / 4 — POST: create (no mem_id) or update (mem_id present)
$memId = trim($_POST['mem_id'] ?? '');
$isUpdate = $memId !== '';

$validation = validate_member_post($_POST);
$v = $validation['values'];

if (!empty($validation['errors'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $validation['errors']]);
    exit();
}

if ($isUpdate) {
    // ---- UPDATE ----
    $nkId = trim($_POST['nk_id'] ?? '');

    if ($nkId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Missing next of kin reference — try reopening the edit form.']]);
        exit();
    }

    $nkResult = supabase_rest('PATCH', 'next_of_kin', ['nk_id' => 'eq.' . $nkId], [
        'first_name' => $v['nk_first_name'],
        'last_name'  => $v['nk_last_name'],
        'relation'   => $v['nk_relation'],
        'address_1'  => $v['nk_address_1'],
        'address_2'  => $v['nk_address_2'] ?: null,
        'parish'     => $v['nk_parish'],
        'telephone'  => $v['nk_telephone'],
        'email'      => $v['nk_email'],
    ]);

    if (!$nkResult['ok']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not update next of kin. Please try again.']]);
        exit();
    }

    $memberResult = supabase_rest('PATCH', 'members', ['mem_id' => 'eq.' . $memId], [
        'first_name'   => $v['first_name'],
        'mid_init'     => $v['mid_init'] ?: null,
        'last_name'    => $v['last_name'],
        'dob'          => $v['dob'],
        'gender'       => $v['gender'],
        'address_1'    => $v['address_1'],
        'address_2'    => $v['address_2'] ?: null,
        'parish'       => $v['parish'],
        'telephone'    => $v['telephone'],
        'email'        => $v['email'],
        'status'       => strtolower($v['status']),
        'date_joined'  => $v['date_joined'],
        'passing_date' => $v['passing_date'] ?: null,
    ]);

    if (!$memberResult['ok'] || empty($memberResult['data'][0])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not update member. Please try again.']]);
        exit();
    }

    $member = $memberResult['data'][0];
    $member['avatar_url'] = !empty($member['avatar_path'])
        ? supabase_public_url('profile-photos', $member['avatar_path'])
        : null;
} else {
    // ---- CREATE ---
    $nkResult = supabase_rest('POST', 'next_of_kin', [], [
        'first_name' => $v['nk_first_name'],
        'last_name'  => $v['nk_last_name'],
        'relation'   => $v['nk_relation'],
        'address_1'  => $v['nk_address_1'],
        'address_2'  => $v['nk_address_2'] ?: null,
        'parish'     => $v['nk_parish'],
        'telephone'  => $v['nk_telephone'],
        'email'      => $v['nk_email'],
    ]);

    if (!$nkResult['ok'] || empty($nkResult['data'][0]['nk_id'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not save next of kin. Please try again.']]);
        exit();
    }

    $newNkId = $nkResult['data'][0]['nk_id'];

    $memberResult = supabase_rest('POST', 'members', [], [
        'first_name'   => $v['first_name'],
        'mid_init'     => $v['mid_init'] ?: null,
        'last_name'    => $v['last_name'],
        'dob'          => $v['dob'],
        'gender'       => $v['gender'],
        'address_1'    => $v['address_1'],
        'address_2'    => $v['address_2'] ?: null,
        'parish'       => $v['parish'],
        'telephone'    => $v['telephone'],
        'email'        => $v['email'],
        'status'       => strtolower($v['status']),
        'date_joined'  => $v['date_joined'],
        'passing_date' => $v['passing_date'] ?: null,
        'nk_id'        => $newNkId,
    ]);

    if (!$memberResult['ok'] || empty($memberResult['data'][0])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'errors' => ['Could not save member. Please try again.']]);
        exit();
    }

    $member = $memberResult['data'][0];
    $member['avatar_url'] = null;
}

$warnings = [];
upload_member_photo_if_present($member['mem_id'], $member, $warnings);

echo json_encode(['success' => true, 'member' => $member, 'warnings' => $warnings]);
