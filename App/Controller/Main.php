<?php

namespace WDRT\App\Controller;

use WDR\Core\Helpers\Input;
use WDR\Core\Helpers\Util;
use WDR\Core\Helpers\WC;
use WDR\Core\Models\Custom\AdminRule;
use WDRT\App\Helper\Plugin;
use WDRT\App\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * All admin-facing behavior for this addon: the "Translate" Add-ons settings screen, and the
 * two translation-plugin integrations (Loco Translate string extraction, WPML String
 * Translation registration).
 */
class Main {

	/**
	 * Run plugin activation scripts. Kept for backward compatibility; lifecycle logic lives in Setup.
	 *
	 * @return bool
	 */
	public static function activate() {
		return Setup::activate();
	}

	/**
	 * Run plugin deactivation scripts. Kept for backward compatibility; lifecycle logic lives in Setup.
	 *
	 * @return bool
	 */
	public static function deactivate() {
		return Setup::deactivate();
	}

	/**
	 * Render this addon's settings screen on WDR's "Add-ons" admin page.
	 *
	 * @param string $addon Add-on slug requested via the `addon` query var (see
	 *                       `Core\Controllers\Admin\AddOn::addMenu()`).
	 *
	 * @return void
	 */
	public static function managePages( $addon = '' ) {
		if ( $addon !== 'translate' ) {
			return;
		}
		Util::renderTemplate( WDRT_PLUGIN_PATH . 'App/Views/Admin/main.php', [
			'is_wpml_translate_string_available' => Plugin::isWpmlStringTranslationActive(),
		] );
	}

	/**
	 * Enqueue this addon's settings-screen assets. Only loaded on the addon's own Add-ons page.
	 *
	 * @return void
	 */
	public static function enqueueAssets() {
		if ( ! WC::hasAdminPrivilege() ) {
			return;
		}
		if ( Input::get( 'page', '' ) != 'woo-discount-rules-addons' || Input::get( 'addon', '' ) != 'translate' ) {
			return;
		}

		wp_enqueue_style( WDRT_PLUGIN_SLUG . '-admin', WDRT_PLUGIN_URL . 'Assets/Admin/Css/wdrt-admin.css', [], WDRT_PLUGIN_VERSION );
		wp_enqueue_script( WDRT_PLUGIN_SLUG . '-admin', WDRT_PLUGIN_URL . 'Assets/Admin/Js/wdrt-admin.js', [ 'jquery' ], WDRT_PLUGIN_VERSION, true );

		wp_localize_script( WDRT_PLUGIN_SLUG . '-admin', 'wdrt_localize_data', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => WC::createNonce( 'wdrt_common_nonce' ),
			'i18n'     => [
				'updating' => __( 'Updating...', 'wdr-translate' ),
				'error'    => __( 'Something went wrong, please try again.', 'wdr-translate' ),
			],
		] );
	}

	/**
	 * Backward-compatible alias of enqueueAssets().
	 *
	 * @return void
	 */
	public static function adminScripts() {
		self::enqueueAssets();
	}

	/**
	 * Loco Translate integration: add Discount Rules' dynamic DB strings to Loco's string
	 * extraction whenever it scans this text domain.
	 *
	 * @param \Loco_gettext_Extraction $extraction Loco translate extraction object.
	 * @param string                   $domain Text domain being scanned.
	 *
	 * @return void
	 */
	public static function addCustomString( \Loco_gettext_Extraction $extraction, $domain ) {
		if ( ! Plugin::isLocoTranslateActive() ) {
			return;
		}
		$new_custom_strings = self::getDynamicStrings( $domain );
		if ( empty( $new_custom_strings ) ) {
			return;
		}
		foreach ( $new_custom_strings as $key ) {
			$extraction->addString( new \Loco_gettext_String( $key ), $domain );
		}
	}

	/**
	 * WPML String Translation integration: AJAX handler ("Update translation" button on this
	 * addon's settings screen) that registers every dynamic Discount Rules string with WPML.
	 *
	 * @return void
	 */
	public static function addWPMLCustomString() {
		$result = [ 'success' => false, 'data' => [] ];

		if ( ! Plugin::isWpmlStringTranslationActive() ) {
			$result['data']['message'] = __( 'WPML String Translation plugin is not activated.', 'wdr-translate' );
			wp_send_json( $result );
		}

		if ( ! WC::hasAdminPrivilege() || ! WC::verifyNonce( Input::get( 'wdrt_nonce', '' ), 'wdrt_common_nonce' ) ) {
			$result['data']['message'] = __( 'Security check validation failed.', 'wdr-translate' );
			wp_send_json( $result );
		}

		if ( ! has_action( 'wpml_register_single_string' ) ) {
			$result['data']['message'] = __( 'WPML translation action not found.', 'wdr-translate' );
			wp_send_json( $result );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wdrt_ is this addon's own public hook prefix
		$domains = apply_filters( 'wdrt_dynamic_string_domain', [ 'woo-discount-rules' ] );
		foreach ( $domains as $domain ) {
			$new_custom_strings = self::getDynamicStrings( $domain );
			foreach ( $new_custom_strings as $key ) {
				do_action( 'wpml_register_single_string', $domain, md5( $key ), $key );
			}
		}

		$result['success']          = true;
		$result['data']['message'] = __( 'Updated WPML translation strings successfully.', 'wdr-translate' );
		wp_send_json( $result );
	}

	/**
	 * Collect every dynamic (DB-stored) string that should be offered for translation for a
	 * given text domain.
	 *
	 * @param string $domain Text domain.
	 *
	 * @return array
	 */
	public static function getDynamicStrings( string $domain ): array {
		$new_custom_strings = [];
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wdrt_ is this addon's own public hook prefix
		$new_custom_strings = apply_filters( 'wdrt_dynamic_string_list', $new_custom_strings, $domain );

		if ( 'woo-discount-rules' === $domain ) {
			self::getRuleStrings( $new_custom_strings );
			self::getSettingsStrings( $new_custom_strings );
			self::getAddOnStrings( $new_custom_strings );
		}

		return array_values( array_unique( array_filter( $new_custom_strings ) ) );
	}

	/**
	 * Add the sibling Discount Rules add-ons' own display name/description strings.
	 *
	 * @param array $new_custom_strings Custom strings, by reference.
	 *
	 * @return void
	 */
	private static function getAddOnStrings( array &$new_custom_strings ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wdrt_ is this addon's own public hook prefix
		$allowed_strings = apply_filters( 'wdrt_addons_string_list', [
			'Woo Discount Translate', 'This add-on used to translate dynamic string for Woo Discount Rules and related add-on.',
			'Woo wholesale price compatibility', 'This add-on used to give compatibility of woocommerce wholesale prices by rymerawebco.',
			'Woo extra product compatibility', 'This add-on used to give compatibility of woocommerce extra product option by themehigh.',
			'OnSale Page', 'Create On-sale Page products using rule based show the on-sale product.',
			'Woo facebook product price compatibility', 'This add-on used to give compatibility of woocommerce facebook product prices by facebook.',
			'Woo country based price compatibility', 'This add-on used to give compatibility of woocommerce country based prices by oscargare.',
		] );
		foreach ( $allowed_strings as $string ) {
			$new_custom_strings[] = $string;
		}
	}

	/**
	 * Add Discount Rules' own customizable settings strings (`wdr_settings` option).
	 *
	 * @param array $new_custom_strings Custom strings, by reference.
	 *
	 * @return void
	 */
	private static function getSettingsStrings( array &$new_custom_strings ) {
		$options = get_option( 'wdr_settings' );
		if ( ! is_array( $options ) ) {
			return;
		}
		$allowed_strings = [
			'you_saved_text', 'table_title_column_name', 'table_discount_column_name', 'table_range_column_name',
			'free_shipping_title', 'discount_label_for_combined_discounts', 'applied_rule_message',
			'on_sale_badge_html', 'on_sale_badge_percentage_html',
		];
		foreach ( $allowed_strings as $key ) {
			if ( ! empty( $options[ $key ] ) ) {
				$new_custom_strings[] = $options[ $key ];
			}
		}
	}

	/**
	 * Add every discount rule's own translatable strings (title, description, labels,
	 * promotion messages) straight from the rules DB table.
	 *
	 * @param array $new_custom_strings Custom strings, by reference.
	 *
	 * @return void
	 */
	private static function getRuleStrings( array &$new_custom_strings ) {
		if ( ! class_exists( AdminRule::class ) ) {
			return;
		}
		$table_name = AdminRule::getTableName();
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table name is this plugin's own fixed constant, not user input
		$rules = $wpdb->get_results( "SELECT * FROM {$table_name}" );
		if ( empty( $rules ) ) {
			return;
		}

		foreach ( $rules as $rule ) {
			if ( ! is_object( $rule ) ) {
				continue;
			}
			if ( ! empty( $rule->title ) ) {
				$new_custom_strings[] = $rule->title;
			}
			if ( ! empty( $rule->description ) ) {
				$new_custom_strings[] = $rule->description;
			}

			$extra_data = ! empty( $rule->extra_data ) ? json_decode( $rule->extra_data ) : new \stdClass();
			if ( ! empty( $extra_data->discount_bar->badge_text ) ) {
				$new_custom_strings[] = $extra_data->discount_bar->badge_text;
			}

			$discount_data = ! empty( $rule->discount_data ) ? json_decode( $rule->discount_data ) : new \stdClass();
			if ( ! empty( $discount_data->cart_label ) ) {
				$new_custom_strings[] = $discount_data->cart_label;
			}
			if ( ! empty( $discount_data->ranges ) && is_array( $discount_data->ranges ) ) {
				foreach ( $discount_data->ranges as $range ) {
					if ( ! empty( $range->label ) ) {
						$new_custom_strings[] = $range->label;
					}
				}
			}

			if ( ! empty( $rule->conditions ) ) {
				$conditions = json_decode( $rule->conditions, true );
				self::getConditionStrings( (array) $conditions, $new_custom_strings );
			}
		}
	}

	/**
	 * Add a rule's condition-level translatable strings (coupon/subtotal/quantity promotion
	 * messages).
	 *
	 * @param array $conditions Rule conditions, decoded from the `conditions` column.
	 * @param array $new_custom_strings Custom strings, by reference.
	 *
	 * @return void
	 */
	private static function getConditionStrings( array $conditions, array &$new_custom_strings ) {
		if ( empty( $conditions ) ) {
			return;
		}
		$message_key_by_type = [
			'cart_coupon'          => 'custom_value',
			'cart_subtotal'        => 'subtotal_promotion_message',
			'cart_items_quantity'  => 'cart_quantity_promotion_message',
		];
		foreach ( $conditions as $condition ) {
			$type = $condition['type'] ?? '';
			if ( ! isset( $message_key_by_type[ $type ] ) || ! is_array( $condition['options'] ?? null ) ) {
				continue;
			}
			$message_key = $message_key_by_type[ $type ];
			if ( ! empty( $condition['options'][ $message_key ] ) ) {
				$new_custom_strings[] = $condition['options'][ $message_key ];
			}
		}
	}
}
