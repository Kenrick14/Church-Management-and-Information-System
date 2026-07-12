<?php
require_once __DIR__ . '/../backend/auth_guard.php';
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

$serverMembers = fetch_members();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Members · CMIS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles/dashboard.css">
</head>

<body>

  <!--NAVBAR (shared across pages)-->
  <nav class="navbar navbar-expand-lg cmis-navbar sticky-top" id="mainNav">
    <div class="container-fluid px-3 px-lg-4">

      <a class="navbar-brand cmis-brand" href="dashboard.php">
        <span class="cmis-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 32 32" width="30" height="30">
            <path d="M4 30V13 A12 12 0 0 1 28 13 V30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
          </svg>
        </span>
        <span class="cmis-brand-text">
          <span class="cmis-brand-name">The Church</span>
          <span class="cmis-brand-sub">CMIS</span>
        </span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-controls="navContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav mx-auto cmis-nav-links">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2 me-1"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link active" href="members.php"><i class="bi bi-people me-1"></i>Members</a></li>
          <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="bi bi-clipboard-check me-1"></i>Attendance</a></li>
          <li class="nav-item"><a class="nav-link" href="events.php"><i class="bi bi-calendar-event me-1"></i>Events</a></li>
          <li class="nav-item"><a class="nav-link" href="ministries.php"><i class="bi bi-diagram-3 me-1"></i>Ministries</a></li>
          <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-bar-chart me-1"></i>Reports</a></li>
        </ul>

        <div class="d-flex align-items-center cmis-nav-actions">
          <button class="cmis-icon-btn" type="button" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="cmis-badge-dot" aria-hidden="true"></span>
          </button>

          <div class="dropdown">
            <button class="cmis-avatar-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="cmis-avatar"><?php echo htmlspecialchars(cmis_initials($_SESSION['user_display_name'] ?? '?')); ?></span>
              <span class="cmis-avatar-name d-none d-xl-inline"><?php echo htmlspecialchars($_SESSION['user_display_name'] ?? 'Staff'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end cmis-dropdown">
              <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
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

  <!--PAGE HEADER-->
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
          <p class="cmis-eyebrow"><i class="bi bi-people me-2"></i>Congregation Records</p>
          <h1 class="cmis-page-title">Members</h1>
          <p class="cmis-hero-subtitle"><span id="memberCountLabel"><?php echo $serverMembers !== null ? count($serverMembers) : 482; ?></span> people registered in the system</p>
        </div>
        <button type="button" class="cmis-btn-gold" data-bs-toggle="modal" data-bs-target="#addMemberModal">
          <i class="bi bi-person-plus me-2"></i>Add Member
        </button>
      </div>
    </div>
  </header>

  <!--MAIN-->
  <main class="container-fluid px-3 px-lg-4 cmis-main">

    <!-- FILTER BAR -->
    <div class="cmis-card cmis-filter-card reveal" data-reveal-order="1">
      <div class="cmis-filter-row">
        <div class="cmis-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="searchInput" class="cmis-search-input" placeholder="Search by name, phone, or email…">
        </div>

        <select id="statusFilter" class="cmis-select">
          <option value="">All statuses</option>
          <option value="Member">Member</option>
          <option value="Adherent">Adherent</option>
          <option value="Visitor">Visitor</option>
        </select>

        <select id="parishFilter" class="cmis-select">
          <option value="">All parishes</option>
          <option value="Kingston">Kingston</option>
          <option value="St. Andrew">St. Andrew</option>
          <option value="St. Thomas">St. Thomas</option>
          <option value="Portland">Portland</option>
          <option value="St. Mary">St. Mary</option>
          <option value="St. Ann">St. Ann</option>
          <option value="Trelawny">Trelawny</option>
          <option value="St. James">St. James</option>
          <option value="Hanover">Hanover</option>
          <option value="Westmoreland">Westmoreland</option>
          <option value="St. Elizabeth">St. Elizabeth</option>
          <option value="Manchester">Manchester</option>
          <option value="Clarendon">Clarendon</option>
          <option value="St. Catherine">St. Catherine</option>
        </select>

        <button type="button" class="cmis-btn-ghost ms-auto" id="exportBtn">
          <i class="bi bi-download me-2"></i>Export
        </button>
      </div>
    </div>

    <!-- MEMBERS TABLE -->
    <div class="cmis-card cmis-table-card reveal" data-reveal-order="2">
      <div class="table-responsive">
        <table class="cmis-table">
          <thead>
            <tr>
              <th>Member</th>
              <th>Status</th>
              <th>Parish</th>
              <th>Phone</th>
              <th>Date Joined</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="membersTableBody">
            <!-- Rows injected by members.js -->
          </tbody>
        </table>
      </div>

      <div class="cmis-table-footer">
        <p class="cmis-table-count" id="resultsCount">Showing 0 of 0 members</p>
        <nav aria-label="Members pagination">
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

  <!--TOAST (success feedback)-->
  <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1080;">
    <div id="successToast" class="toast cmis-toast" role="status" aria-live="polite" aria-atomic="true">
      <div class="d-flex align-items-center">
        <div class="toast-body d-flex align-items-center">
          <i class="bi bi-check-circle-fill me-2"></i>
          <span id="toastMessage">Member added successfully.</span>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!--ADD MEMBER MODAL — 3-step wizard-->
  <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content cmis-modal">

        <div class="modal-header cmis-modal-header">
          <div>
            <h2 class="modal-title cmis-card-title" id="addMemberModalLabel"><span id="memberModalTitleText">Add New Member</span></h2>
            <p class="cmis-card-subtitle mb-0">Step <span id="stepNumLabel">1</span> of 3</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Step indicator -->
        <div class="cmis-stepper" aria-hidden="true">
          <div class="cmis-step is-active" data-step-indicator="1">
            <span class="cmis-step-dot">1</span>
            <span class="cmis-step-label">Personal</span>
          </div>
          <div class="cmis-step-line"></div>
          <div class="cmis-step" data-step-indicator="2">
            <span class="cmis-step-dot">2</span>
            <span class="cmis-step-label">Contact</span>
          </div>
          <div class="cmis-step-line"></div>
          <div class="cmis-step" data-step-indicator="3">
            <span class="cmis-step-dot">3</span>
            <span class="cmis-step-label">Next of Kin</span>
          </div>
        </div>

        <form id="addMemberForm" novalidate>
          <input type="hidden" name="mem_id" id="formMemId" value="">
          <input type="hidden" name="nk_id" id="formNkId" value="">

          <div class="modal-body cmis-modal-body">

            <!-- STEP 1: Personal Information -->
            <fieldset class="cmis-step-panel is-active" data-step="1">
              <legend class="cmis-step-heading">Personal Information</legend>

              <div class="cmis-avatar-upload">
                <div class="cmis-avatar-preview" id="avatarPreview">
                  <i class="bi bi-person"></i>
                </div>
                <div>
                  <label class="cmis-upload-btn" for="avatarInput">
                    <i class="bi bi-camera me-2"></i>Upload Photo
                  </label>
                  <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png" hidden>
                  <p class="cmis-upload-hint">JPG or PNG, up to 5MB.</p>
                </div>
              </div>

              <label class="cmis-field-label">Name</label>
              <div class="row g-2 mb-3">
                <div class="col-5">
                  <input type="text" class="cmis-input" name="first_name" placeholder="First name" required>
                </div>
                <div class="col-2">
                  <input type="text" class="cmis-input" name="mid_init" placeholder="M.I." maxlength="2">
                </div>
                <div class="col-5">
                  <input type="text" class="cmis-input" name="last_name" placeholder="Last name" required>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="cmis-field-label">Date of Birth</label>
                  <input type="date" class="cmis-input" name="dob" required>
                </div>
                <div class="col-md-6">
                  <label class="cmis-field-label">Date Joined</label>
                  <input type="date" class="cmis-input" name="date_joined" required>
                </div>
              </div>

              <label class="cmis-field-label">Gender</label>
              <div class="cmis-radio-row mb-3">
                <label class="cmis-radio-pill">
                  <input type="radio" name="gender" value="Male" checked> Male
                </label>
                <label class="cmis-radio-pill">
                  <input type="radio" name="gender" value="Female"> Female
                </label>
              </div>

              <label class="cmis-field-label">Status</label>
              <div class="cmis-radio-row mb-3">
                <label class="cmis-radio-pill">
                  <input type="radio" name="status" value="Member" checked> Member
                </label>
                <label class="cmis-radio-pill">
                  <input type="radio" name="status" value="Adherent"> Adherent
                </label>
                <label class="cmis-radio-pill">
                  <input type="radio" name="status" value="Visitor"> Visitor
                </label>
              </div>

              <label class="cmis-field-label">Passing Date <span class="cmis-optional">(optional)</span></label>
              <input type="date" class="cmis-input" name="passing_date">
            </fieldset>

            <!-- STEP 2: Contact Information -->
            <fieldset class="cmis-step-panel" data-step="2">
              <legend class="cmis-step-heading">Contact Information</legend>

              <label class="cmis-field-label">Address</label>
              <input type="text" class="cmis-input mb-2" name="address_1" placeholder="Address line 1" required>
              <input type="text" class="cmis-input mb-3" name="address_2" placeholder="Address line 2 (optional)">

              <label class="cmis-field-label">Parish</label>
              <select class="cmis-input mb-3" name="parish" required>
                <option value="" disabled selected>Select parish</option>
                <option>Kingston</option>
                <option>St. Andrew</option>
                <option>St. Thomas</option>
                <option>Portland</option>
                <option>St. Mary</option>
                <option>St. Ann</option>
                <option>Trelawny</option>
                <option>St. James</option>
                <option>Hanover</option>
                <option>Westmoreland</option>
                <option>St. Elizabeth</option>
                <option>Manchester</option>
                <option>Clarendon</option>
                <option>St. Catherine</option>
              </select>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="cmis-field-label">Phone Number</label>
                  <input type="tel" class="cmis-input phone-mask" name="telephone" placeholder="(876) XXX-XXXX" required>
                </div>
                <div class="col-md-6">
                  <label class="cmis-field-label">Email</label>
                  <input type="email" class="cmis-input" name="email" placeholder="name@example.com" required>
                </div>
              </div>
            </fieldset>

            <!-- STEP 3: Next of Kin -->
            <fieldset class="cmis-step-panel" data-step="3">
              <legend class="cmis-step-heading">Next of Kin</legend>

              <label class="cmis-field-label">Name</label>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <input type="text" class="cmis-input" name="nk_first_name" placeholder="First name" required>
                </div>
                <div class="col-6">
                  <input type="text" class="cmis-input" name="nk_last_name" placeholder="Last name" required>
                </div>
              </div>

              <label class="cmis-field-label">Relation</label>
              <select class="cmis-input mb-3" name="nk_relation" required>
                <option value="" disabled selected>Select relation</option>
                <option>Spouse</option>
                <option>Child</option>
                <option>Sibling</option>
                <option>Parent</option>
                <option>Friend</option>
              </select>

              <label class="cmis-field-label">Address</label>
              <input type="text" class="cmis-input mb-2" name="nk_address_1" placeholder="Address line 1" required>
              <input type="text" class="cmis-input mb-3" name="nk_address_2" placeholder="Address line 2 (optional)">

              <label class="cmis-field-label">Parish</label>
              <select class="cmis-input mb-3" name="nk_parish" required>
                <option value="" disabled selected>Select parish</option>
                <option>Kingston</option>
                <option>St. Andrew</option>
                <option>St. Thomas</option>
                <option>Portland</option>
                <option>St. Mary</option>
                <option>St. Ann</option>
                <option>Trelawny</option>
                <option>St. James</option>
                <option>Hanover</option>
                <option>Westmoreland</option>
                <option>St. Elizabeth</option>
                <option>Manchester</option>
                <option>Clarendon</option>
                <option>St. Catherine</option>
              </select>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="cmis-field-label">Phone Number</label>
                  <input type="tel" class="cmis-input phone-mask" name="nk_telephone" placeholder="(876) XXX-XXXX" required>
                </div>
                <div class="col-md-6">
                  <label class="cmis-field-label">Email</label>
                  <input type="email" class="cmis-input" name="nk_email" placeholder="name@example.com" required>
                </div>
              </div>
            </fieldset>

          </div>

          <div class="modal-footer cmis-modal-footer">
            <button type="button" class="cmis-btn-ghost" id="backBtn" disabled>
              <i class="bi bi-arrow-left me-2"></i>Back
            </button>
            <button type="button" class="cmis-btn-primary" id="nextBtn">
              Next<i class="bi bi-arrow-right ms-2"></i>
            </button>
            <button type="submit" class="cmis-btn-gold d-none" id="saveBtn">
              <i class="bi bi-check2 me-2"></i><span id="saveBtnLabel">Save Member</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!--VIEW MEMBER MODAL — read-only summary-->
  <div class="modal fade" id="viewMemberModal" tabindex="-1" aria-labelledby="viewMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content cmis-modal">

        <div class="modal-header cmis-modal-header">
          <h2 class="modal-title cmis-card-title" id="viewMemberModalLabel">Member Details</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body cmis-modal-body text-center">
          <div class="cmis-avatar-preview mx-auto mb-3" id="viewAvatar" style="width:88px;height:88px;font-size:1.8rem;">
            <i class="bi bi-person"></i>
          </div>

          <h3 class="cmis-step-heading mb-1" id="viewName">—</h3>
          <p class="mb-4"><span class="cmis-tag cmis-tag--green" id="viewStatus">—</span></p>

          <div class="text-start">
            <p class="cmis-field-label mb-1">Date of Birth</p>
            <p class="mb-3" id="viewDob">—</p>

            <p class="cmis-field-label mb-1">Next of Kin</p>
            <p class="mb-0 fw-semibold" id="viewNkName">—</p>
            <p class="cmis-card-subtitle mb-0" id="viewNkContact">—</p>
          </div>
        </div>

        <div class="modal-footer cmis-modal-footer justify-content-end">
          <button type="button" class="cmis-btn-ghost" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const serverMembers = <?php echo $serverMembers !== null ? json_encode($serverMembers) : 'null'; ?>;
  </script>

  <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.9/dist/inputmask.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="scripts/members.js"></script>
</body>

</html>