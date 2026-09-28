require([
    "jquery",
    "jquery/ui",
    "domReady!",
    "Magento_Catalog/js/product/remaining-characters",
    "Magento_Ui/js/modal/modal"
], function ($, ui, domReady, remainingCharacters, modal) {

    $('div#bundleSummary').find('.price-as-configured').prepend("<label class='label price_label bundle_product_dynamic_price'>Price: </div>");
    $('div#bundleSummary').find('.price-as-configured').append("<div class='inclsv_text show'>Inclusive of all taxes</div>");
    $('.price-configured_price').addClass('fixed');

    var $btn = $('.buying_tips_product_page');
    if ($btn.length) {
        $btn.on('click', function (e) {
            var $target = $('#tab-label-packaging-content');

            if ($target.length) {
                var top = $target.offset().top - 130;

                $('html, body').animate({
                    scrollTop: top
                }, 600);
            }
        });
    }

    // product page add to cart and price right slider view

    function checkProductInfoVisibility() {
        var $info = $('.product-info-wrapper-custom');
        var $button = $('.right_side_addtocart_button');
        if ($info.length && $button.length) {
            var rect = $info[0].getBoundingClientRect();
            var inView = rect.bottom > 0 && rect.top < window.innerHeight;
            if (!inView) {
                $button.addClass('show');
            } else {
                $button.removeClass('show');
            }
        }
    }
    $(window).on('scroll resize', checkProductInfoVisibility);
    $(document).ready(function () {
        checkProductInfoVisibility();
        $('#staticAddToCartBtn').on('click', function () {
            $('html, body').animate({ scrollTop: 0 }, 'slow');
        });
    });

    // product page add to cart and price right slider view

    $('#product-attribute-specs-table tr').each(function () {
        if ($(this).find('th').text().trim() === 'Similar attribute Label and attribute') {
            $(this).css('display', 'none');
        }
    });

    // add to card popup start
    // var popup = $('#add_to_Cart_success_modal');
    // modal({
    //     type: 'popup',
    //     responsive: false,
    //     innerScroll: false,
    //     title: false,
    //     buttons: [],
    //     modalClass: 'add-to-cart-success-modal',
    //     clickableOverlay: true
    // }, popup);
    // $(document).on('ajax:addToCart', function(event, data) {
    //     var productName = $('.page-title-wrapper.product h1.page-title span.base').text();
    //     if (!data.response || Object.keys(data.response).length === 0) {
    //         popup.find('.product-name').text(productName);
    //         popup.modal('openModal');
    //     }
    //     $(document).find(".img-cross").remove();
    //     $(document).find(".resultimg").remove();
    //     $(document).find('div.message').remove();
    //     $("#print_file").val('');
    //     $("#print_text").val('');
    //     $("p.dragdrop").show();
    //     $(".drag-area").css('border','3px dashed lightgray'); 
    //     $(".input-text.product-custom-option.inputprinting").val('').trigger('change');
    // });

    // $('.action-close, .continue-shopping').on('click', function() {
    //     popup.modal('closeModal'); // Never use .hide()
    // });


    // $(document).on('ajax:addToCart', function(event, data) {
    //     var productName = $('.page-title-wrapper.product h1.page-title span.base').text();
    //     if (!data.response || Object.keys(data.response).length === 0) {
    //         // popup.find('.product-name').text(productName);
    //         // popup.modal('openModal');
    //         showNotification();
    //     }
    //     $(document).find(".img-cross").remove();
    //     $(document).find(".resultimg").remove();
    //     $(document).find('div.message').remove();
    //     $("#print_file").val('');
    //     $("#print_text").val('');
    //     $("p.dragdrop").show();
    //     $(".drag-area").css('border','3px dashed lightgray'); 
    //     $(".input-text.product-custom-option.inputprinting").val('').trigger('change');
    // });
    // var messages = [
    //     "Product has been added to cart!"
    // ];
    // $('.review-cart-checkout').on('click', function() {
    //     window.location.href = window.BASE_URL + 'checkout/cart/';
    // });
    // var notifTimeout;
    // function hideNotification() {
    //     var $notif = $('#dynamicNotification');
    //     $notif.removeClass('show');
    //     setTimeout(function() {
    //         $notif.css('display','none');
    //     }, 400);
    // }
    // function showNotification() {
    //     var msg = messages[Math.floor(Math.random() * messages.length)];
    //     var $notif = $('#dynamicNotification');
    //     $notif.find('.notif-content').text(msg);

    //     $notif.css('display','block');
    //     setTimeout(function() {
    //         $notif.addClass('show');
    //     }, 10);
    //     clearTimeout(notifTimeout);
    //     notifTimeout = setTimeout(hideNotification, 5000);
    // }
    // $('#dynamicNotification').on('click', '.notif-close', function(e) {
    //     hideNotification();
    //     clearTimeout(notifTimeout);
    // });
    // add to card popup end

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
            setTimeout(() => {
                $("div#ptextdiv").hide();
            }, 10);
            $(document).find("div.message").remove();
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
    $(document).on("click", ".img-cross", function () {
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
        window.location.href = window.BASE_URL + 'checkout/cart/';
    });

    // $(".choose_button").click(function () {
    //     $(".choose_button").removeClass("active");
    //     $(this).addClass("active");

    //     if ($(this).hasClass("ptextdiv")) {
    //         $("#ptextdiv").show();
    //         $("#pimgdiv").hide();
    //     } else if ($(this).hasClass("ptextdiv")) {
    //         $("#ptextdiv").hide();
    //         $("#pimgdiv").show();
    //         $('div#ptextdiv input[type="radio"][name="Font"]').prop(
    //             "checked",
    //             false
    //         );
    //     }
    // });


    $(document).on("click", "input#printcheck", function () {
        $('input#textprint').prop('checked', true);
        $('input#textprint').trigger('click');
    });

    $(document).on("click", ".choose_button", function () {
        $(".choose_button").removeClass("active");
        $(this).addClass("active");

        if ($(this).hasClass("ptextdiv")) {
            $("#ptextdiv").show();
            $("#pimgdiv").hide();
        } else if ($(this).hasClass("ptextdiv")) {
            $("#ptextdiv").hide();
            $("#pimgdiv").show();
            $('div#ptextdiv input[type="radio"][name="Font"]').prop(
                "checked",
                false
            );
        }
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
                        $("div#pimgdiv").append(toappend);
                    }
                    if (res.error) {
                        var toappend =
                            '<div class="message message-error error msg_err">';
                        $.each(res.error, function (index, value) {
                            toappend = toappend + "<p>" + value + "</p>";
                        });
                        toappend = toappend + "</div>";
                        $("div#pimgdiv").append(toappend);
                    }
                },
            });
        }
    }
    function deletelogofunc() {
        if ($(document).find(".img-cross").length) {
            el = $(document).find(".resultimg");
            path = $(document)
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
                    $(document)
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

    $('.additional-attributes-wrapper.table-wrapper').on('click', function () {
        $(this).find('> table').slideDown(200);
        $(this).addClass('opened');
    });


    // Auto-activate review tab after 2 seconds without scrolling
    setTimeout(() => {
        try {
            const $reviewTab = $('#tab-label-reviews');
            const $reviewLink = $('a#tab-label-reviews-title');
            if ($reviewTab.length && $reviewLink.length) {
                const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                $reviewTab.attr({
                    'aria-selected': 'true',
                    'aria-expanded': 'true'
                });
                $reviewLink.trigger($.Event('click'));
                $('#reviews').show().css('display', 'block');
                $reviewTab.addClass('active');
                window.scrollTo(0, currentScroll);
                // console.log('Activated reviews tab without scrolling');
            }
        } catch (e) {
            // console.log('Error activating reviews:', e);
        }
    }, 2000);

    // Offer callout for product page
    let offerCount = 0;
    const baseURL = window.BASE_URL;
    const $showAvailableQty = $('.show-available-qty.stock-message');
    const $boxToCart = $('div.box-tocart');
    const containerHtml = `
            <div class="product_offer_carausal_outer outer_container_slider_outer hide">
                <div class="offers_heading_outside">
                    <div class="offers_heading">
                        <div class="left_image">
                            <img src="https://golfoy.com/media/module_theme_images/product_page/discount_icon.svg" />
                        </div>
                        <div class="middle_heading_content"><h2>offers and discounts</h2></div>
                        <div class="see_all_button open_offer_popup"><div>view all</div></div>
                    </div>
                </div>
                <div class="product_offer_carausal outer_container_slider">
                    <div class="inner_container_slider"></div>
                </div>
                <div class="offer_slider_arrows hide">
                    <div class="offer_slider_arrows_right offer_right"></div>
                    <div class="offer_slider_arrows_left offer_left"></div>
                </div>
            </div>`;
    if ($showAvailableQty.length === 1) {
        $showAvailableQty.before(containerHtml);
    } else {
        $boxToCart.before(containerHtml);
    }

    $('.product_offer').each(function () {
        const $this = $(this);
        let image = $this.children('.image').length ? $this.children('.image').html() : '';
        let offer_url = $this.children('.url').length ? $this.children('.url').html() : '';
        offerCount++;
        if ($.trim(image) === '') {
            image = `<img src='${baseURL}media/module_theme_images/product_page/discount_icon.svg'/>`;
        }
        const heading = $this.children('.heading').html();
        const content = $this.children('.content').html();
        const content_short = $this.children('.content').text().length > 50 ? $this.children('.content').html().substr(0, 47) + '...' : content;
        const start_date = $this.children('.start_date').length ? $this.children('.start_date').html() : '';
        const end_date = $this.children('.end_date').length ? $this.children('.end_date').html() : '';
        let know_more = $this.children('.know_more').text();
        if (know_more === '') {
            know_more = "<div class='hid_it'>-</div>";
        }
        // For product page
        const slide_html = GetOfferHtml(image, heading, content_short, start_date, end_date, know_more);
        $('.inner_container_slider').append(slide_html);
        // For popup
        const offers_popup_html = GetOfferHtmlPopup(image, heading, content, start_date, end_date, know_more, offer_url);
        $('.offers_popup_offers_block_inside').append(offers_popup_html);
        $('div#offer_popup').find('.modal-content').find('.offers_popup').css('max-height', 250);
    });
    if (offerCount > 0) {
        $('.product_offer_carausal_outer.outer_container_slider_outer').removeClass('hide');
    }
    if (offerCount >= 3) {
        $('.offer_slider_arrows').removeClass('hide');
    }
    $('.inner_container_slider').width(offerCount * 170);
    $(document).on('click', '.offer_right', function () {
        const $slider = $('.inner_container_slider');
        const scrollAmount = $slider.width() * 0.7;
        $slider.animate({
            scrollLeft: '+=' + scrollAmount
        }, 600);
    });
    $(document).on('click', '.offer_left', function () {
        const $slider = $('.inner_container_slider');
        const scrollAmount = $slider.width() * 0.7;
        $slider.animate({
            scrollLeft: '-=' + scrollAmount
        }, 600);
    });
    $('.open_offer_popup').on('click', function () {
        $('#offer_popup').show();
        setTimeout(function () {
            $('.page-wrapper').addClass('transit');
            $('#offer_popup').addClass('show_pop');
        }, 100);
    });
    $('#offer_popup button.close').on('click', function () {
        $('#offer_popup').removeClass('show_pop');
        setTimeout(function () {
            $('.page-wrapper').removeClass('transit');
            $('#offer_popup').hide();
        }, 100);
    });

    function GetOfferHtmlPopup(image, heading, content, start_date, end_date, know_more, offer_url) {
        let start_date_class = '';
        let end_date_class = '';
        let offer_url_html = '';
        if ($.trim(start_date) === '') start_date_class = 'hide';
        if ($.trim(end_date) === '') end_date_class = 'hide';
        if ($.trim(offer_url) !== '') {
            if ($.trim(know_more) === '') know_more = 'Learn More >>';
            offer_url_html = `<div class="product_link"><a target="_blank" href="${offer_url}">${know_more}</a></div>`;
        }
        return `<div class="offers_popup_offers_single_block"><div class="image_section">${image}</div><div class="content_section"><div class="content_section_heading">${heading}</div><div class="content_section_description">${content}</div>${offer_url_html}<div class="content_section_validity"><div class="content_section_validity_start ${start_date_class}"><div class="valid_from_label label">valid from:</div><div class="valid_from_date date">${start_date}</div></div><div class="content_section_validity_end ${end_date_class}"><div class="valid_till_label label">valid till:</div><div class="valid_till_date date">${end_date}</div></div></div></div></div>`;
    }

    function GetOfferHtml(image, heading, content, start_date, end_date, know_more) {
        return `<div class='offer_single_slide'><div class='offer_single_slide_inside'><div class='header_offer'><div class='image_header_offer'>${image}</div><div class='heading_header_offer'>${heading}</div></div><div class='content_offer open_offer_popup'><div class='content_offer_inside'>${content}</div></div><div class='knowmore_offer'><div class='open_offer_popup'>${know_more}</div></div></div></div>`;
    }
    // End offer callout

    // Move catalog points to offer container
    function moveCatalogPointsToOffers() {
        const $catalogPoints = $('.points.mp-point-label.tooltip');
        const $targetContainer = $('.product_offer_carausal_outer.outer_container_slider_outer');

        if ($catalogPoints.length > 0 && $targetContainer.length > 0) {
            // Check if container already exists to prevent duplicates
            if ($targetContainer.find('.catalog-points-wrapper').length === 0) {
                // Create wrapper container for image and points
                const $wrapperDiv = $('<div class="catalog-points-wrapper"></div>');
                const $imageElement = $('<img src="https://golfoy.com/media/mageplaza/rewardpoints/default/reward_coin.png" alt="Rewards" class="catalog-points-image" />');

                // Add image to wrapper
                $wrapperDiv.append($imageElement);

                // Remove the element from its current position and add to wrapper
                $catalogPoints.detach().appendTo($wrapperDiv);

                // Append the entire wrapper to target container
                $wrapperDiv.appendTo($targetContainer);
                console.log('Catalog points moved to offer container with image in wrapper');
            }
        } else {
            // If elements not found, try again after a short delay
            setTimeout(function () {
                const $catalogPointsRetry = $('.points.mp-point-label.tooltip');
                const $targetContainerRetry = $('.product_offer_carausal_outer.outer_container_slider_outer');

                if ($catalogPointsRetry.length > 0 && $targetContainerRetry.length > 0) {
                    // Check if container already exists to prevent duplicates
                    if ($targetContainerRetry.find('.catalog-points-wrapper').length === 0) {
                        // Create wrapper container for image and points
                        const $wrapperDiv = $('<div class="catalog-points-wrapper"></div>');
                        const $imageElement = $('<img src="https://golfoy.com/media/mageplaza/rewardpoints/default/reward_coin.png" alt="Rewards" class="catalog-points-image" />');

                        // Add image to wrapper
                        $wrapperDiv.append($imageElement);

                        // Remove the element from its current position and add to wrapper
                        $catalogPointsRetry.detach().appendTo($wrapperDiv);

                        // Append the entire wrapper to target container
                        $wrapperDiv.appendTo($targetContainerRetry);
                        console.log('Catalog points moved to offer container with image in wrapper (retry)');
                    }
                }
            }, 1000);
        }
    }

    // Execute the move function
    moveCatalogPointsToOffers();

    // Also try again after page is fully loaded
    $(window).on('load', function () {
        moveCatalogPointsToOffers();
    });

    // Hide productpagereward by default and handle click events
    let hideTimeout;

    // Hide by default
    $('#productpagereward').addClass('hide');

    // Show on click of points element
    $(document).on('click', '.points.mp-point-label.tooltip', function (e) {
        e.stopPropagation();
        const $reward = $('#productpagereward');

        // Clear any existing timeout
        if (hideTimeout) {
            clearTimeout(hideTimeout);
        }

        // Show the element
        $reward.removeClass('hide');

        // Hide after 5 seconds
        hideTimeout = setTimeout(function () {
            $reward.addClass('hide');
        }, 1500);
    });

    // Hide on click anywhere on page
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.points.mp-point-label.tooltip').length) {
            $('#productpagereward').addClass('hide');
            if (hideTimeout) {
                clearTimeout(hideTimeout);
            }
        }
    });

});

document.addEventListener("DOMContentLoaded", function () {

    const inputField = document.getElementById("print_text");
    if (inputField) {
        const unwantedCharsRegex = /[^\w\s]/g;
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
            event.target.value = filterInput(pastedText);
        });
    }

    const questions = document.querySelectorAll(".questions_product_description .question");
    questions.forEach((question) => {
        question.addEventListener("click", function () {
            const answer = this.nextElementSibling;
            // Toggle answer visibility
            answer.classList.toggle("show");
            // Toggle + / -
            this.classList.toggle("expanded");
        });
    });

    function moveRatings() {
        document.querySelectorAll(".amrelated-info").forEach(card => {
            const photoContainer = card.querySelector(".amrelated-photo-container");
            const rating = card.querySelector(".amrelated-details .custom-rating");
            if (photoContainer && rating && !photoContainer.contains(rating)) {
                photoContainer.appendChild(rating);
            }
        });
    }

    window.addEventListener("load", moveRatings);

    function moveSlickRatings() {
        document.querySelectorAll(".slick-slide:not(.slick-cloned)").forEach(slide => {
            const productItem = slide.querySelector(".product-item");
            if (!productItem) return;
            const photoContainer = productItem.querySelector(".product-item-photo");
            const rating = productItem.querySelector(".product-item-details .custom-rating");

            if (photoContainer && rating && !photoContainer.contains(rating)) {
                photoContainer.style.position = "relative";
                photoContainer.appendChild(rating);
            }
        });
    }

    window.addEventListener("load", function () {
        setTimeout(moveSlickRatings, 500);
    });

    const slider = document.querySelector(".slick-track");
    if (slider) {
        const observer = new MutationObserver(moveSlickRatings);
        observer.observe(slider, {
            childList: true,
            subtree: true
        });
    }
});

function incrementQty() {
    var qtyInput = document.getElementById('qty');
    var currentValue = parseInt(qtyInput.value) || 1;
    qtyInput.value = currentValue + 1;
    qtyInput.dispatchEvent(new Event('change'));
}

function decrementQty() {
    var qtyInput = document.getElementById('qty');
    var currentValue = parseInt(qtyInput.value) || 1;
    if (currentValue > 1) {
        qtyInput.value = currentValue - 1;
        qtyInput.dispatchEvent(new Event('change'));
    }
}

// Ensure quantity doesn't go below 1 when manually typed
document.addEventListener('DOMContentLoaded', function () {
    var qtyInput = document.getElementById('qty');
    if (qtyInput) {
        qtyInput.addEventListener('blur', function () {
            var value = parseInt(this.value) || 1;
            if (value < 1) {
                this.value = 1;
            }
        });

        qtyInput.addEventListener('keyup', function () {
            var value = parseInt(this.value) || 1;
            if (value < 1) {
                this.value = 1;
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function() {

    const popup = document.querySelector(".video-popup");
    const popupContainer = document.querySelector(".popup-video-container");
    const closeBtn = document.querySelector(".popup-close");
    const overlay = document.querySelector(".video-popup-overlay");
    
    if (!popup) return;

    document.querySelectorAll(".play-video-btn").forEach(function(button) {
        button.addEventListener("click", function(e) {
            e.preventDefault();

            popup.classList.add("active");
            document.body.style.overflow = "hidden";

            popupContainer.innerHTML = "";

            const type = this.getAttribute("data-type");
            const video = this.getAttribute("data-video");

            if (type === "youtube") {
                popupContainer.innerHTML = `
                <iframe
                    src="https://www.youtube-nocookie.com/embed/${video}?autoplay=1&rel=0&playsinline=1"
                    frameborder="0"
                    allow="autoplay; encrypted-media; fullscreen"
                    allowfullscreen>
                </iframe>
            `;
            } else {
                popupContainer.innerHTML = `
                <video
                    autoplay
                    controls
                    playsinline
                    controlsList="nodownload"
                    style="width:100%;height:100%;object-fit:contain;background:#000;">
                    <source src="${video}" type="video/mp4">
                </video>
            `;
            }
        });
    });
    function closePopup() {
        popup.classList.remove("active");
        popupContainer.innerHTML = "";
        document.body.style.overflow = "";
    }
    if (closeBtn) {
        closeBtn.addEventListener("click", closePopup);
    }
    if (overlay) {
        overlay.addEventListener("click", closePopup);
    }
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            closePopup();
        }
    });

});