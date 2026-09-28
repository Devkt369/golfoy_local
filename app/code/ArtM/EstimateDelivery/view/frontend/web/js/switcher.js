define(["jquery"], function ($) {
    "use strict";
    
    let lastSku = null;
    return {
        switch: function (config, productId) {
            let simpleSku = null;
            window.selectedSimpleProductId = productId;
            if (config.sku && config.sku[productId]) {
                simpleSku = config.sku[productId];
            }

            if (!simpleSku) return;

            $(".product.attribute.sku .value").html(simpleSku);
            window.selectedSimplesimpleSku = simpleSku;
            runEstimateDelivery(simpleSku);
        },
    };

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric"
        });
    }
    
    function runEstimateDelivery(sku) {

        if (sku === lastSku) {
            return;
        }

        lastSku = sku;
        $.ajax({
            url: "/estimatedelivery/estimateDelivery/getEstimateDelivery",
            type: "POST",
            dataType: "json",
            data: { sku: sku },

            success: function (data) {
                const box = document.getElementById("estimate-delivery-box");
                if (!box) return;

                if (!data || !data.estimated_delivery_date) {
                    // console.warn("No delivery data returned");
                    return;
                }

                const formattedDate = formatDate(data.estimated_delivery_date);
                const expressDeliveryIcon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="300" height="300" xmlns:svgjs="http://svgjs.dev/svgjs"><g width="100%" height="100%" transform="matrix(1,0,0,1,0,0)"><g><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="352.999" y1="336.637" x2="211.428" y2="336.637" fill="none" stroke="#000000" stroke-width="12"></line><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M186.517,140.359h-82.769c-9.337,0-16.915,7.561-16.915,16.915v162.449c0,9.337,7.578,16.915,16.915,16.915h36.705" fill="none" stroke="#000000" stroke-width="12"></path><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M388.486,302.283V188.211v-30.937c0-9.354-7.578-16.915-16.915-16.915h-93.194" fill="none" stroke="#000000" stroke-width="12"></path><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M423.973,336.637h61.113c9.337,0,16.915-7.578,16.915-16.915v-50.118c0-44.959-36.451-81.393-81.393-81.393h-32.121v114.073" fill="none" stroke="#000000" stroke-width="12"></path><circle style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" cx="175.946" cy="337.812" r="33.829" fill="none" stroke="#000000" stroke-width="12"></circle><circle style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" cx="388.483" cy="337.812" r="33.829" fill="none" stroke="#000000" stroke-width="12"></circle><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="232.474" y1="140.359" x2="232.474" y2="140.359" fill="none" stroke="#000000" stroke-width="12"></line><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="175.946" y1="337.812" x2="175.946" y2="337.812" fill="none" stroke="#000000" stroke-width="12"></line><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="388.483" y1="337.812" x2="388.483" y2="337.812" fill="none" stroke="#000000" stroke-width="12"></line><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M499.627,260.71h-61.625c-4.613,0-8.353-3.74-8.353-8.353v-61.799" fill="none" stroke="#000000" stroke-width="12"></path><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="36.664" y1="183.474" x2="197.349" y2="183.474" fill="none" stroke="#000000" stroke-width="12"></line><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="10" y1="223.626" x2="140.189" y2="223.626" fill="none" stroke="#000000" stroke-width="12"></line><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="36.664" y1="263.778" x2="197.349" y2="263.778" fill="none" stroke="#000000" stroke-width="12"></line></g></g></svg>';
                const standardDeliveryIcon = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="300" height="300" xmlns:svgjs="http://svgjs.dev/svgjs"><g width="100%" height="100%" transform="matrix(1,0,0,1,0,0)"><g><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="352.999" y1="336.637" x2="211.428" y2="336.637" fill="none" stroke="#000000" stroke-width="12"></line><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M186.517,140.359h-82.769c-9.337,0-16.915,7.561-16.915,16.915v162.449c0,9.337,7.578,16.915,16.915,16.915h36.705" fill="none" stroke="#000000" stroke-width="12"></path><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M388.486,302.283V188.211v-30.937c0-9.354-7.578-16.915-16.915-16.915h-93.194" fill="none" stroke="#000000" stroke-width="12"></path><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M423.973,336.637h61.113c9.337,0,16.915-7.578,16.915-16.915v-50.118c0-44.959-36.451-81.393-81.393-81.393h-32.121v114.073" fill="none" stroke="#000000" stroke-width="12"></path><circle style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" cx="175.946" cy="337.812" r="33.829" fill="none" stroke="#000000" stroke-width="12"></circle><circle style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" cx="388.483" cy="337.812" r="33.829" fill="none" stroke="#000000" stroke-width="12"></circle><path style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" d="M499.627,260.71h-61.625c-4.613,0-8.353-3.74-8.353-8.353v-61.799" fill="none" stroke="#000000" stroke-width="12"></path><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="136" y1="183.474" x2="297" y2="183.474" fill="none" stroke="#000000" stroke-width="12"></line><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="136" y1="223.626" x2="243" y2="223.626" fill="none" stroke="#000000" stroke-width="12"></line><line style="stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;" x1="136" y1="263.778" x2="189" y2="263.778" fill="none" stroke="#000000" stroke-width="12"></line></g></g></svg>';


                if (data.express_shipping) {
                    box.innerHTML = `
                        <span class="estimation_time_text">
                            <span class="express_icon">
                                ${expressDeliveryIcon}
                            </span>
                            <span class="deliver-title"> 
                                <span class="express-title">Express Delivery </span>
                                <span>by </span>
                            </span>
                            <span class="date">${formattedDate}</span>
                        </span>
                    `;
                } else {
                    box.innerHTML = `
                        <span class="estimation_time_text">
                            <span class="express_icon">${standardDeliveryIcon}</span>
                            <span class="deliver-title">
                                <span class="standard-title">Estimated Delivery </span>
                                <span>by </span>
                            </span>
                            <span class="date">${formattedDate}</span>
                        </span>
                    `;
                }
            },

            error: function (xhr, status, error) {
                console.error("AJAX Error:", error);
            },
        });
    }
});
