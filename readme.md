<img width="1280" height="800" alt="grafik" src="https://github.com/user-attachments/assets/6f7c0863-ff79-494c-95fd-a435974dec5b" />

# OpenConsent for WordPress, Joomla, TYPO3 and any website

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version: 1.3.0](https://img.shields.io/badge/Version-1.3.0-brightgreen.svg)](https://github.com/vidual-labs/openconsent/releases)
[![WordPress: 5.0+](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![Joomla: 5 | 6](https://img.shields.io/badge/Joomla-5%20%7C%206-blue.svg)](https://www.joomla.org)
[![TYPO3: 12 | 13 | 14](https://img.shields.io/badge/TYPO3-12%20%7C%2013%20%7C%2014-orange.svg)](https://typo3.org)


OpenConsent helps website owners comply with cookie consent regulations (Google consent mode v2) by displaying a customizable cookie banner. It integrates with Google Tag Manager (GTM) and implements Google Consent Mode v2 (Basic) to manage how Google tags behave based on user consent. It has been vibecoded to replace costly or bloated consent plugins for that purpose.

It comes in four packages that share one banner script. Release file names carry the version (`X.Y.Z`, e.g. `1.3.0`):

| Package | Release file | For |
|---|---|---|
| WordPress plugin | `openconsent-wordpress-X.Y.Z.zip` | WordPress 5.0+ (with Google Site Kit / WP Consent API support) |
| Joomla plugin | `openconsent-joomla-X.Y.Z.zip` | Joomla 5 and 6 |
| TYPO3 extension | `openconsent_X.Y.Z.zip` | TYPO3 12, 13 and 14 |
| Universal snippet | `openconsent-X.Y.Z.js` | Any other website: static sites, Webflow, Squarespace, Ghost, … |


## See it in action

![Animated walkthrough: first visit with all consent signals denied, Accept loads Google Tag Manager, the Cookie Settings widget re-opens the banner, Decline withdraws consent and removes Google cookies](assets/openconsent-demo.svg)

## Features

* **Cookie Consent Banner:** Displays a customizable banner to obtain user consent for cookies.
* **Google Consent Mode v2 (Basic):**
    * Sets default consent states to 'denied' before any tags fire.
    * Updates consent states based on user interaction (Accept/Decline).
    * Dynamically loads Google Tag Manager only after consent is granted.
* **Customization:**
    * **Texts:** Configure the main banner message (supports HTML via WP editor), accept button text, decline button text, and the text for the minimized "re-open" widget.
    * **Appearance:** Adjust banner background color, text color, link color, and button background/text colors.
    * **Positioning:** Choose from 11 positions for the main banner (e.g., bottom full, top right, middle center).
* **Google Tag Manager Integration:** Easily add your GTM ID to inject the GTM script correctly based on consent.
* **Minimized Re-Open Widget:** After initial interaction, a small, clickable widget appears, allowing users to revisit and change their consent settings. (you can deavtivat that)
* **Enable/Disable:** Option to easily activate or deactivate the entire banner functionality.
* **Responsive Design:** The banner and widget are designed to adapt to different screen sizes.
* **NEW with v1.0.7:** Plugin is able to detect Google Site Kit and then change into compatiblity mode. It fully supports WP Consent API now.
* **NEW with v1.1.0:** Complete accessibility overhaul with WCAG 2.1 Level AAA compliance, Google Consent Mode v2 enhancements with conversion tracking support, settings import/export functionality, and improved security throughout.
* **NEW with v1.1.1:** Google Consent Mode v2 compliance fixes; removed broken no-op regional consent default.
* **NEW with v1.1.2:** Compliance and stability release.
    * Consent Mode: only real consent types are sent in `consent` commands (`ads_data_redaction` now uses `gtag('set', …)`); returning visitors who accepted get the consent update and GTM directly in `<head>`.
    * WP Consent API / Site Kit: correct categories (`statistics`, `marketing`, …), client-side updates, registered as an opt-in consent manager.
    * Withdrawal: declining removes Google Analytics/Ads cookies; new shortcode `[openconsent_settings_link]` to re-open the banner from any page.
    * New setting **Remember Choice For (Days)** (default 180 for new installs).
    * Better default banner text (purposes, Google as recipient, withdrawal, automatic privacy policy link).
    * Fixed settings export/import, keyboard access to the widget and the position of centred banners.
* **NEW with v1.2.0:** OpenConsent for Joomla 5/6 and a universal snippet for any website, built from one shared banner script without jQuery. The WordPress release file is now called `openconsent-wordpress.zip`.
* **NEW with v1.3.0:** OpenConsent for TYPO3 12, 13 and 14. Release files now carry the version in their name (e.g. `openconsent-wordpress-1.3.0.zip`).

## Requirements

* **WordPress:** WordPress 5.0 or higher, PHP 7.0 or higher (recommended)
* **Joomla:** Joomla 5 or 6
* **TYPO3:** TYPO3 12.4, 13.4 or 14, PHP 8.1 or higher
* **Snippet:** any website where you can add scripts to the `<head>`

## Installation (WordPress)

### Manual Installation

1.  Download `openconsent-wordpress-X.Y.Z.zip` from the [latest release](https://github.com/vidual-labs/openconsent/releases/latest). Use this file, not GitHub's "Source code (zip)": only the release file contains the plugin with its scripts in the correct `openconsent` folder, so WordPress updates an existing installation instead of adding a second copy. (Up to v1.1.3 the file was called `openconsent.zip`, in v1.2.0 `openconsent-wordpress.zip`.)
2.  In your WordPress admin panel, go to **Plugins > Add New**.
3.  Click **Upload Plugin** at the top.
4.  Upload the `openconsent-wordpress-X.Y.Z.zip` file.
5.  Activate the plugin through the 'Plugins' menu in WordPress.

Alternatively, you can manually upload the unzipped `openconsent` folder to the `/wp-content/plugins/` directory on your server and then activate it from the Plugins page.

## Configuration (WordPress)

Once activated, you can configure the OpenConsent plugin by navigating to:

**Settings > OpenConsent** in your WordPress admin dashboard.

The settings page is divided into the following sections:

* **Plugin Status:**
    * **Enable Cookie Banner:** Globally activates or deactivates the banner and GTM integration.
* **General Settings:**
    * **Google Tag Manager ID:** Enter your GTM ID (e.g., `GTM-XXXXXXX`). Leave empty if not using GTM.
    * **Remember Choice For (Days):** How long the visitor's choice is stored before the banner asks again (1–390 days).
* **Banner Content & Texts:**
    * **Main Banner Message:** Use the rich text editor to craft your banner's main message, including any necessary information and links (e.g., to your Privacy Policy).
    * **Accept/Decline Button Text:** Customize the text for the consent buttons.
    * **Minimized Banner Text:** Set the text for the small widget that allows users to re-open the banner (default: "Cookie Settings").
* **Banner Design:**
    * **Banner Position:** Select the banner's position on the screen.
    * **Colors:** Customize background, text, link, and button colors.

Remember to click **Save Settings** after making changes.

## Joomla

1.  Download `openconsent-joomla-X.Y.Z.zip` from the [latest release](https://github.com/vidual-labs/openconsent/releases/latest).
2.  In the Joomla administrator, go to **System > Install > Extensions** and upload the file.
3.  Go to **System > Manage > Plugins**, open **System - OpenConsent**, enter your GTM ID and adjust texts, position and colors.
4.  Enable the plugin and save.

The banner texts default to the site language (English and German included). Leave **Banner Message** empty for the default text, or write your own and link your privacy policy. To let visitors re-open the banner without the widget, add a link to `#openconsent`, e.g. a menu item of the type **URL** in your footer menu.

## TYPO3

**Composer installation** (recommended): the extension is not on Packagist yet. Download and unpack `openconsent_X.Y.Z.zip` into a local folder of your project (e.g. `packages/openconsent`), add it as a path repository and require it:

```bash
composer config repositories.openconsent path 'packages/openconsent'
```

```bash
composer require vidual/openconsent:@dev
```

**Classic installation** (without Composer): in the TYPO3 backend, go to **Admin Tools > Extensions**, upload `openconsent_X.Y.Z.zip` and activate the extension. Keep the file name: the Extension Manager reads the extension key from it.

Then go to **Admin Tools > Settings > Extension Configuration > openconsent**, enter your GTM ID and adjust texts, position and colors. The settings apply to all sites of the installation.

The banner texts default to the language of the page (English and German included). Leave **Banner message** empty for the default text, or write your own and link your privacy policy. To let visitors re-open the banner without the widget, add a link to `#openconsent`, e.g. in your footer. No TypoScript is needed: the extension adds the banner to every frontend HTML page.

## Universal Snippet (any website)

1.  Download `openconsent-X.Y.Z.js` from the [latest release](https://github.com/vidual-labs/openconsent/releases/latest), rename it to `openconsent.js` and upload it to your website. Host it yourself: the banner should not depend on a third-party server.
2.  Add the following to the `<head>` of every page, as early as possible and before any Google tag. Do not add `async` or `defer`:

```html
<script>
  window.openconsentConfig = {
    gtm_id: 'GTM-XXXXXXX',
    banner_text: '<p>We use cookies for analytics and marketing, including services provided by Google. They are only activated if you click “Accept”. You can change or withdraw your choice at any time. <a href="/privacy">Privacy Policy</a></p>'
  };
</script>
<script src="/openconsent.js"></script>
```

3.  Remove any other GTM snippet from your pages: OpenConsent loads GTM itself after the visitor accepts.

All other options are optional:

| Option | Default |
|---|---|
| `gtm_id` | none (without it, only the banner and the events below are active) |
| `banner_text` | generic English text (HTML) |
| `accept_text`, `decline_text`, `minimized_text` | `Accept`, `Decline`, `Cookie Settings` |
| `banner_label` | `Cookie consent` (accessible name of the banner) |
| `enable_minimized_widget` | `true` |
| `cookie_duration_days` | `180` (1–390) |
| `banner_position` | `bottom-full`; also `bottom-left`, `bottom-center`, `bottom-right`, `top-full`, `top-left`, `top-center`, `top-right`, `middle-left`, `middle-center`, `middle-right` |
| `banner_bg_color`, `banner_text_color`, `link_color` | `#222222`, `#ffffff`, `#00a0d2` |
| `accept_button_bg_color`, `accept_button_text_color` | `#0073aa`, `#ffffff` |
| `decline_button_bg_color`, `decline_button_text_color` | `#757575`, `#ffffff` |
| `widget_bg_color`, `widget_text_color` | `#333333`, `#ffffff` |
| `debug` | `false` (logs to the browser console) |

Any link to `#openconsent` or element with the class `oc-open-settings` re-opens the banner. See [`snippet/example.html`](snippet/example.html) for a complete page.

**Events for integrations** (all packages): `openconsent:ready` (detail: `status` on page load: `granted`, `denied` or `null`) and `openconsent:choice` (detail: `status`, `previous`, `waitUntil(promise)`) are dispatched on `document`.

## How It Works (Google Consent Mode v2 - Basic)

1.  **Default State:** When a new user visits your site, consent for all relevant storage types (`analytics_storage`, `ad_storage`, etc.) is defaulted to `denied` *before* any Google tags or GTM itself is loaded.
2.  **User Interaction:**
    * **Accept:** If the user clicks "Accept", the consent state is updated to `granted`. The page then reloads, and the GTM script (if an ID is provided) is injected, allowing your configured Google tags to fire with granted consent.
    * **Decline:** If the user clicks "Decline", the consent state remains `denied` (or is updated to `denied`). GTM is not loaded.
3.  **Persistence:** The user's choice is stored in a cookie (`openconsent_status`) for 180 days by default (configurable; sites updated from an earlier version keep 365 days until changed).
4.  **Withdrawal:** Declining after a previous acceptance removes the Google Analytics/Ads cookies (`_ga*`, `_gid`, `_gcl_*`, …) and GTM is no longer loaded.
5.  **GTM Loading:** The Google Tag Manager script is only loaded into the page *after* the user has explicitly granted consent. This is characteristic of a "Basic" Consent Mode implementation.

## Frontend Behavior

* **Initial Visit:** The full cookie consent banner is displayed according to your position and style settings.
* **After Interaction (Accept/Decline):** The main banner is hidden, and a small, clickable "widget" (e.g., "Cookie Settings") appears in the bottom-right corner (this widget's style is default, text is configurable).
* **Revisiting Consent:** Clicking this widget will re-display the main cookie consent banner, allowing users to change their previous settings.
* **Without the widget:** If you disable the widget, add the shortcode `[openconsent_settings_link]` (or any link to `#openconsent` / element with the class `oc-open-settings`) to your footer or privacy policy, so visitors can still withdraw consent.

## Using it with Google Site Kit

When Site Kit is active, OpenConsent only provides the banner and passes the choice on through the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/). For this to work, install and activate the WP Consent API plugin and enable **Consent Mode** in the Site Kit settings.

## Your responsibilities

OpenConsent provides the technical consent signal. It is not legal advice. You still need a privacy policy that lists the services you use, and a banner text that names the purposes and recipients. All Google tags must be loaded through the GTM container (or Site Kit) — scripts embedded elsewhere in your theme are not blocked by this plugin.

## Development & Versioning

The repository is a monorepo:

| Folder | Content |
|---|---|
| `core/` | Shared frontend: banner script (`openconsent.js`), head script (`openconsent-head.js`), styles (`openconsent.css`) |
| `wordpress/` | WordPress plugin (PHP, settings page, WP Consent API bridge) |
| `joomla/` | Joomla system plugin (manifest with settings form, PHP, language files) |
| `typo3/` | TYPO3 extension (frontend middleware, extension configuration, language files) |
| `snippet/` | Example page for the universal snippet |

`node build.mjs` (Node 18+, no dependencies) copies the core into the CMS packages and builds `dist/openconsent-wordpress-X.Y.Z.zip`, `dist/openconsent-joomla-X.Y.Z.zip`, `dist/openconsent_X.Y.Z.zip` (TYPO3) and `dist/openconsent-X.Y.Z.js`. The packages in `wordpress/`, `joomla/` and `typo3/` do not contain the core files themselves, so install them from `dist/`.

**Important:** Every commit to this repository includes a version bump and corresponding updates to the README and RELEASE_NOTES. All packages share one version. See [CLAUDE.md](CLAUDE.md) for detailed development documentation and version management policies.

**Releases:** Publishing a GitHub release with a tag that matches the version (e.g. `v1.3.0`) builds all four packages and attaches them to the release automatically, with the version from the tag in each file name. The build fails if the tag and the versions in `wordpress/openconsent.php`, `joomla/openconsent.xml` and `typo3/ext_emconf.php` differ.

## Changelog

See [RELEASE_NOTES.md](RELEASE_NOTES.md) for complete version history.

## Support

If you encounter any issues or have feature requests, please use the [GitHub Issues tracker](https://github.com/vidual-labs/openconsent/issues) 

## Contributing

Contributions are welcome! Please feel free to fork the repository, make changes, and submit a pull request.

## License

This plugin is licensed under the GPL v2 or later.
See the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) file for more details.

## Author

Developed by [vidual](https://vidual.org).
Plugin page: [OpenConsent](https://github.com/vidual-labs/openconsent)

---

**Built with:**
- Initial development: Gemini 2.5 Pro
- v1.1.0 Refactoring & Enhancement: [Claude Code](https://claude.ai/code) (Anthropic)
- v1.1.1 Compliance Fixes: [Claude Code](https://claude.ai/code) (Anthropic): ✅Test succesful with WP 7.0, PHP 8.4 + ✅Compliance approved withGTM Tag Manager
- v1.1.2 Compliance & Stability Review: [Claude Code](https://claude.ai/code) (Anthropic)
- v1.1.3 Automated release ZIP: [Claude Code](https://claude.ai/code) (Anthropic)
- v1.2.0 Joomla plugin, universal snippet, shared core: [Claude Code](https://claude.ai/code) (Anthropic)
- v1.3.0 TYPO3 extension, versioned release files: [Claude Code](https://claude.ai/code) (Anthropic)
