define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'Mohit_Stripe',
                component: 'Mohit_Stripe/js/view/payment/method-renderer/cc-form'
            }
        );

        return Component.extend({});
    }
);
