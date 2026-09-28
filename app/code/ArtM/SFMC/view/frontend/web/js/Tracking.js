require(["jquery"], function (jQuery) {
    jQuery(document).ready(function () {
        function getQueryParams() {
            let params = new URLSearchParams(window.location.search);
            let queryParams = {};
            let allowedKeys = ["sfmc_id", "utm_campaign", "utm_id", "utm_medium", "utm_source", "utm_term"];
        
            params.forEach(function (value, key) {
                if (allowedKeys.includes(key)) { 
                    queryParams[key] = value;
                    setCookie(key, value, 30); 
                }
            });
        
            return queryParams;
        }

        function setCookie(name, value, days) {
            let expires = "";
            if(name && value){
                if (days) {
                    let date = new Date();
                    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000); 
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
            }
        }
        
        getQueryParams();
    });
});
