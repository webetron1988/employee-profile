# SSO Implementation Plan for Employee Profile Module

## Executive Summary

This plan outlines the comprehensive implementation of Single Sign-On (SSO) authentication for the Workforce Profile module. The current system is a static HTML/jQuery frontend with no authentication layer. This implementation will add a complete client-side SSO integration supporting OAuth 2.0 / OpenID Connect (OIDC) and SAML 2.0 providers, session management, route protection, and seamless user experience.

---

## 1. Current State Analysis

### Existing Architecture
- **Frontend**: Single-page static HTML (`workforce-profile.html`, ~14,184 lines)
- **Framework**: Metronic Admin Theme (Bootstrap 4 + jQuery)
- **Plugins**: Select2, Tagify, Chart.js, Perfect Scrollbar, TinyMCE, DataTables
- **Styling**: Custom CSS (`workforce-profile-module.css`, `employee-profile-v2.css`, `ui-v2.css`, `bn-style.css`)
- **Backend**: None (static prototype)
- **Authentication**: None

### Module Structure (7 Tabs)
| Tab | Content | Status |
|-----|---------|--------|
| Personal Profile | Basic Info, Physical & Health, Family, Contact & Social, Languages & Hobbies, Appreciations, Innovations | Complete |
| Identity & Compliance | ID documents, background checks, compliance records | Placeholder ("coming soon") |
| Job & Organization | Job Title, Grade, Location, Reporting Manager, Contract, Work Type, Org Structure, Working Conditions | Complete |
| TA & Onboarding | Talent acquisition, onboarding workflow | Placeholder ("coming soon") |
| Performance | Goals, KRAs, feedback, reviews | Complete |
| Talent Management | Succession, career growth, skills matrix, mobility | Complete (active default) |
| Learning & Development | Training, certifications, learning goals, education, work history, competency, career path | Complete |

---

## 2. SSO Architecture Design

### 2.1 Authentication Flow

```
┌─────────────┐     ┌──────────────┐     ┌──────────────────┐
│  Login Page  │────>│  SSO Provider │────>│  Callback Handler │
│  (sso-login  │     │  (Google/     │     │  (sso-callback    │
│   .html)     │<────│   Azure AD/   │<────│   .html)          │
└─────────────┘     │   Okta/SAML)  │     └────────┬─────────┘
                    └──────────────┘              │
                                                  │ Token validated
                                                  ▼
                                        ┌──────────────────┐
                                        │ workforce-profile │
                                        │     .html         │
                                        │ (Protected Page)  │
                                        └──────────────────┘
```

### 2.2 Supported SSO Protocols
1. **OAuth 2.0 + OIDC** (Primary) — Google, Microsoft Azure AD, Okta
2. **SAML 2.0** (Enterprise) — Azure AD, OneLogin, PingFederate

### 2.3 Token Strategy
- **Access Token**: Short-lived (15-60 min), stored in memory (JavaScript variable)
- **Refresh Token**: Stored in `httpOnly` cookie (when backend available) or `sessionStorage` (frontend-only mode)
- **ID Token**: OIDC ID token for user identity claims, stored in `sessionStorage`

---

## 3. File Structure (New Files)

```
employee-profile/
├── sso-login.html                    # SSO login page
├── sso-callback.html                 # OAuth/OIDC callback handler
├── sso-logout.html                   # Logout confirmation + redirect
├── sso-error.html                    # Authentication error page
├── assets/
│   ├── css/
│   │   └── sso-login.css            # Login page styles
│   ├── js/
│   │   ├── sso/
│   │   │   ├── sso-config.js        # SSO provider configuration
│   │   │   ├── sso-auth.js          # Core authentication module
│   │   │   ├── sso-providers.js     # Provider-specific logic (Google, Azure, SAML)
│   │   │   ├── sso-session.js       # Session management & token handling
│   │   │   ├── sso-guard.js         # Route protection (auth guard)
│   │   │   └── sso-ui.js            # Auth-related UI updates (header, user menu)
│   │   └── ...
│   └── media/
│       └── images/
│           ├── sso-google.svg        # Google SSO icon
│           ├── sso-microsoft.svg     # Microsoft SSO icon
│           ├── sso-okta.svg          # Okta SSO icon
│           └── sso-saml.svg          # Generic SAML icon
└── workforce-profile.html            # Modified: add auth guard + session UI
```

---

## 4. Detailed Implementation Steps

### Phase 1: SSO Configuration & Core Auth Module

#### Step 1.1 — Create `assets/js/sso/sso-config.js`
SSO provider configuration with environment-based settings.

```javascript
// Configuration object for all SSO providers
var SSOConfig = {
  // Active provider: 'google' | 'azure' | 'okta' | 'saml'
  activeProvider: 'azure',

  // Session settings
  session: {
    tokenKey: 'sso_access_token',
    idTokenKey: 'sso_id_token',
    userKey: 'sso_user_profile',
    sessionTimeout: 3600,           // 1 hour in seconds
    refreshBeforeExpiry: 300,       // Refresh 5 min before expiry
    inactivityTimeout: 1800,        // 30 min inactivity logout
    loginPage: '/sso-login.html',
    callbackPage: '/sso-callback.html',
    logoutPage: '/sso-logout.html',
    protectedPage: '/workforce-profile.html',
    errorPage: '/sso-error.html'
  },

  providers: {
    google: {
      clientId: '<GOOGLE_CLIENT_ID>',
      authEndpoint: 'https://accounts.google.com/o/oauth2/v2/auth',
      tokenEndpoint: 'https://oauth2.googleapis.com/token',
      userInfoEndpoint: 'https://openidconnect.googleapis.com/v1/userinfo',
      scopes: ['openid', 'profile', 'email'],
      responseType: 'code'
    },
    azure: {
      clientId: '<AZURE_CLIENT_ID>',
      tenantId: '<AZURE_TENANT_ID>',
      authEndpoint: 'https://login.microsoftonline.com/{tenantId}/oauth2/v2.0/authorize',
      tokenEndpoint: 'https://login.microsoftonline.com/{tenantId}/oauth2/v2.0/token',
      userInfoEndpoint: 'https://graph.microsoft.com/v1.0/me',
      scopes: ['openid', 'profile', 'email', 'User.Read'],
      responseType: 'code'
    },
    okta: {
      clientId: '<OKTA_CLIENT_ID>',
      domain: '<OKTA_DOMAIN>',
      authEndpoint: 'https://{domain}/oauth2/default/v1/authorize',
      tokenEndpoint: 'https://{domain}/oauth2/default/v1/token',
      userInfoEndpoint: 'https://{domain}/oauth2/default/v1/userinfo',
      scopes: ['openid', 'profile', 'email'],
      responseType: 'code'
    },
    saml: {
      entityId: '<SP_ENTITY_ID>',
      ssoUrl: '<IDP_SSO_URL>',
      sloUrl: '<IDP_SLO_URL>',
      certificate: '<IDP_CERTIFICATE>',
      assertionConsumerServiceUrl: '/sso-callback.html'
    }
  }
};
```

#### Step 1.2 — Create `assets/js/sso/sso-auth.js`
Core authentication module handling login initiation, token exchange, and logout.

**Key functions:**
- `SSOAuth.init()` — Initialize auth module, check existing session
- `SSOAuth.login(provider)` — Initiate SSO login flow with PKCE
- `SSOAuth.handleCallback()` — Process OAuth callback, exchange auth code for tokens
- `SSOAuth.logout()` — Clear session, redirect to IdP logout
- `SSOAuth.refreshToken()` — Refresh access token before expiry
- `SSOAuth.isAuthenticated()` — Check if valid session exists
- `SSOAuth.getUser()` — Get current user profile from token claims
- `SSOAuth.generatePKCE()` — Generate PKCE code_verifier and code_challenge (SHA-256)
- `SSOAuth.generateState()` — Generate anti-CSRF state parameter
- `SSOAuth.generateNonce()` — Generate nonce for OIDC replay protection

**Security measures:**
- PKCE (Proof Key for Code Exchange) for all OAuth flows
- State parameter for CSRF protection
- Nonce validation for ID tokens
- Token expiry validation on every protected page load

#### Step 1.3 — Create `assets/js/sso/sso-providers.js`
Provider-specific adapters for Google, Azure AD, Okta, and SAML.

**Key functions per provider:**
- `buildAuthUrl(config, state, codeChallenge, nonce)` — Construct provider-specific auth URL
- `exchangeCode(code, codeVerifier)` — Exchange authorization code for tokens
- `parseUserInfo(response)` — Normalize user info to standard format
- `buildLogoutUrl(idToken)` — Construct provider-specific logout URL

**Normalized user profile structure:**
```javascript
{
  id: '',
  email: '',
  firstName: '',
  lastName: '',
  displayName: '',
  avatar: '',
  jobTitle: '',
  department: '',
  provider: '',
  roles: [],
  rawClaims: {}
}
```

#### Step 1.4 — Create `assets/js/sso/sso-session.js`
Session lifecycle management.

**Key functions:**
- `SSOSession.create(tokens, userProfile)` — Store tokens and user data
- `SSOSession.destroy()` — Clear all session data
- `SSOSession.getAccessToken()` — Retrieve current access token
- `SSOSession.getIdToken()` — Retrieve current ID token
- `SSOSession.getUserProfile()` — Retrieve cached user profile
- `SSOSession.isExpired()` — Check if session has expired
- `SSOSession.getTimeRemaining()` — Seconds until token expiry
- `SSOSession.startInactivityTimer()` — Track mouse/keyboard activity, auto-logout on inactivity
- `SSOSession.startRefreshTimer()` — Auto-refresh token before expiry
- `SSOSession.extendSession()` — Reset inactivity timer on user activity

---

### Phase 2: SSO Login Page

#### Step 2.1 — Create `sso-login.html`
A branded login page consistent with the existing Metronic design system.

**Features:**
- Company branding (logo, colors matching existing theme)
- SSO provider buttons (Google, Microsoft, Okta)
- Enterprise SSO option (SAML) with domain input
- "Remember me" option (extends session duration)
- Loading state during SSO redirect
- Error message display (returned from callback/error page)
- Responsive design (mobile, tablet, desktop)
- Auto-redirect if already authenticated

**Layout:**
```
┌──────────────────────────────────────────────┐
│                                              │
│          ┌────────────────────────┐           │
│          │       [Company Logo]   │           │
│          │                        │           │
│          │   Workforce Profile    │           │
│          │   Sign in to continue  │           │
│          │                        │           │
│          │  ┌──────────────────┐  │           │
│          │  │ 🔵 Sign in with  │  │           │
│          │  │    Microsoft     │  │           │
│          │  └──────────────────┘  │           │
│          │  ┌──────────────────┐  │           │
│          │  │ 🔴 Sign in with  │  │           │
│          │  │    Google        │  │           │
│          │  └──────────────────┘  │           │
│          │  ┌──────────────────┐  │           │
│          │  │ 🔷 Sign in with  │  │           │
│          │  │    Okta          │  │           │
│          │  └──────────────────┘  │           │
│          │                        │           │
│          │  ── or use Enterprise ──│           │
│          │                        │           │
│          │  ┌──────────────────┐  │           │
│          │  │ Enter company    │  │           │
│          │  │ domain...        │  │           │
│          │  └──────────────────┘  │           │
│          │  [Continue with SAML]  │           │
│          │                        │           │
│          └────────────────────────┘           │
│                                              │
└──────────────────────────────────────────────┘
```

#### Step 2.2 — Create `assets/css/sso-login.css`
Login page styling matching the existing design system:
- Use existing color variables (`--primary: #3699FF`, etc.)
- Poppins font family (consistent with Metronic theme)
- Card-based centered layout with subtle gradient background
- Provider button styles with brand colors
- Responsive breakpoints matching existing `responsive.css`
- Loading spinner animation
- Error/success alert styling

---

### Phase 3: SSO Callback & Error Handling

#### Step 3.1 — Create `sso-callback.html`
Handles the OAuth2/OIDC callback after IdP authentication.

**Flow:**
1. Parse URL parameters (authorization code, state, error)
2. Validate state parameter against stored value (CSRF check)
3. Exchange authorization code for tokens (using PKCE code_verifier)
4. Validate ID token (issuer, audience, nonce, expiry)
5. Extract user profile from ID token claims
6. Create session (store tokens + user profile)
7. Redirect to `workforce-profile.html`

**Error handling:**
- Invalid state → redirect to error page (possible CSRF attack)
- Token exchange failure → show retry option + error details
- User denied consent → redirect to login with message
- Network error → show retry with exponential backoff

#### Step 3.2 — Create `sso-error.html`
Authentication error page with clear messaging.

**Features:**
- Error code and description display
- "Try Again" button (redirect to login)
- "Contact Support" link
- Common error explanations (access denied, session expired, invalid config)

#### Step 3.3 — Create `sso-logout.html`
Logout confirmation and session cleanup page.

**Flow:**
1. Clear all local session data (`sessionStorage`, memory)
2. Display logout confirmation message
3. Redirect to IdP logout endpoint (single logout)
4. After IdP logout, redirect back to login page
5. Auto-redirect to login after 3 seconds

---

### Phase 4: Route Protection (Auth Guard)

#### Step 4.1 — Create `assets/js/sso/sso-guard.js`
Authentication guard that protects `workforce-profile.html`.

**Key functions:**
- `SSOGuard.init()` — Run on page load of protected pages
- `SSOGuard.checkAuth()` — Verify valid session exists, redirect to login if not
- `SSOGuard.checkRoles(requiredRoles)` — Role-based access control
- `SSOGuard.onSessionExpired()` — Handle mid-session token expiry

**Implementation:**
```javascript
// Added to workforce-profile.html <head> section (before other scripts)
// This ensures unauthenticated users are redirected before page renders
(function() {
  var token = sessionStorage.getItem('sso_access_token');
  var expiry = sessionStorage.getItem('sso_token_expiry');
  if (!token || !expiry || Date.now() > parseInt(expiry)) {
    sessionStorage.setItem('sso_redirect_after_login', window.location.href);
    window.location.replace('/sso-login.html');
  }
})();
```

#### Step 4.2 — Modify `workforce-profile.html`
Add auth guard script reference in `<head>` (blocking, before page render):

```html
<!-- SSO Auth Guard (must be first script) -->
<script src="assets/js/sso/sso-config.js"></script>
<script src="assets/js/sso/sso-session.js"></script>
<script src="assets/js/sso/sso-guard.js"></script>
```

---

### Phase 5: User Session UI Integration

#### Step 5.1 — Create `assets/js/sso/sso-ui.js`
Dynamic UI updates based on authenticated user session.

**Features:**
- **Header User Menu**: Replace static user info with SSO user data
  - Display user's name from SSO claims
  - Display user's avatar (from Google/Microsoft profile picture)
  - Display user's email
  - Display user's role/department
- **Quick User Panel** (`#kt_quick_user`): Populate with SSO session info
  - Signed in as: `{email}`
  - Provider: `{Google/Microsoft/Okta}`
  - Session expires: `{time remaining}`
- **Logout Button**: Wire up existing UI logout buttons to `SSOAuth.logout()`
- **Session Timeout Warning**: Modal dialog 5 minutes before session expiry
  - "Your session will expire in X minutes"
  - "Extend Session" button (triggers token refresh)
  - "Logout" button

#### Step 5.2 — Modify `workforce-profile.html` Header Section
Update the following existing UI elements:

1. **User toggle button** (`#kt_quick_user_toggle`, line ~841): Dynamically populate with SSO user avatar and name
2. **Quick user panel** (`#kt_quick_user`): Update user details from SSO claims
3. **Profile banner** (line ~864-984): Optionally pre-fill employee name from SSO if it matches the profile being viewed
4. **Mobile header user area** (`#kt_quick_user_toggle1`, line ~601): Mirror desktop SSO user info

#### Step 5.3 — Add Session Timeout Modal
Add a session expiry warning modal to `workforce-profile.html`:

```html
<!-- SSO Session Timeout Warning Modal -->
<div class="modal fade" id="ssoSessionTimeoutModal" tabindex="-1" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Session Expiring</h5>
      </div>
      <div class="modal-body text-center">
        <p>Your session will expire in <strong id="ssoTimeoutCountdown">5:00</strong></p>
        <p class="text-muted font-13">Would you like to extend your session?</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary" id="ssoExtendSession">Extend Session</button>
        <button type="button" class="btn btn-outline-secondary" id="ssoLogoutNow">Logout</button>
      </div>
    </div>
  </div>
</div>
```

---

### Phase 6: SAML 2.0 Support

#### Step 6.1 — SAML Flow Implementation in `sso-providers.js`
Add SAML 2.0 support for enterprise identity providers.

**SAML Flow:**
1. Generate SAML AuthnRequest XML
2. Encode and sign the request (using Web Crypto API)
3. Redirect to IdP SSO URL with SAMLRequest parameter
4. Receive SAML Response on callback page
5. Parse and validate SAML Assertion
6. Extract user attributes from assertion
7. Create session from SAML attributes

**Note:** Full SAML assertion validation (signature verification, XML canonicalization) requires server-side processing. For the frontend-only implementation:
- Use a lightweight SAML relay approach
- Validate basic assertion structure client-side
- Flag for future backend integration for full cryptographic validation

---

### Phase 7: Security Hardening

#### 7.1 — PKCE Implementation
All OAuth flows will use PKCE (RFC 7636):
- Generate cryptographically random `code_verifier` (43-128 chars)
- Compute `code_challenge` = BASE64URL(SHA256(code_verifier))
- Store `code_verifier` in `sessionStorage` during auth flow
- Include `code_challenge` in authorization request
- Send `code_verifier` in token exchange request

#### 7.2 — State Parameter (CSRF Protection)
- Generate random state parameter before each auth request
- Store state in `sessionStorage`
- Validate returned state matches stored value on callback
- Reject mismatched states (possible CSRF attack)

#### 7.3 — Token Validation
- Validate JWT signature (for providers using JWKs)
- Check `iss` (issuer) matches expected provider
- Check `aud` (audience) matches client ID
- Check `exp` (expiry) is in the future
- Check `nonce` matches stored value (replay protection)

#### 7.4 — Content Security Policy
Add CSP headers via meta tag:
```html
<meta http-equiv="Content-Security-Policy"
  content="default-src 'self';
    script-src 'self' 'unsafe-inline' https://accounts.google.com https://login.microsoftonline.com;
    connect-src 'self' https://accounts.google.com https://login.microsoftonline.com https://graph.microsoft.com;
    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
    font-src 'self' https://fonts.gstatic.com;
    img-src 'self' data: https:;">
```

#### 7.5 — Additional Security Measures
- Clear tokens from memory on page unload (configurable)
- No tokens in URL parameters (use POST-based token exchange)
- Secure session storage (prefix keys, validate integrity)
- Rate limiting on login attempts (client-side throttle)
- HTTPS enforcement check

---

### Phase 8: Testing & Validation

#### 8.1 — Manual Testing Checklist

| # | Test Case | Expected Result |
|---|-----------|-----------------|
| 1 | Access `workforce-profile.html` without session | Redirect to `sso-login.html` |
| 2 | Click "Sign in with Microsoft" | Redirect to Azure AD login |
| 3 | Complete Azure AD login | Redirect back to callback, then to profile |
| 4 | Check header user info | Displays SSO user name, email, avatar |
| 5 | Wait for session expiry (or simulate) | Session timeout modal appears |
| 6 | Click "Extend Session" | Token refreshed, modal closes |
| 7 | Click "Logout" | Session cleared, redirected to login page |
| 8 | Use browser back button after logout | Should not access protected page |
| 9 | Open profile in new tab (same session) | Should be authenticated (shared sessionStorage) |
| 10 | Tamper with state parameter | Error page displayed (CSRF detection) |
| 11 | Use expired token | Redirected to login with "session expired" message |
| 12 | Test with Google provider | Full flow works with Google SSO |
| 13 | Test SAML enterprise login | SAML flow completes successfully |
| 14 | Test responsive login page (mobile) | Login page renders correctly on mobile |
| 15 | Test inactivity timeout | Auto-logout after 30 min of inactivity |

#### 8.2 — Cross-Browser Testing
- Chrome 90+
- Firefox 90+
- Safari 14+
- Edge 90+
- Mobile Safari (iOS 14+)
- Chrome Mobile (Android)

---

## 5. Implementation Order & Dependencies

```
Phase 1: SSO Config & Core Auth (Foundation)
   │
   ├── Step 1.1: sso-config.js
   ├── Step 1.2: sso-auth.js
   ├── Step 1.3: sso-providers.js
   └── Step 1.4: sso-session.js
         │
Phase 2: SSO Login Page ──────────────── Phase 4: Auth Guard
   │                                        │
   ├── Step 2.1: sso-login.html             ├── Step 4.1: sso-guard.js
   └── Step 2.2: sso-login.css              └── Step 4.2: Modify workforce-profile.html
         │                                        │
Phase 3: Callback & Error ─────────────── Phase 5: User Session UI
   │                                        │
   ├── Step 3.1: sso-callback.html          ├── Step 5.1: sso-ui.js
   ├── Step 3.2: sso-error.html             ├── Step 5.2: Modify header section
   └── Step 3.3: sso-logout.html            └── Step 5.3: Session timeout modal
         │                                        │
Phase 6: SAML Support ─────────────────── Phase 7: Security Hardening
         │
Phase 8: Testing & Validation
```

---

## 6. Modifications to Existing Files

### `workforce-profile.html` — Changes Summary

| Line Area | Change | Description |
|-----------|--------|-------------|
| `<head>` (line 5-30) | Add SSO script includes | Add `sso-config.js`, `sso-session.js`, `sso-guard.js` before other scripts |
| `<head>` (line 5-30) | Add CSP meta tag | Content Security Policy for SSO domains |
| Header user toggle (~line 841) | Dynamic content | Replace static user info with SSO-populated data |
| Quick user panel | Dynamic content | Populate with SSO user profile and session info |
| Before `</body>` (~line 14184) | Add SSO scripts | Include `sso-auth.js`, `sso-providers.js`, `sso-ui.js` |
| Before `</body>` | Add session modal | Session timeout warning modal HTML |
| Mobile header (~line 601) | Dynamic content | Mirror SSO user info for mobile view |

---

## 7. Configuration Required Before Deployment

Before the SSO implementation can be used in a real environment, the following must be configured:

1. **OAuth App Registration** — Register app with each SSO provider:
   - Google Cloud Console → OAuth 2.0 Client ID
   - Azure Portal → App Registration → Client ID + Tenant ID
   - Okta Admin → Application → Client ID + Domain

2. **Redirect URI Configuration** — Set callback URL in each provider:
   - `https://<your-domain>/sso-callback.html`

3. **SAML Configuration** (if using enterprise SAML):
   - Exchange SP metadata with IdP
   - Configure assertion consumer service URL
   - Import IdP certificate

4. **Update `sso-config.js`** — Replace placeholder values:
   - `<GOOGLE_CLIENT_ID>` → Actual Google Client ID
   - `<AZURE_CLIENT_ID>` → Actual Azure Client ID
   - `<AZURE_TENANT_ID>` → Actual Azure Tenant ID
   - `<OKTA_CLIENT_ID>` → Actual Okta Client ID
   - `<OKTA_DOMAIN>` → Actual Okta domain

5. **HTTPS** — SSO requires HTTPS in production

---

## 8. Future Enhancements (Out of Scope)

These items are noted for future implementation but are **not** part of this plan:

- **Backend token exchange** — Move token exchange server-side for better security
- **Role-Based Access Control (RBAC)** — Restrict tab/field visibility based on user roles
- **Multi-Factor Authentication (MFA)** — Enforce MFA via IdP policies
- **Audit Logging** — Log authentication events (login, logout, session refresh)
- **API Integration** — Connect SSO tokens to backend API calls for real employee data
- **Identity & Compliance tab** — Populate the "coming soon" tab with ID document verification linked to SSO identity
- **TA & Onboarding tab** — Populate with onboarding workflow tied to SSO provisioning

---

## 9. Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Token stored in sessionStorage (XSS vulnerable) | High | CSP headers, input sanitization, migrate to httpOnly cookies with backend |
| SAML validation client-side only | Medium | Flag as "demo mode", require backend for production SAML |
| SSO provider outage | Medium | Cache last-known session, display friendly error |
| Browser incompatibility (Web Crypto API) | Low | Polyfill for older browsers, fallback to plain state |
| Configuration exposure (client IDs in JS) | Low | Client IDs are public by design in OAuth; no secrets in frontend |

---

## 10. Estimated Deliverables

| Deliverable | Files |
|-------------|-------|
| SSO Login Page | `sso-login.html`, `assets/css/sso-login.css` |
| SSO Core Module | `assets/js/sso/sso-config.js`, `sso-auth.js`, `sso-providers.js`, `sso-session.js` |
| Auth Guard | `assets/js/sso/sso-guard.js` |
| UI Integration | `assets/js/sso/sso-ui.js` |
| Callback Handler | `sso-callback.html` |
| Logout Page | `sso-logout.html` |
| Error Page | `sso-error.html` |
| Modified Files | `workforce-profile.html` (auth guard + session UI + timeout modal) |
| SSO Provider Icons | `assets/media/images/sso-*.svg` (4 files) |
| **Total New Files** | **13 files** |
| **Total Modified Files** | **1 file** |
