var Drupal = Drupal || {};

var Payments = (function ($, Drupal, Bootstrap) {

    var self = {};

    var $form = null;

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function() {
        console.log('Payments constructed');

        // Initialize things
        $form = $('#layout-content').find('form');

        return self;
    };

    self.destruct = function() {
        console.log('Payments destructed');

        // Clean up and uninitialize things
        $(window).off('message');
        $form.off('submit');
    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.index = function() {
        console.log('Payments page initialized');

        $(window).on('message', onFrameMessage);
        $form.on('submit', onFormSubmit);

        // TODO : move to module
        $form.find('.btn-product-id').on('click', onProductBooking);

        $('form[target="results"]').on('submit', onResultsClick);
    };

    /** -----------------------------
     * Events
     --------------------------------- */

    var onResultsClick = function(ev) {
        var $form = $(this);

        window.open($form.attr('action'), $form.attr('target'), 'scrollbars=1,resizable=1,width=740,height=690');

        // Weird bug with chrome
        setTimeout(function() {
            $form.get(0).reset();
        }, 500);

        $form.find('button').removeClass('is-clicked is-loading');
    };

    // TODO : move to module
    var onProductBooking = function(ev) {
        console.log('onProductBooking');

        ev.preventDefault();

        var $form = $(this).closest('form');
        var $button = $form.find('.form-actions .btn[type="submit"]');

        $form.find('input[name="product_id"]').attr('disabled', true);

        $(this).parent().closest('.form-group').find('input[name="product_id"]').attr('disabled', false);

        // Trigger real submit button
        $button.trigger('click', [$(this)]);
    };

    var onFormSubmit = function(ev) {
        var $moneris = $form.find('#moneris_frame');
            $moneris.prevAll('.alert').remove();

        if ($moneris.length) {
            var contentWindow = $moneris.get(0).contentWindow;
                contentWindow.postMessage('', $moneris.attr('src').replace(/\?(.*)/, ''));

            ev.preventDefault();
        }
    };

    var onFrameMessage = function(ev) {
        console.log('onFrameMessage');

        var respData = JSON.parse(ev.originalEvent.data);

        if (respData.dataKey) {
            // Great success

            // Insert token into form
            $form.find('input[name="data_key"]').val(respData.dataKey);

            // Remove event handler
            $form.off('submit', onFormSubmit);

            // Re-submit form
            $form.submit();
        } else  {
            // Error
            var $alert = $('<div></div>');
                $alert.addClass('alert alert-danger');
                $alert.text(respData.errorMessage);

            $alert.insertBefore($form.find('#moneris_frame'));
        }
    };

    // Return class
    return self.construct();
});