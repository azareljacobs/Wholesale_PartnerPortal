define([
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'mage/url',
    'underscore'
], function ($, Abstract, urlBuilder, _) {
    'use strict';

    return Abstract.extend({
        defaults: {
            imports: {
                handleNameChange: '${$.parentName}.name:value'
            },
            valueHasChanged: false,
            slugGeneratedFromName: false,
            validationUrl: 'wholesale_partner/partner/validate',
            validationTimeout: 500,
            validateDelay: 1000,
            debouncedHandleNameChange: null,
            debouncedValidateSlug: null
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            
            // Listen for manual changes to the URL key field
            this.on('value', this.onValueChange.bind(this));
            
            // Create debounced versions of methods
            if (!this.debouncedHandleNameChange) {
                this.debouncedHandleNameChange = _.debounce(this.generateSlugFromName.bind(this), this.validateDelay);
            }
            if (!this.debouncedValidateSlug) {
                this.debouncedValidateSlug = _.debounce(this.validateSlug.bind(this), this.validationTimeout);
            }
            
            return this;
        },

        /**
         * Handle when value is changed by user
         *
         * @param {String} value
         */
        onValueChange: function (value) {
            this.valueHasChanged = true;
            
            // Validate the slug when manually changed
            if (value && value.length > 0) {
                this.debouncedValidateSlug(value);
            }
        },

        /**
         * Handle name change
         *
         * @param {String} name
         */
        handleNameChange: function (name) {
            // Don't auto-generate URL key if it's been manually changed,
            // or if the name is empty
            if (this.valueHasChanged || !name) {
                return;
            }

            // Use debounce to avoid excessive slug generation
            this.debouncedHandleNameChange(name);
        },
        
        /**
         * Generate slug from name with enhanced validation
         *
         * @param {String} name
         */
        generateSlugFromName: function (name) {
            // Generate slug from name with enhanced validation
            var slug = name.toLowerCase()
                // Replace non-alphanumeric characters with hyphens
                .replace(/[^a-z0-9]+/g, '-')
                // Replace multiple hyphens with a single hyphen
                .replace(/-+/g, '-')
                // Remove leading and trailing hyphens
                .replace(/^-|-$/g, '')
                // Limit length to 64 characters (common database field length)
                .substring(0, 64);
            
            // Update field value
            this.value(slug);
            this.slugGeneratedFromName = true;
            
            // Validate the generated slug
            this.validateSlug(slug);
        },
        
        /**
         * Validate slug uniqueness via AJAX
         *
         * @param {String} slug
         */
        validateSlug: function (slug) {
            var self = this;
            var partnerId = $('[name="partner_id"]').val() || 0;
            
            // Clear existing validation messages
            this.bubble('clear', 'slug-validation');
            
            $.ajax({
                url: urlBuilder.build(this.validationUrl),
                type: 'POST',
                dataType: 'json',
                data: {
                    slug: slug,
                    partner_id: partnerId
                },
                success: function (response) {
                    if (!response.valid) {
                        self.bubble('error', response.message || 'This URL key is already in use.', 'slug-validation');
                    }
                },
                error: function () {
                    // Silent fail - don't block user interaction on validation failure
                }
            });
        }
    });
});