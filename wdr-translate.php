<?php
/**
 * Plugin Name:         Discount rules : Translation compatibility
 * Plugin URI:          https://www.flycart.org
 * Description:         Useful to translate dynamic strings. Supported translation plugins: WPML and Loco Translate.
 * Version:             1.0.0
 * Requires at least:   5.3
 * Requires PHP:        5.6
 * Author:              Flycart
 * Author URI:          https://www.flycart.org
 * Slug:                wdr-translate
 * Text Domain:         wdr-translate
 * Domain path:         /i18n/languages/
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Contributors:        Ilaiyaraja
 * WC requires at least: 4.3
 * WC tested up to:     8.0
 * Requires Plugins:    woo-discount-rules
 */

defined( "ABSPATH" ) or die();

/**
 * To set plugin is compatible for WC Custom Order Table (HPOS) feature.
 */
add_action('before_woocommerce_init', function() {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

if(!function_exists('wdr_v2_is_plugin_active')){
	function wdr_v2_is_plugin_active($plugin_file){
		$active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
		if (is_multisite()) {
			$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		}
		return in_array($plugin_file, $active_plugins) || array_key_exists($plugin_file, $active_plugins);

	}
}

if(function_exists('get_option') && get_option('advanced_woo_discount_rules_load_version') == 'v2' && wdr_v2_is_plugin_active('woo-discount-rules/woo-discount-rules.php')) {
	return;
}

/**
 * Check woocommerce plugin active or not.
 */
if ( ! function_exists( 'isWoocommerceActive' ) ) {
	function isWoocommerceActive() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins, false ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}
/**
 * Check Discount rules plugin active or not.
 */
if ( ! function_exists( 'isDiscountRulesActive' ) ) {
	function isDiscountRulesActive() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woo-discount-rules-pro/woo-discount-rules-pro.php', $active_plugins, false ) || in_array( 'woo-discount-rules/woo-discount-rules.php', $active_plugins, false );
	}
}
if ( ! isWoocommerceActive() || ! isDiscountRulesActive() ) {
	return;
}

if ( ! class_exists( '\WDR\Core\Helpers\Plugin' ) && file_exists( WP_PLUGIN_DIR . '/woo-discount-rules/vendor/autoload.php' ) ) {
	require_once WP_PLUGIN_DIR . '/woo-discount-rules/vendor/autoload.php';
} elseif ( file_exists( WP_PLUGIN_DIR . '/woo-discount-rules-pro/vendor/autoload.php' ) ) {
	require_once WP_PLUGIN_DIR . '/woo-discount-rules-pro/vendor/autoload.php';
}
if ( ! class_exists( '\WDR\Core\Helpers\Plugin' ) ) {
	return;
}

/**
 * Check discount rules plugin is latest.
 */
if ( ! function_exists( 'isWooDiscountLatestVersion' ) ) {
	function isWooDiscountLatestVersion() {
		$db_version = get_option( 'wdr_version', '' );
		if ( ! empty( $db_version ) ) {
			return ( version_compare( $db_version, '3.0.0', '>=' ) );
		}

		return false;
	}
}

if ( ! isWooDiscountLatestVersion() ) {
	return;
}

defined( 'WDRT_PLUGIN_NAME' ) or define( 'WDRT_PLUGIN_NAME', 'Translation compatibility' );
defined( 'WDRT_PLUGIN_VERSION' ) or define( 'WDRT_PLUGIN_VERSION', '1.0.0' );
defined( 'WDRT_PLUGIN_SLUG' ) or define( 'WDRT_PLUGIN_SLUG', 'wdr-translate' );
defined( 'WDRT_PLUGIN_FILE' ) || define( 'WDRT_PLUGIN_FILE', __FILE__ );
defined( 'WDRT_PLUGIN_URL' ) or define( 'WDRT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'WDRT_PLUGIN_PATH' ) or define( 'WDRT_PLUGIN_PATH', __DIR__ . '/' );

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}
require __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( \WDRT\App\Router::class ) ) {
	return;
}
$myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/flycartinc/woo_discount_translate',
	__FILE__,
	'wdr-translate'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

if (! method_exists(\WDRT\App\Router::class, 'init')) return;

register_activation_hook( WDRT_PLUGIN_FILE, 'WDRT\App\Controller\Main::activate' );
register_deactivation_hook( WDRT_PLUGIN_FILE, 'WDRT\App\Controller\Main::deactivate' );

\WDRT\App\Router::init();

