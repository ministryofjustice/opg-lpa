// Disable link after use module for LPA
// Dependencies: moj, jQuery

(function () {
    'use strict';

    // Define the class
    var SingleUse = function (options) {
        this.settings = $.extend({}, this.defaults, options);
    };

    SingleUse.prototype = {
        defaults: {
            selector: '.js-single-use'
        },

        init: function () {
            // bind 'this' as this in following methods
            _.bindAll(this, 'btnClick');
            this.bindEvents();
        },

        bindEvents: function () {
            $('body')
                // link click
                .on('click.moj.Modules.SingleUse', this.settings.selector, this.btnClick)
                // submit form
                .on('submit.moj.Modules.SingleUse', this.settings.selector, this.submitForm);
        },

        btnClick: function (e) {
            if (e.target.tagName === 'A') {
                var source = $(e.target);

                // Disable link
                source.attr('disabled', 'disabled');
            }
        },

        submitForm: function (e) {
            if (e.target.tagName === 'FORM') {
                var $form = $(e.target);

                // Disable submit button
                $form.find('input[type="submit"]').attr('disabled', 'disabled');
            }
        }
    };

    // Add module to MOJ namespace
    moj.Modules.SingleUse = new SingleUse();
}());
