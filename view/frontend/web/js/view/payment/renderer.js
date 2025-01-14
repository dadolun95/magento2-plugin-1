/*browser:true*/
/*global define*/
define([
    "uiComponent",
    "Magento_Checkout/js/model/payment/renderer-list"
], function (Component, rendererList) {
    "use strict";

    rendererList.push({
        type: "satispay",
        component: "Satispay_Satispay/js/view/payment/method-renderer/satispay"
    });

    return Component.extend({});
});
