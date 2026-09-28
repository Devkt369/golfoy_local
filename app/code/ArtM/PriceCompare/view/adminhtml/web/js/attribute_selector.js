require(['jquery'], function($) {
    $('#attribute_selector_form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        var ajaxUrl = form.attr('action');
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            showLoader: true,
            success: function(resp) {
                alert('Saved successfully!');
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('Error: ' + xhr.responseText);
            }
        });
    });
});
