define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/shipping-rate-processor/customer-address',
    'Magento_Checkout/js/model/shipping-rate-processor/new-address',
], function ($, wrapper, quote, shippingService, rateRegistry, customerAddressProcessor, newAddressProcessor) {

    $(document).on('change', "[name='country']", function () {
        //for country
        alert("change country");
    });

    $(document).on('change', "[name='region_id']", function () {

        stateval = $(this).val();
        var cityId = $('[name="city"]:eq(0)').attr('id');
        populateSelect(stateval, cityId);

    });

    function populateSelect(sval, cityId) {
        var cityOptionObject = JSON.stringify(window.checkoutConfig.cityOptionData);
        var TESTObject = JSON.parse(cityOptionObject);
        var cityData = [];
        for (var x in TESTObject) {
            cityData.push(TESTObject[x]);
        }
        //  console.log(cityData);
        var stateOptionObject = JSON.stringify(window.checkoutConfig.stateOptionData);
        var stateObject = JSON.parse(stateOptionObject);
        var stateData = [];
        for (var x in stateObject) {
            stateData.push(stateObject[x]);
        }
        //console.log(stateData);     
        $.each(stateData, function (index, statedata) {
            var finalcityData = [];
            if (sval == statedata['region_id']) {
                $.each(cityData, function (index, citydata) {
                    if (statedata['code'] == citydata['state_id']) {
//                        console.log(sval);
//                        console.log(statedata['code']);
//                        console.log(citydata['state_id']);
//                        console.log(statedata['region_id']);
                        finalcityData.push(citydata['city_name']);
                    }
                });
                $('#'+cityId).html('');
                finalcityData.forEach(function(t) { 
                $('#'+cityId).append('<option>'+t+'</option>');
                });
               // console.log("finalcityData");
                //console.log(finalcityData);
            }
           
        });
    }
});