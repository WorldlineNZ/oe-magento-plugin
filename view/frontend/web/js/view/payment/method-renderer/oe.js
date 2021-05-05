/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var scriptUrl = window.checkoutConfig.payment.paymarkoe.production ?
    '//open.paymark.co.nz/v1' : '//open.demo.paymark.co.nz/v1';

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Paymark_PaymarkOE/js/action/paymark-status',
        'Paymark_PaymarkOE/js/action/paymark-query',
        'Magento_Checkout/js/model/full-screen-loader',
        scriptUrl
    ],
    function ($, ko, Component, quote, paymarkStatus, paymarkQuery, fullScreenLoader, openJs) {
        'use strict';

        var paymarkConfig = window.checkoutConfig.payment.paymarkoe;
        var paymentActive = ko.observable(false);

        return Component.extend({
            formSelector: '#paymarkoe_form',
            onlineEftposLogo: paymarkConfig.logo,
            paymentActive: paymentActive,

            defaults: {
                template: 'Paymark_PaymarkOE/payment/oe'
            },

            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'paymarkoe';
            },

            getData: function () {
                return {
                    'method': this.item.method
                };
            },

            afterPlaceOrder: function () {
                var self = this;
                this.paymentActive(true);

                fullScreenLoader.startLoader();

                var messageContainer = this.messageContainer;

                var status = paymarkStatus(messageContainer, function (sessionId, orderId) {
                    if (window.openjs) {
                        window.openjs.init({
                            sessionId: sessionId,
                            elementId: 'openjs-wrapper'
                        })

                        fullScreenLoader.stopLoader();

                        // start checking for completed payments
                        paymarkQuery(orderId, messageContainer);
                    } else {
                        // openjs isn't available?
                        fullScreenLoader.stopLoader();
                    }
                })

                // if status fails we can restart payment
                status.fail(function (response) {
                    self.paymentActive(false);
                })

            },
        });
    }
);