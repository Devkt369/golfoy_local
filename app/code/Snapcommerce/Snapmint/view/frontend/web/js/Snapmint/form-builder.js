define(
    [
        'jquery',
        'underscore',
        'mage/template'
    ],
    function ($, _, mageTemplate) {
        'use strict';



        /* This is the form template used to generate the form, from your Controller JSON data */
        var form_template =
            '<form action="<%= data.action %>"' +
                    ' method="POST" hidden enctype="application/x-www-form-urlencoded">' +
                '<% _.each(data.fields, function(val, key){ %>' +
                    '<input value=\'<%= val %>\' name="<%= key %>" type="hidden">' +
                '<% }); %>' +
                '<% _.each(data.product_info, function(product_info_fields, key){ %>' +
                      '<% _.each(product_info_fields, function(val, key){ %>' +
                            '<input value=\'<%= val %>\' name="products[][<%= key %>]" type="hidden">' +
                        '<% }); %>' +
                '<% }); %>' +
            '</form>'

        return function (response) {
            var form = mageTemplate(form_template);
            var final_form = form({
                data: {
                    action: response.action, 
                    fields: response.form_fields, 
                    product_info: response.product_info 
                }
            });

            /*console.log('-------------');
            console.log($(final_form).serializeArray());
            console.log('--------------');*/
            return $(final_form).appendTo($('[data-container="body"]')); //Finally, append the form to the <body> element (or more accurately to the [data-container="body"] element)
        };
    }
);