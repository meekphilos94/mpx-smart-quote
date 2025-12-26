<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MPX_Smart_Quote
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('mpx_air_rates');
delete_option('mpx_sea_rates');
delete_option('mpx_currency');
delete_option('mpx_uk_currency');
delete_option('mpx_whatsapp');
