



</div><!--end::Wrapper-->




</div><!--end::Page-->
    </div>
    <!--end::Main-->

    <!-- begin::User Panel-->
    <div id="kt_quick_user" class="offcanvas offcanvas-right p-10">
      <div class="offcanvas-header d-flex align-items-center justify-content-between pb-5">
        <h3 class="font-weight-bold m-0">User Profile</h3>
        <a href="#" class="btn btn-xs btn-icon btn-light btn-hover-primary" id="kt_quick_user_close">
          <i class="ki ki-close icon-xs text-muted"></i>
        </a>
      </div>
      <div class="offcanvas-content pr-5 mr-n5">
        <div class="d-flex align-items-center mt-5">
          <div class="symbol symbol-100 mr-5">
            <div class="symbol-label user-menu-initials d-flex align-items-center justify-content-center font-weight-bold font-size-h1"><?= e($userInitials ?? 'U') ?></div>
            <i class="symbol-badge bg-success"></i>
          </div>
          <div class="d-flex flex-column">
            <a href="index.php" class="font-weight-bold font-size-h5 text-dark-75 text-hover-primary user-menu-name"><?= e($userName ?? 'User') ?></a>
            <div class="text-muted mt-1"><?= e($userRole ?? 'Employee') ?></div>
            <div class="navi mt-2">
              <a href="javascript:void(0)" id="btn-signout" class="btn btn-sm btn-light-primary font-weight-bolder py-2 px-5">Sign Out</a>
            </div>
          </div>
        </div>
        <div class="separator separator-dashed mt-8 mb-5"></div>
        <div class="navi navi-spacer-x-0 p-0">
          <a href="index.php" class="navi-item">
            <div class="navi-link">
              <div class="symbol symbol-40 bg-light mr-3">
                <div class="symbol-label">
                  <span class="svg-icon svg-icon-md svg-icon-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </span>
                </div>
              </div>
              <div class="navi-text">
                <div class="font-weight-bold">My Profile</div>
                <div class="text-muted">Personal details & settings</div>
              </div>
            </div>
          </a>
          <a href="<?= e(HRMS_BASE) ?>" class="navi-item">
            <div class="navi-link">
              <div class="symbol symbol-40 bg-light mr-3">
                <div class="symbol-label">
                  <span class="svg-icon svg-icon-md svg-icon-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                  </span>
                </div>
              </div>
              <div class="navi-text">
                <div class="font-weight-bold">HR Dashboard</div>
                <div class="text-muted">Go to HRMS main dashboard</div>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
    <!-- end::User Panel-->

    <!--begin::Global Config-->
    <script>
      var KTAppSettings = {"breakpoints": {"sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1400 }, "colors": {"theme": {"base": {"white": "#ffffff", "primary": "#3699FF", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#4e3eb4", "warning": "#FFA800", "danger": "#F64E60", "light": "#E4E6EF", "dark": "#181C32" }, "light": {"white": "#ffffff", "primary": "#E1F0FF", "secondary": "#EBEDF3", "success": "#C9F7F5", "info": "#EEE5FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" }, "inverse": {"white": "#ffffff", "primary": "#ffffff", "secondary": "#3F4254", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" } }, "gray": {"gray-100": "#F3F6F9", "gray-200": "#EBEDF3", "gray-300": "#E4E6EF", "gray-400": "#D1D3E0", "gray-500": "#B5B5C3", "gray-600": "#7E8299", "gray-700": "#5E6278", "gray-800": "#3F4254", "gray-900": "#181C32" } }, "font-family": "Poppins" };
    </script>
    <!--begin::Global Theme Bundle-->
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.6.10/css/perfect-scrollbar.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.6.10/js/perfect-scrollbar.jquery.js"></script>

    <!-- Auth & CI4 API Client -->
    <script src="js/auth.js"></script>
    <script src="js/api.js"></script>

    <!-- Shared JS helpers -->
    <script>
    var HRMS_BASE = '<?= HRMS_BASE ?>';
    var API_BASE = '<?= API_BASE ?>';
    var CURRENT_EMP_ID = '<?= e(get_hrms_emp_id()) ?>';
    var CURRENT_USER = <?= json_encode(get_session_user()) ?>;
    var CSRF_TOKEN = '<?= e(csrf_token()) ?>';
    var HRMS_API_KEY = '<?= e(HRMS_API_KEY) ?>';

    // Get CSRF token from meta tag or JS variable
    function getCsrfToken() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : CSRF_TOKEN;
    }

    // HRMS API POST helper (includes CSRF token)
    async function hrmsPost(path, body) {
      const res = await fetch(HRMS_BASE + path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken(), 'X-Api-Key': HRMS_API_KEY },
        credentials: 'include',
        body: JSON.stringify(body)
      });
      return res.json();
    }

    // Toast notification helper
    function showToast(msg, type) {
      if (typeof toastr !== 'undefined') {
        type === 'success' ? toastr.success(msg) : toastr.error(msg);
      } else {
        alert(msg);
      }
    }

    // Close offcanvas panel
    function closeOffcanvas(panelId) {
      var panel = document.getElementById(panelId);
      if (panel) {
        $(panel).removeClass('offcanvas-on');
        $('body').removeClass('offcanvas-on');
        $('.offcanvas-overlay').remove();
      }
    }

    // Reset form in offcanvas
    function resetForm(panelId) {
      var panel = document.getElementById(panelId);
      if (panel) {
        panel.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="date"], textarea').forEach(function(el) { el.value = ''; });
        panel.querySelectorAll('select').forEach(function(el) { el.selectedIndex = 0; if ($(el).hasClass('select2')) $(el).val('').trigger('change'); });
        panel.querySelectorAll('input[type="checkbox"]').forEach(function(el) { el.checked = false; });
      }
    }

    // UI helper
    function setTextById(id, text) {
      var el = document.getElementById(id);
      if (el) el.textContent = text || '–';
    }
    function setHtmlById(id, html) {
      var el = document.getElementById(id);
      if (el) el.innerHTML = html || '–';
    }

    // CI4 Backend API helper (JWT-authenticated, CSRF-protected)
    async function ci4Api(method, endpoint, body) {
      var token = localStorage.getItem('ep_access_token');
      var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': getCsrfToken() };
      if (token) headers['Authorization'] = 'Bearer ' + token;
      var opts = { method: method, headers: headers };
      if (body && (method === 'POST' || method === 'PUT')) opts.body = JSON.stringify(body);
      var res = await fetch(API_BASE + endpoint, opts);
      if (res.status === 401) {
        // Try Auth.refresh() first (direct refresh token rotation)
        var refreshed = false;
        if (typeof Auth !== 'undefined') refreshed = await Auth.refresh();
        // Fallback: try token_refresh.php (has service-login with API key)
        if (!refreshed) {
          try {
            var trRes = await fetch('token_refresh.php', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
            if (trRes.ok) {
              var trData = await trRes.json();
              if (trData.access_token) {
                localStorage.setItem('ep_access_token', trData.access_token);
                if (trData.refresh_token) localStorage.setItem('ep_refresh_token', trData.refresh_token);
                refreshed = true;
              }
            }
          } catch(e) { console.warn('token_refresh.php fallback failed:', e); }
        }
        if (refreshed) {
          headers['Authorization'] = 'Bearer ' + localStorage.getItem('ep_access_token');
          opts.headers = headers;
          res = await fetch(API_BASE + endpoint, opts);
        } else {
          window.location.href = 'login.php';
          return null;
        }
      }
      var data = await res.json();
      if (!res.ok) {
        var errMsg = 'Request failed';
        if (data && data.messages && data.messages.error) errMsg = data.messages.error;
        else if (data && data.message) errMsg = data.message;
        else if (data && data.error) errMsg = typeof data.error === 'string' ? data.error : JSON.stringify(data.error);
        throw new Error(errMsg);
      }
      return data;
    }

    // Open offcanvas panel
    function openOffcanvas(panelId) {
      var panel = document.getElementById(panelId);
      if (panel) {
        $(panel).addClass('offcanvas-on');
        $('body').addClass('offcanvas-on');
        if (!$('.offcanvas-overlay').length) {
          $('body').append('<div class="offcanvas-overlay"></div>');
          $('.offcanvas-overlay').on('click', function() {
            closeOffcanvas(panelId);
          });
        }
      }
    }

    // Editing state management (exposed globally)
    window._editingRecord = { type: null, id: null, data: null };
    window.setEditMode = function(type, id, data) { window._editingRecord = { type: type, id: id, data: data }; };
    window.clearEditMode = function() { window._editingRecord = { type: null, id: null, data: null }; };
    window.isEditMode = function() { return window._editingRecord.id !== null; };
    // Local aliases for use within this script
    var _editingRecord = window._editingRecord;
    var setEditMode = window.setEditMode;
    var clearEditMode = window.clearEditMode;
    var isEditMode = window.isEditMode;

    // Sign out handler
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('#btn-signout, .btn-signout').forEach(function(el) {
        el.addEventListener('click', function(e) {
          e.preventDefault();
          localStorage.removeItem('ep_access_token');
          localStorage.removeItem('ep_refresh_token');
          localStorage.removeItem('ep_user');
          localStorage.removeItem('ep_token_expires');
          fetch('logout.php').then(function() {
            window.location.href = 'login.php';
          });
        });
      });
    });
    </script>
