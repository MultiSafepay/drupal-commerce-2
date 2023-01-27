Drupal.behaviors.formUpdated = {
    attach: function attach(context)
    {
        var applePayName = drupalSettings.commerce_multisafepay_payments.applepay.name;
        var applePayBlock = document.querySelector('input[value="' + applePayName + '"]').parentElement;
        applePayBlock.style.display = 'none';

        try {
            if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
                applePayBlock.style.display = 'block';
            }
        } catch (error) {
            console.warn('MultiSafepay error when trying to initialize Apple Pay:', error);
        }
    }
};
