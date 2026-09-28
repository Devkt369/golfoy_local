require(['jquery'], function($) {
    $(function() {
        var websiteUrlInputs = {};
        function renderWebsiteUrlInputs() {
            var selected = $('#websites').val() || [];
            var container = $('#website_urls_container');
            container.empty();
            selected.forEach(function(websiteId) {
                var label = $('#websites option[value="'+websiteId+'"]:selected').text();
                var input = $('<input>', {
                    type: 'text',
                    name: 'website_urls['+websiteId+']',
                    class: 'admin__control-text',
                    placeholder: 'Enter URL for ' + label,
                    required: true
                });
                if (websiteUrlInputs[websiteId]) {
                    input.val(websiteUrlInputs[websiteId]);
                }
                input.on('input', function() {
                    websiteUrlInputs[websiteId] = $(this).val();
                });
                container.append(
                    $('<div class="admin__field">').append(
                        $('<label class="admin__field-label">').text('URL for ' + label),
                        $('<div class="admin__field-control">').append(input)
                    )
                );
            });
        }
        $('#websites').on('change', function() {
            renderWebsiteUrlInputs();
        });
        // On form submit, check all URLs are filled
        $('#product_form').on('submit', function(e) {
            var selected = $('#websites').val() || [];
            var allFilled = true;
            selected.forEach(function(websiteId) {
                if (!websiteUrlInputs[websiteId] || websiteUrlInputs[websiteId].trim() === '') {
                    allFilled = false;
                }
            });
            if (!allFilled) {
                alert('Please enter URLs for all selected websites.');
                e.preventDefault();
            }
        });
        // Initial render if any websites are preselected
        renderWebsiteUrlInputs();
    });
});
