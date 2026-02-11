<?php
/**
 * Golden Ticket Uninstall
 *
 * Fired when the plugin is uninstalled. Removes all plugin options from the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'gt_allowed_pages' );
delete_option( 'gt_allowed_pages_action' );
delete_option( 'gt_access_mode' );
delete_option( 'gt_redirect_url' );
delete_option( 'gt_achievements_seen' );
delete_option( 'gt_show_onboarding' );
