require([
    "jquery",
    "mage/mage"
],function($) {
    jQuery(document).ready(function() {
        jQuery('#open_size_chart').click(function(){    
            var brand = jQuery(this).attr('brand');
            brand = brand.replace(" ", "_");
            var size_type = jQuery(this).attr('size_type');
            var attribute_set_name = jQuery(this).attr('attribute_set_name');
            var base_url =  window.location.origin;
            var img_chart_src =  base_url+"/media/catalog/brands/"+brand+".png";
            
            brand = brand.toLowerCase();
            brand = brand.replace("_", "");
            brand = brand.replace(" ", "");
            brand = brand.replace("/", "");
            brand = brand.replace(/[^a-zA-Z0-9]/g, '');
            jQuery.ajax({
                url: base_url+"/size_chart",
                type: "get",
                success: function (response) {
                    var dataToday = jQuery(response).find('#size_chart_static_content').find("."+brand).find("."+size_type).html();

                    jQuery('#view_size_chart').html(dataToday);
                    jQuery('#attribute_set_name_size_chart').text(attribute_set_name);
                    jQuery('#brand_img_size_chart').attr('src', img_chart_src);
                    jQuery('.brand_name_size_chart').text(brand);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                   console.log(textStatus, errorThrown);
                }
            });
        });
    });
});
