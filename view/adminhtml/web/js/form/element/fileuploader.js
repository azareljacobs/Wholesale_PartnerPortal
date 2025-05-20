define([
    'jquery',
    'Magento_Ui/js/form/element/file-uploader'
], function ($, FileUploader) {
    'use strict';

    return FileUploader.extend({
        defaults: {
            deleteButtonLabel: 'Delete',
            uploaderConfig: {}
        },
        
        /**
         * Remove file from collection and add 'delete' flag.
         *
         * @param {Object} file
         * @returns {FileUploader} Chainable.
         */
        removeFile: function (file) {
            var self = this;
            
            // Toggle visual indicators for deletion
            $(this.element).find('[data-role=delete-button]').attr('disabled', true);
            
            // Extract filename from file object
            var filename = '';
            if (file && file.name) {
                filename = file.name;
            }
            
            // Make AJAX call to delete image on the server
            if (filename) {
                // Get partner_id from the form
                var partnerId = '';
                try {
                    // Try to get partner_id from the form
                    var partnerIdField = $('input[name="partner_id"]');
                    if (partnerIdField.length && partnerIdField.val()) {
                        partnerId = partnerIdField.val();
                    }
                } catch (e) {
                    console.warn('Could not get partner_id from form');
                }
                
                $.ajax({
                    url: '/admin/wholesale_partner/partner/deleteimage',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        filename: filename,
                        partner_id: partnerId,
                        form_key: FORM_KEY // Magento CSRF token
                    },
                    success: function(response) {
                        var statusContainer = $(self.element).find('.delete-status');
                        var successStatus = statusContainer.find('.success');
                        var errorStatus = statusContainer.find('.error');
                        
                        // Show status container
                        statusContainer.show();
                        
                        if (response.success) {
                            // Clear the value completely
                            self.value([]);
                            
                            // Update hidden input for form submission
                            var deleteInput = $(self.element).find('input[name="' + self.inputName + '[delete]"]');
                            deleteInput.val('1');
                            
                            // Completely remove the file preview from the UI
                            $(self.element).find('.file-uploader-preview').empty().hide();
                            
                            // Show file upload area again
                            $(self.element).find('.file-uploader-area').show();
                            
                            // Show success message and hide error
                            successStatus.text(response.message || 'Image deleted').show();
                            errorStatus.hide();
                            
                            // Hide status after 3 seconds
                            setTimeout(function() {
                                statusContainer.fadeOut();
                            }, 3000);
                        } else {
                            // Re-enable delete button if there was an error
                            $(self.element).find('[data-role=delete-button]').attr('disabled', false);
                            
                            // Show error message
                            errorStatus.text(response.error || 'Error deleting image').show();
                            successStatus.hide();
                            
                            if (response.error) {
                                console.error('Error deleting image: ' + response.error);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable delete button if there was an error
                        $(self.element).find('[data-role=delete-button]').attr('disabled', false);
                        
                        // Show error message in UI
                        var statusContainer = $(self.element).find('.delete-status');
                        var successStatus = statusContainer.find('.success');
                        var errorStatus = statusContainer.find('.error');
                        
                        statusContainer.show();
                        errorStatus.text('Error: ' + error).show();
                        successStatus.hide();
                        
                        console.error('Error deleting image: ' + error);
                    }
                });
            }
            
            return this;
        }
    });
});