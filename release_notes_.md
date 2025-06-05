# OpenConsent Plugin - Release Notes


## Version 1.0.3 (Current) - 2025-06-05


* **Fixed:** critical bugfix in openconsent.php



## Version 1.0.2 - 2025-06-05

* **Added:** "Minimized State" feature:
    * After user interaction (Accept/Decline), a small, clickable widget appears (default: bottom-right).
    * Clicking the widget re-opens the main cookie banner, allowing users to change their consent settings.
    * Text for the minimized widget is now configurable in the backend settings (Defaults to "Cookie Settings").
* **Improved:** Main banner padding increased to `20px` for better visual spacing on desktop views.
* **Fixed:** Decline button alignment on mobile views, ensuring it stacks correctly and is not shifted.
* **Refined:** Page reload logic on "Accept" action. Reload now occurs only if GTM has not yet been loaded or if the consent state genuinely changes to 'granted', preventing unnecessary reloads.
* **Updated:** Plugin version in file headers.

## Version 1.0.1 - 2025-06-04

* **Added:** "Enable Cookie Banner" option in admin settings to globally activate or deactivate the plugin's frontend functionality.
* **Removed:** Separate backend fields for "Privacy Policy Link Text" and "Privacy Policy URL". Users are now guided to add privacy links directly within the main banner message using the rich text editor.
* **Improved:** Google Tag Manager ID validation:
    * Regex updated to `'/^GTM-[A-Z0-9]{6,8}$/i'` to correctly support GTM IDs with 6, 7, or 8 alphanumeric characters in the suffix (e.g., `GTM-TWZDPZ2N`).
    * Error message and description for GTM ID field in admin settings clarified.
* **Improved:** Default banner text in admin settings now includes a placeholder and guidance for adding a privacy policy link.
* **Updated:** Plugin version in file headers.

## Version 1.0.0 - 2025-06-04

* **Initial Release of OpenConsent.**
* **Core Features:**
    * Display a cookie consent banner with "Accept" and "Decline" options.
    * Integration with Google Tag Manager (GTM) via GTM ID input.
    * Implementation of Google Consent Mode v2 (Basic Mode):
        * Sets default consent states to `denied`.
        * Updates consent states based on user choice.
        * Loads GTM script only after consent is granted.
    * Backend settings page (WordPress Admin: Settings > OpenConsent) for customization:
        * GTM ID.
        * Banner main message (supports HTML via WP Editor).
        * Accept & Decline button texts.
        * Banner design: background color, text color, link color, button background color, button text color.
        * Banner positioning on the page (11 options, e.g., bottom full, top right, middle center modal).
    * Frontend interface and backend settings in English.
    * Responsive banner design for various screen sizes.
    * Plugin Name: OpenConsent
    * Author: vidual 
    * Note: The plugin has been entirely vibecoded with the help of Gemini 2.5 pro
