/**
 * Employee Profile System - Main App Logic
 * Data binding, UI helpers, and page initialization
 */

// ── Utility helpers ───────────────────────────────────────────────────────────

function setHtml(selector, value) {
  const el = document.querySelector(selector);
  if (el) el.innerHTML = value ?? '—';
}

function setText(selector, value) {
  const el = document.querySelector(selector);
  if (el) el.textContent = value ?? '—';
}

function setVal(selector, value) {
  const el = document.querySelector(selector);
  if (el) el.value = value ?? '';
}

function setSrc(selector, src, fallback = '') {
  const el = document.querySelector(selector);
  if (el && src) el.src = src;
  else if (el && fallback) el.src = fallback;
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  try {
    return new Date(dateStr).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  } catch { return dateStr; }
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—';
  try {
    return new Date(dateStr).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  } catch { return dateStr; }
}

function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
}

function badgeClass(status) {
  const map = {
    active: 'success', completed: 'success', approved: 'success',
    in_progress: 'primary', ongoing: 'primary',
    pending: 'warning', under_review: 'warning',
    inactive: 'secondary', not_started: 'secondary',
    failed: 'danger', terminated: 'danger', rejected: 'danger',
  };
  return map[status?.toLowerCase()] || 'secondary';
}

function badge(text, status) {
  return `<span class="label label-inline label-light-${badgeClass(status)} font-weight-bold">${capitalize(text)}</span>`;
}

function emptyRow(cols, message = 'No records found') {
  return `<tr><td colspan="${cols}" class="text-center text-muted py-8">${message}</td></tr>`;
}

// ── Toast notifications ───────────────────────────────────────────────────────

function showToast(message, type = 'success') {
  if (typeof toastr !== 'undefined') {
    toastr[type](message);
    return;
  }
  // Fallback simple toast
  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed;bottom:24px;right:24px;z-index:9999;
    background:${type === 'success' ? '#50CD89' : type === 'error' ? '#F1416C' : '#009EF7'};
    color:#fff;padding:12px 20px;border-radius:6px;font-size:13px;
    box-shadow:0 4px 12px rgba(0,0,0,.15);animation:fadeInUp .3s ease;
  `;
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// ── Loading state ─────────────────────────────────────────────────────────────

function showSectionLoading(selector) {
  const el = document.querySelector(selector);
  if (el) el.innerHTML = `
    <div class="text-center py-12">
      <div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status">
        <span class="sr-only">Loading...</span>
      </div>
    </div>`;
}

function showSectionError(selector, message = 'Failed to load data') {
  const el = document.querySelector(selector);
  if (el) el.innerHTML = `
    <div class="text-center py-8 text-muted">
      <i class="ki ki-warning font-size-h2 text-warning mb-3 d-block"></i>
      ${message}
    </div>`;
}

// ── Profile Header ────────────────────────────────────────────────────────────

async function loadProfileHeader() {
  try {
    const res = await api.profile.getOwn();
    const p = res?.data || {};

    // Avatar
    if (p.profile_picture_url) {
      setSrc('#profileAvatar', p.profile_picture_url);
    } else {
      const initEl = document.querySelector('#profileAvatarInitials');
      if (initEl) initEl.textContent = Auth.getAvatarInitials();
    }

    // Name & designation
    setText('#profileName', p.full_name || p.name || Auth.getUserName());
    setText('#profileDesignation', p.designation || p.job_title || '');
    setText('#profileDepartment', p.department || '');
    setText('#profileEmail', p.email || Auth.getUser()?.email || '');
    setText('#profilePhone', p.phone || p.mobile || '');
    setText('#profileLocation', p.location || p.work_location || '');
    setText('#profileEmployeeId', p.employee_id || '');

    // Role badge
    const roleBadge = document.querySelector('#userRoleBadge');
    if (roleBadge) roleBadge.innerHTML = badge(Auth.getUserRole(), 'active');

    return p;
  } catch (err) {
    console.error('Profile header load failed:', err);
  }
}

// ── Tab: Personal Profile ─────────────────────────────────────────────────────

async function loadPersonalDetails() {
  try {
    const res = await api.profile.getPersonalDetails();
    const d = res?.data || {};

    setText('#pdFirstName', d.first_name);
    setText('#pdLastName', d.last_name);
    setText('#pdDob', formatDate(d.date_of_birth));
    setText('#pdGender', capitalize(d.gender));
    setText('#pdNationality', d.nationality);
    setText('#pdMaritalStatus', capitalize(d.marital_status));
    setText('#pdBloodGroup', d.blood_group);
    setVal('#pdFirstNameInput', d.first_name);
    setVal('#pdLastNameInput', d.last_name);
    setVal('#pdDobInput', d.date_of_birth?.split('T')[0]);
    setVal('#pdGenderInput', d.gender);
    setVal('#pdNationalityInput', d.nationality);
    setVal('#pdMaritalInput', d.marital_status);

  } catch (err) {
    console.error('Personal details load failed:', err);
  }
}

async function loadAddresses() {
  const container = document.querySelector('#addressList');
  if (!container) return;
  try {
    const res = await api.profile.getAddresses();
    const items = res?.data || [];
    if (!items.length) {
      container.innerHTML = '<p class="text-muted py-4">No addresses added yet.</p>';
      return;
    }
    container.innerHTML = items.map(a => `
      <div class="d-flex align-items-start mb-6 p-4 rounded border border-dashed" data-id="${a.id}">
        <span class="label label-md label-light-primary font-weight-bold mr-4 mt-1">${capitalize(a.address_type)}</span>
        <div class="flex-grow-1">
          <div class="font-weight-bold text-dark">${[a.address_line1, a.address_line2].filter(Boolean).join(', ')}</div>
          <div class="text-muted font-size-sm">${[a.city, a.state, a.country, a.postal_code].filter(Boolean).join(', ')}</div>
        </div>
        ${a.is_primary ? '<span class="label label-inline label-success font-weight-bold ml-2">Primary</span>' : ''}
      </div>
    `).join('');
  } catch (err) {
    container.innerHTML = '<p class="text-muted">Failed to load addresses.</p>';
  }
}

async function loadEmergencyContacts() {
  const container = document.querySelector('#emergencyContactList');
  if (!container) return;
  try {
    const res = await api.profile.getEmergencyContacts();
    const items = res?.data || [];
    if (!items.length) {
      container.innerHTML = '<p class="text-muted py-4">No emergency contacts added.</p>';
      return;
    }
    container.innerHTML = items.map(c => `
      <div class="d-flex align-items-center mb-4 p-3 rounded border" data-id="${c.id}">
        <div class="symbol symbol-40 symbol-light-danger mr-4">
          <span class="symbol-label font-weight-bold">${(c.contact_name || 'NA').charAt(0)}</span>
        </div>
        <div class="flex-grow-1">
          <div class="font-weight-bold">${c.contact_name}</div>
          <div class="text-muted font-size-sm">${capitalize(c.relationship)} &bull; ${c.phone}</div>
        </div>
      </div>
    `).join('');
  } catch {}
}

async function loadFamilyDependents() {
  const tbody = document.querySelector('#familyDependentsTable tbody');
  if (!tbody) return;
  try {
    const res = await api.profile.getFamilyDependents();
    const items = res?.data || [];
    if (!items.length) { tbody.innerHTML = emptyRow(5); return; }
    tbody.innerHTML = items.map(f => `
      <tr>
        <td class="font-weight-bold">${f.dependent_name || '—'}</td>
        <td>${capitalize(f.relationship)}</td>
        <td>${formatDate(f.date_of_birth)}</td>
        <td>${f.gender || '—'}</td>
        <td>${badge(f.dependency_status || 'active', f.dependency_status)}</td>
      </tr>
    `).join('');
  } catch { tbody.innerHTML = emptyRow(5, 'Failed to load'); }
}

// ── Tab: Job & Organization ───────────────────────────────────────────────────

async function loadJobInfo() {
  try {
    const res = await api.job.getInfo();
    const j = res?.data || {};

    setText('#jobTitle', j.job_title);
    setText('#jobDepartment', j.department);
    setText('#jobLocation', j.work_location);
    setText('#jobType', capitalize(j.employment_type));
    setText('#jobJoinDate', formatDate(j.joining_date || j.joined_date));
    setText('#jobManager', j.manager_name || j.reporting_manager);
    setText('#jobGrade', j.grade || j.pay_grade);
    setText('#jobCostCentre', j.cost_centre);
    setText('#jobStatus', capitalize(j.employment_status));
    setText('#jobWorkArrangement', capitalize(j.work_arrangement));

  } catch (err) {
    console.error('Job info load failed:', err);
  }
}

async function loadEmploymentHistory() {
  const tbody = document.querySelector('#employmentHistoryTable tbody');
  if (!tbody) return;
  try {
    const res = await api.job.getHistory();
    const items = res?.data || [];
    if (!items.length) { tbody.innerHTML = emptyRow(6); return; }
    tbody.innerHTML = items.map(h => `
      <tr>
        <td class="font-weight-bold">${h.job_title || '—'}</td>
        <td>${h.department || '—'}</td>
        <td>${h.company_name || 'Current Company'}</td>
        <td>${formatDate(h.start_date)}</td>
        <td>${h.end_date ? formatDate(h.end_date) : '<span class="text-success">Present</span>'}</td>
        <td>${badge(h.status || 'active', h.status)}</td>
      </tr>
    `).join('');
  } catch { tbody.innerHTML = emptyRow(6, 'Failed to load'); }
}

async function loadTeamMembers() {
  const container = document.querySelector('#teamMembersList');
  if (!container) return;
  try {
    const res = await api.job.getTeamMembers();
    const items = res?.data || [];
    if (!items.length) {
      container.innerHTML = '<p class="text-muted py-4">No team members found.</p>';
      return;
    }
    container.innerHTML = items.map(m => `
      <div class="d-flex align-items-center mb-4">
        <div class="symbol symbol-40 symbol-light-primary mr-3">
          <span class="symbol-label font-weight-bold">${(m.full_name || m.name || 'NA').charAt(0)}</span>
        </div>
        <div>
          <div class="font-weight-bold text-dark">${m.full_name || m.name || '—'}</div>
          <div class="text-muted font-size-sm">${m.job_title || m.designation || ''}</div>
        </div>
      </div>
    `).join('');
  } catch {}
}

// ── Tab: Performance ─────────────────────────────────────────────────────────

async function loadPerformanceReviews() {
  const tbody = document.querySelector('#performanceReviewsTable tbody');
  if (!tbody) return;
  try {
    const res = await api.performance.getReviews();
    const items = res?.data || [];
    if (!items.length) { tbody.innerHTML = emptyRow(5); return; }
    tbody.innerHTML = items.map(r => `
      <tr>
        <td class="font-weight-bold">${r.review_period || r.period || '—'}</td>
        <td>${r.reviewer_name || '—'}</td>
        <td>${formatDate(r.review_date)}</td>
        <td><span class="font-weight-bolder text-primary">${r.overall_rating || '—'} / 5</span></td>
        <td>${badge(r.status, r.status)}</td>
      </tr>
    `).join('');
  } catch { tbody.innerHTML = emptyRow(5, 'Failed to load'); }
}

async function loadPerformanceGoals() {
  const tbody = document.querySelector('#performanceGoalsTable tbody');
  if (!tbody) return;
  try {
    const res = await api.performance.getGoals();
    const items = res?.data || [];
    if (!items.length) { tbody.innerHTML = emptyRow(5); return; }
    tbody.innerHTML = items.map(g => `
      <tr>
        <td class="font-weight-bold">${g.goal_title || g.title || '—'}</td>
        <td>${g.category || '—'}</td>
        <td>
          <div class="d-flex align-items-center">
            <div class="progress flex-grow-1 mr-2" style="height:6px;">
              <div class="progress-bar bg-primary" style="width:${g.progress_percentage || 0}%"></div>
            </div>
            <span class="text-muted font-size-sm">${g.progress_percentage || 0}%</span>
          </div>
        </td>
        <td>${formatDate(g.end_date)}</td>
        <td>${badge(g.status, g.status)}</td>
      </tr>
    `).join('');
  } catch { tbody.innerHTML = emptyRow(5, 'Failed to load'); }
}

// ── Tab: Talent Management ────────────────────────────────────────────────────

async function loadSkills() {
  const container = document.querySelector('#skillsList');
  if (!container) return;
  try {
    const res = await api.talent.getSkills();
    const items = res?.data || [];
    if (!items.length) {
      container.innerHTML = '<p class="text-muted py-4">No skills added yet.</p>';
      return;
    }
    container.innerHTML = items.map(s => `
      <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded border" data-id="${s.id}">
        <div>
          <div class="font-weight-bold">${s.skill_name}</div>
          <div class="text-muted font-size-sm">${s.skill_category || ''}</div>
        </div>
        <div class="text-right">
          ${badge(s.skill_level || s.proficiency_level || 'intermediate', s.skill_level)}
          ${s.years_of_experience ? `<div class="text-muted font-size-xs mt-1">${s.years_of_experience} yrs</div>` : ''}
        </div>
      </div>
    `).join('');
  } catch {
    container.innerHTML = '<p class="text-muted">Failed to load skills.</p>';
  }
}

async function loadCertifications() {
  const tbody = document.querySelector('#certificationsTable tbody');
  if (!tbody) return;
  try {
    const res = await api.talent.getCertifications();
    const items = res?.data || [];
    if (!items.length) { tbody.innerHTML = emptyRow(5); return; }
    tbody.innerHTML = items.map(c => `
      <tr>
        <td class="font-weight-bold">${c.certification_name || '—'}</td>
        <td>${c.issuing_organization || '—'}</td>
        <td>${formatDate(c.issue_date)}</td>
        <td>${c.expiry_date ? formatDate(c.expiry_date) : 'No Expiry'}</td>
        <td>${badge(c.status || 'active', c.status)}</td>
      </tr>
    `).join('');
  } catch { tbody.innerHTML = emptyRow(5, 'Failed to load'); }
}

async function loadAwards() {
  const container = document.querySelector('#awardsList');
  if (!container) return;
  try {
    const res = await api.talent.getAwards();
    const items = res?.data || [];
    if (!items.length) {
      container.innerHTML = '<p class="text-muted py-4">No awards yet.</p>';
      return;
    }
    container.innerHTML = items.map(a => `
      <div class="d-flex align-items-start mb-4 p-4 rounded bg-light-warning">
        <span class="svg-icon svg-icon-warning svg-icon-2x mr-3">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2z"
              fill="#FFC700" stroke="#FFC700" stroke-width="1" stroke-linejoin="round"/>
          </svg>
        </span>
        <div>
          <div class="font-weight-bolder text-dark">${a.award_name || '—'}</div>
          <div class="text-muted font-size-sm">${formatDate(a.award_date)} &bull; ${a.award_description || ''}</div>
        </div>
      </div>
    `).join('');
  } catch {}
}

// ── Tab: Learning & Development ───────────────────────────────────────────────

async function loadTrainingHistory() {
  const tbody = document.querySelector('#trainingHistoryTable tbody');
  if (!tbody) return;
  try {
    const res = await api.learning.getTrainingHistory();
    const items = res?.data || [];
    if (!items.length) { tbody.innerHTML = emptyRow(5); return; }
    tbody.innerHTML = items.map(t => `
      <tr>
        <td class="font-weight-bold">${t.training_name || t.course_name || '—'}</td>
        <td>${t.training_type || t.category || '—'}</td>
        <td>${t.provider || '—'}</td>
        <td>${formatDate(t.completion_date || t.end_date)}</td>
        <td>${badge(t.status || 'completed', t.status)}</td>
      </tr>
    `).join('');
  } catch { tbody.innerHTML = emptyRow(5, 'Failed to load'); }
}

async function loadEnrollments() {
  const container = document.querySelector('#enrollmentsList');
  if (!container) return;
  try {
    const res = await api.learning.getEnrollments();
    const items = res?.data || [];
    if (!items.length) {
      container.innerHTML = '<p class="text-muted py-4">No active enrollments.</p>';
      return;
    }
    container.innerHTML = items.map(e => `
      <div class="d-flex align-items-center mb-4 p-3 rounded border" data-id="${e.id}">
        <div class="symbol symbol-50 symbol-light-primary mr-4">
          <span class="symbol-label font-weight-bold font-size-h5">
            ${(e.course_name || 'C').charAt(0).toUpperCase()}
          </span>
        </div>
        <div class="flex-grow-1">
          <div class="font-weight-bold">${e.course_name || '—'}</div>
          <div class="text-muted font-size-sm">${e.category || ''}</div>
          <div class="progress mt-2" style="height:5px;">
            <div class="progress-bar bg-primary" style="width:${e.progress_percentage || 0}%"></div>
          </div>
        </div>
        <div class="ml-4 text-right">
          ${badge(e.status, e.status)}
          <div class="text-muted font-size-xs mt-1">${e.progress_percentage || 0}%</div>
        </div>
      </div>
    `).join('');
  } catch {}
}

// ── Header user info ──────────────────────────────────────────────────────────

function populateHeaderUser() {
  const user = Auth.getUser();
  if (!user) return;
  setText('#headerUserName', user.name || user.email?.split('@')[0] || 'User');
  setText('#headerUserRole', capitalize(user.role || 'Employee'));
  setText('#headerUserInitials', Auth.getAvatarInitials());
}

// ── Forms: Save handlers ──────────────────────────────────────────────────────

async function savePersonalDetails(formData) {
  try {
    await api.profile.updatePersonalDetails(formData);
    showToast('Personal details updated successfully');
    await loadPersonalDetails();
    return true;
  } catch (err) {
    showToast(err.message || 'Update failed', 'error');
    return false;
  }
}

async function saveAddress(data, id = null) {
  try {
    if (id) await api.profile.updateAddress(id, data);
    else await api.profile.addAddress(data);
    showToast('Address saved successfully');
    await loadAddresses();
    return true;
  } catch (err) {
    showToast(err.message || 'Save failed', 'error');
    return false;
  }
}

async function saveEmergencyContact(data, id = null) {
  try {
    if (id) await api.profile.updateEmergencyContact(id, data);
    else await api.profile.addEmergencyContact(data);
    showToast('Emergency contact saved');
    await loadEmergencyContacts();
    return true;
  } catch (err) {
    showToast(err.message || 'Save failed', 'error');
    return false;
  }
}

async function saveSkill(data, id = null) {
  try {
    if (id) await api.talent.updateSkill(id, data);
    else await api.talent.addSkill(data);
    showToast('Skill saved successfully');
    await loadSkills();
    return true;
  } catch (err) {
    showToast(err.message || 'Save failed', 'error');
    return false;
  }
}

// ── Profile picture upload ────────────────────────────────────────────────────

async function handleProfilePictureUpload(inputEl) {
  const file = inputEl.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    showToast('File too large. Max 5MB allowed.', 'error');
    return;
  }
  try {
    const res = await api.upload.profilePicture(file);
    if (res?.data?.url) {
      setSrc('#profileAvatar', res.data.url);
      showToast('Profile picture updated');
    }
  } catch (err) {
    showToast(err.message || 'Upload failed', 'error');
  }
}

// ── Page initializer for profile.html ────────────────────────────────────────

async function initProfilePage() {
  if (!Auth.requireAuth()) return;

  populateHeaderUser();

  // Load all sections in parallel
  await Promise.all([
    loadProfileHeader(),
    loadPersonalDetails(),
    loadJobInfo(),
  ]);

  // Lazy-load sections when tabs become active
  document.querySelectorAll('[data-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', async (e) => {
      const target = e.target.getAttribute('href') || e.target.getAttribute('data-target');
      switch (target) {
        case '#tabWP1':
          await Promise.all([loadAddresses(), loadEmergencyContacts(), loadFamilyDependents()]);
          break;
        case '#tabWP3':
          await Promise.all([loadEmploymentHistory(), loadTeamMembers()]);
          break;
        case '#tabWP5':
          await Promise.all([loadPerformanceReviews(), loadPerformanceGoals()]);
          break;
        case '#tabWP6':
          await Promise.all([loadSkills(), loadCertifications(), loadAwards()]);
          break;
        case '#tabWP7':
          await Promise.all([loadTrainingHistory(), loadEnrollments()]);
          break;
      }
    });
  });

  // Load the currently active tab
  const activeTab = document.querySelector('[data-toggle="tab"].active');
  if (activeTab) {
    const target = activeTab.getAttribute('href') || activeTab.getAttribute('data-target');
    if (target === '#tabWP6') {
      await Promise.all([loadSkills(), loadCertifications(), loadAwards()]);
    }
  }
}

// ── Page initializer for dashboard.html ──────────────────────────────────────

async function initDashboardPage() {
  if (!Auth.requireAuth()) return;
  populateHeaderUser();

  try {
    const role = Auth.getUserRole();
    let dashData;

    if (role === 'admin') dashData = await api.dashboard.getAdmin();
    else if (role === 'hr') dashData = await api.dashboard.getHr();
    else if (role === 'manager') dashData = await api.dashboard.getManager();
    else dashData = await api.dashboard.getMy();

    renderDashboard(dashData?.data || {});
  } catch (err) {
    console.error('Dashboard load failed:', err);
    showToast('Failed to load dashboard data', 'error');
  }
}

function renderDashboard(data) {
  // Stat cards
  const stats = {
    '#dashTotalEmployees':   data.total_employees,
    '#dashActiveEmployees':  data.active_employees,
    '#dashPendingReviews':   data.pending_reviews,
    '#dashCoursesEnrolled':  data.courses_enrolled,
    '#dashSkillsCount':      data.skills_count,
    '#dashCertifications':   data.certifications_count,
    '#dashGoalCompletion':   data.goal_completion_rate != null ? data.goal_completion_rate + '%' : null,
    '#dashTrainingsCompleted': data.trainings_completed,
  };
  Object.entries(stats).forEach(([sel, val]) => {
    if (val != null) setText(sel, val);
  });

  // Recent activities
  if (data.recent_activities?.length) {
    const container = document.querySelector('#recentActivitiesList');
    if (container) {
      container.innerHTML = data.recent_activities.map(a => `
        <div class="d-flex align-items-center mb-3">
          <span class="bullet bullet-bar bg-primary align-self-stretch mr-3"></span>
          <div>
            <div class="font-weight-bold">${a.title || a.action || '—'}</div>
            <div class="text-muted font-size-sm">${formatDateTime(a.created_at || a.timestamp)}</div>
          </div>
        </div>
      `).join('');
    }
  }

  // Upcoming reviews
  if (data.upcoming_reviews?.length) {
    const tbody = document.querySelector('#upcomingReviewsTable tbody');
    if (tbody) {
      tbody.innerHTML = data.upcoming_reviews.map(r => `
        <tr>
          <td class="font-weight-bold">${r.employee_name || r.name || '—'}</td>
          <td>${formatDate(r.review_date || r.scheduled_date)}</td>
          <td>${badge(r.status, r.status)}</td>
        </tr>
      `).join('');
    }
  }
}
