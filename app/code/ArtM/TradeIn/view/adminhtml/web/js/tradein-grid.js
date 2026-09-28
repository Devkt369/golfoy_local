require([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/url'
], function ($, alert, urlBuilder) {
    'use strict';

    // Debug inline editing
    $(document).ready(function() {
        console.log('TradeIn Grid JS loaded');
        
        // Listen for inline edit events
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).hasClass('_edit')) {
                console.log('Edit mode activated');
            }
        });
        
        // Debug form submissions
        $(document).on('submit', 'form', function(e) {
            console.log('Form submission detected:', this);
            console.log('Form data:', $(this).serialize());
        });
    });

    // Handle option change for payment method
    window.handleOptionChange = function(selectElement) {
        var selectedValue = selectElement.value;
        var tradeInId = selectElement.getAttribute('data-id');
        var otherInputContainer = $('div[data-id="' + tradeInId + '"]');
        
        if (selectedValue === 'other') {
            otherInputContainer.show();
        } else {
            otherInputContainer.hide();
        }
    };

    // Handle status update
    $(document).on('click', '.update-status', function() {
        var button = $(this);
        var tradeInId = button.data('id');
        var row = button.closest('tr');
        var status = row.find('.status_tradein_admin').val();

        console.log('Status update for ID:', tradeInId, 'Status:', status);

        $.ajax({
            url: urlBuilder.build('artmtradein/index/updateStatus'),
            type: 'POST',
            data: {
                tradein_id: tradeInId,
                status: status,
                form_key: window.FORM_KEY
            },
            success: function(response) {
                console.log('Status update response:', response);
                if (response.success) {
                    alert({
                        title: 'Success',
                        content: response.message
                    });
                    location.reload();
                } else {
                    alert({
                        title: 'Error',
                        content: response.message
                    });
                }
            },
            error: function() {
                alert({
                    title: 'Error',
                    content: 'An error occurred while updating the status.'
                });
            }
        });
    });

    // Handle payment info update
    $(document).on('click', '.submit_tradein_row', function() {
        var button = $(this);
        var tradeInId = button.data('id');
        var row = button.closest('tr');
        
        var paymentMethod = row.find('.tradein-select').val();
        var otherDetails = row.find('.other-input').val();
        var refId = row.find('.ref-input').val();
        var amount = row.find('.amount-input').val();

        console.log('Payment update for ID:', tradeInId, {
            paymentMethod: paymentMethod,
            otherDetails: otherDetails,
            refId: refId,
            amount: amount
        });

        $.ajax({
            url: urlBuilder.build('artmtradein/index/updateStatus'),
            type: 'POST',
            data: {
                tradein_id: tradeInId,
                payment_method: paymentMethod,
                other_details: otherDetails,
                ref_id: refId,
                amount: amount,
                form_key: window.FORM_KEY
            },
            success: function(response) {
                console.log('Payment update response:', response);
                if (response.success) {
                    alert({
                        title: 'Success',
                        content: response.message
                    });
                    location.reload();
                } else {
                    alert({
                        title: 'Error',
                        content: response.message
                    });
                }
            },
            error: function() {
                alert({
                    title: 'Error',
                    content: 'An error occurred while updating the payment information.'
                });
            }
        });
    });

    // Image modal functionality - Global functions for onclick handlers
    window.openImageModal = function(imgElement) {
        const imageUrl = imgElement.getAttribute('data-image');
        const allImages = imgElement.getAttribute('data-images');
        const currentIndex = parseInt(imgElement.getAttribute('data-index'));
        
        // Create modal if it doesn't exist
        let modal = document.getElementById('tradein-image-modal');
        if (!modal) {
            modal = createImageModal();
            document.body.appendChild(modal);
        }
        
        // Set the current image
        const modalImg = modal.querySelector('.modal-image');
        modalImg.src = imageUrl;
        
        // Setup navigation if multiple images
        const imageArray = allImages.split(',').map(img => {
            const baseUrl = window.location.origin + '/pub/media/review_images/';
            return baseUrl + img.trim();
        }).filter(img => img.trim() !== '');
        
        if (imageArray.length > 1) {
            setupImageNavigation(modal, imageArray, currentIndex);
        } else {
            hideImageNavigation(modal);
        }
        
        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };

    function createImageModal() {
        const modal = document.createElement('div');
        modal.id = 'tradein-image-modal';
        modal.className = 'tradein-modal';
        modal.innerHTML = 
            '<div class="modal-content">' +
                '<span class="close-modal">&times;</span>' +
                '<button class="nav-btn prev-btn" onclick="navigateImage(-1)">&#8249;</button>' +
                '<img class="modal-image" src="" alt="Full size image" />' +
                '<button class="nav-btn next-btn" onclick="navigateImage(1)">&#8250;</button>' +
                '<div class="image-counter"></div>' +
            '</div>';
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = 
            '.tradein-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); }' +
            '.tradein-modal .modal-content { position: relative; margin: auto; padding: 20px; width: 90%; height: 90%; display: flex; align-items: center; justify-content: center; }' +
            '.tradein-modal .modal-image { max-width: 100%; max-height: 100%; object-fit: contain; }' +
            '.tradein-modal .close-modal { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; z-index: 10001; }' +
            '.tradein-modal .close-modal:hover { color: #bbb; }' +
            '.tradein-modal .nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background-color: rgba(255,255,255,0.2); color: white; border: none; font-size: 30px; padding: 10px 15px; cursor: pointer; z-index: 10001; }' +
            '.tradein-modal .nav-btn:hover { background-color: rgba(255,255,255,0.4); }' +
            '.tradein-modal .prev-btn { left: 20px; }' +
            '.tradein-modal .next-btn { right: 20px; }' +
            '.tradein-modal .image-counter { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; background-color: rgba(0,0,0,0.5); padding: 5px 10px; border-radius: 5px; z-index: 10001; }';
        document.head.appendChild(style);
        
        // Add event listeners
        modal.querySelector('.close-modal').onclick = closeImageModal;
        modal.onclick = function(e) {
            if (e.target === modal) {
                closeImageModal();
            }
        };
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (modal.style.display === 'block') {
                if (e.key === 'Escape') {
                    closeImageModal();
                } else if (e.key === 'ArrowLeft') {
                    navigateImage(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateImage(1);
                }
            }
        });
        
        return modal;
    }

    let currentImages = [];
    let currentImageIndex = 0;

    function setupImageNavigation(modal, images, startIndex) {
        currentImages = images;
        currentImageIndex = startIndex;
        
        modal.querySelector('.prev-btn').style.display = 'block';
        modal.querySelector('.next-btn').style.display = 'block';
        modal.querySelector('.image-counter').style.display = 'block';
        
        updateImageCounter(modal);
    }

    function hideImageNavigation(modal) {
        modal.querySelector('.prev-btn').style.display = 'none';
        modal.querySelector('.next-btn').style.display = 'none';
        modal.querySelector('.image-counter').style.display = 'none';
    }

    window.navigateImage = function(direction) {
        if (currentImages.length <= 1) return;
        
        currentImageIndex += direction;
        
        if (currentImageIndex >= currentImages.length) {
            currentImageIndex = 0;
        } else if (currentImageIndex < 0) {
            currentImageIndex = currentImages.length - 1;
        }
        
        const modal = document.getElementById('tradein-image-modal');
        const modalImg = modal.querySelector('.modal-image');
        modalImg.src = currentImages[currentImageIndex];
        
        updateImageCounter(modal);
    };

    function updateImageCounter(modal) {
        const counter = modal.querySelector('.image-counter');
        counter.textContent = (currentImageIndex + 1) + ' / ' + currentImages.length;
    }

    function closeImageModal() {
        const modal = document.getElementById('tradein-image-modal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});
