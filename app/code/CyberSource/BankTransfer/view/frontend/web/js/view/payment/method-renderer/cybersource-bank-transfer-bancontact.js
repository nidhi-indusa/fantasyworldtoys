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
                template: 'CyberSource_BankTransfer/payment/cybersource-bank-transfer-bancontact',
                code: 'cybersource_bank_transfer_bancontact'
            },
            initialize: function () {
                this._super();
            },
            getCode: function () {
                return 'cybersource_bank_transfer_bancontact';
            },
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },
            isActive: function () {
                return window.checkoutConfig.payment[this.getCode()].active;
            },
            continueCybersourceBancontact: function () {
                setBillingAddress();
                var getVar = '';
                if (quote.guestEmail !== '' || quote.guestEmail !== undefined) {
                    getVar += '?guestEmail=' + quote.guestEmail;
                }
                var url = urlBuilder.build('cybersourcebt/index/pay');
                $.post(url  + getVar, {
                    bank: 'bancontact'
                }, function (data) {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                }, 'json');
            }
        });
    }
);


