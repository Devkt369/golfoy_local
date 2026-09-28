
    require([
        'jquery',
        'jquery/ui'
    ], function (jQuery) {
        jQuery(document).ready(function () {
//            Starts script for adding new equipment---------------------------------------------
            
            jQuery('body').on('click', '.delete_prd_tradein', function() {
                jQuery(this).parent('.traded_item_inside_row').parent('.traded_item_inside_block').parent('.traded_item').remove();
            });
            
            jQuery('.submit_btn').click(function(e){
                e.preventDefault();
                jQuery('.submit_btn').addClass('submitted');
                jQuery('.trade_btn.trade_another').click();
            });
            
            jQuery('body').on('click', '.trade_another', function() {
                
                jQuery('.last_btn_field').children('.message_form').removeClass('error');
                jQuery('.last_btn_field').children('.message_form').removeClass('success');
                jQuery('.last_btn_field').children('.message_form').text('');
                
                fillimages();
                if( checkValidation().trim() == '' ){

                    var equipment = jQuery('#tradein-form').find( 'input[name="equipment"]' ).val();
                    var brand = jQuery('#tradein-form').find( 'input[name="brand"]' ).val();
                    var prd_model = jQuery('#tradein-form').find( 'input[name="prd_model"]' ).val();
                    var prd_specification = jQuery('#tradein-form').find( 'input[name="prd_specification"]' ).val();
                    var condition = jQuery('#tradein-form').find( 'input[name="condition"]' ).val();
                    var images = jQuery('#tradein-form').find( 'input[name="images"]' ).val();
                    var cust_id = jQuery('#tradein-form').find( 'input[name="cust_id"]' ).val();
                    var email = jQuery('#tradein-form').find( 'input[name="email"]' ).val();
                    var name = jQuery('#tradein-form').find( 'input[name="name"]' ).val();
                    var phone = jQuery('#tradein-form').find( 'input[name="phone"]' ).val();
                    var purchase_date = jQuery('#tradein-form').find( 'input[name="purchase_date"]' ).val();
                    var invoice_upload = jQuery('#tradein-form').find( 'img#uploaded_invoice_preview' ).attr('rel');
                    var order_id = jQuery('#tradein-form').find( 'input[name="order_id"]' ).val();
                    var seller_name = jQuery('#tradein-form').find( 'input[name="seller_name"]' ).val();
                    var purchased_from = jQuery('#tradein-form').find( 'input[name="purchased_from"]' ).val();
                    var tradehtml = GetTradeHtml(equipment,brand,prd_model,prd_specification,condition,images,cust_id,email,name,phone,purchase_date,invoice_upload,order_id,seller_name,purchased_from);
                    jQuery('.selection_of_products').append(tradehtml);
                    jQuery('#invoice_upload_hidden').val('');
                    jQuery('#uploaded_invoice_preview').attr('src', '').hide();
                    jQuery('#invoice_upload').val('').show();
                    jQuery('#remove_uploaded_file').hide();
                    jQuery('#tradein-form').find( 'input[name="equipment"]' ).val('');
                    jQuery('#tradein-form').find( 'input[name="condition"]' ).val('');
                    jQuery('.reset_form_tradein').click();
                    jQuery(".review-resultimg").remove();
                    jQuery('.value_button').removeClass('active');
                    jQuery('.last_btn_field').children('.message_form').text("Product added.");
                    jQuery('.last_btn_field').children('.message_form').addClass('success');
                    var total_num_prds = jQuery(".selection_of_products").children().length; 
                    if( jQuery('.submit_btn').hasClass('submitted') ){
                        if( total_num_prds > 0 ){
                            submit_form_tradein();
                        }
                    }else{
                        jQuery('html, body').animate({
                            'scrollTop' : jQuery("form#tradein-form").position().top
                        });
                    }
                    
                }else{
                    jQuery('.submit_btn').removeClass('submitted');
                    jQuery('.last_btn_field').children('.message_form').text("Some of required fields are missing.");
                    jQuery('.last_btn_field').children('.message_form').addClass('error');
                }
                jQuery('.submit_btn').removeClass('submitted');
                
            });
            
            function submit_form_tradein(){
                var ad_id = jQuery('#tradein-form').find( 'input[name="ad_id"]' ).val();
                var url = jQuery('#tradein-form').attr('action');
                var tradeItems = [];

                jQuery('.trade_item_short').each(function () {
                    var item = {
                        equipment: jQuery(this).find('.equipment').text(),
                        brand: jQuery(this).find('.brand').text(),
                        prd_model: jQuery(this).find('.prd_model').text(),
                        prd_specification: jQuery(this).find('.prd_specification').text(),
                        condition: jQuery(this).find('.condition').text(),
                        images: jQuery(this).find('.images').text(),
                        cust_id: jQuery(this).find('.cust_id').text(),
                        email: jQuery(this).find('.email').text(),
                        name: jQuery(this).find('.name').text(),
                        phone: jQuery(this).find('.phone').text(),
                        purchase_date: jQuery(this).find('.purchase_date').text(),
                        invoice_upload: jQuery(this).find('.invoice_upload').text(),
                        order_id: jQuery(this).find('.order_id').text(),
                        seller_name: jQuery(this).find('.seller_name').text(),
                        purchased_from: jQuery(this).find('.purchased_from').text()
                    };

                    tradeItems.push(item);
                });
                    
                jQuery.ajax({
                    url: url,
                    type: "POST",
                    data: JSON.stringify({
                        ad_id: ad_id,
                        items: tradeItems
                    }),
                    dataType: 'json',
                    showLoader: true,
                    success: function(res) {
                        jQuery('.thank_you_message_tradein').removeClass('hide');

                        jQuery(".tradeprogram_sections").find(".cat_section").slideUp();
                        jQuery(".tradeprogram_sections").find(".faq_Section").slideUp();
                        jQuery(".tradeprogram_sections").find(".selection_of_products").slideUp();

                        jQuery('html, body').animate({
                            'scrollTop' : jQuery("form#tradein-form").position().top
                        });
                        
                        
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                    },
                    complete: function() {
                        
                        
                        jQuery('.thank_you_message_tradein').removeClass('hide');

                        jQuery(".tradeprogram_sections").find(".cat_section").slideUp();
                        jQuery(".tradeprogram_sections").find(".faq_Section").slideUp();
                        jQuery(".tradeprogram_sections").find(".selection_of_products").slideUp();

                        jQuery('html, body').animate({
                            'scrollTop' : jQuery("form#tradein-form").position().top
                        });
                    }
                });
            }
            function fillimages(){
                var images = '';
                var i = 1;
                jQuery(".review-resultimg").each(function(){
                    images = images + "," + jQuery(this).children('input').val();
                });
                jQuery('#tradein-form').find( 'input[name="images"]' ).val(images);
                
                
            }
            function checkValidation(){
//                checkimages();
                
                var error = '';
                
                //check if form is empty and product are added, submit the form
                var equipment = jQuery('#tradein-form').find( 'input[name="equipment"]' ).val();
                var brand = jQuery('#tradein-form').find( 'input[name="brand"]' ).val();
                var prd_model = jQuery('#tradein-form').find( 'input[name="prd_model"]' ).val();
                var prd_specification = jQuery('#tradein-form').find( 'input[name="prd_specification"]' ).val();
                var condition = jQuery('#tradein-form').find( 'input[name="condition"]' ).val();
                var images = jQuery('#tradein-form').find( 'input[name="images"]' ).val();
                
                var total_num_prds = jQuery(".selection_of_products").children().length;    
//                alert( "equipment: "+equipment+" - brand: "+brand+" - prd_model: "+prd_model+" - prd_specification: "+prd_specification+" - condition: "+condition+" - images: "+images+" - total_num_prds: "+total_num_prds );
                
                if( jQuery('.submit_btn').hasClass('submitted') ){

                    if( equipment.trim() == '' && brand.trim() == '' && prd_model.trim() == '' && prd_specification.trim() == '' && condition.trim() == '' && images.trim() == '' && total_num_prds > 0){
//                        alert('submit data, validation completed, form empty but product added');
                        
                        return error;
                    }
                }
                
                jQuery("#tradein-form").find('input').each(function(){
                    jQuery(this).addClass('passed');
                    
                    if( jQuery(this).hasClass('required') ){
                        jQuery(this).addClass('has_required');
                        
                        if( jQuery(this).val().trim() == '' ){
                            jQuery(this).addClass('empty_here');
                            
                            if( jQuery(this).siblings('.error_message').length ){
                            }else{
                                jQuery(this).parent().append("<div class='error_message'>This is required field</div>");
                            }
                            error = 'found';
                        }else{
                            jQuery(this).siblings('.error_message').remove();
                        }
                    }
                });
                
                
                var images = jQuery('#tradein-form').find( 'input[name="images"]' ).val();
                
                var temp = new Array();
                var images_num = images.split(",");
                if( images_num.length < 4 ){
                    alert('Please upload at least 3 images of products');
                    error = 'found';
                    
                    if( jQuery('.pic_upload_block').children('.error_message').length ){
                    }else{
                        jQuery('.pic_upload_block').append("<div class='error_message'>This is required field</div>");
                    }
                }else{
                    jQuery('.pic_upload_block').children('.error_message').remove();
                    
                }
                return error;
            }
            function GetTradeHtml(equipment,brand,prd_model,prd_specification,condition,images,cust_id,email,name,phone, purchase_date,invoice_upload,order_id,seller_name,purchased_from){
//                alert(equipment,brand,prd_model,prd_specification,condition,images);
                var html = '';
                var images_arr = images.split(',');
                var img_to_show = document.location.origin+"/media/review_images/"+images_arr[1];
                
                if(equipment =='' && brand =='' && prd_model =='' && prd_specification =='' && condition =='' && images == '' ){
                    return html;
                }

                html = html + '<div class="traded_item"><div class="traded_item_inside_block">	<div class="traded_item_inside_row">	<div class="left_image_block">	<img src="'+img_to_show+'" /></div><div class="right_info_block">	<div class="tradein_heading">	'+brand+' '+equipment+'</div><div class="other_info"><div class="tradein_prd_model"><span class="title">model</span><span class="val">'+prd_model+'</span></div><div class="tradein_condition"><span class="title">condition</span><span class="val">'+condition+'</span></div><div class="tradein_prd_specification"><span class="title">specification</span><span class="val">'+prd_specification+'</span></div></div></div><div class="delete_prd_tradein"></div></div></div><div class="trade_item_short hide"><div class="equipment">'+equipment+'</div><div class="brand">'+brand+'</div><div class="prd_model">'+prd_model+'</div><div class="prd_specification">'+prd_specification+'</div><div class="condition">'+condition+'</div><div class="images">'+images+'</div><div class="cust_id">'+cust_id+'</div><div class="email">'+email+'</div><div class="name">'+name+'</div><div class="phone">'+phone+'</div><div class="purchase_date">'+purchase_date+'</div><div class="invoice_upload">'+invoice_upload+'</div><div class="order_id">'+order_id+'</div><div class="seller_name">'+seller_name+'</div><div class="purchased_from">'+purchased_from+'</div></div></div>';
                return html;
            }
//            Ends script for adding new equipment---------------------------------------------
            
            
            
            
            jQuery('.list_to_use').find('.value_button').click(function(){
                if( jQuery(this).hasClass('active') ){  
                    jQuery(this).removeClass('active');
                    jQuery(this).parent().parent('.list_to_use').siblings('input').val('');
                }else{
                    jQuery(this).parent().parent('.list_to_use').find('.value_button').removeClass('active');
                    jQuery(this).addClass('active');
                    //alert( jQuery(this).text() );
                    jQuery(this).parent().parent('.list_to_use').siblings('input').val( jQuery(this).text() );
                }
            });
            
            jQuery('#tradein-form').submit(function () {
                var maxImages = 5;
                var maxFileSize = 2 * 1024 * 1024; // 2MB
                var allowedExtensions = ['jpg', 'jpeg', 'png'];

                var fileInput = jQuery('#tradein-images')[0];
                var files = fileInput.files;

                if (files.length < 2 || files.length > maxImages) {
                    alert('Please select 2 to 5 images.');
                    return false;
                }

                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var fileSize = file.size;
                    var fileExtension = file.name.split('.').pop().toLowerCase();

                    if (jQuery.inArray(fileExtension, allowedExtensions) === -1) {
                        alert('Please upload images in JPG, JPEG, or PNG format only.');
                        return false;
                    }

                    if (fileSize > maxFileSize) {
                        alert('Please make sure the file size is not more than 2MB.');
                        return false;
                    }
                }

                return true;
            });
            //-------------------drag drop image upload----------
            jQuery("html").on("dragover", function(e) {
              e.preventDefault();
              e.stopPropagation();
             
          });
    
          jQuery("html").on("drop", function(e) { e.preventDefault(); e.stopPropagation(); });
    
          // Drag enter        
            jQuery("#tradein-form").on("dragenter", ".review-drag-area", function(e){ 
              e.stopPropagation();
              e.preventDefault();
          });
    
          // Drag over         
            jQuery("#tradein-form").on("dragover", ".review-drag-area", function(e){ 
              e.stopPropagation();
              e.preventDefault();
          });
    
          // Drop         
            jQuery("#tradein-form").on("drop", ".review-drag-area", function(e){                 
              e.stopPropagation();
              e.preventDefault();
              var file = e.originalEvent.dataTransfer.files;
              var ins = file.length;  
              var ln = jQuery(".review-resultimg").length; 
              var tc = 0;
              if(ln){ tc = parseInt(ln) + parseInt(tc);}   
              if(ins){ tc = parseInt(ins) + parseInt(tc);}          
              if(tc >10){
                jQuery("#media_field-error").show();
              }else{
                jQuery("#media_field-error").hide();
                for (var x = 0; x < ins; x++) {
                        var fdata = new FormData();   
                        fdata.append("files[]", file[x]);
                        uploadlogofunc(fdata); 
                }
              }
          });  
          function uploadlogofunc(fdata) {             
           if(fdata != null && fdata != ''){
            jQuery.ajax({
                url: "/reviewpopup/index/ajaxupload",
                type: "POST",
                cache: false,
                contentType: false,
                processData: false,
                data: fdata,      
                dataType: 'json',
                showLoader: true,
                success:function(res){
                  if(res.path){ 
                    htmlstr = '<div class ="review-resultimg" ><img src = "/media/review_images'+res.path+'"><div class="review-img-cross" rel = "'+res.path+'">&#10006;</div>';                   
                    htmlstr = htmlstr + '<input type="hidden" name = "review_media_files[]" value ="'+ res.path +'"></div>'; 
                    jQuery(htmlstr).insertBefore("#review-drag-area"); 
                                
                  }
                },
                error: function(xhr, status, error) {alert (error);}
            });  
          }
          } 
          jQuery("#tradein-form").on("click", ".review-dragdrop", function(){ 
               jQuery("#review_media").trigger('click');
            }); 
            jQuery("#tradein-form").on("click", ".review-img-cross", function(){  
                var el = jQuery(this).parent(".review-resultimg");
                var rel = jQuery(this).attr('rel');
                var path = 'review_images' + rel;
                jQuery.ajax({
                    url: "/reviewpopup/index/deleteajaxupload",
                    type: "POST",
                    data: 'path='+path,      
                    dataType: 'json',
                    showLoader: true,
                    success:function(res){
                        el.remove();
                        jQuery("#media_field-error").hide();
                    }
                }); 
            })
            jQuery(document).on('change','#review_media',function(){
                var files = jQuery(this)[0].files;
                var ins = files.length;
                var tc = 0;
                var ln = jQuery(".review-resultimg").length; 
                if(ln){ tc = parseInt(ln) + parseInt(tc);}   
                if(ins){ tc = parseInt(ins) + parseInt(tc);}  
                if(tc > 10){
                    jQuery(this).val('');
                    jQuery("#media_field-error").show();
                }else{
                    for (var x = 0; x < ins; x++) {
                        var fdata = new FormData();   
                        fdata.append("files[]", document.getElementById('review_media').files[x]);
                        uploadlogofunc(fdata);                              
                    }
                    jQuery("#media_field-error").hide();
                }
            });
            //---------------------------------------------------
        });
    });