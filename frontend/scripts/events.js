const PAGE_SIZE = 8;
let currentPage = 1;
let filteredEvents = [];

const fallbackEvents = [
  { event_id: 1, event_type: 'church_service', date: '2026-07-19', description: 'Sunday morning worship', service_types: { name: 'Sunday AM' }, ministries: null, event_subjects: [] },
  { event_id: 2, event_type: 'ministry_meeting', date: '2026-07-12', description: 'Monthly planning meeting', service_types: null, ministries: { name: 'Youth Ministry' }, event_subjects: [] },
  { event_id: 3, event_type: 'wedding', date: '2026-07-18', description: 'Sanctuary, 2:00 PM', service_types: null, ministries: null, event_subjects: [
      { subject_role: 'groom', members: { first_name: 'Michael', last_name: 'Whyte' } },
      { subject_role: 'bride', members: { first_name: 'Andrea', last_name: 'Reid' } },
    ] },
];

// Mirrors SUBJECT_RULES in backend/eventActions.php — keep these in sync.
const SUBJECT_RULES = {
  wedding:     { min: 2, max: 2, labels: ['Groom', 'Bride'] },
  anniversary: { min: 2, max: 2, labels: ['Spouse 1', 'Spouse 2'] },
  birthday:    { min: 1, max: 1, labels: ['Celebrant'] },
  funeral:       { min: 1, max: 1, labels: ['Deceased'] },
  baptism:     { min: 1, max: null, labels: null }, // null labels = dynamic "Candidate #N"
};

let allEvents = (typeof serverEvents !== 'undefined' && Array.isArray(serverEvents) && serverEvents.length)
  ? serverEvents
  : fallbackEvents;

// Choices.js instances currently attached to subject-picker <select>s —
// tracked so we can destroy them cleanly before rebuilding the list
// (e.g. when the event type changes).
let subjectChoicesInstances = [];

document.addEventListener('DOMContentLoaded', () => {
  filteredEvents = [...allEvents];
  renderTable();
  bindFilterEvents();
  bindRowActionEvents();
  bindEventTypeChange();
  bindFormSubmit();
});

/* ---------------------------------------------------------------------
   Rendering
--------------------------------------------------------------------- */
function renderTable() {
  const tbody = document.getElementById('eventsTableBody');
  const start = (currentPage - 1) * PAGE_SIZE;
  const pageItems = filteredEvents.slice(start, start + PAGE_SIZE);

  tbody.innerHTML = pageItems.map((ev) => {
    const formattedDate = new Date(ev.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const typeLabel = eventTypeLabels[ev.event_type] || ev.event_type;
    const related = describeRelated(ev);

    return `
      <tr>
        <td>${formattedDate}</td>
        <td><span class="cmis-tag cmis-tag--green">${typeLabel}</span></td>
        <td>${ev.description || '—'}</td>
        <td>${related}</td>
        <td>
          <div class="cmis-row-actions">
            <button class="cmis-icon-btn js-view-event" type="button" data-event-id="${ev.event_id}" title="View event"><i class="bi bi-eye"></i></button>
            <button class="cmis-icon-btn js-edit-event" type="button" data-event-id="${ev.event_id}" title="Edit event"><i class="bi bi-pencil"></i></button>
            <button class="cmis-icon-btn js-delete-event" type="button" data-event-id="${ev.event_id}" data-event-label="${typeLabel} on ${formattedDate}" title="Delete event"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>`;
  }).join('');

  document.getElementById('resultsCount').textContent =
    `Showing ${pageItems.length ? start + 1 : 0}–${start + pageItems.length} of ${filteredEvents.length} events`;

  renderPagination();
}

function describeRelated(ev) {
  if (ev.event_type === 'ministry_meeting') return ev.ministries?.name || '—';
  if (ev.event_type === 'church_service') return ev.service_types?.name || '—';
  if (ev.event_subjects && ev.event_subjects.length) {
    return ev.event_subjects
      .map((s) => `${s.members.first_name} ${s.members.last_name}`)
      .join(', ');
  }
  return '—';
}

function renderPagination() {
  const totalPages = Math.max(1, Math.ceil(filteredEvents.length / PAGE_SIZE));
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
      const totalPages = Math.max(1, Math.ceil(filteredEvents.length / PAGE_SIZE));
      if (page < 1 || page > totalPages) return;
      currentPage = page;
      renderTable();
    });
  });
}

/* ---------------------------------------------------------------------
   Filtering
--------------------------------------------------------------------- */
function bindFilterEvents() {
  const searchInput = document.getElementById('searchInput');
  const typeFilter = document.getElementById('typeFilter');

  const applyFilters = () => {
    const q = searchInput.value.trim().toLowerCase();
    const type = typeFilter.value;

    filteredEvents = allEvents.filter((ev) => {
      const matchesSearch = !q || (ev.description || '').toLowerCase().includes(q);
      const matchesType = !type || ev.event_type === type;
      return matchesSearch && matchesType;
    });

    currentPage = 1;
    renderTable();
  };

  searchInput.addEventListener('input', applyFilters);
  typeFilter.addEventListener('change', applyFilters);
}

/* ---------------------------------------------------------------------
   Row actions — view
--------------------------------------------------------------------- */
function bindRowActionEvents() {
  document.getElementById('eventsTableBody').addEventListener('click', (e) => {
    const viewBtn = e.target.closest('.js-view-event');
    const editBtn = e.target.closest('.js-edit-event');
    const deleteBtn = e.target.closest('.js-delete-event');

    if (viewBtn) openViewModal(viewBtn.dataset.eventId);
    else if (editBtn) openEditModal(editBtn.dataset.eventId);
    else if (deleteBtn) handleDeleteEvent(deleteBtn.dataset.eventId, deleteBtn.dataset.eventLabel);
  });
}

async function fetchEventDetail(eventId) {
  const response = await fetch(`../backend/eventActions.php?event_id=${encodeURIComponent(eventId)}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.errors?.[0] || 'Could not load event.');
  return result.event;
}

async function openViewModal(eventId) {
  let event;
  try {
    event = await fetchEventDetail(eventId);
  } catch (err) {
    showToast(err.message);
    return;
  }

  document.getElementById('viewEventType').textContent = eventTypeLabels[event.event_type] || event.event_type;
  document.getElementById('viewEventDate').textContent = new Date(event.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  document.getElementById('viewEventDescription').textContent = event.description || '—';

  const relatedLabel = document.getElementById('viewEventRelatedLabel');
  if (event.event_type === 'ministry_meeting') {
    relatedLabel.textContent = 'Ministry';
    document.getElementById('viewEventRelated').textContent = event.ministries?.name || '—';
  } else if (event.event_type === 'church_service') {
    relatedLabel.textContent = 'Service Type';
    document.getElementById('viewEventRelated').textContent = event.service_types?.name || '—';
  } else if (event.event_subjects && event.event_subjects.length) {
    relatedLabel.textContent = 'People Involved';
    document.getElementById('viewEventRelated').textContent = event.event_subjects
      .map((s) => `${s.members.first_name} ${s.members.last_name} (${s.subject_role})`)
      .join(', ');
  } else {
    relatedLabel.textContent = 'Related';
    document.getElementById('viewEventRelated').textContent = '—';
  }

  new bootstrap.Modal(document.getElementById('viewEventModal')).show();
}

/* ---------------------------------------------------------------------
   Edit modal — reuses the Add Event form, event_type locked
--------------------------------------------------------------------- */
async function openEditModal(eventId) {
  let event;
  try {
    event = await fetchEventDetail(eventId);
  } catch (err) {
    showToast(err.message);
    return;
  }

  const form = document.getElementById('addEventForm');
  form.reset();

  document.getElementById('formEventId').value = event.event_id;
  form.date.value = event.date;
  form.description.value = event.description || '';

  // Lock the type selector — event_type can't change after creation
  // (see the comment in backend/eventActions.php for why). It's
  // `disabled`, not just visually styled, so it's also correctly
  // excluded from FormData on submit.
  const typeSelect = document.getElementById('eventTypeSelect');
  typeSelect.value = event.event_type;
  typeSelect.disabled = true;
  document.getElementById('eventTypeLockedHint').classList.remove('d-none');

  // Show the right conditional section for this type, same as the
  // Add flow, then fill in its value(s).
  updateFormForEventType(event.event_type, event.event_subjects || []);

  if (event.event_type === 'ministry_meeting') {
    form.min_id.value = event.min_id || '';
  } else if (event.event_type === 'church_service') {
    form.service_type_id.value = event.service_type_id || '';
  }

  document.getElementById('eventModalTitleText').textContent = 'Edit Event';
  document.getElementById('saveEventBtnLabel').textContent = 'Save Changes';

  new bootstrap.Modal(document.getElementById('addEventModal')).show();
}

function resetModalToAddMode() {
  document.getElementById('formEventId').value = '';
  document.getElementById('eventTypeSelect').disabled = false;
  document.getElementById('eventTypeLockedHint').classList.add('d-none');
  document.getElementById('eventModalTitleText').textContent = 'Add Event';
  document.getElementById('saveEventBtnLabel').textContent = 'Save Event';
}

/* ---------------------------------------------------------------------
   Add Event modal — dynamic fields based on event_type
--------------------------------------------------------------------- */
function bindEventTypeChange() {
  document.getElementById('eventTypeSelect').addEventListener('change', (e) => {
    updateFormForEventType(e.target.value);
  });

  document.getElementById('addSubjectBtn').addEventListener('click', () => {
    const type = document.getElementById('eventTypeSelect').value;
    addSubjectRow(type, document.querySelectorAll('#subjectsList .subject-row').length);
  });

  document.getElementById('addEventModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('addEventForm').reset();
    updateFormForEventType('');
    resetModalToAddMode();
  });
}

/**
 * @param {string} type
 * @param {Array} existingSubjects - when editing, the event's current
 *   event_subjects (each with mem_id via nested `members`), used to
 *   pre-fill both the row COUNT (matters for baptism, which has no
 *   fixed number) and each row's selected person.
 */
function updateFormForEventType(type, existingSubjects = []) {
  const serviceGroup = document.getElementById('serviceTypeGroup');
  const ministryGroup = document.getElementById('ministryGroup');
  const subjectsGroup = document.getElementById('subjectsGroup');
  const addSubjectBtn = document.getElementById('addSubjectBtn');
  const subjectsList = document.getElementById('subjectsList');
  const subjectsLabel = document.getElementById('subjectsLabel');

  serviceGroup.classList.toggle('d-none', type !== 'church_service');
  ministryGroup.classList.toggle('d-none', type !== 'ministry_meeting');

  const rule = SUBJECT_RULES[type];
  subjectsGroup.classList.toggle('d-none', !rule);

  subjectChoicesInstances.forEach((instance) => instance.destroy());
  subjectChoicesInstances = [];
  subjectsList.innerHTML = '';

  if (rule) {
    subjectsLabel.textContent = type === 'baptism' ? 'Candidates' : 'People Involved';

    // Use however many subjects already exist (relevant for baptism's
    // open-ended count); fall back to the type's minimum for a fresh Add.
    const rowCount = existingSubjects.length || rule.min;
    for (let i = 0; i < rowCount; i++) {
      const existingMemId = existingSubjects[i]?.members?.mem_id ?? null;
      addSubjectRow(type, i, existingMemId);
    }

    addSubjectBtn.classList.toggle('d-none', rule.max !== null); // only baptism has no max
  }
}

function addSubjectRow(type, index, preselectedMemId = null) {
  const rule = SUBJECT_RULES[type];
  if (!rule) return;
  if (rule.max !== null && index >= rule.max) return;

  const label = rule.labels ? rule.labels[index] : `Candidate #${index + 1}`;

  const row = document.createElement('div');
  row.className = 'subject-row mb-2';
  row.innerHTML = `<label class="cmis-field-label">${label}</label><select name="subjects[${index}][mem_id]"></select>`;
  document.getElementById('subjectsList').appendChild(row);

  const selectEl = row.querySelector('select');
  const choicesInstance = new Choices(selectEl, {
    choices: membersLookup.map((m) => ({
      value: m.mem_id,
      label: `${m.first_name} ${m.last_name}`,
      selected: preselectedMemId !== null && String(m.mem_id) === String(preselectedMemId),
    })),
    searchEnabled: true,
    searchPlaceholderValue: 'Search for a person…',
    itemSelectText: '',
    shouldSort: false, // membersLookup is already sorted by first name server-side
    placeholder: true,
    placeholderValue: 'Select person',
  });

  subjectChoicesInstances.push(choicesInstance);
}

/* ---------------------------------------------------------------------
   Delete
--------------------------------------------------------------------- */
async function handleDeleteEvent(eventId, eventLabel) {
  if (!confirm(`Delete this event (${eventLabel})? This cannot be undone.`)) return;

  try {
    const response = await fetch('../backend/eventActions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `_method=DELETE&event_id=${encodeURIComponent(eventId)}`,
    });
    const result = await response.json();

    if (!result.success) {
      showToast(result.errors?.[0] || 'Could not delete event.');
      return;
    }

    allEvents = allEvents.filter((ev) => String(ev.event_id) !== String(eventId));
    filteredEvents = filteredEvents.filter((ev) => String(ev.event_id) !== String(eventId));
    renderTable();
    showToast('Event deleted.');
  } catch (err) {
    showToast('Something went wrong. Please check your connection and try again.');
  }
}

/* ---------------------------------------------------------------------
   Submit
--------------------------------------------------------------------- */
function bindFormSubmit() {
  document.getElementById('addEventForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const saveBtn = document.getElementById('saveEventBtn');
    saveBtn.disabled = true;

    const formData = new FormData(e.target);

    try {
      const response = await fetch('../backend/eventActions.php', { method: 'POST', body: formData });
      const result = await response.json();

      if (!result.success) {
        showToast(result.errors?.[0] || 'Could not save event.');
        return;
      }

      const eventId = document.getElementById('formEventId').value;
      const isEditing = Boolean(eventId);

      if (isEditing) {
        const index = allEvents.findIndex((ev) => String(ev.event_id) === String(eventId));
        if (index !== -1) allEvents[index] = result.event;
      } else {
        allEvents.unshift(result.event);
      }
      filteredEvents = [...allEvents];
      currentPage = 1;
      renderTable();

      bootstrap.Modal.getInstance(document.getElementById('addEventModal')).hide();

      const warningText = (result.warnings && result.warnings.length) ? ` (${result.warnings.join(' ')})` : '';
      showToast(`Event ${isEditing ? 'updated' : 'saved'} successfully.${warningText}`);
    } catch (err) {
      showToast('Something went wrong. Please check your connection and try again.');
    } finally {
      saveBtn.disabled = false;
    }
  });
}

/* ---------------------------------------------------------------------
   Toast
--------------------------------------------------------------------- */
function showToast(message) {
  document.getElementById('toastMessage').textContent = message;
  new bootstrap.Toast(document.getElementById('successToast'), { delay: 3500 }).show();
}