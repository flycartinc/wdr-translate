<?php

namespace WDRT\App\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Boot-time dependency checks for this addon.
 *
 * checkDependencies() does NOT require any specific translation plugin - the addon supports
 * either WPML String Translation or Loco Translate, and each feature gates itself on whichever
 * one it needs.
 */
class Plugin {

	/**
	 * Plugin file (relative to the plugins directory) used to detect WPML String Translation.
	 *
	 * @var string
	 */
	const WPML_STRING_TRANSLATION_PLUGIN_FILE = 'wpml-string-translation/plugin.php';

	/**
	 * Plugin file (relative to the plugins directory) used to detect Loco Translate.
	 *
	 * @var string
	 */
	const LOCO_TRANSLATE_PLUGIN_FILE = 'loco-translate/loco.php';

	/**
	 * Run all checks needed before this addon may run: PHP/WP/WooCommerce version floors,
	 * WooCommerce + Woo Discount Rules both active, and Woo Discount Rules actually running in
	 * v3 (Core) mode. Deliberately does not require any translation plugin - see class docblock.
	 *
	 * @param bool $allow_exit Die immediately on failure instead of showing an admin notice.
	 *
	 * @return bool
	 */
	public static function checkDependencies( bool $allow_exit = false ): bool {
		if ( ! self::isPHPCompatible() ) {
			/* translators: %1$s: plugin name, %2$s: minimum PHP version */
			$message = sprintf( __( '%1$s requires minimum PHP version %2$s', 'wdr-translate' ), WDRT_PLUGIN_NAME, WDRT_REQUIRED_PHP_VERSION );
			$allow_exit ? die( esc_html( $message ) ) : self::adminNotice( esc_html( $message ), 'error' );

			return false;
		}

		if ( ! self::isWordPressCompatible() ) {
			/* translators: %1$s: plugin name, %2$s: minimum WordPress version */
			$message = sprintf( __( '%1$s requires minimum WordPress version %2$s', 'wdr-translate' ), WDRT_PLUGIN_NAME, WDRT_REQUIRED_WP_VERSION );
			$allow_exit ? die( esc_html( $message ) ) : self::adminNotice( esc_html( $message ), 'error' );

			return false;
		}

		if ( ! self::isWooAndWdrActive() ) {
			/* translators: %s: plugin name */
			$message = sprintf( __( '%s requires WooCommerce and Woo Discount Rules to be installed and active.', 'wdr-translate' ), WDRT_PLUGIN_NAME );
			$allow_exit ? die( esc_html( $message ) ) : self::adminNotice( esc_html( $message ), 'error' );

			return false;
		}

		if ( ! self::isWooCompatible() ) {
			/* translators: %1$s: plugin name, %2$s: minimum WooCommerce version */
			$message = sprintf( __( '%1$s requires minimum WooCommerce version %2$s', 'wdr-translate' ), WDRT_PLUGIN_NAME, WDRT_REQUIRED_WC_VERSION );
			$allow_exit ? die( esc_html( $message ) ) : self::adminNotice( esc_html( $message ), 'error' );

			return false;
		}

		if ( ! self::isWdrV3Active() ) {
			/* translators: %s: plugin name */
			$message = sprintf( __( '%s requires Woo Discount Rules to be running in v3 mode.', 'wdr-translate' ), WDRT_PLUGIN_NAME );
			$allow_exit ? die( esc_html( $message ) ) : self::adminNotice( esc_html( $message ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Check if a plugin is active.
	 *
	 * @param string $plugin_path Plugin path relative to the plugins directory.
	 *
	 * @return bool
	 */
	public static function isActive( string $plugin_path ): bool {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( $plugin_path, $active_plugins, true ) || array_key_exists( $plugin_path, $active_plugins );
	}

	/**
	 * Whether WooCommerce and Woo Discount Rules (free or Pro) are both active.
	 *
	 * @return bool
	 */
	public static function isWooAndWdrActive(): bool {
		return self::isActive( 'woocommerce/woocommerce.php' )
			&& ( self::isActive( 'woo-discount-rules/woo-discount-rules.php' ) || self::isActive( 'woo-discount-rules-pro/woo-discount-rules-pro.php' ) );
	}

	/**
	 * Whether Woo Discount Rules is actually running in v3 (Core) mode.
	 *
	 * WDR_PLUGIN_VERSION alone isn't enough: a site can have v3.x installed but be
	 * runtime-switched back to embedded v2 mode via is_wdr_load_v2() (see
	 * woo-discount-rules/v2/migration.php). This addon only targets v3 Core's filters, so it
	 * must stay inactive whenever v2 mode is in effect.
	 *
	 * @return bool
	 */
	public static function isWdrV3Active(): bool {
		if ( function_exists( 'is_wdr_load_v2' ) && is_wdr_load_v2() ) {
			return false;
		}
		if ( ! defined( 'WDR_PLUGIN_VERSION' ) ) {
			return false;
		}

		// Strip any pre-release/build suffix (e.g. "3.0.0-RC2", "3.0.0-beta1", "3.0.0+build")
		// before comparing - version_compare() otherwise ranks a pre-release below its plain
		// release (3.0.0-RC2 < 3.0.0), which would wrongly reject every v3 RC/beta build.
		$version = preg_replace( '/[-+].*$/', '', WDR_PLUGIN_VERSION );
		if ( ! version_compare( $version, '3.0.0', '>=' ) ) {
			return false;
		}

		return class_exists( '\WDR\Core\Helpers\Plugin' );
	}

	/**
	 * Whether WPML String Translation is active. Used by the settings screen and WPML AJAX handler.
	 *
	 * @return bool
	 */
	public static function isWpmlStringTranslationActive(): bool {
		return self::isActive( self::WPML_STRING_TRANSLATION_PLUGIN_FILE );
	}

	/**
	 * Whether Loco Translate is active. Used by the Loco Translate integration.
	 *
	 * @return bool
	 */
	public static function isLocoTranslateActive(): bool {
		return self::isActive( self::LOCO_TRANSLATE_PLUGIN_FILE );
	}

	/**
	 * Check PHP version is compatible.
	 *
	 * @return bool
	 */
	protected static function isPHPCompatible(): bool {
		return version_compare( PHP_VERSION, WDRT_REQUIRED_PHP_VERSION, '>=' );
	}

	/**
	 * Check WordPress required version.
	 *
	 * @return bool
	 */
	protected static function isWordPressCompatible(): bool {
		return version_compare( get_bloginfo( 'version' ), WDRT_REQUIRED_WP_VERSION, '>=' );
	}

	/**
	 * Check WooCommerce is compatible.
	 *
	 * @return bool
	 */
	protected static function isWooCompatible(): bool {
		return version_compare( self::getWooVersion(), WDRT_REQUIRED_WC_VERSION, '>=' );
	}

	/**
	 * Get WooCommerce version.
	 *
	 * @return string
	 */
	protected static function getWooVersion(): string {
		if ( defined( 'WC_VERSION' ) ) {
			return WC_VERSION;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugins( '/woocommerce' );

		return $plugin_data['woocommerce.php']['Version'] ?? '1.0.0';
	}

	/**
	 * Display an admin notice.
	 *
	 * @param string $message Notice message.
	 * @param string $status Notice type: 'success', 'error', 'warning', or 'info'.
	 *
	 * @return void
	 */
	public static function adminNotice( string $message, string $status = 'success' ): void {
		add_action( 'admin_notices', function () use ( $message, $status ) {
			?>
            <div class="notice notice-<?php echo esc_attr( $status ); ?>">
                <p><?php echo wp_kses_post( $message ); ?></p>
            </div>
			<?php
		}, 1 );
	}
}
