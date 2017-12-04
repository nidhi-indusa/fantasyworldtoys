define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CyberSource_SecureAcceptance/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Customer/js/model/customer',
        'mage/url',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/validation'
    ],
    function (
        $,
        Component,
        setPaymentMethodAction,
        additionalValidators,
        quote,
        customerData,
        setBillingAddress,
        customer,
        urlBuilder,
        ccValidator,
        Validation
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CyberSource_SecureAcceptance/payment/cybersource-form',
                selectedCardType: null,
                creditCardType: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'selectedCardType',
                        'creditCardType'
                    ]);
                return this;
            },
            getCode: function () {
                return 'chcybersource';
            },
            placeOrderHandler: null,
            validateHandler: null,
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },
            context: function () {
                return this;
            },
            isShowLegend: function () {
                return true;
            },
            isVaultEnabled: function () {
                return false;
            },
            isActive: function () {
                return window.checkoutConfig.payment['chcybersource'].active;
            },
            hasVerification: function () {
                return window.checkoutConfig.payment.ccform.hasVerification[this.getCode()];
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_type': this.creditCardType()
                    }
                };
            },
            getCcAvailableTypes: function () {
                return window.checkoutConfig.payment.ccform.availableTypes['cybersourcepaypal'];
            },
            getCcAvailableTypesValues: function () {
                return _.map(this.getCcAvailableTypes(), function (value, key) {
                    return {
                        'value': key,
                        'type': value
                    };
                });
            },
            getCcTypeTitleByCode: function (code) {
                var title = '';
                var keyValue = 'value';
                var keyType = 'type';

                _.each(this.getCcAvailableTypesValues(), function (value) {
                    if (value[keyValue] === code) {
                        title = value[keyType];
                    }
                });

                return title;
            },
            isSilentPost: function () {
                if (!customer.isLoggedIn()) {
                    $("#cybersource_credit_card_form").show();
                }
                return window.checkoutConfig.payment['chcybersource'].isSilent;
            },
            /* Validation Form*/
            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },
            continueCybersource: function () {
                var me = this;
                if (this.isSilentPost()) {
//                    setBillingAddress().done(function() {
                        me.continueSop();
//                        this.continueSop();
//                    });
                } else {
                    this.continueCybersourceSecureAcceptant();
                }
            },
            continueSop: function() {
                if (additionalValidators.validate()) {
                    if ($("#cybersource_credit_card_form").is(":visible")) {
                        if (!customer.isLoggedIn() && !this.validateForm('#cybersource-silent-form-validate')) {
                            return;
                        } else if (!$('#cybersource-token-selected').attr('checked') && !this.validateForm('#cybersource-silent-form-validate')) { //logged in customer
                            return;
                        }
                    }

                    this.selectPaymentMethod();
                    //update payment method information if additional data was changed
                    setPaymentMethodAction(this.messageContainer).done(
                        function () {
                            var getVar = '?x=1';
                            var isTokenPay = false;
                            if (quote.guestEmail != '' || quote.guestEmail != undefined) {
                                getVar += '&quoteEmail=' + quote.guestEmail;
                            }
                            if ($('#cybersource-token-selected') && $('#cybersource-token-selected').attr('checked')) {
                                isTokenPay = true;
                                getVar += '&isTokenPay=1&token=' + $('#cybersource-tokens-list').val();
                            } else {
                                getVar += '&isTokenPay=0';
                            }
                            setBillingAddress().done(function() {
                                $.ajax({
                                    url: urlBuilder.build("cybersource/index/loadsilentdata") + getVar,
                                    type: "get",
                                    dataType: 'json',
                                    showLoader: true,
                                    success: function (data) {
                                        var form = $('#cybersource-silent-form');
                                        form.attr('action', data.action_url);
                                        for (var name in data.form_data) {
                                            form.append('<input type="hidden" name="' + name + '" value="' + data.form_data[name] + '" />');
                                        }
                                        if (!isTokenPay) {
                                            form.append('<input type="hidden" name="card_type" value="' + $('#card_type').val() + '" />');
                                            form.append('<input type="hidden" name="card_number" value="' + $('#card_number').val() + '" />');
                                            form.append('<input type="hidden" name="card_expiry_date" value="' + $('#cybersource_exp_month').val() + '-' + $('#cybersource_exp_year').val() + '" />');
                                        }
                                        form.submit();
                                    }
                                });
                            });
                        }
                    )
                }
            },
            continueCybersourceSecureAcceptant: function () {
                var me = this;
                var paymentToken = $('#cybersource-tokens-list').val();
                var tokenSelected = $('#cybersource-token-selected').is(':checked');
                if (tokenSelected == false) {
                    paymentToken = '';
                }
                setBillingAddress().done(function(){
                    if (additionalValidators.validate()) {
                        me.selectPaymentMethod();
                        //update payment method information if additional data was changed
                        setPaymentMethodAction(this.messageContainer).done(
                            function () {
                                customerData.invalidate(['cart']);
                                $.ajax({
                                    url: urlBuilder.build("cybersource/index/loadinfo"),
                                    type: "post",
                                    dataType: 'json',
                                    data: {
                                        token: paymentToken,
                                        quoteEmail: quote.guestEmail,
                                        checkIframe: 1
                                    },
                                    success: function (data) {
                                        if (data.use_iframe) {
                                            me.popupIframe(paymentToken);
                                        } else {
                                            var form = $(document.createElement('form'));
                                            $(form).attr("action", data['request_url']);
                                            $(form).attr("method", "POST");
                                            for (var k in data) {
                                                $(form).append('<input type="hidden" name="'+k+'" value="'+data[k]+'"/>');
                                            }
                                            $("body").append(form);
                                            $(form).submit();
                                        }
                                    }
                                });
                            }
                        );
                        return false;
                    }
                });
            },
            popupIframe: function (paymentToken) {
                var tokenGetVar = '?token=' + paymentToken;
                if (paymentToken == '' || paymentToken == undefined) {
                    tokenGetVar = '';
                }
                var guestGetVar = '';
                if (quote.guestEmail != '' || quote.guestEmail != undefined) {
                    if (tokenGetVar == '') {
                        guestGetVar = '?quoteEmail=' + quote.guestEmail;
                    } else {
                        guestGetVar = '&quoteEmail=' + quote.guestEmail;
                    }
                }
                var iframeSrc = urlBuilder.build('cybersource/index/loadiframe');
                var myHeight = (document.documentElement.clientHeight / 2) - 325;
                var modalWidth = ($(window).width() < 500) ? $(window).width() : 500;
                var loading = '<div id="cybersource-modal-content" style="width: ' + modalWidth + 'px;padding: 0;position: fixed;z-index: 9999;margin: 0 auto;left: 0;right: 0;top: '+myHeight+'px;">' +
                    '<div id="cybersource-modal-modal-child" style="position: relative; background-color: #fff;">' +
                    '<div style="width:100%;text-align:right;">' +
                    '<a href="" id="cybersource-close-iframe"></a>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                if (document.getElementById('cybersource-view-detail-processing-modal')) {
                    $('#cybersource-view-detail-processing-modal').remove();
                }
                if (!$('.loading-mask')) {
                    $('body').append('<div class="loading-mask"></div>');
                }
                if (!document.getElementById('cybersource-view-detail-processing-modal')) {
                    var overlay = $(document.createElement('div'));
                    $(overlay).attr('id', 'cybersource-view-detail-processing-modal');
                    overlay.append(loading);
                    $('body').append(overlay);
                    $.get(iframeSrc + tokenGetVar + guestGetVar, function ( html ) {
                        var iframe = document.createElement('iframe');
                        var modal = document.getElementById("cybersource-modal-modal-child");
                        iframe.onload= function () {
                            console.log("onload");
                            try {
                                var form = iframe.contentWindow.document.getElementById('cybersource-iframe-form');
                                $(form).submit();
                            } catch (err) {
                                console.log(err.message);
                            }
                        };
                        modal.appendChild(iframe);
                        iframe.contentWindow.document.open();
                        iframe.contentWindow.document.write(html);
                        iframe.contentWindow.document.close();
                        var el = $(iframe);
                        el.attr('id', "cybersource-frame");
                        el.attr('sandbox', "allow-top-navigation allow-scripts allow-forms");
                        el.css("width", "100%");
                        el.css("height", "650px");
                        el.css("border", "#ccc 1px solid");
                    });
                    $('#cybersource-close-iframe').click(function () {
                        $('#cybersource-view-detail-processing-modal').remove();
                        $('.loading-mask').hide();
                        return false;
                    });
                }
            },
            getCcTypes: function () {
                var cybersource_cc_codes = {
                    VI: '001',
                    MC: '002',
                    AE: '003',
                    DI: '004'
                };
                var html = '<label class="label">Credit Card Type</label><br />';
                html += '<select name="card_type" id="card_type" data-validate="{\'required-entry\':true}">';
                for (var code in window.checkoutConfig.payment.ccform.availableTypes['chcybersource']) {
                    html += '<option value=' + cybersource_cc_codes[code] + '>' + window.checkoutConfig.payment.ccform.availableTypes['chcybersource'][code] + '</option>';
                }
                html += '</select><br />';
                return html;
            },
            getCcNumber: function () {
                var html = '<label class="label">Card Number</label><br />';
                html += '<input type="text" name="card_number" id="card_number" data-validate="{\'required-entry\':true, \'validate-cc-number\':true, \'validate-number\':true}"/><br />';
                return html;
            },
            getExpiryInput: function () {
                var html = '<label class="label">Expiry Month</label><br />';
                html += '<select name="payment[\'cc_exp_month\']" id="cybersource_exp_month" data-validate="{\'required-entry\':true}">';
                for (var i=0; i < 12; i++) {
                    var month = (i < 9) ? '0' + (i+1) : (i+1);
                    html += '<option value=' + month + '>' + month + '</option>';
                }
                html += '</select><br />';
                html += '<label class="label">Expiry Year</label><br />';
                html += '<select name="payment[\'cc_exp_year\']" id="cybersource_exp_year" data-validate="{\'required-entry\':true}">';
                var d = new Date();
                for (var i=0; i < 12; i++) {
                    html += '<option value=' + (d.getFullYear()+i) + '>' + (d.getFullYear()+i) + '</option>';
                }
                html += '</select><br />';
                return html;
            },
            getCcCvv: function () {
                var html = '<label class="label" >CVV</label><br />';
                html += '<input type="text" name="card_cvn" id="card_svn" data-validate="{\'required-entry\':true, \'validate-number\':true}"/>';
                return html;
            },
            getTokens: function () {
                var self = this;
                if (customer.isLoggedIn()) {
                    var urlTokens = urlBuilder.build('cybersource/index/gettokens');
                    var urlCards = urlBuilder.build('cybersource/manage/card');

                    $.ajax({
                        url: urlTokens,
                        type: "post",
                        dataType: 'json',
                        success: function (data) {

                            var tokenListHtml = '';
                            if (Object.keys(data).length > 0) {
                                tokenListHtml = '<div><label class="label"><input checked="checked" id="cybersource-token-selected" type="radio" name="token_type" />';
                                tokenListHtml += '<span>'+$.mage.__('Select predefined credit card')+ ': </span><select style="width: auto;" id="cybersource-tokens-list">';
                                for (var k in data) {
                                    tokenListHtml += '<option value="'+k+'">'+data[k]+'</option>';
                                }
                                tokenListHtml += '</select></label></div>';
                                tokenListHtml += '<br/><div><label class="label"><input id="cybersource-token-new" type="radio" name="token_type"/>'+$.mage.__('New credit card')+'</label></div><br>';
                                $('.payment-method-token-list').html(tokenListHtml);
                                $("#cybersource-token-new").click(function () {
                                    $("#cybersource_credit_card_form").show();
                                });

                                $("#cybersource-token-selected").click(function () {
                                    $("#cybersource_credit_card_form").hide();
                                });
                            } else {
                                tokenListHtml = '<div><label class="label"><input disabled id="cybersource-token-selected" type="radio" name="token_type" />';
                                tokenListHtml += $.mage.__("You have no predefined payment methods. You can define a new one ")+'<a href="' + urlCards + '">'+$.mage.__('here')+'</a>.';
                                tokenListHtml += '</label></div>';
                                tokenListHtml += '<br/><div><label class="label"><input checked="checked" id="cybersource-token-new" type="radio" name="token_type" />'+$.mage.__('Use new token')+'</label></div>';
                                $('.payment-method-token-list').html(tokenListHtml);
                                $("#cybersource_credit_card_form").show();
                            }
                            $('.cybersource-button-action').removeClass('disabled');
                        }
                    });

                    return 'Payment token loading...';
                } else {
                    return '';
                }
            },
            isPlaceOrderActionAllowed: function () {
                return !customer.isLoggedIn();
            }
        });
    }
);

