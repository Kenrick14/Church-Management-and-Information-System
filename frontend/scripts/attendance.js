const HC_PAGE_SIZE = 8;
let hcCurrentPage = 1;
let hcFiltered = [];

const eventTypeLabels = {
  church_service: 'Church Service',
  ministry_meeting: 'Ministry Meeting',
};

const fallbackHeadcountEvents = [
  { event_id: 1, event_type: 'church_service', date: '2026-07-26', description: null, service_types: { name: 'Sunday Morning Service' }, ministries: null, headcount_attendance: { headcount_id: 1, attendee_count: 342 } },
  { event_id: 2, event_type: 'ministry_meeting', date: '2026-07-25', description: 'Weekly planning', service_types: null, ministries: { name: 'Youth Ministry' }, headcount_attendance: null },
];

let allHeadcountEvents = (typeof serverHeadcountEvents !== 'undefined' && Array.isArray(serverHeadcountEvents) && serverHeadcountEvents.length)
  ? serverHeadcountEvents
  : fallbackHeadcountEvents;

/* Sunday School state */
const SS_PAGE_SIZE = 8;
let ssCurrentPage = 1;
let ssFiltered = [];

const fallbackSundaySchoolEvents = [
  { event_id: 101, date: '2026-07-26', description: 'Weekly Sunday School', sunday_school_attendance: [{ mem_id: 1 }, { mem_id: 2 }] },
  { event_id: 102, date: '2026-07-19', description: 'Weekly Sunday School', sunday_school_attendance: [] },
];

let allSundaySchoolEvents = (typeof serverSundaySchoolEvents !== 'undefined' && Array.isArray(serverSundaySchoolEvents) && serverSundaySchoolEvents.length)
  ? serverSundaySchoolEvents
  : fallbackSundaySchoolEvents;

const safeChildrenLookup = (typeof childrenLookup !== 'undefined' && Array.isArray(childrenLookup)) ? childrenLookup : [];

/* Vestry Hours state */
const VH_PAGE_SIZE = 8;
let vhCurrentPage = 1;
let vhFiltered = [];

const fallbackVestryHours = [
  { vestry_id: 1, date: '2026-07-20', hours_logged: 2.5, duties: 'Home visits', members: { first_name: 'Andrew', last_name: 'Campbell' } },
];

let allVestryHours = (typeof serverVestryHours !== 'undefined' && Array.isArray(serverVestryHours) && serverVestryHours.length)
  ? serverVestryHours
  : fallbackVestryHours;

document.addEventListener('DOMContentLoaded', () => {
  hcFiltered = [...allHeadcountEvents];
  hcRenderTable();
  hcBindFilterEvents();
  hcBindRowActionEvents();
  hcBindFormSubmit();

  ssFiltered = [...allSundaySchoolEvents];
  ssRenderTable();
  ssBindFilterEvents();
  ssBindRowActionEvents();
  ssBindFormSubmit();

  vhFiltered = [...allVestryHours];
  vhRenderTable();
  vhBindFilterEvents();
  vhBindRowActionEvents();
  vhBindFormSubmit();

  document.getElementById('vhModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('vhForm').reset();
    vhResetModalToAddMode();
  });
});

/* Rendering */
function hcDescribeRelated(ev) {
  if (ev.event_type === 'ministry_meeting') return ev.ministries?.name || '—';
  if (ev.event_type === 'church_service') return ev.service_types?.name || '—';
  return '—';
}

function hcRenderTable() {
  const tbody = document.getElementById('hcTableBody');
  const start = (hcCurrentPage - 1) * HC_PAGE_SIZE;
  const pageItems = hcFiltered.slice(start, start + HC_PAGE_SIZE);

  tbody.innerHTML = pageItems.map((ev) => {
    const formattedDate = new Date(ev.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const typeLabel = eventTypeLabels[ev.event_type] || ev.event_type;
    const hc = ev.headcount_attendance;

    const countCell = hc
      ? `<span class="cmis-tag cmis-tag--green">${hc.attendee_count}</span>`
      : `<span class="cmis-tag cmis-tag--slate">Not recorded</span>`;

    const actionBtn = hc
      ? `<button class="cmis-icon-btn js-edit-hc" type="button" data-event-id="${ev.event_id}" title="Edit attendance"><i class="bi bi-pencil"></i></button>
         <button class="cmis-icon-btn js-delete-hc" type="button" data-headcount-id="${hc.headcount_id}" title="Remove record"><i class="bi bi-trash"></i></button>`
      : `<button class="cmis-icon-btn js-record-hc" type="button" data-event-id="${ev.event_id}" title="Record attendance"><i class="bi bi-plus-lg"></i></button>`;

    return `
      <tr>
        <td>${formattedDate}</td>
        <td><span class="cmis-tag cmis-tag--gold">${typeLabel}</span></td>
        <td>${hcDescribeRelated(ev)}</td>
        <td>${countCell}</td>
        <td><div class="cmis-row-actions">${actionBtn}</div></td>
      </tr>`;
  }).join('');

  document.getElementById('hcResultsCount').textContent =
    `Showing ${pageItems.length ? start + 1 : 0}–${start + pageItems.length} of ${hcFiltered.length} events`;

  hcRenderPagination();
}

function hcRenderPagination() {
  const totalPages = Math.max(1, Math.ceil(hcFiltered.length / HC_PAGE_SIZE));
  const pagination = document.getElementById('hcPagination');

  let html = `<li class="page-item ${hcCurrentPage === 1 ? 'disabled' : ''}"><button class="page-link" data-page="${hcCurrentPage - 1}">Prev</button></li>`;
  for (let i = 1; i <= totalPages; i++) {
    html += `<li class="page-item ${i === hcCurrentPage ? 'active' : ''}"><button class="page-link" data-page="${i}">${i}</button></li>`;
  }
  html += `<li class="page-item ${hcCurrentPage === totalPages ? 'disabled' : ''}"><button class="page-link" data-page="${hcCurrentPage + 1}">Next</button></li>`;
  pagination.innerHTML = html;

  pagination.querySelectorAll('.page-link').forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.dataset.page, 10);
      const totalPages = Math.max(1, Math.ceil(hcFiltered.length / HC_PAGE_SIZE));
      if (page < 1 || page > totalPages) return;
      hcCurrentPage = page;
      hcRenderTable();
    });
  });
}

/* Filtering */
function hcBindFilterEvents() {
  const searchInput = document.getElementById('hcSearchInput');
  const typeFilter = document.getElementById('hcTypeFilter');
  const recordedFilter = document.getElementById('hcRecordedFilter');

  const applyFilters = () => {
    const q = searchInput.value.trim().toLowerCase();
    const type = typeFilter.value;
    const recorded = recordedFilter.value; // '', 'recorded', 'unrecorded'

    hcFiltered = allHeadcountEvents.filter((ev) => {
      const matchesSearch = !q || (ev.description || '').toLowerCase().includes(q) || hcDescribeRelated(ev).toLowerCase().includes(q);
      const matchesType = !type || ev.event_type === type;
      const matchesRecorded = !recorded
        || (recorded === 'recorded' && ev.headcount_attendance)
        || (recorded === 'unrecorded' && !ev.headcount_attendance);
      return matchesSearch && matchesType && matchesRecorded;
    });

    hcCurrentPage = 1;
    hcRenderTable();
  };

  searchInput.addEventListener('input', applyFilters);
  typeFilter.addEventListener('change', applyFilters);
  recordedFilter.addEventListener('change', applyFilters);
}

/* Row actions — record / edit / delete */
function hcBindRowActionEvents() {
  document.getElementById('hcTableBody').addEventListener('click', (e) => {
    const recordBtn = e.target.closest('.js-record-hc');
    const editBtn = e.target.closest('.js-edit-hc');
    const deleteBtn = e.target.closest('.js-delete-hc');

    if (recordBtn) hcOpenModal(recordBtn.dataset.eventId);
    else if (editBtn) hcOpenModal(editBtn.dataset.eventId);
    else if (deleteBtn) hcHandleDelete(deleteBtn.dataset.headcountId);
  });
}

function hcOpenModal(eventId) {
  const ev = allHeadcountEvents.find((e) => String(e.event_id) === String(eventId));
  if (!ev) return;

  const hc = ev.headcount_attendance;
  const formattedDate = new Date(ev.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

  document.getElementById('hcFormEventId').value = ev.event_id;
  document.getElementById('hcFormHeadcountId').value = hc ? hc.headcount_id : '';
  document.getElementById('hcModalEventType').textContent = eventTypeLabels[ev.event_type] || ev.event_type;
  document.getElementById('hcModalEventContext').textContent = `${hcDescribeRelated(ev)} — ${formattedDate}`;
  document.getElementById('hcAttendeeCount').value = hc ? hc.attendee_count : '';
  document.getElementById('hcModalTitleText').textContent = hc ? 'Edit Attendance' : 'Record Attendance';

  new bootstrap.Modal(document.getElementById('headcountModal')).show();
}

async function hcHandleDelete(headcountId) {
  if (!confirm('Remove this attendance record? The event itself will stay — only the count is removed.')) return;

  try {
    const response = await fetch('../backend/headcountActions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `_method=DELETE&headcount_id=${encodeURIComponent(headcountId)}`,
    });
    const result = await response.json();

    if (!result.success) {
      showToast(result.errors?.[0] || 'Could not remove this record.');
      return;
    }

    const ev = allHeadcountEvents.find((e) => e.headcount_attendance && String(e.headcount_attendance.headcount_id) === String(headcountId));
    if (ev) ev.headcount_attendance = null;
    hcFiltered = [...allHeadcountEvents];
    hcRenderTable();
    showToast('Attendance record removed.');
  } catch (err) {
    showToast('Something went wrong. Please check your connection and try again.');
  }
}

/* Submit */
function hcBindFormSubmit() {
  document.getElementById('headcountForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('hcSaveBtn');
    saveBtn.disabled = true;

    const formData = new FormData(e.target);

    try {
      const response = await fetch('../backend/headcountActions.php', { method: 'POST', body: formData });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not save attendance.');
        return;
      }

      const eventId = document.getElementById('hcFormEventId').value;
      const ev = allHeadcountEvents.find((e) => String(e.event_id) === String(eventId));
      if (ev) ev.headcount_attendance = result.headcount;

      hcFiltered = [...allHeadcountEvents];
      hcRenderTable();

      bootstrap.Modal.getInstance(document.getElementById('headcountModal')).hide();
      showToast('Attendance saved successfully.');
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    } finally {
      saveBtn.disabled = false;
    }
  });
}

/* SUNDAY SCHOOL TAB */

function ssRenderTable() {
  const tbody = document.getElementById('ssTableBody');
  const start = (ssCurrentPage - 1) * SS_PAGE_SIZE;
  const pageItems = ssFiltered.slice(start, start + SS_PAGE_SIZE);

  tbody.innerHTML = pageItems.map((ev) => {
    const formattedDate = new Date(ev.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const presentCount = (ev.sunday_school_attendance || []).length;

    return `
      <tr>
        <td>${formattedDate}</td>
        <td>${ev.description || '—'}</td>
        <td><span class="cmis-tag cmis-tag--green">${presentCount}</span></td>
        <td>
          <div class="cmis-row-actions">
            <button class="cmis-icon-btn js-record-ss" type="button" data-event-id="${ev.event_id}" title="Take attendance"><i class="bi bi-pencil"></i></button>
          </div>
        </td>
      </tr>`;
  }).join('');

  document.getElementById('ssResultsCount').textContent =
    `Showing ${pageItems.length ? start + 1 : 0}–${start + pageItems.length} of ${ssFiltered.length} sessions`;

  ssRenderPagination();
}

function ssRenderPagination() {
  const totalPages = Math.max(1, Math.ceil(ssFiltered.length / SS_PAGE_SIZE));
  const pagination = document.getElementById('ssPagination');

  let html = `<li class="page-item ${ssCurrentPage === 1 ? 'disabled' : ''}"><button class="page-link" data-page="${ssCurrentPage - 1}">Prev</button></li>`;
  for (let i = 1; i <= totalPages; i++) {
    html += `<li class="page-item ${i === ssCurrentPage ? 'active' : ''}"><button class="page-link" data-page="${i}">${i}</button></li>`;
  }
  html += `<li class="page-item ${ssCurrentPage === totalPages ? 'disabled' : ''}"><button class="page-link" data-page="${ssCurrentPage + 1}">Next</button></li>`;
  pagination.innerHTML = html;

  pagination.querySelectorAll('.page-link').forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.dataset.page, 10);
      const totalPages = Math.max(1, Math.ceil(ssFiltered.length / SS_PAGE_SIZE));
      if (page < 1 || page > totalPages) return;
      ssCurrentPage = page;
      ssRenderTable();
    });
  });
}

function ssBindFilterEvents() {
  const searchInput = document.getElementById('ssSearchInput');
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim().toLowerCase();
    ssFiltered = allSundaySchoolEvents.filter((ev) => !q || (ev.description || '').toLowerCase().includes(q));
    ssCurrentPage = 1;
    ssRenderTable();
  });
}

function ssBindRowActionEvents() {
  document.getElementById('ssTableBody').addEventListener('click', (e) => {
    const btn = e.target.closest('.js-record-ss');
    if (btn) ssOpenModal(btn.dataset.eventId);
  });
}

async function ssOpenModal(eventId) {
  const ev = allSundaySchoolEvents.find((e) => String(e.event_id) === String(eventId));
  if (!ev) return;

  let presentMemIds = [];
  try {
    const response = await fetch(`../backend/sundaySchoolActions.php?event_id=${encodeURIComponent(eventId)}`);
    const result = await response.json();
    if (result.success) presentMemIds = result.mem_ids.map(String);
  } catch (err) {
    // Non-fatal — the checklist just opens with nothing pre-checked.
  }

  const formattedDate = new Date(ev.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  document.getElementById('ssFormEventId').value = ev.event_id;
  document.getElementById('ssModalEventContext').textContent = `${ev.description || 'Sunday School'} — ${formattedDate}`;
  document.getElementById('ssChecklistSearch').value = '';

  ssRenderChecklist(presentMemIds);

  document.getElementById('ssChecklistSearch').oninput = (e) => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('#ssChecklist .ss-checklist-row').forEach((row) => {
      row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
  };

  new bootstrap.Modal(document.getElementById('ssModal')).show();
}

function ssRenderChecklist(presentMemIds) {
  const container = document.getElementById('ssChecklist');

  container.innerHTML = safeChildrenLookup.map((child) => {
    const checked = presentMemIds.includes(String(child.mem_id));
    const fullName = `${child.first_name} ${child.last_name}`;
    return `
      <label class="ss-checklist-row d-flex align-items-center gap-2 py-2 border-bottom" data-name="${fullName.toLowerCase()}">
        <input type="checkbox" name="mem_ids[]" value="${child.mem_id}" class="js-ss-checkbox" ${checked ? 'checked' : ''}>
        <span>${fullName}</span>
      </label>`;
  }).join('');

  ssUpdateCheckedCount();
  container.querySelectorAll('.js-ss-checkbox').forEach((cb) => {
    cb.addEventListener('change', ssUpdateCheckedCount);
  });
}

function ssUpdateCheckedCount() {
  const count = document.querySelectorAll('#ssChecklist .js-ss-checkbox:checked').length;
  document.getElementById('ssCheckedCount').textContent = count;
}

function ssBindFormSubmit() {
  document.getElementById('ssForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('ssSaveBtn');
    saveBtn.disabled = true;

    const formData = new FormData(e.target);

    try {
      const response = await fetch('../backend/sundaySchoolActions.php', { method: 'POST', body: formData });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not save attendance.');
        return;
      }

      const eventId = document.getElementById('ssFormEventId').value;
      const ev = allSundaySchoolEvents.find((e) => String(e.event_id) === String(eventId));
      if (ev) ev.sunday_school_attendance = result.mem_ids.map((id) => ({ mem_id: id }));

      ssFiltered = [...allSundaySchoolEvents];
      ssRenderTable();

      bootstrap.Modal.getInstance(document.getElementById('ssModal')).hide();

      const warningText = (result.warnings && result.warnings.length) ? ` (${result.warnings.join(' ')})` : '';
      showToast(`Attendance saved.${warningText}`);
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    } finally {
      saveBtn.disabled = false;
    }
  });
}

/* VESTRY HOURS TAB*/

function vhRenderTable() {
  const tbody = document.getElementById('vhTableBody');
  const start = (vhCurrentPage - 1) * VH_PAGE_SIZE;
  const pageItems = vhFiltered.slice(start, start + VH_PAGE_SIZE);

  tbody.innerHTML = pageItems.map((vh) => {
    const formattedDate = new Date(vh.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const name = vh.members ? `${vh.members.first_name} ${vh.members.last_name}` : '—';

    return `
      <tr>
        <td>${formattedDate}</td>
        <td>${name}</td>
        <td><span class="cmis-tag cmis-tag--gold">${vh.hours_logged}</span></td>
        <td>${vh.duties || '—'}</td>
        <td>
          <div class="cmis-row-actions">
            <button class="cmis-icon-btn js-edit-vh" type="button" data-vestry-id="${vh.vestry_id}" title="Edit entry"><i class="bi bi-pencil"></i></button>
            <button class="cmis-icon-btn js-delete-vh" type="button" data-vestry-id="${vh.vestry_id}" title="Delete entry"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>`;
  }).join('');

  document.getElementById('vhResultsCount').textContent =
    `Showing ${pageItems.length ? start + 1 : 0}–${start + pageItems.length} of ${vhFiltered.length} entries`;

  vhRenderPagination();
}

function vhRenderPagination() {
  const totalPages = Math.max(1, Math.ceil(vhFiltered.length / VH_PAGE_SIZE));
  const pagination = document.getElementById('vhPagination');

  let html = `<li class="page-item ${vhCurrentPage === 1 ? 'disabled' : ''}"><button class="page-link" data-page="${vhCurrentPage - 1}">Prev</button></li>`;
  for (let i = 1; i <= totalPages; i++) {
    html += `<li class="page-item ${i === vhCurrentPage ? 'active' : ''}"><button class="page-link" data-page="${i}">${i}</button></li>`;
  }
  html += `<li class="page-item ${vhCurrentPage === totalPages ? 'disabled' : ''}"><button class="page-link" data-page="${vhCurrentPage + 1}">Next</button></li>`;
  pagination.innerHTML = html;

  pagination.querySelectorAll('.page-link').forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.dataset.page, 10);
      const totalPages = Math.max(1, Math.ceil(vhFiltered.length / VH_PAGE_SIZE));
      if (page < 1 || page > totalPages) return;
      vhCurrentPage = page;
      vhRenderTable();
    });
  });
}

function vhBindFilterEvents() {
  const searchInput = document.getElementById('vhSearchInput');
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim().toLowerCase();
    vhFiltered = allVestryHours.filter((vh) => {
      const name = vh.members ? `${vh.members.first_name} ${vh.members.last_name}`.toLowerCase() : '';
      return !q || name.includes(q) || (vh.duties || '').toLowerCase().includes(q);
    });
    vhCurrentPage = 1;
    vhRenderTable();
  });
}

function vhBindRowActionEvents() {
  document.getElementById('vhTableBody').addEventListener('click', (e) => {
    const editBtn = e.target.closest('.js-edit-vh');
    const deleteBtn = e.target.closest('.js-delete-vh');
    if (editBtn) vhOpenEditModal(editBtn.dataset.vestryId);
    else if (deleteBtn) vhHandleDelete(deleteBtn.dataset.vestryId);
  });
}

function vhOpenEditModal(vestryId) {
  const vh = allVestryHours.find((v) => String(v.vestry_id) === String(vestryId));
  if (!vh) return;

  document.getElementById('vhFormVestryId').value = vh.vestry_id;
  document.getElementById('vhMemberSelect').value = vh.mem_id;
  document.getElementById('vhDate').value = vh.date;
  document.getElementById('vhHours').value = vh.hours_logged;
  document.getElementById('vhDuties').value = vh.duties || '';
  document.getElementById('vhModalTitleText').textContent = 'Edit Vestry Hours';
  document.getElementById('vhSaveBtnLabel').textContent = 'Save Changes';

  new bootstrap.Modal(document.getElementById('vhModal')).show();
}

function vhResetModalToAddMode() {
  document.getElementById('vhFormVestryId').value = '';
  document.getElementById('vhModalTitleText').textContent = 'Log Vestry Hours';
  document.getElementById('vhSaveBtnLabel').textContent = 'Save';
}

async function vhHandleDelete(vestryId) {
  if (!confirm('Delete this vestry hours entry? This cannot be undone.')) return;

  try {
    const response = await fetch('../backend/vestryHoursActions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `_method=DELETE&vestry_id=${encodeURIComponent(vestryId)}`,
    });
    const result = await response.json();

    if (!result.success) {
      showToast(result.errors?.[0] || 'Could not delete this entry.');
      return;
    }

    allVestryHours = allVestryHours.filter((v) => String(v.vestry_id) !== String(vestryId));
    vhFiltered = [...allVestryHours];
    vhRenderTable();
    showToast('Entry deleted.');
  } catch (err) {
    showToast('Something went wrong. Please check your connection and try again.');
  }
}

function vhBindFormSubmit() {
  document.getElementById('vhForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('vhSaveBtn');
    saveBtn.disabled = true;

    const formData = new FormData(e.target);
    const vestryId = document.getElementById('vhFormVestryId').value;
    const isEditing = Boolean(vestryId);

    try {
      const response = await fetch('../backend/vestryHoursActions.php', { method: 'POST', body: formData });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not save vestry hours.');
        return;
      }

      if (isEditing) {
        const index = allVestryHours.findIndex((v) => String(v.vestry_id) === String(vestryId));
        if (index !== -1) allVestryHours[index] = result.vestry_hours;
      } else {
        allVestryHours.unshift(result.vestry_hours);
      }
      vhFiltered = [...allVestryHours];
      vhCurrentPage = 1;
      vhRenderTable();

      bootstrap.Modal.getInstance(document.getElementById('vhModal')).hide();
      showToast(`Vestry hours ${isEditing ? 'updated' : 'logged'} successfully.`);
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    } finally {
      saveBtn.disabled = false;
    }
  });
}

/*Toast*/
function showToast(message) {
  document.getElementById('toastMessage').textContent = message;
  new bootstrap.Toast(document.getElementById('successToast'), { delay: 3500 }).show();
}