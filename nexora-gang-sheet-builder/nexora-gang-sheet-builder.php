<?php
/**
 * Plugin Name: Nexora Gang Sheet Builder
 * Plugin URI: https://nexora.io/gang-sheet-builder
 * Description: Professional Direct-to-Film (DTF) Gang Sheet Builder for WordPress & WooCommerce. Includes interactive canvas, auto-nesting, DPI resolution checking, 300 DPI print-ready export, and dynamic pricing.
 * Version: 1.0.0
 * Author: jasmin
 * Author URI: https://nexora.io
 * Text Domain: nexora-gang-sheet-builder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define('NEXORA_VERSION', '1.0.0');
define('NEXORA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEXORA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEXORA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NEXORA_DB_VERSION', '1.0.0');

// Declare HPOS (High-Performance Order Storage) compatibility for WooCommerce
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

/**
 * The code that runs during plugin activation.
 */
function activate_nexora_gang_sheet_builder() {
    require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-activator.php';
    Nexora_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_nexora_gang_sheet_builder() {
    require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-deactivator.php';
    Nexora_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_nexora_gang_sheet_builder');
register_deactivation_hook(__FILE__, 'deactivate_nexora_gang_sheet_builder');

/**
 * Core plugin class autoloader / includes.
 */
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-db.php';
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-export.php';
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-api.php';
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-admin.php';
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-woocommerce.php';
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-shortcodes.php';
require_once NEXORA_PLUGIN_DIR . 'includes/class-nexora-core.php';

/**
 * Begins execution of the plugin.
 */
function run_nexora_gang_sheet_builder() {
    $plugin = new Nexora_Core();
    $plugin->run();
}
run_nexora_gang_sheet_builder();
