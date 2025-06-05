<?php
/**
 * Plugin Name: OpenConsent
 * Plugin URI: https://github.com/vidual-labs/openconsent/
 * Description: A cookie banner plugin with Google Tag Manager integration and Google Consent Mode v2 support.
 * Version: 1.0.3
 * Author: vidual
 * Author URI: https://vidual.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openconsent
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OC_VERSION', '1.0.3' );
define( 'OC_SETTINGS_SLUG', 'openconsent_settings' );
define( 'OC_OPTION_NAME', 'openconsent_settings' );

require_once OC_PLUGIN_DIR . 'admin/admin-einstellungen.php';

class OpenConsent_Plugin {

    private $options;
    private $is_banner_enabled;

    public function init_plugin() {
        $this->options = get_option( OC_OPTION_NAME );
        $this->is_banner_enabled = isset( $this->options['enable_banner'] ) && $this->options['enable_banner'] == 1;

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( 'OpenConsent_Admin_Einstellungen', 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        if ( $this->is_banner_enabled ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
            add_action( 'wp_head', array( $this, 'insert_gtm_consent_default' ), 1 );
            add_action( 'wp_footer', array( $this, 'display_elements' ) );
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'openconsent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function add_admin_menu() {
        add_options_page(
            __( 'OpenConsent Settings', 'openconsent' ), __( 'OpenConsent', 'openconsent' ),
            'manage_options', OC_SETTINGS_SLUG,
            array( 'OpenConsent_Admin_Einstellungen', 'settings_page_html' )
        );
    }

    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( 'settings_page_' . OC_SETTINGS_SLUG !== $hook_suffix ) return;
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 'oc-frontend-css', OC_PLUGIN_URL . 'css/frontend-consent.css', array(), OC_VERSION );
        wp_enqueue_script( 'oc-frontend-js', OC_PLUGIN_URL . 'js/frontend-consent.js', array('jquery'), OC_VERSION, true );

        $gtm_id = isset( $this->options['gtm_id'] ) ? trim( $this->options['gtm_id'] ) : '';
        $default_banner_text = sprintf(
            '<p>%s</p><p>%s <a href="YOUR_PRIVACY_POLICY_URL_HERE" target="_blank">%s</a>.</p>',
            __( 'This website uses cookies to enhance your experience. By clicking "Accept", you agree to our use of cookies.', 'openconsent' ),
            __( 'For more information, please see our', 'openconsent' ),
            __( 'Privacy Policy', 'openconsent' )
        );
        $minimized_text_default = __( 'Cookie Settings', 'openconsent' );

        wp_localize_script( 'oc-frontend-js', 'oc_js_vars', array(
            'gtm_id'              => $gtm_id,
            'banner_text'         => isset( $this->options['banner_text'] ) && !empty(trim($this->options['banner_text'])) ? wp_kses_post( $this->options['banner_text'] ) : $default_banner_text,
            'accept_text'         => isset( $this->options['accept_text'] ) ? esc_html( $this->options['accept_text'] ) : __( 'Accept', 'openconsent' ),
            'decline_text'        => isset( $this->options['decline_text'] ) ? esc_html( $this->options['decline_text'] ) : __( 'Decline', 'openconsent' ),
            'minimized_text'      => isset( $this->options['minimized_text'] ) ? esc_html( $this->options['minimized_text'] ) : $minimized_text_default,
            'cookie_name'         => 'openconsent_status',
            'cookie_duration_days'=> 365,
            'banner_position'     => $this->options['banner_position'] ?? 'bottom-full',
        ) );
    }

    public function insert_gtm_consent_default() {
        echo "\n";
        echo "<script>\n";
        echo "  window.dataLayer = window.dataLayer || [];\n";
        echo "  function gtag(){dataLayer.push(arguments);}\n";
        echo "  gtag('consent', 'default', {\n";
        echo "    'ad_storage': 'denied', 'ad_user_data': 'denied', 'ad_personalization': 'denied', 'analytics_storage': 'denied',\n";
        echo "    'wait_for_update': 500\n  });\n";
        echo "</script>\n\n";
    }

    public function display_elements() {
        $options = $this->options;
        $banner_bg_color   = $options['banner_bg_color'] ?? '#222222';
        $banner_text_color = $options['banner_text_color'] ?? '#ffffff';
        $link_color        = $options['link_color'] ?? '#00a0d2';
        $button_bg_color   = $options['button_bg_color'] ?? '#0073aa';
        $button_text_color = $options['button_text_color'] ?? '#ffffff';
        $banner_position   = $options['banner_position'] ?? 'bottom-full';

        // ** THIS IS THE CORRECTED LINE **
        $default_banner_text_val = sprintf(
            '<p>%s</p><p>%s <a href="YOUR_PRIVACY_POLICY_URL_HERE" target="_blank">%s</a>.</p>',
            __( 'This website uses cookies to enhance your experience. By clicking "Accept", you agree to our use of cookies.', 'openconsent' ),
            __( 'For more information, please see our', 'openconsent' ),
            __( 'Privacy Policy', 'openconsent' )
        );
        // Use trim() to check if banner_text from options is effectively empty after potential kses_post
        $banner_text_from_options = isset($options['banner_text']) ? trim($options['banner_text']) : '';
        $banner_text = !empty($banner_text_from_options) ? wp_kses_post( $options['banner_text'] ) : $default_banner_text_val;

        $accept_text = $options['accept_text'] ?? __( 'Accept', 'openconsent' );
        $decline_text = $options['decline_text'] ?? __( 'Decline', 'openconsent' );

        ?>
        <div id="oc-cookie-banner" class="oc-banner-position-<?php echo esc_attr( $banner_position ); ?>"
             style="display: none; background-color: <?php echo esc_attr( $banner_bg_color ); ?>; color: <?php echo esc_attr( $banner_text_color ); ?>;">
            <div class="oc-banner-content">
                <?php echo $banner_text; // This will now correctly use $default_banner_text_val if options['banner_text'] is empty ?>
            </div>
            <div class="oc-banner-actions">
                <button id="oc-accept-btn" style="background-color: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>;">
                    <?php echo esc_html( $accept_text ); ?>
                </button>
                <button id="oc-decline-btn" style="background-color: <?php echo esc_attr( $button_bg_color ); ?>; color: <?php echo esc_attr( $button_text_color ); ?>; margin-left: 10px;">
                    <?php echo esc_html( $decline_text ); ?>
                </button>
            </div>
        </div>

        <div id="oc-cookie-widget" style="display: none;">
            <?php /* Text is set by JS, this is just the container */ ?>
        </div>

        <style>
            #oc-cookie-banner .oc-banner-content a,
            #oc-cookie-banner .oc-banner-content a:visited { color: <?php echo esc_attr( $link_color ); ?> !important; }
            #oc-cookie-banner .oc-banner-content a:hover,
            #oc-cookie-banner .oc-banner-content a:focus { text-decoration: underline; }
        </style>
        <?php
    }
}

function openconsent_instantiate_plugin() {
    // Ensure the class hasn't been loaded by another process, though unlikely for a plugin.
    if (class_exists('OpenConsent_Plugin')) {
        $GLOBALS['openconsent_plugin'] = new OpenConsent_Plugin();
        $GLOBALS['openconsent_plugin']->init_plugin();
    }
}
add_action( 'plugins_loaded', 'openconsent_instantiate_plugin', 5 );
?>
