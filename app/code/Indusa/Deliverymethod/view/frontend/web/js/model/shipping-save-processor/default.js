/*global define,alert*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address'
    ],
    function (
        $,
        ko,
        quote,
        resourceUrlManager,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction
    ) {
        'use strict';
         
        return {
            
            saveShippingInformation: function () {
                var payload;
                 quoteIsHomedelivery: 0;
               
                if (!quote.billingAddress()) {
                    selectBillingAddressAction(quote.shippingAddress());
                }

                payload = {
                    
                    addressInformation: {
                        shipping_address: quote.shippingAddress(),
                        billing_address: quote.billingAddress(),
                        shipping_method_code: quote.shippingMethod().method_code,
                        shipping_carrier_code: quote.shippingMethod().carrier_code,
                       
                        extension_attributes:{
                          
                            ax_store_id: $('[name="ax_store_id"]').val(),
                            location_id: $('[name="location_id"]').val(),
                            delivery_from: $('[name="delivery_from"]').val(),
                            transfer_order_quantity: $('[name="transfer_order_quantity"]').val(),
                            newdeliverymethod: $('[name="newdeliverymethod"]').val(),
                            deliverymethod: $('[name="deliverymethod"]').val(),
                            delivery_date: $('[name="delivery_date"]').val(),
                            delivery_comment: $('[name="delivery_comment"]').val()
                            
                          
                        }
                    }
                };
                
               
                
                fullScreenLoader.startLoader();
                
                
                
                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        quote.setTotals(response.totals);
                        paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        var messageObject = JSON.parse(response.responseText);
                        errorValidationMessage: messageObject.message;
                        $('#delivery_error').val(messageObject.message);
                        $('.message_error').css("display", "block");
                        $('.messages').css("display", "none");
                        goalId : ko.observable(messageObject.message);
                        
                        fullScreenLoader.stopLoader();
                    }
                );
        
                 
                 
            }
        };
    }
);
