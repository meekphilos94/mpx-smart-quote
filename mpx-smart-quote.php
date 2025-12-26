<?php
/**
 * Plugin Name: MPX Global Shipping Calculator
 * Plugin URI: https://mpxglobal.co.zw
 * Author URI: https://meeknesstapera.co.zw
 * Description: AJAX-driven shipping price and instruction calculator for MPX Global.
 * Version: 1.0.2
 * Author: MPX Global
 * License: GPL v2 or later
 * Text Domain: mpx-smart-quote
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include main class
require_once plugin_dir_path(__FILE__) . 'vendor/tcpdf/tcpdf.php';

require_once plugin_dir_path(__FILE__) . 'class-mpx-calculator.php';

// Initialize the plugin
new MPX_Calculator();