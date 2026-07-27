<?php
require_once __DIR__ . '/../backend/auth_guard.php';
require_once __DIR__ . '/../backend/supabase_client.php';
require_once __DIR__ . '/../backend/eventsData.php';

function cmis_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials ?: '?';
}

// Server-fetched data — null/empty falls back gracefully in the JS
// (same pattern as members.php: never let a Supabase hiccup break the page).
$serverEvents = fetch_events();
$ministries = fetch_ministries_lookup();
$serviceTypes = fetch_service_types_lookup();
$membersLookup = fetch_members_lookup();

$eventTypeLabels = [
    'church_service'   => 'Church Service',
    'ministry_meeting' => 'Ministry Meeting',
    'sunday_school'    => 'Sunday School',
    'vestry'           => 'Vestry',
    'wedding'          => 'Wedding',
    'anniversary'      => 'Anniversary',
    'birthday'         => 'Birthday',
    'baptism'          => 'Baptism',
    'funeral'          => 'Funeral',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events · CMIS</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>

<!-- NAVBAR (shared) -->
<nav class="navbar navbar-expand-lg cmis-navbar sticky-top" id="mainNav">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand cmis-brand" href="dashboard.php">
      <span class="cmis-brand-mark" aria-hidden="true">
        <svg viewBox="0 0 32 32" width="30" height="30"><path d="M4 30V13 A12 12 0 0 1 28 13 V30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
      </span>
      <span class="cmis-brand-text">
        <span class="cmis-brand-name">Portmore United Church</span>
        <span class="cmis-brand-sub">CMIS</span>
      </span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-controls="navContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navContent">
      <ul class="navbar-nav mx-auto cmis-nav-links">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="members.php"><i class="bi bi-people me-1"></i>Members</a></li>
        <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="bi bi-clipboard-check me-1"></i>Attendance</a></li>
        <li class="nav-item"><a class="nav-link active" href="events.php"><i class="bi bi-calendar-event me-1"></i>Events</a></li>
        <li class="nav-item"><a class="nav-link" href="ministries.php"><i class="bi bi-diagram-3 me-1"></i>Ministries</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-bar-chart me-1"></i>Reports</a></li>
      </ul>
      <div class="d-flex align-items-center cmis-nav-actions">
        <button class="cmis-icon-btn" type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span class="cmis-badge-dot" aria-hidden="true"></span></button>
        <div class="dropdown">
          <button class="cmis-avatar-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="cmis-avatar"><?php echo htmlspecialchars(cmis_initials($_SESSION['user_display_name'] ?? '?')); ?></span>
            <span class="cmis-avatar-name d-none d-xl-inline"><?php echo htmlspecialchars($_SESSION['user_display_name'] ?? 'Staff'); ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end cmis-dropdown">
            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../backend/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- PAGE HEADER -->
<header class="cmis-page-header">
  <div class="cmis-hero-arches" aria-hidden="true">
    <svg viewBox="0 0 480 140" preserveAspectRatio="xMidYMax slice">
      <path d="M0,140 L0,55 A40,55 0 0 1 80,55 L80,140" /><path d="M80,140 L80,55 A40,55 0 0 1 160,55 L160,140" />
      <path d="M160,140 L160,55 A40,55 0 0 1 240,55 L240,140" /><path d="M240,140 L240,55 A40,55 0 0 1 320,55 L320,140" />
      <path d="M320,140 L320,55 A40,55 0 0 1 400,55 L400,140" /><path d="M400,140 L400,55 A40,55 0 0 1 480,55 L480,140" />
    </svg>
  </div>
  <div class="container-fluid px-3 px-lg-4">
    <div class="cmis-page-header-row reveal" data-reveal-order="0">
      <div>
        <p class="cmis-eyebrow"><i class="bi bi-calendar-event me-2"></i>Church Calendar</p>
        <h1 class="cmis-page-title">Events</h1>
        <p class="cmis-hero-subtitle">Services, meetings, weddings, baptisms &amp; more</p>
      </div>
      <button type="button" class="cmis-btn-gold" data-bs-toggle="modal" data-bs-target="#addEventModal">
        <i class="bi bi-calendar-plus me-2"></i>Add Event
      </button>
    </div>
  </div>
</header>

<main class="container-fluid px-3 px-lg-4 cmis-main">

  <!-- FILTER BAR -->
  <div class="cmis-card cmis-filter-card reveal" data-reveal-order="1">
    <div class="cmis-filter-row">
      <div class="cmis-search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="cmis-search-input" placeholder="Search by description…">
      </div>
      <select id="typeFilter" class="cmis-select">
        <option value="">All event types</option>
        <?php foreach ($eventTypeLabels as $key => $label): ?>
          <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- EVENTS TABLE -->
  <div class="cmis-card cmis-table-card reveal" data-reveal-order="2">
    <div class="table-responsive">
      <table class="cmis-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Description</th>
            <th>Related</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody id="eventsTableBody"></tbody>
      </table>
    </div>
    <div class="cmis-table-footer">
      <p class="cmis-table-count" id="resultsCount">Showing 0 of 0 events</p>
      <nav aria-label="Events pagination"><ul class="pagination cmis-pagination" id="pagination"></ul></nav>
    </div>
  </div>
</main>

<footer class="cmis-footer">
  <div class="container-fluid px-3 px-lg-4"><p class="mb-0">Church Management &amp; Information System</p></div>
</footer>

<!-- TOAST -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1080;">
  <div id="successToast" class="toast cmis-toast" role="status" aria-live="polite" aria-atomic="true">
    <div class="d-flex align-items-center">
      <div class="toast-body d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><span id="toastMessage">Saved.</span></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- ADD EVENT MODAL -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content cmis-modal">
      <div class="modal-header cmis-modal-header">
        <h2 class="modal-title cmis-card-title" id="addEventModalLabel"><span id="eventModalTitleText">Add Event</span></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="addEventForm" novalidate>
        <input type="hidden" name="event_id" id="formEventId" value="">
        <div class="modal-body cmis-modal-body">

          <label class="cmis-field-label">Event Type</label>
          <select class="cmis-input mb-1" name="event_type" id="eventTypeSelect" required>
            <option value="" disabled selected>Select event type</option>
            <?php foreach ($eventTypeLabels as $key => $label): ?>
              <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
          <p class="cmis-upload-hint mb-3 d-none" id="eventTypeLockedHint">Event type can't be changed after creation — delete and recreate if this is wrong.</p>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="cmis-field-label">Date</label>
              <input type="date" class="cmis-input" name="date" required>
            </div>
          </div>

          <!-- Shown only for church_service -->
          <div id="serviceTypeGroup" class="mb-3 d-none">
            <label class="cmis-field-label">Service Type</label>
            <select class="cmis-input" name="service_type_id">
              <option value="" disabled selected>Select service type</option>
              <?php foreach ($serviceTypes as $st): ?>
                <option value="<?php echo htmlspecialchars($st['service_type_id']); ?>"><?php echo htmlspecialchars($st['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Shown only for ministry_meeting -->
          <div id="ministryGroup" class="mb-3 d-none">
            <label class="cmis-field-label">Ministry</label>
            <select class="cmis-input" name="min_id">
              <option value="" disabled selected>Select ministry</option>
              <?php foreach ($ministries as $min): ?>
                <option value="<?php echo htmlspecialchars($min['min_id']); ?>"><?php echo htmlspecialchars($min['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Shown only for life events (wedding/anniversary/birthday/baptism/funeral) -->
          <div id="subjectsGroup" class="mb-3 d-none">
            <label class="cmis-field-label" id="subjectsLabel">People Involved</label>
            <div id="subjectsList"></div>
            <button type="button" class="cmis-btn-ghost mt-2 d-none" id="addSubjectBtn">
              <i class="bi bi-person-plus me-2"></i>Add Another Person
            </button>
          </div>

          <label class="cmis-field-label">Description <span class="cmis-optional">(optional)</span></label>
          <textarea class="cmis-input" name="description" rows="3"></textarea>
        </div>

        <div class="modal-footer cmis-modal-footer justify-content-end">
          <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="cmis-btn-gold" id="saveEventBtn"><i class="bi bi-check2 me-2"></i><span id="saveEventBtnLabel">Save Event</span></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW EVENT MODAL -->
<div class="modal fade" id="viewEventModal" tabindex="-1" aria-labelledby="viewEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cmis-modal">
      <div class="modal-header cmis-modal-header">
        <h2 class="modal-title cmis-card-title" id="viewEventModalLabel">Event Details</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body cmis-modal-body">
        <p class="mb-3"><span class="cmis-tag cmis-tag--green" id="viewEventType">—</span></p>
        <p class="cmis-field-label mb-1">Date</p>
        <p class="mb-3" id="viewEventDate">—</p>
        <p class="cmis-field-label mb-1" id="viewEventRelatedLabel">Related</p>
        <p class="mb-3" id="viewEventRelated">—</p>
        <p class="cmis-field-label mb-1">Description</p>
        <p class="mb-0" id="viewEventDescription">—</p>
      </div>
      <div class="modal-footer cmis-modal-footer justify-content-end">
        <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  const serverEvents = <?php echo $serverEvents !== null ? json_encode($serverEvents) : 'null'; ?>;
  const membersLookup = <?php echo json_encode($membersLookup); ?>;
  const eventTypeLabels = <?php echo json_encode($eventTypeLabels); ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script src="scripts/events.js"></script>
</body>
</html>