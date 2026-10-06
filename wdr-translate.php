<?php
/**
 * Plugin Name:         Discount rules : Translation compatibility
 * Plugin URI:          https://www.flycart.org
 * Description:         Useful to translate dynamic strings. Supported translation plugins: WPML and Loco Translate.
 * Version:             1.1.0
 * Requires at least:   6.0
 * Requires PHP:        7.4
 * Author:              Flycart
 * Author URI:          https://www.flycart.org
 * Slug:                wdr-translate
 * Text Domain:         wdr-translate
 * Domain path:         /i18n/languages/
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Contributors:        Ilaiyaraja
 * WC requires at least: 7.0
 * WC tested up to:     10.2
 * Requires Plugins:    woocommerce
 */

defined( 'ABSPATH' ) || exit;

// Declare WooCommerce feature compatibility (HPOS).
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Plugin constants. Safe to define unconditionally - they don't depend on any other plugin
 * having loaded yet.
 */
defined( 'WDRT_PLUGIN_NAME' ) || define( 'WDRT_PLUGIN_NAME', 'Translation compatibility' );
defined( 'WDRT_PLUGIN_VERSION' ) || define( 'WDRT_PLUGIN_VERSION', '1.1.0' );
defined( 'WDRT_PLUGIN_SLUG' ) || define( 'WDRT_PLUGIN_SLUG', 'wdr-translate' );
defined( 'WDRT_PLUGIN_FILE' ) || define( 'WDRT_PLUGIN_FILE', __FILE__ );
defined( 'WDRT_PLUGIN_URL' ) || define( 'WDRT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'WDRT_PLUGIN_PATH' ) || define( 'WDRT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
defined( 'WDRT_PLUGIN_PREFIX' ) || define( 'WDRT_PLUGIN_PREFIX', 'wdrt_' );
defined( 'WDRT_REQUIRED_PHP_VERSION' ) || define( 'WDRT_REQUIRED_PHP_VERSION', '7.4.0' );
defined( 'WDRT_REQUIRED_WP_VERSION' ) || define( 'WDRT_REQUIRED_WP_VERSION', '6.0.0' );
defined( 'WDRT_REQUIRED_WC_VERSION' ) || define( 'WDRT_REQUIRED_WC_VERSION', '7.0.0' );

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}
require __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( \WDRT\App\Router::class ) || ! class_exists( \WDRT\App\Setup::class ) ) {
	return;
}

// Plugin updates: runs unconditionally so existing installs keep receiving updates even while
// WooCommerce / Discount Rules are inactive.
if ( class_exists( \YahnisElsts\PluginUpdateChecker\v5\PucFactory::class ) ) {
	$wdrt_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/flycartinc/woo_discount_translate',
		__FILE__,
		'wdr-translate'
	);
	$wdrt_update_checker->getVcsApi()->enableReleaseAssets();
}

// Registers activation/deactivation/uninstall hooks. Runs on every load (not gated behind
// dependency checks) so WordPress can reliably fire them even if WooCommerce/WDR aren't active yet.
\WDRT\App\Setup::init();

add_action( 'init', function () {
	if ( \WDRT\App\Helper\Plugin::checkDependencies() ) {
		\WDRT\App\Router::init();
	}
} );
