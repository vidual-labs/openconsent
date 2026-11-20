<?php
/**
 * Admin Settings for OpenConsent Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenConsent_Admin_Einstellungen {

    const GTM_ID_PATTERN = '/^GTM-[A-Z0-9]{4,12}$/';
    const DEFAULT_COOKIE_DURATION_DAYS = 180;
    const LEGACY_COOKIE_DURATION_DAYS = 365;
    const MAX_COOKIE_DURATION_DAYS = 390;

    public static function is_site_kit_active() {
        if ( defined( 'GOOGLESITEKIT_VERSION' ) ) {
            return true;
        }
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active( 'google-site-kit/google-site-kit.php' );
    }

    /**
     * How long the visitor's choice is remembered.
     * Sites that saved their settings before v1.1.2 keep the former 365 days
     * until the value is changed; new installs start with 180 days.
     *
     * @param array|false $options Stored plugin options.
     * @return int Days
     */
    public static function get_cookie_duration_days( $options ) {
        if ( ! is_array( $options ) ) {
            return self::DEFAULT_COOKIE_DURATION_DAYS;
        }
        if ( empty( $options['cookie_duration_days'] ) ) {
            return self::LEGACY_COOKIE_DURATION_DAYS;
        }
        return max( 1, min( self::MAX_COOKIE_DURATION_DAYS, absint( $options['cookie_duration_days'] ) ) );
    }

    /**
     * Default banner message. Names the purposes, Google as recipient and the
     * right to withdraw, and links to the site's privacy policy if one is set.
     */
    public static function get_default_banner_text() {
        $text = __( 'We use cookies and similar technologies for analytics and marketing, including services provided by Google. They are only activated if you click "Accept". You can decline, and you can change or withdraw your choice at any time.', 'openconsent' );

        $links = array();
        $privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
        if ( $privacy_url ) {
            $links[] = sprintf( '<a href="%s">%s</a>', esc_url( $privacy_url ), esc_html__( 'Privacy Policy', 'openconsent' ) );
        }
        $links[] = sprintf( '<a href="https://business.safety.google/privacy/" target="_blank" rel="noopener noreferrer">%s</a>', esc_html__( 'How Google uses data', 'openconsent' ) );

        return '<p>' . esc_html( $text ) . ' ' . implode( ' | ', $links ) . '</p>';
    }

    /**
     * Get default settings
     * @return array Default settings
     */
    public static function get_default_settings() {
        return array(
            'enable_banner' => 1,
            'enable_minimized_widget' => 1,
            'gtm_id' => '',
            'cookie_duration_days' => self::DEFAULT_COOKIE_DURATION_DAYS,
            'banner_text' => self::get_default_banner_text(),
            'accept_text' => __('Accept', 'openconsent'),
            'decline_text' => __('Decline', 'openconsent'),
            'minimized_text' => __('Cookie Settings', 'openconsent'),
            'banner_position' => 'bottom-full',
            'banner_bg_color' => '#222222',
            'banner_text_color' => '#ffffff',
            'link_color' => '#00a0d2',
            'accept_button_bg_color' => '#0073aa',
            'accept_button_text_color' => '#ffffff',
            'decline_button_bg_color' => '#757575',
            'decline_button_text_color' => '#ffffff',
            'widget_bg_color' => '#333333',
            'widget_text_color' => '#ffffff',
        );
    }

    /**
     * Register all settings, sections, and fields.
     */
    public static function register_settings() {
        register_setting(
            'openconsent_settings_group', // Option group
            OC_OPTION_NAME,               // Option name
            array(
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
                'show_in_rest' => false
            )
        );

        // --- SECTIONS ---
        add_settings_section( 'oc_status_section', __('Plugin Status', 'openconsent'), null, OC_SETTINGS_SLUG);
        add_settings_section( 'oc_integration_section', __('Standalone & Integration Settings', 'openconsent'), null, OC_SETTINGS_SLUG);
        add_settings_section( 'oc_content_section', __('Banner Content & Texts', 'openconsent'), null, OC_SETTINGS_SLUG);
        add_settings_section( 'oc_design_section', __('Banner & Widget Design', 'openconsent'), null, OC_SETTINGS_SLUG);

        // --- FIELDS ---

        // Status Section
        add_settings_field( 'oc_enable_banner', __('Enable Cookie Banner', 'openconsent'), array(__CLASS__, 'render_enable_banner_field'), OC_SETTINGS_SLUG, 'oc_status_section');

        // Integration Settings Section
        add_settings_field( 'oc_gtm_id', __('Google Tag Manager ID', 'openconsent'), array(__CLASS__, 'render_gtm_id_field'), OC_SETTINGS_SLUG, 'oc_integration_section');
        add_settings_field( 'oc_cookie_duration_days', __('Remember Choice For (Days)', 'openconsent'), array(__CLASS__, 'render_cookie_duration_field'), OC_SETTINGS_SLUG, 'oc_integration_section');

        // Content & Texts Section
        add_settings_field( 'oc_banner_text', __('Main Banner Message', 'openconsent'), array(__CLASS__, 'render_banner_text_field'), OC_SETTINGS_SLUG, 'oc_content_section');
        add_settings_field( 'oc_accept_text', __('Accept Button Text', 'openconsent'), array(__CLASS__, 'render_accept_text_field'), OC_SETTINGS_SLUG, 'oc_content_section');
        add_settings_field( 'oc_decline_text', __('Decline Button Text', 'openconsent'), array(__CLASS__, 'render_decline_text_field'), OC_SETTINGS_SLUG, 'oc_content_section');
        add_settings_field( 'oc_enable_minimized_widget', __('Minimized "Re-Open" Widget', 'openconsent'), array(__CLASS__, 'render_enable_minimized_widget_field'), OC_SETTINGS_SLUG, 'oc_content_section');
        add_settings_field( 'oc_minimized_text', __('Minimized Widget Text', 'openconsent'), array(__CLASS__, 'render_minimized_text_field'), OC_SETTINGS_SLUG, 'oc_content_section');

        // Design Section
        add_settings_field('oc_banner_position', __('Banner Position', 'openconsent'), array(__CLASS__, 'render_banner_position_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_banner_bg_color', __('Banner Background Color', 'openconsent'), array(__CLASS__, 'render_banner_bg_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_banner_text_color', __('Banner Text Color', 'openconsent'), array(__CLASS__, 'render_banner_text_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_link_color', __('Link Color (in banner message)', 'openconsent'), array(__CLASS__, 'render_link_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_accept_button_bg_color', __('Accept Button Background', 'openconsent'), array(__CLASS__, 'render_accept_button_bg_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_accept_button_text_color', __('Accept Button Text Color', 'openconsent'), array(__CLASS__, 'render_accept_button_text_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_decline_button_bg_color', __('Decline Button Background', 'openconsent'), array(__CLASS__, 'render_decline_button_bg_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_decline_button_text_color', __('Decline Button Text Color', 'openconsent'), array(__CLASS__, 'render_decline_button_text_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_widget_bg_color', __('Minimized Widget Background', 'openconsent'), array(__CLASS__, 'render_widget_bg_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
        add_settings_field('oc_widget_text_color', __('Minimized Widget Text Color', 'openconsent'), array(__CLASS__, 'render_widget_text_color_field'), OC_SETTINGS_SLUG, 'oc_design_section');
    }

    public static function sanitize_settings( $input ) {
        $sanitized_input = array();
        $options = get_option( OC_OPTION_NAME );

        $input = (array) $input;

        $sanitized_input['enable_banner'] = ! empty( $input['enable_banner'] ) ? 1 : 0;
        $sanitized_input['enable_minimized_widget'] = ! empty( $input['enable_minimized_widget'] ) ? 1 : 0;

        if ( isset( $input['cookie_duration_days'] ) ) {
            $days = absint( $input['cookie_duration_days'] );
            if ( $days >= 1 ) {
                $sanitized_input['cookie_duration_days'] = min( self::MAX_COOKIE_DURATION_DAYS, $days );
            }
        }

        if ( isset( $input['gtm_id'] ) ) {
            $gtm_id_value = strtoupper( sanitize_text_field( $input['gtm_id'] ) );
            if ( ! empty( $gtm_id_value ) && ! preg_match( self::GTM_ID_PATTERN, $gtm_id_value ) ) {
                add_settings_error('oc_gtm_id', 'oc_gtm_id_error', __('Invalid GTM ID format. It must start with "GTM-" followed by letters (A-Z) or numbers (0-9). Example: GTM-TWZDPZ2N or GTM-K2PQR79.', 'openconsent'), 'error');
                $sanitized_input['gtm_id'] = $options['gtm_id'] ?? '';
            } else {
                $sanitized_input['gtm_id'] = $gtm_id_value;
            }
        }

        if ( isset( $input['banner_text'] ) ) {
            $sanitized_input['banner_text'] = wp_kses_post( $input['banner_text'] );
        }

        $text_fields = ['accept_text', 'decline_text', 'minimized_text'];
        foreach ( $text_fields as $field ) {
            if ( isset( $input[$field] ) ) {
                $sanitized_input[$field] = sanitize_text_field( $input[$field] );
            }
        }

        $color_fields = [
            'banner_bg_color', 'banner_text_color', 'link_color',
            'accept_button_bg_color', 'accept_button_text_color',
            'decline_button_bg_color', 'decline_button_text_color',
            'widget_bg_color', 'widget_text_color'
        ];
        foreach ( $color_fields as $field ) {
            if ( isset( $input[$field] ) ) {
                // Invalid or empty values are ignored so the previous colour stays in place.
                $color = sanitize_hex_color( $input[$field] );
                if ( ! empty( $color ) ) {
                    $sanitized_input[$field] = $color;
                }
            }
        }

        if ( isset( $input['banner_position'] ) ) {
            $valid_positions = ['bottom-full', 'bottom-left', 'bottom-center', 'bottom-right', 'top-full', 'top-left', 'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right'];
            if (in_array($input['banner_position'], $valid_positions, true)) {
                 $sanitized_input['banner_position'] = $input['banner_position'];
            } else {
                 $sanitized_input['banner_position'] = $options['banner_position'] ?? 'bottom-full';
            }
        }

        return array_merge( (array) $options, $sanitized_input );
    }

    public static function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p style="color: #666;"><?php esc_html_e( 'Configure your cookie consent banner settings below. All colors support hex values.', 'openconsent' ); ?></p>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'openconsent_settings_group' );
                do_settings_sections( OC_SETTINGS_SLUG );
                submit_button( __( 'Save Settings', 'openconsent' ) );
                ?>
            </form>

            <hr style="margin: 40px 0;">
            <h2><?php esc_html_e( 'Import/Export Settings', 'openconsent' ); ?></h2>
            <p><?php esc_html_e( 'Backup your settings or transfer them to another site.', 'openconsent' ); ?></p>

            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field( 'oc_export_nonce' ); ?>
                <?php submit_button( __( 'Export Settings', 'openconsent' ), 'secondary', 'oc_export_settings' ); ?>
            </form>

            <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <?php wp_nonce_field( 'oc_import_nonce' ); ?>
                <input type="file" name="oc_import_file" accept=".json" required>
                <?php submit_button( __( 'Import Settings', 'openconsent' ), 'secondary', 'oc_import_settings' ); ?>
            </form>

            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field( 'oc_reset_nonce' ); ?>
                <?php submit_button( __( 'Reset to Defaults', 'openconsent' ), 'delete', 'oc_reset_settings', array( 'onclick' => 'return confirm("' . esc_attr( __( 'Are you sure? This will reset all settings to defaults.', 'openconsent' ) ) . '");' ) ); ?>
            </form>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.oc-color-picker').wpColorPicker();
            });
        </script>
        <?php
    }

    /**
     * Handle export / import / reset. Runs on admin_init, i.e. before any output,
     * so the export can send its download headers.
     */
    public static function handle_admin_actions() {
        if ( empty( $_POST ) || ! isset( $_GET['page'] ) || OC_SETTINGS_SLUG !== $_GET['page'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['oc_export_settings'] ) && check_admin_referer( 'oc_export_nonce' ) ) {
            self::handle_export_settings();
        }
        if ( isset( $_POST['oc_import_settings'] ) && check_admin_referer( 'oc_import_nonce' ) ) {
            self::handle_import_settings();
        }
        if ( isset( $_POST['oc_reset_settings'] ) && check_admin_referer( 'oc_reset_nonce' ) ) {
            self::handle_reset_settings();
        }
    }

    /**
     * Handle settings export
     */
    public static function handle_export_settings() {
        $settings = get_option( OC_OPTION_NAME );
        if ( ! is_array( $settings ) ) {
            $settings = self::get_default_settings();
        }
        $filename = 'openconsent-settings-' . gmdate( 'Y-m-d-H-i-s' ) . '.json';

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo wp_json_encode( $settings, JSON_PRETTY_PRINT );
        exit;
    }

    /**
     * Handle settings import
     */
    public static function handle_import_settings() {
        if ( ! isset( $_FILES['oc_import_file'] ) || $_FILES['oc_import_file']['error'] !== UPLOAD_ERR_OK ) {
            add_settings_error( 'oc_import', 'oc_import_error', __( 'Failed to upload file.', 'openconsent' ), 'error' );
            return;
        }

        $tmp_name = $_FILES['oc_import_file']['tmp_name'];
        if ( ! is_uploaded_file( $tmp_name ) || $_FILES['oc_import_file']['size'] > 512 * KB_IN_BYTES ) {
            add_settings_error( 'oc_import', 'oc_import_error', __( 'Invalid settings file format.', 'openconsent' ), 'error' );
            return;
        }

        $file_content = file_get_contents( $tmp_name );
        $imported_settings = json_decode( $file_content, true );

        if ( ! is_array( $imported_settings ) ) {
            add_settings_error( 'oc_import', 'oc_import_error', __( 'Invalid settings file format.', 'openconsent' ), 'error' );
            return;
        }

        // Sanitize imported settings
        $sanitized = self::sanitize_settings( $imported_settings );
        update_option( OC_OPTION_NAME, $sanitized );
        add_settings_error( 'oc_import', 'oc_import_success', __( 'Settings imported successfully.', 'openconsent' ), 'updated' );
    }

    /**
     * Handle settings reset
     */
    public static function handle_reset_settings() {
        update_option( OC_OPTION_NAME, self::get_default_settings() );
        add_settings_error( 'oc_reset', 'oc_reset_success', __( 'Settings have been reset to defaults.', 'openconsent' ), 'updated' );
    }

    public static function render_enable_banner_field() {
        $options = get_option( OC_OPTION_NAME );
        $checked = isset( $options['enable_banner'] ) && $options['enable_banner'] == 1 ? 'checked' : '';
        echo '<label><input type="checkbox" id="oc_enable_banner" name="' . OC_OPTION_NAME . '[enable_banner]" value="1" ' . $checked . '></label>';
        echo '<p class="description">' . __( 'Globally activate or deactivate the cookie consent functionality.', 'openconsent' ) . '</p>';
    }

    public static function render_gtm_id_field() {
        $options = get_option( OC_OPTION_NAME );
        $value = $options['gtm_id'] ?? '';
        $site_kit_active = self::is_site_kit_active();

        echo '<input type="text" id="oc_gtm_id" name="' . OC_OPTION_NAME . '[gtm_id]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="GTM-XXXXXXX" pattern="[Gg][Tt][Mm]-[A-Za-z0-9]{4,12}">';

        if ($site_kit_active) {
            echo '<p class="description" style="color: #0a7600; background: #f0f6f0; padding: 8px; border-radius: 3px; margin-top: 8px;">' .
                sprintf(
                    '<strong>✓ %s</strong> %s',
                    __('Google Site Kit is active', 'openconsent'),
                    __('OpenConsent passes consent to Site Kit via the WP Consent API. Configure your GTM ID in Site Kit, enable "Consent Mode" in the Site Kit settings and make sure the WP Consent API plugin is active.', 'openconsent')
                ) .
                '</p>';
        } else {
            echo '<p class="description" style="background: #f9f9f9; padding: 8px; border-radius: 3px; margin-top: 8px; border-left: 4px solid #0073aa;">' .
                sprintf(
                    '<strong>%s:</strong> %s',
                    __('Standalone Mode', 'openconsent'),
                    __('Google Site Kit is not active. OpenConsent will load GTM directly if you provide a valid GTM ID. This enables consent mode integration.', 'openconsent')
                ) .
                '</p>';
        }
    }

    public static function render_cookie_duration_field() {
        $value = self::get_cookie_duration_days( get_option( OC_OPTION_NAME ) );
        echo '<input type="number" id="oc_cookie_duration_days" name="' . OC_OPTION_NAME . '[cookie_duration_days]" value="' . esc_attr( $value ) . '" class="small-text" min="1" max="' . esc_attr( self::MAX_COOKIE_DURATION_DAYS ) . '" step="1">';
        echo '<p class="description">' . esc_html__( 'After this period visitors are asked again. Supervisory authorities recommend 6 to 12 months (the French CNIL: 6 months).', 'openconsent' ) . '</p>';
    }

    public static function render_banner_text_field() {
        $options = get_option( OC_OPTION_NAME );
        $defaults = self::get_default_settings();
        $content = $options['banner_text'] ?? $defaults['banner_text'];

        wp_editor(
            $content,
            'oc_banner_text_editor',
            array(
                'textarea_name' => OC_OPTION_NAME . '[banner_text]',
                'media_buttons' => false,
                'textarea_rows' => 7,
                'quicktags' => true,
                'tinymce' => array(
                    'toolbar1' => 'bold,italic,underline,link,unlink'
                )
            )
        );

        echo '<p class="description">' .
            sprintf(
                '%s %s',
                __( 'Enter the message for the main cookie banner. Use the editor to add formatting and links.', 'openconsent' ),
                sprintf(
                    '<strong>%s:</strong> %s',
                    __( 'Tip', 'openconsent' ),
                    __( 'Name the purposes (analytics, marketing), mention Google as recipient, say that the choice can be withdrawn and link to your Privacy Policy. This is required by the GDPR and by Google\'s EU user consent policy.', 'openconsent' )
                )
            ) .
            '</p>';
    }

    public static function render_accept_text_field() {
        $options = get_option( OC_OPTION_NAME );
        $value = $options['accept_text'] ?? __( 'Accept', 'openconsent' );
        echo '<input type="text" id="oc_accept_text" name="' . OC_OPTION_NAME . '[accept_text]" value="' . esc_attr( $value ) . '" class="regular-text">';
    }

    public static function render_decline_text_field() {
        $options = get_option( OC_OPTION_NAME );
        $value = $options['decline_text'] ?? __( 'Decline', 'openconsent' );
        echo '<input type="text" id="oc_decline_text" name="' . OC_OPTION_NAME . '[decline_text]" value="' . esc_attr( $value ) . '" class="regular-text">';
    }

    public static function render_enable_minimized_widget_field() {
        $options = get_option( OC_OPTION_NAME );
        $checked = !isset( $options['enable_minimized_widget'] ) || $options['enable_minimized_widget'] == 1 ? 'checked' : '';
        echo '<label><input type="checkbox" id="oc_enable_minimized_widget" name="' . OC_OPTION_NAME . '[enable_minimized_widget]" value="1" ' . $checked . '></label>';
        echo '<p class="description">' . esc_html__( 'Show the minimized widget after the user makes a choice. Visitors must be able to withdraw consent as easily as they gave it: if you turn the widget off, place the shortcode [openconsent_settings_link] (e.g. in your footer or privacy policy) so the banner can be re-opened.', 'openconsent' ) . '</p>';
    }

    public static function render_minimized_text_field() {
        $options = get_option( OC_OPTION_NAME );
        $value = $options['minimized_text'] ?? __( 'Cookie Settings', 'openconsent' );
        echo '<input type="text" id="oc_minimized_text" name="' . OC_OPTION_NAME . '[minimized_text]" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . __( 'Text for the small tab to re-open the cookie banner. Only shown if the widget is enabled above.', 'openconsent' ) . '</p>';
    }

    public static function render_banner_position_field() {
        $options = get_option( OC_OPTION_NAME );
        $current_position = $options['banner_position'] ?? 'bottom-full';
        $positions = ['bottom-full' => __( 'Bottom (Full Width)', 'openconsent'), 'bottom-left' => __( 'Bottom Left (Floating)', 'openconsent'), 'bottom-center' => __( 'Bottom Center (Floating)', 'openconsent'), 'bottom-right' => __( 'Bottom Right (Floating)', 'openconsent'), 'top-full' => __( 'Top (Full Width)', 'openconsent'), 'top-left' => __( 'Top Left (Floating)', 'openconsent'), 'top-center' => __( 'Top Center (Floating)', 'openconsent'), 'top-right' => __( 'Top Right (Floating)', 'openconsent'), 'middle-left' => __( 'Middle Left (Floating)', 'openconsent'), 'middle-center' => __( 'Middle Center (Modal)', 'openconsent'), 'middle-right' => __( 'Middle Right (Floating)', 'openconsent')];
        echo '<select id="oc_banner_position" name="' . OC_OPTION_NAME . '[banner_position]">';
        foreach ($positions as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_position, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    private static function render_color_picker_field( $field_name, $default_color = '#000000' ) {
        $options = get_option( OC_OPTION_NAME );
        $value = $options[$field_name] ?? $default_color;
        echo '<input type="text" id="oc_' . esc_attr($field_name) . '" name="' . OC_OPTION_NAME . '[' . esc_attr( $field_name ) . ']" value="' . esc_attr( $value ) . '" class="oc-color-picker" data-default-color="' . esc_attr( $default_color ) . '">';
    }

    public static function render_banner_bg_color_field() { self::render_color_picker_field( 'banner_bg_color', '#222222' ); }
    public static function render_banner_text_color_field() { self::render_color_picker_field( 'banner_text_color', '#ffffff' ); }
    public static function render_link_color_field() { self::render_color_picker_field( 'link_color', '#00a0d2' ); }
    public static function render_accept_button_bg_color_field() { self::render_color_picker_field( 'accept_button_bg_color', '#0073aa' ); }
    public static function render_accept_button_text_color_field() { self::render_color_picker_field( 'accept_button_text_color', '#ffffff' ); }
    public static function render_decline_button_bg_color_field() {
        self::render_color_picker_field( 'decline_button_bg_color', '#757575' );
        echo '<p class="description" style="background: #fef5f5; border-left: 4px solid #c00; padding: 10px; border-radius: 3px; color: #333;">' .
            sprintf(
                '<strong>⚠️ %s:</strong> %s',
                __( 'GDPR/CCPA Compliance Required', 'openconsent' ),
                __( 'The "Decline" button must be as prominent and easy to click as the "Accept" button. Visual de-emphasis may make your banner non-compliant with regulations. Consider using similar colors and prominence for both buttons.', 'openconsent' )
            ) .
            '</p>';
    }
    public static function render_decline_button_text_color_field() { self::render_color_picker_field( 'decline_button_text_color', '#ffffff' ); }
    public static function render_widget_bg_color_field() { self::render_color_picker_field( 'widget_bg_color', '#333333' ); }
    public static function render_widget_text_color_field() { self::render_color_picker_field( 'widget_text_color', '#ffffff' ); }
}