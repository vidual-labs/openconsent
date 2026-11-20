# OpenConsent - Cookie Banner for WordPress, Joomla and any website

## Overview

**OpenConsent** is a lightweight, developer-friendly cookie banner designed for **Google Consent Mode v2 (Basic)** compliance. It provides a customizable cookie consent banner that integrates seamlessly with Google Tag Manager (GTM) and, on WordPress, the WordPress Consent API.

### Monorepo Layout
One shared frontend, three packages, **one shared version**:

| Folder | Content |
|---|---|
| `core/` | Shared frontend, the only copy: `openconsent.js` (banner, no dependencies), `openconsent-head.js` (Consent Mode defaults, inlined in `<head>`), `openconsent.css` |
| `wordpress/` | WordPress plugin; `js/wp-consent-bridge.js` connects the core to the WP Consent API |
| `joomla/` | Joomla 5/6 system plugin `plg_system_openconsent` (settings form in `openconsent.xml`, en-GB + de-DE) |
| `snippet/` | `example.html` for the universal snippet (`openconsent.js` = head script + CSS + banner in one file) |
| `build.mjs` | `node build.mjs` builds `dist/openconsent-wordpress.zip`, `dist/openconsent-joomla.zip`, `dist/openconsent.js` |

- Core files are **not** committed inside `wordpress/` or `joomla/`; the build copies them in (`js/`, `css/` resp. `media/js/`, `media/css/`)
- Behaviour changes to the banner belong in `core/`, not in a CMS package
- CMS-specific features (WP Consent API, Site Kit, shortcode) stay in their package and talk to the core via the events `openconsent:ready` / `openconsent:choice`

### Key Purpose
This plugin replaces bloated, expensive consent management plugins by providing a focused, efficient solution specifically for Google Consent Mode v2 implementation. It enables:

- ✅ **GDPR/CCPA Compliance**: Full cookie consent user choice implementation
- ✅ **Google Consent Mode v2**: Proper default consent states before tags fire
- ✅ **Google Tag Manager Integration**: Standalone or with Google Site Kit
- ✅ **WordPress Consent API**: Native WP 6.5+ consent integration
- ✅ **Conversion Tracking**: Support for GCLID/WBRAID parameters
- ✅ **Accessibility**: WCAG 2.1 Level AAA compliance

### Design Principle: Keep It Simple
The banner is deliberately one decision: **Accept** or **Decline** for everything. This is a core product decision by the maintainer.

- Do **not** add granular consent categories, per-purpose toggles or a second "settings" layer
- Do **not** propose them as an improvement in reviews
- New features must keep the banner and the settings page small

---

## Critical Features for Development

### 1. Google Consent Mode v2 Implementation
The plugin implements **Basic Consent Mode** with the following approach:

- **Default State**: All consent types (`ad_storage`, `analytics_storage`, etc.) default to `denied` before GTM loads
- **User Choice**: Banner allows users to explicitly grant or deny consent
- **Dynamic Loading**: GTM script only loads after user grants consent
- **Consent Update**: Uses `gtag('consent', 'update', {...})` to update Google's consent state
- **Only consent types in consent commands**: settings such as `ads_data_redaction` go through `gtag('set', ...)`, never into `consent` commands
- **Returning visitors**: the inline `<head>` script reads the consent cookie, sends the update and loads GTM
- **No regional targeting**: defaults apply to all visitors

**Important Files**:
- `core/openconsent-head.js` - Consent defaults + GTM for returning visitors (shared)
- `core/openconsent.js` - Client-side consent logic and GTM loading (shared)
- `wordpress/openconsent.php` - WordPress main file, inlines config + head script
- `wordpress/admin/admin-einstellungen.php` - WordPress settings and validation
- `joomla/src/Extension/OpenConsent.php` - Joomla plugin, inserts config + head script after `<head>`

### 2. Dual-Mode Architecture
The plugin automatically adapts based on what's active:

**Mode 1: Google Site Kit Active**
- Plugin acts as UI provider only
- Passes consent to WordPress Consent API
- Site Kit manages all Google scripts

**Mode 2: Standalone (No Site Kit)**
- Plugin loads GTM directly
- Manages full consent flow
- Injects default consent script in `<head>`

### 3. WordPress Consent API Support
- Works with the WP Consent API plugin (`wp-consent-api`); registers as an opt-in consent manager
- Sets consent for categories: `statistics`, `statistics-anonymous`, `marketing`, `preferences` (`functional` is always allowed)
- Client-side via `wp_set_consent()` when available; AJAX fallback: `wp_ajax_openconsent_update_consent`

---

## Important Code Areas

### Backend (PHP)
**File**: `wordpress/openconsent.php` (plus `uninstall.php`, which deletes the option on plugin removal)

Key Methods:
- `init_plugin()` - Registers hooks and initializes plugin
- `enqueue_frontend_scripts()` - Enqueues CSS, WP Consent API bridge and banner script
- `get_frontend_config()` - Builds `window.openconsentConfig`
- `insert_head_script()` - Prints config + `js/openconsent-head.js` in `<head>` (priority 1)
- `ajax_update_wp_consent()` - Handles consent AJAX requests

**File**: `wordpress/admin/admin-einstellungen.php`

Key Methods:
- `register_settings()` - Registers all settings and fields
- `sanitize_settings()` - Validates and sanitizes all inputs
- `settings_page_html()` - Main settings page template
- `handle_admin_actions()` - Runs export/import/reset on `admin_init` (before output)
- `handle_export_settings()` - Exports settings as JSON
- `handle_import_settings()` - Imports settings from JSON
- `handle_reset_settings()` - Resets to default values

**File**: `joomla/src/Extension/OpenConsent.php` (Joomla)

- `onBeforeCompileHead()` - Adds CSS and banner script (defer) via the WebAssetManager
- `onAfterRender()` - Inserts config + head script right after `<head>` (site, HTML only)
- `getFrontendConfig()` - Builds `window.openconsentConfig` from the plugin params (validated)
- `services/provider.php` - Creates the plugin; passes the dispatcher only on Joomla < 5.4

### Frontend (JavaScript)
**File**: `core/openconsent.js` (no jQuery; renders banner + widget from the config)

Key Functions:
- `setCookie()` - Secure cookie handling with SameSite/Secure flags
- `getCookie()` - Retrieves consent cookie value
- `loadGtmScript()` - Loads GTM script in standalone mode
- `updateGoogleConsentMode()` - Updates Google Consent Mode state
- `removeTrackingCookies()` - Deletes Google cookies when consent is declined/withdrawn
- `renderElements()` - Builds banner and widget (skipped if `#oc-cookie-banner` already exists)

**File**: `wordpress/js/wp-consent-bridge.js` - `update()` / `sync()` for the WP Consent API (client-side, AJAX fallback via `fetch`)

### Frontend (CSS)
**File**: `core/openconsent.css`

Key Features:
- CSS Custom Properties for theming
- WCAG 2.1 Level AAA accessibility
- Focus states for keyboard navigation
- Responsive design with mobile optimizations
- Support for `prefers-reduced-motion`
- 11 positioning options for banner placement

---

## Development Workflow & Version Management

### **IMPORTANT: Version Bump Policy**

⚠️ **Every commit must include**:
1. **Version bump** in `wordpress/openconsent.php` (plugin header + `OC_VERSION` constant) **and** `joomla/openconsent.xml` (`<version>`); `build.mjs` fails if they differ
2. **Updated README.md** with new features/changes
3. **Updated RELEASE_NOTES.md** with version entry and date

### Version Format
```
Version X.Y.Z

Where:
- X = Major (breaking changes, major features)
- Y = Minor (new features, improvements)
- Z = Patch (bug fixes, small adjustments)
```

### Example Workflow
```bash
# 1. Make code changes
# 2. Update version in wordpress/openconsent.php and joomla/openconsent.xml
# 3. Update RELEASE_NOTES.md with new entry
# 4. Update README.md if adding features
# 5. Commit with message including version

git add wordpress/openconsent.php joomla/openconsent.xml RELEASE_NOTES.md README.md [other files]
git commit -m "feat: Add feature XYZ (v1.1.1)"
```

### Releases
Publishing a GitHub release runs `.github/workflows/release-zip.yml`, which runs `node build.mjs --tag <tag>` and attaches `openconsent-wordpress.zip` (top-level folder `openconsent`), `openconsent-joomla.zip` and `openconsent.js` to the release.

- The tag must be `v` + the version (e.g. `v1.2.0`); the build fails on a mismatch between the tag, the WordPress header, `OC_VERSION` and the Joomla manifest
- The workflow can be started manually for an existing tag (workflow_dispatch)

### Current Version
- **Latest**: v1.2.0 (2026-11-20)
- **Previous**: v1.1.3 (2026-11-20)

---

## Configuration & Settings

WordPress: all settings stored in the options table under `openconsent_settings`. Joomla: the same keys as plugin params (no `enable_banner`: the plugin's own enabled state; no import/export/reset).

### Settings Structure
```php
$options = get_option('openconsent_settings');

// Core settings
'enable_banner'              // bool: Enable/disable plugin
'enable_minimized_widget'    // bool: Show re-open widget
'gtm_id'                     // string: GTM-XXXXXXX format
'cookie_duration_days'       // int: 1-390, default 180 (365 for installs saved before v1.1.2)

// Content
'banner_text'                // HTML: Main banner message
'accept_text'                // string: Accept button label
'decline_text'               // string: Decline button label
'minimized_text'             // string: Re-open widget label

// Design
'banner_position'            // string: One of 11 positions
'banner_bg_color'            // hex: Banner background
'banner_text_color'          // hex: Banner text color
'link_color'                 // hex: Link color in banner
'accept_button_bg_color'     // hex: Accept button background
'accept_button_text_color'   // hex: Accept button text
'decline_button_bg_color'    // hex: Decline button background
'decline_button_text_color'  // hex: Decline button text
'widget_bg_color'            // hex: Widget background
'widget_text_color'          // hex: Widget text color
```

---

## Frontend Config

All packages pass the settings as `window.openconsentConfig`, printed in `<head>` together with the head script (WordPress, Joomla) or written by the site owner (snippet). Missing or empty values fall back to the defaults in `core/openconsent.js`.

```javascript
window.openconsentConfig = {
    'gtm_id':                  string (GTM-XXXXXXX, validated)
    'should_load_own_gtm':     boolean (snippet: derived from gtm_id)
    'banner_text':             HTML
    'accept_text':             string
    'decline_text':            string
    'minimized_text':          string
    'banner_label':            string (aria-label)
    'enable_minimized_widget': boolean
    'cookie_name':             'openconsent_status'
    'cookie_duration_days':    number (1-390)
    'banner_position':         string (one of 11)
    '<name>_color':            hex (the 9 design colors)
    'debug':                   boolean (WP_DEBUG / JDEBUG)
    // WordPress only (bridge):
    'consent_categories':      string[]
    'wp_consent_api_active':   boolean
    'ajax_url':                string
    'ajax_nonce':              string
}
```

Events on `document`: `openconsent:ready` (`detail.status`) and `openconsent:choice` (`detail.status`, `detail.previous`, `detail.waitUntil(promise)`; the reload waits up to 5 s).

---

## Cookie Structure

### Main Consent Cookie
```
Name: openconsent_status
Value: 'granted' | 'denied'
Duration: configurable, default 180 days
Flags: SameSite=Lax; Secure (on HTTPS)
Path: /
```

### Conversion Tracking Cookies
The plugin no longer writes its own `gclid` / `wbraid` cookies (removed in v1.1.2; Google's Conversion Linker handles click IDs). On decline, Google cookies (`_ga*`, `_gid`, `_gat*`, `_gcl_*`, `_gac_*`) and legacy `gclid` / `wbraid` cookies are deleted.

---

## AJAX Endpoints

### Update WP Consent API
```
POST /wp-admin/admin-ajax.php
action: openconsent_update_consent
nonce: [generated via wp_create_nonce()]
status: 'granted' | 'denied'
```

**Response** (JSON):
```json
{
    "success": true,
    "data": {
        "message": "WP Consent API updated to: allow",
        "status": "granted"
    }
}
```

---

## Hooks & Filters

### Available Hooks for Extension
Currently, the plugin uses WordPress standard hooks:
- `plugins_loaded` - Plugin initialization
- `init` - Text domain loading
- `admin_menu` - Admin menu registration
- `admin_init` - Admin settings registration
- `wp_enqueue_scripts` - Frontend script/style loading
- `wp_footer` - Banner HTML output
- `wp_head` - Config + head script (priority 1)
- Joomla: `onBeforeCompileHead`, `onAfterRender`

---

## Security Considerations

✅ **Implemented Security Measures**:
- Nonce verification on all AJAX calls
- Input sanitization via `sanitize_text_field()`, `sanitize_hex_color()`, `sanitize_key()`
- Output escaping via `esc_html()`, `esc_attr()`
- HTML sanitization via `wp_kses_post()`
- GTM ID validation with regex pattern
- Try-catch error handling throughout

⚠️ **Security Notes**:
- Cookie uses `SameSite=Lax` by default (compliant with modern standards)
- Secure flag added automatically for HTTPS sites
- No sensitive data stored in cookies
- AJAX responses validated on frontend

---

## Testing Checklist

Before releasing a new version:

- [ ] PHP syntax validation (`php -l` on all .php files)
- [ ] `node build.mjs` succeeds; install the plugins from `dist/`
- [ ] Settings import/export functionality works
- [ ] Banner displays correctly at all 11 positions
- [ ] Accept/Decline buttons work and trigger page reload
- [ ] Google Consent Mode API updates properly
- [ ] GTM loads in standalone mode
- [ ] WordPress Consent API integration works
- [ ] Google Site Kit detection works
- [ ] Responsive design on mobile (< 768px)
- [ ] Keyboard navigation (Tab, Enter)
- [ ] Screen reader compatible
- [ ] Declining after accepting removes Google cookies
- [ ] `[openconsent_settings_link]` re-opens the banner
- [ ] Prefers-reduced-motion respected
- [ ] Color picker works in admin
- [ ] Banner text editor (wp_editor) works
- [ ] No console errors
- [ ] Joomla: plugin installs/updates, settings form saves, banner + `#openconsent` link work, texts in en-GB and de-DE
- [ ] Snippet: `snippet/example.html` with `dist/openconsent.js` works (defaults, Accept loads GTM, Decline removes cookies)

---

## Debugging

### Console Logging
With `WP_DEBUG` enabled, the plugin logs to the browser console:
```javascript
// Helpful messages for troubleshooting
OpenConsent: Found existing consent cookie - GRANTED
OpenConsent: Updating Google Consent Mode to GRANTED
OpenConsent: WP Consent API updated successfully
```

### WordPress Debug
Enable in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## License & Attribution

- **License**: GPL v2 or later
- **Author**: vidual (https://vidual.org)
- **GitHub**: https://github.com/vidual-labs/openconsent
- **Latest Claude Refactoring**: v1.2.0 (2026-11-20)

---

## Quick Reference

| Item | Value |
|------|-------|
| Current Version | 1.2.0 |
| Minimum WordPress | 5.0 |
| Minimum PHP | 7.0 (WordPress) |
| Joomla | 5, 6 (`plg_system_openconsent`) |
| Main Files | wordpress/openconsent.php, joomla/openconsent.xml |
| Settings Key | openconsent_settings |
| Cookie Name | openconsent_status |
| Main Slug | openconsent_settings |
| Text Domain | openconsent |

