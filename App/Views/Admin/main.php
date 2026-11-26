<?php
/**
 * @author Flycart
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 * @link https://www.flycart.org
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wdrt-notification" id="wdrt-notification"></div>
<div id="wdrt-main">
    <div class="wdrt-main-header">
        <h1><?php echo esc_html( WDRT_PLUGIN_NAME ); ?></h1>
        <div><b><?php echo esc_html( 'v' . WDRT_PLUGIN_VERSION ); ?></b></div>
    </div>
    <div class="wdrt-content">
        <div class="wdrt-section">
            <div class="wdrt-section-title">
                <h3><?php esc_html_e( 'Loco Translate', 'wdr-translate' ); ?></h3>
            </div>
            <div class="wdrt-section-body">
                <p><?php esc_html_e( 'Discount Rules\' dynamic content - rule titles, descriptions, coupon labels, promotion messages and the texts of the settings - is added to Discount Rules\' template whenever Loco Translate scans the plugin: Loco Translate > Plugins > Discount Rules for WooCommerce > Edit template > Sync > Save, then Sync your language.', 'wdr-translate' ); ?></p>
            </div>
        </div>
        <div class="wdrt-section">
            <div class="wdrt-section-title">
                <h3><?php esc_html_e( 'WPML String Translation', 'wdr-translate' ); ?></h3>
            </div>
            <div class="wdrt-section-body">
                <?php if ( ! empty( $is_wpml_translate_string_available ) ) : ?>
                    <p><?php esc_html_e( 'Discount Rules\' dynamic content is registered with WPML String Translation when a rule or the settings are saved. Register everything again with the button - after an import, a migration, or when String Translation was switched on later.', 'wdr-translate' ); ?></p>
                    <div class="wdrt-button-row">
                        <button type="button" class="button button-primary" id="wdrt-update-wpml-string">
                            <?php esc_html_e( 'Update Dynamic Strings for WPML', 'wdr-translate' ); ?>
                        </button>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e( 'WPML String Translation is not active. Activate it to register Discount Rules\' dynamic content for translation.', 'wdr-translate' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
