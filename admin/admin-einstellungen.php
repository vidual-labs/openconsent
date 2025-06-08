<?php
/**
 * Admin Settings for OpenConsent Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenConsent_Admin_Einstellungen {

    /**
     * Register all settings, sections, and fields.
     */
    public static function register_settings() {
        register_setting(
            'openconsent_settings_group', // Option group
            OC_OPTION_NAME,               // Option name
            array( __CLASS__, 'sanitize_settings' ) // Sanitize callback
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

        $sanitized_input['enable_banner'] = isset( $input['enable_banner'] ) ? 1 : 0;
        $sanitized_input['enable_minimized_widget'] = isset( $input['enable_minimized_widget'] ) ? 1 : 0;

        if ( isset( $input['gtm_id'] ) ) {
            $gtm_id_value = sanitize_text_field( $input['gtm_id'] );
            if ( ! empty( $gtm_id_value ) && ! preg_match( '/^GTM-[A-Z0-9]{6,8}$/i', $gtm_id_value ) ) {
                add_settings_error('oc_gtm_id', 'oc_gtm_id_error', __('Invalid GTM ID format. It must start with "GTM-" followed by 6 to 8 letters (A-Z) or numbers (0-9). Example: GTM-TWZDPZ2N or GTM-K2PQR79.', 'openconsent'), 'error');
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
                $sanitized_input[$field] = sanitize_hex_color( $input[$field] );
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
            <?php settings_errors(); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'openconsent_settings_group' );
                do_settings_sections( OC_SETTINGS_SLUG );
                submit_button( __( 'Save Settings', 'openconsent' ) );
                ?>
            </form>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.oc-color-picker').wpColorPicker();
            });
        </script>
        <?php
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
        $site_kit_active = is_plugin_active('google-site-kit/google-site-kit.php');

        echo '<input type="text" id="oc_gtm_id" name="' . OC_OPTION_NAME . '[gtm_id]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="GTM-XXXXXXX">';
        
        if ($site_kit_active) {
            echo '<p class="description" style="color: green;">' . __( '<strong>Google Site Kit is active.</strong> OpenConsent will not load GTM itself. It will only pass consent information to Site Kit. Please ensure your GTM ID is configured within Site Kit.', 'openconsent' ) . '</p>';
        } else {
            echo '<p class="description">' . __( '<strong>Standalone Mode:</strong> Google Site Kit is not active. OpenConsent will load GTM using this ID if provided.', 'openconsent' ) . '</p>';
        }
    }

    public static function render_banner_text_field() {
        $options = get_option( OC_OPTION_NAME );
        $default_text = sprintf('<p>%s <a href="YOUR_PRIVACY_POLICY_URL_HERE" target="_blank">%s</a>.</p>', __('This website uses cookies to enhance your experience.', 'openconsent'), __('Read our Privacy Policy', 'openconsent'));
        $content = $options['banner_text'] ?? $default_text;
        wp_editor( $content, 'oc_banner_text_editor', array( 'textarea_name' => OC_OPTION_NAME . '[banner_text]', 'media_buttons' => false, 'textarea_rows' => 7, 'quicktags' => true, 'tinymce' => array( 'toolbar1' => 'bold,italic,underline,link,unlink')) );
        echo '<p class="description">' . __( 'Enter the message for the main cookie banner. Use the editor to add formatting and links.', 'openconsent' ) . '</p>';
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
        echo '<p class="description">' . __( 'Show the minimized widget after the user makes a choice.', 'openconsent' ) . '</p>';
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
        echo '<p class="description" style="color:#c00;">' . __( '<strong>Note:</strong> To comply with regulations like GDPR, the "Decline" option must be as easy to choose as "Accept". Visually de-emphasizing this button may not be compliant.', 'openconsent' ) . '</p>';
    }
    public static function render_decline_button_text_color_field() { self::render_color_picker_field( 'decline_button_text_color', '#ffffff' ); }
    public static function render_widget_bg_color_field() { self::render_color_picker_field( 'widget_bg_color', '#333333' ); }
    public static function render_widget_text_color_field() { self::render_color_picker_field( 'widget_text_color', '#ffffff' ); }
}