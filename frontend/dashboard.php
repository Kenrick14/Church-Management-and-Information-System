<?php require_once __DIR__ . '/../backend/auth_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMIS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles/dashboard.css">
</head>

<body>
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
          <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-grid-1x2 me-1"></i>Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="members.php"><i class="bi bi-people me-1"></i>Members</a></li>
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

  <!--HERO -->
  <header class="cmis-hero">
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
      <div class="cmis-hero-inner reveal" data-reveal-order="0">
        <p class="cmis-eyebrow"><i class="bi bi-calendar3 me-2"></i><span id="todayDate">Loading date…</span></p>
        <h1 class="cmis-greeting">
          <span id="greetingWord">Good day</span>, <?php echo htmlspecialchars($_SESSION['user_display_name'] ?? 'there'); ?>
        </h1>
        <p class="cmis-hero-subtitle">Here's what's happening across the church family this week.</p>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT-->
  <main class="container-fluid px-3 px-lg-4 cmis-main">

    <!-- STAT CARDS -->
    <section class="row g-3 g-lg-4 cmis-stats-row" aria-label="Key statistics">

      <div class="col-sm-6 col-xl-3">
        <div class="cmis-stat-card reveal" data-reveal-order="1">
          <div class="cmis-stat-icon cmis-stat-icon--green"><i class="bi bi-people-fill"></i></div>
          <p class="cmis-stat-label">Total Members</p>
          <p class="cmis-stat-value"><span class="cmis-count" data-target="482">0</span></p>
          <p class="cmis-stat-trend cmis-trend-up"><i class="bi bi-arrow-up-short"></i>12 new this month</p>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="cmis-stat-card reveal" data-reveal-order="2">
          <div class="cmis-stat-icon cmis-stat-icon--gold"><i class="bi bi-diagram-3-fill"></i></div>
          <p class="cmis-stat-label">Active Ministries</p>
          <p class="cmis-stat-value"><span class="cmis-count" data-target="14">0</span></p>
          <p class="cmis-stat-trend cmis-trend-up"><i class="bi bi-arrow-up-short"></i>1 new this quarter</p>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="cmis-stat-card reveal" data-reveal-order="3">
          <div class="cmis-stat-icon cmis-stat-icon--wine"><i class="bi bi-clipboard-data-fill"></i></div>
          <p class="cmis-stat-label">Avg. Weekly Attendance</p>
          <p class="cmis-stat-value"><span class="cmis-count" data-target="356">0</span></p>
          <p class="cmis-stat-trend cmis-trend-down"><i class="bi bi-arrow-down-short"></i>3% vs last month</p>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="cmis-stat-card reveal" data-reveal-order="4">
          <div class="cmis-stat-icon cmis-stat-icon--slate"><i class="bi bi-calendar-event-fill"></i></div>
          <p class="cmis-stat-label">Upcoming Events</p>
          <p class="cmis-stat-value"><span class="cmis-count" data-target="7">0</span></p>
          <p class="cmis-stat-trend cmis-trend-neutral"><i class="bi bi-dot"></i>Next 30 days</p>
        </div>
      </div>
    </section>

    <!-- CHARTS -->
    <section class="row g-3 g-lg-4 cmis-charts-row" aria-label="Attendance and ministry charts">

      <div class="col-xl-8">
        <div class="cmis-card reveal" data-reveal-order="5">
          <div class="cmis-card-header">
            <div>
              <h2 class="cmis-card-title">Attendance Trend</h2>
              <p class="cmis-card-subtitle">Weekly service attendance, last 8 weeks</p>
            </div>
            <div class="dropdown">
              <button class="cmis-filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Last 8 weeks
              </button>
              <ul class="dropdown-menu dropdown-menu-end cmis-dropdown">
                <li><a class="dropdown-item" href="#">Last 8 weeks</a></li>
                <li><a class="dropdown-item" href="#">Last quarter</a></li>
                <li><a class="dropdown-item" href="#">Year to date</a></li>
              </ul>
            </div>
          </div>
          <div class="cmis-chart-wrap">
            <canvas id="attendanceChart" role="img" aria-label="Line chart of weekly attendance over the last eight weeks"></canvas>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="cmis-card reveal" data-reveal-order="6">
          <div class="cmis-card-header">
            <div>
              <h2 class="cmis-card-title">Ministry Distribution</h2>
              <p class="cmis-card-subtitle">Members by ministry</p>
            </div>
          </div>
          <div class="cmis-chart-wrap cmis-chart-wrap--donut">
            <canvas id="ministryChart" role="img" aria-label="Doughnut chart of members distributed across ministries"></canvas>
          </div>
        </div>
      </div>
    </section>

    <!-- ALERTS / QUICK ACTIONS -->
    <section class="row g-3 g-lg-4 cmis-lower-row" aria-label="Upcoming events and quick actions">

      <div class="col-xl-7">
        <div class="cmis-card reveal" data-reveal-order="7">
          <div class="cmis-card-header">
            <div>
              <h2 class="cmis-card-title">Upcoming Events</h2>
              <p class="cmis-card-subtitle">Weddings, baptisms, meetings &amp; more</p>
            </div>
            <a href="events.php" class="cmis-link-btn">View all <i class="bi bi-arrow-right ms-1"></i></a>
          </div>

          <ul class="cmis-ledger-list">
            <li class="cmis-ledger-item">
              <span class="cmis-ledger-date"><span class="cmis-ledger-day">12</span><span class="cmis-ledger-mon">JUL</span></span>
              <span class="cmis-ledger-body">
                <span class="cmis-ledger-title">Youth Ministry Meeting</span>
                <span class="cmis-ledger-meta">Ministry Meeting · 6:00 PM</span>
              </span>
              <span class="cmis-tag cmis-tag--green">Ministry</span>
            </li>
            <li class="cmis-ledger-item">
              <span class="cmis-ledger-date"><span class="cmis-ledger-day">18</span><span class="cmis-ledger-mon">JUL</span></span>
              <span class="cmis-ledger-body">
                <span class="cmis-ledger-title">Wedding — Michael &amp; Andrea Whyte</span>
                <span class="cmis-ledger-meta">Sanctuary · 2:00 PM</span>
              </span>
              <span class="cmis-tag cmis-tag--gold">Wedding</span>
            </li>
            <li class="cmis-ledger-item">
              <span class="cmis-ledger-date"><span class="cmis-ledger-day">20</span><span class="cmis-ledger-mon">JUL</span></span>
              <span class="cmis-ledger-body">
                <span class="cmis-ledger-title">Baptism — 3 candidates</span>
                <span class="cmis-ledger-meta">Sunday AM Service</span>
              </span>
              <span class="cmis-tag cmis-tag--slate">Baptism</span>
            </li>
            <li class="cmis-ledger-item">
              <span class="cmis-ledger-date"><span class="cmis-ledger-day">25</span><span class="cmis-ledger-mon">JUL</span></span>
              <span class="cmis-ledger-body">
                <span class="cmis-ledger-title">25th Anniversary — Pastor &amp; Mrs. Brown</span>
                <span class="cmis-ledger-meta">Fellowship Hall · 4:00 PM</span>
              </span>
              <span class="cmis-tag cmis-tag--wine">Anniversary</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="cmis-card reveal" data-reveal-order="8">
          <div class="cmis-card-header">
            <div>
              <h2 class="cmis-card-title">Quick Actions</h2>
              <p class="cmis-card-subtitle">Common tasks</p>
            </div>
          </div>

          <div class="cmis-quick-grid">
            <a href="members.php" class="cmis-quick-btn">
              <i class="bi bi-person-plus"></i>
              <span>Add Member</span>
            </a>
            <a href="attendance.php" class="cmis-quick-btn">
              <i class="bi bi-check2-square"></i>
              <span>Record Attendance</span>
            </a>
            <a href="events.php" class="cmis-quick-btn">
              <i class="bi bi-calendar-plus"></i>
              <span>Log Event</span>
            </a>
            <a href="reports.php" class="cmis-quick-btn">
              <i class="bi bi-file-earmark-bar-graph"></i>
              <span>View Reports</span>
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="cmis-footer">
    <div class="container-fluid px-3 px-lg-4">
      <p class="mb-0">Church Management &amp; Information System</p>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script src="scripts/dashboard.js"></script>
</body>

</html>
<?php
// Small helper used above for the navbar avatar initials.
// (Defined at the bottom so it doesn't interrupt the HTML flow above;
//  PHP hoists function declarations within the same file.)
function cmis_initials(string $name): string
{
  $parts = preg_split('/\s+/', trim($name));
  $initials = '';
  foreach (array_slice($parts, 0, 2) as $p) {
    $initials .= mb_strtoupper(mb_substr($p, 0, 1));
  }
  return $initials ?: '?';
}
?>