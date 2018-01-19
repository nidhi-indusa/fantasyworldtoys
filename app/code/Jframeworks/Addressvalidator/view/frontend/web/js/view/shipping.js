define(
    [
		'Jframeworks_Addressvalidator/js/custom',
		'mage/url',
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
        'Magento_Checkout/js/model/shipping-rate-service',
		'mage/template',
		'jquery/ui',
		'mage/translate',
		
    ],function (
		custom,
		url,
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
        $t) {
    'use strict';
	

    var mixin = {

		setShippingInformation: function () {
			
					var checkData;
					var baseUrl = url.build('');
					
		
			if (this.validateShippingInformation()) {
				setShippingInformationAction().done(
					function () {
							if (customer.isLoggedIn()) {
							checkData = quote.shippingAddress();
							//console.log (checkData);
							
							
							var country = checkData.countryId;
							
							if(country == '')
							{
								country = $('select[name="country_id"]').val();
							}
							
							var city = checkData.city;
							if(city == ''){
								city = $('input[name="city"]').val();
							}
							
							var regionid = checkData.regionId;
							if(regionid == ''){
								regionid = $('select[name="region_id"] option:selected').val();
							}
							
							
							var state = checkData.region;
							
							if(state == '')
							{	
								if( $('div[name="shippingAddress.region_id"]').attr('style') == "display: none;")
								{
									state = $('input[name="region"]').val();
									$('select[name="region_id"]').val($('select[name="region_id"] option:first').val());
								}
								else{
									state = $('select[name="region_id"] option:selected').text();
									$('input[name="region"]').val('');
								}
							}
							

							/* if(state == '')
							{
								state = $('input[name="region"]').val();
							}
							else if(state == '' || state.indexOf("Please select a region") >= 0){
								state = $('select[name="region_id"] option:selected').text();
							}	 */
							
							
							var postcode = checkData.postcode;
							if(postcode == ''){
								postcode = $('input[name="postcode"]').val();
							}
							
							
							var street1 = checkData.street[0];
							if(street1 == ''){
								street1 = $('input[name="street[0]"]').val();
							}
							
							var street2 = checkData.street[1];
							if(street2 == ''){
								street2 = $('input[name="street[1]"]').val();
							}
								
							
						}
						else{
							var country = $('select[name="country_id"]').val();
							var city = $('input[name="city"]').val();
							var regionid = $('select[name="region_id"] option:selected').val();
							
							
							if( $('div[name="shippingAddress.region_id"]').attr('style') == "display: none;")
							{
								var state = $('input[name="region"]').val();
								$('select[name="region_id"]').val($('select[name="region_id"] option:first').val());
							}
							else{
								var state = $('select[name="region_id"] option:selected').text();
								$('input[name="region"]').val('');
							}
							
							/* var state = $('input[name="region"]').val();
							if(state == '')
							{	
								state = $('select[name="region_id"] option:selected').text();
							} */
							var postcode = $('input[name="postcode"]').val();
							var street1 = $('input[name="street[0]"]').val();
							var street2 = $('input[name="street[1]"]').val();
							
						}
						/* debugger; */
						var selected = $('input[name="jframeworks_which_to_use"]:checked').val();

						if(selected != undefined)
						{
						   
							if(selected != 'orig'){
								selected = "corrected_"+selected;
							}
						  
							var post_addr1 = $('#jframeworks_ship_addr_'+selected+'_addr1').val();
							var post_addr2 = $('#jframeworks_ship_addr_'+selected+'_addr2').val();
							var post_city 	= $('#jframeworks_ship_addr_'+selected+'_city').val();
							var post_state = $('#jframeworks_ship_addr_'+selected+'_state').val();
							var post_zip 	= $('#jframeworks_ship_addr_'+selected+'_zip').val();
							var post_country 	= $('#jframeworks_ship_addr_'+selected+'_country').val();
							
							var Postdata = {strt1: street1, strt2: street2, pstcde: postcode, ct: city, st: state, regid: regionid, ctry: country, post_addr1: post_addr1, post_addr2:post_addr2, post_city:post_city, post_state:post_state, post_zip:post_zip, post_country:post_country, selected:selected};
							

						}
						else{

							var Postdata = {strt1: street1, strt2: street2, pstcde: postcode, ct: city, st: state, regid: regionid, ctry: country};
						}
						
						$.ajax({
						type: "POST",
						url : baseUrl+"shippingblock/shipping/check",
						data : Postdata,
						dataType: "json",
						showLoader: true,
						success: function(response){
								var res  = $.parseJSON(response);
								if(res.error == true){
									custom.canVisibleRadio(res);
								}
								else{
									$('#jframeworks_ship_addr_radio').html('');
									$('#jframeworks_ship_addr_correction').css('display','none');
									stepNavigator.next();
								}
							}
						});

					}
				);
			}
        },
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
		
		
		
    };

    return function (target) { // target == Result that Magento_Ui/.../default returns.
    return target.extend(mixin); // new result that all other modules receive 
};
});