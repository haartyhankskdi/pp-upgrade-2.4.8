function stripHtml(str) {
    if (!str) return '';
    var tmp = document.createElement('DIV');
    tmp.innerHTML = str;
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
}

require([
    'jquery',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/redirect-on-success',
], function ($, url, messageList, storage, customerData, quote, fullScreenLoader, redirectOnSuccessAction) {
    'use strict';
    var GooglePayCart = {
        config: window.checkoutConfig.payment.cardnetgooglepay || {},
        merchantId: window.checkoutConfig.payment.cardnetgooglepay.merchant_id,
        paymentMode: window.checkoutConfig.payment.cardnetgooglepay.payment_mode,
        merchantName: window.checkoutConfig.payment.cardnetgooglepay.merchant_name,
        currentCurrency: window.checkoutConfig.payment.cardnetgooglepay.currency || window.checkoutConfig.quoteData.quote_currency_code,
        gatewayMerchantId: window.checkoutConfig.payment.cardnetgooglepay.gateway_merchant_id,
        supported_networks: window.checkoutConfig.payment.cardnetgooglepay.supported_networks ?
            window.checkoutConfig.payment.cardnetgooglepay.supported_networks.split(",") :
            ["AMEX", "DISCOVER", "MASTERCARD", "VISA"],
        allowedCountries: window.checkoutConfig.payment.cardnetgooglepay.allowed_countries ?
            window.checkoutConfig.payment.cardnetgooglepay.allowed_countries.split(",") :
            ["GB", "CA", "US"],
        buttonType: window.checkoutConfig.payment.cardnetgooglepay.button_type || 'buy',
        buttonColor: window.checkoutConfig.payment.cardnetgooglepay.button_color || 'black',
        apiVersion: window.checkoutConfig.payment.cardnetgooglepay.api_version || 2,
        apiVersionMinor: window.checkoutConfig.payment.cardnetgooglepay.api_version_minor || 0,
        googlePayButtonRendered: false,
        paymentsClient: null,
        shippingMethods: [],
        selectedShippingMethod: null,
        buttonSelector: window.checkoutConfig.payment.cardnetgooglepay.button_selector || '#google-pay-button-container',
        paymentMethodCode: 'cardnetgooglepay',
        
        init: function () {
            this.loadGooglePayScript();
        },
        
        loadGooglePayScript: function () {
            var self = this;
            if (typeof google === "undefined" || !google.payments) {
                var script = document.createElement('script');
                script.src = "https://pay.google.com/gp/p/js/pay.js";
                script.onload = function () { self.renderGooglePayButton(); };
                script.onerror = function (error) { console.log('Error loading Google Pay script', error); };
                document.head.appendChild(script);
            } else {
                this.renderGooglePayButton();
            }
        },
        
        getApiUrl: function (uri) {
            var storeCode = window.checkoutConfig.storeCode || 'default';
            return !!customerData.get('customer')().firstname ?
                'rest/' + storeCode + '/V1/carts/mine/' + uri :
                'rest/' + storeCode + '/V1/guest-carts/' + quote.getQuoteId() + '/' + uri;
        },
        
        renderGooglePayButton: function () {
            var self = this;
            var checkInterval = setInterval(function () {
                if (!self.googlePayButtonRendered && $(self.buttonSelector).length) {
                    self.addGooglePayButton();
                    self.googlePayButtonRendered = true;
                    clearInterval(checkInterval);
                }
            }, 1000);
            setTimeout(function () { clearInterval(checkInterval); }, 10000);
        },
        
        getGooglePaymentsClient: function () {
            if (!this.paymentsClient) {
                this.paymentsClient = new google.payments.api.PaymentsClient({
                    environment: this.paymentMode,
                    paymentDataCallbacks: {
                        onPaymentDataChanged: this.onPaymentDataChanged.bind(this),
                        onPaymentAuthorized: this.onPaymentAuthorized.bind(this)
                    }
                });
            }
            return this.paymentsClient;
        },
        
        onPaymentAuthorized: function (paymentData) {
            var self = this;
            return new Promise(function (resolve) {
                fullScreenLoader.startLoader();
                try {
                    self.saveShippingInformation(paymentData)
                        .then(function () { return self.setPaymentMethod(paymentData); })
                        .then(function () { resolve({ transactionState: 'SUCCESS' }); })
                        .catch(function (error) {
                            fullScreenLoader.stopLoader();
                            messageList.addErrorMessage({
                                message: error.message || $.mage.__('An error occurred during checkout. Please try again.')
                            });
                            resolve({
                                transactionState: 'ERROR',
                                error: {
                                    intent: 'PAYMENT_AUTHORIZATION',
                                    message: error.message || 'Payment processing failed',
                                    reason: 'PAYMENT_DATA_INVALID'
                                }
                            });
                        });
                } catch (e) {
                    fullScreenLoader.stopLoader();
                    resolve({
                        transactionState: 'ERROR',
                        error: {
                            intent: 'PAYMENT_AUTHORIZATION',
                            message: 'An unexpected error occurred during checkout',
                            reason: 'PAYMENT_DATA_INVALID'
                        }
                    });
                }
            });
        },
        
        addGooglePayButton: function () {
            var self = this;
            var paymentsClient = this.getGooglePaymentsClient();
            var isReadyToPayRequest = {
                apiVersion: this.apiVersion,
                apiVersionMinor: this.apiVersionMinor,
                allowedPaymentMethods: [self.getBaseCardPaymentMethod()],
                existingPaymentMethodRequired: false
            };
            
            paymentsClient.isReadyToPay(isReadyToPayRequest)
                .then(function (response) {
                    if (response.result) {
                        var button = paymentsClient.createButton({
                            onClick: self.onGooglePaymentButtonClicked.bind(self),
                            buttonColor: self.buttonColor,
                            buttonType: self.buttonType
                        });
                        $(self.buttonSelector).empty().append(button);
                    } else {
                        $(self.buttonSelector).empty().append('<div class="message info"><span>' +
                            ($.mage.__('Google Pay is not available for this device or browser') || 'Google Pay is not available') +
                            '</span></div>');
                    }
                })
                .catch(function () {
                    $(self.buttonSelector).empty().append('<div class="message error"><span>' +
                        ($.mage.__('Unable to initialize Google Pay') || 'Unable to initialize Google Pay') +
                        '</span></div>');
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
                    'https://apple-pay-gateway-pr-pod1.apple.com'
                ];
                
                return trustedDomains.some(function(domain) {
                    return parsedUrl.href.startsWith(domain);
                });
            } catch (e) {
                // If URL parsing fails, treat as relative URL
                // But ensure it doesn't contain protocol separator
                return url.indexOf('://') === -1 && url.indexOf('//') !== 0;
            }
        },
        
        getBaseCardPaymentMethod: function () {
            return {
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                    allowedCardNetworks: this.supported_networks,
                    assuranceDetailsRequired: true
                }
            };
        },
        
        getCurrentQuoteTotals: function() {
            var totals = quote.getTotals()();
            return {
                subtotal: parseFloat(totals.subtotal || 0),
                tax_amount: parseFloat(totals.tax_amount || 0),
                discount_amount: parseFloat(totals.discount_amount || 0),
                grand_total: parseFloat(totals.grand_total || 0)
            };
        },
        
        getCardPaymentMethod: function () {
            var method = this.getBaseCardPaymentMethod();
            method.tokenizationSpecification = {
                type: 'PAYMENT_GATEWAY',
                parameters: {
                    'gateway': 'fiservipg',
                    'gatewayMerchantId': this.gatewayMerchantId
                }
            };
            method.parameters.billingAddressRequired = true;
            method.parameters.billingAddressParameters = {
                format: 'FULL',
                phoneNumberRequired: true
            };
            return method;
        },
        
        getGoogleTransactionInfo: function (shippingOption, recalculatedTotals) {
            try {
                var totals = recalculatedTotals || this.getCurrentQuoteTotals();
                var shippingAmount = shippingOption ? parseFloat(shippingOption.cost || 0) : 0;
                var total = Math.max(0, totals.subtotal + totals.tax_amount + totals.discount_amount + shippingAmount);
                
                return {
                    currencyCode: this.currentCurrency,
                    totalPriceStatus: 'FINAL',
                    totalPrice: total.toFixed(2),
                    totalPriceLabel: $.mage.__('Total') || 'Total',
                    displayItems: this.getDisplayLineItems(totals, shippingOption)
                };
            } catch (e) {
                console.error('Error in getGoogleTransactionInfo:', e);
                return {
                    currencyCode: this.currentCurrency,
                    totalPriceStatus: 'FINAL',
                    totalPrice: '0.00',
                    totalPriceLabel: $.mage.__('Total') || 'Total'
                };
            }
        },
        
        getDisplayLineItems: function (totals, shippingOption) {
            var items = [];
            if (totals.subtotal > 0) {
                items.push({
                    label: $.mage.__('Subtotal') || 'Subtotal',
                    price: totals.subtotal.toFixed(2),
                    type: 'SUBTOTAL'
                });
            }
            if (totals.tax_amount !== 0) {
                items.push({
                    label: $.mage.__('Tax') || 'Tax',
                    price: totals.tax_amount.toFixed(2),
                    type: 'TAX'
                });
            }
            if (totals.discount_amount !== 0) {
                items.push({
                    label: $.mage.__('Discount') || 'Discount',
                    price: totals.discount_amount.toFixed(2),
                    type: 'LINE_ITEM'
                });
            }
            if (shippingOption && shippingOption.cost) {
                items.push({
                    label: shippingOption.label || ($.mage.__('Shipping') || 'Shipping'),
                    price: shippingOption.cost,
                    type: 'SHIPPING'
                });
            }
            return items;
        },
        
        fetchInitialShippingMethods: function () {
            var self = this;
            return new Promise(function (resolve) {
                if (quote.shippingMethod && typeof quote.getShippingRates === 'function') {
                    var rates = quote.getShippingRates();
                    if (rates && rates.length > 0) {
                        var methods = rates.filter(function(rate) {
                            return rate.carrier_code && rate.method_code;
                        }).map(function(rate) {
                            return {
                                carrier_code: rate.carrier_code,
                                method_code: rate.carrier_code + '_' + rate.method_code,
                                carrier_title: stripHtml(rate.carrier_title),
                                method_title: stripHtml(rate.method_title),
                                price_incl_tax: parseFloat(rate.price_incl_tax || rate.amount)
                            };
                        });
                        
                        if (methods.length > 0) {
                            self.shippingMethods = methods;
                            self.setSelectedShippingMethod(quote.shippingMethod());
                            resolve(methods);
                            return;
                        }
                    }
                }
                
                var domMethods = self.getShippingMethodsFromDOM();
                if (domMethods.length > 0) {
                    self.shippingMethods = domMethods;
                    self.setDefaultShippingMethod();
                    resolve(domMethods);
                    return;
                }
                
                self.fetchShippingMethodsFromAPI().then(function (methods) {
                    self.shippingMethods = methods || [];
                    if (self.shippingMethods.length > 0) {
                        self.setDefaultShippingMethod();
                    }
                    resolve(self.shippingMethods);
                }).catch(function () {
                    resolve([]);
                });
            });
        },
        
        getShippingMethodsFromDOM: function () {
            var methods = [];
            $('.checkout-shipping-method .table-checkout-shipping-method tbody tr').each(function () {
                var $row = $(this);
                var methodCode = $row.find('input[type="radio"]').val();
                if (methodCode) {
                    methods.push({
                        carrier_code: methodCode.split('_')[0],
                        method_code: methodCode,
                        carrier_title: stripHtml($row.find('.col-carrier').html()),
                        method_title: stripHtml($row.find('.col-method').html()),
                        price_incl_tax: parseFloat($row.find('.col-price .price').text().replace(/[^0-9.-]+/g, '')),
                        isSelected: $row.find('input[type="radio"]').prop('checked')
                    });
                }
            });
            return methods;
        },
        
        setSelectedShippingMethod: function (method) {
            if (!method) {
                this.setDefaultShippingMethod();
                return;
            }
            
            var methodCode = method.method_code;
            if (method.carrier_code && !methodCode.includes(method.carrier_code)) {
                methodCode = method.carrier_code + '_' + method.method_code;
            }
            
            var foundMethod = this.shippingMethods.find(function(m) {
                return m.method_code === methodCode;
            });
            
            if (foundMethod) {
                this.selectedShippingMethod = {
                    id: methodCode,
                    label: stripHtml(foundMethod.carrier_title) || $.mage.__('Shipping') || 'Shipping',
                    description: stripHtml(foundMethod.method_title) || '',
                    cost: foundMethod.price_incl_tax.toFixed(2)
                };
            } else {
                this.setDefaultShippingMethod();
            }
        },
        
        setDefaultShippingMethod: function () {
            if (!this.shippingMethods || this.shippingMethods.length === 0) {
                this.selectedShippingMethod = null;
                return;
            }
            
            var defaultMethod = this.shippingMethods.find(function(m) { return m.isSelected; }) || this.shippingMethods[0];
            this.selectedShippingMethod = {
                id: defaultMethod.method_code,
                label: stripHtml(defaultMethod.carrier_title) || $.mage.__('Shipping') || 'Shipping',
                description: stripHtml(defaultMethod.method_title) || '',
                cost: defaultMethod.price_incl_tax.toFixed(2)
            };
        },
        
        fetchShippingMethodsFromAPI: function () {
            var self = this;
            var defaultAddress = {
                address: {
                    country_id: self.allowedCountries[0] || 'GB',
                    region: self.allowedCountries[0] === 'US' ? 'CA' : (self.allowedCountries[0] === 'CA' ? 'ON' : 'GB'),
                    postcode: self.allowedCountries[0] === 'US' ? '90001' : (self.allowedCountries[0] === 'CA' ? 'M5V 2H1' : 'SW1A 1AA'),
                    save_in_address_book: 0
                }
            };
            
            return storage.post(
                self.getApiUrl('estimate-shipping-methods'),
                JSON.stringify(defaultAddress)
            ).then(function(result) {
                return result;
            }, function() {
                return [];
            });
        },
        
        fetchShippingMethods: function (shippingAddress) {
            var self = this;
            return new Promise(function (resolve, reject) {
                if (!shippingAddress) {
                    reject(new Error('Invalid shipping address'));
                    return;
                }
                
                var payload = {
                    address: {
                        city: shippingAddress.locality || '',
                        region: shippingAddress.administrativeArea || '',
                        country_id: (shippingAddress.countryCode || 'GB').toUpperCase(),
                        postcode: shippingAddress.postalCode || '',
                        save_in_address_book: 0
                    }
                };
                
                storage.post(
                    self.getApiUrl('estimate-shipping-methods'),
                    JSON.stringify(payload)
                ).done(function (result) {
                    if (result.length === 0) {
                        var cartData = customerData.get('cart')();
                        var isVirtual = cartData && cartData.items && cartData.items.length > 0 &&
                            cartData.items.every(function (item) {
                                return ['virtual', 'downloadable', 'bundle'].includes(item.product_type) || item.is_virtual;
                            });
                        
                        if (!isVirtual) {
                            resolve([]);
                            return;
                        }
                    }
                    
                    var excludedCarriers = ['instore', 'instorepickup', 'in_store_pickup'];
                    self.shippingMethods = result.filter(function(method) {
                        return typeof method.method_code === 'string' &&
                            excludedCarriers.indexOf(method.carrier_code) === -1;
                    });
                    resolve(self.shippingMethods);
                }).fail(reject);
            });
        },
        
        getShippingOptions: function (shippingMethods) {
            if (!shippingMethods || !shippingMethods.length) return [];
            
            return shippingMethods.map(function (method) {
                var cost = parseFloat(method.price_incl_tax || 0).toFixed(2);
                return {
                    id: method.method_code,
                    label: stripHtml(method.carrier_title) || $.mage.__('Shipping') || 'Shipping',
                    description: stripHtml(method.method_title || '') + ' - ' + this.currentCurrency + ' ' + cost,
                    cost: cost
                };
            }, this);
        },
        
        getGooglePaymentDataRequest: function () {
            var request = {
                apiVersion: this.apiVersion,
                apiVersionMinor: this.apiVersionMinor,
                allowedPaymentMethods: [this.getCardPaymentMethod()],
                transactionInfo: this.getGoogleTransactionInfo(this.selectedShippingMethod),
                merchantInfo: { merchantName: this.merchantName },
                emailRequired: true,
                shippingAddressRequired: true,
                shippingAddressParameters: {
                    phoneNumberRequired: true,
                    allowedCountryCodes: this.allowedCountries
                },
                callbackIntents: ['SHIPPING_ADDRESS', 'SHIPPING_OPTION', 'PAYMENT_AUTHORIZATION'],
                shippingOptionRequired: true
            };
            
            if (this.merchantId && this.merchantId.trim() !== '') {
                request.merchantInfo.merchantId = this.merchantId;
            }
            
            return request;
        },
        
        onGooglePaymentButtonClicked: function () {
            var self = this;
            this.paymentsClient = null;
            var paymentsClient = this.getGooglePaymentsClient();
            
            paymentsClient.loadPaymentData(this.getGooglePaymentDataRequest())
                .then(function () { return Promise.resolve(true); })
                .catch(function (error) {
                    self.paymentsClient = null;
                    if (error.statusCode === "CANCELED") {
                        location.reload();
                    } else {
                        messageList.addErrorMessage({
                            message: $.mage.__('Google Pay error: ') + (error.statusMessage || error.statusCode || $.mage.__('Unknown error'))
                        });
                    }
                });
        },
        
        recalculateTotalsForAddress: function(shippingAddress, shippingMethod) {
            var self = this;
            return new Promise(function(resolve) {
                if (!shippingAddress) {
                    resolve(self.getCurrentQuoteTotals());
                    return;
                }
                
                var formattedAddress = {
                    address: {
                        country_id: (shippingAddress.countryCode || 'GB').toUpperCase(),
                        region: shippingAddress.administrativeArea || '',
                        city: shippingAddress.locality || '',
                        postcode: shippingAddress.postalCode || '',
                        street: [shippingAddress.address1 || '', shippingAddress.address2 || ''],
                        firstname: (shippingAddress.name || 'John Doe').split(' ')[0],
                        lastname: (shippingAddress.name || 'John Doe').split(' ').slice(1).join(' ') || 'Doe',
                        telephone: shippingAddress.phoneNumber || '1234567890',
                        save_in_address_book: 0
                    }
                };
                
                if (shippingMethod && shippingMethod.id) {
                    var parts = shippingMethod.id.split('_');
                    formattedAddress.shipping_method_code = parts.slice(1).join('_');
                    formattedAddress.shipping_carrier_code = parts[0];
                }
                
                storage.post(
                    self.getApiUrl('totals-information'),
                    JSON.stringify({ addressInformation: formattedAddress })
                ).done(function(result) {
                    resolve({
                        subtotal: parseFloat(result.subtotal || 0),
                        tax_amount: parseFloat(result.tax_amount || 0),
                        discount_amount: parseFloat(result.discount_amount || 0),
                        grand_total: parseFloat(result.grand_total || 0)
                    });
                }).fail(function() {
                    resolve(self.getCurrentQuoteTotals());
                });
            });
        },
        
        onPaymentDataChanged: function (intermediatePaymentData) {
            var self = this;
            return new Promise(function (resolve) {
                try {
                    var trigger = intermediatePaymentData.callbackTrigger;
                    
                    if (trigger === 'INITIALIZE' || trigger === 'SHIPPING_ADDRESS') {
                        var shippingAddress = intermediatePaymentData.shippingAddress;
                        var fetchPromise = shippingAddress ? 
                            self.fetchShippingMethods(shippingAddress) : 
                            self.fetchInitialShippingMethods();
                        
                        fetchPromise.then(function (methods) {
                            var options = self.getShippingOptions(methods);
                            
                            if (options.length > 0) {
                                var defaultId = (self.selectedShippingMethod && 
                                    options.find(function(o) { return o.id === self.selectedShippingMethod.id; })) ?
                                    self.selectedShippingMethod.id : options[0].id;
                                
                                self.selectedShippingMethod = options.find(function(o) { return o.id === defaultId; });
                                
                                self.recalculateTotalsForAddress(shippingAddress, self.selectedShippingMethod)
                                    .then(function(totals) {
                                        resolve({
                                            newShippingOptionParameters: {
                                                defaultSelectedOptionId: defaultId,
                                                shippingOptions: options
                                            },
                                            newTransactionInfo: self.getGoogleTransactionInfo(self.selectedShippingMethod, totals)
                                        });
                                    });
                            } else {
                                resolve({
                                    error: {
                                        reason: 'SHIPPING_ADDRESS_UNSERVICEABLE',
                                        message: $.mage.__('No shipping options available for this address'),
                                        intent: 'SHIPPING_ADDRESS'
                                    }
                                });
                            }
                        }).catch(function () {
                            resolve({
                                error: {
                                    reason: 'OTHER_ERROR',
                                    message: $.mage.__('Unable to calculate shipping options'),
                                    intent: 'SHIPPING_ADDRESS'
                                }
                            });
                        });
                        
                    } else if (trigger === 'SHIPPING_OPTION') {
                        var selectedId = intermediatePaymentData.shippingOptionData.id;
                        var options = self.getShippingOptions(self.shippingMethods);
                        var selected = options.find(function(o) { return o.id === selectedId; });
                        
                        if (selected) {
                            self.selectedShippingMethod = selected;
                            self.recalculateTotalsForAddress(intermediatePaymentData.shippingAddress, selected)
                                .then(function(totals) {
                                    resolve({
                                        newTransactionInfo: self.getGoogleTransactionInfo(selected, totals)
                                    });
                                });
                        } else {
                            resolve({ newTransactionInfo: self.getGoogleTransactionInfo(self.selectedShippingMethod) });
                        }
                    } else {
                        resolve({});
                    }
                } catch (e) {
                    console.error('Error in onPaymentDataChanged', e);
                    resolve({
                        error: {
                            reason: 'OTHER_ERROR',
                            message: $.mage.__('An unexpected error occurred'),
                            intent: 'PAYMENT_DATA_CHANGED'
                        }
                    });
                }
            });
        },

        formatAddress: function (address, type) {
            if (!address) return null;
            
            var nameParts = (address.name || 'John Doe').split(' ');
            var formatted = {
                firstname: nameParts[0] || '',
                lastname: nameParts.slice(1).join(' ') || 'Doe',
                street: [address.address1 || '', address.address2 || address.address3 || ''],
                city: address.locality || '',
                postcode: address.postalCode || '',
                countryId: (address.countryCode || '').toUpperCase(),
                telephone: address.phoneNumber || '',
                region: address.administrativeArea || '',
                regionId: null,
                company: address.organization || '',
                saveInAddressBook: 0
            };
            
            if (type === 'billing') {
                formatted.email = address.email || '';
            }
            
            return formatted;
        },
        
        getAgreementIds: function () {
            var ids = [];
            
            $('input[type="checkbox"][name^="agreement"]').each(function () {
                var id = $(this).val();
                if (id && id !== 'undefined' && id !== '') {
                    ids.push(isNaN(parseInt(id, 10)) ? id : parseInt(id, 10));
                }
            });
            
            if (ids.length === 0 && window.checkoutConfig && window.checkoutConfig.checkoutAgreements) {
                $.each(window.checkoutConfig.checkoutAgreements, function (index, agreement) {
                    if (agreement && agreement.agreementId) {
                        ids.push(isNaN(parseInt(agreement.agreementId, 10)) ? agreement.agreementId : parseInt(agreement.agreementId, 10));
                    }
                });
            }
            
            return ids.length > 0 ? ids.filter(function(id) { return id !== undefined && id !== null; }) : [1];
        },

        setPaymentMethod: function (paymentData) {
            var self = this;
            return new Promise(function (resolve, reject) {
                var paymentToken = paymentData.paymentMethodData.tokenizationData.token;
                
                try {
                    paymentToken = typeof paymentToken === 'string' ? JSON.parse(paymentToken) : paymentToken;
                } catch (e) {
                    console.log('Token is not a valid JSON', e);
                }

                var payload = {
                    cartId: quote.getQuoteId(),
                    billingAddress: quote.billingAddress() || self.formatAddress(paymentData.paymentMethodData.info.billingAddress, 'billing'),
                    paymentMethod: {
                        method: self.paymentMethodCode,
                        additional_data: {
                            payment_method_nonce: JSON.stringify(paymentToken),
                            google_pay_response: JSON.stringify(paymentData),
                            device_data: navigator.userAgent || ''
                        },
                        extension_attributes: {
                            agreement_ids: self.getAgreementIds()
                        }
                    }
                };
                
                if (paymentData.email) {
                    payload.email = paymentData.email;
                }
                
                storage.post(
                    self.getApiUrl('payment-information'),
                    JSON.stringify(payload)
                ).done(function () {
                    var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                    var browser_height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
                    
                    $.ajax({
                        url: url.build('lloyds/googlepay/payment'),
                        type: 'POST',
                        data: {
                            form_key: $.mage.cookies.get('form_key'),
                            screen_height: browser_height,
                            screen_width: browser_width,
                            paymentData: JSON.stringify(paymentData)
                        }
                    }).done(function (response) {
                        if (response) {
                            if (response.url && self.isValidRedirectUrl(response.url)) {
                                $('body').trigger('processStart');
                                window.location = response.url;
                            } else if (response.form_data) {
                                formBuilder(response.form_data).submit();
                            } else if (response['3dsframe'] && response.data) {
                                var ThreeDSURL = url.build('lloyds/paymentjs/threedsframe');
                                var redirectUrl = ThreeDSURL + '?ipg_id=' + encodeURIComponent(response['ipg_transaction_id']) + 
                                    '&order_id=' + encodeURIComponent(response['order_id']) + 
                                    '&threeds_data=' + encodeURIComponent(response['data']);
                                window.location.href = redirectUrl;
                            } else if (response.status === 'success') {
                                customerData.invalidate(['cart']);
                                redirectOnSuccessAction.execute();
                                resolve(true);
                            } else {
                                reject({ message: response.message || 'Payment processing failed' });
                            }
                        } else {
                            reject({ message: 'Invalid response from payment processor' });
                        }
                        fullScreenLoader.stopLoader();
                    }).fail(function () {
                        globalMessageList.addErrorMessage({
                            message: $t('Payment processing failed.')
                        });
                                            
                        resolve({
                            transactionState: 'ERROR',
                            error: {
                                reason: 'PAYMENT_DATA_INVALID',
                                message: 'Payment processing failed.',
                                intent: 'PAYMENT_AUTHORIZATION'
                            }
                        });
                        location.reload();
                        return false;
                    });
                }).fail(function (response) {
                    fullScreenLoader.stopLoader();
                    try {
                        var error = JSON.parse(response.responseText);
                        reject(error);
                    } catch (e) {
                        reject({ message: 'Payment processing failed. Please try again.' });
                    }
                });
            });
        },

        saveShippingInformation: function (paymentData) {
            var self = this;
            return new Promise(function (resolve, reject) {
                if (!paymentData.shippingAddress || !self.selectedShippingMethod) {
                    var cartData = customerData.get('cart')();
                    var isVirtual = cartData && cartData.items && cartData.items.length > 0 &&
                        cartData.items.every(function (item) {
                            return ['virtual', 'downloadable'].includes(item.product_type) || item.is_virtual;
                        });
                    
                    if (isVirtual) {
                        resolve();
                        return;
                    } else {
                        reject({ message: $.mage.__('The shipping method is missing. Please select a shipping method and try again.') });
                        return;
                    }
                }
                
                var shippingAddress = self.formatAddress(paymentData.shippingAddress, 'shipping');
                var methodId = self.selectedShippingMethod.id;
                var foundMethod = self.shippingMethods.find(function(method) {
                    return method.method_code === methodId || 
                          (method.carrier_code + '_' + method.method_code) === methodId;
                });
                
                var carrierCode, methodCode;
                if (foundMethod) {
                    carrierCode = foundMethod.carrier_code;
                    methodCode = foundMethod.method_code;
                    if (methodCode.indexOf(carrierCode + '_') === 0) {
                        methodCode = methodCode.substring(carrierCode.length + 1);
                    }
                } else {
                    var parts = methodId.split('_');
                    carrierCode = parts[0];
                    methodCode = parts.slice(1).join('_');
                }
                
                var payload = {
                    addressInformation: {
                        shipping_address: shippingAddress,
                        billing_address: shippingAddress,
                        shipping_method_code: methodCode,
                        shipping_carrier_code: carrierCode
                    }
                };
                
                storage.post(
                    self.getApiUrl('shipping-information'),
                    JSON.stringify(payload)
                ).done(resolve).fail(function (response) {
                    try {
                        var error = JSON.parse(response.responseText);
                        reject(error);
                    } catch (e) {
                        reject({ message: $.mage.__('Failed to save shipping information') });
                    }
                });
            });
        }
    };
    GooglePayCart.init();
    return GooglePayCart;
});
