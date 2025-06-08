<?php
/**
 * Plugin Name: OpenConsent
 * Plugin URI: https://vidual.org/openconsent
 * Description: A cookie banner that provides consent signals to the native WP Consent API or manages GTM standalone.
 * Version: 1.0.7
 * Author: vidual
 * Author URI: https://vidual.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openconsent
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OC_VERSION', '1.0.6' );
define( 'OC_SETTINGS_SLUG', 'openconsent_settings' );
define( 'OC_OPTION_NAME', 'openconsent_settings' );

require_once OC_PLUGIN_DIR . 'admin/admin-einstellungen.php';

class OpenConsent_Plugin {
    private $options;
    private $is_banner_enabled;
    private $is_site_kit_active;
    private $should_load_own_gtm;

    public function init_plugin() {
        // This function is needed for the is_plugin_active check on frontend.
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $this->options = get_option( OC_OPTION_NAME );
        $this->is_banner_enabled = !empty( $this->options['enable_banner'] );
        $this->is_site_kit_active = is_plugin_active('google-site-kit/google-site-kit.php');
        $this->should_load_own_gtm = !$this->is_site_kit_active && !empty($this->options['gtm_id']);

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( 'OpenConsent_Admin_Einstellungen', 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        add_action( 'wp_ajax_nopriv_openconsent_update_consent', array( $this, 'ajax_update_wp_consent' ) );
        add_action( 'wp_ajax_openconsent_update_consent', array( $this, 'ajax_update_wp_consent' ) );

        if ( $this->is_banner_enabled ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
            add_action( 'wp_footer', array( $this, 'display_elements' ) );
            // Conditionally add GCM default script only if we are in standalone mode
            if ( $this->should_load_own_gtm ) {
                add_action( 'wp_head', array( $this, 'insert_gtm_consent_default' ), 1 );
            }
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'openconsent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function add_admin_menu() {
        add_options_page(__( 'OpenConsent Settings', 'openconsent' ), __( 'OpenConsent', 'openconsent' ), 'manage_options', OC_SETTINGS_SLUG, array( 'OpenConsent_Admin_Einstellungen', 'settings_page_html' ));
    }

    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( 'settings_page_' . OC_SETTINGS_SLUG !== $hook_suffix ) return;
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 'oc-frontend-css', OC_PLUGIN_URL . 'css/frontend-consent.css', array(), OC_VERSION );
        wp_enqueue_script( 'oc-frontend-js', OC_PLUGIN_URL . 'js/frontend-consent.js', array('jquery'), OC_VERSION, true );
        
        $options = $this->options;
        $default_banner_text = sprintf('<p>%s <a href="YOUR_PRIVACY_POLICY_URL_HERE" target="_blank">%s</a>.</p>', __('This website uses cookies to enhance your experience.', 'openconsent'), __('Read our Privacy Policy', 'openconsent'));
        $minimized_text_default = __( 'Cookie Settings', 'openconsent' );

        wp_localize_script( 'oc-frontend-js', 'oc_js_vars', array(
            'gtm_id'                  => $options['gtm_id'] ?? '',
            'should_load_own_gtm'     => $this->should_load_own_gtm,
            'banner_text'             => isset($options['banner_text']) && !empty(trim($options['banner_text'])) ? wp_kses_post($options['banner_text']) : $default_banner_text,
            'accept_text'             => $options['accept_text'] ?? __( 'Accept', 'openconsent' ),
            'decline_text'            => $options['decline_text'] ?? __( 'Decline', 'openconsent' ),
            'minimized_text'          => $options['minimized_text'] ?? $minimized_text_default,
            'enable_minimized_widget' => !isset($options['enable_minimized_widget']) || $options['enable_minimized_widget'] == 1,
            'cookie_name'             => 'openconsent_status',
            'cookie_duration_days'    => 365,
            'banner_position'         => $options['banner_position'] ?? 'bottom-full',
            'ajax_url'                => admin_url( 'admin-ajax.php' ),
            'ajax_nonce'              => wp_create_nonce( 'openconsent_consent_nonce' ),
        ) );
    }

    public function ajax_update_wp_consent() {
        check_ajax_referer( 'openconsent_consent_nonce', 'nonce' );
        if ( ! function_exists( 'wp_set_consent' ) ) {
            wp_send_json_error( array( 'message' => 'WP Consent API not available.' ) );
            return;
        }
        $status_from_js = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'denied';
        $wp_api_status = ( $status_from_js === 'granted' ) ? 'allow' : 'deny';
        $categories = array( 'analytics', 'marketing', 'preferences', 'functional' );
        foreach ( $categories as $category ) {
            wp_set_consent( $category, $wp_api_status );
        }
        wp_send_json_success( array( 'message' => 'WP Consent API updated to: ' . $wp_api_status ) );
    }

    public function insert_gtm_consent_default() {
        echo "\n<script>\nwindow.dataLayer = window.dataLayer || [];\nfunction gtag(){dataLayer.push(arguments);}\ngtag('consent', 'default', {\n'ad_storage': 'denied', 'ad_user_data': 'denied', 'ad_personalization': 'denied', 'analytics_storage': 'denied'\n});\n</script>\n";
    }

    public function display_elements() {
        $options = $this->options;
        $banner_bg_color = $options['banner_bg_color'] ?? '#222222';
        $banner_text_color = $options['banner_text_color'] ?? '#ffffff';
        $link_color = $options['link_color'] ?? '#00a0d2';
        $banner_position = $options['banner_position'] ?? 'bottom-full';
        $accept_bg_color = $options['accept_button_bg_color'] ?? '#0073aa';
        $accept_text_color = $options['accept_button_text_color'] ?? '#ffffff';
        $decline_bg_color = $options['decline_button_bg_color'] ?? '#757575';
        $decline_text_color= $options['decline_button_text_color'] ?? '#ffffff';
        $widget_bg_color = $options['widget_bg_color'] ?? '#333333';
        $widget_text_color = $options['widget_text_color'] ?? '#ffffff';

        $banner_text_from_options = isset($options['banner_text']) ? trim($options['banner_text']) : '';
        $default_banner_text_val = sprintf('<p>%s <a href="YOUR_PRIVACY_POLICY_URL_HERE" target="_blank">%s</a>.</p>', __('This website uses cookies to enhance your experience.', 'openconsent'), __('Read our Privacy Policy', 'openconsent'));
        $banner_text = !empty($banner_text_from_options) ? wp_kses_post($options['banner_text']) : $default_banner_text_val;
        $accept_text = $options['accept_text'] ?? __( 'Accept', 'openconsent' );
        $decline_text = $options['decline_text'] ?? __( 'Decline', 'openconsent' );
        ?>
        <div id="oc-cookie-banner" class="oc-banner-position-<?php echo esc_attr( $banner_position ); ?>" style="display: none; background-color: <?php echo esc_attr( $banner_bg_color ); ?>; color: <?php echo esc_attr( $banner_text_color ); ?>;">
            <div class="oc-banner-content"><?php echo $banner_text; ?></div>
            <div class="oc-banner-actions">
                <button id="oc-accept-btn" style="background-color: <?php echo esc_attr( $accept_bg_color ); ?>; color: <?php echo esc_attr( $accept_text_color ); ?>;"><?php echo esc_html( $accept_text ); ?></button>
                <button id="oc-decline-btn" style="background-color: <?php echo esc_attr( $decline_bg_color ); ?>; color: <?php echo esc_attr( $decline_text_color ); ?>; margin-left: 10px;"><?php echo esc_html( $decline_text ); ?></button>
            </div>
        </div>
        <div id="oc-cookie-widget" style="display: none; background-color: <?php echo esc_attr( $widget_bg_color ); ?>; color: <?php echo esc_attr( $widget_text_color ); ?>;"></div>
        <style>
            #oc-cookie-banner .oc-banner-content a, #oc-cookie-banner .oc-banner-content a:visited { color: <?php echo esc_attr( $link_color ); ?> !important; }
            #oc-cookie-banner .oc-banner-content a:hover, #oc-cookie-banner .oc-banner-content a:focus { text-decoration: underline; }
        </style>
        <?php
    }
}

function openconsent_instantiate_plugin() {
    if ( class_exists('OpenConsent_Plugin') ) {
        $GLOBALS['openconsent_plugin'] = new OpenConsent_Plugin();
        $GLOBALS['openconsent_plugin']->init_plugin();
    }
}
add_action( 'plugins_loaded', 'openconsent_instantiate_plugin', 10 );