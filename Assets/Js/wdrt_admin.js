if ( typeof wdrt_jquery === 'undefined' ) {
    wdrt_jquery = jQuery.noConflict();
}

var wdrt = window.wdrt || {};

(function (wdrt) {

    wdrt.showToast = function (type, message) {
        var $toast = wdrt_jquery(
            '<div class="wdrt-toast ' + type + '">'
            + '<div class="wdrt-toast-content">'
            + '<span class="wdrt-toast-msg"></span>'
            + '</div>'
            + '<button type="button" class="wdrt-toast-close dashicons dashicons-no-alt" aria-label="Close"></button>'
            + '</div>'
        );

        $toast.find('.wdrt-toast-msg').text(message);

        $toast.find('.wdrt-toast-close').on('click', function () {
            $toast.remove();
        });

        wdrt_jquery('#wdrt-notification').append($toast);

        setTimeout(function () {
            $toast.remove();
        }, 4000);
    };

    wdrt.updateWpmlStrings = function () {
        var $button = wdrt_jquery('#wdrt-update-wpml-string');
        var original_text = $button.text();

        $button.prop('disabled', true).text(wdrt_localize_data.i18n.updating);

        wdrt_jquery.ajax({
            type: 'POST',
            url: wdrt_localize_data.ajax_url,
            dataType: 'json',
            data: {
                action: 'wdrt_add_dynamic_string',
                wdrt_nonce: wdrt_localize_data.nonce
            },

            error: function () {
                wdrt.showToast('error', wdrt_localize_data.i18n.error);
            },

            success: function (json) {
                if (!json.success) {
                    wdrt.showToast(
                        'error',
                        json.data && json.data.message
                            ? json.data.message
                            : wdrt_localize_data.i18n.error
                    );

                    return;
                }

                wdrt.showToast('success', json.data.message);
            },

            complete: function () {
                $button.prop('disabled', false).text(original_text);
            }
        });
    };

    wdrt_jquery(document).on('click', '#wdrt-update-wpml-string', function () {
        wdrt.updateWpmlStrings();
    });

}(wdrt));
