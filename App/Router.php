<?php

namespace WDRT\App;

use WDR\Core\Helpers\Input;
use WDRT\App\Controller\Main;

defined( 'ABSPATH' ) || exit;

class Router {

	/**
	 * Register plugin hooks. Everything this addon does is admin-only.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! is_admin() ) {
			return;
		}

		// Loco Translate: add Discount Rules' dynamic strings when Loco scans a text domain.
		// Main::addCustomString() checks Loco Translate is active itself.
		add_filter( 'loco_extracted_template', [ Main::class, 'addCustomString' ], 10, 2 );

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
