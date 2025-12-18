define([
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote', // shopping cart
    'MyCompany_StorePickup/js/model/pickup-state' // Import the state
], function (Component, ko, quote, pickupState) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MyCompany_StorePickup/checkout/shipping/store-pickup-fields' // what to be drawn
        },

        initObservable: function () {
            this._super();
            var config = window.checkoutConfig.shipping.storepickup;
            this.availableStores = config.stores;
            this.availableSlots = config.slots;

            this.selectedStore = ko.observable();
            this.selectedSlot = ko.observable();

            this.selectedStore.subscribe(function (value) {
                pickupState.pickupStore(value);
            });
            this.selectedSlot.subscribe(function (value) {
                pickupState.pickupTime(value);
            });

            this.isMethodSelected = ko.computed(function () {
                var method = quote.shippingMethod();
                return method && method.carrier_code === 'storepickup';
            }, this);

            return this;
        }
    });
});
