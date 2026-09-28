define(['jquery'], function($) {
    'use strict';

    return function (config) {
        var $form = $(config.formSelector);
        var $result = $(config.resultSelector);

        $form.on('submit', function (e) {
            e.preventDefault();

            $result.empty();
            var $btn = $form.find('button[type=submit]');
            $btn.prop('disabled', true);

            var fd = new FormData($form[0]);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (resp) {
                if (!resp) {
                    $result.html('<div class="message message-error">No response from server.</div>');
                    return;
                }
                if (resp.success) {
                    var html = '<div class="message message-success"><strong>Allocated: ' + resp.allocated + '</strong></div>';
                    if (resp.coupons && resp.coupons.length) {
                        html += '<table class="data-grid"><thead><tr><th>Coupon Code</th></tr></thead><tbody>';
                        resp.coupons.forEach(function(c){ html += '<tr><td>' + $('<div>').text(c).html() + '</td></tr>'; });
                        html += '</tbody></table>';
                    }
                    $result.html(html);
                    $form[0].reset();
                } else {
                    $result.html('<div class="message message-error">' + $('<div>').text(resp.message || 'Error').html() + '</div>');
                }
            }).fail(function () {
                $result.html('<div class="message message-error">Request failed.</div>');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    };
});
