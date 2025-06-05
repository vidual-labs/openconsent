<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenConsent_Admin_Einstellungen {

    public static function register_settings() {
        register_setting(
            'openconsent_settings_group', // Option group
            OC_OPTION_NAME,               // Option name (e.g., 'openconsent_settings')
            array( __CLASS__, 'sanitize_settings' ) // Sanitize callback
        );

        // Enable/Disable Section
        add_settings_section(
            'oc_status_section',
            __( 'Plugin Status', 'openconsent' ),
            null,
            OC_SETTINGS_SLUG
        );

        add_settings_field(
            'oc_enable_banner',
            __( 'Enable Cookie Banner', 'openconsent' ),
            array( __CLASS__, 'render_enable_banner_field' ),
            OC_SETTINGS_SLUG,
            'oc_status_section'
        );

        // General Settings Section
        add_settings_section(
            'oc_general_section',
            __( 'General Settings', 'openconsent' ),
            null,
            OC_SETTINGS_SLUG
        );

        add_settings_field(
            'oc_gtm_id',
            __( 'Google Tag Manager ID', 'openconsent' ),
            array( __CLASS__, 'render_gtm_id_field' ),
            OC_SETTINGS_SLUG,
            'oc_general_section'
        );

        // Banner Content Section
        add_settings_section(
            'oc_content_section',
            __( 'Banner Content & Texts', 'openconsent' ), // Section title updated
            null,
            OC_SETTINGS_SLUG
        );

        add_settings_field(
            'oc_banner_text',
            __( 'Main Banner Message', 'openconsent' ),
            array( __CLASS__, 'render_banner_text_field' ),
            OC_SETTINGS_SLUG,
            'oc_content_section'
        );

        add_settings_field(
            'oc_accept_text',
            __( 'Accept Button Text', 'openconsent' ),
            array( __CLASS__, 'render_accept_text_field' ),
            OC_SETTINGS_SLUG,
            'oc_content_section'
        );

        add_settings_field(
            'oc_decline_text',
            __( 'Decline Button Text', 'openconsent' ),
            array( __CLASS__, 'render_decline_text_field' ),
            OC_SETTINGS_SLUG,
            'oc_content_section'
        );

        add_settings_field( // New field for minimized banner text
            'oc_minimized_text',
            __( 'Minimized Banner Text', 'openconsent' ),
            array( __CLASS__, 'render_minimized_text_field' ),
            OC_SETTINGS_SLUG,
            'oc_content_section'
        );


        // Banner Design Section
        add_settings_section(
            'oc_design_section',
            __( 'Banner Design', 'openconsent' ),
            null,
            OC_SETTINGS_SLUG
        );

        add_settings_field(
            'oc_banner_position',
            __( 'Banner Position', 'openconsent' ),
            array( __CLASS__, 'render_banner_position_field' ),
            OC_SETTINGS_SLUG,
            'oc_design_section'
        );

        add_settings_field(
            'oc_banner_bg_color',
            __( 'Banner Background Color', 'openconsent' ),
            array( __CLASS__, 'render_banner_bg_color_field' ),
            OC_SETTINGS_SLUG,
            'oc_design_section'
        );

        add_settings_field(
            'oc_banner_text_color',
            __( 'Banner Text Color', 'openconsent' ),
            array( __CLASS__, 'render_banner_text_color_field' ),
            OC_SETTINGS_SLUG,
            'oc_design_section'
        );

        add_settings_field(
            'oc_link_color',
            __( 'Link Color (in banner message)', 'openconsent' ),
            array( __CLASS__, 'render_link_color_field' ),
            OC_SETTINGS_SLUG,
            'oc_design_section'
        );

        add_settings_field(
            'oc_button_bg_color',
            __( 'Button Background Color', 'openconsent' ),
            array( __CLASS__, 'render_button_bg_color_field' ),
            OC_SETTINGS_SLUG,
            'oc_design_section'
        );

        add_settings_field(
            'oc_button_text_color',
            __( 'Button Text Color', 'openconsent' ),
            array( __CLASS__, 'render_button_text_color_field' ),
            OC_SETTINGS_SLUG,
            'oc_design_section'
        );
    }

    public static function sanitize_settings( $input ) {
        $sanitized_input = array();
        $options = get_option( OC_OPTION_NAME );

        $sanitized_input['enable_banner'] = isset( $input['enable_banner'] ) ? 1 : 0;

        if ( isset( $input['gtm_id'] ) ) {
            $gtm_id_value = sanitize_text_field( $input['gtm_id'] );
            if ( ! empty( $gtm_id_value ) && ! preg_match( '/^GTM-[A-Z0-9]{6,8}$/i', $gtm_id_value ) ) { // Updated Regex
                add_settings_error(
                    'oc_gtm_id', 'oc_gtm_id_error',
                    __( 'Invalid GTM ID format. It must start with "GTM-" followed by 6 to 8 letters (A-Z, a-z) or numbers (0-9). Example: GTM-TWZDPZ2N or GTM-K2PQR79.', 'openconsent' ),
                    'error'
                );
                $sanitized_input['gtm_id'] = $options['gtm_id'] ?? '';
            } else {
                $sanitized_input['gtm_id'] = $gtm_id_value;
            }
        } else {
             $sanitized_input['gtm_id'] = '';
        }

        if ( isset( $input['banner_text'] ) ) {
            $sanitized_input['banner_text'] = wp_kses_post( $input['banner_text'] );
        }

        // Added 'minimized_text' to text fields
        $text_fields = ['accept_text', 'decline_text', 'minimized_text'];
        foreach ( $text_fields as $field ) {
            if ( isset( $input[$field] ) ) {
                $sanitized_input[$field] = sanitize_text_field( $input[$field] );
            }
        }

        $color_fields = ['banner_bg_color', 'banner_text_color', 'link_color', 'button_bg_color', 'button_text_color'];
        foreach ( $color_fields as $field ) {
            if ( isset( $input[$field] ) ) {
                $sanitized_input[$field] = sanitize_hex_color( $input[$field] );
            }
        }

        if ( isset( $input['banner_position'] ) ) {
            $valid_positions = [
                'bottom-full', 'bottom-left', 'bottom-center', 'bottom-right',
                'top-full', 'top-left', 'top-center', 'top-right',
                'middle-left', 'middle-center', 'middle-right',
            ];
            if ( in_array( $input['banner_position'], $valid_positions, true ) ) {
                $sanitized_input['banner_position'] = $input['banner_position'];
            } else {
                $sanitized_input['banner_position'] = $options['banner_position'] ?? 'bottom-full';
            }
        }

        return array_merge( (array) $options, $sanitized_input );
    }

    public static function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
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

    // --- Render Callbacks for Fields ---

    public static function render_enable_banner_field() {
        $options = get_option( OC_OPTION_NAME );
        $checked = isset( $options['enable_banner'] ) && $options['enable_banner'] == 1 ? 'checked' : '';
        echo '<label><input type="checkbox" id="oc_enable_banner" name="' . OC_OPTION_NAME . '[enable_banner]" value="1" ' . $checked . '>';
        echo ' ' . __( 'Activate the cookie consent banner on the frontend.', 'openconsent' ) . '</label>';
        echo '<p class="description">' . __( 'If unchecked, the banner and GTM integration will not be active.', 'openconsent' ) . '</p>';
    }

    public static function render_gtm_id_field() {
        $options = get_option( OC_OPTION_NAME );
        $value = $options['gtm_id'] ?? '';
        echo '<input type="text" id="oc_gtm_id" name="' . OC_OPTION_NAME . '[gtm_id]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="GTM-XXXXXXX">';
        echo '<p class="description">' . __( 'Enter your Google Tag Manager ID. Format: GTM- followed by 6-8 letters/numbers (e.g., GTM-TWZDPZ2N). Leave empty if not using GTM.', 'openconsent' ) . '</p>';
    }

    public static function render_banner_text_field() {
        $options = get_option( OC_OPTION_NAME );
        $default_text = sprintf(
            '<p>%s</p><p>%s <a href="YOUR_PRIVACY_POLICY_URL_HERE" target="_blank">%s</a>.</p>',
            __( 'This website uses cookies to enhance your experience. By clicking "Accept", you agree to our use of cookies.', 'openconsent' ),
            __( 'For more information, please see our', 'openconsent' ),
            __( 'Privacy Policy', 'openconsent' )
        );
        $content = $options['banner_text'] ?? $default_text;
        $editor_id = 'oc_banner_text_editor';
        $settings = array( 'textarea_name' => OC_OPTION_NAME . '[banner_text]', 'media_buttons' => false, 'textarea_rows' => 7, 'quicktags' => true, 'tinymce' => array( 'toolbar1' => 'bold,italic,underline,link,unlink', 'toolbar2' => '', 'plugins' => 'link') );
        wp_editor( $content, $editor_id, $settings );
        echo '<p class="description">' . __( 'Enter the message for the main cookie banner. Use the editor to add formatting and links (e.g., to your privacy policy).', 'openconsent' ) . '</p>';
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

    public static function render_minimized_text_field() { // New render method
        $options = get_option( OC_OPTION_NAME );
        $value = $options['minimized_text'] ?? __( 'Cookie Settings', 'openconsent' );
        echo '<input type="text" id="oc_minimized_text" name="' . OC_OPTION_NAME . '[minimized_text]" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . __( 'Text for the small tab/button to re-open the cookie banner.', 'openconsent' ) . '</p>';
    }

    public static function render_banner_position_field() {
        $options = get_option( OC_OPTION_NAME );
        $current_position = $options['banner_position'] ?? 'bottom-full';
        $positions = [
            'bottom-full'   => __( 'Bottom (Full Width)', 'openconsent' ), 'bottom-left'   => __( 'Bottom Left (Floating)', 'openconsent' ),
            'bottom-center' => __( 'Bottom Center (Floating)', 'openconsent' ), 'bottom-right'  => __( 'Bottom Right (Floating)', 'openconsent' ),
            'top-full'      => __( 'Top (Full Width)', 'openconsent' ), 'top-left'      => __( 'Top Left (Floating)', 'openconsent' ),
            'top-center'    => __( 'Top Center (Floating)', 'openconsent' ), 'top-right'     => __( 'Top Right (Floating)', 'openconsent' ),
            'middle-left'   => __( 'Middle Left (Floating)', 'openconsent' ), 'middle-center' => __( 'Middle Center (Modal)', 'openconsent' ),
            'middle-right'  => __( 'Middle Right (Floating)', 'openconsent' ),
        ];
        echo '<select id="oc_banner_position" name="' . OC_OPTION_NAME . '[banner_position]">';
        foreach ( $positions as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current_position, $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Select where the main cookie banner should appear.', 'openconsent' ) . '</p>';
    }

    private static function render_color_picker_field( $field_name, $default_color = '#000000' ) {
        $options = get_option( OC_OPTION_NAME );
        $value = $options[$field_name] ?? $default_color;
        echo '<input type="text" id="oc_' . esc_attr($field_name) . '" name="' . OC_OPTION_NAME . '[' . esc_attr( $field_name ) . ']" value="' . esc_attr( $value ) . '" class="oc-color-picker" data-default-color="' . esc_attr( $default_color ) . '">';
    }
    public static function render_banner_bg_color_field() { self::render_color_picker_field( 'banner_bg_color', '#222222' ); }
    public static function render_banner_text_color_field() { self::render_color_picker_field( 'banner_text_color', '#ffffff' ); }
    public static function render_link_color_field() {
        self::render_color_picker_field( 'link_color', '#00a0d2' );
         echo '<p class="description">' . __( 'Color for links within the banner message.', 'openconsent' ) . '</p>';
    }
    public static function render_button_bg_color_field() { self::render_color_picker_field( 'button_bg_color', '#0073aa' ); }
    public static function render_button_text_color_field() { self::render_color_picker_field( 'button_text_color', '#ffffff' ); }
}
?>