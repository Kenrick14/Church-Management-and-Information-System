<?php
session_start();

// Already logged in? Skip straight to the dashboard.
if (!empty($_SESSION['supabase_access_token'])) {
  header('Location: dashboard.php');
  exit();
}

// Pick up anything loginVal.php left for us, then clear it so a
// refresh of this page doesn't keep re-showing a stale error.
$error = $_SESSION['login_error'] ?? null;
$oldEmail = $_SESSION['old_email'] ?? '';
unset($_SESSION['login_error'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In · CMIS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles/dashboard.css">
</head>

<body class="cmis-login-body">

  <div class="cmis-login-wrap">
    <div class="cmis-login-arches" aria-hidden="true">
      <svg viewBox="0 0 480 140" preserveAspectRatio="xMidYMax slice">
        <path d="M0,140 L0,55 A40,55 0 0 1 80,55 L80,140" />
        <path d="M80,140 L80,55 A40,55 0 0 1 160,55 L160,140" />
        <path d="M160,140 L160,55 A40,55 0 0 1 240,55 L240,140" />
        <path d="M240,140 L240,55 A40,55 0 0 1 320,55 L320,140" />
        <path d="M320,140 L320,55 A40,55 0 0 1 400,55 L400,140" />
        <path d="M400,140 L400,55 A40,55 0 0 1 480,55 L480,140" />
      </svg>
    </div>

    <div class="cmis-login-card">
      <div class="cmis-login-brand">
        <span class="cmis-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 32 32" width="34" height="34">
            <path d="M4 30V13 A12 12 0 0 1 28 13 V30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
          </svg>
        </span>
        <div>
          <div class="cmis-brand-name">The Church</div>
          <div class="cmis-brand-sub">CMIS</div>
        </div>
      </div>

      <h1 class="cmis-login-title">Welcome back</h1>
      <p class="cmis-login-subtitle">Sign in to manage your congregation.</p>

      <?php if ($error): ?>
        <div class="cmis-login-error"><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form action="../backend/loginVal.php" method="POST" novalidate>
        <label class="cmis-field-label">Email</label>
        <input
          type="email"
          name="email"
          class="cmis-input mb-3"
          placeholder="name@example.com"
          value="<?php echo htmlspecialchars($oldEmail); ?>"
          required
          autofocus>

        <label class="cmis-field-label">Password</label>
        <input type="password" name="password" class="cmis-input mb-4" placeholder="••••••••" required>

        <button type="submit" class="cmis-btn-primary w-100 justify-content-center">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>
    </div>
  </div>

</body>

</html>