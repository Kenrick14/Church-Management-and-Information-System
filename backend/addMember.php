<?php
// addMember.php — validates + persists a new member via Supabase.
// Called by fetch() from members.js. Returns JSON, never HTML — the
// modal stays open on error, or closes and the page updates on success.

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/validators.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit();
}

$errors = [];
$validParishes = [
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

// ---- Personal info ---------------------------------------------------
$first_name   = trim($_POST['first_name'] ?? '');
$mid_init     = trim($_POST['mid_init'] ?? '');
$last_name    = trim($_POST['last_name'] ?? '');
$dob          = trim($_POST['dob'] ?? '');
$gender       = trim($_POST['gender'] ?? '');
$date_joined  = trim($_POST['date_joined'] ?? '');
$status       = trim($_POST['status'] ?? '');
$passing_date = trim($_POST['passing_date'] ?? '');

if (!validate_name($first_name)) $errors[] = 'Invalid first name.';
if (!validate_mid_init($mid_init)) $errors[] = 'Invalid middle initial.';
if (!validate_name($last_name)) $errors[] = 'Invalid last name.';
if (!validate_date_not_future($dob)) $errors[] = 'Invalid date of birth.';
if (!validate_in_list($gender, ['Male', 'Female'])) $errors[] = 'Invalid gender.';
if (!validate_date_not_future($date_joined)) $errors[] = 'Invalid date joined.';
if (!validate_in_list($status, ['Member', 'Adherent', 'Visitor'])) $errors[] = 'Invalid status.';
if (!validate_optional_date($passing_date)) $errors[] = 'Invalid passing date.';

// ---- Contact info ------------------------------------------------------
$address_1 = trim($_POST['address_1'] ?? '');
$address_2 = trim($_POST['address_2'] ?? '');
$parish    = trim($_POST['parish'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email     = trim($_POST['email'] ?? '');

if ($address_1 === '') $errors[] = 'Address line 1 is required.';
if (!validate_in_list($parish, $validParishes)) $errors[] = 'Invalid parish.';
if (!validate_phone($telephone)) $errors[] = 'Invalid phone number.';
if (!validate_email($email)) $errors[] = 'Invalid email address.';

// ---- Next of kin --------------------------------------------------------
$nk_first_name = trim($_POST['nk_first_name'] ?? '');
$nk_last_name  = trim($_POST['nk_last_name'] ?? '');
$nk_relation   = trim($_POST['nk_relation'] ?? '');
$nk_address_1  = trim($_POST['nk_address_1'] ?? '');
$nk_address_2  = trim($_POST['nk_address_2'] ?? '');
$nk_parish     = trim($_POST['nk_parish'] ?? '');
$nk_telephone  = trim($_POST['nk_telephone'] ?? '');
$nk_email      = trim($_POST['nk_email'] ?? '');

if (!validate_name($nk_first_name)) $errors[] = 'Invalid next of kin first name.';
if (!validate_name($nk_last_name)) $errors[] = 'Invalid next of kin last name.';
if (!validate_in_list($nk_relation, ['Spouse', 'Child', 'Sibling', 'Parent', 'Friend'])) $errors[] = 'Invalid relation.';
if ($nk_address_1 === '') $errors[] = 'Next of kin address line 1 is required.';
if (!validate_in_list($nk_parish, $validParishes)) $errors[] = 'Invalid next of kin parish.';
if (!validate_phone($nk_telephone)) $errors[] = 'Invalid next of kin phone number.';
if (!validate_email($nk_email)) $errors[] = 'Invalid next of kin email address.';

// ---- Stop here if anything failed — nothing touches Supabase yet -------
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// ---- Insert next_of_kin first — members.nk_id references it ------------
$nkResult = supabase_rest('POST', 'next_of_kin', [], [
    'first_name' => $nk_first_name,
    'last_name'  => $nk_last_name,
    'relation'   => $nk_relation,
    'address_1'  => $nk_address_1,
    'address_2'  => $nk_address_2 ?: null,
    'parish'     => $nk_parish,
    'telephone'  => $nk_telephone,
    'email'      => $nk_email,
]);

if (!$nkResult['ok'] || empty($nkResult['data'][0]['nk_id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Could not save next of kin. Please try again.']]);
    exit();
}

$nk_id = $nkResult['data'][0]['nk_id'];

// ---- Insert the member, linked to that next_of_kin row -------------------
// Note: enum values in the database are lowercase (member/adherent/
// visitor), but the form sends Title Case for display — lowercase it
// here before it hits Supabase.
$memberResult = supabase_rest('POST', 'members', [], [
    'first_name'   => $first_name,
    'mid_init'     => $mid_init ?: null,
    'last_name'    => $last_name,
    'dob'          => $dob,
    'gender'       => $gender,
    'address_1'    => $address_1,
    'address_2'    => $address_2 ?: null,
    'parish'       => $parish,
    'telephone'    => $telephone,
    'email'        => $email,
    'status'       => strtolower($status),
    'date_joined'  => $date_joined,
    'passing_date' => $passing_date ?: null,
    'nk_id'        => $nk_id,
]);

if (!$memberResult['ok'] || empty($memberResult['data'][0])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Could not save member. Please try again.']]);
    exit();
}

echo json_encode(['success' => true, 'member' => $memberResult['data'][0]]);
