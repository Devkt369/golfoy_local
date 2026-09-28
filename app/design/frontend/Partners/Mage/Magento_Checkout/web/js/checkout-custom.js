require(["jquery"], function (jQuery) {
    jQuery(document).ready(function () {
        
        setInterval(function (){
            var pincode = jQuery('input[name="postcode"]').val();
            if(pincode === '*'){
                jQuery('input[name="postcode"]').val('');  
            }
        },1000);

        function toggleVisibility() {

            jQuery(".payment-method._active").each(function () {
                if (jQuery(this).find("input#cashondelivery").length) {
                    jQuery(this).find(".other-payment").parents('.checkout-agreement').remove();
                } else {
                    jQuery(this).find(".other-payment").removeClass("hide");
                }

                if (jQuery(this).find("input#razorpay").length) {
                    jQuery(this).find(".cod-termscondition").remove();
                } else {
                    jQuery(this).find(".cod-termscondition").removeClass("hide");
                }
            });
        }
        toggleVisibility();
        jQuery("input#cashondelivery, input#razorpay").on("click", function () {
            toggleVisibility();
        });
        
        setInterval(() => {
            toggleVisibility();
        }, 1000);


    });
});