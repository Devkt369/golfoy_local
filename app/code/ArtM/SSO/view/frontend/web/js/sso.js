require(["jquery","mage/cookies"], function (jQuery) {
    jQuery(document).ready(function () {

        var BASE_URL = window.BASE_URL;
        var LOGO_URL = BASE_URL + "media/logo/stores/1/Golfoy.png"; 

        function checkBodyClass() {
            var inputValueLengthLogin = jQuery('body').hasClass('customer-account-login');
            var inputValueLengthCreate = jQuery('body').hasClass('customer-account-create');
            var inputValueLengthcheckout = jQuery('body').hasClass('checkout-index-index');
            if (inputValueLengthLogin || inputValueLengthCreate || inputValueLengthcheckout) {
                jQuery(".modal.fade.not-login-page").remove();
                jQuery(".signin_signout_blockoutside.not-logged-in.sso-model-popup").addClass('active-login-page');
            } else {
                jQuery(".signin_signout_blockoutside.not-logged-in.sso-model-popup").removeClass('active-login-page');
            }

        }
        setInterval(checkBodyClass, 1000);

        jQuery(document).on(
            "click",
            ".hidden-sm.hidden-xs.authorization-link.signin_signout.sso, .sso-model-popup , .hidden-sm.hidden-xs.register-link.create_an_account , .account_icon_header.header_icon.customer-not-logged-in",
            function (e) {
                e.preventDefault();
                jQuery(".icon_list_here.popup_account").hide();
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "signon";
                var loggedIn = '<img class="success_image" src="'+BASE_URL+'media/sales/download.gif">';
                jQuery.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        pagetype: pagetype,
                    },
                    showLoader: true,
                    success: function (response) {
                        jQuery("#modal-body_sso").html(response.content);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        // alert("error");
                        console.log(textStatus, errorThrown);
                        console.log("1error" + response);
                        console.log("2error" + jqXHR);
                    },
                });
            }
        );

        function sendId(url, pagetype, formData) {
            var loader = jQuery('<img class="loading" src="'+BASE_URL+'pub/media/loader.gif">'
            );
            var loggedIn ='<img class="success_image" src="'+BASE_URL+'media/sales/download.gif">';
            // jQuery(".modal-content #modal-body_sso").html(loader);
            jQuery(".loading-mask").css("display", "block");
            jQuery(".loading-mask").css("z-index", "999999999");

            var requestData = {
                pagetype: pagetype,
                formData: formData,
            };

            jQuery.ajax({
                url: url,
                method: "POST",
                dataType: "json",
                data: requestData,
                showLoader: true,
                success: function (response) {
                    // loader.remove();
                    var $html = jQuery(response.content);

                    if ($html.hasClass("success_image")) {
                        jQuery.removeCookie("new-user-email-password");
                        jQuery.removeCookie("add-mobilenumber");
                        jQuery.removeCookie("new-user-email");
                        jQuery.removeCookie("userid");
                        setTimeout(function () {
                            location.reload(true);
                        }, 1000);
                    }
                    jQuery(".loading-mask").css("display", "none");
                    jQuery("#modal-body_sso").html(response.content);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    // loader.remove();
                    // alert("error");
                    console.log(textStatus, errorThrown);
                    console.log("1error" + response);
                    console.log("2error" + jqXHR);
                },
            });
        }

        // enter mobile number form action
        jQuery(document).on("submit", "#login-number", function (e) {
            e.preventDefault();
            if (jQuery(".sbmt_btn").hasClass("active")) {
                var formData = jQuery("#mobileNumber").val();

                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_otp";
                sendId(url, pagetype, formData);
            } else {
                console.log("enter valid number");
            }
        });

        // otp submit form action
        jQuery(document).on("submit", "#otp_form", function (e) {
            e.preventDefault();
            var formData = "";
            jQuery('.input_field_box input[type="number"]').each(function () {
                formData += jQuery(this).val(); // Concatenate the value of each input field
            });
            var url = BASE_URL+"signon/gethtml/index/";
            var pagetype = "valid_otp";
            sendId(url, pagetype, formData);
        });

        // resend OTP
        function getCookieValue(cookieName) {
            var name = cookieName + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var cookieArray = decodedCookie.split(";");

            for (var i = 0; i < cookieArray.length; i++) {
                var cookie = cookieArray[i].trim();
                if (cookie.indexOf(name) === 0) {
                    return cookie.substring(name.length, cookie.length);
                }
            }
            return null;
        }

        jQuery(document).on("click", "#resend_otp", function (e) {
            e.preventDefault();
            var formData = getCookieValue("userid");
            if (formData !== null) {
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_otp";
                sendId(url, pagetype, formData);
            } else {
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "signon";
                sendId(url, pagetype, formData);
            }
        });

        jQuery(document).on("click", "div#resend_otp_forgot", function (e) {
            e.preventDefault();
            var formData = getCookieValue("forgot-password-mobilenumber");
            if (formData !== null) {
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "forgot_password_otp";
                sendId(url, pagetype, formData);
            } else {
                var useForgotPasswordForm = `<button class="revert-back" id="back-to-use_email">Back</button>
                                            <div class="sign-up-forms-details">
                                            <div class="login-heading">
                                            <img src="${LOGO_URL}" title="Golfoy.com: India Online Golf Shop" alt="Golfoy.com India Online Golf Shop" loading="lazy" width="403" height="103">         
                                                <p>Please enter your registered mobile number.</p>
                                            </div>
                                            <div class="mobile-number-login-create">
                                                <form action="" method="post" id="forgot-password">
                                                    <div class="mobile-number-login__">+91</div>
                                                    <input type="text" inputmode="numeric" maxlength="10"  class="new-mobile-number" name="mobileNumber" placeholder="Enter Mobile Number" autocomplete="off" id="forgot-password-mobile" required>
                                                    <input type="hidden" name="pagetype" value="forgot_password_otp" class="new-number-pagetype">
                                                    <button type="submit" id="number_input">Send OTP</button>
                                                </form>    
                                            </div>
                                        </div>`;
                jQuery("#modal-body_sso").html(useForgotPasswordForm);
            }
        });

        jQuery(document).on("click", "#resend_otp_create", function (e) {
            e.preventDefault();
            var formData = getCookieValue("userid");
            if (formData !== null) {
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_otp_registration";
                sendId(url, pagetype, formData);
            } else {
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "signon";
                sendId(url, pagetype, formData);
            }
        });

        jQuery(document).on("click", "#edit_number", function (e) {
            e.preventDefault();
            jQuery.removeCookie("userid");
            var useNumberForm = `<div class="sign-up-forms-details">
                                      <form name="login-number" id="login-number" action="#">
                                          <div class="sign-up-content">
                                              <div class="login-heading">
                                              <img src="${LOGO_URL}" title="Golfoy.com: India Online Golf Shop" alt="Golfoy.com India Online Golf Shop" loading="lazy" width="403" height="103">
                                              <p class="normal-login-popup">Please enter your mobile number to <br> Sign In / Sign Up</p>
                                                  
                                              </div>
                                              <div class="input_form_div">
                                                  <div class="form-group">
                                                      <div class="mobile-number-login__dateSlct">+91</div>
                                                      <input type="text" inputmode="numeric" 
                                                          oninput="javascript: if (this.value.length > 10) this.value = this.value.slice(0, 10);"
                                                          maxlength="10" class="mobile-number-login__form_control" name="mobileNumber"
                                                          placeholder="Enter Mobile Number" autocomplete="off" id="mobileNumber" value="">
                                                  </div>
                                              </div>
                                              <h2 class="use-email" id="use_email">Sign in using E-mail</h2>
                                              <div class="terms-condition-content">
                                                  <p>By continuing, I agree to <a href="${BASE_URL}terms-and-conditions" class="acnhor_page">Terms of Service</a><a href="${BASE_URL}privacy-policy-cookie-restriction-mode"
                                                  class="acnhor_page"> & Privacy Policy </a>
                                                  </p>
                                              </div>
                                              <div class="submit_form_button">
                                                  <button class="sbmt_btn" type="">continue</button>
                                              </div>
                                              
                                          </div>
                                      </form>
                                  </div>`;
            jQuery("#modal-body_sso").html(useNumberForm);
        });

        jQuery(document).on("click", "#edit_number_create", function (e) {
            e.preventDefault();
            var formData = jQuery(this).serialize();
            var url = BASE_URL+"signon/gethtml/index/";
            var pagetype = "enter_number";
            sendId(url, pagetype, formData);
        });

        jQuery(document).on("click", "#use_email", function (e) {
            e.preventDefault();
            var useEmailForm = `<button class='revert-back' id='back-to-use_number'>Back</button>
                                      <div class='sign-up-forms-details'>
                                          <div class='sign-up-content'>
                                              <div class='login-heading'>
                                              <img src='${LOGO_URL}' title='Golfoy.com: India Online Golf Shop' alt='Golfoy.com India Online Golf Shop' loading='lazy' width='403' height='103'>         
                                              </div>
                                              <div class='enter_email'> 
                                                  <form action='' method='post'  id='enter_email'>
                                                  <div class='form-group'>
                                                      <input type='email' name='email' class='email-input' placeholder='Enter email' required>
                                                      <lable for='email' class='email-label'>E-mail Address</lable>
                                                  </div>
                                                  <div class='form-group'>
                                                      <input type='password' name='password' maxlength='20' class='password-input' placeholder='Password' required>
                                                      <label for='password' class='password-label'>Password</label>
                                                  </div>
                                                      <span toggle='#password-field' class='fa fa-fw fa-eye-slash field_icon toggle-password'></span>
      
                                                      <input type='hidden' name='pagetype' value='enter_password'>
                                                      <div class='custom-button'>
                                                          <h2 class='forget-password' id='forget-password'>Forgot Password</h2>
                                                          <h2 class='use-number' id='use_number'>Use Mobile Number</h2>
                                                      </div>
                                                      <div class='terms-condition-content'>
                                                          <p>By clicking on Log In, I agree to <a href='${BASE_URL}terms-and-conditions' class='acnhor_page'>Terms of Service </a>& <a href='${BASE_URL}privacy-policy-cookie-restriction-mode'
                                                          class='acnhor_page'>Privacy Policy </a>
                                                      </div>
                                                      <button type='submit' class='button-submit'>Log In</button>
      
                                                  </form>
                                              </div>
                                          </div>
                                      </div>`;
            jQuery("#modal-body_sso").html(useEmailForm);
        });

        jQuery(document).on("submit", "#enter_email", function (e) {
            e.preventDefault();
            var formData = jQuery("#enter_email .email-input").val();

            if (!isValidEmail(formData)) {
                jQuery(".error").remove();
                jQuery(".login-heading").append(
                    '<p class="error">Not a valid email address !</p>'
                );
                return; // Exit the function
            }

            jQuery.cookie("new-user-email", formData, {
                expires: 1 / 48,
                path: "/",
            });
            var passwordData = jQuery("#enter_email .password-input").val();
            if (passwordData.length >= 6) {
                jQuery.cookie("new-user-email-password", passwordData, {
                    expires: 1 / 48,
                    path: "/",
                });
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_password";
                sendId(url, pagetype, formData);
            } else {
                jQuery(".error").remove();
                jQuery(".login-heading").append(
                    '<p class="error">Minimum 6 Letter</p>'
                );
            }
        });

        jQuery(document).on("submit", "#enter_password", function (e) {
            e.preventDefault();
            var formData = jQuery("#enter_password .password-input").val();
            var url = BASE_URL+"signon/gethtml/index/";
            var pagetype = "validate_password";

            sendId(url, pagetype, formData);
        });

        jQuery(document).on("submit", "#enter_number", function (e) {
            e.preventDefault();
            var formData = jQuery("#enter_number .mobile-number").val();
            var url = BASE_URL+"signon/gethtml/index/";
            var pagetype = "validate_password";

            sendId(url, pagetype, formData);
        });

        jQuery(document).on("submit", "#create-account-otp", function (e) {
            e.preventDefault();
            var formData = jQuery(
                "#create-account-otp .new-mobile-number"
            ).val();
            var newCustomerName = jQuery(
                "#create-account-otp .first-name"
            ).val();
            jQuery.removeCookie("userid");
            jQuery.removeCookie("new-user-name");
            jQuery.cookie("userid", formData, {
                expires: 1 / 48,
                path: "/",
            });
            jQuery.cookie("new-user-name", newCustomerName, {
                expires: 1 / 48,
                path: "/",
            });
            if (formData.length == 10) {
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_otp_registration";
                sendId(url, pagetype, formData);
            } else {
                jQuery(".error").remove();
                jQuery(".login-heading").append(
                    '<p class="error">Please enter valid number</p>'
                );
            }
        });

        jQuery(document).on("submit", "#otp_validation_form", function (e) {
            e.preventDefault();
            var formData = "";
            jQuery('.input_field_box input[type="number"]').each(function () {
                formData += jQuery(this).val();
            });
            var url = BASE_URL+"signon/gethtml/index/";
            var pagetype = "registration_otp_validation";

            sendId(url, pagetype, formData);
        });

        jQuery(document).on("submit", "#create_email", function (e) {
            e.preventDefault();
            var formData = jQuery("#notregisteredemail").val();

            if (!isValidEmail(formData)) {
                jQuery(".error").remove();
                jQuery(".login-heading").append(
                    '<p class="error">Not a valid email address !</p>'
                );
                return; // Exit the function
            }

            var passwordData = jQuery("#notregisteredemailpassword").val();
            var customerName = jQuery("#create_email .first-name").val();
            if (passwordData.length >= 6) {
                jQuery.cookie("new-user-email", formData, {
                    expires: 1 / 48,
                    path: "/",
                });
                jQuery.cookie("new-user-email-password", passwordData, {
                    expires: 1 / 48,
                    path: "/",
                });
                jQuery.cookie("new-user-name", customerName, {
                    expires: 1 / 48,
                    path: "/",
                });
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_golfoy";
                sendId(url, pagetype, formData);
            } else {
                jQuery(".error").remove();
                jQuery(".login-heading").append(
                    '<p class="error">Minimum 6 Letter</p>'
                );
            }
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        jQuery(document).on("click", "#use_number", function (e) {
            e.preventDefault();
            var useNumberForm = `<div class="sign-up-forms-details">
                                  <form name="login-number" id="login-number" action="#">
                                      <div class="sign-up-content">
                                          <div class="login-heading">
                                          <img src="${LOGO_URL}" title="Golfoy.com: India Online Golf Shop" alt="Golfoy.com India Online Golf Shop" loading="lazy" width="403" height="103">
                                          <p class="normal-login-popup">Please enter your mobile number to <br> Sign In / Sign Up</p>
                                          </div>
                                          <div class="input_form_div">
                                              <div class="form-group">
                                                  <div class="mobile-number-login__dateSlct">+91</div>
                                                  <input type="text" inputmode="numeric" 
                                                      oninput="javascript: if (this.value.length > 10) this.value = this.value.slice(0, 10);"
                                                      maxlength="10" class="mobile-number-login__form_control" name="mobileNumber"
                                                      placeholder="Enter Mobile Number" autocomplete="off" id="mobileNumber" value="">
                                              </div>
                                          </div>
                                          <h2 class="use-email" id="use_email">Sign in using E-mail</h2>
                                          <div class="terms-condition-content">
                                              <p>By continuing, I agree to <a href="${BASE_URL}terms-and-conditions" class="acnhor_page">Terms of Service</a><a href="${BASE_URL}privacy-policy-cookie-restriction-mode"
                                              class="acnhor_page"> & Privacy Policy </a>
                                              </p>
                                          </div>
                                          <div class="submit_form_button">
                                              <button class="sbmt_btn" type="">continue</button>
                                          </div>
                                          
                                      </div>
                                  </form>
                              </div>`;
            jQuery("#modal-body_sso").html(useNumberForm);
        });

        jQuery(document).on("submit", "form#add-phonenumber", function (e) {
            e.preventDefault();
            var formData = jQuery("#add-mobilenumber").val();
            jQuery.removeCookie("add-mobilenumber");
            jQuery.cookie("add-mobilenumber", formData, {
                expires: 1 / 48,
                path: "/",
            });
            var url = BASE_URL+"signon/gethtml/index/";
            var pagetype = "add_phonenumber_otp";
            sendId(url, pagetype, formData);
        });

        jQuery(document).on(
            "submit",
            "form#add_phonenumber_otp_form",
            function (e) {
                e.preventDefault();
                var formData = "";
                jQuery('.input_field_box input[type="number"]').each(
                    function () {
                        formData += jQuery(this).val();
                    }
                );

                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "add_phonenumber_validation";
                sendId(url, pagetype, formData);
            }
        );

        jQuery(document).on("click", "button#back-to-use_number", function (e) {
            e.preventDefault();
            jQuery.removeCookie("new-user-email-password");
            jQuery.removeCookie("new-user-email");

            var useNumberForm = `<div class="sign-up-forms-details">
                                          <form name="login-number" id="login-number" action="#">
                                          <div class="sign-up-content">
                                              <div class="login-heading">
                                              <img src="${LOGO_URL}" title="Golfoy.com: India Online Golf Shop" alt="Golfoy.com India Online Golf Shop" loading="lazy" width="403" height="103">
                                              <p class="normal-login-popup">Please enter your mobile number to <br> Sign In / Sign Up</p>
                                              </div>
                                              <div class="input_form_div">
                                                  <div class="form-group">
                                                      <div class="mobile-number-login__dateSlct">+91</div>
                                                      <input type="text" inputmode="numeric" 
                                                          oninput="javascript: if (this.value.length > 10) this.value = this.value.slice(0, 10);"
                                                          maxlength="10" class="mobile-number-login__form_control" name="mobileNumber"
                                                          placeholder="Enter Mobile Number" autocomplete="off" id="mobileNumber" value="">
                                                  </div>
                                              </div>
                                              <h2 class="use-email" id="use_email">Sign in using E-mail</h2>
                                              <div class="terms-condition-content">
                                                  <p>By continuing, I agree to <a href="${BASE_URL}terms-and-conditions" class="acnhor_page">Terms of Service</a><a href="${BASE_URL}privacy-policy-cookie-restriction-mode"
                                                  class="acnhor_page"> & Privacy Policy </a>
                                                  </p>
                                              </div>
                                              <div class="submit_form_button">
                                                  <button class="sbmt_btn" type="">continue</button>
                                              </div>
                                              
                                          </div>
                                      </form>
                                  </div>`;
            jQuery("#modal-body_sso").html(useNumberForm);
            // var formData = "";
            // var url = BASE_URL+"signon/gethtml/index/";
            // var pagetype = "signon";
            // sendId(url, pagetype, formData);
        });

        jQuery(document).on(
            "click",
            "button#back-to-enter_email",
            function (e) {
                e.preventDefault();
                jQuery.removeCookie("new-user-email-password");
                jQuery.removeCookie("new-user-email");
                var formData = "";
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "enter_email";
                sendId(url, pagetype, formData);
            }
        );

        jQuery(document).on(
            "click",
            "button#back-to-forgot_password",
            function (e) {
                e.preventDefault();
                jQuery.removeCookie("forgot-password-mobilenumber");
                jQuery.removeCookie("new-user-email-password");
                jQuery.removeCookie("new-user-email");
                jQuery.removeCookie("Userid");
                var formData = "";
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "forgot_password";
                sendId(url, pagetype, formData);
            }
        );

        jQuery(document).on(
            "click",
            ".add-phonenumber-edit-button #add-phonenumber-edit",
            function (e) {
                e.preventDefault();
                jQuery.removeCookie("add-mobilenumber");
                var formData = "";
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "add_phonenumber";
                sendId(url, pagetype, formData);
            }
        );

        jQuery(document).on(
            "click",
            ".add-phonenumber-edit-button #add-phonenumber-resend_otp",
            function (e) {
                e.preventDefault();
                var formData = getCookieValue("add-mobilenumber");
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "add_phonenumber_otp";
                sendId(url, pagetype, formData);
            }
        );

        jQuery(document).on("click", "#back-to-use_email", function (e) {
            e.preventDefault();
            jQuery.removeCookie("new-user-email-password");
            jQuery.removeCookie("new-user-email");
            var useEmailForm = `<button class='revert-back' id='back-to-use_number'>Back</button>
                                          <div class='sign-up-forms-details'>
                                              <div class='sign-up-content'>
                                                  <div class='login-heading'>
                                                  <img src='${LOGO_URL}' title='Golfoy.com: India Online Golf Shop' alt='Golfoy.com India Online Golf Shop' loading='lazy' width='403' height='103'>         
                                                  </div>
                                                  <div class='enter_email'> 
                                                      <form action='' method='post'  id='enter_email'>
                                                      <div class='form-group'>
                                                          <input type='email' name='email' class='email-input' placeholder='Enter email' required>
                                                          <lable for='email' class='email-label'>E-mail Address</lable>
                                                      </div>
                                                      <div class='form-group'>
                                                          <input type='password' name='password' maxlength='20' class='password-input' placeholder='Password' required>
                                                          <label for='password' class='password-label'>Password</label>
                                                      </div>
                                                          <span toggle='#password-field' class='fa fa-fw fa-eye-slash field_icon toggle-password'></span>
      
                                                          <input type='hidden' name='pagetype' value='enter_password'>
                                                          <div class='custom-button'>
                                                              <h2 class='forget-password' id='forget-password'>Forgot Password</h2>
                                                              <h2 class='use-number' id='use_number'>Use Mobile Number</h2>
                                                          </div>
                                                          <div class='terms-condition-content'>
                                                              <p>By clicking on Log In, I agree to <a href='${BASE_URL}terms-and-conditions' class='acnhor_page'>Terms of Service </a>& <a href='${BASE_URL}privacy-policy-cookie-restriction-mode'
                                                              class='acnhor_page'>Privacy Policy </a>
                                                          </div>
                                                          <button type='submit' class='button-submit'>Log In</button>
      
                                                      </form>
                                                  </div>
                                              </div>
                                          </div>`;
            jQuery("#modal-body_sso").html(useEmailForm);
            // var formData = "";
            // var url = BASE_URL+"signon/gethtml/index/";
            // var pagetype = "enter_email";
            // sendId(url, pagetype, formData);
        });

        jQuery(document).on("click", "h2#forget-password", function (e) {
            e.preventDefault();
            jQuery.removeCookie("forgot-password-mobilenumber");
            jQuery.removeCookie("new-user-email-password");
            jQuery.removeCookie("new-user-email");
            jQuery.removeCookie("userid");
            var useForgotPasswordForm = `<button class="revert-back" id="back-to-use_email">Back</button>
                              <div class="sign-up-forms-details">
                              <div class="login-heading">
                              <img src="${LOGO_URL}" title="Golfoy.com: India Online Golf Shop" alt="Golfoy.com India Online Golf Shop" loading="lazy" width="403" height="103">         
                                  <p>Please enter your registered mobile number.</p>
                              </div>
                              <div class="mobile-number-login-create">
                                  <form action="" method="post" id="forgot-password">
                                      <div class="mobile-number-login">+91</div>
                                      <input type="text" inputmode="numeric" maxlength="10"  class="new-mobile-number" name="mobileNumber" placeholder="Enter Mobile Number" autocomplete="off" id="forgot-password-mobile" required>
                                      <input type="hidden" name="pagetype" value="forgot_password_otp" class="new-number-pagetype">
                                      <button type="submit" id="number_input">Send OTP</button>
                                  </form>    
                              </div>
                          </div>`;
            jQuery("#modal-body_sso").html(useForgotPasswordForm);
            // var formData = "";
            // var url = BASE_URL+"signon/gethtml/index/";
            // var pagetype = "forgot_password";
            // sendId(url, pagetype, formData);
        });

        jQuery(document).on("submit", "form#forgot-password", function (e) {
            e.preventDefault();
            var formData = jQuery("#forgot-password-mobile").val();
            if (formData.length == 10) {
                jQuery.cookie("forgot-password-mobilenumber", formData, {
                    expires: 1 / 48,
                    path: "/",
                });
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "forgot_password_otp";
                sendId(url, pagetype, formData);
            } else {
                jQuery(".error").remove();
                jQuery(".login-heading").append(
                    '<p class="error">Phone number not valid.</p>'
                );
            }
        });

        jQuery(document).on(
            "submit",
            "form#forgot_password_otp_form",
            function (e) {
                e.preventDefault();
                // var formData = jQuery('#forgot-password-otpvalidation').val();
                var formData = "";
                jQuery('.input_field_box input[type="number"]').each(
                    function () {
                        formData += jQuery(this).val(); // Concatenate the value of each input field
                    }
                );
                var url = BASE_URL+"signon/gethtml/index/";
                var pagetype = "forgot_password_validation";
                sendId(url, pagetype, formData);
            }
        );

        jQuery(document).on(
            "submit",
            "form#forgot_password_enter",
            function (e) {
                e.preventDefault();
                var formData1 = jQuery(
                    "#forgot_password_enter .password-input1"
                ).val();
                var formData2 = jQuery(
                    "#forgot_password_enter .password-input2"
                ).val();
                if (formData1 == formData2 && formData2.length > 5) {
                    var formData = formData2;
                    var url = BASE_URL+"signon/gethtml/index/";
                    var pagetype = "set_new_password";
                    sendId(url, pagetype, formData);
                } else {
                    jQuery(".error").remove();
                    if (formData1.length < 6 || formData2.length < 6) {
                        jQuery(".error").remove();
                        jQuery(".login-heading").append(
                            '<p class="error">Password should be minimum 6 digits</p>'
                        );
                    } else {
                        jQuery(".error").remove();
                        jQuery(".login-heading").append(
                            '<p class="error">Passwords do not match</p>'
                        );
                    }
                }
            }
        );

        jQuery(document).on("click", ".toggle-password", function (e) {
            jQuery(this).toggleClass("fa-eye fa-eye-slash");
            // var input = jQuery("#notregisteredemailpassword");
            var input = jQuery("input.password-input");
            var input2 = jQuery("input.password-input2");
            var input1 = jQuery("input.password-input1");
            input.attr("type") === "password"
                ? input.attr("type", "text")
                : input.attr("type", "password");
            input2.attr("type") === "password"
                ? input2.attr("type", "text")
                : input2.attr("type", "password");
            input1.attr("type") === "password"
                ? input1.attr("type", "text")
                : input1.attr("type", "password");
        });

        jQuery(document).on("input", "#mobileNumber", function () {
            var input = jQuery(this);
            var button = jQuery(".sbmt_btn");
            var characterCount = input.val().length;
            if (characterCount === 10) {
                button.addClass("active");
            } else {
                button.removeClass("active");
            }
        });

        function checkPasswordLength() {
            var password = jQuery(".password-input").val();
            var isValid = password.length >= 6;
            if (isValid) {
                jQuery(".password-error").text("");
            } else {
                jQuery(".password-error").text(
                    "Password must be at least 6 characters long."
                );
            }
            return isValid;
        }

        jQuery(document).on(
            "click",
            "#shipping-new-address-form",
            function (e) {
                if (
                    jQuery("input[name='telephone']").attr("inputmode") !=
                    "numeric"
                ) {
                    jQuery("input[name='telephone']").attr(
                        "inputmode",
                        "numeric"
                    );
                }
            }
        );

        jQuery(document).on(
            "input",
            ".checkout-index-index input[name='telephone'].input-text , input[name='mobileNumber'] , input[name='telephone'].input-text",
            function (e) {
                jQuery(this).attr("inputmode", "numeric");

                var phoneNumber = jQuery(this).val().trim();

                var isValid = /^\d{0,10}jQuery/.test(phoneNumber);

                var button = jQuery(".sbmt_btn");
                if (phoneNumber.length > 10) {
                    jQuery(this).val(phoneNumber.slice(0, 10));
                    jQuery(".error").hide();
                }
                if (phoneNumber.length == 10) {
                    button.addClass("active");
                    jQuery(".error").hide();
                } else {
                    button.removeClass("active");
                    jQuery(".error").show();
                }

                if (phoneNumber.length == 0) {
                    jQuery(".error").hide();
                }
            }
        );
        /* ---Dev Prevent entering special character and 1 to 5 number in input field--- */
        jQuery(document).on(
            "input",
            '[id="mobileNumber"], [name="mobileNumber"], [name="telephone"]',
            function (event) {
                var number = jQuery(this);
                var enteredValue = number.val();
                if (enteredValue && /^[0-5]/.test(enteredValue)) {
                    number.val("");
                    event.preventDefault();
                } else {
                    number.val(enteredValue.replace(/[^0-9]/g, ""));
                }
            }
        );

        jQuery(document).on(
            "keypress",
            "div#shipping-new-address-form input[name='telephone'].input-text , input[name='mobileNumber'] , input[name='telephone'].input-text",
            function (event) {
                var charCode = event.which ? event.which : event.keyCode;
                if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                    event.preventDefault();
                }
            }
        );

        function checkInputLength() {
            var inputField = jQuery('.input_field_box input[type="number"]');

            if (inputField.length > 0) {
                var inputValueLength = inputField.val();
                if (inputValueLength.length === 6) {
                    jQuery("#submitotp").addClass("active");
                } else {
                    jQuery("#submitotp").removeClass("active");
                }
            }
        }
        setInterval(checkInputLength, 1000);
    });
});
