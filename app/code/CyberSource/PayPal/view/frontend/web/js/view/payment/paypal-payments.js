/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    var isContextCheckout = window.checkoutConfig.payment.cybersourcepaypal.isContextCheckout,
        paypalExpress = 'CyberSource_PayPal/js/view/payment/method-renderer' +
            (isContextCheckout ? '/in-context/checkout-express' : '/paypal-express');

    rendererList.push(
        {
            type: 'cybersourcepaypal',
            component: paypalExpress,
            config: window.checkoutConfig.payment.cybersourcepaypal.inContextConfig
        }
    );

    /**
     * Add view logic here if needed
     **/
    return Component.extend({});
});
