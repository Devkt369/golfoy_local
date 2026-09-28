require(["jquery"], function ($) {
  $(document).ready(function () {
    $(document).on(
      "click",
      ".sorting_overlay.visible-xs.wizzy-sort-mobile-entry",
      function (e) {
        e.preventDefault();
        $("span.wizzy-common-select-label").click();
      },
    );
    $(document).on(
      "click",
      ".filter_heading_popup, .close-icon-category.filter_toggler.filter-cross-btn.visible-xs.apply-bar-btn",
      function (e) {
        $(".wizzy-filters-close-btn").click();
      },
    );
    $(document).on(
      "click",
      ".wizzy-common-select-options span.custom-sort-close",
      function () {
        $(".wizzy-common-select-options").slideUp(400);
        $(".wizzy-common-select-overlay").fadeOut(400);
      },
    );

    // product slider ajax
    var $sliders = $(".slider_html_here");

    if ($sliders.length) {
      var baseUrl = window.location.origin + "/common/productslider/slider";

      $sliders.each(function () {
        var $slider = $(this);

        var requestData = {
          cat_id: $slider.attr("param_catid"),
          heading: $slider.attr("param_heading"),
          num_prds: $slider.attr("param_numproducts"),
        };

        $.ajax({
          url: baseUrl,
          type: "POST",
          dataType: "json",
          data: requestData,
        })
          .done(function (response) {
            if (!response || !response.output) return;
            $slider.html(response.output);
          })
          .fail(function (xhr, status, error) {
            console.error("Slider AJAX error:", status, error);
          });
      });
    }

    $(document).on(
      "click",
      ".product_content_product_slider_inside .right_arrow",
      function () {
        console.log("right_arrow");
        var $slider = $(this)
          .closest(".product_content_product_slider_inside")
          .find(".product_content_product_slider_before_list .product-items");
        $slider.scrollLeft($slider.scrollLeft() + 300);
      },
    );

    $(document).on(
      "click",
      ".product_content_product_slider_inside .left_arrow",
      function () {
        console.log("left_arrow");

        var $slider = $(this)
          .closest(".product_content_product_slider_inside")
          .find(".product_content_product_slider_before_list .product-items");
        $slider.scrollLeft($slider.scrollLeft() - 300);
      },
    );

    // product slider ajax

    $(document).on("click", ".ftab ul li", function () {
      $(".ftab ul li").removeClass("active");
      $(this).addClass("active");
      var li_value = $(this).data("li");
      $(".target").hide();
      $("." + li_value).show();
    });

    $(document).on("click", ".tablinks", function () {
      var cityName = $(this).data("city");
      $(".tabcontent").hide();
      $(".tablinks").removeClass("active");
      $("#" + cityName).show();
      $(this).addClass("active");
    });

    $("#defaultOpen").addClass("active");
    var defaultCity = $("#defaultOpen").data("city");
    $("#" + defaultCity).show();
    $(document).on("click", ".accordion-2", function () {
      $(this).next(".pane").slideToggle(); // Toggle the next pane
      $(this).toggleClass("minus");
    });

    $(".nav-brands > a").on("click", function (e) {
      window.location.href = $(this).attr("href");
    });
    console.log("header.js");

    /**
     * Login / Signup UI Handler with Optimized Customer State Detection
     * - Fetches customer login & reward data via AJAX
     * - Updates UI based on authentication state
     * - Performance-optimized for PLP & header usage
     * - Mobile-friendly dropdown handling
     */

    // Performance optimized - Cache DOM elements
    var $customerName = $("[data-customer-name]"),
      $customerInitial = $("[data-customer-initial]"),
      $customerPoints = $("[data-customer-points]"),
      $loggedIn = $('[data-customer-container="logged-in"]'),
      $loggedOut = $('[data-customer-container="logged-out"]'),
      $logoutButtons = $("#logout-button, [data-customer-logout-button]"),
      $accountSection = $(".account-section"),
      $window = $(window);

    // Request debouncing to prevent multiple simultaneous calls
    var isLoading = false;

    function loadCustomerData() {
      // Prevent multiple simultaneous requests
      if (isLoading) return;

      isLoading = true;
      var dataUrl = $accountSection.data("customer-data-url");

      $.ajax({
        url: dataUrl,
        type: "GET",
        data: {
          account: 1,
          points: 1,
          logged_in: 1,
        },
        dataType: "json",
        cache: false, // Always fetch fresh login status
        timeout: 10000, // 10 second timeout
      })
        .done(function (response) {
          updateUI(response);
        })
        .fail(function (xhr, status, error) {
          console.warn("Customer data request failed:", status, error);
          // Show logged out state as fallback
          updateUI({ logged_in: false });
        })
        .always(function () {
          isLoading = false;
        });
    }

    // Separate UI update function for better performance
    function updateUI(response) {
      var windowWidth = $window.width();

      if (response.logged_in) {
        $customerName.text(response.name || "");

        if (response.name && response.name.length > 0) {
          $customerInitial.text(response.name.charAt(0).toUpperCase());
        }

        if (response.balance !== undefined) {
          $customerPoints.html(
            '<span class="customer-profile__rewards-label">Points:</span> <span class="customer-profile__rewards-value">' +
              response.balance +
              "</span>",
          );
        }

        $loggedIn.show();
        $loggedOut.hide();

        if (windowWidth < 768) {
          $logoutButtons.show();
        }

        $accountSection.removeClass("signon_popup_opener");
      } else {
        $loggedOut.show();
        $loggedIn.hide();
        $logoutButtons.hide();
        $accountSection.addClass("signon_popup_opener");
      }
    }

    // Load customer data immediately to prevent flash
    loadCustomerData();

    // Cache dropdown elements - Use permanent icon
    var $accountIcon = $(".account-icon.permanent-icon"),
      $accountDropdown = $(".account-dropdown-menu");

    // Optimized mobile dropdown handling
    if ($accountIcon.length && $accountDropdown.length) {
      // Use more efficient event delegation
      $accountIcon.on("click.accountDropdown", function (e) {
        if (window.innerWidth < 769) {
          // Only handle dropdown if user is logged in (no signon_popup_opener class)
          if (!$accountSection.hasClass("signon_popup_opener")) {
            e.preventDefault();
            e.stopPropagation();
            $accountDropdown.toggleClass("mobile-active");
          }
          // If user is logged out (has signon_popup_opener class), let the event bubble to popup handler
        }
      });

      // Optimized document click handler with debouncing
      var clickTimeout;
      $(document).on("click.accountDropdown", function (e) {
        if (window.innerWidth < 769) {
          // Only handle dropdown closing if user is logged in (no signon_popup_opener class)
          if (!$accountSection.hasClass("signon_popup_opener")) {
            clearTimeout(clickTimeout);
            clickTimeout = setTimeout(function () {
              if (
                $accountSection.length &&
                !$accountSection.is(e.target) &&
                !$accountSection.has(e.target).length
              ) {
                $accountDropdown.removeClass("mobile-active");
              }
            }, 10);
          }
        }
      });

      // Throttled resize handler
      var resizeTimeout;
      $window.on("resize.accountDropdown", function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
          if (window.innerWidth >= 769) {
            $accountDropdown.removeClass("mobile-active");
          }
        }, 100);
      });
    }

    // Login / Signup UI Handler end

    // footer js start
    $(".footer-header").on("click", function () {
      var $column = $(this).closest(".footer-column");
      var $menu = $column.find("ul");

      if ($column.hasClass("active")) {
        $menu.css("height", $menu[0].scrollHeight + "px").animate(
          {
            height: "0px",
          },
          300,
        );
        $column.removeClass("active");
      } else {
        $menu.css("height", $menu[0].scrollHeight + "px");
        $column.addClass("active");
      }
    });
    // footer js end
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const items = document.querySelectorAll("#myCarouselbrand .item");
  const prevBtn = document.getElementById("prevSlide");
  const nextBtn = document.getElementById("nextSlide");

  let currentIndex = 0;
  const total = items.length;
  let autoSlide;

  function showSlide(index) {
    if (index < 0) index = total - 1;
    if (index >= total) index = 0;

    items[currentIndex].classList.remove("active");
    items[index].classList.add("active");

    currentIndex = index;
  }

  function startAutoSlide() {
    autoSlide = setInterval(() => {
      showSlide(currentIndex + 1);
    }, 3000);
  }

  function stopAutoSlide() {
    clearInterval(autoSlide);
  }

  nextBtn.addEventListener("click", function (e) {
    e.preventDefault();
    stopAutoSlide();
    showSlide(currentIndex + 1);
    startAutoSlide();
  });

  prevBtn.addEventListener("click", function (e) {
    e.preventDefault();
    stopAutoSlide();
    showSlide(currentIndex - 1);
    startAutoSlide();
  });

  // Mobile Swipe Support
  let startX = 0;

  document
    .querySelector("#myCarouselbrand")
    .addEventListener("touchstart", function (e) {
      startX = e.touches[0].clientX;
    });

  document
    .querySelector("#myCarouselbrand")
    .addEventListener("touchend", function (e) {
      let endX = e.changedTouches[0].clientX;
      if (startX - endX > 50) {
        showSlide(currentIndex + 1);
      } else if (endX - startX > 50) {
        showSlide(currentIndex - 1);
      }
    });

  startAutoSlide();
});

document.addEventListener("DOMContentLoaded", function () {
  var link = document.createElement("link");
  link.rel = "stylesheet";
  link.href =
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css";
  document.head.appendChild(link);

  // Mobile search toggle functionality
  const searchToggle = document.querySelector(".mobile-search-toggle");
  const searchBar = document.querySelector(".mobile-search-bar");

  if (searchToggle && searchBar) {
    searchToggle.addEventListener("click", function () {
      searchBar.classList.toggle("active");
    });

    // Close search bar when clicking outside
    document.addEventListener("click", function (event) {
      if (
        !searchToggle.contains(event.target) &&
        !searchBar.contains(event.target)
      ) {
        searchBar.classList.remove("active");
      }
    });
  }

  // Add ui-menu-active class on hover for li.level0 elements
  const level0Items = document.querySelectorAll("li.level0");

  level0Items.forEach(function (item) {
    // Add class on mouse enter
    item.addEventListener("mouseenter", function () {
      this.classList.add("ui-menu-active");
    });

    // Remove class on mouse leave
    item.addEventListener("mouseleave", function () {
      this.classList.remove("ui-menu-active");
    });
  });

  // Sign-on popup functionality with event delegation (optimized for mobile)
  const signonPopup = document.getElementById("signon_button");
  const popupCloseBtn = document.querySelector(
    "#signon_button .modal-header button.close",
  );

  // Debug: Check if popup element exists
  // console.log('Signon popup element:', signonPopup);

  // Use event delegation to handle clicks on dynamically added elements
  document.addEventListener("click", function (e) {
    // Check if clicked element or its parent has signon_popup_opener class
    const clickedElement = e.target;
    const accountSection = clickedElement.closest(".signon_popup_opener");

    if (accountSection) {
      // Check if clicked on icon, login link, or signup link
      if (
        clickedElement.closest(".account-icon") ||
        clickedElement.classList.contains("login-link") ||
        clickedElement.classList.contains("signup-link")
      ) {
        e.preventDefault();
        e.stopPropagation(); // Prevent event bubbling for mobile
        // console.log('Account section clicked - opening popup');

        if (signonPopup) {
          // console.log('Showing popup');
          signonPopup.style.display = "block";

          // Mobile optimization: ensure popup is visible and focused
          if (window.innerWidth < 769) {
            document.body.style.overflow = "hidden"; // Prevent background scrolling on mobile
            signonPopup.scrollTop = 0; // Scroll to top of popup
          }
        } else {
          // console.log('Popup element not found!');
          // Remove alert for better UX - just log the error
          console.error("Sign-in popup element not found on page");
        }
      }
    }
  });

  // Hide popup when clicking close button
  if (popupCloseBtn) {
    popupCloseBtn.addEventListener("click", function (e) {
      e.preventDefault();
      if (signonPopup) {
        signonPopup.style.display = "none";

        // Mobile optimization: restore body scrolling
        if (window.innerWidth < 769) {
          document.body.style.overflow = "";
        }
      }
    });
  }

  // Mobile optimization: Close popup when clicking outside (for mobile usability)
  if (signonPopup) {
    signonPopup.addEventListener("click", function (e) {
      // Close if clicking on the backdrop (not the popup content)
      if (e.target === signonPopup) {
        signonPopup.style.display = "none";
        if (window.innerWidth < 769) {
          document.body.style.overflow = "";
        }
      }
    });
  }
});
