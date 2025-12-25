<?php
/**
 * Plugin Name: MPX Global Shipping Calculator
 * Plugin URI: https://mpxglobal.com
 * Description: AJAX-driven shipping price and instruction calculator for MPX Global.
 * Version: 1.0.0
 * Author: MPX Global
 * License: GPL v2 or later
 * Text Domain: mpx-calculator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include main class
require_once plugin_dir_path(__FILE__) . 'vendor/tcpdf/tcpdf.php';
require_once plugin_dir_path(__FILE__) . 'vendor/php-qrcode/src/QRCode.php';
require_once plugin_dir_path(__FILE__) . 'class-mpx-calculator.php';

// Initialize the plugin
new MPX_Calculator();