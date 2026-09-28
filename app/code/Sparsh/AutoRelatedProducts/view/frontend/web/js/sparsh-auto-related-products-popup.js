require(['jquery'], function ($) {
	$('.sparsh-auto-related-products-popup .popup-action .remove-popup').click(function () {
		$(this).parent().parent().hide();
	});
	$('.sparsh-auto-related-products-popup .popup-action .close-popup').click(function () {
		var opened = $('.sparsh-auto-related-products-popup .popup-action').attr('data-opened');
		if (opened == "true") {
			$('.sparsh-auto-related-products-popup .popup-action').attr('data-opened', "false");				
		} else {
			$('.sparsh-auto-related-products-popup .popup-action').attr('data-opened', "true");				
		}
		$(this).parent().siblings().toggle();
	});
});