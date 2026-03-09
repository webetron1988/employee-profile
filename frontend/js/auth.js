/**
 * Employee Profile System - Auth Module
 * Manages JWT storage, refresh, and session lifecycle
 */

const Auth = {
  // ── Storage keys ──────────────────────────────────────────────────────────
  KEYS: {
    ACCESS_TOKEN:  'ep_access_token',
    REFRESH_TOKEN: 'ep_refresh_token',
    USER:          'ep_user',
    EXPIRES_AT:    'ep_token_expires',
  },

  getAccessToken() {
    return localStorage.getItem(this.KEYS.ACCESS_TOKEN);
  },

  getRefreshToken() {
    return localStorage.getItem(this.KEYS.REFRESH_TOKEN);
  },

  getUser() {
    try {
      return JSON.parse(localStorage.getItem(this.KEYS.USER)) || null;
    } catch { return null; }
  },

  isLoggedIn() {
    return !!this.getAccessToken();
  },

  isTokenExpired() {
    const exp = localStorage.getItem(this.KEYS.EXPIRES_AT);
    if (!exp) return false;
    // Add 60s buffer before actual expiry
    return Date.now() > (parseInt(exp) - 60000);
  },

  setSession(data) {
    localStorage.setItem(this.KEYS.ACCESS_TOKEN, data.access_token);
    if (data.refresh_token) {
      localStorage.setItem(this.KEYS.REFRESH_TOKEN, data.refresh_token);
    }
    if (data.user) {
      localStorage.setItem(this.KEYS.USER, JSON.stringify(data.user));
    }
    if (data.expires_in) {
      localStorage.setItem(this.KEYS.EXPIRES_AT, Date.now() + data.expires_in * 1000);
    }
    // Bridge to PHP session
    try {
      fetch('session_bridge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          access_token: data.access_token,
          refresh_token: data.refresh_token || '',
          user: data.user || {}
        })
      });
    } catch(e) { /* ignore */ }
  },

  clearSession() {
    Object.values(this.KEYS).forEach(k => localStorage.removeItem(k));
  },

  // ── Token refresh ─────────────────────────────────────────────────────────
  _refreshPromise: null,

  async refresh() {
    // Deduplicate concurrent refresh calls
    if (this._refreshPromise) return this._refreshPromise;

    this._refreshPromise = (async () => {
      const refreshToken = this.getRefreshToken();
      if (!refreshToken) return false;
      try {
        const res = await fetch(`${API_BASE}/auth/refresh`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + this.getAccessToken() },
          body: JSON.stringify({ refresh_token: refreshToken }),
        });
        if (!res.ok) return false;
        const data = await res.json();
        if (data?.data?.access_token) {
          this.setSession(data.data);
          return true;
        }
        return false;
      } catch { return false; }
      finally { this._refreshPromise = null; }
    })();

    return this._refreshPromise;
  },

  // ── Logout ────────────────────────────────────────────────────────────────
  async logout() {
    try {
      await fetch(`${API_BASE}/auth/logout`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + this.getAccessToken(),
        },
        body: JSON.stringify({}),
      });
    } catch {}
    this.clearSession();
    // Destroy PHP session then redirect
    window.location.href = 'logout.php';
  },

  // ── Route guard ───────────────────────────────────────────────────────────
  requireAuth() {
    if (!this.isLoggedIn()) {
      window.location.href = 'login.php';
      return false;
    }
    // Silently refresh if token is near expiry
    if (this.isTokenExpired()) {
      this.refresh().then(ok => {
        if (!ok) { this.clearSession(); window.location.href = 'login.php'; }
      });
    }
    return true;
  },

  // ── User helpers ──────────────────────────────────────────────────────────
  getUserRole() {
    return this.getUser()?.role || 'employee';
  },

  hasRole(...roles) {
    return roles.includes(this.getUserRole());
  },

  getUserName() {
    const u = this.getUser();
    if (!u) return 'User';
    return u.name || u.email?.split('@')[0] || 'User';
  },

  getAvatarInitials() {
    const name = this.getUserName();
    const parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
  },
};

// ── Auto-refresh: refresh token 5 min before expiry ──────────────────────────
setInterval(async () => {
  if (Auth.isLoggedIn() && Auth.isTokenExpired()) {
    await Auth.refresh();
  }
}, 60 * 1000); // Check every 60 seconds
