/**
 * Employee Profile System - API Client
 * Handles all HTTP requests to the backend REST API
 */

// API_BASE must be set by PHP in footer.php before this script loads
if (typeof API_BASE === 'undefined') {
  console.error('API_BASE is not defined. Ensure config.php is loaded.');
  var API_BASE = '';
}

/**
 * Core fetch wrapper with auth header and token refresh
 */
async function apiRequest(method, endpoint, body = null, isRetry = false) {
  const token = Auth.getAccessToken();
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
  if (token) headers['Authorization'] = 'Bearer ' + token;

  const options = { method, headers };
  if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
    options.body = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetch(API_BASE + endpoint, options);
  } catch (err) {
    throw new ApiError(0, 'Network error – check that the API server is running');
  }

  // 401 → try refresh once
  if (response.status === 401 && !isRetry) {
    const refreshed = await Auth.refresh();
    if (refreshed) return apiRequest(method, endpoint, body, true);
    Auth.logout();
    return;
  }

  const text = await response.text();
  let data;
  try { data = JSON.parse(text); } catch { data = { raw: text }; }

  if (!response.ok) {
    const message = data?.messages?.error
      || data?.messages?.token
      || (typeof data?.messages === 'string' ? data.messages : null)
      || `HTTP ${response.status}`;
    throw new ApiError(response.status, message, data);
  }

  return data;
}

class ApiError extends Error {
  constructor(status, message, data = null) {
    super(message);
    this.status = status;
    this.data = data;
  }
}

// Convenience wrappers
const api = {
  get:    (url)         => apiRequest('GET',    url),
  post:   (url, body)   => apiRequest('POST',   url, body),
  put:    (url, body)   => apiRequest('PUT',    url, body),
  delete: (url)         => apiRequest('DELETE', url),

  // ── Auth ────────────────────────────────────────────────────────────────
  auth: {
    login:     (email, password) => api.post('/auth/login', { email, password }),
    ssoLogin:  (token)           => api.post('/auth/sso-login', { token }),
    refresh:   (refreshToken)    => api.post('/auth/refresh', { refresh_token: refreshToken }),
    verify:    ()                => api.get('/auth/verify'),
    logout:    ()                => api.post('/auth/logout', {}),
  },

  // ── Profile ─────────────────────────────────────────────────────────────
  profile: {
    getOwn:              ()     => api.get('/profile'),
    getById:             (id)   => api.get(`/profile/${id}`),
    updateOwn:           (data) => api.put('/profile', data),
    getPersonalDetails:  ()     => api.get('/profile/personal-details'),
    updatePersonalDetails:(data)=> api.put('/profile/personal-details', data),

    // Addresses
    getAddresses:        ()     => api.get('/profile/addresses'),
    addAddress:          (data) => api.post('/profile/addresses', data),
    updateAddress:       (id, d)=> api.put(`/profile/addresses/${id}`, d),
    deleteAddress:       (id)   => api.delete(`/profile/addresses/${id}`),

    // Emergency contacts
    getEmergencyContacts: ()    => api.get('/profile/emergency-contacts'),
    addEmergencyContact:  (d)   => api.post('/profile/emergency-contacts', d),
    updateEmergencyContact:(id,d)=> api.put(`/profile/emergency-contacts/${id}`, d),

    // Govt IDs
    getGovtIds:          ()     => api.get('/profile/govt-ids'),
    addGovtId:           (data) => api.post('/profile/govt-ids', data),
    updateGovtId:        (id,d) => api.put(`/profile/govt-ids/${id}`, d),

    // Bank details
    getBankDetails:      ()     => api.get('/profile/bank-details'),
    addBankDetail:       (data) => api.post('/profile/bank-details', data),
    updateBankDetail:    (id,d) => api.put(`/profile/bank-details/${id}`, d),

    // Health & family
    getHealth:           ()     => api.get('/profile/health'),
    updateHealth:        (data) => api.put('/profile/health', data),
    getFamilyDependents: ()     => api.get('/profile/family-dependents'),
    addFamilyDependent:  (data) => api.post('/profile/family-dependents', data),
    updateFamilyDependent:(id,d)=> api.put(`/profile/family-dependents/${id}`, d),

    // Languages
    getLanguages:        ()     => api.get('/profile/languages'),
    addLanguage:         (data) => api.post('/profile/languages', data),
    updateLanguage:      (id,d) => api.put(`/profile/languages/${id}`, d),
    deleteLanguage:      (id)   => api.delete(`/profile/languages/${id}`),

    // Hobbies / Sports / Talents
    getHobbies:          ()     => api.get('/profile/hobbies'),
    addHobby:            (data) => api.post('/profile/hobbies', data),
    updateHobby:         (id,d) => api.put(`/profile/hobbies/${id}`, d),
    deleteHobby:         (id)   => api.delete(`/profile/hobbies/${id}`),

    // Volunteer Activities
    getVolunteerActivities: ()     => api.get('/profile/volunteer-activities'),
    addVolunteerActivity:   (data) => api.post('/profile/volunteer-activities', data),
    updateVolunteerActivity:(id,d) => api.put(`/profile/volunteer-activities/${id}`, d),

    // Patents
    getPatents:          ()     => api.get('/profile/patents'),
    addPatent:           (data) => api.post('/profile/patents', data),
    updatePatent:        (id,d) => api.put(`/profile/patents/${id}`, d),

    // Working Conditions
    getWorkingConditions:   ()     => api.get('/profile/working-conditions'),
    updateWorkingConditions:(data) => api.put('/profile/working-conditions', data),

    // Mobility Preferences
    getMobilityPreferences:   ()     => api.get('/profile/mobility-preferences'),
    updateMobilityPreferences:(data) => api.put('/profile/mobility-preferences', data),

    // GDPR Consents
    getConsents:        ()     => api.get('/profile/consents'),
    recordConsent:      (data) => api.post('/profile/consents', data),
    withdrawAllConsents:()     => api.del('/profile/consents'),

    // Data Version History
    getVersionHistory:  (entityType, entityId) => api.get('/profile/versions?entity_type=' + entityType + '&entity_id=' + entityId),
  },

  // ── Job & Organization ───────────────────────────────────────────────────
  job: {
    getInfo:            ()     => api.get('/job/information'),
    getInfoById:        (id)   => api.get(`/job/information/${id}`),
    getHistory:         ()     => api.get('/job/history'),
    addHistory:         (data) => api.post('/job/history', data),
    getOrgHierarchy:    ()     => api.get('/job/org-hierarchy'),
    getTeamMembers:     ()     => api.get('/job/team-members'),
    getReportingStructure: ()  => api.get('/job/reporting-structure'),
    getPromotions:      ()     => api.get('/job/promotions'),
    getTransfers:       ()     => api.get('/job/transfers'),
  },

  // ── Performance ──────────────────────────────────────────────────────────
  performance: {
    getReviews:    ()      => api.get('/performance/reviews'),
    getReview:     (id)    => api.get(`/performance/reviews/${id}`),
    createReview:  (data)  => api.post('/performance/reviews', data),
    updateReview:  (id, d) => api.put(`/performance/reviews/${id}`, d),
    getGoals:      ()      => api.get('/performance/goals'),
    createGoal:    (data)  => api.post('/performance/goals', data),
    updateGoal:    (id, d) => api.put(`/performance/goals/${id}`, d),
    getFeedback:   ()      => api.get('/performance/feedback'),
    submitFeedback:(data)  => api.post('/performance/feedback', data),
    getRatings:    ()      => api.get('/performance/ratings'),
  },

  // ── Talent ───────────────────────────────────────────────────────────────
  talent: {
    getSkills:        ()      => api.get('/talent/skills'),
    addSkill:         (data)  => api.post('/talent/skills', data),
    updateSkill:      (id, d) => api.put(`/talent/skills/${id}`, d),
    getCompetencies:  ()      => api.get('/talent/competencies'),
    getMyCompetencies:()      => api.get('/talent/my-competencies'),
    getCertifications:()      => api.get('/talent/certifications'),
    addCertification: (data)  => api.post('/talent/certifications', data),
    updateCertification:(id,d)=> api.put(`/talent/certifications/${id}`, d),
    getIdp:           ()      => api.get('/talent/idp'),
    createIdp:        (data)  => api.post('/talent/idp', data),
    getAwards:        ()      => api.get('/talent/awards'),
  },

  // ── Learning ─────────────────────────────────────────────────────────────
  learning: {
    getCourses:       ()      => api.get('/learning/courses'),
    getCourse:        (id)    => api.get(`/learning/courses/${id}`),
    getEnrollments:   ()      => api.get('/learning/enrollments'),
    enroll:           (data)  => api.post('/learning/enrollments', data),
    updateEnrollment: (id, d) => api.put(`/learning/enrollments/${id}`, d),
    getTrainingHistory:()     => api.get('/learning/training-history'),
    getLearningPaths: ()      => api.get('/learning/paths'),
  },

  // ── Dashboard ────────────────────────────────────────────────────────────
  dashboard: {
    getMy:      () => api.get('/dashboard/my-dashboard'),
    getManager: () => api.get('/dashboard/manager-dashboard'),
    getHr:      () => api.get('/dashboard/hr-dashboard'),
    getAdmin:   () => api.get('/dashboard/admin-dashboard'),
  },

  // ── Admin ─────────────────────────────────────────────────────────────────
  admin: {
    getEmployees: (params = {}) => {
      const q = new URLSearchParams(params).toString();
      return api.get('/admin/employees' + (q ? '?' + q : ''));
    },
    createEmployee: (data) => api.post('/admin/employees', data),
    updateEmployee: (id, d) => api.put(`/admin/employees/${id}`, d),
    deleteEmployee: (id)   => api.delete(`/admin/employees/${id}`),
    getAuditLogs:  (params = {}) => {
      const q = new URLSearchParams(params).toString();
      return api.get('/admin/audit-logs' + (q ? '?' + q : ''));
    },
  },

  // ── Upload ────────────────────────────────────────────────────────────────
  upload: {
    profilePicture: async (file) => {
      const form = new FormData();
      form.append('file', file);
      const token = Auth.getAccessToken();
      const response = await fetch(`${API_BASE}/upload/profile-picture`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: form,
      });
      if (!response.ok) throw new ApiError(response.status, 'Upload failed');
      return response.json();
    },
    certificate: async (file, data = {}) => {
      const form = new FormData();
      form.append('file', file);
      Object.entries(data).forEach(([k, v]) => form.append(k, v));
      const token = Auth.getAccessToken();
      const response = await fetch(`${API_BASE}/upload/certificate`, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: form,
      });
      if (!response.ok) throw new ApiError(response.status, 'Upload failed');
      return response.json();
    },
  },

  // ── Search ────────────────────────────────────────────────────────────────
  search: {
    employees: (q)  => api.get(`/search/employees?q=${encodeURIComponent(q)}`),
    skills:    (q)  => api.get(`/search/skills?q=${encodeURIComponent(q)}`),
    courses:   (q)  => api.get(`/search/courses?q=${encodeURIComponent(q)}`),
    global:    (q)  => api.get(`/search/global?q=${encodeURIComponent(q)}`),
  },
};
