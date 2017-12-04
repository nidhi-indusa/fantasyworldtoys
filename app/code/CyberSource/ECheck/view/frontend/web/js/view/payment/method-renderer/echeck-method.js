/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'mage/translate',
        'jquery'
    ],
    function (Component, $t, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CyberSource_ECheck/payment/form',
                code: 'cybersourceecheck',
                active: false,
                checkBankTransitNumber: '',
                checkAccountNumber: ''
            },

            initObservable: function () {

                this._super().observe([
                    'active',
                    'checkBankTransitNumber',
                    'checkAccountNumber'
                ]);
                return this;
            },

            getCode: function () {
                return this.code;
            },

            getTitle: function () {
            
              return window.checkoutConfig.payment[this.getCode()].title;
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                var active = (this.getCode() === this.isChecked());

                this.active(active);

                return active;
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'check_bank_transit_number': this.checkBankTransitNumber(),
                        'check_account_number': this.checkAccountNumber()
                    }
                };
            },

            /**
             * Get image url for CVV
             * @returns {String}
             */
            getECheckImageUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].echeckImage;
            },

            /**
             * Get Echeck image
             * @returns {String}
             */
            getECheckImageHtml: function () {
                return '<img src="' + this.getECheckImageUrl() +
                    '" alt="' + $t('Check Visual Reference') +
                    '" title="' + $t('Check Visual Reference') +
                    '" />';
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);