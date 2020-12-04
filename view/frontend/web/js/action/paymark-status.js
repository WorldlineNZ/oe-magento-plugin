define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {

        'use strict';

        return function (messageContainer, callBack) {
            var module = 'paymarkoe';

            if (!customer.isLoggedIn()) {
                var url = '/guest-carts/:module/status';
            } else {
                var url = '/carts/mine/:module/status';
            }

            var serviceUrl = urlBuilder.createUrl(url, {module: module});

            storage.get(serviceUrl)
                .done(function (result) {
                    if(!result.length) {
                        return;
                    }

                    callBack(result);
                })
                .fail(function (response) {
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                });
        };
    });
