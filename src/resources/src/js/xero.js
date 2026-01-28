// ==========================================================================

// Xero Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.Xero === typeof undefined) {
    Craft.Xero = {};
}

(function($) {

Craft.Xero.CpSendOrderToXero = Garnish.Base.extend({
    orderId: null,

    init: function(orderId) {
        this.orderId = orderId;

        // Find the settings menubtn, and add a new option to it
        var $menubtn = $('.menubtn[data-icon="settings"]').data('menubtn');

        if ($menubtn) {
            var $newOption = $('<li><a data-action="send-to-xero">' + Craft.t('commerce-xero', 'Send to Xero') + '</a></li>');

            // Add the option to the menubtn
            $menubtn.menu.addOptions($newOption.children());

            // Add it to the DOM
            $newOption.prependTo($menubtn.menu.$container.children().first());

            // Hijack the event
            $menubtn.menu.on('optionselect', $.proxy(this, '_handleMenuBtn'));
        }
    },

    sendToXero() {
        const data = {
            orderId: this.orderId,
        };

        Craft.sendActionRequest('POST', 'commerce-xero/orders/send', { data })
            .then((response) => {
                Craft.cp.displayNotice(Craft.t('commerce-xero', 'Order sent to Xero.'));

                location.reload();
            })
            .catch(({response}) => {
                if (response && response.data && response.data.message) {
                    Craft.cp.displayError(response.data.message);
                } else {
                    Craft.cp.displayError();
                }
            });
    },

    _handleMenuBtn: function(ev) {
        var $option = $(ev.selectedOption);

        // Just action our option
        if ($option.data('action') == 'send-to-xero') {
            this.sendToXero();
        }
    },
});


})(jQuery);
