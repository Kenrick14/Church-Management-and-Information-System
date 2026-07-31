const PAGE_SIZE = 8;
let currentPage = 1;
let filteredMinistries = [];

const fallbackMinistries = [
  { min_id: 1, name: 'Youth Ministry', description: 'Ages 13-25 fellowship and outreach', member_count: 24, is_active: true },
  { min_id: 2, name: 'Choir', description: 'Sunday worship music ministry', member_count: 18, is_active: true },
  { min_id: 3, name: "Women's Fellowship", description: null, member_count: 31, is_active: false },
];

let allMinistries = (typeof serverMinistries !== 'undefined' && Array.isArray(serverMinistries) && serverMinistries.length)
  ? serverMinistries
  : fallbackMinistries;

let rosterMemberChoices = null;

document.addEventListener('DOMContentLoaded', () => {
  filteredMinistries = [...allMinistries];
  renderTable();
  bindFilterEvents();
  bindRowActionEvents();
  bindFormSubmit();
  bindRosterEvents();

  document.getElementById('addMinistryModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('addMinistryForm').reset();
    resetModalToAddMode();
  });
});

/*Rendering*/
function renderTable() {
  const tbody = document.getElementById('ministriesTableBody');
  const start = (currentPage - 1) * PAGE_SIZE;
  const pageItems = filteredMinistries.slice(start, start + PAGE_SIZE);

  tbody.innerHTML = pageItems.map((m) => {
    const statusTag = m.is_active
      ? '<span class="cmis-tag cmis-tag--green">Active</span>'
      : '<span class="cmis-tag cmis-tag--slate">Inactive</span>';

    const toggleBtn = m.is_active
      ? `<button class="cmis-icon-btn js-deactivate-ministry" type="button" data-min-id="${m.min_id}" data-min-name="${m.name}" title="Deactivate ministry"><i class="bi bi-slash-circle"></i></button>`
      : `<button class="cmis-icon-btn js-activate-ministry" type="button" data-min-id="${m.min_id}" data-min-name="${m.name}" title="Reactivate ministry"><i class="bi bi-arrow-counterclockwise"></i></button>`;

    return `
      <tr>
        <td class="cmis-member-name">${m.name}</td>
        <td>${m.description || '—'}</td>
        <td><span class="cmis-tag cmis-tag--gold">${m.member_count ?? 0}</span></td>
        <td>${statusTag}</td>
        <td>
          <div class="cmis-row-actions">
            <button class="cmis-icon-btn js-view-ministry" type="button" data-min-id="${m.min_id}" title="View ministry"><i class="bi bi-eye"></i></button>
            <button class="cmis-icon-btn js-edit-ministry" type="button" data-min-id="${m.min_id}" title="Edit ministry"><i class="bi bi-pencil"></i></button>
            ${toggleBtn}
          </div>
        </td>
      </tr>`;
  }).join('');

  document.getElementById('resultsCount').textContent =
    `Showing ${pageItems.length ? start + 1 : 0}–${start + pageItems.length} of ${filteredMinistries.length} ministries`;

  renderPagination();
}

function renderPagination() {
  const totalPages = Math.max(1, Math.ceil(filteredMinistries.length / PAGE_SIZE));
  const pagination = document.getElementById('pagination');

  let html = `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><button class="page-link" data-page="${currentPage - 1}">Prev</button></li>`;
  for (let i = 1; i <= totalPages; i++) {
    html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><button class="page-link" data-page="${i}">${i}</button></li>`;
  }
  html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><button class="page-link" data-page="${currentPage + 1}">Next</button></li>`;
  pagination.innerHTML = html;

  pagination.querySelectorAll('.page-link').forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.dataset.page, 10);
      const totalPages = Math.max(1, Math.ceil(filteredMinistries.length / PAGE_SIZE));
      if (page < 1 || page > totalPages) return;
      currentPage = page;
      renderTable();
    });
  });
}

/*Filtering*/
function bindFilterEvents() {
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');

  const applyFilters = () => {
    const q = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value; // '', 'active', 'inactive'

    filteredMinistries = allMinistries.filter((m) => {
      const matchesSearch = !q || m.name.toLowerCase().includes(q);
      const matchesStatus = !status || (status === 'active' ? m.is_active : !m.is_active);
      return matchesSearch && matchesStatus;
    });

    currentPage = 1;
    renderTable();
  };

  searchInput.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);
}

/*Row actions — view / edit / deactivate / activate*/
function bindRowActionEvents() {
  document.getElementById('ministriesTableBody').addEventListener('click', (e) => {
    const viewBtn = e.target.closest('.js-view-ministry');
    const editBtn = e.target.closest('.js-edit-ministry');
    const deactivateBtn = e.target.closest('.js-deactivate-ministry');
    const activateBtn = e.target.closest('.js-activate-ministry');

    if (viewBtn) openViewModal(viewBtn.dataset.minId);
    else if (editBtn) openEditModal(editBtn.dataset.minId);
    else if (deactivateBtn) handleToggleStatus(deactivateBtn.dataset.minId, deactivateBtn.dataset.minName, 'DEACTIVATE');
    else if (activateBtn) handleToggleStatus(activateBtn.dataset.minId, activateBtn.dataset.minName, 'ACTIVATE');
  });
}

async function fetchMinistryDetail(minId) {
  const response = await fetch(`../backend/ministryActions.php?min_id=${encodeURIComponent(minId)}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.errors?.[0] || 'Could not load ministry.');
  return result.ministry;
}

function updateMinistryInList(ministry) {
  const index = allMinistries.findIndex((m) => String(m.min_id) === String(ministry.min_id));
  if (index !== -1) allMinistries[index] = ministry;
  else allMinistries.unshift(ministry);
  filteredMinistries = [...allMinistries];
  renderTable();
}

/*View modal (+ roster)*/
async function openViewModal(minId) {
  let ministry;
  try {
    ministry = await fetchMinistryDetail(minId);
  } catch (err) {
    showToast(err.message);
    return;
  }

  renderViewModal(ministry);
  initRosterMemberPicker();
  new bootstrap.Modal(document.getElementById('viewMinistryModal')).show();
}

function renderViewModal(ministry) {
  document.getElementById('viewMinistryMinId').value = ministry.min_id;
  document.getElementById('viewMinistryName').textContent = ministry.name;
  document.getElementById('viewMinistryDescription').textContent = ministry.description || 'No description yet.';

  const roster = ministry.ministry_members || [];
  document.getElementById('viewMinistryRosterCount').textContent = roster.length;

  const rosterList = document.getElementById('viewMinistryRoster');
  rosterList.innerHTML = roster.length
    ? roster.map((rm) => `
        <li class="cmis-ledger-item">
          <span class="cmis-ledger-body">
            <span class="cmis-ledger-title">${rm.members.first_name} ${rm.members.last_name}</span>
          </span>
          <select class="cmis-select js-roster-role-select" data-ministry-member-id="${rm.ministry_member_id}" style="max-width:160px;">
            ${ministryRolesLookup.map((r) => `<option value="${r.role_id}" ${r.role_id === rm.role_id ? 'selected' : ''}>${r.name}</option>`).join('')}
          </select>
          <button class="cmis-icon-btn js-remove-roster-member" type="button" data-ministry-member-id="${rm.ministry_member_id}" title="Remove from roster">
            <i class="bi bi-x-lg"></i>
          </button>
        </li>`).join('')
    : '<li class="cmis-ledger-item"><span class="cmis-ledger-body"><span class="cmis-ledger-title">No members yet</span></span></li>';
}

function initRosterMemberPicker() {
  if (rosterMemberChoices) {
    rosterMemberChoices.destroy();
    rosterMemberChoices = null;
  }

  const selectEl = document.getElementById('rosterMemberSelect');
  rosterMemberChoices = new Choices(selectEl, {
    choices: membersLookup.map((m) => ({ value: m.mem_id, label: `${m.first_name} ${m.last_name}` })),
    searchEnabled: true,
    searchPlaceholderValue: 'Search for a person…',
    itemSelectText: '',
    shouldSort: false,
    placeholder: true,
    placeholderValue: 'Select person',
  });

  document.getElementById('rosterRoleSelect').value = '';
}

/*Roster management — add / remove / change role*/
function bindRosterEvents() {
  document.getElementById('addRosterMemberBtn').addEventListener('click', async () => {
    const minId = document.getElementById('viewMinistryMinId').value;
    const memId = document.getElementById('rosterMemberSelect').value;
    const roleId = document.getElementById('rosterRoleSelect').value;

    if (!memId) { showToast('Please select a person to add.'); return; }
    if (!roleId) { showToast('Please select a role.'); return; }

    try {
      const response = await fetch('../backend/ministryRosterActions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `min_id=${encodeURIComponent(minId)}&mem_id=${encodeURIComponent(memId)}&role_id=${encodeURIComponent(roleId)}`,
      });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not add this person.');
        return;
      }

      renderViewModal(result.ministry);
      initRosterMemberPicker();
      updateMinistryInList(result.ministry);
      showToast('Added to roster.');
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    }
  });

  document.getElementById('viewMinistryRoster').addEventListener('click', async (e) => {
    const removeBtn = e.target.closest('.js-remove-roster-member');
    if (!removeBtn) return;

    if (!confirm('Remove this person from the ministry roster?')) return;

    const minId = document.getElementById('viewMinistryMinId').value;
    const ministryMemberId = removeBtn.dataset.ministryMemberId;

    try {
      const response = await fetch('../backend/ministryRosterActions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `_method=DELETE&min_id=${encodeURIComponent(minId)}&ministry_member_id=${encodeURIComponent(ministryMemberId)}`,
      });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not remove this person.');
        return;
      }

      renderViewModal(result.ministry);
      initRosterMemberPicker();
      updateMinistryInList(result.ministry);
      showToast('Removed from roster.');
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    }
  });

  document.getElementById('viewMinistryRoster').addEventListener('change', async (e) => {
    const roleSelect = e.target.closest('.js-roster-role-select');
    if (!roleSelect) return;

    const minId = document.getElementById('viewMinistryMinId').value;
    const ministryMemberId = roleSelect.dataset.ministryMemberId;
    const roleId = roleSelect.value;

    try {
      const response = await fetch('../backend/ministryRosterActions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `min_id=${encodeURIComponent(minId)}&ministry_member_id=${encodeURIComponent(ministryMemberId)}&role_id=${encodeURIComponent(roleId)}`,
      });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not update their role.');
        return;
      }

      updateMinistryInList(result.ministry);
      showToast('Role updated.');
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    }
  });
}

/*Edit modal*/
async function openEditModal(minId) {
  let ministry;
  try {
    ministry = await fetchMinistryDetail(minId);
  } catch (err) {
    showToast(err.message);
    return;
  }

  const form = document.getElementById('addMinistryForm');
  form.reset();
  document.getElementById('formMinId').value = ministry.min_id;
  form.name.value = ministry.name;
  form.description.value = ministry.description || '';

  document.getElementById('ministryModalTitleText').textContent = 'Edit Ministry';
  document.getElementById('saveMinistryBtnLabel').textContent = 'Save Changes';

  new bootstrap.Modal(document.getElementById('addMinistryModal')).show();
}

function resetModalToAddMode() {
  document.getElementById('formMinId').value = '';
  document.getElementById('ministryModalTitleText').textContent = 'Add Ministry';
  document.getElementById('saveMinistryBtnLabel').textContent = 'Save Ministry';
}

/*Deactivate / Activate*/
async function handleToggleStatus(minId, minName, action) {
  const confirmText = action === 'DEACTIVATE'
    ? `Deactivate "${minName}"? It'll be hidden from new event/roster assignments, but its history is kept — you can reactivate it anytime.`
    : `Reactivate "${minName}"?`;

  if (!confirm(confirmText)) return;

  try {
    const response = await fetch('../backend/ministryActions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `_method=${action}&min_id=${encodeURIComponent(minId)}`,
    });
    const result = await response.json();

    if (!result.success) {
      showToast(result.errors?.[0] || 'Could not update ministry status.');
      return;
    }

    updateMinistryInList(result.ministry);
    showToast(`"${minName}" was ${action === 'DEACTIVATE' ? 'deactivated' : 'reactivated'}.`);
  } catch (err) {
    showToast('Something went wrong. Please check your connection and try again.');
  }
}

/*Submit (create / edit)*/
function bindFormSubmit() {
  document.getElementById('addMinistryForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('saveMinistryBtn');
    saveBtn.disabled = true;

    const formData = new FormData(e.target);
    const minId = document.getElementById('formMinId').value;
    const isEditing = Boolean(minId);

    try {
      const response = await fetch('../backend/ministryActions.php', { method: 'POST', body: formData });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not save ministry.');
        return;
      }

      updateMinistryInList(result.ministry);

      bootstrap.Modal.getInstance(document.getElementById('addMinistryModal')).hide();
      showToast(`"${result.ministry.name}" was ${isEditing ? 'updated' : 'added'} successfully.`);
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