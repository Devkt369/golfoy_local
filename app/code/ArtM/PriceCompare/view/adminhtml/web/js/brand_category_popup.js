require(['jquery', 'Magento_Ui/js/modal/modal'], function($, modal) {

    $(function() {

        function showPopup(type) {

            var popupHtml =
                '<div class="popup-content">' +
                    '<input type="text" id="new_' + type + '" placeholder="Enter ' + type + ' name" /> ' +
                    '<button id="save_' + type + '">Save</button>' +
                '</div>';

            var options = {
                type: 'popup',
                title: 'Add ' + type.charAt(0).toUpperCase() + type.slice(1),
                buttons: []
            };

            var popupElement = $('<div id="' + type + '_popup">' + popupHtml + '</div>');

            modal(options, popupElement);

            popupElement.modal('openModal');

            $('#save_' + type).on('click', function() {

                var val = $('#new_' + type).val();

                if (!val) {
                    alert('Please enter ' + type + ' name');
                    return;
                }

                // Use correct admin URL from template
                var ajaxUrl = (type === 'brand') ? window.ajaxBrandUrl : window.ajaxCategoryUrl;
                console.log('AJAX URL:', ajaxUrl);
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        type: type,
                        name: val,
                        form_key: window.FORM_KEY
                    },
                    dataType: 'json',
                    showLoader: true,

                    success: function(resp) {

                        if (resp.success) {

                            var select = $('#' + type);

                            // Prevent duplicate option
                            if (select.find('option[value="' + resp.id + '"]').length === 0) {

                                select.append(
                                    '<option value="' + resp.id + '">' +
                                    resp.name +
                                    '</option>'
                                );
                            }

                            select.val(resp.id);

                            popupElement.modal('closeModal');

                        } else {

                            alert(resp.message || 'Something went wrong');
                        }
                    },

                    error: function(xhr) {

                        console.log(xhr);

                        alert('Error saving ' + type);
                    }
                });
            });
        }

        $('#add_brand').on('click', function() {
            showPopup('brand');
        });

        $('#add_category').on('click', function() {
            showPopup('category');
        });

    });

});