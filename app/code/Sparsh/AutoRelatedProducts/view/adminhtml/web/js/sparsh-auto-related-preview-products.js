define(['jquery'], function($) {
	'use strict';
	return function(config, element) {
		$(element).click(function () {
			if ($(element).attr('data-hidelist') == 'true') {
				$(element).attr('data-hidelist', 'false');
				$(element).text('Preview Products');
				$('.sparsh-auto-related-products-'+config.type+'-product-list').html('');
			} else {
				var formData = $('#edit_form').serialize();
				$.ajax({
		            showLoader: true,
		            url: config.url,
		            data: formData,
		            type: "get",
		        }).done(function (data) {
		        	$(element).attr('data-hidelist', 'true');
		        	$(element).text('Hide Products');
		        	$('.sparsh-auto-related-products-'+config.type+'-product-list').html(data);
		        });
		    }
	    });
	};
});
