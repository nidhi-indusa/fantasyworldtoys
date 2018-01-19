jQuery(document).ready(function () {

    //initializing custom.js
    console.log("initialization:custom.js");
	
	//responsive megamenu accordion
	jQuery("#om").click(function(){
		jQuery(".menu-dropdown-icon").click(function(){
			jQuery("ul.show-on-mobile li").not(this).children("ul").hide();
		});
	});

    //product list page filter accordion
    jQuery(".sidebar .filter-options .filter-options-title").click(function () {
        jQuery(this).next(".filter-options-content").slideToggle();
        jQuery(this).toggleClass("closefilter");
    });

    //list page searchbox
    jQuery('#search_list').keyup(function () {
        if (jQuery(this).val() == '') {
            jQuery(".btn-search1").attr('disabled', 'disabled');
        } else {
            jQuery(".btn-search1").removeAttr("disabled");
        }
    });

    //toggle gift finder
    jQuery(".gift-finder-btn").click(function () {
        jQuery(".mana-filter-block.gift-finder-dropdown").toggle();
    });

    //responsive menu clone dropdown
    var $clonedMenu = jQuery('.panel.header > .header.links').html();
    jQuery(".header.links").after('<div class="btn-group responsive-sub-menu">' +
            '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
            '<i class="fa fa-user-circle-o"></i>' +
            '</button>' +
            '<ul class="dropdown-menu mobile-menu-list">' +
            '</ul>' +
            '</div>');
    jQuery('.mobile-menu-list').append($clonedMenu);

    //minicart overlay		
    // jQuery(document).on('click', function(){
    // if( jQuery(".minicart-wrapper").hasClass("active")){
    // jQuery("body").addClass("minicart-open");
    // }
    // else{
    // jQuery("body").removeClass("minicart-open");
    // }		
    // jQuery("#btn-minicart-close").click(function(){	
    // setTimeout(function(){ jQuery("body").removeClass("minicart-open"); }, 0);	
    // });		
    // });

    // jQuery(".showcart").click(function(){		
    // jQuery("body").addClass("minicart-open");
    // });

    // jQuery("#btn-minicart-close").click(function(){	
    // setTimeout(function(){ jQuery("body").removeClass("minicart-open"); }, 700);	
    // });
	
	//mobile(greater than 768) js
    if (jQuery(window).width() > 767) {
		//header search module
		jQuery("#search").hide();
		jQuery(".page-header button.search").click(function () {
			jQuery("#search").toggle();
			jQuery("#search").css({"width": "330"}, {"opacity": "1"});
			jQuery("#search").focus();
			jQuery(this).toggleClass("open");
			jQuery(".block-search").find("button.action").removeAttr("disabled");
		});
		jQuery("#search").blur(function () {
			jQuery("#search").css({"width": "330"}, {"opacity": "0"});
			jQuery("#search").val('');
			jQuery(".block-search").find("button.action").removeAttr("disabled");
		});
	}//>768ends


    //mobile(less than 768) js
    if (jQuery(window).width() < 768) {
	
		//filter accordion for list page
		jQuery(".mana-filter-block-custom .filter-title").click(function(){
			jQuery(this).siblings(".filter-content").fadeToggle();
			jQuery(this).find("i").toggleClass("fa-plus fa-minus");
		});	
		jQuery("h4.category-title").click(function(){
			jQuery(this).next("ul").fadeToggle();
			jQuery(this).find("i").toggleClass("fa-plus fa-minus");
		});	
		jQuery(".block.filter-custom .block-title").click(function(){
			jQuery(this).next("ul.items").fadeToggle();
			jQuery(this).siblings("a").fadeToggle();
			jQuery(this).find("i").toggleClass("fa-plus fa-minus");
		});

        //responsive account link menu and gift finder	
        jQuery(".header.links, .nav-sections").find("li.gift-finder-wrapper").remove();
        jQuery(".responsive-sub-menu").after("<ul class='responsive-gift-finder'></ul>");
        jQuery(".mobile-menu-list").find("li.gift-finder-wrapper").appendTo(".responsive-gift-finder");
        jQuery(".responsive-gift-finder a").html("<i class='fa fa-gift'></i>");
        jQuery(".gift-finder-btn").click(function () {
            jQuery(".mana-filter-block.gift-finder-dropdown").toggle();
            jQuery(this).toggleClass("arrow");
        });

        //responsive minicart
        jQuery(".showcart").click(function () {
            jQuery("body").css("overflow", "hidden");
            jQuery("#btn-minicart-close").click(function () {
                jQuery("body").css("overflow", "visible");
            });
        });

        //footer accordion in smaller device
        jQuery(".footer h3").click(function () {
            jQuery(this).toggleClass('open');
            jQuery(this).siblings('ul.accord').slideToggle(200);
        });
		
		//header search module
		jQuery("#search").hide();
		jQuery(".page-header .search").click(function (event) {
			event.preventDefault();
			jQuery("#search,#autoClose").toggle();
			jQuery("#search").focus();
			jQuery(this).find("label.label").addClass("active");
			
			//jQuery(this).toggleClass("open");
			//jQuery(".block-search").find("button.action").removeAttr("disabled");
		});
		jQuery("#search").click(function (event) {
			event.stopPropagation();
		});
		jQuery("#search").blur(function () {
			jQuery("#search").val('');
		});

    }//768 ends

    //popup x issue
    jQuery('.magebuzz_quickview_button').click(checkLoader);
    function checkLoader() {
        window.setInterval(function () {
            if (jQuery('.mfp-preloader').css('display') == 'none') {
                jQuery('.mfp-close').delay(100).fadeIn(100);
            }
            else {
                jQuery('.mfp-close').hide();
            }
        }, 500);
    }

    //scroll to top
    jQuery(window).scroll(function () {
        if (jQuery(this).scrollTop() >= 800) {
            jQuery('#return-to-top').fadeIn(200);
        } else {
            jQuery('#return-to-top').fadeOut(200);
        }
    });
    jQuery('#return-to-top').click(function () {
        jQuery('body,html').animate({
            scrollTop: 0
        }, 500);
    });
    var mobile = (/iphone|ipod|android|blackberry|mini|windows\sce|palm/i.test(navigator.userAgent.toLowerCase()));
    if (mobile) {
        jQuery('#return-to-top').hide();
    }

    //bhagyashri code starts
    jQuery(window).load(function () {
        if (jQuery('*').hasClass('checkout-index-index')) {
            if (jQuery('select[name="city"]').has('option').length == 0)
            {
                jQuery('select[name="city"]').append('<option value=""  >Please Select City.</option>');
            }
        }
    });
    jQuery(document).on('change', ".checkout-index-index [name='region_id']", function () {
        stateval = jQuery(this).val();
        var cityId = jQuery('[name="city"]:eq(0)').attr('id');
        if (stateval == "") {
            jQuery('#' + cityId).html('');
            jQuery('#' + cityId).append('<option value=""  >Please Select City.</option>');
        } else {
            populateSelect(stateval, cityId);
        }
    });
    function populateSelect(sval, cityId) {
        var cityOptionObject = JSON.stringify(window.checkoutConfig.cityOptionData);
        var TESTObject = JSON.parse(cityOptionObject);
        var cityData = [];
        for (var x in TESTObject) {
            cityData.push(TESTObject[x]);
        }
        var stateOptionObject = JSON.stringify(window.checkoutConfig.stateOptionData);
        var stateObject = JSON.parse(stateOptionObject);
        var stateData = [];
        for (var x in stateObject) {
            stateData.push(stateObject[x]);
        }
        jQuery.each(stateData, function (index, statedata) {
            var finalcityData = [];
            if (sval == statedata['region_id']) {
                jQuery.each(cityData, function (index, citydata) {
                    if (statedata['code'] == citydata['state_id']) {
                        finalcityData.push(citydata['city_name']);
                    }
                });
                jQuery('#' + cityId).html('');
                jQuery('#' + cityId).append('<option value=""  >Please Select City.</option>');
                finalcityData.forEach(function (t) {
                    jQuery('#' + cityId).append('<option value="' + t + '">' + t + '</option>');
                });
            }
        });
    }
	//bhagyashri code ends

	//home slider
	jQuery(".banner-image img").css("display", "block");
	
	//checkout payment page label change
	function paymentLblCheck(){
		if (jQuery('li.opc-progress-bar-item').hasClass('_complete'))
		{
			if (jQuery("#clickandcollectLabel").is(':checked')) {
				jQuery("label.label[for='cashondelivery'] span").text("Pay at Store");
			}
			else{
				jQuery("label.label[for='cashondelivery'] span").text("Cash on Delivery");
			}
		}
	};	
	jQuery(document.body).on('click', "#clickandcollectLabel", function(){
		setInterval(paymentLblCheck, 500);
    });


});//document ready ends

jQuery(window).load(function () {

    //homepage loader
    //jQuery(".cms-home").css("overflow","auto");
    jQuery(".home-loader").fadeOut("slow");

    //home slider
    jQuery(".custom-slider").css("visibility", "visible");
    
});//window load ends