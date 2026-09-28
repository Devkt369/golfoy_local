// ****Default selection of single swatch/configurable option
require(["jquery", "Magento_Swatches/js/swatch-renderer"], function (
    $,
    swatch
  ) {
    $(document).on("swatch.initialized", function () {
      setTimeout(function () {
        if (
          $(".page-product-configurable").length &&
          $(".swatch-attribute-options").length
        ) {
          var proceed = true;
          $(".swatch-attribute-options").each(function (index, value) {
            var total = $(value).find(".swatch-option").length;
            if (total >= 2) {
              proceed = false;
              return;
            }
          });
          if (proceed == true) {
            $(".swatch-attribute-options").each(function (index, value) {
              //$(value).find(".swatch-option").first().trigger('click');
              var el = $(value).find(".swatch-option").first();
              //var el = $(value).find(".swatch-option:not(.disabled)").first();
              el[0].click();
            });
          }
        }
      }, 1000);
    });
});
  
  
  //account menu dropdown on header
require(["jquery", "bootstrap"], function (jQuery) {
    jQuery(document).ready(function () {
      
      let isMouseDown = false;
      let startX;
      let scrollLeft;

      jQuery(document).on("mousedown", "ol#sliderWrapper", function(e) {
          isMouseDown = true;
          startX = e.pageX - jQuery(this).offset().left;
          scrollLeft = jQuery(this).scrollLeft();
          jQuery(this).css("cursor", "grabbing");
      });

      jQuery(document).on("mouseleave mouseup", "ol#sliderWrapper", function() {
          isMouseDown = false;
          jQuery(this).css("cursor", "grab");
      });

      jQuery(document).on("mousemove", "ol#sliderWrapper", function(e) {
          if (!isMouseDown) return;
          e.preventDefault();
          const x = e.pageX - jQuery(this).offset().left;
          const walk = (x - startX) * 2;
          jQuery('ol#sliderWrapper').scrollLeft(scrollLeft - walk);
      });

      jQuery(document).on("dragstart", "ol#sliderWrapper a", function(e) {
          e.preventDefault();
      });

      jQuery(document).on("click", "button.prev-slide.left_arrow", function(e) {
          var $sliderWrapper = jQuery('ol#sliderWrapper');
          $sliderWrapper.css("scroll-behavior", "smooth");
          $sliderWrapper.scrollLeft(function(index, value) {
              return value - 250;
          });
          setTimeout(function() {
              $sliderWrapper.css("scroll-behavior", "auto");
          }, 300);
      });

      jQuery(document).on("click", "button.next-slide.right_arrow", function(e) {
          var $sliderWrapper = jQuery('ol#sliderWrapper');
          $sliderWrapper.css("scroll-behavior", "smooth");
          $sliderWrapper.scrollLeft(function(index, value) {
              return value + 250;
          });
          setTimeout(function() {
              $sliderWrapper.css("scroll-behavior", "auto");
          }, 300);
      });

      jQuery(document).on('click', '.slider-new-next-btn', function(){
          var container = jQuery('#latest-new-scroll-container');
          var scrollAmount = container.children().first().outerWidth(true) + 100; // Adjust 100 if needed
          var maxScroll = container[0].scrollWidth - container.outerWidth();

          if (container.scrollLeft() < maxScroll) {
              container.animate({
                  scrollLeft: container.scrollLeft() + scrollAmount
              }, 400);
          }
      });

      jQuery(document).on('click', '.slider-new-prev-btn', function(){
          var container = jQuery('#latest-new-scroll-container');
          var scrollAmount = container.children().first().outerWidth(true) + 100; // Adjust 100 if needed
          if (container.scrollLeft() > 0) {
              container.animate({
                  scrollLeft: container.scrollLeft() - scrollAmount
              }, 400);
          }
      });

      jQuery(document).on("click",".ftab ul li", function() {
        jQuery(".ftab ul li").removeClass("active");
        jQuery(this).addClass("active");
        var li_value = jQuery(this).data("li");
        jQuery(".target").hide();
        jQuery("." + li_value).show();
      });

      jQuery(document).on('click','.tablinks', function() {
          var cityName = jQuery(this).data('city');
          jQuery(".tabcontent").hide();
          jQuery(".tablinks").removeClass("active");
          jQuery("#" + cityName).show();
          jQuery(this).addClass("active");
      });
      
      jQuery("#defaultOpen").addClass("active");
      var defaultCity = jQuery("#defaultOpen").data('city');
      jQuery("#" + defaultCity).show();

      jQuery("body").addClass("no_notice");
      jQuery(".close_notice").on("click", function () {
        jQuery("body").removeClass("no_notice");
      });

      jQuery(document).on('click', '.accordion-2', function () {
        jQuery(this).next('.pane').slideToggle(); // Toggle the next pane
        jQuery(this).toggleClass('minus');
      });

      function handleFilterClick(event=null) {
        if(!event) {
          return;
        }
      var target = event.target;

      if (
        target.id === "filters" ||
        target.id === "cross_btn" ||
        jQuery(target).closest("#filters").length ||
        jQuery(target).closest("#cross_btn").length
      ) {
        
        var targetDiv = jQuery(".block-content.filter-content");

        targetDiv.toggleClass("full_filter_block");

        jQuery("body").toggleClass("filter-active");

        jQuery("body").toggleClass(
          "no-filter-active",
          !jQuery("body").hasClass("filter-active")
        );
        var narrowByList = jQuery("#narrow-by-list");
        if (targetDiv.hasClass("full_filter_block")) {
          narrowByList.css("display", "block");
        } else {
          narrowByList.css("display", "none");
        }
        var layeredNavigationState = jQuery("#layered-navigation-state");
        var filterActions = jQuery(".block-actions.filter-actions");

        if (
          jQuery("body").hasClass("filter-active") &&
          jQuery(window).width() <= 768
        ) {
          layeredNavigationState.css("display", "block");
          filterActions.css("display", "block");
        } else {
          layeredNavigationState.css("display", "none");
          filterActions.css("display", "none");
        }
      }
    }

    jQuery("body").on("click", handleFilterClick);

    jQuery("#layered-filter-block").on("click", "a", function (event) {
      event.preventDefault();
      handleFilterClick(event);
    });

    jQuery(window).on("resize", function () {
      handleFilterClick();
    });

    jQuery(document).ready(function () {
      jQuery("body").addClass("no-filter-active");
    });

    jQuery(window).on("scroll", function (event) {
      event.stopPropagation();
    });

    jQuery(window).scroll(function () {
      var $infiniteScrollContainer = jQuery(".infinite-scroll-page-link-container");
      
      if ($infiniteScrollContainer.length) {
        var elementTop = $infiniteScrollContainer.offset().top;
        var viewportBottom = jQuery(window).scrollTop() + jQuery(window).height();
        
        if (elementTop < viewportBottom) {
          jQuery(".abcf_status").text("here");
    
          if ($infiniteScrollContainer.is(":visible")) {
            jQuery("a.ajaxcatalog-infinite-scroll-next").click();
          }
        } else {
          jQuery(".abcf_status").text("here 2");
          jQuery(".abcf").html("--elementTop:" + elementTop +
                              "--viewportBottom:" + viewportBottom);
        }
      }
    });
    // mobile slider slide by touch start

    let touchstartX = 0;
    let touchendX = 0;

    function handleGesture() {
        if (touchendX < touchstartX) {
            jQuery('#golfoy-carousel-mobile .right.carousel-control').trigger('click');
        }
        if (touchendX > touchstartX) {
            jQuery('#golfoy-carousel-mobile .left.carousel-control').trigger('click');
        }
    }

    jQuery(document).on('touchstart', '#golfoy-carousel-mobile', function (event) {
        touchstartX = event.originalEvent.changedTouches[0].screenX;
    });

    jQuery(document).on('touchend', '#golfoy-carousel-mobile', function (event) {
        touchendX = event.originalEvent.changedTouches[0].screenX;
        handleGesture();
    });
    // mobile slider slide by touch end
    function checkNoticeTop() {
        if (jQuery(".notice_top").length === 0) {
            jQuery("body").removeClass("no_notice");
        }
    }
    checkNoticeTop();
    jQuery(document).ajaxComplete(function() {
        checkNoticeTop();
    });
  
      function fix_header() {
        var windowScrollTop = jQuery(window).scrollTop();
        var header = jQuery("header.page-header");
        var navSections = jQuery(".page-wrapper > .sections.nav-sections");
  
        if (windowScrollTop > 100) {
          header.addClass("fixed");
          navSections.addClass("fixed");
  
          if (jQuery(window).width() > 768) {
            navSections.css("top", header.outerHeight());
          }
        } else {
          header.removeClass("fixed");
          navSections.removeClass("fixed");
        }
      }
  
      //change heading in my account customer dashboard
      if (jQuery(".title.block-collapsible-nav-title").length) {
        var selected_bar = jQuery("div#block-collapsible-nav")
          .find("li.nav.item.current")
          .find("strong")
          .text();
        jQuery(".title.block-collapsible-nav-title").html(
          "<strong>" + selected_bar + "</strong"
        );
      }
  
      jQuery(window).scroll(function () {
        fix_header();
      });
  
      jQuery(
        ".signin_signout_blockoutside.logged-in .account_image.open_account"
      ).click(function (e) {
        e.preventDefault();
        jQuery(".icon_list_here").show();
      });
      jQuery(".close_button_account_icon").click(function () {
        jQuery(".icon_list_here").hide();
      });
  
      jQuery(".signin_signout_blockoutside.logged-in").hover(
        function (e) {
          e.preventDefault();
          jQuery(".icon_list_here").show();
        },
        function () {
          jQuery(".icon_list_here").hide();
        }
      );
  
      //set width of menu
      set_menu_width();
      jQuery(window).resize(function () {
        set_menu_width();
      });
  
      function set_menu_width() {
        if (jQuery(window).width() > 768) {
          jQuery("ul.level0.submenu").each(function () {
            var num_child = jQuery(this).children("li").length;
            var width = parseInt(jQuery(this).children("li").css("width"));
            //alert(num_child+'-'+width);
            var ul_width = width * num_child + 5;
            jQuery(this).css("width", ul_width);
          });
        }
      }
  
      //increment and decrement quantity cart page
      jQuery(".dec-qty_box_cart").click(function () {
        var inputField = jQuery(this)
          .siblings(".input-qty_box_cart")
          .children("input");
        var currentValue = parseInt(inputField.val());
  
        if (currentValue < 1) {
          inputField.val(1);
        } else {
          inputField.val(currentValue - 1);
        }
      });
  
      jQuery(".inc-qty_box_cart").click(function () {
        var inputField = jQuery(this)
          .siblings(".input-qty_box_cart")
          .find("input");
        var currentValue = parseInt(inputField.val());
        inputField.val(currentValue + 1);
      });
      //<!----read more for category page----------------------------------------->
      jQuery(".buying_tips.buying_tips_product_page").click(function () {
        var offsetTop =
          jQuery("div#tab-label-packaging-content").offset().top - 200;
        jQuery("html, body").animate({ scrollTop: offsetTop }, 200, function () {
          jQuery("div#tab-label-packaging-content").click();
        });
      });
  
      //        lazy load image function starts
      function load_images() {
        jQuery(".lazy_load").each(function () {
          var img_top = jQuery(this).offset().top;
          jQuery(this).attr("from_top", img_top);
  
          var scrolltop = jQuery(window).scrollTop();
          var window_height = jQuery(window).height();
          if (scrolltop + window_height + 100 > img_top) {
            jQuery(this).attr("src", jQuery(this).attr("data-src"));
          }
        });
      }
      load_images();
      jQuery(window).scroll(function () {
        load_images();
      });
      //        lazy load image function ends
  
      //print_on_it_module
      function hide_select_button() {
        if (!jQuery("input#print_file").val()) {
        } else {
          jQuery(".upload_buttons label").addClass("hide");
        }
      }
      jQuery("input#print_file").change(function () {
        hide_select_button();
      });
    
      if (jQuery(window).width() < 768) {
        jQuery("#narrow-by-list").slideUp();
      }
      //jQuery('.filter_heading').click(function(){
      jQuery(document).on("click", ".filter_heading", function () {
        var windowWidth = jQuery(window).width();
  
        if (windowWidth < 768) {
          var $this = jQuery(this);
  
          if ($this.hasClass("open")) {
            $this.removeClass("open").addClass("close");
            jQuery("#narrow-by-list").slideUp();
          } else if ($this.hasClass("close")) {
            $this.removeClass("close").addClass("open");
            jQuery("#narrow-by-list").slideDown();
          }
        }
      });
  
      //on search button click, show input search
      jQuery(".field.search > label").click(function () {
        if (jQuery(window).width() < 768) {
          jQuery(this).siblings(".control").toggleClass("active");
          jQuery(".block.block-collapsible-nav").toggleClass("down");
        }
      });
  
      jQuery(".action.showcart").click(function () {
        setTimeout(function () {
          jQuery(".minicart-items-wrapper").height(jQuery(window).height() - 280);
        }, 1000);
      });
   
      var layeredNavigationState = jQuery("#layered-navigation-state");
      if (layeredNavigationState.length && jQuery(window).width() < 768) {
        var totalWidth = 0;
        layeredNavigationState.find("ol.items > li.item").each(function () {
          totalWidth += jQuery(this).outerWidth();
        });
        var numberOfBlocks =
          layeredNavigationState.find("ol.items > li.item").length;
        layeredNavigationState
          .find("ol.items")
          .width(totalWidth + numberOfBlocks * 20);
      }
  
      //breadcrumbs under category heading in category pages
      if (jQuery(".catalog-category-view").length) {
        jQuery(".breadcrumbs").insertAfter(".category-view");
      }
  
      //Blog link above filters heading in category pages
      if (jQuery(".catalog-category-view").length) {
        if (jQuery(".blog_link_html_category_page").length) {
          jQuery(".blog_link_html_category_page").insertBefore(
            "#ajaxlist-reload-layered-navigation"
          );
        }
      }
  
      if (jQuery(".heading_slide").length) {
        jQuery(".heading_slide").click(function () {
          jQuery(this).toggleClass("active");
          jQuery(this).siblings(".content_slide").slideToggle();
        });
      }
  
      setTimeout(function () {
        if (
          jQuery("table.data.table.totals td.amount .price.mp-rw-highlight")
            .length
        ) {
          var total_earned_points =
            jQuery(
              "table.data.table.totals td.amount .price.mp-rw-highlight"
            ).text() + " ";
          if (jQuery("#redeempoint_earned_points").length) {
            jQuery("#redeempoint_earned_points").text(total_earned_points);
          }
        }
      }, 7000);
  
      var path = window.location.pathname;
      var searchParams = new URLSearchParams(window.location.search);
  
      if (
        path === "/checkout/cart/" &&
        searchParams.has("clear_cart") &&
        searchParams.get("clear_cart") === "true"
      ) {
        jQuery("html, body").animate(
          {
            scrollTop: jQuery("#empty_cart_button").offset().top - 100,
          },
          2000
        );
  
        var colors = ["yellow", "transparent"];
        var interval = 500;
        var duration = 3200;
  
        var toggleColor = function () {
          colors.push(colors.shift());
          jQuery("#empty_cart_button").css("background-color", colors[0]);
        };
  
        var colorInterval = setInterval(toggleColor, interval);
  
        setTimeout(function () {
          clearInterval(colorInterval);
          jQuery("#empty_cart_button").css("background-color", "");
        }, duration);
      }
  
      jQuery(window).scroll(function () {
        if (jQuery(window).scrollTop() > jQuery(window).height() + 300) {
          jQuery(".scroll_to_top").removeClass("hide");
        } else {
          jQuery(".scroll_to_top").addClass("hide");
        }
      });
      jQuery(".scroll_to_top").click(function () {
        jQuery("html, body").animate({ scrollTop: 0 }, 600);
      });
  
      jQuery("body").click(function () {
        if (
          jQuery("#minicart-content-wrapper")
            .children(".block-content")
            .children("strong.empty").length
        ) {
          if (
            jQuery(".minicart_account_inside")
              .children(".minicart-wrapper")
              .hasClass("active")
          ) {
            jQuery("a.action.showcart").click();
          }
        }
      });
  
      jQuery("#tab-label-additional-title").text("Additional information");
  
      jQuery(".view_all_text").click(function () {
        jQuery(".view_all_offer").toggleClass("opened closed");
    });
    
    jQuery('.page-wrapper').click(() => {
        // Close the view_all_offer if it is open
        if (jQuery(".view_all_offer").hasClass("opened")) {
            jQuery(".view_all_offer").removeClass("opened").addClass("closed");
        }
    });
    
    // Prevent click on .view_all_offer from closing it
    jQuery(".view_all_offer").click(function (e) {
        e.stopPropagation();
    });
      //<!--------------------------------------------->
      jQuery("#gfmyBtn").click(function () {
        jQuery("#gfmore").toggle("slow", function () {
          // check paragraph once toggle effect is completed
          if (jQuery("#gfmore").is(":visible")) {
            jQuery("#gfdots").hide();
            jQuery("#gfmyBtn").html("Read less");
          } else {
            jQuery("#gfdots").show();
            jQuery("#gfmyBtn").html("Read more");
          }
        });
      });
      //<!----read more for category page----------------------------------------->
  
      function setup_onclick_slides() {
        jQuery(".product_content_product_slider_inside").each(function () {
          console.log("here");
          var prd_count = jQuery(this).find(".product-items").find("li").length;
          var width_per_slide = jQuery(this)
            .find(".product-items")
            .find("li")
            .outerWidth();
          jQuery(this)
            .find(".product-items")
            .width(width_per_slide * prd_count);
  
          var window_width = jQuery(window).width();
          //alert(window_width);
          var slides_move_on_click = Math.floor(window_width / width_per_slide);
  
          var scrollleft = jQuery(this).find("ol.product-items").scrollLeft();
          var total_width_to_move = slides_move_on_click * prd_count;
        });
      }
      jQuery("body").on("click", ".left_arrow", function () {
        // jQuery('.left_arrow').click(function(){
        //                    setup_onclick_slides();
  
        jQuery.currcontainer = jQuery(this)
          .parent(".slider_arrows")
          .siblings(".product_content_product_slider_before_list")
          .children(".product-items");
  
        var width_per_slide = jQuery.currcontainer.find("li").outerWidth();
        var window_width = jQuery(this)
          .parent(".slider_arrows")
          .siblings(".product_content_product_slider_before_list")
          .width();
  
        var slides_move_on_click = Math.floor(window_width / width_per_slide);
  
        var curr_scroll = jQuery.currcontainer
          .parent(".product_content_product_slider_before_list")
          .scrollLeft();
       jQuery.currcontainer
          .parent(".product_content_product_slider_before_list")
          .scrollLeft(curr_scroll - slides_move_on_click * width_per_slide);
      });
      jQuery("body").on("click", ".right_arrow", function () {
        // jQuery('.right_arrow').click(function(){
        jQuery.currcontainer = jQuery(this)
          .parent(".slider_arrows")
          .siblings(".product_content_product_slider_before_list")
          .children(".product-items");
  
        var width_per_slide = jQuery.currcontainer.find("li").outerWidth();
        var window_width = jQuery(this)
          .parent(".slider_arrows")
          .siblings(".product_content_product_slider_before_list")
          .width();
  
        var slides_move_on_click = Math.floor(window_width / width_per_slide);
  
        var curr_scroll = jQuery.currcontainer
          .parent(".product_content_product_slider_before_list")
          .scrollLeft();
        
        jQuery.currcontainer
          .parent(".product_content_product_slider_before_list")
          .scrollLeft(curr_scroll + slides_move_on_click * width_per_slide);
      });
  
      jQuery(".offer-code").click(function () {
        jQuery(".view_all_text").click();
        jQuery("#coupon_code").val(jQuery(this).text());
        jQuery("#discount-coupon-form").find(".action.apply.primary").click();
      });
      if (jQuery("input#coupon_code").val() != "") {
        setTimeout(function () {
          jQuery("div#block-discount").children(".content").slideDown();
        }, 3000);
      }
      //////////////////////////// header js sifted here ///////////////////////////////////
  
      jQuery(".mp-productt span.tooltip").click(function () {
        jQuery("#productpagereward").toggle();
      });
      jQuery(".page-main").click(function (event) {
        var num = jQuery(".points.mp-point-label.tooltip").length;
        //alert('num');
        if (num > 1) {
          var target = jQuery(event.target);
          var targetClass = target.attr("class");
  
          if (targetClass !== "points mp-point-label tooltip") {
            jQuery(".points.mp-point-label.tooltip").each(function () {
              jQuery(this).find("span").eq(1).css("display", "none");
            });
  
            jQuery("#productpagereward").css("display", "none");
          }
        } else {
          var target = jQuery(event.target);
          var targetClass = target.attr("class");
  
          if (targetClass !== "points mp-point-label tooltip") {
            jQuery("#productpagereward").css("display", "none");
          }
        }
      });
  
      setInterval(function () {
        if (jQuery(window).width() >= 1024) {
          jQuery(".launches_first_block")
            .children(".row")
            .children(".la_first_text")
            .height(
              jQuery(".launches_first_block")
                .children(".row")
                .children(".la_first_image")
                .height()
            );
          jQuery(".la_first_text").removeClass("hide");
        }
      }, 2000);
  
      jQuery(".clubs_click").click(function () {
        jQuery("html, body").animate(
          {
            scrollTop: jQuery("#clubs").offset().top - 130,
          },
          1
        );
      });
      jQuery(".shoes_click").click(function () {
        jQuery("html, body").animate(
          {
            scrollTop: jQuery("#shoes").offset().top - 130,
          },
          1
        );
      });
      jQuery(".apparels_click").click(function () {
        jQuery("html, body").animate(
          {
            scrollTop: jQuery("#apparels").offset().top - 130,
          },
          1
        );
      });
      jQuery(".bags_click").click(function () {
        jQuery("html, body").animate(
          {
            scrollTop: jQuery("#bags").offset().top - 130,
          },
          1
        );
      });
      if (jQuery("img").hasClass("desktop_images")) {
        jQuery(
          ".additional-attributes-wrapper.table-wrapper, .additional-attributes-wrapper.table-wrapper table"
        ).css("margin-left", "11px");
      }
  
      jQuery(
        ".input-text,.row.logo_block,.header_logo,.cart_icon_header.header_icon,.page-main,.page-footer,.sections.nav-sections"
      ).click(function () {
        jQuery(".icon_list_here").hide();
      });
  
      jQuery(".mp-point-label span.tooltip").click(function () {
        jQuery(this).children("#productpagereward").toggle();
      });
  
      jQuery(".icons_booster_inside")
        .children(".icons_booster_single_block")
        .click(function () {
          jQuery(this).find(".icons_booster_single_text").click();
        });
  
      jQuery(document).on("click", function (e) {
        if (jQuery(e.target).closest(".icons_booster").length === 0) {
          jQuery(".catalog-product-view span.points.mp-point-label.tooltip")
            .children(".tooltiptext")
            .css("visibility", "hidden");
          jQuery(".mp-point-label.tooltip")
            .children(".tooltiptext")
            .css("visibility", "hidden");
        }
      });
      jQuery(
        ".catalog-product-view span.points.mp-point-label.tooltip, .mp-point-label.tooltip"
      ).click(function () {
        jQuery("#popup_timer").text(6);
  
        jQuery(".catalog-product-view span.points.mp-point-label.tooltip")
          .children(".tooltiptext")
          .css("visibility", "hidden");
        jQuery(".mp-point-label.tooltip")
          .children(".tooltiptext")
          .css("visibility", "hidden");
        jQuery(this).children(".tooltiptext").css("visibility", "visible");
      });
  
      setInterval(function () {
        if (parseInt(jQuery("#popup_timer").text()) == 0) {
          //                alert('a');
          jQuery(".catalog-product-view span.points.mp-point-label.tooltip")
            .children(".tooltiptext")
            .css("visibility", "hidden");
          jQuery(".mp-point-label.tooltip")
            .children(".tooltiptext")
            .css("visibility", "hidden");
        } else if (parseInt(jQuery("#popup_timer").text()) > 0) {
          jQuery("#popup_timer").text(
            parseInt(jQuery("#popup_timer").text() - 1)
          );
        }
      }, 1000);
  
      jQuery(".icons_booster_single_block").each(function () {
        jQuery(this).attr("pos", jQuery(this).offset().left);
        if (jQuery(window).width() < 768) {
          jQuery(this)
            .find("span.tooltiptext")
            .css(
              "margin-left",
              "-" + parseInt(jQuery(this).offset().left) + "px"
            );
        }
      });
  
      jQuery(window).scroll(function () {
        jQuery(".b_o_s")
          .children(".pa_re")
          .each(function () {
            var curr_div_top_height = jQuery(this).offset().top;
            var curr_div_top_height_outer = jQuery(this).outerHeight();
            var window_height = jQuery(window).height();
            var scroll_top = jQuery(window).scrollTop();
            if (
              scroll_top >
              curr_div_top_height +
                curr_div_top_height_outer -
                window_height +
                100
            ) {
              jQuery(this).addClass("opened");
            }
          });
      });
  
      ///////////////////////////////////////////////////////////////

      // header script start

      jQuery(document).on("click", "span.cross-btn-shipping", function(e) {
          e.preventDefault();
          jQuery('li#shipping').removeClass('fixed');
      });
        
      // header script end

      // footer script start

      document.addEventListener('DOMContentLoaded', () => {
        const slidesContainer = document.querySelector('.video-slides');
        const slides = document.querySelectorAll('.video-slide');
        const prevButton = document.querySelector('.slider-prev');
        const nextButton = document.querySelector('.slider-next');
        let currentIndex = 0;
        let startX = 0; 

        function getVisibleSlides() {
            if (window.innerWidth <= 480) {
                return 3; 
            } else if (window.innerWidth <= 768) {
                return 2; 
            } else {
                return 4; 
            }
        }

        function updateSliderPosition() {
            const slideWidth = slides[0].clientWidth + parseFloat(getComputedStyle(slides[0]).marginRight);
            const visibleSlides = getVisibleSlides();
            const totalSlidesWidth = slides.length * slideWidth;
            const containerWidth = slidesContainer.clientWidth;
            const maxTranslateX = Math.max(totalSlidesWidth - containerWidth, 0);

            
            const translateX = -Math.min(currentIndex * slideWidth, maxTranslateX);
            slidesContainer.style.transform = `translateX(${translateX}px)`;
        }

        function updateButtonVisibility() {
            const visibleSlides = getVisibleSlides();
            nextButton.style.display = currentIndex < slides.length - visibleSlides ? 'block' : 'none';
            prevButton.style.display = currentIndex > 0 ? 'block' : 'none';
        }

        function slideForward() {
            const visibleSlides = getVisibleSlides();
            if (currentIndex < slides.length - visibleSlides) {
                currentIndex++;
                updateSliderPosition();
            }
        }

        function slideBackward() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSliderPosition();
            }
        }

        
        nextButton.addEventListener('click', () => {
            slideForward();
            updateButtonVisibility();
        });

        
        prevButton.addEventListener('click', () => {
            slideBackward();
            updateButtonVisibility();
        });

        
        slidesContainer.addEventListener('touchstart', (event) => {
            startX = event.touches[0].clientX;
        });

        slidesContainer.addEventListener('touchend', (event) => {
              const endX = event.changedTouches[0].clientX;
              const diffX = startX - endX;
              if (Math.abs(diffX) > 50) {
                  if (diffX > 0) {
                      slideForward();
                  } else {
                      slideBackward();
                  }
                  updateButtonVisibility();
              }
          });
          function initialize() {
              updateSliderPosition();
              updateButtonVisibility();
          }
          initialize();
          window.addEventListener('resize', () => {
              setTimeout(() => {
                  initialize();
              }, 100); 
          });
      });

      jQuery('li.footer_heading').click(function() {
          if (jQuery(window).width() < 767) {
              jQuery(this).siblings('li').slideToggle();
              jQuery(this).toggleClass('active');
          }
      });
      jQuery('li.footer_heading').each(function() {
          jQuery(this).click();
      });
      
      // footer script end

      jQuery(".tooltip-btn").click(function(e) {
        e.stopPropagation();
        jQuery(this).siblings(".tooltip").toggle();
      });
      jQuery(document).click(function() {
          jQuery(".icons_booster").find(".tooltip").hide();
      });
    });
  
    // ****ajax load for subcategories on top
    if (
      jQuery("#hidden-catid").length &&
      jQuery(".catalog-category-view").length
    ) {
      var catId = jQuery("#hidden-catid").val();
      var subcatConf = jQuery("#hidden-subcatconf").val();
      var url = "/common/index/subcatontop";
      jQuery.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        data: { catId: catId, subcatConf: subcatConf },
        success: function (response) {
          jQuery(".subcategories_of_category.outer_width_js").html(
            response.output
          );
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log(textStatus, errorThrown);
        },
      });
    }
  
    //    open menu on click only for mobile starts
    jQuery(".level0 > a").addClass("collapsed");
    jQuery(".level0 > a").click(function () {
      if (jQuery(window).width() < 768) {
        var $siblingsUl = jQuery(this).siblings("ul.level0");
  
        if ($siblingsUl.hasClass("expanded")) {
          $siblingsUl.addClass("collapsed").removeClass("expanded");
          jQuery(this).addClass("collapsed").removeClass("expanded");
        } else {
          $siblingsUl.addClass("expanded").removeClass("collapsed");
          jQuery(this).addClass("expanded").removeClass("collapsed");
        }
      } else if (jQuery(window).width() === 768) {
        window.location.href = jQuery(this).attr("href");
      }
    });
  jQuery('a[rel-href]').each(function() {
    var $linkToUpdate = jQuery(this); 
    var targetUrl = $linkToUpdate.attr('rel-href');
    if (targetUrl) {
        $linkToUpdate.attr('href', targetUrl);
    }
  });
    //    open menu on click only for mobile ends
  
    //****ajax load for homepage other sliders
    
      var $sliderHtmlElements = jQuery(".slider_html_here");
    
      if ($sliderHtmlElements.length) {
        $sliderHtmlElements.each(function () {
          var $this = jQuery(this);
          var url = window.location.origin + "/common/productslider/slider";
          var catId = $this.attr("param_catid");
          var heading = $this.attr("param_heading");
          var numPrds = $this.attr("param_numproducts");
            jQuery.ajax({
              url: url,
              type: "POST",
              dataType: "json",
              data: { cat_id: catId, heading: heading, num_prds: numPrds },
              success: function (response) {
                $this.html(response.output);
                $this
                  .find(".product_content_product_slider_inside")
                  .each(function () {
                    var $productItems = jQuery(this).find(".product-items");
                    var prdCount = $productItems.find("li").length;
                    var widthPerSlide = $productItems.find("li").outerWidth();
                    $productItems.width(widthPerSlide * prdCount);
      
                    var windowWidth = jQuery(window).width();
                    var slidesMoveOnClick = Math.floor(windowWidth / widthPerSlide);
                    var scrollLeft = $productItems.scrollLeft();
                    var totalWidthToMove = slidesMoveOnClick * prdCount;
                  });
              },
              error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
              },
            });
          
        });
      }
      
  
    //update price on cart page near fixed checkout button on mobile
    if (jQuery(".checkout-cart-index").length) {
      setInterval(function () {
        jQuery("a.action.primary.checkout.pc span.price_custom").text(
          jQuery("div#cart-totals .grand.totals .amount .price").text()
        );
      }, 1000);
    }
  
    // you save text display on order summary
  
    // function addNewRowCheckout(save) {
    //   if (save == "₹ 0" || save == "giftvoucher") {
    //     jQuery("table.data.table.totals tr.you-save").remove();
    //     jQuery.removeCookie("total-save-summary");
    //     if(save == "giftvoucher"){
    //       var giftCardAvail = `<div class="no-cod-showing">*Please order gift cards separately for Cash on Delivery. E-gift cards
    //       aren't available for COD.*</div>`;
    //       jQuery("div#checkout-payment-method-load").after(giftCardAvail);
    //     }else{
    //       jQuery(".no-cod-showing").remove();
    //     }
    //     return;
    //   } else {
    //     jQuery(".no-cod-showing").remove();
    //     var newRowHtml =
    //       '<tr class="you-save">' +
    //       '<th class="mark" colspan="1">' +
    //       '<span class="detailed">You Save</span>' +
    //       "</th>" +
    //       '<td class="amount">' +
    //       '<span class="price">' +
    //       save +
    //       "</span>" +
    //       "</td>" +
    //       "</tr>";
    //     jQuery("table.data.table.totals tr.you-save").remove();
    //     jQuery("table.data.table.totals tr.grand.totals").before(newRowHtml);
    //     jQuery.removeCookie("total-save-summary");
    //     jQuery.cookie("total-save-summary", save, {
    //       expires: 1,
    //       path: "/",
    //     });
  
    //     jQuery(".no-cod-showing").remove();
    //   }
    // }
  
    function makeAjaxCall() {
      jQuery.ajax({
        url: "/common/index/priceupdate/",
        method: "POST",
        dataType: "json",
        data: "",
        success: function (response) {
          console.log(response.save);
          addNewRowCheckout(response.save);
        },
      });
    }
  
    var initialValue = jQuery(
      ".checkout-cart-index table.data.table.totals tr.grand.totals td.amount span.price"
    ).text();
    function checkForChanges() {
      var currentValue = jQuery(
        ".checkout-cart-index table.data.table.totals tr.grand.totals td.amount span.price"
      ).text();
      if (currentValue !== initialValue) {
        makeAjaxCall();
        initialValue = currentValue;
      }
    }
    setInterval(checkForChanges, 1000);
  
    // end
  
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
      return;
    }
  
    function addNewRow() {
      var formData = getCookieValue("total-save-summary");
      if (formData) {
        if (formData == "₹ 0") {
          jQuery("tr#you-save").remove();
          return;
        } else {
          var formData = getCookieValue("total-save-summary");
          var newRowHtml =
            '<tr class="you-save" id="you-save">' +
            '<th class="mark" colspan="1">' +
            '<span class="detailed">You Save</span>' +
            "</th>" +
            '<td class="amount">' +
            '<span class="price">' +
            formData +
            "</span>" +
            "</td>" +
            "</tr>";
  
          jQuery("tr#you-save").remove();
          jQuery(".no-cod-showing").remove();
          jQuery(
            ".opc-block-summary table.data.table.table-totals tr.grand.totals"
          ).before(newRowHtml);
        }
      }
    }
    function checkoutOrderSummary() {
      if (jQuery("body").hasClass("checkout-index-index")) {
        jQuery("tr#you-save").remove();
        addNewRow();
      }
    }
    setInterval(checkoutOrderSummary, 4000);
  
    jQuery(document).on("input", 'input[name="postcode"]', function () {
      var postcodeValue = jQuery(this).val();
      if (postcodeValue.length < 6) {
          jQuery('input[name="postcode"]').css('border', '2px solid red');
          jQuery('button.action.primary.action-save-address').prop("disabled", true);
        }else{
          jQuery('input[name="postcode"]').css('border', '2px solid #66686C');
          jQuery('button.action.primary.action-save-address').prop("disabled", false);
      }
  });

    jQuery(document).on("input", 'input[name="postcode"]', function () {
      jQuery(this).addClass('minimum-length-6 validate-length');
      var $fields = jQuery(this).closest(".field").siblings(".field");
      var $select = $fields.find('select[name="region_id"]');
      var $input = $fields.find('input[name="city"]');
      var $input_2 = $fields.find('input[name="region"]');
      var pincode = jQuery(this).val();
      var pincode = pincode.replace(/[^0-9]/g, '');
      if (pincode.length > 6) {
        pincode = pincode.substring(0, 6);
      }
      jQuery(this).val(pincode);

      if (pincode.length === 6) {
        $select.add($input).prop("disabled", true);
        jQuery.ajax({
          url: document.location.origin + "/get-state?pincode=" + pincode,
          method: "GET",
          success: function (response) {
            var district_val = jQuery(response)
              .find(".region_details .district .value")
              .html();
            var state_val = jQuery(response)
              .find(".region_details .state .value")
              .html();
            $input.val(district_val || "");
            $input_2.val(district_val || "");
            $input_2.change();
            if (district_val) {
              jQuery(".field._required[name='shippingAddress.city']").addClass(
                "focused"
              );
              jQuery(".field[name='shippingAddress.city']").addClass("focused");
              jQuery('.field[name="shippingAddress.city"] input').change();
              jQuery("input[name='city']")
                .parent(".control")
                .parent(".field")
                .addClass("focused");
              jQuery("input[name='city']").change();
            } else {
              jQuery(".field._required[name='shippingAddress.city']").removeClass(
                "focused"
              );
              jQuery(".field[name='shippingAddress.city']").removeClass(
                "focused"
              );
              jQuery("input[name='city']")
                .parent(".control")
                .parent(".field")
                .removeClass("focused");
            }
            if (state_val) {
              var selectedValue = $select
                .find('option[data-title="' + state_val + '"]')
                .val();
              $select.val(selectedValue);
            } else {
              $select.val("");
            }
            $select.add($input).prop("disabled", false);
          },
          error: function (xhr, status, error) {
            console.error(xhr.responseText);
            $select.add($input).prop("disabled", false);
          },
        });
      }
  
      jQuery(document).on("click", "span.cross-btn-shipping", function (e) {
        e.preventDefault();
        jQuery("li#shipping").removeClass("fixed");
      });
      jQuery(".tablinks").click(function () {
        var containeropen = jQuery(this).attr("contextmenu");
        jQuery(".faqp .tabcontent").hide();
        jQuery("#" + containeropen).show();
      });
  
      jQuery(".tablinks").click(function () {
        jQuery(this).siblings().removeClass("active");
        jQuery(this).addClass("active");
      });
      // checkout js 15-04-24 start
    });
    jQuery(document).ready(function () {
      jQuery("#discount-giftcard-form-title").click(function () {
        jQuery("#discount-giftcard-form .content").slideToggle(300);
        jQuery("#block-gift-card-discount-heading").toggleClass("active");
      });
  
      jQuery(document).on(
        "click",
        ".opc-wrapper .shipping-address-item.selected-item",
        function () {
          if (
            !jQuery("li#shipping.checkout-shipping-address").hasClass("fixed")
          ) {
            jQuery("li#shipping.checkout-shipping-address").addClass("fixed");
          }
        }
      );
      jQuery(document).on(
        "click",
        "button.action.action-select-shipping-item, button.action.primary.action-save-address",
        function () {
          setTimeout(function () {
            if (
              jQuery("li#shipping.checkout-shipping-address").hasClass("fixed")
            ) {
              jQuery("li#shipping.checkout-shipping-address").removeClass(
                "fixed"
              );
            }
          }, 500);
        }
      );


  
      function AddFocusedClass() {
        jQuery(".checkout-index-index form input.input-text").each(function () {
          if (jQuery(this).val() != "") {
            jQuery(this).parent().parent(".field").addClass("focused");
            if (jQuery(this).closest("fieldset.field.street").length) {
              jQuery(this).closest("fieldset.field.street").addClass("focused");
            }
          }
        });
        jQuery(".checkout-index-index form select.select").each(function () {
          jQuery(this).parent().parent(".field").addClass("focused select");
        });
      }
      setInterval(AddFocusedClass , 1000);
      jQuery(document).on(
        "click",
        ".new-address-popup, button.action.edit-address-link",
        function () {
          AddFocusedClass();
        }
      );
      jQuery(document).on("change", ".field.field-select-billing", function () {
        AddFocusedClass();
      });
  
      jQuery(document).on(
        "click",
        "fieldset.field.street.admin__control-fields>legend.label",
        function () {
          jQuery(this).parent("fieldset").addClass("focused");
          jQuery(this)
            .parent("fieldset")
            .find("div[name='shippingAddress.street.0']")
            .find("input")
            .focus();
        }
      );
  
      jQuery(document).on(
        "focusin",
        ".checkout-index-index form.form input.input-text",
        function () {
          jQuery(this).parent().parent(".field").addClass("focused");
          if (jQuery(this).closest("fieldset.field.street").length) {
            jQuery(this).closest("fieldset.field.street").addClass("focused");
          }
        }
      );
      jQuery(document).on(
        "focusout",
        ".checkout-index-index form.form input.input-text",
        function () {
          if (jQuery(this).val() == "") {
            jQuery(this).parent().parent(".field").removeClass("focused");
            if (jQuery(this).closest("fieldset.field.street").length) {
              jQuery(this)
                .closest("fieldset.field.street")
                .removeClass("focused");
            }
          }
          jQuery('.field select.select[name="region_id"]').change();
        }
      );
    });
    // checkout js 15-04-24 end
    jQuery(document).on("click", "span.cross-btn-shipping", function(e) {
      e.preventDefault();
      jQuery('li#shipping').removeClass('fixed');
    });
    var liElements = jQuery('#block-collapsible-nav .nav.items > .nav.item');
    liElements.each(function() {
        var text = jQuery(this).text().trim();
        var newClass = text.replace(/\s+/g, '_').toLowerCase();
        jQuery(this).addClass(newClass);
    });
    jQuery(document).on("click", ".cross-icon-result", function(e) {
        e.preventDefault();
        jQuery('.wizzy-autocomplete-wrapper.onLeft').hide();
    });
    jQuery("dt:contains('Gift Card template')").addClass("hide").next("dd").addClass("hide");
        
    function createIframe(videoSrc, source) {
        let iframeSrc;
        if (source === "youtube") {
            const videoId = videoSrc.split(/(?:v=|\/shorts\/|\?si=)/)[1];
            iframeSrc = videoSrc.includes('/shorts/') ?
            `https://www.youtube.com/embed/${videoId}?autoplay=1&muted=1&playsinline=1&controls=1&modestbranding=1&rel=0` :
            `https://www.youtube.com/embed/${videoId}?autoplay=1&muted=1&playsinline=1&controls=1&rel=0`;
        } else {
            iframeSrc = `${videoSrc}?&playsinline=1controls=1`; // Add controls for non-YouTube sources
        }

        return `<iframe src="${iframeSrc}" id="yourIframeId" frameborder="0" allow="autoplay; fullscreen"></iframe>`;
    }

    // Function to get video groups based on video source
    function getVideoGroups(group) {
        let videoGroups = [];
        jQuery(`.video-trigger[group='${group}']`).each(function() {
            videoGroups.push(jQuery(this).attr('video-src'));
        });
        return videoGroups;
    }

    // Function to display video groups as thumbnails
    function displayVideoGroups(videoGroups) {
        const thumbnails = videoGroups.map(videoGroup => {
            let thumbnailSrc;
            if (videoGroup.includes('youtube.com')) {
                const videoId = videoGroup.split(/(?:v=|\/shorts\/|\?si=)/)[1];
                thumbnailSrc = `https://img.youtube.com/vi/${videoId}/0.jpg`;
            } else {
                const videoFileName = videoGroup.split('/').pop().split('.')[0];
                thumbnailSrc = `http://golfoy.com/media/wysiwyg/2024/thumbnails/${videoFileName}.jpg`;
            }
            return `<img src="${thumbnailSrc}" class="video-thumbnail" data-video-src="${videoGroup}">`;
        });
        jQuery("#exampleCustomModal .video-groups").html(thumbnails.join(""));
        jQuery("#exampleCustomModal .video-groups").html(thumbnails.join(""));
    }

    // Show the modal and load video groups when the trigger div is clicked
    jQuery(".video-trigger").click(function() {
        const videoSrc = jQuery(this).attr("video-src");
        const videoTitle = jQuery(this).attr("video-title");
        const source = jQuery(this).attr("source");
        const group = jQuery(this).attr("group");
        const iframe = createIframe(videoSrc, source);

        jQuery("#exampleCustomModal .custom-modal-title").text(videoTitle);
        jQuery("#exampleCustomModal .video-container").html(iframe);
        const videoGroups = getVideoGroups(group);
        displayVideoGroups(videoGroups);

        if (group === "short_videos") {
            jQuery("#exampleCustomModal").addClass("short-video-modal");
        } else {
            jQuery("#exampleCustomModal").removeClass("short-video-modal");
        }

        jQuery("#exampleCustomModal").css('display', 'block');
        jQuery("body").addClass("custom-videos");
    });

    // Handle click on video thumbnail
    jQuery(document).on("click", ".video-thumbnail", function() {
        const videoSrc = jQuery(this).data("video-src");
        const source = videoSrc.includes('youtube.com') ? "youtube" : "local";
        const iframe = createIframe(videoSrc, source);

        jQuery(".video-container").html(iframe);
    });

    // Hide the modal when the close button is clicked
    jQuery(".close, #closeCustomModal").click(function() {
        jQuery("#exampleCustomModal").css('display', 'none');
        jQuery("#exampleCustomModal .video-container").html('');
        jQuery("body").removeClass("custom-videos");
        jQuery("#exampleCustomModal").removeClass("short-video-modal"); // Ensure class is removed when closing the modal
    });

    // Close the modal when clicking outside the modal content
    jQuery(window).click(function(event) {
        if (jQuery(event.target).hasClass('custom-modal')) {
            jQuery("#exampleCustomModal").css('display', 'none');
            jQuery("#exampleCustomModal .video-container").html('');
            jQuery("body").removeClass("custom-videos");
            jQuery("#exampleCustomModal").removeClass("short-video-modal"); // Ensure class is removed when closing the modal
        }
    });
});

require(['jquery'], function($) {
    $(document).ready(function() {
      // header js for $ start

      $(document).on("click", "span.cross-btn-shipping", function(e) {
          e.preventDefault();
          $('li#shipping').removeClass('fixed');
      });
      $(document).on("click", ".cross-icon-result", function(e) {
          e.preventDefault();
          $('.wizzy-autocomplete-wrapper.onLeft').hide();
      });
      var liElements = $('#block-collapsible-nav .nav.items > .nav.item');
      liElements.each(function() {
          var text = $(this).text().trim();
          var newClass = text.replace(/\s+/g, '_').toLowerCase();
          $(this).addClass(newClass);
      });

      function addClassBasedOnText(element, text, className) {
        if (element instanceof HTMLElement) {
            const elementText = element.textContent.trim();
            if (elementText === text) {
                element.classList.add(className);
            }
        } else {
            console.warn("Invalid element provided to addClassBasedOnText");
        }
      }

        function changeAccountClass() {
          const titles = document.querySelectorAll('.title.block-collapsible-nav-title');
          titles.forEach(title => {
              const titleText = title.textContent.trim();
              switch (titleText) {
                  case 'My Account':
                      addClassBasedOnText(title, 'My Account', 'myaccount');
                      break;
                  case 'My Orders':
                      addClassBasedOnText(title, 'My Orders', 'myorders');
                      break;
                  case 'My Product Reviews':
                      addClassBasedOnText(title, 'My Product Reviews', 'myproductreviews');
                      break;
                  case 'My Gift Cards':
                      addClassBasedOnText(title, 'My Gift Cards', 'mygiftcards');
                      break;
                  case 'My Wish List':
                      addClassBasedOnText(title, 'My Wish List', 'mywishlist');
                      break;
                  case 'Address Book':
                      addClassBasedOnText(title, 'Address Book', 'addressbook');
                      break;
                  case 'Transactions':
                      addClassBasedOnText(title, 'Transactions', 'transactions');
                      break;
                  case 'My Referral':
                      addClassBasedOnText(title, 'My Referral', 'my_referral');
                      break;
                  default:
                      addClassBasedOnText(title, 'Reward Dashboard', 'rewarddashboard');
              }

          });
        }
        setInterval(changeAccountClass, 400);

        $(".close_notice").on("click", function() {
            $("section#home").hide();
        });

      function isElementInViewport(el) {
          var rect = el.getBoundingClientRect();
          return (
              rect.top >= 0 &&
              rect.left >= 0 &&
              rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
              rect.right <= (window.innerWidth || document.documentElement.clientWidth)
          );
      }

      function loadImagesIfVisible() {
          $('img[img-src]').each(function() {
              if (isElementInViewport(this)) {
                  $(this).attr('src', $(this).attr('img-src'));
                  $(this).removeAttr('img-src');
              }
          });
      }

      loadImagesIfVisible();

      $(window).scroll(function() {
          loadImagesIfVisible();
      });


      // header js for $ end

  });
});