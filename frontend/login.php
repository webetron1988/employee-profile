<?php
require_once 'config.php';
// If SSO token is in the URL, let JS handle it (even if already logged in)
$hasSsoToken = !empty($_GET['token']) || !empty($_GET['hrms_token']);
// If already logged in and no SSO token, redirect to index
if (!$hasSsoToken && is_logged_in()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Login – Employee Profile System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
  <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
  <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
  <style>
    * { box-sizing: border-box; }
    body {
      background: #f3f6f9;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-wrapper { width: 100%; max-width: 460px; }
    .login-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 40px rgba(0,0,0,.09);
      padding: 44px 40px 36px;
    }
    .login-logo { text-align: center; margin-bottom: 28px; }
    .login-logo-icon {
      display: inline-flex; align-items: center; justify-content: center;
      width: 56px; height: 56px; background: #009EF7; border-radius: 12px; margin-bottom: 14px;
    }
    .login-logo h2 { font-size: 21px; font-weight: 700; color: #3F4254; margin: 0 0 4px; }
    .login-logo p { color: #A1A5B7; font-size: 13px; margin: 0; }
    .login-tabs { display: flex; background: #F5F8FA; border-radius: 8px; padding: 4px; margin-bottom: 24px; }
    .login-tab {
      flex: 1; text-align: center; padding: 8px 10px; border-radius: 6px;
      font-size: 12.5px; font-weight: 600; color: #7E8299; cursor: pointer;
      transition: all .2s; user-select: none; border: none; background: transparent;
    }
    .login-tab.active { background: #fff; color: #009EF7; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
    .login-tab:hover:not(.active) { color: #3F4254; }
    .alert-error, .alert-success {
      border-radius: 7px; padding: 11px 16px; font-size: 13px;
      margin-bottom: 18px; display: none; align-items: center; gap: 8px;
    }
    .alert-error  { background: #FFF5F8; border: 1px solid #F1416C; color: #F1416C; }
    .alert-success{ background: #E8FFF3; border: 1px solid #50CD89; color: #47BE7D; }
    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 12.5px; font-weight: 600; color: #3F4254; margin-bottom: 6px; }
    .input-wrap { position: relative; }
    .form-input {
      width: 100%; height: 44px; border: 1.5px solid #E4E6EF; border-radius: 7px;
      padding: 0 42px 0 14px; font-size: 13.5px; color: #3F4254; outline: none;
      font-family: 'Poppins', sans-serif; transition: border-color .2s, box-shadow .2s; background: #fff;
    }
    .form-input:focus { border-color: #009EF7; box-shadow: 0 0 0 3px rgba(0,158,247,.12); }
    .form-input.error-field { border-color: #F1416C; }
    .input-icon { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); color: #B5B5C3; cursor: pointer; line-height: 1; }
    .field-error { font-size: 11.5px; color: #F1416C; margin-top: 4px; display: none; }
    .toggle-pw { cursor: pointer; }
    .form-footer { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
    .remember-label { display: flex; align-items: center; gap: 7px; font-size: 13px; color: #5E6278; cursor: pointer; }
    .remember-label input[type="checkbox"] { accent-color: #009EF7; }
    .forgot-link { font-size: 13px; color: #009EF7; text-decoration: none; font-weight: 500; }
    .forgot-link:hover { text-decoration: underline; }
    .btn-primary-login {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; height: 46px; background: #009EF7; color: #fff; border: none;
      border-radius: 7px; font-size: 14px; font-weight: 600; cursor: pointer;
      font-family: 'Poppins', sans-serif; transition: background .2s, box-shadow .2s;
    }
    .btn-primary-login:hover:not(:disabled) { background: #0095E8; box-shadow: 0 4px 16px rgba(0,158,247,.35); }
    .btn-primary-login:disabled { opacity: .65; cursor: not-allowed; }
    .btn-sso-full {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; height: 46px; background: #1a1a2e; color: #fff; border: none;
      border-radius: 7px; font-size: 14px; font-weight: 600; cursor: pointer;
      font-family: 'Poppins', sans-serif; transition: background .2s;
    }
    .btn-sso-full:hover { background: #0f3460; }
    .divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; color: #B5B5C3; font-size: 12px; }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #EFF2F5; }
    .spinner {
      width: 17px; height: 17px; border: 2.5px solid rgba(255,255,255,.35);
      border-top-color: #fff; border-radius: 50%; animation: spin .55s linear infinite; display: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .version-tag { text-align: center; color: #B5B5C3; font-size: 11px; margin-top: 24px; }
    .login-panel { display: none; }
    .login-panel.active { display: block; }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
      <div class="login-logo-icon">
        <svg width="30" height="30" viewBox="0 0 48 48" fill="none">
          <path d="M24 14C20.686 14 18 16.686 18 20C18 23.314 20.686 26 24 26C27.314 26 30 23.314 30 20C30 16.686 27.314 14 24 14Z" fill="white"/>
          <path d="M14 36C14 31.582 18.477 28 24 28C29.523 28 34 31.582 34 36H14Z" fill="white" opacity="0.85"/>
        </svg>
      </div>
      <h2>Employee Profile System</h2>
      <p>Sign in to access your profile</p>
    </div>

    <!-- Alerts -->
    <div class="alert-error" id="errorMsg">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#F1416C"/></svg>
      <span id="errorText"></span>
    </div>
    <div class="alert-success" id="successMsg">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z" fill="#47BE7D"/></svg>
      <span id="successText"></span>
    </div>

    <!-- Tab Switcher -->
    <div class="login-tabs" role="tablist">
      <button class="login-tab active" onclick="switchTab('password')" id="tab-password">Email &amp; Password</button>
      <button class="login-tab" onclick="switchTab('sso')" id="tab-sso">HRMS SSO</button>
    </div>

    <!-- Panel 1: Email + Password -->
    <div class="login-panel active" id="panel-password">
      <form id="formPassword" onsubmit="submitPasswordLogin(event)" novalidate>
        <div class="form-group">
          <label class="form-label" for="inputEmail">Email Address</label>
          <div class="input-wrap">
            <input type="email" id="inputEmail" class="form-input" placeholder="you@company.com" autocomplete="email" required />
            <span class="input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="#B5B5C3"/></svg>
            </span>
          </div>
          <div class="field-error" id="emailError"></div>
        </div>
        <div class="form-group">
          <label class="form-label" for="inputPassword">Password</label>
          <div class="input-wrap">
            <input type="password" id="inputPassword" class="form-input" placeholder="Enter your password" autocomplete="current-password" required />
            <span class="input-icon toggle-pw" onclick="togglePassword()" title="Show/hide password">
              <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="#B5B5C3"/></svg>
            </span>
          </div>
          <div class="field-error" id="passwordError"></div>
        </div>
        <div class="form-footer">
          <label class="remember-label">
            <input type="checkbox" id="rememberMe" /> Remember me
          </label>
          <a href="#" class="forgot-link" onclick="showForgotMsg(event)">Forgot password?</a>
        </div>
        <button type="submit" class="btn-primary-login" id="btnPasswordLogin">
          <span id="pwBtnText">Sign In</span>
          <div class="spinner" id="pwSpinner"></div>
        </button>
      </form>
    </div>

    <!-- Panel 2: HRMS SSO -->
    <div class="login-panel" id="panel-sso">
      <p style="font-size:13.5px; color:#5E6278; line-height:1.7; margin-bottom:22px;">
        Sign in using your HRMS credentials. You'll be redirected to the HRMS portal to authenticate,
        then automatically returned to this application.
      </p>
      <button class="btn-sso-full" onclick="initSsoLogin()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" fill="rgba(255,255,255,.85)"/>
        </svg>
        Sign in with HRMS (SSO)
      </button>
      <div class="divider">or enter token manually</div>
      <div class="form-group">
        <label class="form-label" for="ssoTokenInput">HRMS JWT Token</label>
        <div class="input-wrap">
          <input type="text" id="ssoTokenInput" class="form-input" placeholder="Paste HRMS JWT token..." autocomplete="off" />
        </div>
        <div class="dev-note">Paste the <code>hrmsJwtToken</code> value from your HRMS session.</div>
      </div>
      <button class="btn-primary-login" id="btnSsoTokenLogin" onclick="submitSsoToken()">
        <span id="ssoTokenBtnText">Verify &amp; Sign In</span>
        <div class="spinner" id="ssoTokenSpinner"></div>
      </button>
    </div>

    <div class="version-tag">
      v1.0.0 &bull; Employee Profile &amp; HRMS System
    </div>
  </div>
</div>

<script src="assets/plugins/global/plugins.bundle.js"></script>
<script>
  const API_BASE = '<?= API_BASE ?>';

  // Auto-check existing session
  (function () {
    const token = localStorage.getItem('ep_access_token');
    if (!token) return;
    fetch(API_BASE + '/auth/verify', { headers: { 'Authorization': 'Bearer ' + token } })
      .then(function(r) { if (r.ok) window.location.href = 'index.php'; })
      .catch(function() {});
  })();

  // Check for SSO callback token in URL
  (function () {
    const params = new URLSearchParams(window.location.search);
    const token  = params.get('token') || params.get('hrms_token');
    if (token) {
      switchTab('sso');
      performSsoLogin(token);
    }
  })();

  function switchTab(name) {
    ['password', 'sso'].forEach(function(t) {
      var panel = document.getElementById('panel-' + t);
      var tab = document.getElementById('tab-' + t);
      if (panel) panel.classList.toggle('active', t === name);
      if (tab) tab.classList.toggle('active', t === name);
    });
    clearMessages();
  }

  function togglePassword() {
    var inp = document.getElementById('inputPassword');
    var ico = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
      inp.type = 'text';
      ico.querySelector('path').setAttribute('fill', '#009EF7');
    } else {
      inp.type = 'password';
      ico.querySelector('path').setAttribute('fill', '#B5B5C3');
    }
  }

  function showForgotMsg(e) {
    e.preventDefault();
    showError('Please contact your HR administrator to reset your password.');
  }

  // ── Session bridge: sync PHP session after JS login ──
  async function bridgeSession(data) {
    try {
      await fetch('session_bridge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          access_token: data.access_token,
          refresh_token: data.refresh_token || '',
          user: data.user || {}
        })
      });
    } catch(e) { console.warn('Session bridge failed:', e); }
  }

  // ── Panel 1: Email + Password ──
  async function submitPasswordLogin(e) {
    e.preventDefault();
    clearFieldErrors();
    clearMessages();

    var email    = document.getElementById('inputEmail').value.trim();
    var password = document.getElementById('inputPassword').value;
    var valid = true;

    if (!email) { showFieldError('inputEmail', 'emailError', 'Email address is required'); valid = false; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showFieldError('inputEmail', 'emailError', 'Enter a valid email address'); valid = false; }
    if (!password) { showFieldError('inputPassword', 'passwordError', 'Password is required'); valid = false; }
    if (!valid) return;

    setPanelLoading('pw', true);

    try {
      var response = await fetch(API_BASE + '/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, password: password }),
      });

      var data = await response.json();

      if (!response.ok) {
        var msg = (data.messages && data.messages.error) || (typeof data.messages === 'string' ? data.messages : null) || 'Invalid email or password';
        showError(msg);
        return;
      }

      storeSession(data.data);
      await bridgeSession(data.data);
      showSuccess('Login successful! Redirecting\u2026');

      if (document.getElementById('rememberMe').checked) {
        localStorage.setItem('ep_remember_email', email);
      } else {
        localStorage.removeItem('ep_remember_email');
      }

      setTimeout(function() { window.location.href = 'index.php'; }, 550);

    } catch(err) {
      showError('Cannot connect to the server. Please check your network.');
    } finally {
      setPanelLoading('pw', false);
    }
  }

  // Restore remembered email
  (function () {
    var saved = localStorage.getItem('ep_remember_email');
    if (saved) {
      document.getElementById('inputEmail').value = saved;
      document.getElementById('rememberMe').checked = true;
    }
  })();

  // ── Panel 2: HRMS SSO ──
  function initSsoLogin() {
    var callbackUrl = encodeURIComponent(window.location.origin + window.location.pathname);
    var hrmsLoginUrl = 'https://webetron.in/hrms-extension-v2/login?ref_app=employee_profile&callback=' + callbackUrl;
    showSuccess('Redirecting to HRMS login\u2026');
    setTimeout(function() { window.location.href = hrmsLoginUrl; }, 700);
  }

  async function submitSsoToken() {
    var token = document.getElementById('ssoTokenInput').value.trim();
    if (!token) { showError('Please paste a valid HRMS JWT token.'); return; }
    clearMessages();
    setPanelLoading('ssoToken', true);
    await performSsoLogin(token);
    setPanelLoading('ssoToken', false);
  }

  async function performSsoLogin(hrmsToken) {
    try {
      var response = await fetch(API_BASE + '/auth/sso-login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: hrmsToken }),
      });
      var data = await response.json();
      if (!response.ok) {
        var msg = (data.messages && (data.messages.error || data.messages.token)) || (typeof data.messages === 'string' ? data.messages : null) || 'SSO login failed';
        showError(msg);
        return;
      }
      storeSession(data.data);
      await bridgeSession(data.data);
      showSuccess('Login successful! Redirecting\u2026');
      setTimeout(function() { window.location.href = 'index.php'; }, 550);
    } catch(err) {
      showError('Cannot connect to the server. Check the API is running on ' + API_BASE);
    }
  }

  // ── Helpers ──
  function storeSession(d) {
    localStorage.setItem('ep_access_token',  d.access_token);
    localStorage.setItem('ep_refresh_token', d.refresh_token || '');
    localStorage.setItem('ep_user', JSON.stringify(d.user || {}));
    localStorage.setItem('ep_token_expires', d.expires_in ? String(Date.now() + d.expires_in * 1000) : '');
  }

  function setPanelLoading(prefix, on) {
    var btnTextMap = { pw: 'pwBtnText', ssoToken: 'ssoTokenBtnText' };
    var spinnerMap = { pw: 'pwSpinner', ssoToken: 'ssoTokenSpinner' };
    var btnMap     = { pw: 'btnPasswordLogin', ssoToken: 'btnSsoTokenLogin' };
    var btnText = document.getElementById(btnTextMap[prefix]);
    var spinner = document.getElementById(spinnerMap[prefix]);
    var btn = document.getElementById(btnMap[prefix]);
    if (btnText) btnText.style.display = on ? 'none' : '';
    if (spinner) spinner.style.display = on ? 'block' : 'none';
    if (btn) btn.disabled = on;
  }

  function showFieldError(inputId, errorId, msg) {
    var inp = document.getElementById(inputId);
    var err = document.getElementById(errorId);
    if (inp) inp.classList.add('error-field');
    if (err) { err.textContent = msg; err.style.display = 'block'; }
  }

  function clearFieldErrors() {
    document.querySelectorAll('.form-input').forEach(function(el) { el.classList.remove('error-field'); });
    document.querySelectorAll('.field-error').forEach(function(el) { el.textContent = ''; el.style.display = 'none'; });
  }

  function showError(msg) {
    document.getElementById('errorText').textContent = msg;
    document.getElementById('errorMsg').style.display  = 'flex';
    document.getElementById('successMsg').style.display = 'none';
  }

  function showSuccess(msg) {
    document.getElementById('successText').textContent = msg;
    document.getElementById('successMsg').style.display  = 'flex';
    document.getElementById('errorMsg').style.display    = 'none';
  }

  function clearMessages() {
    document.getElementById('errorMsg').style.display   = 'none';
    document.getElementById('successMsg').style.display = 'none';
  }
</script>
</body>
</html>
