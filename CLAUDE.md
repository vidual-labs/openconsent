# OpenConsent - WordPress Cookie Banner Plugin

## Overview

**OpenConsent** is a lightweight, developer-friendly WordPress plugin designed for **Google Consent Mode v2 (Basic)** compliance. It provides a customizable cookie consent banner that integrates seamlessly with Google Tag Manager (GTM) and the WordPress Consent API.

### Key Purpose
This plugin replaces bloated, expensive consent management plugins by providing a focused, efficient solution specifically for Google Consent Mode v2 implementation. It enables:

- ✅ **GDPR/CCPA Compliance**: Full cookie consent user choice implementation
- ✅ **Google Consent Mode v2**: Proper default consent states before tags fire
- ✅ **Google Tag Manager Integration**: Standalone or with Google Site Kit
- ✅ **WordPress Consent API**: Native WP 6.5+ consent integration
- ✅ **Conversion Tracking**: Support for GCLID/WBRAID parameters
- ✅ **Accessibility**: WCAG 2.1 Level AAA compliance

---

## Critical Features for Development

### 1. Google Consent Mode v2 Implementation
The plugin implements **Basic Consent Mode** with the following approach:

- **Default State**: All consent types (`ad_storage`, `analytics_storage`, etc.) default to `denied` before GTM loads
- **User Choice**: Banner allows users to explicitly grant or deny consent
- **Dynamic Loading**: GTM script only loads after user grants consent
- **Consent Update**: Uses `gtag('consent', 'update', {...})` to update Google's consent state
- **EU Regional Targeting**: Includes regional targeting for GDPR compliance

**Important Files**:
- `openconsent.php` - Main plugin file, handles consent default injection
- `js/frontend-consent.js` - Client-side consent logic and GTM loading
- `admin/admin-einstellungen.php` - Backend settings and validation

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
- Implements native WP Consent API (WP 6.5+)
- Sets consent for categories: `analytics`, `marketing`, `preferences`, `functional`
- AJAX endpoint: `wp_ajax_openconsent_update_consent`

---

## Important Code Areas

### Backend (PHP)
**File**: `openconsent.php`

Key Methods:
- `init_plugin()` - Registers hooks and initializes plugin
- `enqueue_frontend_scripts()` - Sends settings to frontend via `wp_localize_script()`
- `insert_gtm_consent_default()` - Injects default consent script in `<head>` (standalone mode only)
- `display_elements()` - Renders banner HTML with inline styles
- `ajax_update_wp_consent()` - Handles consent AJAX requests

**File**: `admin/admin-einstellungen.php`

Key Methods:
- `register_settings()` - Registers all settings and fields
- `sanitize_settings()` - Validates and sanitizes all inputs
- `settings_page_html()` - Main settings page template
- `handle_export_settings()` - Exports settings as JSON
- `handle_import_settings()` - Imports settings from JSON
- `handle_reset_settings()` - Resets to default values

### Frontend (JavaScript)
**File**: `js/frontend-consent.js`

Key Functions:
- `setCookie()` - Secure cookie handling with SameSite/Secure flags
- `getCookie()` - Retrieves consent cookie value
- `loadGtmScript()` - Loads GTM script in standalone mode
- `updateGoogleConsentMode()` - Updates Google Consent Mode state
- `handleConversionTrackingParameters()` - Manages GCLID/WBRAID
- `updateWPConsentAPI()` - AJAX call to update WP Consent API

### Frontend (CSS)
**File**: `css/frontend-consent.css`

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
1. **Version bump** in `openconsent.php` (line 19: `OC_VERSION` constant)
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
# 2. Update version in openconsent.php
# 3. Update RELEASE_NOTES.md with new entry
# 4. Update README.md if adding features
# 5. Commit with message including version

git add openconsent.php RELEASE_NOTES.md README.md [other files]
git commit -m "feat: Add feature XYZ (v1.1.1)"
```

### Current Version
- **Latest**: v1.1.0 (2026-04-16)
- **Previous**: v1.0.8 (2025-06-19)

---

## Configuration & Settings

All settings stored in WordPress options table under `openconsent_settings`.

### Settings Structure
```php
$options = get_option('openconsent_settings');

// Core settings
'enable_banner'              // bool: Enable/disable plugin
'enable_minimized_widget'    // bool: Show re-open widget
'gtm_id'                     // string: GTM-XXXXXXX format

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

## Frontend Data Localization

JavaScript receives configuration via `wp_localize_script()` in `oc_js_vars`:

```javascript
window.oc_js_vars = {
    'gtm_id':                  string
    'should_load_own_gtm':     boolean
    'banner_text':             HTML
    'accept_text':             string
    'decline_text':            string
    'minimized_text':          string
    'enable_minimized_widget': boolean
    'cookie_name':             'openconsent_status'
    'cookie_duration_days':    365
    'banner_position':         string
    'ajax_url':                string
    'ajax_nonce':              string
}
```

---

## Cookie Structure

### Main Consent Cookie
```
Name: openconsent_status
Value: 'granted' | 'denied'
Duration: 365 days
Flags: SameSite=Lax; Secure (on HTTPS)
Path: /
```

### Conversion Tracking Cookies (if consent granted)
```
Name: gclid (Google Click ID)
Duration: 90 days
```

```
Name: wbraid (Web Attribution ID)
Duration: 90 days
```

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
- `wp_head` - GTM consent default (standalone mode)

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
- [ ] GCLID/WBRAID parameters handled
- [ ] Prefers-reduced-motion respected
- [ ] Color picker works in admin
- [ ] Banner text editor (wp_editor) works
- [ ] No console errors

---

## Debugging

### Console Logging
The plugin logs extensively to browser console:
```javascript
// Helpful messages for troubleshooting
OpenConsent: Found existing consent cookie - GRANTED
OpenConsent: Updating Google Consent Mode to GRANTED
OpenConsent: GCLID detected - conversion tracking enabled
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
- **Latest Claude Refactoring**: v1.1.0 (2026-04-16)

---

## Quick Reference

| Item | Value |
|------|-------|
| Current Version | 1.1.0 |
| Minimum WordPress | 5.0 |
| Minimum PHP | 7.0 |
| Main File | openconsent.php |
| Settings Key | openconsent_settings |
| Cookie Name | openconsent_status |
| Main Slug | openconsent_settings |
| Text Domain | openconsent |

