define([
    'ko'
], function (ko) {
    'use strict';

    // returns a singleton object
    // it is returned an object, not a function
    return {
        pickupStore: ko.observable(null),
        pickupTime: ko.observable(null)
    };
});
