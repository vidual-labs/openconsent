# OpenConsent Plugin for WordPress

OpenConsent is a WordPress plugin designed to help website owners comply with cookie consent regulations (Google consent mode v2) by displaying a customizable cookie banner. It integrates with Google Tag Manager (GTM) and implements Google Consent Mode v2 (Basic) to manage how Google tags behave based on user consent. The plugin has been vibecoded to replace costly or bloated Wordpress consent plugins for that purpose.

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
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
* **Minimized Re-Open Widget:** After initial interaction, a small, clickable widget appears, allowing users to revisit and change their consent settings.
* **Enable/Disable:** Option to easily activate or deactivate the entire banner functionality.
* **Responsive Design:** The banner and widget are designed to adapt to different screen sizes.

## Requirements

* WordPress 5.0 or higher (recommended)
* PHP 7.0 or higher (recommended)

## Installation

### Manual Installation

1.  Download the latest release (`openconsent.zip`) from the [GitHub Releases page](https://github.com/vidual-labs/openconsent) 
2.  In your WordPress admin panel, go to **Plugins > Add New**.
3.  Click **Upload Plugin** at the top.
4.  Upload the `openconsent.zip` file.
5.  Activate the plugin through the 'Plugins' menu in WordPress.

Alternatively, you can manually upload the unzipped `openconsent` folder to the `/wp-content/plugins/` directory on your server and then activate it from the Plugins page.

## Configuration

Once activated, you can configure the OpenConsent plugin by navigating to:

**Settings > OpenConsent** in your WordPress admin dashboard.

The settings page is divided into the following sections:

* **Plugin Status:**
    * **Enable Cookie Banner:** Globally activates or deactivates the banner and GTM integration.
* **General Settings:**
    * **Google Tag Manager ID:** Enter your GTM ID (e.g., `GTM-XXXXXXX`). Leave empty if not using GTM.
* **Banner Content & Texts:**
    * **Main Banner Message:** Use the rich text editor to craft your banner's main message, including any necessary information and links (e.g., to your Privacy Policy).
    * **Accept/Decline Button Text:** Customize the text for the consent buttons.
    * **Minimized Banner Text:** Set the text for the small widget that allows users to re-open the banner (default: "Cookie Settings").
* **Banner Design:**
    * **Banner Position:** Select the banner's position on the screen.
    * **Colors:** Customize background, text, link, and button colors.

Remember to click **Save Settings** after making changes.

## How It Works (Google Consent Mode v2 - Basic)

1.  **Default State:** When a new user visits your site, consent for all relevant storage types (`analytics_storage`, `ad_storage`, etc.) is defaulted to `denied` *before* any Google tags or GTM itself is loaded.
2.  **User Interaction:**
    * **Accept:** If the user clicks "Accept", the consent state is updated to `granted`. The page then reloads, and the GTM script (if an ID is provided) is injected, allowing your configured Google tags to fire with granted consent.
    * **Decline:** If the user clicks "Decline", the consent state remains `denied` (or is updated to `denied`). GTM is not loaded.
3.  **Persistence:** The user's choice is stored in a cookie (`openconsent_status`) for 365 days (by default).
4.  **GTM Loading:** The Google Tag Manager script is only loaded into the page *after* the user has explicitly granted consent. This is characteristic of a "Basic" Consent Mode implementation.

## Frontend Behavior

* **Initial Visit:** The full cookie consent banner is displayed according to your position and style settings.
* **After Interaction (Accept/Decline):** The main banner is hidden, and a small, clickable "widget" (e.g., "Cookie Settings") appears in the bottom-right corner (this widget's style is default, text is configurable).
* **Revisiting Consent:** Clicking this widget will re-display the main cookie consent banner, allowing users to change their previous settings.

## Changelog

See [RELEASE_NOTES.md](RELEASE_NOTES.md).

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

This plugin has been vibecoded with Gemini 2.5 pro
