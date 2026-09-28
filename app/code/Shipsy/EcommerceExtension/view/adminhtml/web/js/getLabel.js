define([
    'jquery',
    'uiElement',
], function ($) {
    'use strict';

    return function (config) { 
        $(document).on("click",`input[id^=label${config.referenceNumber}]`, async () => {
            const url = config.baseUrl + '/api/ecommerce/shippinglabel/link?reference_number=' + config.referenceNumber;
            console.log(config.orgId);
            const cookieValue = Object.fromEntries(document.cookie.split('; ').map(cookie => {
                const [ key, ...v ] = cookie.split('=');
                return [ key, v.join('=') ];
            }));
            let response =  await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'organisation-id': config.orgId,
                    'shop-url': config.shopUrl,
                    'shop-origin':'magento',
                    'customer-id': cookieValue['customer-id'],
                    'access-token': cookieValue['access-token-shipsy']
                }
            });
            let data = await response.json();
            if ('data' in data) {
                const requiredData = data.data;
                const getLabelButton = document.querySelector('#label'+config.referenceNumber);
                // Create anchor element. 
                const downloadLabelButton = document.createElement('a');
                downloadLabelButton.classList.add("btn", "btn-success");
                const downloadLabelButtonText = document.createTextNode("Download"); 
                    
                // Append the text node to anchor element. 
                downloadLabelButton.appendChild(downloadLabelButtonText);

                // Set the title.
                downloadLabelButton.target = "_blank";
                
                // Set the href property. 
                downloadLabelButton.href = requiredData.url;
                getLabelButton.parentNode.replaceChild(downloadLabelButton, getLabelButton);
            }
            else {
                alert("Error occurred while generating label: " + data.error.message);
            }
        });   
    };
});