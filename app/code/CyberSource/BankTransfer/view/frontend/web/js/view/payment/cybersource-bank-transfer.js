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
        rendererList.push({
            type: 'cybersource_bank_transfer_ideal',
            component: 'CyberSource_BankTransfer/js/view/payment/method-renderer/cybersource-bank-transfer-ideal'
        },{
            type: 'cybersource_bank_transfer_sofort',
            component: 'CyberSource_BankTransfer/js/view/payment/method-renderer/cybersource-bank-transfer-sofort'
        },{
            type: 'cybersource_bank_transfer_bancontact',
            component: 'CyberSource_BankTransfer/js/view/payment/method-renderer/cybersource-bank-transfer-bancontact'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);


