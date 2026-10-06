<?php

namespace WDRT\App;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin lifecycle: activation, deactivation, and uninstall.
 */
class Setup {

	/**
	 * Addon slug tracked in Woo Discount Rules' own `wdr_active_addons` option and used by the
	 * remote Add-ons catalog (`translate`).
	 *
	 * @var string
	 */
	const ADDON_SLUG = 'translate';

	/**
	 * Register plugin lifecycle hooks.
	 *
	 * Must run unconditionally on every load, not gated behind dependency checks, so
	 * WordPress can reliably fire the 'activate_{plugin}' / 'deactivate_{plugin}' hooks
	 * whenever this plugin itself gets (de)activated - even if WooCommerce/WDR aren't active
	 * yet at that moment.
	 *
	 * @return void
	 */
	public static function init() {
		register_activation_hook( WDRT_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( WDRT_PLUGIN_FILE, [ __CLASS__, 'deactivate' ] );
		register_uninstall_hook( WDRT_PLUGIN_FILE, [ __CLASS__, 'uninstall' ] );
	}

	/**
	 * Run plugin activation scripts: register this addon in WDR's own add-ons tracking option.
	 *
	 * @return bool
	 */
	public static function activate() {
		$active_addons = (array) get_option( 'wdr_active_addons', [] );
		if ( ! in_array( self::ADDON_SLUG, $active_addons, true ) ) {
			$active_addons[] = self::ADDON_SLUG;
		}

		return update_option( 'wdr_active_addons', $active_addons );
	}

	/**
	 * Run plugin deactivation scripts: unregister this addon from WDR's add-ons tracking
	 * option.
	 *
	 * @return bool
	 */
	public static function deactivate() {
		$active_addons = (array) get_option( 'wdr_active_addons', [] );
		$key           = array_search( self::ADDON_SLUG, $active_addons, true );
		if ( $key !== false ) {
			unset( $active_addons[ $key ] );
		}

		return update_option( 'wdr_active_addons', $active_addons );
	}

	/**
	 * Run plugin uninstall scripts.
	 *
	 * Just unregisters the addon - it has no options of its own to clean up.
	 *
	 * @return void
	 */
	public static function uninstall() {
		self::deactivate();
	}
}
