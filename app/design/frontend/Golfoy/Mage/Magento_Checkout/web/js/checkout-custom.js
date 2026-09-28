require(["jquery"], function (jQuery) {
    jQuery(document).ready(function () {

        jQuery(document).on("click", ".opc-estimated-wrapper button.action.showcart", function(e) {
            jQuery('aside.modal-custom.opc-sidebar.opc-summary-wrapper.custom-slide').addClass('_show');
        });

    
        setInterval(function () {
            var target = jQuery('div#checkout-payment-method-load');
            var totalText = jQuery(".payment-method-title.field.choice input#cashondelivery");
            if (!totalText.length){
                if (target.length && jQuery('body#html-body').hasClass('checkout-index-index') && !target.hasClass('cod-append')) {
                    target.addClass('cod-append');
                    var htmlToAppend = `
                        <div class="payment-method-title-cod-availabe">
                            <input type="radio" id="cashondelivery" value="cashondelivery" disabled>
                            <label class="label" for="cashondelivery"><span>Cash On Delivery</span></label>
                            <div class="notice-for-cod">Cash on Delivery is available only for orders above ₹999 to ₹99,999 only</div>
                        </div>
                    `;
                    target.after(htmlToAppend);
                }
            }else{
                jQuery('.payment-method-title-cod-availabe').remove();
            }

        }, 1000);
        
        setInterval(function (){
            var pincode = jQuery('input[name="postcode"]').val();
            if(pincode === '*'){
                jQuery('input[name="postcode"]').val('');  
            }
        },1000);

        function toggleVisibility() {

            jQuery(".payment-method._active").each(function () {
                if(jQuery("tr.grand.totals td.amount span.price").text() == "₹ 0"){
                    jQuery(this).find(".other-payment").parents('.checkout-agreement').remove();
                    return;
                }
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