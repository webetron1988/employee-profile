jQuery(document).ready(function(){
								
"use strict";

 if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
      $('body, html').addClass('touch')
    }

$.fn.slideFadeToggle  = function(speed, easing, callback) {
        return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
};



	jQuery('#menu-button').on('click', function() {
			jQuery('#main-nav ul#options').slideFadeToggle();
			$('#menu-button').toggleClass('open');
			return false;
	});
	
	if ( jQuery(window).width() < 980) {
	jQuery('#main-nav ul#options li a').not('.sub-page #main-nav ul#options li a').on('click', function() {
			jQuery('#main-nav ul#options').hide();
			$('#menu-button').removeClass('open');
			return false;
	});
	}
	
	
});	
	
	
	

	