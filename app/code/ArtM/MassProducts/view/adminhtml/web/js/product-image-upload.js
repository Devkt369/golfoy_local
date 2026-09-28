require(["jquery"], function ($) {
    $(document).ready(function () {
        // ajax upload for product media
        // Prevent default drag behaviors on the whole page

        $("html").on("dragover drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        // Drag enter & over for specific areas
        $(".artm-upload-image-form").on("dragenter dragover", ".product-drag-area", function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).css('border-color', '#333'); // Optional visual feedback
        });

        // Drop logic
        $(".artm-upload-image-form").on("drop", ".product-drag-area", function (e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).css('border-color', 'lightgray');

            var files = e.originalEvent.dataTransfer.files;
            var $form = $(this).closest(".artm-upload-image-form");
            var existingImgCount = $form.find(".product-resultimg").length;
            var totalCount = existingImgCount + files.length;

            if (totalCount > 10) {
                alert("Maximum 10 images allowed per product.");
            } else {
                for (var x = 0; x < files.length; x++) {
                    var fdata = new FormData();
                    fdata.append("files", files[x]); // Changed to 'files' to match UploaderFactory
                    uploadlogofunc(fdata, $(this)); // Pass the clicked area context
                }
            }
        });

        // Click to trigger hidden file input
        $(document).on("click", ".product-dragdrop", function () {
            // Find the specific file input inside the same form
            $(this).closest(".product-field-media").find('input[type="file"]').trigger('click');
        });

        // File input change logic
        $(document).on('change', '.product-field-media input[type="file"]', function () {
            var files = $(this)[0].files;
            var $dragArea = $(this).closest(".product-field-media").find(".product-drag-area");
            var $form = $(this).closest(".artm-upload-image-form");

            var existingImgCount = $form.find(".product-resultimg").length;
            if ((existingImgCount + files.length) > 10) {
                $(this).val('');
                alert("Maximum 10 images allowed.");
            } else {
                for (var x = 0; x < files.length; x++) {
                    var fdata = new FormData();
                    fdata.append("files", files[x]);
                    fdata.append("form_key", window.FORM_KEY);
                    uploadlogofunc(fdata, $dragArea);
                }
            }
        });

        function uploadlogofunc(fdata, $targetElement) {
            if (fdata != null) {
                $.ajax({
                    url: window.artmMediaConfig.ajaxUploadUrl, // This should be set in the config passed to the renderer
                    type: "POST",
                    processData: false,
                    contentType: false,
                    data: fdata,
                    dataType: 'json',
                    showLoader: true,
                    success: function (res) {
                        if (res.path) {
                            // Create the preview HTML
                            var htmlstr = '<div class="product-resultimg">' +
                                '<img src="/media' + res.path + '" style="width:50px;height:50px;margin:2px;">' +
                                '<div class="product-img-cross" rel="' + res.path + '" style="cursor:pointer;color:red;">&#10006;</div>' +
                                '<input type="hidden" name="product_media_files[]" value="' + res.path + '">' +
                                '</div>';

                            // Insert preview BEFORE the plus (+) icon area inside the specific form
                            $(htmlstr).insertBefore($targetElement);
                        } else if (res.error) {
                            alert(res.error);
                        }
                    }
                });
            }
        }

        // Delete logic
        $(document).on("click", ".product-img-cross", function () {
            var $imageContainer = $(this).parent(".product-resultimg");
            var filePath = $(this).attr('rel'); // This is "temp/product_media/filename.jpg"

            $.ajax({
                url: window.artmMediaConfig.ajaxDeleteUrl, // This should be set in the config passed to the renderer
                type: "POST",
                data: { path: filePath },
                dataType: 'json',
                showLoader: true,
                success: function (res) {
                    $imageContainer.remove();
                }
            });
        });

        // end of ajax upload for product media
        $(document).on("submit", ".artm-upload-image-form", function (e) {
            e.preventDefault();

            let form = $(this);
            let sku = form.data("sku");
            let images = [];

            form.find("input[name='product_media_files[]']").each(function () {
                let path = $(this).val();
                path = path.replace(/^\/+/, '');
                path = path.replace(/^media\//, '');
                images.push(path);
            });

            if (!images.length) {
                alert("No images selected");
                return;
            }

            $.ajax({
                url: window.artmMediaConfig.setProductImageUrl, // Use the secure Admin URL
                type: "POST",
                dataType: "json",
                data: {
                    sku: sku,
                    imageNames: images,
                    form_key: window.FORM_KEY // Required for Admin Controllers
                },
                showLoader: true,
                success: function (res) {
                    if (res.success) {
                        // Clear the preview images on success
                        form.find(".product-resultimg").remove();
                        var imageUrl = res.data.image_url;
                        var $td = form.closest('td.artm-col-upload-right');
                        $td.html(
                            '<div class="product-base-image">' +
                                '<img src="' + imageUrl + '" style="width:50px;height:50px;margin:2px;">' +
                            '</div>'
                        );
                        // alert(res.message);
                    } else {
                        alert("Error: " + res.message);
                    }
                },
                error: function (xhr) {
                    console.error("Critical Error:", xhr.responseText);
                    alert("A server error occurred. Check console for details.");
                }
            });
        });
        // end of image upload and assignment logic
    });
});