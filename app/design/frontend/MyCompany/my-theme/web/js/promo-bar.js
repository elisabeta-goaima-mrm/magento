define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('MyCompany.promoBar', {

        options: {
            autoClose: false,
            closeDelay: 5000
        },

        _create: function () {

            this._bindEvents();

            if (this.options.autoClose) {
                this._setupAutoClose();
            }
        },

        _bindEvents: function () {
            var self = this;

            this.element.on('click', function () {
                self.closeBar();
            });

            this.element.css('cursor', 'pointer');
            this.element.attr('title', 'Click to close this');
        },

        _setupAutoClose: function () {
            var self = this;
            setTimeout(function() {
                self.closeBar();
            }, this.options.closeDelay);
        },

        closeBar: function () {
            this.element.slideUp();
        }
    });

    return $.MyCompany.promoBar;
});
