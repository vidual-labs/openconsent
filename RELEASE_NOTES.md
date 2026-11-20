# OpenConsent Plugin - Release Notes

## Version 1.3.0 (Current) - 2026-11-20

### TYPO3 Extension and Versioned Release Files

OpenConsent now also comes as a TYPO3 extension. Every release contains `openconsent-wordpress-X.Y.Z.zip`, `openconsent-joomla-X.Y.Z.zip`, `openconsent_X.Y.Z.zip` (TYPO3) and `openconsent-X.Y.Z.js`.

**TYPO3**
* **Added:** TYPO3 12.4, 13.4 and 14 extension `openconsent` (`typo3/`, Composer package `vidual/openconsent`). A frontend middleware adds the config and the head script right after `<head>` and the banner script and styles before `</head>` on every HTML page, including cached pages; no TypoScript needed
* **Added:** Settings in **Admin Tools > Settings > Extension Configuration** (global for the installation): show banner, GTM ID, choice duration, re-open widget, texts, position and colors. Custom banner HTML is cleaned with TYPO3's HTML sanitizer
* **Added:** Default texts in English and German follow the language of the page; any link to `#openconsent` re-opens the banner

**Build & release**
* **Changed:** Release files carry the version from the tag, e.g. `openconsent-wordpress-1.3.0.zip`. The folder inside the WordPress zip is still `openconsent`, so uploading it updates an existing installation
* **Changed:** The TYPO3 zip is named `openconsent_X.Y.Z.zip` because the TYPO3 Extension Manager reads the extension key from the file name
* **Changed:** `build.mjs` also checks the version in `typo3/ext_emconf.php`; the workflow also checks the TYPO3 PHP files

---

## Version 1.2.0 - 2026-11-20

### Joomla Plugin, Universal Snippet and Shared Core

OpenConsent now comes in three packages that share one banner script. Every release contains `openconsent-wordpress.zip`, `openconsent-joomla.zip` and `openconsent.js`.

**New packages**
* **Added:** Joomla 5/6 system plugin (`joomla/`). Settings: GTM ID, choice duration, re-open widget, texts, position and colors. Ships with English and German texts; any link to `#openconsent` (e.g. a menu item of the type URL) re-opens the banner
* **Added:** Universal snippet `openconsent.js` for any website (static sites, Webflow, Squarespace, Ghost, …): one self-hosted file plus a small `window.openconsentConfig` object

**Shared core (`core/`)**
* **Changed:** The banner script no longer needs jQuery. It renders the banner itself from `window.openconsentConfig` (previously `oc_js_vars`), so the markup lives in one place
* **Changed:** The head script (Consent Mode defaults, GTM for returning visitors) is a shared file that the WordPress and Joomla plugins inline before any other script
* **Added:** Events `openconsent:ready` and `openconsent:choice` on `document` for integrations

**WordPress**
* **Changed:** The plugin moved to `wordpress/`; the WP Consent API part is now a small bridge script (`js/wp-consent-bridge.js`). The AJAX fallback uses `fetch` instead of jQuery
* **Changed:** Release asset renamed from `openconsent.zip` to `openconsent-wordpress.zip`. The folder inside is still `openconsent`, so uploading it updates an existing installation. Settings and the consent cookie are unchanged

**Build & release**
* **Added:** `build.mjs` (Node, no dependencies) builds all packages into `dist/` and fails if the versions in `wordpress/openconsent.php` and `joomla/openconsent.xml` differ
* **Changed:** The release workflow builds and attaches all three packages

---

## Version 1.1.3 - 2026-11-20

### Automated Release ZIP

No changes to the plugin's behaviour.

* **Added:** GitHub Actions workflow (`.github/workflows/release-zip.yml`) that builds `openconsent.zip` and attaches it to every published release. The ZIP contains the folder `openconsent`, so uploading it in WordPress updates an existing installation
* **Added:** The workflow checks PHP syntax and fails if the release tag does not match the plugin version in `openconsent.php`
* **Improved:** Installation instructions link to the latest release and explain which ZIP to use

---

## Version 1.1.2 - 2026-11-20

### Compliance & Stability Review

**Google Consent Mode v2**
* **Fixed:** `url_passthrough`, `ads_data_redaction` and `conversion_linker` were sent inside `gtag('consent', …)` commands. They are not consent types and were ignored by Google. `ads_data_redaction` is now set with `gtag('set', 'ads_data_redaction', true)`; `url_passthrough` was dropped because it has no effect in Basic mode (no tag runs before consent)
* **Added:** `functionality_storage`, `personalization_storage` and `security_storage` to the default and update commands
* **Improved:** Returning visitors who accepted get the consent update and GTM from the inline `<head>` script instead of waiting for the footer script
* **Removed:** The plugin's own `gclid` / `wbraid` cookies. Google's Conversion Linker handles click IDs itself; the plugin never read these cookies
* **Removed:** The `<noscript>` GTM iframe that was inserted by JavaScript (it could never run)

**EU cookie rules (GDPR / ePrivacy)**
* **Added:** Declining or withdrawing consent removes Google Analytics/Ads cookies (`_ga*`, `_gid`, `_gat*`, `_gcl_*`, `_gac_*`) and the legacy `gclid` / `wbraid` cookies
* **Added:** Setting "Remember Choice For (Days)" (1–390). New installs default to 180 days; existing installs keep 365 days until changed
* **Added:** Shortcode `[openconsent_settings_link]`; any link to `#openconsent` or element with class `oc-open-settings` re-opens the banner, so consent can be withdrawn even when the widget is disabled
* **Improved:** Default banner text names the purposes, Google as recipient and the right to withdraw, links to the WordPress privacy policy page automatically and no longer contains the `YOUR_PRIVACY_POLICY_URL_HERE` placeholder

**WP Consent API / Google Site Kit**
* **Fixed:** Consent was set for the non-existent category `analytics`; the API (and Site Kit) expects `statistics`. Categories are now `statistics`, `statistics-anonymous`, `marketing`, `preferences`; `functional` is always allowed
* **Improved:** Consent is set through the API's JavaScript function when available, so Site Kit and other listeners are notified immediately; the AJAX endpoint remains as fallback and is only called when the API is active
* **Added:** The plugin registers itself as an opt-in consent manager (`wp_consent_api_registered_*`, consent type `optin`) and aligns the API's cookie lifetime with its own; the API cookies are re-synced on page load if they expired earlier
* **Fixed:** A stale nonce (cached pages) now returns a clean JSON error instead of a bare `-1`

**Admin**
* **Fixed:** Settings export ran after the admin page had started rendering, so the download contained HTML and headers could not be sent. Export, import and reset now run on `admin_init`
* **Fixed:** Importing a file with `enable_banner: 0` enabled the banner
* **Fixed:** An invalid or empty colour was stored as empty and produced broken inline styles; the previous colour is now kept
* **Fixed:** GTM IDs are normalised to upper case and shorter/longer container IDs are accepted
* **Fixed:** Duplicate "Settings saved" notices
* **Added:** `uninstall.php` removes the plugin's option when the plugin is deleted
* **Added:** Plugin headers `Requires at least` / `Requires PHP`

**Frontend**
* **Accessibility:** The widget is now a real `<button>` (reachable and operable by keyboard); the banner has `role="dialog"` and an accessible name; focus moves to the banner when it is re-opened
* **Fixed:** The slide-in animation overrode the `transform` used to centre the banner, so centred positions jumped while fading in
* **Fixed:** Middle-left / middle-right banners were stretched on mobile; long banners can now scroll on small screens
* **Improved:** Console logging only when `WP_DEBUG` is enabled

**Documentation**
* **Added:** Animated walkthrough (`assets/openconsent-demo.svg`) in the README

---

## Version 1.1.1 - 2026-05-13

### Google Consent Mode v2 Compliance Fixes

**Backend (PHP)**
* **Fixed:** Removed broken regional `consent default` call that specified only `regions` with no consent states (was a no-op)
* **Added:** `url_passthrough: true` and `ads_data_redaction: true` to the consent default script for measurement preservation when consent is denied

**Frontend JavaScript**
* **Added:** `ads_data_redaction` and `url_passthrough` toggled in `updateGoogleConsentMode()` — `true` when denied, `false` when granted — per Google Consent Mode v2 best practices

---

## Version 1.1.0 - 2026-04-16

### Complete Refactoring & Accessibility Overhaul

**Backend Improvements (PHP)**
* **Fixed:** Version consistency issue (was 1.0.6, now properly 1.1.0 throughout)
* **Added:** `OC_CONSENT_API_CATEGORIES` constant for better code maintainability
* **Improved:** AJAX handler with validation, error handling, and better response structure
* **Enhanced:** GTM consent default script with `wait_for_update` timing and EU regional GDPR targeting
* **Added:** Try-catch blocks for robust error handling in security-critical areas

**Admin UI Enhancements**
* **Feature:** Settings import/export as JSON for easy backup and migration between sites
* **Feature:** Reset to defaults button for quick settings restoration
* **Improved:** GDPR/CCPA compliance warnings on decline button color picker
* **Improved:** Google Site Kit detection with better visual feedback and messaging
* **Added:** Helper tips for Privacy Policy link requirements
* **Enhanced:** Field descriptions throughout the settings page

**Frontend JavaScript (v1.1.0)**
* **Security:** Better cookie handling with URL encoding and Secure flag for HTTPS
* **Feature:** Enhanced Google Consent Mode integration with conversion tracking support
* **Feature:** Automatic handling of GCLID and WBRAID parameters for Google Ads
* **Improved:** AJAX requests with 5-second timeout and better error logging
* **Improved:** Consent status validation and better logging for debugging
* **Fixed:** Event handling with proper preventDefault() and smart page reload logic

**Frontend CSS (v1.1.0)**
* **Feature:** CSS Custom Properties (CSS variables) for easier theme customization
* **Accessibility:** WCAG 2.1 Level AAA compliance with 44px touch targets
* **Accessibility:** Focus states with visible outlines for keyboard navigation
* **Feature:** Smooth animations (slide-in for banner, fade-in for widget)
* **Feature:** Support for prefers-reduced-motion media query for accessibility
* **Improved:** Responsive design with full-width buttons on mobile
* **Added:** Print styles to hide banner when printing
* **Cleanup:** Removed duplicate frontend-css.css file

**Security & Compliance**
* **Security:** SameSite and Secure cookie flags for enhanced security
* **Security:** Improved nonce verification and AJAX error handling
* **Compliance:** Better GDPR/CCPA compliance messaging in admin interface
* **Validation:** Input validation and sanitization throughout all settings

**Code Quality**
* **Documentation:** Added comprehensive CLAUDE.md with development guidelines
* **Documentation:** Detailed JSDoc comments for all JavaScript functions
* **Cleanup:** Better code formatting and organization

---

## Version 1.0.8 - 2025-06-19

* **Fixed:** Corrected the positioning of the minimized "Cookie Settings" widget on mobile devices. The widget now has appropriate margins and a max-width to ensure it no longer overflows or gets cut off on smaller viewports.

## Version 1.0.7 - 2025-06-08

* **Fixed:** Corrected a critical issue in the Google Site Kit integration where tags would not fire after consent was accepted. The plugin's JavaScript now correctly sends a client-side `gtag('consent', 'update', ...)` command on subsequent page loads, ensuring that tags managed by Site Kit are activated as expected.
* **Improved:** Refined the JavaScript logic for both "Accept" and "Decline" paths to ensure page reloads only happen when the consent status genuinely changes, preventing unnecessary reloads.
* **Documentation:** Provided guidance on how to correctly configure third-party tags (like Meta Pixel) within Google Tag Manager to make them "consent-aware" and respect the signals sent by the plugin.

## Version 1.0.6 - 2025-06-08

* **Major Improvement:** Implemented a hybrid "Standalone / Integration" mode.
    * The plugin now automatically detects if Google Site Kit is active.
    * **If Site Kit is active:** OpenConsent acts purely as a UI provider, passing consent decisions to the WP Consent API and letting Site Kit manage all Google scripts.
    * **If Site Kit is NOT active:** OpenConsent functions as a complete solution, loading and managing GTM and Google Consent Mode itself using the GTM ID from its own settings.
* **Fixed:** Resolved a core conflict with the WP Consent API and Site Kit. OpenConsent no longer injects its own default consent scripts when Site Kit is active.
* **Improved:** Re-introduced the GTM ID field in the admin settings with conditional help text explaining its purpose based on whether Site Kit is active.
* **Note:** Users on WordPress 6.5+ are strongly advised to deactivate the standalone "WP Consent API" plugin to avoid conflicts with the WordPress Core implementation.

## Version 1.0.5 - 2025-06-08

* **Feature:** Added compatibility with the native WordPress Consent API (introduced in WP 6.5).
* **Feature:** Added a backend switch to enable or disable the minimized "Cookie Settings" widget.
* **Fixed:** Resolved a timing issue by reloading the page on "Decline" to ensure server-side integrations like Site Kit correctly read the new consent state.


## Version 1.0.4 - 2025-06-06

* **Feature:** Added individual color settings for "Accept" and "Decline" buttons.
* **Feature:** Added color configuration for the minimized "Cookie Settings" widget.
* **Improved:** Added a compliance note in the admin panel regarding the "Decline" button's styling.
* **Fixed:** The minimized widget is now perfectly aligned with the floating banner positions.


## Version 1.0.3 - 2025-06-05

* **Fixed:** Corrected a fatal PHP error (`sprintf() ArgumentCountError`) that could cause a "white screen of death" in version 1.0.2.
* **Improved:** The plugin's text domain loading hook was moved from `plugins_loaded` to `init` to align more closely with WordPress 6.7+ best practice recommendations.
* **Improved:** Hardened the plugin's instantiation logic to ensure it loads robustly within the `plugins_loaded` action.

## Version 1.0.2 - 2025-06-05

* **Feature:** Added "Minimized State" functionality:
    * After user interaction (Accept/Decline), a small, clickable widget appears (default: bottom-right).
    * Clicking the widget re-opens the main cookie banner, allowing users to change their consent settings.
    * Text for the minimized widget is now configurable in the backend settings (Defaults to "Cookie Settings").
* **Improved:** Main banner padding increased to `20px` for better visual spacing on desktop views.
* **Fixed:** Decline button alignment on mobile views, ensuring it stacks correctly and is not shifted.
* **Refined:** Page reload logic on "Accept" action. Reload now occurs only if GTM has not yet been loaded or if the consent state genuinely changes to 'granted', preventing unnecessary reloads.

## Version 1.0.1 - 2025-06-05

* **Feature:** Added "Enable Cookie Banner" option in admin settings to globally activate or deactivate the plugin's frontend functionality.
* **Improved:** Google Tag Manager ID validation regex updated to correctly support GTM IDs with 6, 7, or 8 characters in the suffix.
* **Removed:** Separate backend fields for "Privacy Policy Link Text" and "Privacy Policy URL". Users are now guided to add privacy links directly within the main banner message using the rich text editor.

## Version 1.0.0 - 2025-06-05

* **Initial Release of OpenConsent.**
* **Core Features:**
    * Cookie consent banner with "Accept" and "Decline" options.
    * Integration with Google Tag Manager (GTM) via GTM ID input.
    * Implementation of Google Consent Mode v2 (Basic Mode).
    * Backend settings page for full customization of texts, colors, and positioning.
    * Responsive design.

----------
    www.vidual.org
    vibecoded with gemini 2.5 pro
    tested manually
