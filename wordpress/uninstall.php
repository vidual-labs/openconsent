<?php
/**
 * Removes the plugin's settings when OpenConsent is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'openconsent_settings' );
