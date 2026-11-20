<?php
/**
 * Plugin Name: OpenConsent
 * Plugin URI: https://vidual.org/openconsent
 * Description: A cookie banner that provides consent signals to the WP Consent API or manages GTM standalone (Google Consent Mode v2, Basic).
 * Version: 1.1.2
 * Requires at least: 5.0
 * Requires PHP: 7.0
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
define( 'OC_VERSION', '1.1.2' );
define( 'OC_SETTINGS_SLUG', 'openconsent_settings' );
define( 'OC_OPTION_NAME', 'openconsent_settings' );
define( 'OC_COOKIE_NAME', 'openconsent_status' );
// Categories of the WP Consent API that follow the visitor's choice.
// ('functional' is always allowed and therefore not part of this list.)
define( 'OC_CONSENT_API_CATEGORIES', array( 'statistics', 'statistics-anonymous', 'marketing', 'preferences' ) );

require_once OC_PLUGIN_DIR . 'admin/admin-einstellungen.php';

class OpenConsent_Plugin {
    private $options;
    private $is_banner_enabled;
    private $is_site_kit_active;
    private $should_load_own_gtm;

    public function init_plugin() {
        $this->options = get_option( OC_OPTION_NAME );
        $this->is_banner_enabled = ! empty( $this->options['enable_banner'] );
        $this->is_site_kit_active = OpenConsent_Admin_Einstellungen::is_site_kit_active();
        $this->should_load_own_gtm = ! $this->is_site_kit_active && '' !== $this->get_gtm_id();

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( 'OpenConsent_Admin_Einstellungen', 'register_settings' ) );
        add_action( 'admin_init', array( 'OpenConsent_Admin_Einstellungen', 'handle_admin_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        add_action( 'wp_ajax_nopriv_openconsent_update_consent', array( $this, 'ajax_update_wp_consent' ) );
        add_action( 'wp_ajax_openconsent_update_consent', array( $this, 'ajax_update_wp_consent' ) );

        add_shortcode( 'openconsent_settings_link', array( $this, 'render_settings_link_shortcode' ) );

        if ( $this->is_banner_enabled ) {
            // WP Consent API: declare this plugin as a registered, opt-in consent manager.
            add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );
            add_filter( 'wp_get_consent_type', array( $this, 'filter_consent_type' ) );
            add_filter( 'wp_consent_api_cookie_expiration', array( $this, 'filter_consent_cookie_expiration' ) );

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

    /**
     * Returns the stored GTM ID if it is valid, otherwise an empty string.
     */
    private function get_gtm_id() {
        $gtm_id = isset( $this->options['gtm_id'] ) ? strtoupper( trim( (string) $this->options['gtm_id'] ) ) : '';
        return preg_match( OpenConsent_Admin_Einstellungen::GTM_ID_PATTERN, $gtm_id ) ? $gtm_id : '';
    }

    /**
     * Returns a stored setting, falling back to the default when it is missing or empty.
     */
    private function get_setting( $key ) {
        if ( isset( $this->options[ $key ] ) && '' !== trim( (string) $this->options[ $key ] ) ) {
            return $this->options[ $key ];
        }
        $defaults = OpenConsent_Admin_Einstellungen::get_default_settings();
        return $defaults[ $key ] ?? '';
    }

    private function get_cookie_duration_days() {
        return OpenConsent_Admin_Einstellungen::get_cookie_duration_days( $this->options );
    }

    public function filter_consent_type() {
        return 'optin';
    }

    public function filter_consent_cookie_expiration() {
        return $this->get_cookie_duration_days();
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

        wp_localize_script( 'oc-frontend-js', 'oc_js_vars', array(
            'gtm_id'                  => $this->get_gtm_id(),
            'should_load_own_gtm'     => $this->should_load_own_gtm,
            'banner_text'             => wp_kses_post( $this->get_setting( 'banner_text' ) ),
            'accept_text'             => $this->get_setting( 'accept_text' ),
            'decline_text'            => $this->get_setting( 'decline_text' ),
            'minimized_text'          => $this->get_setting( 'minimized_text' ),
            'enable_minimized_widget' => !isset($options['enable_minimized_widget']) || $options['enable_minimized_widget'] == 1,
            'cookie_name'             => OC_COOKIE_NAME,
            'cookie_duration_days'    => $this->get_cookie_duration_days(),
            'banner_position'         => $this->get_setting( 'banner_position' ),
            'consent_categories'      => OC_CONSENT_API_CATEGORIES,
            'wp_consent_api_active'   => function_exists( 'wp_set_consent' ),
            'debug'                   => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'ajax_url'                => admin_url( 'admin-ajax.php' ),
            'ajax_nonce'              => wp_create_nonce( 'openconsent_consent_nonce' ),
        ) );
    }

    public function ajax_update_wp_consent() {
        if ( ! check_ajax_referer( 'openconsent_consent_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security verification failed.' ), 403 );
        }

        if ( ! function_exists( 'wp_set_consent' ) ) {
            wp_send_json_error( array( 'message' => 'WP Consent API not available.' ) );
        }

        // Validate and sanitize status
        $status_from_js = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'denied';
        if ( ! in_array( $status_from_js, array( 'granted', 'denied' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid consent status provided.' ) );
        }

        $wp_api_status = ( $status_from_js === 'granted' ) ? 'allow' : 'deny';

        wp_set_consent( 'functional', 'allow' );
        foreach ( OC_CONSENT_API_CATEGORIES as $category ) {
            wp_set_consent( $category, $wp_api_status );
        }

        wp_send_json_success( array(
            'message' => 'WP Consent API updated to: ' . $wp_api_status,
            'status' => $status_from_js
        ) );
    }

    /**
     * Standalone mode: sets the Consent Mode v2 defaults before anything else runs.
     * Returning visitors who already granted consent get the update and GTM right here,
     * so tags do not have to wait for the footer script.
     */
    public function insert_gtm_consent_default() {
        ?>
        <script id="openconsent-consent-default" data-cfasync="false">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied',
                'functionality_storage': 'denied',
                'personalization_storage': 'denied',
                'security_storage': 'granted',
                'wait_for_update': 500
            });
            gtag('set', 'ads_data_redaction', true);
            (function(w,d,s,l,i){
                var m = d.cookie.match(/(?:^|;\s*)<?php echo esc_js( OC_COOKIE_NAME ); ?>=([^;]*)/);
                if (!m || m[1] !== 'granted') return;
                gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted',
                    'analytics_storage': 'granted',
                    'functionality_storage': 'granted',
                    'personalization_storage': 'granted'
                });
                w.ocGtmLoaded = true;
                w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
                var f = d.getElementsByTagName(s)[0], j = d.createElement(s);
                j.async = true;
                j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', <?php echo wp_json_encode( $this->get_gtm_id() ); ?>);
        </script>
        <?php
    }

    /**
     * Shortcode [openconsent_settings_link]: a link that re-opens the banner,
     * e.g. for the privacy policy page or the footer.
     */
    public function render_settings_link_shortcode( $atts, $content = '' ) {
        if ( ! $this->is_banner_enabled ) return '';
        $label = '' !== trim( (string) $content ) ? $content : $this->get_setting( 'minimized_text' );
        return '<a href="#openconsent" class="oc-open-settings">' . esc_html( $label ) . '</a>';
    }

    public function display_elements() {
        $banner_bg_color = $this->get_setting( 'banner_bg_color' );
        $banner_text_color = $this->get_setting( 'banner_text_color' );
        $link_color = $this->get_setting( 'link_color' );
        $banner_position = $this->get_setting( 'banner_position' );
        $accept_bg_color = $this->get_setting( 'accept_button_bg_color' );
        $accept_text_color = $this->get_setting( 'accept_button_text_color' );
        $decline_bg_color = $this->get_setting( 'decline_button_bg_color' );
        $decline_text_color= $this->get_setting( 'decline_button_text_color' );
        $widget_bg_color = $this->get_setting( 'widget_bg_color' );
        $widget_text_color = $this->get_setting( 'widget_text_color' );

        $banner_text = wp_kses_post( $this->get_setting( 'banner_text' ) );
        $accept_text = $this->get_setting( 'accept_text' );
        $decline_text = $this->get_setting( 'decline_text' );
        $minimized_text = $this->get_setting( 'minimized_text' );
        ?>
        <div id="oc-cookie-banner" role="dialog" aria-modal="false" aria-label="<?php esc_attr_e( 'Cookie consent', 'openconsent' ); ?>" tabindex="-1" class="oc-banner-position-<?php echo esc_attr( $banner_position ); ?>" style="display: none; background-color: <?php echo esc_attr( $banner_bg_color ); ?>; color: <?php echo esc_attr( $banner_text_color ); ?>;">
            <div class="oc-banner-content"><?php echo $banner_text; ?></div>
            <div class="oc-banner-actions">
                <button type="button" id="oc-accept-btn" style="background-color: <?php echo esc_attr( $accept_bg_color ); ?>; color: <?php echo esc_attr( $accept_text_color ); ?>;"><?php echo esc_html( $accept_text ); ?></button>
                <button type="button" id="oc-decline-btn" style="background-color: <?php echo esc_attr( $decline_bg_color ); ?>; color: <?php echo esc_attr( $decline_text_color ); ?>; margin-left: 10px;"><?php echo esc_html( $decline_text ); ?></button>
            </div>
        </div>
        <button type="button" id="oc-cookie-widget" style="display: none; background-color: <?php echo esc_attr( $widget_bg_color ); ?>; color: <?php echo esc_attr( $widget_text_color ); ?>;"><?php echo esc_html( $minimized_text ); ?></button>
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
