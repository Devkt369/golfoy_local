
        function sendAjaxRequest(orderId) {
            var url = 'http://mag.snapmint.com/snapmint/refund/snapmintrefund'; // Replace with your controller URL

            // Create a new XMLHttpRequest object
            var xhr = new XMLHttpRequest();

            // Configure the AJAX request
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

            // Define the callback function when the AJAX request is complete
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        // Handle the response from the server if needed
                        var response = JSON.parse(xhr.responseText);
                        alert(response.message);
                    } else {
                        // Handle errors here
                        alert('Error: ' + xhr.status);
                    }
                }
            };

            // Send the request with the order ID as data
            xhr.send('order_id=' + orderId);
        }
   
