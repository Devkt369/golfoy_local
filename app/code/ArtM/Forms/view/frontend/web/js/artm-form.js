require(["jquery"], function ($) {
    $(document).ready(function () {
        var artmDebugMode = /(?:\?|&)artm_form_debug=1(?:&|$)/.test(window.location.search);

        function artmCollectPayloadPreview(formData) {
            var entries = [];
            formData.forEach(function (value, key) {
                if (value instanceof File) {
                    entries.push(key + ': [file] ' + value.name + ' (' + value.size + ' bytes)');
                } else {
                    entries.push(key + ': ' + String(value));
                }
            });
            return entries;
        }

        function artmRenderDebug(form, title, lines) {
            if (!artmDebugMode) {
                return;
            }

            var $panel = form.find('.artm-submit-debug-panel');
            if (!$panel.length) {
                $panel = $('<pre class="artm-submit-debug-panel" style="background:#111;color:#d8ffd8;padding:12px;border-radius:8px;white-space:pre-wrap;word-break:break-word;margin:12px 0;"></pre>');
                form.prepend($panel);
            }

            var text = ['ARTM FRONTEND DEBUG: ' + title].concat(lines).join('\n');
            $panel.text(text);
            console.log('[artm-debug] ' + title + '\n' + text);
        }

        // Function to initialize a specific form
        function initializeForm(form) {
            // Exit if form is invalid or already initialized
            if (!form.length || form.data('initialized')) return;

            // Mark form as initialized to prevent duplicate bindings
            form.data('initialized', true);

            // Function to check conditional fields
            function checkConditionalFields() {
                form.find('.artm-conditional-field[data-show-if]').each(function () {
                    var $row = $(this);
                    var cond = $row.data('show-if');
                    if (!cond) {
                        $row.show();
                        return;
                    }
                    var match = cond.match(/^([a-zA-Z0-9_]+)\s*([!=]=?)\s*(.+)$/);
                    if (!match) {
                        $row.show();
                        return;
                    }
                    var field = match[1],
                        op = match[2],
                        val = match[3];
                    // Support field names with spaces
                    var $input = form.find('[name="fields[' + field.replace(/(["'\\])/g, '\\$1') + ']"], [name="fields[' + field.replace(/(["'\\])/g, '\\$1') + '][]"]');
                    var inputVal = '';
                    if ($input.is(':radio')) {
                        inputVal = (form.find('[name="fields[' + field + ']"]:checked').val() || '').trim();
                    } else if ($input.is('select[multiple]')) {
                        inputVal = ($input.val() || []).map(function (v) { return v.trim(); });
                    } else if ($input.is(':checkbox')) {
                        inputVal = $input.is(':checked') ? ($input.val() || '').trim() : '';
                    } else if ($input.is(':input[type="date"]')) {
                        inputVal = ($input.val() || '').trim();
                        if (inputVal && inputVal.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            var d = inputVal.split('-');
                            inputVal = d[1].replace(/^0+/, '') + '/' + d[2].replace(/^0+/, '') + '/' + d[0];
                        }
                    } else {
                        inputVal = ($input.val() || '').trim();
                    }

                    var condVals = val.split(',').map(function (v) {
                        v = v.trim();
                        if (v.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            var d = v.split('-');
                            v = d[1].replace(/^0+/, '') + '/' + d[2].replace(/^0+/, '') + '/' + d[0];
                        }
                        return v;
                    });
                    var show = false;
                    if (Array.isArray(inputVal)) {
                        if (op === '==' || op === '=') {
                            show = condVals.some(function (v) { return inputVal.includes(v); });
                        } else if (op === '!=') {
                            show = condVals.every(function (v) { return !inputVal.includes(v); });
                        }
                    } else {
                        if (op === '==' || op === '=') {
                            show = condVals.includes(inputVal);
                        } else if (op === '!=') {
                            show = !condVals.includes(inputVal);
                        }
                    }

                    if (!show) {
                        $row.find(':input').each(function () {
                            if ($(this).is(':checkbox,:radio')) {
                                $(this).prop('checked', false);
                            } else {
                                $(this).val('');
                            }
                        });
                    }
                    $row.toggle(show);
                });
            }

            // Use event delegation for input changes on document level
            $(document).on('change input', '.artm-form input, .artm-form select, .artm-form textarea', function (e) {
                e.preventDefault();
                if ($(e.target).closest('.artm-form').is(form)) {
                    checkConditionalFields();
                }
            });
            checkConditionalFields();

            // Check for unique input names
            function validateUniqueNames() {
                var names = [];
                var isUnique = true;
                var duplicateName = null;

                form.find('input[name^="fields["], select[name^="fields["], textarea[name^="fields["]').each(function () {
                    var $this = $(this);
                    var name = $this.attr('name');

                    if (names.includes(name)) {
                        // A duplicate name was found.
                        // ONLY fail if it's NOT a radio button.
                        if (!$this.is(':radio')) {
                            isUnique = false;
                            duplicateName = name;
                            return false; // Break the loop - this is a real error.
                        }
                        // If it IS a radio button, this is normal. Do nothing and continue the loop.

                    } else {
                        // If the name is new, add it to the list.
                        names.push(name);
                    }
                });

                return { isUnique: isUnique, duplicateName: duplicateName };
            }

            // Submit handler with event delegation
            // $(document).on('submit', '.artm-form', function(e) {
            form.on('submit', function (e) {
                e.preventDefault(); // stop native submit right away
                var form = $(this); // capture current form instance

                $('.artm-captcha-error').hide();
                var valid = true;
                var firstInvalidLabel = null;

                // Validate required fields
                form.find('.form-field-row:visible').each(function () {
                    var row = $(this);
                    var label = row.find('label.required').first();
                    var input = row.find('input, select, textarea').first();
                    var reqText = label.find('.artm-required-text');
                    var isFilled = true;

                    if (label.length) {
                        if (input.is(':checkbox')) {
                            isFilled = input.is(':checked');
                        } else if (input.is(':radio')) {
                            var name = input.attr('name');
                            isFilled = row.find('input[type="radio"][name="' + name + '"]').is(':checked');
                        } else {
                            isFilled = !!$.trim(input.val());
                        }

                        if (!isFilled) {
                            label.addClass('artm-error');
                            reqText.show();
                            valid = false;
                            if (!firstInvalidLabel) {
                                firstInvalidLabel = label;
                            }
                        } else {
                            label.removeClass('artm-error');
                            reqText.hide();
                        }
                    }
                });

                // Validate unique names
                var nameValidation = validateUniqueNames();
                if (!nameValidation.isUnique) {
                    valid = false;
                    alert('The field name "' + nameValidation.duplicateName + '" is not unique.');
                    console.log('[artm] Validation failed: Duplicate field name - ' + nameValidation.duplicateName);
                    return;
                }

                if (!valid) {
                    if (firstInvalidLabel) {
                        var labelText = $.trim(firstInvalidLabel.clone().children().remove().end().text());
                        alert(labelText + ' cannot be empty');
                    } else {
                        alert('Please fill all required fields.');
                    }
                    console.log('[artm] Validation failed, not submitting');
                    return;
                }

                // Captcha validation
                if (form.find('.form-field-row.artm-captcha-row').length) {
                    var userVal = $.trim(form.find('#artm_captcha_input').val());
                    var answer = form.find('.artm-captcha-row').data('answer');

                    if (userVal === '' || isNaN(userVal) || parseInt(userVal) !== answer) {
                        form.find('.artm-captcha-error').text('Incorrect answer. Please try again.').show();
                        form.find('#artm_captcha_input').focus();
                        // reload captcha
                        $.get(window.location.href, { ajax_captcha: 1 }, function (html) {
                            var $newCaptcha = $(html).find('.artm-captcha-row');
                            if ($newCaptcha.length) {
                                form.find('.artm-captcha-row').replaceWith($newCaptcha);
                            }
                        });
                        return false;
                    } else {
                        form.find('.artm-captcha-error').hide();
                    }
                }

                // Submit via AJAX
                var formData = new FormData(form[0]);
                var payloadPreview = artmCollectPayloadPreview(formData);
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    showLoader: true,
                    success: function (resp) {
                        if (resp && resp.success) {
                            console.log('[artm] AJAX success, showing thank you message');
                            form[0].reset();

                            // 2️⃣ Show success message
                            var $successMsg = $('#artm-form-success-message');
                             $successMsg.stop(true, true).slideDown(300, function () {

                            // Scroll AFTER it is visible
                            $('html, body').animate(
                                {
                                    scrollTop: Math.max(0, form.offset().top - 400)
                                },
                                500
                                
                            );
                        });
                        // Auto-hide after 10 seconds
                        setTimeout(function () {
                            $successMsg.slideUp();
                        }, 10000);
                        } else if (resp && resp.message && resp.message.indexOf('Incorrect captcha answer') !== -1) {
                            form.find('.artm-captcha-error').text('Incorrect answer. Please try again.').show();
                            form.find('#artm_captcha_input').focus();
                            artmRenderDebug(form, 'Captcha validation failed', [
                                'URL: ' + form.attr('action'),
                                'Response message: ' + resp.message,
                                'Payload:',
                            ].concat(payloadPreview));
                        } else {
                            alert((resp && resp.message) ? resp.message : 'There was an error submitting the form. Please try again.');
                            console.log('[artm] AJAX error: invalid response payload');
                            artmRenderDebug(form, 'Invalid success payload', [
                                'URL: ' + form.attr('action'),
                                'Response type: ' + (typeof resp),
                                'Response JSON: ' + JSON.stringify(resp),
                                'Payload:',
                            ].concat(payloadPreview));
                        }
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'There was an error submitting the form. Please try again.';
                        alert(msg);
                        console.log('[artm] AJAX error: status ' + xhr.status);
                        artmRenderDebug(form, 'AJAX request failed', [
                            'URL: ' + form.attr('action'),
                            'Status: ' + xhr.status,
                            'Status text: ' + (xhr.statusText || '(empty)'),
                            'Response headers: ' + (xhr.getAllResponseHeaders ? xhr.getAllResponseHeaders().replace(/\r?\n/g, ' | ') : '(not available)'),
                            'Response text (first 5000 chars):',
                            (xhr.responseText || '(empty)').substring(0, 5000),
                            'Payload:',
                        ].concat(payloadPreview));
                    }
                });
            });

        }

        // Initialize existing forms
        $('.artm-form').each(function () {
            initializeForm($(this));
        });

        // Use MutationObserver to detect dynamically added forms
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length) {
                    $(mutation.addedNodes).find('.artm-form').each(function () {
                        initializeForm($(this));
                    });
                }
            });
        });

        // Observe the document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Ensure cleanup on page unload
        $(window).on('unload', function () {
            observer.disconnect();
        });
    });
});
document.addEventListener('DOMContentLoaded', function () {
    var radioRows = document.querySelectorAll('.form-field-row.artm-conditional-field');
    radioRows.forEach(function (row) {
        var radios = row.querySelectorAll('input[type="radio"]');
        var requiredMsg = row.querySelector('.artm-required-text');
        if (requiredMsg) requiredMsg.style.display = 'none';
        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (requiredMsg) requiredMsg.style.display = 'none';
            });
        });
        var form = row.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                var checked = Array.from(radios).some(function (r) { return r.checked; });
                if (!checked) {
                    if (requiredMsg) requiredMsg.style.display = '';
                    e.preventDefault();
                }
            });
        }
    });
});

function artmUpdateCharCount(textarea, max) {
    if (!max) return;
    var remaining = max - textarea.value.length;
    var el = document.getElementById(textarea.id + '_remaining');
    if (el) {
        el.querySelector('span').textContent = remaining;
    }
}

function artmHandleFileChange(input) {
    var container = input.closest('.artm-file-upload');
    var preview = container.querySelector('.artm-file-preview');
    var errorBox = container.querySelector('.artm-file-error');
    errorBox.style.display = 'none';
    errorBox.textContent = '';
    var isImage = container.dataset.type === 'image';
    var isMultiple = container.dataset.multiple === '1';
    var maxSize = parseInt(container.dataset.maxSize || '5242880', 10);
    var maxCount = parseInt(container.dataset.maxCount || '5', 10);
    var allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Use a persistent file list per input
    if (!input._artmFiles) input._artmFiles = [];
    var newFiles = Array.from(input.files);
    // Merge old and new files, but avoid duplicates (by name+size+lastModified)
    var allFiles = input._artmFiles.concat(newFiles);
    // Remove duplicates
    var seen = {};
    allFiles = allFiles.filter(function (f) {
        var key = f.name + '_' + f.size + '_' + f.lastModified;
        if (seen[key]) return false;
        seen[key] = true;
        return true;
    });
    // Limit to maxCount
    if (allFiles.length > maxCount) {
        errorBox.textContent = 'You can upload up to ' + maxCount + ' file(s).';
        errorBox.style.display = 'block';
        // Don't add new files if over limit
        return;
    }
    // Validate all files
    for (var i = 0; i < allFiles.length; i++) {
        var file = allFiles[i];
        var ext = file.name.split('.').pop().toLowerCase();
        if (file.size > maxSize) {
            errorBox.textContent = 'File "' + file.name + '" exceeds the size limit (' + (maxSize / 1024 / 1024).toFixed(1) + 'MB).';
            errorBox.style.display = 'block';
            return;
        }
        if (isImage && !allowedImageExt.includes(ext)) {
            errorBox.textContent = 'Only image files (jpg, jpeg, png, gif, webp) are allowed.';
            errorBox.style.display = 'block';
            return;
        }
    }
    // Save back the file list
    input._artmFiles = allFiles;
    // Update the input's FileList using DataTransfer
    var dt = new DataTransfer();
    allFiles.forEach(function (f) {
        dt.items.add(f);
    });
    input.files = dt.files;

    // Render preview for all files
    preview.innerHTML = '';
    allFiles.forEach(function (file, i) {
        if (isImage) {
            var loaderId = 'artm-loader-' + i + '-' + Date.now();
            preview.innerHTML += '<span id="' + loaderId + '" class="artm-file-box"><span class="artm-loader" style="margin-right:6px;">⏳</span></span>';
            (function (idx, lid, fileObj) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var imgHtml = '<div style="display:inline-block;position:relative;margin-right:8px;">' +
                        '<img src="' + e.target.result + '" alt="Preview" />' +
                        '<span class="artm-remove" onclick="artmRemoveFile(this,' + idx + ')">×</span></div>';
                    var loaderEl = document.getElementById(lid);
                    if (loaderEl) loaderEl.outerHTML = imgHtml;
                };
                reader.readAsDataURL(fileObj);
            })(i, loaderId, file);
        } else {
            preview.innerHTML += '<span class="artm-file-box">' + file.name + ' <span class="artm-remove" onclick="artmRemoveFile(this,' + i + ')">×</span></span>';
        }
    });
}

function artmRemoveFile(el, idx) {
    var container = el.closest('.artm-file-upload');
    var input = container.querySelector('input[type=file]');
    if (!input._artmFiles) input._artmFiles = Array.from(input.files);
    input._artmFiles.splice(idx, 1);
    // Update FileList
    var dt = new DataTransfer();
    input._artmFiles.forEach(function (f) {
        dt.items.add(f);
    });
    input.files = dt.files;
    // Re-render preview
    artmHandleFileChange(input);
}

function artmBeforeSubmit(form) {
    // Validate file/image fields before submit
    var valid = true;
    var fileUploads = form.querySelectorAll('.artm-file-upload');
    fileUploads.forEach(function (container) {
        var input = container.querySelector('input[type=file]');
        var errorBox = container.querySelector('.artm-file-error');
        errorBox.style.display = 'none';
        errorBox.textContent = '';
        var files = input.files;
        var maxSize = parseInt(container.dataset.maxSize || '5242880', 10);
        var maxCount = parseInt(container.dataset.maxCount || '5', 10);
        if (files && files.length) {
            if (files.length > maxCount) {
                errorBox.textContent = 'You can upload up to ' + maxCount + ' file(s).';
                errorBox.style.display = 'block';
                input.value = '';
                valid = false;
            }
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                if (file.size > maxSize) {
                    errorBox.textContent = 'File "' + file.name + '" exceeds the size limit (' + (maxSize / 1024 / 1024).toFixed(1) + 'MB).';
                    errorBox.style.display = 'block';
                    input.value = '';
                    valid = false;
                }
            }
        }
    });
    if (!valid) {
        // Scroll to first error
        var firstError = form.querySelector('.artm-file-error[style*="display: block"]');
        if (firstError) {
            firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
    return valid;
}