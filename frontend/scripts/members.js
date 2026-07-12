const PAGE_SIZE = 8;
let currentPage = 1;
let filteredMembers = [];

const fallbackMembers = [
  { mem_id: 1, first_name: 'Andrea', last_name: 'Whyte', status: 'Member', parish: 'Kingston', telephone: '(876) 555-0132', email: 'andrea.whyte@example.com', date_joined: '2019-03-14' },
  { mem_id: 2, first_name: 'Michael', last_name: 'Brown', status: 'Member', parish: 'St. Andrew', telephone: '(876) 555-0187', email: 'michael.brown@example.com', date_joined: '2020-07-02' },
  { mem_id: 3, first_name: 'Kadeen', last_name: 'Campbell', status: 'Adherent', parish: 'St. Catherine', telephone: '(876) 555-0298', email: 'kadeen.c@example.com', date_joined: '2022-11-20' },
  { mem_id: 4, first_name: 'Omar', last_name: 'Reid', status: 'Visitor', parish: 'Clarendon', telephone: '(876) 555-0341', email: 'omar.reid@example.com', date_joined: '2024-01-08' },
  { mem_id: 5, first_name: 'Sherene', last_name: 'Campbell', status: 'Member', parish: 'St. Andrew', telephone: '(876) 555-0110', email: 'sherene.c@example.com', date_joined: '2016-05-30' },
  { mem_id: 6, first_name: 'Tamika', last_name: 'Grant', status: 'Member', parish: 'St. James', telephone: '(876) 555-0456', email: 'tamika.grant@example.com', date_joined: '2018-09-12' },
  { mem_id: 7, first_name: 'Devon', last_name: 'Lewis', status: 'Adherent', parish: 'Manchester', telephone: '(876) 555-0578', email: 'devon.lewis@example.com', date_joined: '2021-02-27' },
  { mem_id: 8, first_name: 'Paulette', last_name: 'Morris', status: 'Member', parish: 'Portland', telephone: '(876) 555-0623', email: 'paulette.morris@example.com', date_joined: '2015-12-01' },
  { mem_id: 9, first_name: 'Rohan', last_name: 'Facey', status: 'Visitor', parish: 'St. Thomas', telephone: '(876) 555-0734', email: 'rohan.facey@example.com', date_joined: '2024-04-19' },
  { mem_id: 10, first_name: 'Latoya', last_name: 'Powell', status: 'Member', parish: 'Westmoreland', telephone: '(876) 555-0812', email: 'latoya.powell@example.com', date_joined: '2017-06-06' },
  { mem_id: 11, first_name: 'Garfield', last_name: 'Bailey', status: 'Adherent', parish: 'St. Ann', telephone: '(876) 555-0945', email: 'garfield.bailey@example.com', date_joined: '2023-08-15' },
  { mem_id: 12, first_name: 'Nordia', last_name: 'Thompson', status: 'Member', parish: 'Hanover', telephone: '(876) 555-1023', email: 'nordia.t@example.com', date_joined: '2019-10-03' },
  { mem_id: 13, first_name: 'Kemar', last_name: 'Anderson', status: 'Member', parish: 'Trelawny', telephone: '(876) 555-1176', email: 'kemar.a@example.com', date_joined: '2020-01-22' },
  { mem_id: 14, first_name: 'Simone', last_name: 'Clarke', status: 'Visitor', parish: 'St. Elizabeth', telephone: '(876) 555-1289', email: 'simone.clarke@example.com', date_joined: '2024-06-11' },
];

let allMembers = (typeof serverMembers !== 'undefined' && Array.isArray(serverMembers) && serverMembers.length)
  ? serverMembers
  : fallbackMembers;

const avatarPalette = ['#1F4B3F', '#C9A227', '#7A2E3A', '#6B776F', '#4E7A6A'];
const statusTagClass = { Member: 'cmis-tag--green', Adherent: 'cmis-tag--gold', Visitor: 'cmis-tag--slate' };

// Simple string hash so avatar colors are stable whether mem_id is a
// mock integer or a real Supabase UUID.
function hashToIndex(value, modulo) {
  const str = String(value);
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = (hash * 31 + str.charCodeAt(i)) >>> 0;
  }
  return hash % modulo;
}

document.addEventListener('DOMContentLoaded', () => {
  filteredMembers = [...allMembers];
  renderTable();
  bindFilterEvents();
  bindRowActionEvents();
  initStepper();
  initAvatarUpload();
  bindFormSubmit();
  setPhoneMask();
});

function setPhoneMask() {
  Inputmask("(876)999-9999").mask(document.querySelectorAll(".phone-mask"));
}

/*Rendering*/
function renderTable() {
  const tbody = document.getElementById('membersTableBody');
  const start = (currentPage - 1) * PAGE_SIZE;
  const pageItems = filteredMembers.slice(start, start + PAGE_SIZE);

  tbody.innerHTML = pageItems.map((m) => {
    const initials = (m.first_name[0] + m.last_name[0]).toUpperCase();
    const color = avatarPalette[hashToIndex(m.mem_id, avatarPalette.length)];
    const tagClass = statusTagClass[m.status] || 'cmis-tag--slate';
    const formattedDate = new Date(m.date_joined).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

    const avatarHtml = m.avatar_url
      ? `<img src="${m.avatar_url}" alt="" class="cmis-member-avatar" style="object-fit:cover;">`
      : `<span class="cmis-member-avatar" style="background:${color}">${initials}</span>`;

    return `
      <tr>
        <td>
          <div class="cmis-member-cell">
            ${avatarHtml}
            <div>
              <div class="cmis-member-name">${m.first_name} ${m.last_name}</div>
              <div class="cmis-member-sub">${m.email}</div>
            </div>
          </div>
        </td>
        <td><span class="cmis-tag ${tagClass}">${m.status}</span></td>
        <td>${m.parish}</td>
        <td>${m.telephone}</td>
        <td>${formattedDate}</td>
        <td>
          <div class="cmis-row-actions">
            <button class="cmis-icon-btn js-view-member" type="button" data-mem-id="${m.mem_id}" title="View member"><i class="bi bi-eye"></i></button>
            <button class="cmis-icon-btn js-edit-member" type="button" data-mem-id="${m.mem_id}" title="Edit member"><i class="bi bi-pencil"></i></button>
            <button class="cmis-icon-btn js-delete-member" type="button" data-mem-id="${m.mem_id}" data-mem-name="${m.first_name} ${m.last_name}" title="Delete member"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>`;
  }).join('');

  document.getElementById('resultsCount').textContent =
    `Showing ${pageItems.length ? start + 1 : 0}–${start + pageItems.length} of ${filteredMembers.length} members`;
  document.getElementById('memberCountLabel').textContent = allMembers.length;

  renderPagination();
}

function renderPagination() {
  const totalPages = Math.max(1, Math.ceil(filteredMembers.length / PAGE_SIZE));
  const pagination = document.getElementById('pagination');

  let html = `
    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
      <button class="page-link" data-page="${currentPage - 1}">Prev</button>
    </li>`;

  for (let i = 1; i <= totalPages; i++) {
    html += `
      <li class="page-item ${i === currentPage ? 'active' : ''}">
        <button class="page-link" data-page="${i}">${i}</button>
      </li>`;
  }

  html += `
    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
      <button class="page-link" data-page="${currentPage + 1}">Next</button>
    </li>`;

  pagination.innerHTML = html;

  pagination.querySelectorAll('.page-link').forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.dataset.page, 10);
      const totalPages = Math.max(1, Math.ceil(filteredMembers.length / PAGE_SIZE));
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
  const parishFilter = document.getElementById('parishFilter');

  const applyFilters = () => {
    const q = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value.toLowerCase();
    const parish = parishFilter.value.toLowerCase();

    filteredMembers = allMembers.filter((m) => {
      const matchesSearch = !q ||
        `${m.first_name} ${m.last_name}`.toLowerCase().includes(q) ||
        m.telephone.toLowerCase().includes(q) ||
        m.email.toLowerCase().includes(q);
      const matchesStatus = !status || m.status === status;
      const matchesParish = !parish || m.parish === parish;
      return matchesSearch && matchesStatus && matchesParish;
    });

    currentPage = 1;
    renderTable();
  };

  searchInput.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);
  parishFilter.addEventListener('change', applyFilters);

  document.getElementById('exportBtn').addEventListener('click', () => {
    // TODO: implement real CSV export once wired to Supabase
    showToast('Export is not yet connected to real data.');
  });
}

/* Row actions — view / edit / delete (event delegation)*/
function bindRowActionEvents() {
  document.getElementById('membersTableBody').addEventListener('click', (e) => {
    const viewBtn = e.target.closest('.js-view-member');
    const editBtn = e.target.closest('.js-edit-member');
    const deleteBtn = e.target.closest('.js-delete-member');

    if (viewBtn) openViewModal(viewBtn.dataset.memId);
    else if (editBtn) openEditModal(editBtn.dataset.memId);
    else if (deleteBtn) handleDeleteMember(deleteBtn.dataset.memId, deleteBtn.dataset.memName);
  });
}

async function fetchMemberDetail(memId) {
  const response = await fetch(`../backend/memberActions.php?mem_id=${encodeURIComponent(memId)}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.errors?.[0] || 'Could not load member.');
  return result.member;
}

/*View modal*/
async function openViewModal(memId) {
  let member;
  try {
    member = await fetchMemberDetail(memId);
  } catch (err) {
    showToast(err.message);
    return;
  }

  const avatarEl = document.getElementById('viewAvatar');
  avatarEl.innerHTML = member.avatar_url
    ? `<img src="${member.avatar_url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
    : '<i class="bi bi-person"></i>';

  document.getElementById('viewName').textContent = `${member.first_name} ${member.last_name}`;
  document.getElementById('viewStatus').textContent = member.status
    ? member.status.charAt(0).toUpperCase() + member.status.slice(1)
    : '—';
  document.getElementById('viewDob').textContent = member.dob
    ? new Date(member.dob).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    : '—';

  const nk = member.next_of_kin;
  document.getElementById('viewNkName').textContent = nk ? `${nk.first_name} ${nk.last_name} (${nk.relation})` : '—';
  document.getElementById('viewNkContact').textContent = nk ? `${nk.telephone} · ${nk.email}` : '—';

  new bootstrap.Modal(document.getElementById('viewMemberModal')).show();
}

/*Edit modal — reuses the Add Member wizard, pre-filled*/
async function openEditModal(memId) {
  let member;
  try {
    member = await fetchMemberDetail(memId);
  } catch (err) {
    showToast(err.message);
    return;
  }

  const form = document.getElementById('addMemberForm');
  form.reset();

  document.getElementById('formMemId').value = member.mem_id;
  document.getElementById('formNkId').value = member.next_of_kin ? member.next_of_kin.nk_id : '';

  form.first_name.value = member.first_name || '';
  form.mid_init.value = member.mid_init || '';
  form.last_name.value = member.last_name || '';
  form.dob.value = member.dob || '';
  form.date_joined.value = member.date_joined || '';
  form.passing_date.value = member.passing_date || '';
  setRadioValue(form.gender, member.gender);
  setRadioValue(form.status, member.status ? member.status.charAt(0).toUpperCase() + member.status.slice(1) : '');

  form.address_1.value = member.address_1 || '';
  form.address_2.value = member.address_2 || '';
  form.parish.value = member.parish || '';
  form.telephone.value = member.telephone || '';
  form.email.value = member.email || '';

  const nk = member.next_of_kin || {};
  form.nk_first_name.value = nk.first_name || '';
  form.nk_last_name.value = nk.last_name || '';
  form.nk_relation.value = nk.relation || '';
  form.nk_address_1.value = nk.address_1 || '';
  form.nk_address_2.value = nk.address_2 || '';
  form.nk_parish.value = nk.parish || '';
  form.nk_telephone.value = nk.telephone || '';
  form.nk_email.value = nk.email || '';

  const preview = document.getElementById('avatarPreview');
  preview.innerHTML = member.avatar_url
    ? `<img src="${member.avatar_url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
    : '<i class="bi bi-person"></i>';

  document.getElementById('memberModalTitleText').textContent = 'Edit Member';
  document.getElementById('saveBtnLabel').textContent = 'Save Changes';

  goToStep(1);
  new bootstrap.Modal(document.getElementById('addMemberModal')).show();
}

function setRadioValue(radioNodeList, value) {
  if (!radioNodeList || !value) return;
  for (const radio of radioNodeList) {
    radio.checked = radio.value === value;
  }
}

function resetModalToAddMode() {
  document.getElementById('formMemId').value = '';
  document.getElementById('formNkId').value = '';
  document.getElementById('memberModalTitleText').textContent = 'Add New Member';
  document.getElementById('saveBtnLabel').textContent = 'Save Member';
}

/*Delete*/
async function handleDeleteMember(memId, memName) {
  if (!confirm(`Delete ${memName}? This cannot be undone.`)) return;

  try {
    const response = await fetch('../backend/memberActions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `_method=DELETE&mem_id=${encodeURIComponent(memId)}`,
    });
    const result = await response.json();

    if (!result.success) {
      showToast(result.errors?.[0] || 'Could not delete member.');
      return;
    }

    allMembers = allMembers.filter((m) => String(m.mem_id) !== String(memId));
    filteredMembers = filteredMembers.filter((m) => String(m.mem_id) !== String(memId));
    renderTable();
    showToast(`${memName} was deleted.`);
  } catch (err) {
    showToast('Something went wrong. Please check your connection and try again.');
  }
}

/*Add Member modal — step wizard */
let currentStep = 1;
const TOTAL_STEPS = 3;

function initStepper() {
  document.getElementById('nextBtn').addEventListener('click', () => {
    const activePanel = document.querySelector(`.cmis-step-panel[data-step="${currentStep}"]`);
    if (!validateStep(activePanel)) return;

    if (currentStep < TOTAL_STEPS) {
      goToStep(currentStep + 1);
    }
  });

  document.getElementById('backBtn').addEventListener('click', () => {
    if (currentStep > 1) goToStep(currentStep - 1);
  });

  document.getElementById('addMemberModal').addEventListener('hidden.bs.modal', () => {
    // Reset wizard state when modal closes
    document.getElementById('addMemberForm').reset();
    resetAvatarPreview();
    resetModalToAddMode();
    goToStep(1);
  });
}

function goToStep(step) {
  currentStep = step;

  document.querySelectorAll('.cmis-step-panel').forEach((panel) => {
    panel.classList.toggle('is-active', parseInt(panel.dataset.step, 10) === step);
  });

  document.querySelectorAll('.cmis-step').forEach((el) => {
    const n = parseInt(el.dataset.stepIndicator, 10);
    el.classList.remove('is-active', 'is-complete');
    if (n === step) el.classList.add('is-active');
    else if (n < step) el.classList.add('is-complete');
  });

  document.getElementById('stepNumLabel').textContent = step;
  document.getElementById('backBtn').disabled = step === 1;

  const nextBtn = document.getElementById('nextBtn');
  const saveBtn = document.getElementById('saveBtn');
  if (step === TOTAL_STEPS) {
    nextBtn.classList.add('d-none');
    saveBtn.classList.remove('d-none');
  } else {
    nextBtn.classList.remove('d-none');
    saveBtn.classList.add('d-none');
  }
}

function validateStep(panelEl) {
  const requiredInputs = panelEl.querySelectorAll('[required]');
  for (const input of requiredInputs) {
    if (!input.checkValidity()) {
      input.reportValidity();
      return false;
    }
  }
  return true;
}

/*Avatar upload preview*/
function initAvatarUpload() {
  const input = document.getElementById('avatarInput');
  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      const preview = document.getElementById('avatarPreview');
      preview.innerHTML = `<img src="${e.target.result}" alt="Member photo preview">`;
    };
    reader.readAsDataURL(file);

  });
}

function resetAvatarPreview() {
  const preview = document.getElementById('avatarPreview');
  preview.innerHTML = '<i class="bi bi-person"></i>';
}

function bindFormSubmit() {
  document.getElementById('addMemberForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const lastPanel = document.querySelector(`.cmis-step-panel[data-step="${TOTAL_STEPS}"]`);
    if (!validateStep(lastPanel)) return;

    const formData = new FormData(e.target);
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;

    const memId = document.getElementById('formMemId').value;
    const isEditing = Boolean(memId);

    try {
      const response = await fetch('../backend/memberActions.php', {
        method: 'POST',
        body: formData,
      });
      const result = await response.json();

      if (!result.success) {
        showModalErrors(result.errors);
        return;
      }

      if (isEditing) {
        const index = allMembers.findIndex((m) => String(m.mem_id) === String(memId));
        if (index !== -1) allMembers[index] = result.member;
      } else {
        allMembers.unshift(result.member);
      }
      filteredMembers = [...allMembers];
      currentPage = 1;
      renderTable();

      bootstrap.Modal.getInstance(document.getElementById('addMemberModal')).hide();

      const name = `${result.member.first_name} ${result.member.last_name}`;
      const warningText = (result.warnings && result.warnings.length)
        ? ` (${result.warnings.join(' ')})`
        : '';
      showToast(`${name} was ${isEditing ? 'updated' : 'added'} successfully.${warningText}`);
    } catch (err) {
      showModalErrors(['Something went wrong. Please check your connection and try again.']);
    } finally {
      saveBtn.disabled = false;
    }
  });
}

function showModalErrors(errors) {
  let box = document.getElementById('modalErrorBox');
  if (!box) {
    box = document.createElement('div');
    box.id = 'modalErrorBox';
    box.className = 'cmis-login-error mb-3';
    document.querySelector('.cmis-modal-body').prepend(box);
  }
  box.innerHTML = errors.map((msg) => `<div>${msg}</div>`).join('');
}

/*Toast helpe */
function showToast(message) {
  document.getElementById('toastMessage').textContent = message;
  const toastEl = document.getElementById('successToast');
  new bootstrap.Toast(toastEl, { delay: 3500 }).show();
}