define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/set-billing-address',
        'mage/url'
    ],
    function ($, ko, quote, Component, setBillingAddress, urlBuilder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'CyberSource_BankTransfer/payment/cybersource-bank-transfer-sofort',
                code: 'cybersource_bank_transfer_sofort'
            },
            initialize: function () {
                this._super();
            },
            getCode: function () {
                return 'cybersource_bank_transfer_sofort';
            },
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },
            isActive: function () {
                return window.checkoutConfig.payment[this.getCode()].active;
            },
            continueCybersourceSofort: function () {
                setBillingAddress();
                var getVar = '';
                if (quote.guestEmail !== '' || quote.guestEmail !== undefined) {
                    getVar += '?guestEmail=' + quote.guestEmail;
                }
                var url = urlBuilder.build('cybersourcebt/index/pay');
                $.post(url  + getVar, {
                    bank: 'sofort'
                }, function (data) {
                    console.log(data.redirect_url)
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                }, 'json');
            }
        });
    }
);


