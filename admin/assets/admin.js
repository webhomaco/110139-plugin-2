/**
 * Classima VIP Admin Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Media uploader for plan image
        var mediaUploader;

        $('.wh-upload-image-button').on('click', function(e) {
            e.preventDefault();

            var button = $(this);
            var inputField = $('#plan_image_url');
            var previewContainer = $('#plan_image_preview');

            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Extend the wp.media object
            mediaUploader = wp.media({
                title: 'Choose Plan Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });

            // When a file is selected, grab the URL and set it as the input value
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                inputField.val(attachment.url);
                previewContainer.html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px;">');
            });

            // Open the uploader dialog
            mediaUploader.open();
        });

        // Toggle duration fields based on token type
        $('#token_type').on('change', function() {
            var tokenType = $(this).val();
            if (tokenType === 'unlimited') {
                $('.wh-duration-row').addClass('hidden');
            } else {
                $('.wh-duration-row').removeClass('hidden');
            }
        }).trigger('change');

        // Confirm delete actions
        $('.button-link-delete').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-dismiss notices after 5 seconds
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);

    });

})(jQuery);
