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
        'Onfire_PaymarkOE/js/action/paymark-query'
    ],
    function ($, ko, Component, quote, paymarkQuery) {
        'use strict';

        var paymarkConfig = window.checkoutConfig.payment.paymarkoe;
        var availBanks = [];

        $.each(paymarkConfig.available_banks, function (id, values) {
            availBanks.push({
                value: id,
                text: values.title,
                short: values.short,
            })
        });

        var defaultPhone = null;

        // if billing phone is set and matches our regex, set the mobile observable to it.
        if(quote.billingAddress() != null && quote.billingAddress().telephone != null) {
            var billingPhone = quote.billingAddress().telephone;
            if(billingPhone.length >= 9 &&  billingPhone.length <= 11
                && billingPhone.match(/^0{1}(20|21|22|27|28|29){1}\d{6,8}$/)) {

                defaultPhone = billingPhone;
            }
        }

        var mobileNumber = ko.observable(defaultPhone);
        var selectedBank = ko.observable();
        var autopayAllowed = ko.observable(false);
        var setupAutoplay = ko.observable();
        var autopayBanks = paymarkConfig.autopay_banks;

        var loadingBankName = ko.observable();
        var loadingBankImage = ko.computed(function(){
            return paymarkConfig.popup_images[selectedBank()];
        }, self);

        var bankChanged = function() {
            var newBank = selectedBank();

            var bankName = availBanks.find(function(option) {
                return option.value == newBank;
            });

            loadingBankName(bankName.short);

            var allowAutopay = autopayBanks.find(function(option) {
                return option == newBank;
            });

            autopayAllowed(allowAutopay ? true : false);
        }

        selectedBank.subscribe(bankChanged);

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

            formSelector: '#paymarkoe_form',

            onlineEftposLogo: paymarkConfig.logo,
            availableBanks : availBanks,
            autopayEnabled: paymarkConfig.allow_autopay,
            autopayAllowed: autopayAllowed,
            setupAutoplay: setupAutoplay,

            loadingBankName: loadingBankName,
            loadingBankImage: loadingBankImage,
            processingScreen: '#payment-processing',
            countdownTimer: '.oe-loader-timer',
            timerInterval: null,

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
                        'mobile_number': this.mobileNumber(),
                        'setup_autopay': this.setupAutoplay()
                    }
                };
            },

            validate: function () {
                return $(this.formSelector).validation() && $(this.formSelector).validation('isValid');
            },

            afterPlaceOrder: function() {
                this.showPaymentLoader();
                paymarkQuery(this.messageContainer, this.hidePaymentLoader.bind(this));
            },

            showPaymentLoader: function () {
                $(this.processingScreen).addClass('show');
                this.startCountdownTimer();
            },

            hidePaymentLoader: function () {
                $(this.processingScreen).removeClass('show');
                this.resetCountdownTimer();
            },
            
            startCountdownTimer: function () {
                var _self = this;
                var duration = 299; // start at 4:59
                var minutes;
                var seconds;

                this.timerInterval = setInterval(function () {
                    minutes = parseInt(duration / 60, 10);
                    seconds = parseInt(duration % 60, 10);

                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    if (duration > 0) {
                        --duration;
                    }

                    $(_self.countdownTimer).text(minutes + ":" + seconds);

                }, 1000);
            },

            resetCountdownTimer: function () {
                $(this.countdownTimer).text('5:00');
                clearInterval(this.timerInterval);
            }
        });
    }
);