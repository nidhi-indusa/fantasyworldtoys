/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
        [
            'jquery',
            'underscore',
            'Magento_Ui/js/form/form',
            'ko',
            'Magento_Customer/js/model/customer',
            'Magento_Customer/js/model/address-list',
            'Magento_Checkout/js/model/address-converter',
            'Magento_Checkout/js/model/quote',
            'Magento_Checkout/js/action/create-shipping-address',
            'Magento_Checkout/js/action/select-shipping-address',
            'Magento_Checkout/js/model/shipping-rates-validator',
            'Magento_Checkout/js/model/shipping-address/form-popup-state',
            'Magento_Checkout/js/model/shipping-service',
            'Magento_Checkout/js/action/select-shipping-method',
            'Magento_Checkout/js/model/shipping-rate-registry',
            'Magento_Checkout/js/action/set-shipping-information',
            'Magento_Checkout/js/model/step-navigator',
            'Magento_Ui/js/modal/modal',
            'Magento_Checkout/js/model/checkout-data-resolver',
            'Magento_Checkout/js/checkout-data',
            'uiRegistry',
            'mage/translate',
            'Magento_Checkout/js/model/shipping-rate-service'
        ],
        function (
                $,
                _,
                Component,
                ko,
                customer,
                addressList,
                addressConverter,
                quote,
                createShippingAddress,
                selectShippingAddress,
                shippingRatesValidator,
                formPopUpState,
                shippingService,
                selectShippingMethodAction,
                rateRegistry,
                setShippingInformationAction,
                stepNavigator,
                modal,
                checkoutDataResolver,
                checkoutData,
                registry,
                $t
                ) {
            'use strict';
            var self = {};
            self.Selected = ko.observable();

            var popUp = null;

            var quoteItemData = window.checkoutConfig.quoteItemData;
            var show_hide_custom_blockConfig = window.checkoutConfig.show_hide_custom_block;
            var storeItemDataConfig = window.checkoutConfig.storeItemData;

            var jsonObject = JSON.stringify(window.checkoutConfig.storeItemData);
            var TESTObject = JSON.parse(jsonObject);

            var storeItemData = [];

            for (var x in TESTObject) {
                storeItemData.push(TESTObject[x]);
            }




            return Component.extend({
                defaults: {
                    template: 'MageVision_Blog16/shipping'
                },
                visible: ko.observable(!quote.isVirtual()),
                errorValidationMessage: ko.observable(false),
                isCustomerLoggedIn: customer.isLoggedIn,
                isFormPopUpVisible: formPopUpState.isVisible,
                isFormInline: addressList().length == 0,
                isNewAddressAdded: ko.observable(false),
                saveInAddressBook: 1,
                quoteIsVirtual: quote.isVirtual(),
                quoteItemData: quoteItemData,
                quoteIsHomedelivery: 0,
                /**
                 * @return {exports}
                 */

                spamFlavor: ko.observable('homedelivery'),
                currentProfit: ko.observable(10),
                myClass: ko.observable('hidden1'),
                title: ko.observable('Title'),
                storeItemData: storeItemData,
                selectedStore: ko.observable(),
                radioclick: function (data, event) {
                    if (event.target.defaultValue == "homedelivery") {
                        //alert("clicked homedelivery...");
                        // $("button[data-role='opc-continue']").show();

                        $(".table-checkout-shipping-method").addClass("hidden");
                        $(".storeswitcher").css("display", "none");
                        $(".table-checkout-shipping-method input").removeAttr("checked");
                        //$("#s_method_freeshipping_freeshipping").attr('checked', 'checked');
                        $("#s_method_flatrate").attr('checked', 'checked');
                        
                        $("input[name='delivery_from'").val("Warehouse");
                        $("input[name='newdeliverymethod'").val("homedelivery");
                        $("input[name='location_id'").val("0");
                        $("input[name='ax_store_id'").val("0");
                        
                        var clickcollectmethod = 0;
                    } else if (event.target.defaultValue == "clickandcollect") {

                        // alert("clicked collect...");

                        $("button[data-role='opc-continue']").hide();


                        //$(".googlestorelocator").removeClass("hidden");
                        $(".storeswitcher").css("display", "block");
                        $(".table-checkout-shipping-method").addClass("hidden");
                        $(".table-checkout-shipping-method input").removeAttr("checked");
                        //$("#s_method_freeshipping_freeshipping").attr('checked', 'checked');
                        $("#s_method_flatrate").attr('checked', 'checked');
                        var clickcollectmethod = 1;
                        
                    }
                    return true;
                },
                getHomeDelivery: function () {
                    var jsonObject = JSON.stringify(window.checkoutConfig.quoteItemData);
                    var JSONObject = JSON.parse(jsonObject);
                    var is_homedelivery = false;
                    
                   
                    
                    //Issue facing when bodyonload shipping method not selected start
                    $(".table-checkout-shipping-method").addClass("hidden");
                    $(".storeswitcher").css("display", "none");
                    $(".table-checkout-shipping-method input").removeAttr("checked");
                   // $("#s_method_freeshipping_freeshipping").attr('checked', 'checked');
                    $("#s_method_flatrate").attr('checked', 'checked');
                    //Issue facing when bodyonload shipping method not selected end

                    for (var prop in JSONObject) {
                        if (JSONObject[prop]['is_homedelivery'] == "Yes")
                        {
                            is_homedelivery = true;
                        }

                    }

                    return is_homedelivery;
                },
                initialize: function () {



                    var self = this,
                            hasNewAddress,
                            fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';

                    this._super();
                    shippingRatesValidator.initFields(fieldsetName);

                    if (!quote.isVirtual()) {
                        stepNavigator.registerStep(
                                'shipping',
                                '',
                                $t('Delivery Method'),
                                this.visible, _.bind(this.navigate, this),
                                10
                                );
                    }
                    checkoutDataResolver.resolveShippingAddress();

                    hasNewAddress = addressList.some(function (address) {
                        return address.getType() == 'new-customer-address';
                    });

                    this.isNewAddressAdded(hasNewAddress);

                    this.isFormPopUpVisible.subscribe(function (value) {
                        if (value) {
                            self.getPopUp().openModal();
                        }
                    });

                    quote.shippingMethod.subscribe(function () {
                        self.errorValidationMessage(false);
                    });

                    registry.async('checkoutProvider')(function (checkoutProvider) {
                        var shippingAddressData = checkoutData.getShippingAddressFromData();

                        if (shippingAddressData) {
                            checkoutProvider.set(
                                    'shippingAddress',
                                    $.extend({}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                                    );
                        }
                        checkoutProvider.on('shippingAddress', function (shippingAddressData) {
                            checkoutData.setShippingAddressFromData(shippingAddressData);
                        });
                    });

                    return this;
                },
                /**
                 * Load data from server for shipping step
                 */
                navigate: function () {
                    //load data from server for shipping step
                },
                /**
                 * @return {*}
                 */
                getPopUp: function () {
                    var self = this,
                            buttons;

                    if (!popUp) {
                        buttons = this.popUpForm.options.buttons;
                        this.popUpForm.options.buttons = [
                            {
                                text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                                class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                                click: self.saveNewAddress.bind(self)
                            },
                            {
                                text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                                class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',
                                click: function () {
                                    this.closeModal();
                                }
                            }
                        ];
                        this.popUpForm.options.closed = function () {
                            self.isFormPopUpVisible(false);
                        };
                        popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
                    }

                    return popUp;
                },
                /**
                 * Show address form popup
                 */
                showFormPopUp: function () {
                    this.isFormPopUpVisible(true);
                },
                /**
                 * Save new shipping address
                 */
                saveNewAddress: function () {
                    var addressData,
                            newShippingAddress;

                    this.source.set('params.invalid', false);
                    this.source.trigger('shippingAddress.data.validate');

                    if (!this.source.get('params.invalid')) {
                        addressData = this.source.get('shippingAddress');
                        // if user clicked the checkbox, its value is true or false. Need to convert.
                        addressData.save_in_address_book = this.saveInAddressBook ? 1 : 0;

                        // New address must be selected as a shipping address
                        newShippingAddress = createShippingAddress(addressData);
                        selectShippingAddress(newShippingAddress);
                        checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                        checkoutData.setNewCustomerShippingAddress(addressData);
                        this.getPopUp().closeModal();
                        this.isNewAddressAdded(true);
                    }
                },
                /**
                 * Shipping Method View
                 */
                rates: shippingService.getShippingRates(),
                isLoading: shippingService.isLoading,
                isSelected: ko.computed(function () {

                    return quote.shippingMethod() ?
                            quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code
                            : null;
                }
                ),
                /**
                 * @param {Object} shippingMethod
                 * @return {Boolean}
                 */


                selectShippingMethod: function (shippingMethod) {
                    selectShippingMethodAction(shippingMethod);
                    checkoutData.setSelectedShippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code);

                    return true;
                },
                /**
                 * Set shipping information handler
                 */
                setShippingInformation: function () {
                    if (this.validateShippingInformation()) {
                        setShippingInformationAction().done(
                                function () {
                                    stepNavigator.next();
                                }
                        );
                    }
                },
                /**
                 * @return {Boolean}
                 */
                validateShippingInformation: function () {
                    var shippingAddress,
                            addressData,
                            loginFormSelector = 'form[data-role=email-with-possible-login]',
                            emailValidationResult = customer.isLoggedIn();

                    if (!quote.shippingMethod()) {
                        this.errorValidationMessage('Please specify a shipping method.');

                        return false;
                    }

                    if (!customer.isLoggedIn()) {
                        $(loginFormSelector).validation();
                        emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                    }

                    if (this.isFormInline) {
                        this.source.set('params.invalid', false);
                        this.source.trigger('shippingAddress.data.validate');

                        if (this.source.get('shippingAddress.custom_attributes')) {
                            this.source.trigger('shippingAddress.custom_attributes.data.validate');
                        }

                        if (this.source.get('params.invalid') ||
                                !quote.shippingMethod().method_code ||
                                !quote.shippingMethod().carrier_code ||
                                !emailValidationResult
                                ) {
                            return false;
                        }

                        shippingAddress = quote.shippingAddress();
                        addressData = addressConverter.formAddressDataToQuoteAddress(
                                this.source.get('shippingAddress')
                                );

                        //Copy form data to quote shipping address object
                        for (var field in addressData) {

                            if (addressData.hasOwnProperty(field) &&
                                    shippingAddress.hasOwnProperty(field) &&
                                    typeof addressData[field] != 'function' &&
                                    _.isEqual(shippingAddress[field], addressData[field])
                                    ) {
                                shippingAddress[field] = addressData[field];
                            } else if (typeof addressData[field] != 'function' &&
                                    !_.isEqual(shippingAddress[field], addressData[field])) {
                                shippingAddress = addressData;
                                break;
                            }
                        }

                        if (customer.isLoggedIn()) {
                            shippingAddress.save_in_address_book = 1;
                        }
                        selectShippingAddress(shippingAddress);
                    }

                    if (!emailValidationResult) {
                        $(loginFormSelector + ' input[name=username]').focus();

                        return false;
                    }

                    return true;
                }
            });

            ///
            ko.bindingHandlers.iChecked = {
                init: function (element, valueAccessor) {
                    $(element).iCheck({
                        radioClass: "iradio_square-green",
                    });

                    $(element).on('ifChanged', function () {
                        var observable = valueAccessor();
                        observable($(element)[0].checked);
                    });
                },
                update: function (element, valueAccessor) {
                    var value = ko.unwrap(valueAccessor());
                    if (value) {
                        $(element).iCheck('check');
                    } else {
                        $(element).iCheck('uncheck');
                    }
                }
            };

            var ViewModel = function () {
                var self = this;
                self.fixedPrice = ko.observable(true);
                self.allowBiding = ko.observable(false);

            };
            ko.applyBindings(new ViewModel());
            ///



        }


);
