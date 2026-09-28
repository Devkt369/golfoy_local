require(["jquery", "bootstrap"], function (jQuery) {
    jQuery(document).ready(function () {
        jQuery('.data.item.content').each(function() {
            var dynamicPart = jQuery(this).attr('id');
            if (dynamicPart && dynamicPart.indexOf('amrelated_product_after_related_') === 0) {
                jQuery(this).css('background-color', '#f6f6f6'); 
                var titleDiscription = jQuery(this).attr('id');
                jQuery('.data.item.title#tab-label-' + titleDiscription)[0].style.setProperty('display', 'none', 'important');
            }
        });
        //offer popup on product page js starts here
        var i = 0;
        var inner_container_slider = 0;
        const baseURL = window.BASE_URL;

        var containerHtml = `<div class="product_offer_carausal_outer outer_container_slider_outer hide">
                                <div class="offers_heading_outside">
                                    <div class="offers_heading">
                                        <div class="left_image">
                                            <img src="https://golfoy.com/media/module_theme_images/product_page/discount_icon.svg" />
                                        </div>
                                        <div class="middle_heading_content">
                                            <h2>offers</h2>
                                        </div>
                                        <div class="see_all_button open_offer_popup">
                                            <div>view all</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product_offer_carausal outer_container_slider">
                                    <div class="inner_container_slider">
                                    </div>
                                </div>
                                <div class="offer_slider_arrows">
                                    <div class="offer_slider_arrows_right offer_right"></div>
                                    <div class="offer_slider_arrows_left offer_left"></div>
                                </div>
                            </div>`;
        if(jQuery(".show-available-qty.stock-message").length == 1){
            jQuery('.show-available-qty.stock-message').before(containerHtml);
        }else{
            jQuery('div.box-tocart').before(containerHtml);
        }
        jQuery(".product_offer").each(function () {
            if (jQuery(this).children(".image").length) {
                var image = jQuery(this).children(".image").html();
            } else {
                var image = "";
            }

            if (jQuery(this).children(".url").length) {
                var offer_url = jQuery(this).children(".url").html();
            } else {
                var offer_url = "";
            }

            i = i + 1;
            if (jQuery.trim(image) == "") {
                image =
                    "<img src='"+baseURL+"media/module_theme_images/product_page/discount_icon.svg'/>";
            }

            var heading = jQuery(this).children(".heading").html();
            var content = jQuery(this).children(".content").html();
            if (jQuery(this).children(".content").text().length > 50) {
                var content_short =
                    jQuery(this).children(".content").html().substr(0, 47) +
                    "...";
            } else {
                var content_short = content;
            }

            if (jQuery(this).children(".start_date").length) {
                var start_date = jQuery(this).children(".start_date").html();
            } else {
                var start_date = "";
            }
            if (jQuery(this).children(".end_date").length) {
                var end_date = jQuery(this).children(".end_date").html();
            } else {
                var end_date = "";
            }

            var know_more = jQuery(this).children(".know_more").text();
            if (know_more == "") {
                know_more = "<div class='hid_it'>-</div>";
            }

            //for product page
            var slide_html = GetOfferHtml(
                image,
                heading,
                content_short,
                start_date,
                end_date,
                know_more
            );            
            jQuery(".inner_container_slider").append(slide_html);

            //for popup
            var offers_popup_html = GetOfferHtmlPopup(
                image,
                heading,
                content,
                start_date,
                end_date,
                know_more,
                offer_url
            );
            jQuery(".offers_popup_offers_block_inside").append(
                offers_popup_html
            );

            jQuery("div#offer_popup")
                .find(".modal-content")
                .find(".offers_popup")
                .css("max-height", 250);
        });
        if (i > 0) {
            jQuery(
                ".product_offer_carausal_outer.outer_container_slider_outer"
            ).removeClass("hide");
        }
        jQuery(".inner_container_slider").width(i * 170);

        AdjustArrows();
        jQuery(".outer_container_slider_outer").click(function () {
            AdjustArrows();
        });

        function AdjustArrows() {
            //                console.log( "scrollLeft: " + jQuery('.product_offer_carausal.outer_container_slider').scrollLeft() );
            //                console.log( "width: " + jQuery('.inner_container_slider').width() );

            if (
                jQuery(
                    ".product_offer_carausal.outer_container_slider"
                ).scrollLeft() > 10
            ) {
                jQuery(".offer_slider_arrows_left.offer_left").show();
            } else {
                jQuery(".offer_slider_arrows_left.offer_left").hide();
            }

            if (
                jQuery(
                    ".product_offer_carausal.outer_container_slider"
                ).scrollLeft() >
                jQuery(".inner_container_slider").width() -
                    jQuery(
                        ".product_offer_carausal.outer_container_slider"
                    ).width() -
                    10
            ) {
                jQuery(".offer_slider_arrows_right.offer_right").hide();
            } else {
                jQuery(".offer_slider_arrows_right.offer_right").show();
            }
        }
        jQuery(".product_offer_carausal.outer_container_slider").scroll(
            function () {
                AdjustArrows();
            }
        );
        jQuery(".offer_right").click(function () {
            jQuery(".product_offer_carausal.outer_container_slider").animate(
                {
                    scrollLeft:
                        "+=" +
                        (jQuery(
                            ".product_offer_carausal.outer_container_slider"
                        ).width() -
                            120) +
                        "",
                },
                400
            );
            setTimeout(function () {
                AdjustArrows();
            }, 500);
        });
        jQuery(".offer_left").click(function () {
            jQuery(".product_offer_carausal.outer_container_slider").animate(
                {
                    scrollLeft:
                        "-=" +
                        (jQuery(
                            ".product_offer_carausal.outer_container_slider"
                        ).width() -
                            120) +
                        "",
                },
                400
            );
            setTimeout(function () {
                AdjustArrows();
            }, 500);
        });

        jQuery(".open_offer_popup").on("click", function () {
            jQuery("#offer_popup").show();
            setTimeout(function () {
                jQuery(".page-wrapper").addClass("transit");
                jQuery("#offer_popup").addClass("show_pop");
            }, 100);
        });
        jQuery("#offer_popup button.close").on("click", function () {
            jQuery("#offer_popup").removeClass("show_pop");
            setTimeout(function () {
                jQuery(".page-wrapper").removeClass("transit");
                jQuery("#offer_popup").hide();
            }, 100);
        });

        function GetOfferHtmlPopup(
            image,
            heading,
            content,
            start_date,
            end_date,
            know_more,
            offer_url
        ) {
            var html = "";
            var start_date_class = "";
            var end_date_class = "";
            var offer_url_html = "";

            if (jQuery.trim(start_date) == "") {
                start_date_class = "hide";
            }
            if (jQuery.trim(end_date) == "") {
                end_date_class = "hide";
            }
            if (jQuery.trim(offer_url) == "") {
                offer_url_html = "";
            } else {
                if (jQuery.trim(know_more) == "") {
                    know_more = "Learn More >>";
                }
                offer_url_html =
                    '<div class="product_link"><a target="_blank" href="' +
                    offer_url +
                    '">' +
                    know_more +
                    "</a></div>";
            }

            html =
                '<div class="offers_popup_offers_single_block"><div class="image_section">' +
                image +
                '</div><div class="content_section"><div class="content_section_heading">' +
                heading +
                '</div><div class="content_section_description">' +
                content +
                "</div>" +
                offer_url_html +
                '<div class="content_section_validity"><div class="content_section_validity_start ' +
                start_date_class +
                '"><div class="valid_from_label label">valid from:</div><div class="valid_from_date date">' +
                start_date +
                '</div></div><div class="content_section_validity_end ' +
                end_date_class +
                '"><div class="valid_till_label label">valid till:</div><div class="valid_till_date date">' +
                end_date +
                "</div></div></div></div></div>";
            return html;
        }
        function GetOfferHtml(
            image,
            heading,
            content,
            start_date,
            end_date,
            know_more
        ) {
            var html = "";
            html =
                html +
                "<div class='offer_single_slide'><div class='offer_single_slide_inside'><div class='header_offer'><div class='image_header_offer'>" +
                image +
                "</div><div class='heading_header_offer'>" +
                heading +
                "</div></div><div class='content_offer open_offer_popup'><div class='content_offer_inside'>" +
                content +
                "</div></div><div class='knowmore_offer'><div class='open_offer_popup'>" +
                know_more +
                "</div></div></div></div>";
            return html;
        }

        //offer popup on product page js ends here

        //sticky image js on product page js starts here
        //sticky image js on product page js starts here
        jQuery(".notice_top .close_notice").click(function () {
            jQuery(".notice_top").slideUp();
            if (window.matchMedia("(max-width: 767px)").matches) {
                jQuery("main#maincontent").addClass("nonotice");
            }
        });

        var viewportWidth = window.innerWidth;

        if (jQuery(window).width() > 768) {
            setInterval(function () {
                if (
                    !jQuery(".fotorama__stage__shaft.fotorama__grab").children(
                        ".tag"
                    ).length &&
                    jQuery(".fotorama__stage__shaft.fotorama__grab").length
                ) {
                    console.log("tag is not there");
                    jQuery(".fotorama__stage__shaft.fotorama__grab").prepend(
                        "<div class='tag'>" +
                            jQuery(".product.media").children(".tags").html() +
                            "<div>"
                    );
                    jQuery(".product.media").children(".tags").html("");
                } else {
                    console.log("tag is there");
                }
            }, 3000);
        }

        //increment and decrement quantity product page
        jQuery(".qty_decrement").click(function () {
            if (parseInt(jQuery(this).siblings(".qty").val()) < parseInt(1)) {
                parseInt(jQuery(this).siblings(".qty").val(1));
            } else {
                jQuery(this)
                    .siblings(".qty")
                    .val(parseInt(jQuery(this).siblings(".qty").val()) - 1);
            }
        });
        jQuery(".qty_increment").click(function () {
            jQuery(this)
                .siblings(".qty")
                .val(parseInt(jQuery(this).siblings(".qty").val()) + 1);
        });

        function updateDiscount() {
            if (!jQuery(".catalog-product-view").length) return;

            var mrpText = jQuery(".product-info-main .old-price .price").first().text().trim();
            var dealText = jQuery(".product-info-main .special-price .price").first().text().trim();

            if(jQuery('body').hasClass('page-product-configurable')){
                var dealText = jQuery(".product-info-main .normal-price .price").first().text().trim();
            }
            var mrp = parseFloat(mrpText.replace(/[^\d.]/g, ""));
            var deal = parseFloat(dealText.replace(/[^\d.]/g, ""));
            if (!mrp || !deal || mrp <= deal) return;

            var discountAmount = mrp - deal;
            var discountPercent = ((discountAmount / mrp) * 100);
            var discountRounded = Math.round(discountPercent);
            jQuery('.yousave_per_jquery').remove();
            var youSaveHTML =
                "<span class='yousave_per_jquery' style='display:block !important;'> (" + discountRounded + "% OFF)</span>";
            jQuery(".product-info-main .old-price").after(youSaveHTML);
        }
        updateDiscount();
        jQuery(document).on('click change', '.swatch-attribute-options div, .swatch-option, select[data-role=swatch]', function () {
            setTimeout(updateDiscount, 300);
        });
        jQuery("body").on("updatePrice", function () {
            setTimeout(updateDiscount, 100);
        });

        function set_productpage_addtocart_popup() {
            var current_scrolled = jQuery(window).scrollTop();
            if (jQuery(".product-options-bottom").length) {
                var needed_scroll = jQuery(".product-options-bottom").offset()
                    .top;
            } else {
                var needed_scroll = 0;
            }
            if (current_scrolled > needed_scroll) {
                jQuery(".right_side_addtocart_button").addClass("show");
            } else {
                jQuery(".right_side_addtocart_button").removeClass("show");
            }
        }
        set_productpage_addtocart_popup();
        jQuery(window).scroll(function () {
            set_productpage_addtocart_popup();
        });
        jQuery(".right_side_addtocart_button").click(function () {
            if (jQuery(".product_page_namedetails").length) {
                var needed_scroll = jQuery(".product_page_namedetails").offset()
                    .top;
            } else {
                var needed_scroll = 0;
            }
            jQuery("html, body").animate({ scrollTop: needed_scroll }, "slow");
        });

        //slide toggle the questions on product page
        jQuery(".questions_product_description .question").click(function () {
            if (jQuery(this).hasClass("expanded")) {
                var close = true;
            } else {
                var close = false;
            }
            jQuery(".questions_product_description .question").removeClass(
                "expanded"
            );
            jQuery(".questions_product_description .answer").slideUp();

            if (close == true) {
                jQuery(this).removeClass("expanded");
                jQuery(this).next(".answer").slideUp();
            } else {
                jQuery(this).addClass("expanded");
                jQuery(this).next(".answer").slideDown();
            }
        });

        //text and image logo tab swap on product page
        jQuery(".choose_button").click(function () {
            jQuery(".choose_button").removeClass("active");
            jQuery(this).addClass("active");

            if (jQuery(this).hasClass("ptextdiv")) {
                jQuery("#ptextdiv").show();
                jQuery("#pimgdiv").hide();
            } else if (jQuery(this).hasClass("ptextdiv")) {
                jQuery("#ptextdiv").hide();
                jQuery("#pimgdiv").show();
                $('div#ptextdiv input[type="radio"][name="Font"]').prop(
                    "checked",
                    false
                );
            }
        });

        //set source of products in description
        function displayHiddenImages() {
            var windowWidth = jQuery(window).width();

            if (windowWidth > 425) {
                jQuery(".mobile_images").attr("src", "");
                jQuery(".desktop_images").each(function () {
                    jQuery(this).attr("src", jQuery(this).attr("data-src"));
                });
            } else {
                jQuery(".desktop_images").attr("src", "");
                jQuery(".mobile_images").each(function () {
                    jQuery(this).attr("src", jQuery(this).attr("data-src"));
                });
            }
        }
        displayHiddenImages();
        jQuery(window).scroll(displayHiddenImages);

        jQuery(document).on("click", function (e) {
            var $iconsBooster = jQuery(e.target).closest(".icons_booster");
            var $tooltipText = jQuery(
                ".catalog-product-view span.points.mp-point-label.tooltip, .mp-point-label.tooltip"
            ).children(".tooltiptext");

            if ($iconsBooster.length === 0) {
                $tooltipText.css("visibility", "hidden");
            }
        });

        jQuery(
            ".catalog-product-view span.points.mp-point-label.tooltip, .mp-point-label.tooltip"
        ).click(function () {
            jQuery("#popup_timer").text(6);

            var $tooltipText = jQuery(this).children(".tooltiptext");
            jQuery(
                ".catalog-product-view span.points.mp-point-label.tooltip, .mp-point-label.tooltip"
            )
                .children(".tooltiptext")
                .not($tooltipText)
                .css("visibility", "hidden");
            $tooltipText.css("visibility", "visible");
        });

        setInterval(function () {
            var $popupTimer = jQuery("#popup_timer");
            var popupTimerText = parseInt($popupTimer.text());

            if (popupTimerText === 0) {
                jQuery(
                    ".catalog-product-view span.points.mp-point-label.tooltip, .mp-point-label.tooltip"
                )
                    .children(".tooltiptext")
                    .css("visibility", "hidden");
            } else if (popupTimerText > 0) {
                $popupTimer.text(popupTimerText - 1);
            }
        }, 1000);
    });
});

require([
    "jquery",
    "jquery/ui",
    "Magento_Catalog/js/product/remaining-characters",
], function ($) {
    $(document).ready(function ($) {
        // custom print js code

        var fdata = "";
        $("#print_text").remainingCharacters({
            maxLength: 10,
            noteSelector: ".note",
            counterSelector: ".note .character-counter",
        });
        $(".dragdrop").click(function () {
            $("#print_file").trigger("click");
        });
        if ($("#imgtextprint").length) {
            $("#imgtextprint").insertAfter(".label_printon_it");
            $("#pimgdiv").insertAfter("#ptextdiv");
        }
        $(".input-text.product-custom-option.inputprinting")
            .parents(".field")
            .hide();
        $("#ptextdiv, #imgtextprint, #pimgdiv").hide();
        var optpricehtml = $(".option_price_hidden:first").html();
        $(".combine_pricefield").html(optpricehtml);
        $("#product_addtocart_form").on("click", "#printcheck", function () {
            deletelogofunc();
            var pricehtml = $(
                ".input-text.product-custom-option.inputprinting.text"
            )
                .parents(".field")
                .find("span.price-notice")
                .html();
            $(".printq").find("span.price-notice").html(pricehtml);
            $("#ptextdiv, #imgtextprint, #pimgdiv, .resultimg").hide();
            $(".drag-area").css("border", "3px dashed lightgray");
            var pball = document.getElementById("printcheck").checked;
            if (pball == true) {
                $("#textprint").prop("checked", true);
                $("#imgtextprint, .printfielddiv:first").show();
                // $(".pimgdiv").removeClass('active');
                //$(".ptextdiv").addClass('active');
                $(".choose_button").removeClass("active");
                $(".choose_button:first").addClass("active");
            } else {
                fdata = "";
                $("#ptextdiv, #imgtextprint, #pimgdiv,.resultimg").hide();
                $("#product-options-wrapper").find("div.message").remove();
                $("#print_file").val("");
                $("#print_text").val("");
                $(".dragdrop").text("+");
                $(".input-text.product-custom-option.inputprinting")
                    .val("")
                    .trigger("change");
            }
        });
        $("#product_addtocart_form").on("click", "#imgprint , .choose_button.pimgdiv", function () {
            var optprihtml = $(".logo.option_price_hidden").html();
            $(".combine_pricefield").html(optprihtml);
            deletelogofunc();
            fdata = "";
            $("#ptextdiv, .resultimg").hide();
            $("#pimgdiv").show();
            $("#print_text").val("");
            $("p.dragdrop").show();
            $(".drag-area").css("border", "3px dashed lightgray");
            $(".input-text.product-custom-option.inputprinting")
                .val("")
                .trigger("change");
            $("#product-options-wrapper").find("div.message").remove();
            var pricehtml = $(
                ".input-text.product-custom-option.inputprinting.logo"
            )
                .parents(".field")
                .find("span.price-notice")
                .html();
            $(".printq").find("span.price-notice").html(pricehtml);
        });
        $("#product_addtocart_form").on("click", "#textprint , .choose_button.ptextdiv", function () {
            var optprihtml = $(".text.option_price_hidden").html();
            $(".combine_pricefield").html(optprihtml);
            deletelogofunc();
            fdata = "";
            $("#ptextdiv").show();
            $("#print_text").val("");
            $("#pimgdiv, .resultimg").hide();
            $("#print_file").val("");
            $(".dragdrop").text("+");
            $(".dragdrop").show();
            $(".drag-area").css("border", "3px dashed lightgray");
            $(".input-text.product-custom-option.inputprinting")
                .val("")
                .trigger("change");
            $("#product-options-wrapper").find("div.message").remove();
            var pricehtml = $(
                ".input-text.product-custom-option.inputprinting.text"
            )
                .parents(".field")
                .find("span.price-notice")
                .html();
            $(".printq").find("span.price-notice").html(pricehtml);
        });
        $("#product-options-wrapper").on("click", ".img-cross", function () {
            var rel = $(this).attr("rel");
            deletelogofunc();
        });
        //----------------drag drop upload-----------------
        $("html").on("dragover", function (e) {
            e.preventDefault();
            e.stopPropagation();
            // $(".dragdrop").text("Drag here");
        });

        $("html").on("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        // Drag enter
        $(".drag-area").on("dragenter", function (e) {
            e.stopPropagation();
            e.preventDefault();
            // $(".dragdrop").text("Drop");
        });

        // Drag over
        $(".drag-area").on("dragover", function (e) {
            e.stopPropagation();
            e.preventDefault();
            // $(".dragdrop").text("Drop");
        });

        // Drop
        $(".drag-area").on("drop", function (e) {
            e.stopPropagation();
            e.preventDefault();
            var file = e.originalEvent.dataTransfer.files;
            // $(".dragdrop").text(file[0].name);
            var fd = new FormData();
            fd.append("file", file[0]);
            fdata = fd;
            // $("#print_file").val('').trigger('change');
            uploadlogofunc(fdata);
        });
        //-----------------------------------
        $("#print_file").change(function () {
            $("#product-options-wrapper").find("div.message").remove();
            $(".dragdrop").text("+");
            var opimgval = $(this).val();
            if (opimgval != "") {
                //$(".input-text.product-custom-option.inputprinting").val(opimgval).trigger('change');
                $(".input-text.product-custom-option.inputprinting")
                    .val("")
                    .trigger("change");
                var file_data = $("#print_file").prop("files")[0];
                var form_data = new FormData();
                form_data.append("file", file_data);
                fdata = form_data;
                uploadlogofunc(fdata);
            } else {
            }
        });
        $("#print_text").keyup(function () {
            var textval = $(this).val();
            //if(textval != ''){
            if (textval.length > 10) {
                textval = textval.substring(0, 10);
            }
            $(".input-text.product-custom-option.inputprinting")
                .val("")
                .trigger("change");
            $(".input-text.product-custom-option.inputprinting.text")
                .val(textval)
                .trigger("change");
            // }
        });
      
        $(document).on("click", "div#add_to_Cart_success_modal span.close", function (e) {
            e.preventDefault();
            $('#add_to_Cart_success_modal').remove();
        });
        
        $(document).on("click", "div#add_to_Cart_success_modal button.continue-shopping", function (e) {
            e.preventDefault();
            $('#add_to_Cart_success_modal').remove();
        });
        
        $(document).on("click", "div#add_to_Cart_success_modal button.review-cart-checkout", function (e) {
            e.preventDefault();
            window.location.href = window.BASE_URL+'checkout/cart/';
        });

        function uploadlogofunc(fdata) {
            if (fdata != null && fdata != "") {
                // Log the FormData contents
                for (let pair of fdata.entries()) {
                    console.log(pair[0] + ": " + pair[1]);
                }
            }
            $("#product-options-wrapper").find("div.message").remove();
            $(".resultimg").remove();
            if (fdata != null && fdata != "") {
                $.ajax({
                    url: "/customprinting/index/ajaxupload",
                    type: "POST",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: fdata,
                    dataType: "json",
                    showLoader: true,
                    success: function (res) {
                        if (res.path) {
                            $htmlstr = "";
                            if (res.path.split(".").pop() == "pdf") {
                                $htmlstr =
                                    $htmlstr +
                                    '<div class ="resultimg image"><img src = "/media/customprint/pdf.svg"><div class="img-cross" rel = "' +
                                    res.path +
                                    '">&#10006;</div></div>';
                            } else if (res.path.split(".").pop() == "ai") {
                                $htmlstr =
                                    $htmlstr +
                                    '<div class ="resultimg image"><img src = "/media/customprint/ai.svg"><div class="img-cross" rel = "' +
                                    res.path +
                                    '">&#10006;</div></div>';
                            } else if (res.path.split(".").pop() == "eps") {
                                $htmlstr =
                                    $htmlstr +
                                    '<div class ="resultimg image"><img src = "/media/customprint/eps.svg"><div class="img-cross" rel = "' +
                                    res.path +
                                    '">&#10006;</div></div>';
                            } else {
                                $htmlstr =
                                    $htmlstr +
                                    '<div class ="resultimg image" ><img src = "/media/' +
                                    res.path +
                                    '"><div class="img-cross" rel = "' +
                                    res.path +
                                    '">&#10006;</div></div>';
                            }
                            $(".input-text.product-custom-option.inputprinting")
                                .val("")
                                .trigger("change");
                            $(
                                ".input-text.product-custom-option.inputprinting.logo"
                            )
                                .val(res.path)
                                .trigger("change");
                            $("#drag-area").append($htmlstr);
                            $("#drag-area").css("border", "none");
                            $("p.dragdrop").hide();
                            toappend =
                                '<div class="message message-success success msg_succ"><p>Image uploaded.</p></div>';
                            $("#product-options-wrapper").append(toappend);
                        }
                        if (res.error) {
                            var toappend =
                                '<div class="message message-error error msg_err">';
                            $.each(res.error, function (index, value) {
                                toappend = toappend + "<p>" + value + "</p>";
                            });
                            toappend = toappend + "</div>";
                            $("#product-options-wrapper").append(toappend);
                        }
                    },
                });
            }
        }
        function deletelogofunc() {
            if ($("#product-options-wrapper").find(".img-cross").length) {
                el = $("#product-options-wrapper").find(".resultimg");
                path = $("#product-options-wrapper")
                    .find(".img-cross")
                    .attr("rel");
                $.ajax({
                    url: "/customprinting/index/deleteajaxupload",
                    type: "POST",
                    data: "path=" + path,
                    dataType: "json",
                    showLoader: true,
                    success: function (res) {
                        el.remove();
                        $("#product-options-wrapper")
                            .find("div.message")
                            .remove();
                        $("#print_file").val("");
                        $("#print_text").val("");
                        $("p.dragdrop").show();
                        $(".drag-area").css("border", "3px dashed lightgray");
                        $(".input-text.product-custom-option.inputprinting")
                            .val("")
                            .trigger("change");
                    },
                });
            }
        }

        const inputField = document.getElementById("print_text");
        const unwantedCharsRegex = /[^\w\s]/g; // Matches non-alphanumeric characters and whitespace

        function filterInput(value) {
            return value.replace(unwantedCharsRegex, "");
        }

        inputField.addEventListener("keyup", function (event) {
            event.target.value = filterInput(event.target.value);
        });

        inputField.addEventListener("paste", function (event) {
            event.preventDefault();
            const pastedText = (
                event.clipboardData || window.clipboardData
            ).getData("text");
            const filteredText = filterInput(pastedText);
            if (filteredText) {
                event.target.value = filteredText;
            } else {
                // Handle invalid pasted text (e.g., display an error message)
            }
        });
    });
});
