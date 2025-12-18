define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Theme/calculator'
        },

        initialize: function () {
            this._super();

            this.price = ko.observable(100);
            this.discount = ko.observable(10);

            this.finalPrice = ko.computed(function () {
                var p = parseFloat(this.price());
                var d = parseFloat(this.discount());

                if (isNaN(p) || isNaN(d)) {
                    return 0;
                }

                return p - (p * d / 100);
            }, this);
        }
    });
});
