var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'MyCompany_StorePickup/js/action/set-shipping-information-mixin': true
            }
        }
    }
};
// this file is stoping js before executing 'Magento_Checkout/js/action/set-shipping-information' and
// executes 'MyCompany_StorePickup/js/action/set-shipping-information-mixin'
