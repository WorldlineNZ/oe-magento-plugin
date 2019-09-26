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
        'Onfire_PaymarkOE/js/action/paymark-query'
    ],
    function ($, ko, Component, paymarkQuery) {
        'use strict';

        var paymarkConfig = window.checkoutConfig.payment.paymarkoe;
        var availBanks = [];

        $.each(paymarkConfig.available_banks, function (id, text) {
            availBanks.push({
                value: id,
                text: text,
            })
        });

        var mobileNumber = ko.observable();
        var selectedBank = ko.observable();

        $.each({
            'validate-nz-phone': [
                function (number) {
                    return number.length >= 9 &&  number.length <= 11
                        && number.match(/^0{1}(20|21|22|27|28|29){1}\d{6,8}$/);
                }, $.mage.__('Please enter a valid NZ mobile number with no country code or spaces')
            ]
        }, function (i, rule) {
            rule.unshift(i);
                $.validator.addMethod.apply($.validator, rule);
        });

        return Component.extend({
            mobileNumber: mobileNumber,
            selectedBank: selectedBank,

            showAutopay: paymarkConfig.allow_autopay,
            availableBanks : availBanks,

            defaults: {
                template: 'Onfire_PaymarkOE/payment/oe'
            },

            redirectAfterPlaceOrder: false,

            initObservable: function () {
                this._super()
                    .observe([
                        'mobileNumber',
                        'selectedBank'
                    ]);
                return this;
            },

            getCode: function () {
                return 'paymarkoe';
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'selected_bank': this.selectedBank(),
                        'mobile_number': this.mobileNumber()
                    }
                };
            },

            validate: function () {
                var form = '#paymarkoe_form';
                return $(form).validation() && $(form).validation('isValid');
            },

            afterPlaceOrder: function() {
                paymarkQuery(this.messageContainer);
            }
        });
    }
);