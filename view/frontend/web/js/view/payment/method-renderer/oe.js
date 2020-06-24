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
        'Paymark_PaymarkOE/js/action/paymark-query'
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
                logo: values.logo,
                lower: id.toLowerCase()
            })
        });

        var agreements = [];
        if(paymarkConfig.allow_autopay) {
            $.each(paymarkConfig.agreements, function (id, values) {
                agreements.push({
                    id: id,
                    payer: values.payer,
                    logo: paymarkConfig.bank_logos[values.bank]
                })
            });
        }

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
        var bankLogos = paymarkConfig.bank_logos;

        var paymentType = ko.observable(paymarkConfig.type_standard);
        var selectedAgreement = ko.observable();
        var customerHasAgreements = ko.observable(false);

        var loadingBankName = ko.observable();
        var loadingBankImage = ko.computed(function(){
            var image = '';
            if(paymentType() == paymarkConfig.type_autopay) {
                if(selectedAgreement()) {
                    var bankId = paymarkConfig.agreements[selectedAgreement()].bank;
                    image = paymarkConfig.popup_images[bankId];
                }
            } else {
                image = paymarkConfig.popup_images[selectedBank()];
            }

            return image;
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

            var shouldAllowAutopay = (allowAutopay && window.isCustomerLoggedIn) ? true : false;

            autopayAllowed(shouldAllowAutopay);

            if(!shouldAllowAutopay) {
                setupAutoplay(0);
            }
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

        if(paymarkConfig.allow_autopay && agreements.length) {
            paymentType(paymarkConfig.type_autopay);
            selectedAgreement(agreements[0].id);
            customerHasAgreements(true);
        }

        var shouldShowAutopay = ko.computed(function() {
            return (paymarkConfig.allow_autopay && paymentType() == paymarkConfig.type_autopay)
        });

        return Component.extend({
            mobileNumber: mobileNumber,
            selectedBank: selectedBank,

            formSelector: '#paymarkoe_form',
            formAutopaySelector: '#paymarkoe_autopay_form',

            onlineEftposLogo: paymarkConfig.logo,
            availableBanks: availBanks,
            autopayEnabled: paymarkConfig.allow_autopay,
            autopayAllowed: autopayAllowed,
            setupAutoplay: setupAutoplay,
            bankLogos: bankLogos,
            agreements: agreements,

            paymentType: paymentType,
            selectedAgreement: selectedAgreement,
            shouldShowAutopay: shouldShowAutopay,
            customerHasAgreements: customerHasAgreements,

            loadingBankName: loadingBankName,
            loadingBankImage: loadingBankImage,
            processingScreen: '#payment-processing',
            whatsAutopayOverlay: '#paymark-oe-what',
            countdownTimer: '.oe-loader-timer',
            timerInterval: null,

            defaults: {
                template: 'Paymark_PaymarkOE/payment/oe'
            },

            redirectAfterPlaceOrder: false,

            initObservable: function () {
                this._super()
                    .observe([
                        'mobileNumber',
                        'selectedBank',
                        'setupAutoplay',
                        'paymentType',
                        'selectedAgreement'
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
                        'setup_autopay': this.setupAutoplay(),
                        'payment_type': this.paymentType(),
                        'selected_agreement': this.selectedAgreement()
                    }
                };
            },

            validate: function () {
                var formSelector = this.paymentType() == paymarkConfig.type_autopay ? this.formAutopaySelector : this.formSelector;
                return $(formSelector).validation() && $(formSelector).validation('isValid');
            },

            afterPlaceOrder: function() {
                this.showPaymentLoader();
                paymarkQuery(this.messageContainer, this.hidePaymentLoader.bind(this));
            },

            getLogoImage: function (bank) {
                return this.bankLogos[bank];
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
            },

            showWhatsAutopay: function () {
                $(this.whatsAutopayOverlay).addClass('show');
            },

            closeWhatsAutopay: function () {
                $(this.whatsAutopayOverlay).removeClass('show');
            },

            showAutopay: function() {
                this.paymentType(paymarkConfig.type_autopay)
            },

            closeAutopay: function () {
                this.paymentType(paymarkConfig.type_standard)
            }
        });
    }
);