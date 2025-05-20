<?php
/**
 * @author      Flycart
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.flycart.org
 * */
defined('ABSPATH') or die;
?>
<div id="wdrt-main">
    <div class="wdrt-main-header">
        <h1><?php echo WDRT_PLUGIN_NAME; ?> </h1>
        <div><b><?php echo "v" . WDRT_PLUGIN_VERSION; ?></b></div>
    </div>
    <div class="wdrt_content">
        <div class="wdrt-sections">
            <div class="title">
                <h3><?php _e('Loco Translate:', 'wdr-translate'); ?></h3>
            </div>
            <div class="content">
                <p><?php echo __('You can now translate the dynamic content like rules title, descriptions easily.', 'wdr-translate'); ?></p>
            </div>
        </div>
	    <?php if (isset($is_wpml_translate_string_available) && $is_wpml_translate_string_available): ?>
        <div class="wdrt-sections">
            <div class="title">
                <h3><?php _e('WPML Translate:', 'wdr-translate'); ?></h3>
            </div>
            <div class="content">
                <p><?php echo __('You can now translate the dynamic content like rules title, descriptions easily.', 'wdr-translate'); ?></p>
                    <div class="wdrt_button">
                        <a class="wdrt_wpml_button" id="wdrt_update_wpml_string"
                           style="background-color: #0f172a;padding: 8px 12px;color: #FFF;border-radius: 6px;"
                        ><?php _e('Update Dynamic String for WPML', 'wdr-translate'); ?></a>
                    </div>
            </div>
        </div>
	    <?php endif; ?>
    </div>
</div>