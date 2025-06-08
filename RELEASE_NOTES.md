# OpenConsent Plugin - Release Notes

## Version 1.0.7 (Current) - 2025-06-08

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