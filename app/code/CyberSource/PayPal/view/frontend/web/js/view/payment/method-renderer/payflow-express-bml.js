/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'CyberSource_PayPal/js/view/payment/method-renderer/paypal-express-abstract'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'CyberSource_PayPal/payment/payflow-express-bml'
        }
    });
});
