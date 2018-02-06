/**
 * Indusa Deliverymethod
 *
 * @category     Indusa_Deliverymethod
 * @package      Indusa_Deliverymethod
 * @author      Indusa_Deliverymethod Team
 * @copyright    Copyright (c) 2017 Indusa Deliverymethod (http://www.indusa.com/)
 * @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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

            
            var jsonObject = JSON.stringify(window.checkoutConfig.storeItemData);
            var CustomeTESTObject = JSON.parse(jsonObject);
            var customoptionsList = [];
            for (var x in CustomeTESTObject) {
                customoptionsList.push(CustomeTESTObject[x]);
            }
           
             console.log("customoptionsList");
             console.log(customoptionsList);
            


            return Component.extend({
                defaults: {
                    template: 'Indusa_Deliverymethod/shipping'
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
                error_message: ko.observable(false),
                delivery_error_message: ko.observable(''),
                goalId: ko.observable(),
                
                customoptions:customoptionsList,
                radioSelectedOptionValue: ko.observable("Al Rai"),
                radioclick: function (data, event) {
                    if (event.target.defaultValue == "homedelivery") {
                        //alert("clicked homedelivery...");
                        $(".message_error").css("display", "none");

                        $("button[data-role='opc-continue']").show();
                        $("button[data-role='opc-continue2']").hide();

                        $(".table-checkout-shipping-method").addClass("hidden");
                        $(".storeswitcher").css("display", "none");
                        $(".table-checkout-shipping-method input").removeAttr("checked");
                        //$("#s_method_freeshipping_freeshipping").attr('checked', 'checked');
                        $("#s_method_flatrate").attr('checked', 'checked');
                        
                        
                       
                        
                        $("input[name=delivery_from]").val("Warehouse");
                        $("input[name=newdeliverymethod]").val("homedelivery");
                        $("input[name=location_id]").val("0");
                        $("input[name=ax_store_id]").val("0");

                        $(".indusa_delivery_date").show();
                        $("input[name=delivery_date]").val("");
                        $("#delivery_date").show();
                        $("#delivery_comment").show();




                        var clickcollectmethod = 0;
                    } else if (event.target.defaultValue == "clickandcollect") {

                        // alert("clicked collect...");
                        $(".message_error").css("display", "none");

                        $("button[data-role='opc-continue']").hide();
                        $("button[data-role='opc-continue2']").show();


                        //$(".googlestorelocator").removeClass("hidden");
                        $(".storeswitcher").css("display", "block");
                        $(".table-checkout-shipping-method").addClass("hidden");
                        $(".table-checkout-shipping-method input").removeAttr("checked");
                        //$("#s_method_freeshipping_freeshipping").attr('checked', 'checked');
                        $("#s_method_flatrate").attr('checked', 'checked');

                        $(".indusa_delivery_date").hide();
                        $("#delivery_date").hide();
                        $("#delivery_comment").hide();

                        var clickcollectmethod = 1;

                    }
                    return true;
                },
                getHomeDelivery: function () {
                    var jsonObject = JSON.stringify(window.checkoutConfig.quoteItemData);
                    var JSONObject = JSON.parse(jsonObject);
                    var is_homedelivery = false;
                    jQuery(window).load(function () {
                    if($('*').hasClass('checkout-index-index')){
                        if($('select[name="city"]').has('option').length == 0)
                        { 
                           
                              $('select[name="city"]').append('<option value=""  >Please Select City.</option>');

                        } 
                    }
                    });

                    //Issue facing when bodyonload shipping method not selected start
                    $(".table-checkout-shipping-method").addClass("hidden");
                    $(".storeswitcher").css("display", "none");
                    $(".table-checkout-shipping-method input").removeAttr("checked");
                    //$("#s_method_freeshipping_freeshipping").attr('checked', 'checked');
                    $("#s_method_flatrate").attr('checked', 'checked');
                    //Issue facing when bodyonload shipping method not selected end

                    $(".message_error").css("display", "none");


                    for (var prop in JSONObject) {

                        if (JSONObject[prop]['is_homedelivery'] == $.mage.__("Yes"))
                        {
                            is_homedelivery = true;
                        }

                    }


                    return is_homedelivery;
                },
	        initialize: function () {
                    this._super();
                    var disabled = window.checkoutConfig.shipping.deliverydatemethod.disabled;
                    var noday = window.checkoutConfig.shipping.deliverydatemethod.noday;

                    var format = window.checkoutConfig.shipping.deliverydatemethod.format;

                    var maxOrders = window.checkoutConfig.shipping.deliverydatemethod.maxOrders;

                    var show_hide_canBeDelivered = window.checkoutConfig.shipping.deliverydatemethod.show_hide_canBeDelivered;

                   
                    format = 'dd-mm-yy';
                  
                    var disabledDay = disabled.split(",").map(function (item) {
                        return parseInt(item, 10);
                    });

                    ko.bindingHandlers.datepicker = {
                        init: function (element, valueAccessor, allBindingsAccessor) {
                            var $el = $(element);
                            //initialize datepicker      



                            if (noday) {

                                var options = {
                                    minDate: 0,
									maxDate: '+1M',
                                    dateFormat: format,
                                    maxOrders: maxOrders,
                                    /* fix buggy IE focus functionality */

                                    //clear button starts
                                    showButtonPanel: true,
                                    closeText: 'Clear', // Text to show for "close" button
                                    onClose: function (date) {
                                        $('.ui-datepicker-close').live('click', function () {
                                            $('[name="delivery_date"]').val('')
                                        });
                                    }
                                    //clear button ends


                                };

                                //working
                                $('.ui-datepicker-current').live('click', function () {
                                    $('#ui-datepicker-div').hide();
                                    $("#delivery_date").blur();
                                    $("#delivery_date").datepicker("hide");

                                });


                                $.datepicker._gotoToday = function () {
                                    $("#delivery_date").datepicker('setDate', new Date()).datepicker('hide').blur();
                                };

                                //added by suresh k for closing bug IE
                                $.datepicker._gotoToday = function (id) {
                                    var target = $(id);
                                    var inst = this._getInst(target[0]);
                                    if (this._get(inst, "gotoCurrent") && inst.currentDay) {
                                        inst.selectedDay = inst.currentDay;
                                        inst.drawMonth = inst.selectedMonth = inst.currentMonth;
                                        inst.drawYear = inst.selectedYear = inst.currentYear;
                                    } else {
                                        var date = new Date();
                                        inst.selectedDay = date.getDate();
                                        inst.drawMonth = inst.selectedMonth = date.getMonth();
                                        inst.drawYear = inst.selectedYear = date.getFullYear();
                                        // the below two lines are new
                                        this._setDateDatepicker(target, date);
                                        this._selectDate(id, this._getDateDatepicker(target));
                                    }
                                    this._notifyChange(inst);
                                    this._adjustDate(target);
                                };


                            } else {

                                var options = {
                                    minDate: 0,
									maxDate: '+1M',
                                    dateFormat: format,
                                    //hourMin: hourMin,
                                    // hourMax: hourMax,
                                    maxOrders: maxOrders,
                                    beforeShowDay: function (date) {
                                        var day = date.getDay();
                                        if (disabledDay.indexOf(day) > -1) {
                                            return [false];
                                        } else {
                                            return [true];
                                        }
                                    },
                                    //clear button starts
                                    showButtonPanel: true,
                                    closeText: 'Clear', // Text to show for "close" button
                                    onClose: function (date) {
                                        $('.ui-datepicker-close').live('click', function () {
                                            $('[name="delivery_date"]').val('')
                                        });
                                    }
                                    //clear button ends


                                };

                                //working
                                $('.ui-datepicker-current').live('click', function () {
                                    $('#ui-datepicker-div').hide();
                                    $("#delivery_date").blur();
                                    $("#delivery_date").datepicker("hide");

                                });


                                $.datepicker._gotoToday = function () {
                                    $("#delivery_date").datepicker('setDate', new Date()).datepicker('hide').blur();
                                    // this.fixFocusIE = true;
                                    //$(this).change().focus();
                                };

                                //added by suresh k for closing bug IE
                                $.datepicker._gotoToday = function (id) {
                                    var target = $(id);
                                    var inst = this._getInst(target[0]);
                                    if (this._get(inst, "gotoCurrent") && inst.currentDay) {
                                        inst.selectedDay = inst.currentDay;
                                        inst.drawMonth = inst.selectedMonth = inst.currentMonth;
                                        inst.drawYear = inst.selectedYear = inst.currentYear;
                                    } else {
                                        var date = new Date();
                                        inst.selectedDay = date.getDate();
                                        inst.drawMonth = inst.selectedMonth = date.getMonth();
                                        inst.drawYear = inst.selectedYear = date.getFullYear();
                                        // the below two lines are new
                                        this._setDateDatepicker(target, date);
                                        this._selectDate(id, this._getDateDatepicker(target));
                                    }
                                    this._notifyChange(inst);
                                    this._adjustDate(target);
                                };
                            }


                            $el.datepicker(options);

                            var writable = valueAccessor();
                            if (!ko.isObservable(writable)) {
                                var propWriters = allBindingsAccessor()._ko_property_writers;
                                if (propWriters && propWriters.datepicker) {
                                    writable = propWriters.datepicker;
                                } else {
                                    return;
                                }
                            }
                            writable($(element).datepicker("getDate"));
                        },
                        update: function (element, valueAccessor) {
                            var widget = $(element).data("DatePicker");
                            //when the view model is updated, update the widget
                            if (widget) {
                                var date = ko.utils.unwrapObservable(valueAccessor());
                                widget.date(date);
                            }
                        }
                    };



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
                checkmaxorder: function () {
                    var disabled = window.checkoutConfig.shipping.deliverydatemethod.disabled;
                    var noday = window.checkoutConfig.shipping.deliverydatemethod.noday;
                    var hourMin = parseInt(window.checkoutConfig.shipping.deliverydatemethod.hourMin);
                    var hourMax = parseInt(window.checkoutConfig.shipping.deliverydatemethod.hourMax);
                    var format = window.checkoutConfig.shipping.deliverydatemethod.format;
                    var maxOrders = window.checkoutConfig.shipping.deliverydatemethod.maxOrders;
                    var show_hide_canBeDelivered = window.checkoutConfig.shipping.deliverydatemethod.show_hide_canBeDelivered;
                    return true;
                },
                /**
                 * @return {Boolean}
                 */
                validateShippingInformation: function () {
                    var shippingAddress,
                            addressData,
                            loginFormSelector = 'form[data-role=email-with-possible-login]',
                            emailValidationResult = customer.isLoggedIn();

                    var format = window.checkoutConfig.shipping.deliverydatemethod.format;
                  

                   /* if (format != 'dd-mm-yy') {
                        this.errorValidationMessage('Delivery Method format is invalid.');
                        return false;
                    }*/

                    //If(HOMEDELIVERY)
                    //IF(DELIVERYDATE !=NULL)
                    
                    if ($("input[name=deliverymethod]").val() == 'homedelivery') {
                        if ($("input[name=delivery_date]").val() != '') {
                            var Delivereddateval = $("input[name=delivery_date]").val();
                            var show_hide_canBeDelivered = window.checkoutConfig.shipping.deliverydatemethod.show_hide_canBeDelivered;

                        }

                    }




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
