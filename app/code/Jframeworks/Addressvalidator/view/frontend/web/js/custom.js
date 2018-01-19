define(
    [
        'jquery',
		 'ko',
		 'uiComponent',
      
    ],
    function($ , ko , Component) {
		
		 'use strict';
		 
		 
		 $(document).ready(function(){
			 
			
			$(document).on('change',"input[name='jframeworks_which_to_use']:radio",function()
			{
				var item =$('input[name="jframeworks_which_to_use"]:checked').val();
				
				if(item =='orig'){
					//go with orig values
					addr1 = $('#jframeworks_ship_addr_orig_addr1').val();
					addr2 = $('#jframeworks_ship_addr_orig_addr2').val();
					city  = $('#jframeworks_ship_addr_orig_city').val();
					region_id = $('#jframeworks_ship_addr_orig_region_id').val();
					state = $('#jframeworks_ship_addr_orig_state').val();
					zip   = $('#jframeworks_ship_addr_orig_zip').val();
					country   = $('#jframeworks_ship_addr_orig_country').val();
					
				} else {
					//it is one of the corrected fields
					var key = item;
					
					var addr1 = $('#jframeworks_ship_addr_corrected_' + key + '_addr1').val();
					var addr2 = $('#jframeworks_ship_addr_corrected_' + key + '_addr2').val();
					var city  = $('#jframeworks_ship_addr_corrected_' + key + '_city').val();
					var region_id = $('#jframeworks_ship_addr_corrected_' + key + '_region_id').val();
					var state = $('#jframeworks_ship_addr_corrected_' + key + '_state').val();
					var zip   = $('#jframeworks_ship_addr_corrected_' + key + '_zip').val();
					var country   = $('#jframeworks_ship_addr_corrected_' + key + '_country').val();
					
					$('.action-show-popup').trigger('click');
					
					$(document).on('click',".action-show-popup",function()
					{
						$('#jframeworks_ship_addr_radio').html('');
						$('#jframeworks_ship_addr_correction').css('display','none');
					});

				}

				//OK are we shipping to different addr?
				if($('input[name="street[0]"]').length > 0)
				{
					$('input[name="street[0]"]').val(addr1);
					$('input[name="street[1]"]').val(addr2);
					$('input[name="street[0]"]').keyup();
					$('input[name="street[1]"]').keyup();
				}
				else{
					$('#street_1').val(addr1);
					$('#street_2').val(addr2);
					$('#street_1').keyup();
				    $('#street_2').keyup();
				}
				
				$('input[name="city"]').val(city);
				$('input[name="city"]').keyup();
				$('input[name="postcode"]').val(zip);
				$('input[name="postcode"]').keyup();
				$('select[name="country_id"]').val(country).change();
				
				var requestPath = location.pathname; 
				
				if(requestPath.indexOf('multishipping') != -1)
				{
					if( $('select[name="region_id"]').attr('style') == "display: none;")
					{
						$('input[name="region"]').val(state);
						$('input[name="region"]').keyup();
						$('select[name="region_id"]').val($('select[name="region_id"] option:first').val());	
					}
					else{
						$('select[name="region_id"]').val(region_id).change();
						$('input[name="region"]').val('');
					}
				}
				else{
					if( $('div[name="shippingAddress.region_id"]').attr('style') == "display: none;")
					{
						$('input[name="region"]').val(state);
						$('input[name="region"]').keyup();
						$('select[name="region_id"]').val($('select[name="region_id"] option:first').val());	
					}
					else{
						$('select[name="region_id"]').val(region_id).change();
						$('input[name="region"]').val('');
					}
				}
				
				

				
				
			});
			
			$(document).on('click',"button[name='clearAddress']",function()
			{
				$('#jframeworks_ship_addr_radio').html('');
				$('#jframeworks_ship_addr_correction').css('display','none');
			});
			
		 });
		 
		 
		
		return {
					  
			canVisibleRadio: function (res) {
				 	
				
				$('#jframeworks_ship_addr_radio').html('');
				 //first the original
				 //radio button
				 if(res.data.orig.addr2 == null)
				 {
					res.data.orig.addr2 = '';
				 }
				 
				 var addr;
				 var orgAddress;
				 var address;
				 addr = ((res.data.orig.addr1 =="") ? "" : res.data.orig.addr1 + ", ");
				 addr += ((res.data.orig.addr2 =="") ? "" : res.data.orig.addr2 + ", "); 
				 addr += ((res.data.orig.city =="") ? "" : res.data.orig.city + ", ");
				 addr += ((res.data.orig.state =="") ? "" : res.data.orig.state + ", ");
				 addr += ((res.data.orig.zip =="") ? "" : res.data.orig.zip);
				 
				
				
				
				
				   address = $('#jframeworks_ship_addr_radio');
				 
				  orgAddress = '<div class="jframeworks-addr-radio"><input type="radio" name="jframeworks_which_to_use" id="jframeworks_ship_radio_orig" value="orig" checked><label for="jframeworks_ship_radio_orig"><b> Use Original: </b>' + addr + '</label></div>';
				 
				 //The hidden fields that get posted back to our plugin
				orgAddress +="<div style='display: hidden;'><input type='hidden' name='jframeworks_ship_addr_orig_addr1' id='jframeworks_ship_addr_orig_addr1' value='" + res.data.orig.addr1 + "'><input type='hidden' name='jframeworks_ship_addr_orig_addr2' id='jframeworks_ship_addr_orig_addr2' value='" + res.data.orig.addr2 + "'><input type='hidden' name='jframeworks_ship_addr_orig_city' id='jframeworks_ship_addr_orig_city' value='"  + res.data.orig.city + "'><input type='hidden' name='jframeworks_ship_addr_orig_state' id='jframeworks_ship_addr_orig_state' value='" + res.data.orig.state + "'><input type='hidden' name='jframeworks_ship_addr_orig_region_id' id='jframeworks_ship_addr_orig_region_id' value='" + res.data.orig.region_id + "'><input type='hidden' name='jframeworks_ship_addr_orig_zip' id='jframeworks_ship_addr_orig_zip' value='" + res.data.orig.zip + "'><input type='hidden' name='jframeworks_ship_addr_orig_country' id='jframeworks_ship_addr_orig_country' value='" + res.data.orig.country + "'></div>";
				 
				 //do we have any corrected addresses?
				if( typeof(res.data.corrected) !== 'undefined' && res.data.corrected.length > 0){
					for (var i = 0; i < res.data.corrected.length; i++) {
						addr = ((res.data.corrected[i].addr1 =="") ? "" : res.data.corrected[i].addr1 + ", ");
						addr += ((res.data.corrected[i].addr2 =="") ? "" : res.data.corrected[i].addr2 + ", "); 
						addr += ((res.data.corrected[i].city =="") ? "" : res.data.corrected[i].city + ", ");
						addr += ((res.data.corrected[i].state =="") ? "" : res.data.corrected[i].state + ", ");
						addr += ((res.data.corrected[i].zip =="") ? "" : res.data.corrected[i].zip);

						orgAddress += '<div class="jframeworks-addr-radio"><input type="radio" name="jframeworks_which_to_use" id="jframeworks_ship_radio_' + i + '" value="' + i + '" ><label for="jframeworks_ship_radio_' + i + '"><b> Suggestion: </b>' + addr + '</label></div>';

						//The hidden fields that get posted back to our plugin

						orgAddress += "<div style='display: hidden;'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_addr1' id='jframeworks_ship_addr_corrected_" + i + "_addr1' value='" + res.data.corrected[i].addr1 + "'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_addr2' id='jframeworks_ship_addr_corrected_" + i + "_addr2' value='" + res.data.corrected[i].addr2 + "'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_city' id='jframeworks_ship_addr_corrected_" + i + "_city' value='"  + res.data.corrected[i].city + "'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_state' id='jframeworks_ship_addr_corrected_" + i + "_state' value='" + res.data.corrected[i].state + "'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_region_id' id='jframeworks_ship_addr_corrected_" + i + "_region_id' value='" + res.data.corrected[i].region_id + "'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_zip' id='jframeworks_ship_addr_corrected_" + i + "_zip' value='" + res.data.corrected[i].zip + "'><input type='hidden' name='jframeworks_ship_addr_corrected_" + i + "_country' id='jframeworks_ship_addr_corrected_" + i + "_country' value='" + res.data.corrected[i].country + "'></div>";
					}
				 }
				 
				 orgAddress +='<div style="margin-top: 10px!important;margin-bottom:25px!important;"><button name="clearAddress" id="clearAddress">Clear Results</button></div>';
				 
				 address.html(orgAddress);
				 //un-hide the display
				 $('#jframeworks_ship_addr_correction').show();
				
				if($('input[name="street[0]"]').length > 0)
				{
					$('input[name="street[0]"]').val($('#jframeworks_ship_addr_orig_addr1').val());
					$('input[name="street[1]"]').val($('#jframeworks_ship_addr_orig_addr2').val());
					$('input[name="street[0]"]').keyup();
					$('input[name="street[1]"]').keyup();
				}
				else{
					$('#street_1').val($('#jframeworks_ship_addr_orig_addr1').val());
					$('#street_2').val($('#jframeworks_ship_addr_orig_addr2').val());
					$('#street_1').keyup();
				    $('#street_2').keyup();
				}
				
				$('input[name="city"]').val($('#jframeworks_ship_addr_orig_city').val());
				$('input[name="postcode"]').val($('#jframeworks_ship_addr_orig_zip').val());
				$('input[name="city"]').keyup();
				$('select[name="country_id"]').val($('#jframeworks_ship_addr_orig_country').val()).change();
				
				var requestedPath = location.pathname; 
				
				if(requestedPath.indexOf('multishipping') != -1)
				{
					if( $('select[name="region_id"]').attr('style') == "display: none;")
					{
						$('input[name="region"]').val($('#jframeworks_ship_addr_orig_state').val());
						$('input[name="region"]').keyup();
						$('select[name="region_id"]').val($('select[name="region_id"] option:first').val());	
					}
					else{
						$('select[name="region_id"]').val($('#jframeworks_ship_addr_orig_region_id').val()).change();
						$('input[name="region"]').val('');
					}
				}
				else{
					if( $('div[name="shippingAddress.region_id"]').attr('style') == "display: none;")
					{
						$('input[name="region"]').val($('#jframeworks_ship_addr_orig_state').val()).keyup();
						$('select[name="region_id"]').val('');	
					}
					else{
						$('select[name="region_id"]').val($('#jframeworks_ship_addr_orig_region_id').val()).change();
						$('input[name="region"]').val('');
					}
				}
				
				$('input[name="postcode"]').keyup();
						
			}
			  
		};

    }
);