define(['jquery'], function($) {
	'use strict';
	return function(config) {
		$.ajax({
		    url: config.baseUrl + 'sparsh_auto_related_products/index/ajax',
		    method: 'POST',
		    data: {
		    	'id': config.id,
		    	'type': config.type,
		    	'rule_id': config.rule_id
		    },
		    success: function (response) {
		    	response = response.data;
		    	if (response.success = true && response.hasRules == true) {
			    	$.each(response.rules, function (key, value) {
			    		if (value.position) {
			    		    if($("#sparsh-auto-related-products-custom-"+config.rule_id).length)
                            {
                                //$("#sparsh-auto-related-products-custom-"+config.rule_id).append(value.html).trigger('contentUpdated');
								$("#sparsh-auto-related-products-custom-"+config.rule_id).html(value.html).trigger('contentUpdated');
                            }else {
                                /*var position = '#sparsh-auto-related-products-' + value.position.replace('_', '-');
                                if (position.search('replace') > 0) {
                                    var replacePosition = value.position.split('_');
                                    if ($('.block.'+replacePosition[1]).length > 0) {
                                        $('.block.'+replacePosition[1]).html(value.html).trigger('contentUpdated');
                                    } else {                                       
									   $("#csparsh-auto-related-products-after-related").html(value.html);
                                    }
                                } else {
                                    $(position).append(value.html).trigger('contentUpdated');
                                }*/
								$("#csparsh-inner").remove();
								$("#csparsh-auto-related-products-after-related").append(value.html).trigger('contentUpdated');
                            }
					    }
			    	});
		    	}
		    }
		});
	};
});
