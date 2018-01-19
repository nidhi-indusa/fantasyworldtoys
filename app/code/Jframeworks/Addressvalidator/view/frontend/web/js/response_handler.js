define(
    [
		'Jframeworks_Addressvalidator/js/custom',
        'jquery',
        'ko'
		
    ],function (
		custom,
        $,
        ko) {
    'use strict';


        var addresponse = function(configs) { // target == Result that Magento_Ui/.../default returns.
			var res  = configs;
			console.log(res);
			
			if(res.error == true){
				return custom.canVisibleRadio(res);
			}
			else{
				$('#jframeworks_ship_addr_radio').html('');
				$('#jframeworks_ship_addr_correction').css('display','none');
			}
			
		};
		
		return addresponse;
});