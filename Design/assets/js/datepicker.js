// Class definition

var KTBootstrapDatepicker = function () {

	var arrows;
	if (KTUtil.isRTL()) {
		arrows = {
			leftArrow: '<i class="la la-angle-right"></i>',
			rightArrow: '<i class="la la-angle-left"></i>'
		}
	} else {
		arrows = {
			leftArrow: '<i class="la la-angle-left"></i>',
			rightArrow: '<i class="la la-angle-right"></i>'
		}
	}

    // Private functions
    var demos = function () {


        // range picker
        $('#kt_datepicker_5').datepicker({
        	rtl: KTUtil.isRTL(),
        	todayHighlight: true,
        	format:'dd-mm-yyyy',
        	templates: arrows
        });
        
    }

    return {
        // public functions
        init: function() {
        	demos(); 
        }
    };
}();

jQuery(document).ready(function() {    
	KTBootstrapDatepicker.init();
});