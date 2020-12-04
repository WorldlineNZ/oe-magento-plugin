/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
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
        '//open.demo.paymark.co.nz/v1/loader/open.js'
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
                fullScreenLoader.startLoader();
                this.paymentActive(true);

                var messageContainer = this.messageContainer;

                paymarkStatus(messageContainer, function (sessionId) {
                    if (window.openjs) {
                        window.openjs.init({
                            sessionId: sessionId,
                            elementId: 'openjs-wrapper'
                        })

                        fullScreenLoader.stopLoader();

                        // start checking for completed payments
                        paymarkQuery(messageContainer);
                    } else {
                        // openjs isn't available?
                        fullScreenLoader.stopLoader();
                    }
                })

            },
        });
    }
);