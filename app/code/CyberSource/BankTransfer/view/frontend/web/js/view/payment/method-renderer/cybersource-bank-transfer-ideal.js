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
                template: 'CyberSource_BankTransfer/payment/cybersource-bank-transfer-ideal',
                code: 'cybersource_bank_transfer_ideal',
                bankList: ko.observableArray()
            },
            initialize: function () {
                this._super();
                this.observe(['bankList']);
                var me = this;
                var url = urlBuilder.build('cybersourcebt/index/info');
                $.getJSON(url, function (data) {
                    var listArray = [];
                    var i = 0;
                    for (var bankCode in data) {
                        listArray[i] = {
                            code: bankCode,
                            name: data[bankCode]
                        };
                        i++;
                    }
                    me.bankList(listArray);
                });
            },
            getCode: function () {
                return 'cybersource_bank_transfer_ideal';
            },
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },
            isActive: function () {
                return window.checkoutConfig.payment[this.getCode()].active;
            },
            continueCybersourceIdeal: function () {
                setBillingAddress();
                var getVar = '';
                if (quote.guestEmail !== '' || quote.guestEmail !== undefined) {
                    getVar += '?guestEmail=' + quote.guestEmail;
                }
                var url = urlBuilder.build('cybersourcebt/index/pay');
                $.post(url + getVar, {
                    bank: $('#bank-select').val()
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


