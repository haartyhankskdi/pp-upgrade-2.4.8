define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/error-processor',
    ],
    function (
        ko,
        $,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        additionalValidators,
        url,
        fullScreenLoader,
        quote,
        errorProcessor
    ) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            saveCards: window.checkoutConfig.payment.lbopcheckoutsolution.saveCards || [],
            selectedCard: ko.observable(''),
            paymentClientToken: null,
            saveCard: ko.observable(false),
            isCustomerLoggedIn: ko.observable(customer.isLoggedIn()), 
            defaults: {
                template: 'AutifyDigital_LloydscardnetPayment/payment/lbopcheckoutsolution'
            },
            
            /** Returns payment method code */
            getCode: function() {
                return 'lbopcheckoutsolution';
            },

            findTokenById: function (tokenId) {
                return this.saveCards.find(function (card) {
                    return card.token_id === tokenId;
                });
            },

            /**
            * Render Saved Cards Options
            */
            renderSaveCardsDropdown: function () {
                var options = [{
                    value: '',
                    label: 'Please select'
                }];

                this.saveCards.forEach(function (card) {
                    var cardBrand = card.brand;
                    options.push({
                        value: card.token_id,
                        label: card.masked + ' (' + cardBrand.charAt(0).toUpperCase() + cardBrand.slice(1) + ')'
                    });
                });

                return options;
            },
            
            /** Get payment method title */
            getTitle: function() {
                return window.checkoutConfig.payment.lbopcheckoutsolution.title;
            },
            
            /** Check if payment is active */
            isActive: function() {
                return true;
            },
            
            getData: function() {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'save_card': this.saveCard(),
                        'token_id': this.selectedCard()
                    }
                };
                
                return data;
            },
            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                var self = this;
                var selectedTokenId = this.selectedCard();
                var saveCard = this.saveCard();

                // Prepare redirect URL
                var redirectUrl = url.build('lloyds/checkout/redirect');
                
                // If using saved card, pass token in the URL
                if (selectedTokenId) {
                    redirectUrl = url.build('lloyds/checkout/redirect') + '?token_id=' + encodeURIComponent(selectedTokenId);
                }

                // Get browser dimensions
                var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                var browser_height = window.innerHeight || document.documentElement.clientHeight|| document.body.clientHeight;

                // API call to redirect controller
                $.ajax({
                    showLoader: true,
                    url: redirectUrl,
                    data: {
                        browser_height: browser_height,
                        browser_width: browser_width,
                        save_card: saveCard ? 1 : 0
                    },
                    dataType: 'json',
                    type: 'GET'
                }).done(function (response) {
                    if (response) {
                        if (response.url && self.isValidRedirectUrl(response.url)) {
                            $('body').trigger('processStart');
                            window.location = response.url;
                            return true;
                        } else if (response.form_data) {
                            self.buildAndSubmitForm(response.form_data);
                            return true;
                        } else if (response['3dsframe'] && response.data) {
                            var ThreeDSURL = url.build('lloyds/paymentjs/threedsframe');
                            var redirectUrl = ThreeDSURL + '?ipg_id=' + encodeURIComponent(response['ipg_transaction_id']) + 
                                '&order_id=' + encodeURIComponent(response['order_id']) + 
                                '&threeds_data=' + encodeURIComponent(response['data']);
                            window.location.href = redirectUrl;
                            return true;
                        } else if (response.error) {
                            errorProcessor.process({
                                status: 500,
                                responseText: JSON.stringify({
                                    message: response.message || 'Payment gateway error'
                                })
                            }, self.messageContainer);
                            fullScreenLoader.stopLoader();
                            self.isPlaceOrderActionAllowed(true);
                            return false;
                        }
                    } else {
                        errorProcessor.process({
                            status: 500,
                            responseText: JSON.stringify({
                                message: 'Invalid response from payment gateway'
                            })
                        }, self.messageContainer);
                        fullScreenLoader.stopLoader();
                        self.isPlaceOrderActionAllowed(true);
                        return false;
                    }
                }).fail(function (response) {
                    errorProcessor.process(response, self.messageContainer);
                    fullScreenLoader.stopLoader();
                    self.isPlaceOrderActionAllowed(true);
                });

                return false;
            },
            
            buildAndSubmitForm: function(formData) {
                // Helper function to build and submit form data with security validation
                if (!formData || typeof formData !== 'object') {
                    return false;
                }
                
                // Validate form action URL
                var formAction = formData.action || '';
                if (formAction && !this.isValidRedirectUrl(formAction)) {
                    console.error('Invalid form action URL:', formAction);
                    return false;
                }
                
                var form = document.createElement('form');
                form.method = String(formData.method || 'POST').toUpperCase();
                form.action = formAction;
                form.style.display = 'none';
                // Add security attribute
                form.setAttribute('data-validated', 'true');
                
                if (formData.fields && Array.isArray(formData.fields)) {
                    for (var i = 0; i < formData.fields.length; i++) {
                        var field = formData.fields[i];
                        if (field && typeof field === 'object') {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            // Sanitize field name and value to prevent XSS
                            input.name = this.sanitizeInput(String(field.name || ''));
                            input.value = this.sanitizeInput(String(field.value || ''));
                            form.appendChild(input);
                        }
                    }
                }
                
                // Security: Append and submit form
                document.body.appendChild(form);
                form.submit();
                // Clean up
                setTimeout(function() {
                    if (form && form.parentNode) {
                        form.parentNode.removeChild(form);
                    }
                }, 100);
                return true;
            },
            
            sanitizeInput: function(input) {
                if (typeof input !== 'string') {
                    return String(input);
                }
                // Basic HTML entity encoding to prevent XSS
                return input.replace(/[&<>"']/g, function(match) {
                    var entityMap = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    };
                    return entityMap[match];
                });
            },
            
            isValidRedirectUrl: function(url) {
                if (!url || typeof url !== 'string') {
                    return false;
                }
                
                try {
                    // Parse the URL
                    var parsedUrl = new URL(url, window.location.origin);
                    
                    // Allow only relative URLs or same-origin URLs
                    if (parsedUrl.origin === window.location.origin) {
                        return true;
                    }
                    
                    // Allow specific trusted payment domains
                    var trustedDomains = [
                        'https://pay.google.com',
                        'https://pay.sandbox.google.com',
                        'https://pay.google.co.uk',
                        'https://pay.google.ie',
                        'https://payments.google.com',
                        'https://applepay.cdn-apple.com',
                        'https://apple-pay-gateway.apple.com',
                        'https://apple-pay-gateway-cert.apple.com',
                        'https://apple-pay-gateway-nc-pod1.apple.com',
                        'https://apple-pay-gateway-pr-pod1.apple.com',
                        'https://ci.checkout-lane.com'
                    ];
                    
                    return trustedDomains.some(function(domain) {
                        return parsedUrl.href.startsWith(domain);
                    });
                } catch (e) {
                    // If URL parsing fails, treat as relative URL
                    // But ensure it doesn't contain protocol separator
                    return url.indexOf('://') === -1 && url.indexOf('//') !== 0;
                }
            }
        });
    }
);