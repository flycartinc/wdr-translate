<?php

namespace WDRT\App;

use WDR\Core\Helpers\Input;
use WDRT\App\Controller\Main;

defined( 'ABSPATH' ) || exit;

class Router {

	/**
	 * Register plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Loco Translate: add Discount Rules' dynamic strings when Loco scans a text domain - in wp-admin and from the command
		// line (`wp loco extract` / `wp loco sync`). Main::addCustomString() checks Loco Translate is active itself.
		add_filter( 'loco_extracted_template', [ Main::class, 'addCustomString' ], 10, 2 );

		// WPML String Translation: a rule's / the settings' strings are registered when they are saved, wherever that happens
		// (the rule editor, a duplicate, the settings screen, WP-CLI). The button below registers everything again.
		// The texts a rule had before its save: those no rule or setting uses afterwards are taken out of WPML again.
		add_filter( 'wdr_before_rule_save', [ Main::class, 'rememberRuleStrings' ], 10, 2 );
		add_action( 'wdr_after_rule_save', [ Main::class, 'registerRuleStrings' ] );
		add_action( 'wdr_after_rule_duplicate', [ Main::class, 'registerRuleStrings' ] );
		add_action( 'add_option_wdr_settings', [ Main::class, 'registerSettingsStrings' ], 10, 2 );
		add_action( 'update_option_wdr_settings', [ Main::class, 'registerSettingsStrings' ], 10, 2 );

		if ( ! is_admin() ) {
			return;
		}

		// WPML String Translation: AJAX action behind the "Update Dynamic Strings" button.
		// AJAX requests carry no page/addon query vars, so this is not gated by the check below.
		add_action( 'wp_ajax_wdrt_add_dynamic_string', [ Main::class, 'addWPMLCustomString' ] );

		// The settings screen and its assets are only needed on this addon's own Add-ons page.
		if ( Input::get( 'page', '' ) != 'woo-discount-rules-addons' || Input::get( 'addon', '' ) != 'translate' ) {
			return;
		}

		add_action( 'wdr_addons_page', [ Main::class, 'managePages' ] );
		add_action( 'admin_enqueue_scripts', [ Main::class, 'enqueueAssets' ] );
	}
}
