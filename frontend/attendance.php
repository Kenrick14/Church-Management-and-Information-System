<?php
require_once __DIR__ . '/../backend/auth_guard.php';
require_once __DIR__ . '/../backend/supabase_client.php';
require_once __DIR__ . '/../backend/headcountData.php';
require_once __DIR__ . '/../backend/sundaySchoolData.php';
require_once __DIR__ . '/../backend/vestryHoursData.php';

function cmis_initials(string $name): string
{
  $parts = preg_split('/\s+/', trim($name));
  $initials = '';
  foreach (array_slice($parts, 0, 2) as $p) {
    $initials .= mb_strtoupper(mb_substr($p, 0, 1));
  }
  return $initials ?: '?';
}

$serverHeadcountEvents = fetch_headcount_events();
$serverSundaySchoolEvents = fetch_sunday_school_events();
$childrenLookup = fetch_children_lookup();
$serverVestryHours = fetch_vestry_hours();
$ministersLookup = fetch_ministers_lookup();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance · CMIS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles/dashboard.css">
</head>

<body>

  <!-- NAVBAR (shared) -->
  <nav class="navbar navbar-expand-lg cmis-navbar sticky-top" id="mainNav">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand cmis-brand" href="dashboard.php">
        <span class="cmis-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 32 32" width="30" height="30">
            <path d="M4 30V13 A12 12 0 0 1 28 13 V30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
          </svg>
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
          <li class="nav-item"><a class="nav-link active" href="attendance.php"><i class="bi bi-clipboard-check me-1"></i>Attendance</a></li>
          <li class="nav-item"><a class="nav-link" href="events.php"><i class="bi bi-calendar-event me-1"></i>Events</a></li>
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
              <li>
                <hr class="dropdown-divider">
              </li>
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
        <path d="M0,140 L0,55 A40,55 0 0 1 80,55 L80,140" />
        <path d="M80,140 L80,55 A40,55 0 0 1 160,55 L160,140" />
        <path d="M160,140 L160,55 A40,55 0 0 1 240,55 L240,140" />
        <path d="M240,140 L240,55 A40,55 0 0 1 320,55 L320,140" />
        <path d="M320,140 L320,55 A40,55 0 0 1 400,55 L400,140" />
        <path d="M400,140 L400,55 A40,55 0 0 1 480,55 L480,140" />
      </svg>
    </div>
    <div class="container-fluid px-3 px-lg-4">
      <div class="cmis-page-header-row reveal" data-reveal-order="0">
        <div>
          <p class="cmis-eyebrow"><i class="bi bi-clipboard-check me-2"></i>Attendance Records</p>
          <h1 class="cmis-page-title">Attendance</h1>
          <p class="cmis-hero-subtitle">Headcounts, Sunday School &amp; Vestry Hours</p>
        </div>
      </div>
    </div>
  </header>

  <main class="container-fluid px-3 px-lg-4 cmis-main">

    <!-- TABS -->
    <ul class="nav cmis-attendance-tabs reveal" data-reveal-order="1" id="attendanceTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="headcount-tab" data-bs-toggle="tab" data-bs-target="#headcount-pane" type="button" role="tab">
          <i class="bi bi-people me-2"></i>Headcount
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="sundayschool-tab" data-bs-toggle="tab" data-bs-target="#sundayschool-pane" type="button" role="tab">
          <i class="bi bi-mortarboard me-2"></i>Sunday School
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="vestry-tab" data-bs-toggle="tab" data-bs-target="#vestry-pane" type="button" role="tab">
          <i class="bi bi-journal-text me-2"></i>Vestry Hours
        </button>
      </li>
    </ul>

    <div class="tab-content">

      <!-- ============================= HEADCOUNT ============================= -->
      <div class="tab-pane fade show active" id="headcount-pane" role="tabpanel">

        <div class="cmis-card cmis-filter-card reveal" data-reveal-order="2">
          <div class="cmis-filter-row">
            <div class="cmis-search-wrap">
              <i class="bi bi-search"></i>
              <input type="text" id="hcSearchInput" class="cmis-search-input" placeholder="Search by description…">
            </div>
            <select id="hcTypeFilter" class="cmis-select">
              <option value="">All types</option>
              <option value="church_service">Church Service</option>
              <option value="ministry_meeting">Ministry Meeting</option>
            </select>
            <select id="hcRecordedFilter" class="cmis-select">
              <option value="">All records</option>
              <option value="recorded">Recorded</option>
              <option value="unrecorded">Not yet recorded</option>
            </select>
          </div>
        </div>

        <div class="cmis-card cmis-table-card reveal" data-reveal-order="3">
          <div class="table-responsive">
            <table class="cmis-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Related</th>
                  <th>Attendance</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="hcTableBody"></tbody>
            </table>
          </div>
          <div class="cmis-table-footer">
            <p class="cmis-table-count" id="hcResultsCount">Showing 0 of 0 events</p>
            <nav aria-label="Headcount pagination">
              <ul class="pagination cmis-pagination" id="hcPagination"></ul>
            </nav>
          </div>
        </div>
      </div>

      <!-- ============================= SUNDAY SCHOOL ============================= -->
      <div class="tab-pane fade" id="sundayschool-pane" role="tabpanel">

        <div class="cmis-card cmis-filter-card">
          <div class="cmis-filter-row">
            <div class="cmis-search-wrap">
              <i class="bi bi-search"></i>
              <input type="text" id="ssSearchInput" class="cmis-search-input" placeholder="Search by description…">
            </div>
          </div>
        </div>

        <div class="cmis-card cmis-table-card">
          <div class="table-responsive">
            <table class="cmis-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Present</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="ssTableBody"></tbody>
            </table>
          </div>
          <div class="cmis-table-footer">
            <p class="cmis-table-count" id="ssResultsCount">Showing 0 of 0 sessions</p>
            <nav aria-label="Sunday School pagination">
              <ul class="pagination cmis-pagination" id="ssPagination"></ul>
            </nav>
          </div>
        </div>
      </div>

      <!-- ============================= VESTRY HOURS ============================= -->
      <div class="tab-pane fade" id="vestry-pane" role="tabpanel">

        <div class="cmis-card cmis-filter-card">
          <div class="cmis-filter-row">
            <div class="cmis-search-wrap">
              <i class="bi bi-search"></i>
              <input type="text" id="vhSearchInput" class="cmis-search-input" placeholder="Search by minister or duties…">
            </div>
            <button type="button" class="cmis-btn-gold ms-auto" data-bs-toggle="modal" data-bs-target="#vhModal" id="vhAddBtn">
              <i class="bi bi-plus-lg me-2"></i>Log Hours
            </button>
          </div>
        </div>

        <div class="cmis-card cmis-table-card">
          <div class="table-responsive">
            <table class="cmis-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Minister</th>
                  <th>Hours</th>
                  <th>Duties</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="vhTableBody"></tbody>
            </table>
          </div>
          <div class="cmis-table-footer">
            <p class="cmis-table-count" id="vhResultsCount">Showing 0 of 0 entries</p>
            <nav aria-label="Vestry Hours pagination">
              <ul class="pagination cmis-pagination" id="vhPagination"></ul>
            </nav>
          </div>
        </div>
      </div>

    </div>
  </main>

  <footer class="cmis-footer">
    <div class="container-fluid px-3 px-lg-4">
      <p class="mb-0">Church Management &amp; Information System</p>
    </div>
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

  <!-- RECORD / EDIT HEADCOUNT MODAL -->
  <div class="modal fade" id="headcountModal" tabindex="-1" aria-labelledby="headcountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content cmis-modal">
        <div class="modal-header cmis-modal-header">
          <h2 class="modal-title cmis-card-title" id="headcountModalLabel"><span id="hcModalTitleText">Record Attendance</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form id="headcountForm" novalidate>
          <input type="hidden" name="event_id" id="hcFormEventId" value="">
          <input type="hidden" name="headcount_id" id="hcFormHeadcountId" value="">
          <div class="modal-body cmis-modal-body">
            <p class="cmis-field-label mb-1" id="hcModalEventType">—</p>
            <p class="mb-3" id="hcModalEventContext">—</p>

            <label class="cmis-field-label">Number of Attendees</label>
            <input type="number" class="cmis-input" name="attendee_count" id="hcAttendeeCount" min="0" step="1" required>
          </div>
          <div class="modal-footer cmis-modal-footer justify-content-end">
            <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="cmis-btn-gold" id="hcSaveBtn"><i class="bi bi-check2 me-2"></i>Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- SUNDAY SCHOOL ATTENDANCE MODAL -->
  <div class="modal fade" id="ssModal" tabindex="-1" aria-labelledby="ssModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content cmis-modal">
        <div class="modal-header cmis-modal-header">
          <h2 class="modal-title cmis-card-title" id="ssModalLabel">Sunday School Attendance</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form id="ssForm" novalidate>
          <input type="hidden" name="event_id" id="ssFormEventId" value="">
          <div class="modal-body cmis-modal-body">
            <p class="mb-3" id="ssModalEventContext">—</p>

            <div class="cmis-search-wrap mb-3">
              <i class="bi bi-search"></i>
              <input type="text" id="ssChecklistSearch" class="cmis-search-input" placeholder="Filter the list…">
            </div>

            <p class="cmis-field-label mb-2"><span id="ssCheckedCount">0</span> marked present</p>
            <div id="ssChecklist" style="max-height: 320px; overflow-y: auto;"></div>

            <?php if (empty($childrenLookup)): ?>
              <p class="cmis-login-error mt-3 mb-0">
                No members are marked with status "minor" yet — add children via the Members page (status = Minor) before taking attendance here.
              </p>
            <?php endif; ?>
          </div>
          <div class="modal-footer cmis-modal-footer justify-content-end">
            <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="cmis-btn-gold" id="ssSaveBtn"><i class="bi bi-check2 me-2"></i>Save Attendance</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- LOG / EDIT VESTRY HOURS MODAL -->
  <div class="modal fade" id="vhModal" tabindex="-1" aria-labelledby="vhModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content cmis-modal">
        <div class="modal-header cmis-modal-header">
          <h2 class="modal-title cmis-card-title" id="vhModalLabel"><span id="vhModalTitleText">Log Vestry Hours</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form id="vhForm" novalidate>
          <input type="hidden" name="vestry_id" id="vhFormVestryId" value="">
          <div class="modal-body cmis-modal-body">

            <?php if (empty($ministersLookup)): ?>
              <p class="cmis-login-error mb-3">
                No one is currently recorded as a Minister in any ministry roster. Add someone via the Ministries page (assign the "Minister" role) before logging hours here.
              </p>
            <?php endif; ?>

            <label class="cmis-field-label">Minister</label>
            <select class="cmis-input mb-3" name="mem_id" id="vhMemberSelect" required <?php echo empty($ministersLookup) ? 'disabled' : ''; ?>>
              <option value="" disabled selected>Select minister</option>
              <?php foreach ($ministersLookup as $minister): ?>
                <option value="<?php echo htmlspecialchars($minister['mem_id']); ?>" <?php echo ($minister['mem_id'] === ($_SESSION['user_mem_id'] ?? null)) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($minister['first_name'] . ' ' . $minister['last_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <label class="cmis-field-label">Date</label>
            <input type="date" class="cmis-input mb-3" name="date" id="vhDate" required>

            <label class="cmis-field-label">Hours</label>
            <input type="number" class="cmis-input mb-3" name="hours_logged" id="vhHours" min="0.25" step="0.25" required>

            <label class="cmis-field-label">Duties <span class="cmis-optional">(optional)</span></label>
            <textarea class="cmis-input" name="duties" id="vhDuties" rows="3" placeholder="What was done during this time…"></textarea>
          </div>
          <div class="modal-footer cmis-modal-footer justify-content-end">
            <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="cmis-btn-gold" id="vhSaveBtn"><i class="bi bi-check2 me-2"></i><span id="vhSaveBtnLabel">Save</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    const serverHeadcountEvents = <?php echo $serverHeadcountEvents !== null ? json_encode($serverHeadcountEvents) : 'null'; ?>;
    const serverSundaySchoolEvents = <?php echo $serverSundaySchoolEvents !== null ? json_encode($serverSundaySchoolEvents) : 'null'; ?>;
    const childrenLookup = <?php echo json_encode($childrenLookup); ?>;
    const serverVestryHours = <?php echo $serverVestryHours !== null ? json_encode($serverVestryHours) : 'null'; ?>;
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="scripts/attendance.js"></script>
</body>

</html>