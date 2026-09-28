//account menu dropdown on header
require(['jquery', 'bootstrap'],function(jQuery){
    jQuery(document).ready(function() {
        function checknumbervalidation(number , length , text){
            if(number == ''){
                var error_message = text +" can not be empty.";
            }else if( number.length < length ){
//                var error_message = "number is not "+length+" character";
                var error_message = text+" must be at least of "+length+" digits.";
            }else{
                error_message = '';
            }
            return error_message;
        }
        
        function changetootpagain(){
            
            //change html of buttons and page after send otp
            jQuery("input#login_input").prop('disabled', true);
            jQuery('.after_otp_sent').show();
            jQuery('#loginbyphone_submit').addClass('resend_otp');
            jQuery('.submit_button').addClass('remove_button');
            jQuery('.message_loginbyphone').hide();
            
            var seconds = 60;
            jQuery('.otp_message').show();
            jQuery('.otp_message').children('span').text( seconds );
            
            var setIntervalotp = setInterval(function(){
                if( parseInt(jQuery('.otp_message > span').text()) > 0 ){
                    jQuery('.otp_message').children('span').text( parseInt(jQuery('.otp_message').children('span').text()) - 1 );
                    jQuery('#loginbyphone_submit').hide();
                }else{
                    jQuery('#loginbyphone_submit').show();
                    jQuery('.otp_message').hide();
                    jQuery('#loginbyphone_submit').val('Resend OTP');
                    jQuery('.submit_button').show();
                    clearInterval(setIntervalotp);
                }
            } , 1000);
        }
        
        
        
        jQuery('#loginbyphone_submit').click(function(e){

            var phone = jQuery('#login_input').val();
            var number_length = 10
            error_message = checknumbervalidation(phone , number_length, 'Phone Number');
            
            if(error_message == '' ){
                //no error, now send sms
                var url = jQuery('#base_url').val() + "loginbyphone/loginpage/sendotp";
//                jQuery('.message_loginbyphone').html('Finding Number...');
                
                jQuery.ajax({
                    url: url,
                    type: "POST",
                    showLoader: true,
                    data: { phone: phone },
                    success: function (response) { 
                        
                        var json = JSON.stringify( response.html );
                        json = json.replace(/"/g, '');
                        var id = JSON.stringify( response.id );
                        id = id.replace(/"/g, '');
                        var otp_count = JSON.stringify( response.otp_count );
                        otp_count = otp_count.replace(/"/g, '');
                        
                        var message;
                        var msgClass = 'error';
                        if(json != ''){
                            var result = json.split(':');
                            var message_type = result[0];
                            var message_msg = result[1];
                            if( message_type == 'error' ){
                                message = message_msg;
                            }else if( message_type == 'success' ){
                                msgClass = 'success';
                                if( otp_count == 'single' ){
//                                    message = "OTP was successfully added to database!";
                                    message = "OTP was successfully sent to entered Phone Number!";
        //                             $return_html .= '<div class="message">OTP was successfully added to database!</div>';

                                    //replace text and hide for 60 seconds
                                    jQuery('.otp_message').show();
                                    jQuery('.otp_message > span').text(60);
                                    jQuery('.submit_button').hide();

                                    changetootpagain();
                                }else if( otp_count == 'multiple' ){
                                    message = "OTP was successfully sent again to entered Phone Number!";
                                    changetootpagain();
                                }
                                message = message_msg;
                               // message = message + "<br>Customer was found, Customer ID: "+id;   
                            }
                        }
                        
                        jQuery('.message_loginbyphone').html( message );
                        
                        jQuery('.loginbyphone.message').addClass(msgClass);
                        jQuery('.loginbyphone.message').html("<div>"+ message +"</div>");
                        
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('error');
                        console.log(textStatus, errorThrown);
                    }
                });
                
            }else{
                //error
                jQuery('.message_loginbyphone').html(error_message);
                jQuery('.loginbyphone.message').addClass('error');
                jQuery('.loginbyphone.message').html("<div>"+ error_message +"</div>");
            }
        });
        
        //check otp and logged in customer
        jQuery('#loginbyphone_enter_otp_submit').click(function(e){
            var phone = jQuery('#login_input').val();
            var loginbyphone_otp = jQuery('#loginbyphone_otp').val();
            var otp_length = 6;
            var phone_length = 6;
            
            var error_message_otp = checknumbervalidation( loginbyphone_otp , otp_length , 'OTP');
            var error_message_phone = checknumbervalidation( phone , phone_length , 'Phone Number');
            
//            jQuery('#loginbyphone_enter_otp_submit').val('Checking...');
            
            if( error_message_otp == '' && error_message_phone == ''  ){
                //no error, now send sms
                var base_url = jQuery('#base_url').val();
                var url = base_url + "loginbyphone/loginpage/Loggingin";
                
                jQuery.ajax({
                    url: url,
                    type: "POST",
                    data: { phone: phone, otp: loginbyphone_otp },
                    success: function (response) { 
                        
                        var html = JSON.stringify( response.html );
                        html = html.replace(/"/g, '');
                        var found_otp = JSON.stringify( response.found_otp );
                        found_otp = found_otp.replace(/"/g, '');
                        var result = html.split(':');
                        var message_type = result[0];
                        var message_msg = result[1];
                        
                        if(found_otp == '1'){
                            jQuery('#loginbyphone_enter_otp_submit').val('Logging you in...');                    
                            location.reload();  
                        }else{
                            jQuery('.message_after_send_otp').html( message_msg );
                            jQuery('#loginbyphone_enter_otp_submit').val('Login');
                        }
                        
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        
                        console.log(textStatus, errorThrown);
                    }
                });
                
            }else{
                //error
                jQuery('.message_after_send_otp').html( error_message_phone + "<br>" + error_message_otp);
                jQuery('#loginbyphone_enter_otp_submit').val('Login');
            }
            
        });
    });
})
