
document.addEventListener('DOMContentLoaded', () => {
  setGreetingAndDate();
  animateStatCounts();
  initNavbarScrollState();
  initAttendanceChart();
  initMinistryChart();
});

/*Greeting + date*/
function setGreetingAndDate() {
  const now = new Date();
  const hour = now.getHours();

  let greetingWord = 'Good evening';
  if (hour < 12) greetingWord = 'Good morning';
  else if (hour < 17) greetingWord = 'Good afternoon';

  // TODO: replace 'Sherene' with the logged-in user's first name
  const greetingEl = document.getElementById('greeting');
  if (greetingEl) greetingEl.textContent = `${greetingWord}, Sherene`;

  const dateEl = document.getElementById('todayDate');
  if (dateEl) {
    dateEl.textContent = now.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  }
}

/* Stat card count-up animation*/
function animateStatCounts() {
  const counters = document.querySelectorAll('.cmis-count');

  counters.forEach((el) => {
    const target = parseInt(el.dataset.target, 10) || 0;
    const duration = 1100; // ms
    const start = performance.now();

    function tick(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent = Math.round(eased * target).toLocaleString();

      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = target.toLocaleString();
    }

    requestAnimationFrame(tick);
  });
}

/*Navbar shadow on scroll*/
function initNavbarScrollState() {
  const nav = document.getElementById('mainNav');
  if (!nav) return;

  const update = () => {
    if (window.scrollY > 8) nav.classList.add('is-scrolled');
    else nav.classList.remove('is-scrolled');
  };

  update();
  window.addEventListener('scroll', update, { passive: true });
}

/* Attendance trend chart*/
function initAttendanceChart() {
  const ctx = document.getElementById('attendanceChart');
  if (!ctx || typeof Chart === 'undefined') return;

  // TODO: replace with real data from `headcount_attendance` joined
  // to `events` (event_type = 'church_service'), grouped by week.
  const labels = ['May 18', 'May 25', 'Jun 1', 'Jun 8', 'Jun 15', 'Jun 22', 'Jun 29', 'Jul 6'];
  const data = [332, 341, 358, 349, 362, 371, 340, 356];

  const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, 'rgba(31, 75, 63, 0.28)');
  gradient.addColorStop(1, 'rgba(31, 75, 63, 0.0)');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Attendance',
        data,
        borderColor: '#1F4B3F',
        backgroundColor: gradient,
        borderWidth: 2.5,
        pointRadius: 3,
        pointBackgroundColor: '#1F4B3F',
        pointBorderColor: '#fff',
        pointBorderWidth: 1.5,
        tension: 0.35,
        fill: true,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1E2A26',
          titleFont: { family: 'Inter', weight: '600' },
          bodyFont: { family: 'IBM Plex Mono' },
          padding: 10,
          cornerRadius: 8,
          displayColors: false,
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#6B776F', font: { family: 'Inter', size: 11 } },
        },
        y: {
          grid: { color: '#E7E1D2' },
          ticks: { color: '#6B776F', font: { family: 'IBM Plex Mono', size: 11 } },
          beginAtZero: false,
        },
      },
    },
  });
}

/* Ministry distribution donut chart*/
function initMinistryChart() {
  const ctx = document.getElementById('ministryChart');
  if (!ctx || typeof Chart === 'undefined') return;

  // TODO: replace with a grouped count from `ministry_members` joined
  // to `ministries`.
  const labels = ['Choir', 'Ushers', 'Youth', "Men's Fellowship", "Women's Fellowship", 'Sunday School'];
  const data = [42, 28, 65, 34, 58, 30];
  const colors = ['#1F4B3F', '#C9A227', '#7A2E3A', '#6B776F', '#4E7A6A', '#E0C25F'];

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors,
        borderColor: '#FFFFFF',
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: '#4A5551',
            font: { family: 'Inter', size: 11 },
            padding: 12,
            usePointStyle: true,
            pointStyle: 'circle',
          },
        },
        tooltip: {
          backgroundColor: '#1E2A26',
          bodyFont: { family: 'IBM Plex Mono' },
          padding: 10,
          cornerRadius: 8,
        },
      },
    },
  });
}
