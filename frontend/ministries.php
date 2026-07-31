<?php
require_once __DIR__ . '/../backend/auth_guard.php';
require_once __DIR__ . '/../backend/supabase_client.php';
require_once __DIR__ . '/../backend/ministriesData.php';
require_once __DIR__ . '/../backend/membersData.php';

function cmis_initials(string $name): string
{
  $parts = preg_split('/\s+/', trim($name));
  $initials = '';
  foreach (array_slice($parts, 0, 2) as $p) {
    $initials .= mb_strtoupper(mb_substr($p, 0, 1));
  }
  return $initials ?: '?';
}

$serverMinistries = fetch_ministries();
$membersLookup = fetch_members_lookup();
$ministryRolesLookup = fetch_ministry_roles_lookup();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ministries · CMIS</title>

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
          <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="bi bi-clipboard-check me-1"></i>Attendance</a></li>
          <li class="nav-item"><a class="nav-link" href="events.php"><i class="bi bi-calendar-event me-1"></i>Events</a></li>
          <li class="nav-item"><a class="nav-link active" href="ministries.php"><i class="bi bi-diagram-3 me-1"></i>Ministries</a></li>
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
          <p class="cmis-eyebrow"><i class="bi bi-diagram-3 me-2"></i>Church Groups</p>
          <h1 class="cmis-page-title">Ministries</h1>
          <p class="cmis-hero-subtitle">Ministries, fellowships &amp; their rosters</p>
        </div>
        <button type="button" class="cmis-btn-gold" data-bs-toggle="modal" data-bs-target="#addMinistryModal">
          <i class="bi bi-plus-lg me-2"></i>Add Ministry
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
          <input type="text" id="searchInput" class="cmis-search-input" placeholder="Search by ministry name…">
        </div>
        <select id="statusFilter" class="cmis-select">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>

    <!-- MINISTRIES TABLE -->
    <div class="cmis-card cmis-table-card reveal" data-reveal-order="2">
      <div class="table-responsive">
        <table class="cmis-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Description</th>
              <th>Members</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="ministriesTableBody"></tbody>
        </table>
      </div>
      <div class="cmis-table-footer">
        <p class="cmis-table-count" id="resultsCount">Showing 0 of 0 ministries</p>
        <nav aria-label="Ministries pagination">
          <ul class="pagination cmis-pagination" id="pagination"></ul>
        </nav>
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

  <!-- ADD / EDIT MINISTRY MODAL -->
  <div class="modal fade" id="addMinistryModal" tabindex="-1" aria-labelledby="addMinistryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content cmis-modal">
        <div class="modal-header cmis-modal-header">
          <h2 class="modal-title cmis-card-title" id="addMinistryModalLabel"><span id="ministryModalTitleText">Add Ministry</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form id="addMinistryForm" novalidate>
          <input type="hidden" name="min_id" id="formMinId" value="">
          <div class="modal-body cmis-modal-body">
            <label class="cmis-field-label">Ministry Name</label>
            <input type="text" class="cmis-input mb-3" name="name" placeholder="e.g. Youth Ministry" required maxlength="100">

            <label class="cmis-field-label">Description <span class="cmis-optional">(optional)</span></label>
            <textarea class="cmis-input" name="description" rows="3" placeholder="What this ministry does…"></textarea>
          </div>
          <div class="modal-footer cmis-modal-footer justify-content-end">
            <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="cmis-btn-gold" id="saveMinistryBtn"><i class="bi bi-check2 me-2"></i><span id="saveMinistryBtnLabel">Save Ministry</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- VIEW MINISTRY MODAL -->
  <div class="modal fade" id="viewMinistryModal" tabindex="-1" aria-labelledby="viewMinistryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content cmis-modal">
        <div class="modal-header cmis-modal-header">
          <h2 class="modal-title cmis-card-title" id="viewMinistryModalLabel">Ministry Details</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body cmis-modal-body">
          <input type="hidden" id="viewMinistryMinId" value="">
          <h3 class="cmis-step-heading mb-1" id="viewMinistryName">—</h3>
          <p class="cmis-card-subtitle mb-3" id="viewMinistryDescription">—</p>

          <p class="cmis-field-label mb-2">Roster (<span id="viewMinistryRosterCount">0</span>)</p>
          <ul class="cmis-ledger-list mb-3" id="viewMinistryRoster">
            <li class="cmis-ledger-item"><span class="cmis-ledger-body"><span class="cmis-ledger-title">No members yet</span></span></li>
          </ul>

          <div class="border-top pt-3">
            <label class="cmis-field-label">Add to Roster</label>
            <div class="row g-2">
              <div class="col-md-6">
                <select id="rosterMemberSelect"></select>
              </div>
              <div class="col-md-4">
                <select id="rosterRoleSelect" class="cmis-input">
                  <option value="" disabled selected>Select role</option>
                  <?php foreach ($ministryRolesLookup as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <button type="button" class="cmis-btn-primary w-100 justify-content-center" id="addRosterMemberBtn">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer cmis-modal-footer justify-content-end">
          <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const serverMinistries = <?php echo $serverMinistries !== null ? json_encode($serverMinistries) : 'null'; ?>;
    const membersLookup = <?php echo json_encode($membersLookup); ?>;
    const ministryRolesLookup = <?php echo json_encode($ministryRolesLookup); ?>;
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
  <script src="scripts/ministries.js"></script>
</body>

</html>