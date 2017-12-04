/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'chcybersource',
                component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/cybersource-method'
            }
        );
        return Component.extend({});
    }
);