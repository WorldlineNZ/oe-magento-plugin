define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data',
        'mage/url'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, customerData, websiteUrl) {

        'use strict';

        return function (orderId, messageContainer) {
            var module = 'paymarkoe';
            var attempts = 0;
            var timeoutLength = 5000; // 5 second query interval
            var maxAttempts = 65; // 5 minute 5 second wait period (so as that we get a response from the API before this completely fails)
            var redirectUrl = null;
            var finished = false;
            var interval = null;

            if (!customer.isLoggedIn()) {
                var url = '/guest-carts/:module/query/:orderId';
            } else {
                var url = '/carts/mine/:module/query/:orderId';
            }

            var serviceUrl = urlBuilder.createUrl(url, {module: module, orderId: orderId});

            var stopPolling = function () {
                clearInterval(interval);
            }

            // start querying to see if the payment is complete
            interval = setInterval(function (serviceUrl, storage, messageContainer) {
                attempts += 1;

                storage.get(serviceUrl)
                    .done(function (result) {
                        if(!result.length) {
                            return;
                        }

                        var result = JSON.parse(result);

                        if(result.status) {
                            redirectUrl = result.redirect;
                            finished = true;

                            if(result.status == 'success') {
                                customerData.invalidate(['cart']);
                            }
                        }

                        if(finished) {
                            stopPolling();
                            $.mage.redirect(redirectUrl);
                        }
                    })
                    .fail(function (response) {
                        errorProcessor.process(response, messageContainer);
                        stopPolling();
                    });

                if(attempts > maxAttempts) {
                    finished = true;
                    stopPolling();
                    messageContainer.addErrorMessage({message: 'Payment request timeout'});
                    $.mage.redirect(websiteUrl.build('checkout/cart'));
                }

            }, timeoutLength, serviceUrl, storage, messageContainer);
        };
    });
