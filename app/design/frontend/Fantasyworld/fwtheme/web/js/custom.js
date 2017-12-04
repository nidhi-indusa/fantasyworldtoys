jQuery(document).ready(function() {
	
	//initializing custom.js
	console.log("initialization:custom.js"); 
		
	//header search module
	jQuery("#search").hide();
	jQuery(".page-header button.search").click(function(){
		jQuery("#search").toggle();
		jQuery("#search").css({"width":"330"},{"opacity":"1"});
		jQuery("#search").focus();
		jQuery(this).toggleClass("open");
		jQuery(".block-search").find("button.action").removeAttr("disabled");
	});
	jQuery("#search").blur(function(){
		jQuery("#search").css({"width":"330"},{"opacity":"0"});
		jQuery("#search").val('');
		jQuery(".block-search").find("button.action").removeAttr("disabled");
	});
	
	//product list page filter accordion
	 jQuery(".sidebar .filter-options .filter-options-title").click(function(){
		 jQuery(this).next(".filter-options-content").slideToggle();
		 jQuery(this).toggleClass("closefilter"); 
	 });
	 
	 //list page searchbox
	 jQuery('#search_list').keyup(function() {
        if (jQuery(this).val() == '') {
           jQuery(".btn-search1").attr('disabled', 'disabled');
        } else {
           jQuery(".btn-search1").removeAttr("disabled");
        }
    });
	
	//toggle gift finder
	jQuery(".gift-finder-btn").click(function(){
		jQuery(".mana-filter-block.gift-finder-dropdown").toggle();	
	});
	
	//responsive menu clone dropdown
	var $clonedMenu = jQuery('.panel.header > .header.links').html();
	jQuery(".header.links").after('<div class="btn-group responsive-sub-menu">'+
	'<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'+
    '<i class="fa fa-user-circle-o"></i> <span class="caret"></span>'+
	'</button>'+
	'<ul class="dropdown-menu mobile-menu-list">'+
	'</ul>'+  
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
	
	
	//mobile(less than 768) js
	if (jQuery(window).width() < 768) {	

		//responsive account link menu and gift finder	
		jQuery(".header.links, .nav-sections").find("li.gift-finder-wrapper").remove();
		jQuery(".responsive-sub-menu").after("<ul class='responsive-gift-finder'></ul>");
		jQuery(".mobile-menu-list").find("li.gift-finder-wrapper").appendTo(".responsive-gift-finder");
		jQuery(".responsive-gift-finder a").html("<i class='fa fa-gift'></i>");
		jQuery(".gift-finder-btn").click(function(){
			jQuery(".mana-filter-block.gift-finder-dropdown").toggle();
			jQuery(this).toggleClass("arrow");	
		});
		
		//responsive minicart
		jQuery(".showcart").click(function(){
			jQuery("body").css("overflow","hidden");			
			jQuery("#btn-minicart-close").click(function(){
				jQuery("body").css("overflow","visible");			
			});
		});

		//footer accordion in smaller device
		jQuery(".footer h3").click(function(){
			jQuery(this).toggleClass('open');
			jQuery(this).siblings('ul.accord').slideToggle(200);
		});		
		
	}
	
});//document ready ends


jQuery(window).load(function() {
	
	//homepage loader
	jQuery(".home-loader").fadeOut("slow");
	
	//home slider
	jQuery(".custom-slider").css("visibility","visible"); 
	jQuery(".banner-image img").css("display","block"); 
	
});//window load ends