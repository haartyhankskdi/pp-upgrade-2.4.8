function stripHtml(str) {
    if (!str) return '';
    var tmp = document.createElement('DIV');
    tmp.innerHTML = str;
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
}

require([
  'jquery', 'mage/translate', 'mage/url', 'Magento_Customer/js/customer-data',
  'Magento_Checkout/js/model/full-screen-loader', 'mage/cookies',
  'Magento_Ui/js/model/messageList', 'mage/storage'
], function ($, $t, url, customerData, fullScreenLoader, cookies, globalMessageList, storage) {
  'use strict';

  var GooglePayProduct = {
    defaults: {
      grandTotal: 0, currencyCode: 'GBP', countryCode: 'GB', isPlaceOrderActionAllowed: true,
      countryLists: null, quoteId: null, shippingMethods: {}, shippingAddress: {}, selectedShippingMethod: null,
      taxAmount: 0
    },

    isValidRedirectUrl: function (url) {
      if (!url || typeof url !== 'string') {
        return false;
      }

      // Allow relative URLs (no protocol)
      if (url.indexOf('://') === -1) {
        return true;
      }

      try {
        var parsedUrl = new URL(url);
        var hostname = parsedUrl.hostname.toLowerCase();
        
        // Allow same origin
        if (hostname === window.location.hostname) {
          return true;
        }
        
        // Allow trusted payment domains
        var trustedDomains = [
          'pay.google.com',
          'pay.sandbox.google.com',
          'pay.google.co.uk',
          'pay.google.ie',
          'payments.google.com',
          'applepay.cdn-apple.com',
          'apple-pay-gateway.apple.com',
          'apple-pay-gateway-cert.apple.com',
          'apple-pay-gateway-nc-pod1.apple.com',
          'apple-pay-gateway-pr-pod1.apple.com'
        ];
        
        return trustedDomains.indexOf(hostname) !== -1;
      } catch (e) {
        return false;
      }
    },

    init: function () {
      let minOrderAmount = parseFloat(window.googlepayConfig.minOrderAmount) || 0;
      let maxOrderAmount = parseFloat(window.googlepayConfig.maxOrderAmount) || 999999;
      let cartTotal = this.defaults.grandTotal = parseFloat($('#google-pay-button-container').data('googlepay-price'));

      if (cartTotal < minOrderAmount || cartTotal > maxOrderAmount) return;

      if (typeof google === 'undefined' || typeof google.payments === 'undefined') {
        $.getScript('https://pay.google.com/gp/p/js/pay.js', this.onGooglePayLoaded.bind(this));
      } else {
        this.onGooglePayLoaded();
      }

      if (!this.countryLists) {
        storage.get('rest/V1/directory/countries').done(function (result) {
          this.countryLists = {};
          for (let i = 0; i < result.length; ++i) {
            let data = result[i];
            this.countryLists[data.two_letter_abbreviation] = {};
            if (typeof data.available_regions === 'undefined') continue;
            for (let x = 0; x < data.available_regions.length; ++x) {
              let region = data.available_regions[x];
              let name = region.name.toLowerCase().replace(/[^A-Z0-9]/ig, '');
              this.countryLists[data.two_letter_abbreviation][name] = region.id;
            }
          }
        }.bind(this));
      }
    },

    onGooglePayLoaded: function () {
      this.paymentsClient = new google.payments.api.PaymentsClient({
        environment: window.googlepayConfig.payment_mode,
        merchantInfo: {
          merchantName: window.googlepayConfig.merchant_name,
          merchantId: window.googlepayConfig.merchant_id
        },
        paymentDataCallbacks: {
          onPaymentAuthorized: this.onPaymentAuthorized.bind(this),
          onPaymentDataChanged: this.onPaymentDataChanged.bind(this)
        }
      });

      this.paymentsClient.isReadyToPay(this.getIsReadyToPayRequest())
        .then((response) => {
          if (response.result) {
            this.createButton();
          }
        }).catch(function (error) {
          console.error('Google Pay is not available:', error);
        });
    },

    getIsReadyToPayRequest: function () {
      return {
        apiVersion: 2, apiVersionMinor: 0,
        allowedPaymentMethods: [this.getBaseCardPaymentMethod()]
      };
    },

    getBaseCardPaymentMethod: function () {
      return {
        type: 'CARD',
        parameters: {
          allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
          allowedCardNetworks: window.googlepayConfig.supported_networks
            ? window.googlepayConfig.supported_networks.split(',')
            : ["AMEX", "DISCOVER", "MASTERCARD", "VISA"],
          assuranceDetailsRequired: true
        }
      };
    },

    getCardPaymentMethod: function () {
      var method = this.getBaseCardPaymentMethod();
      method.tokenizationSpecification = {
        type: 'PAYMENT_GATEWAY',
        parameters: {
          'gateway': 'fiservipg',
          'gatewayMerchantId': window.googlepayConfig.gateway_merchant_id
        }
      };
      method.parameters.billingAddressRequired = true;
      method.parameters.billingAddressParameters = {
        format: 'FULL', phoneNumberRequired: true
      };
      return method;
    },

    getTransactionInfo: function (shippingOption) {
      var total = parseFloat(this.defaults.grandTotal);
      var productTotal = parseFloat(this.defaults.grandTotal);
      var tax = parseFloat(this.defaults.taxAmount) || 0;

      if (shippingOption && shippingOption.cost) {
        var shippingCost = parseFloat(shippingOption.cost);
        if (!isNaN(shippingCost)) total += shippingCost;
      }
      if (tax > 0) {
          total += tax;
      }
      
      return {
        currencyCode: this.defaults.currencyCode,
        totalPriceStatus: 'FINAL',
        totalPrice: total.toFixed(2),
        totalPriceLabel: $t('Total'),
        displayItems: this.getDisplayLineItems(total, tax, 0, shippingOption, productTotal)
      };
    },

    getDisplayLineItems: function (subtotal, tax, discount, shippingOption, productTotal) {
      var items = [];
      if (subtotal > 0) items.push({
        label: $t('Subtotal'), price: productTotal.toFixed(2), type: 'SUBTOTAL'
      });
      if (tax && tax !== 0) items.push({
        label: $t('Tax'), price: tax.toFixed(2), type: 'TAX'
      });
      if (discount && discount !== 0) items.push({
        label: $t('Discount'), price: discount.toFixed(2), type: 'LINE_ITEM'
      });
      if (shippingOption && shippingOption.cost) items.push({
        label: shippingOption.label || $t('Shipping'),
        price: shippingOption.cost, type: 'SHIPPING'
      });
      return items;
    },

    getPaymentRequest: function () {
      return {
        apiVersion: 2, apiVersionMinor: 0,
        allowedPaymentMethods: [this.getCardPaymentMethod()],
        transactionInfo: this.getTransactionInfo(this.selectedShippingMethod),
        merchantInfo: {
          merchantName: window.googlepayConfig.merchant_name,
          merchantId: window.googlepayConfig.merchant_id
        },
        emailRequired: true,
        shippingAddressRequired: true,
        shippingAddressParameters: {
          phoneNumberRequired: true,
          allowedCountryCodes: window.googlepayConfig.allowed_countries
            ? window.googlepayConfig.allowed_countries.split(',')
            : ["GB", "CA", "US"]
        },
        callbackIntents: ['SHIPPING_ADDRESS', 'SHIPPING_OPTION', 'PAYMENT_AUTHORIZATION'],
        shippingOptionRequired: true
      };
    },

    onPaymentDataChanged: function (intermediatePaymentData) {
      var self = this;
      return new Promise(function (resolve, reject) {
        try {
          if (intermediatePaymentData.callbackTrigger === 'INITIALIZE' ||
                intermediatePaymentData.callbackTrigger === 'SHIPPING_ADDRESS') {
        
                var shippingAddress = null;
                
                if (intermediatePaymentData.callbackTrigger === 'SHIPPING_ADDRESS') {
                  shippingAddress = intermediatePaymentData.shippingAddress;
                } else if (intermediatePaymentData.callbackTrigger === 'INITIALIZE' && 
                           intermediatePaymentData.shippingAddress) {
                  shippingAddress = intermediatePaymentData.shippingAddress;
                }
                
                const payload = {
                  address: {
                    region: shippingAddress && shippingAddress.administrativeArea ? shippingAddress.administrativeArea : '',
                    country_id: shippingAddress && shippingAddress.countryCode ? shippingAddress.countryCode.toUpperCase() : self.defaults.countryCode,
                    postcode: shippingAddress && shippingAddress.postalCode ? shippingAddress.postalCode : ''
                  }
                };
        
                self.shippingAddress = payload.address;
        
                $.ajax({
                  url: window.baseUrl + 'lloyds/wallet/shippingoptions',
                  type: 'POST',
                  contentType: 'application/json',
                  data: JSON.stringify(payload),
                  success: function (result) {
                    const productItems = customerData.get('cart')().items || [];
                    let virtualFlag = productItems.every(item => item.is_virtual || item.product_type === 'bundle');
        
                    if (!result || result.length === 0) {
                      if (!virtualFlag) {
                        reject({
                          reason: 'SHIPPING_ADDRESS_UNSERVICEABLE',
                          message: 'No shipping methods available for this address'
                        });
                        return;
                      }
                    }
        
                    const shippingOptions = [];
                    self.shippingMethods = {};
                    const excludedCarriers = ['instore', 'instorepickup', 'in_store_pickup'];

                    (result || []).forEach(methodData => {
                      if (typeof methodData.method_code !== 'string') return;
                      if (excludedCarriers.indexOf(methodData.carrier_code) !== -1) return;
        
                      const method = {
                        id: methodData.method_code,
                        label: stripHtml(methodData.carrier_title),
                        description: stripHtml(methodData.method_title || '') + ' - ' + parseFloat(methodData.amount).toFixed(2),
                        cost: parseFloat(methodData.amount).toFixed(2)
                      };
        
                      shippingOptions.push(method);
                      self.shippingMethods[methodData.method_code] = methodData;
                    });
        
                    if (shippingOptions.length > 0) {
                      self.selectedShippingMethod = {
                        id: shippingOptions[0].id,
                        label: shippingOptions[0].label,
                        description: shippingOptions[0].description,
                        cost: shippingOptions[0].cost
                      };
                    }
        
                    if (virtualFlag || shippingOptions.length > 0) {
                      const selectedMethod = virtualFlag ? null :
                        self.shippingMethods[shippingOptions[0].id];
        
                      const totalsPayload = {
                        addressInformation: {
                          address: {
                            countryId: self.shippingAddress.country_id,
                            region: self.shippingAddress.region,
                            regionId: self.getRegionId(self.shippingAddress.country_id, self.shippingAddress.region),
                            postcode: self.shippingAddress.postcode
                          },
                          shipping_method_code: virtualFlag ? null : selectedMethod.method_code,
                          shipping_carrier_code: virtualFlag ? null : selectedMethod.carrier_code
                        }
                      };
        
                      $.ajax({
                        url: window.baseUrl + 'lloyds/wallet/shippingupdate',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(totalsPayload),
                        success: function (totals) {
                          if (totals && totals.tax_amount !== undefined) {
                            self.defaults.taxAmount = parseFloat(totals.tax_amount) || 0;
                          }
                          let originalPrice = parseFloat($('#google-pay-button-container').data('googlepay-price'));
                          self.setGrandTotalAmount(originalPrice);
                          resolve({
                            newShippingOptionParameters: shippingOptions.length ? {
                              defaultSelectedOptionId: shippingOptions[0].id,
                              shippingOptions: shippingOptions
                            } : undefined,
                            newTransactionInfo: self.getTransactionInfo(
                              shippingOptions.length ? self.selectedShippingMethod : null
                            )
                          });
                        },
                        error: function (error) {
                          console.error('Error updating shipping totals:', error);
                          reject({
                            reason: 'SHIPPING_OPTION_INVALID',
                            message: 'Failed to update shipping'
                          });
                        }
                      });
                    } else {
                      reject({
                        reason: 'SHIPPING_ADDRESS_UNSERVICEABLE',
                        message: 'No shipping options available'
                      });
                    }
                  },
                  error: function (error) {
                    console.error('Error fetching shipping options:', error);
                    reject({
                      reason: 'SHIPPING_ADDRESS_INVALID',
                      message: 'Failed to fetch shipping options'
                    });
                  }
                });
              } else if (intermediatePaymentData.callbackTrigger === 'SHIPPING_OPTION') {
            var selectedOptionId = intermediatePaymentData.shippingOptionData.id;
            var selectedMethod = self.shippingMethods[selectedOptionId] || null;

            if (selectedMethod) {
              var payload = {
                addressInformation: {
                  address: {
                    countryId: self.shippingAddress.country_id,
                    region: self.shippingAddress.region,
                    regionId: self.getRegionId(self.shippingAddress.country_id, self.shippingAddress.region),
                    postcode: self.shippingAddress.postcode
                  },
                  shipping_method_code: selectedMethod.method_code,
                  shipping_carrier_code: selectedMethod.carrier_code
                }
              };

              self.selectedShippingMethod = {
                id: selectedOptionId,
                label: stripHtml(selectedMethod.carrier_title) || $t('Shipping'),
                description: stripHtml(selectedMethod.method_title || '') + ' - ' + parseFloat(selectedMethod.amount).toFixed(2),
                cost: parseFloat(selectedMethod.amount).toFixed(2)
              };

              $.ajax({
                url: window.baseUrl + 'lloyds/wallet/shippingupdate',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (totals) {
                  let originalPrice = parseFloat($('#google-pay-button-container').data('googlepay-price'));
                  self.setGrandTotalAmount(originalPrice);
                  resolve({
                    newTransactionInfo: self.getTransactionInfo(self.selectedShippingMethod)
                  });
                },
                error: function (error) {
                  console.error('Error updating shipping method:', error);
                  reject({
                    reason: 'SHIPPING_OPTION_INVALID',
                    message: 'Failed to update shipping method'
                  });
                }
              });
            } else {
              resolve({
                newTransactionInfo: self.getTransactionInfo(self.selectedShippingMethod)
              });
            }
          } else {
            resolve({});
          }
        } catch (e) {
          console.error('Error in onPaymentDataChanged:', e);
          reject({
            reason: 'OTHER_ERROR',
            message: e.message || 'An unexpected error occurred'
          });
        }
      });
    },

    onPaymentAuthorized: function (paymentData) {
      var self = this;
      return new Promise(function (resolve, reject) {
        try {
          $('body').trigger('processStart');

          let shippingContact = paymentData.shippingAddress;
          let billingContact = paymentData.paymentMethodData.info.billingAddress;
          let email = paymentData.email;

          var formKey = $.mage.cookies.get('form_key');
          var selectedShippingMethod = self.selectedShippingMethod;
          var carrierCode = '', shippingMethodCode = '';

          if (selectedShippingMethod && self.shippingMethods[selectedShippingMethod.id]) {
            carrierCode = self.shippingMethods[selectedShippingMethod.id].carrier_code;
            shippingMethodCode = self.shippingMethods[selectedShippingMethod.id].method_code;
          }

          var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
          var browser_height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

          $.ajax({
            url: url.build('lloyds/wallet/googlepaycreateorder'),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
              form_key: formKey,
              shippingContact: shippingContact,
              billingContact: billingContact,
              email: email,
              shipping_method: carrierCode && shippingMethodCode ? carrierCode + '_' + shippingMethodCode : '',
              payment_method: 'cardnetgooglepay'
            }),
            success: function (data) {
              if (data.success) {
                $.ajax({
                  url: url.build('lloyds/googlepay/payment'),
                  type: 'POST',
                  dataType: 'json',
                  data: {
                    form_key: $.mage.cookies.get('form_key'),
                    screen_height: browser_height,
                    screen_width: browser_width,
                    paymentData: JSON.stringify(paymentData)
                  }
                }).done(function (response) {
                  if (response) {
                    if (response['url']) {
                      if (self.isValidRedirectUrl(response['url'])) {
                        $('body').trigger('processStart');
                        window.location = response['url'];
                        return true;
                      } else {
                        resolve({
                          transactionState: 'ERROR',
                          error: {
                            reason: 'PAYMENT_DATA_INVALID',
                            message: $t('Invalid redirect URL received from payment gateway')
                          }
                        });
                        fullScreenLoader.stopLoader();
                        return false;
                      }
                    } else if (response['form_data']) {
                      formBuilder(response['form_data']).submit();
                      return true;
                    } else if (response['3dsframe'] && response['data']) {
                        var ThreeDSURL = url.build('lloyds/paymentjs/threedsframe');
                        var redirectUrl = ThreeDSURL + '?ipg_id=' + encodeURIComponent(response['ipg_transaction_id']) + 
                          '&order_id=' + encodeURIComponent(response['order_id']) + 
                          '&threeds_data=' + encodeURIComponent(response['data']);
                        window.location.href = redirectUrl;
                    } else {
                      resolve({
                        transactionState: 'ERROR',
                        error: {
                          reason: 'PAYMENT_DATA_INVALID',
                          message: $t(response.message)
                        }
                      });
                      location.reload();
                      fullScreenLoader.stopLoader();
                      return false;
                    }
                    return false;
                  } else {
                    resolve({
                      transactionState: 'ERROR',
                      error: {
                        reason: 'PAYMENT_DATA_INVALID',
                        message: $t(response.message)
                      }
                    });
                    fullScreenLoader.stopLoader();
                    return false;
                  }
                }).fail(function (response) {
                  resolve({
                      transactionState: 'ERROR',
                      error: {
                        reason: 'PAYMENT_DATA_INVALID',
                        message: $t(response.message)
                      }
                    });
                  fullScreenLoader.stopLoader();
                });
              } else {
                resolve({
                  transactionState: 'ERROR',
                  error: {
                    reason: 'OTHER_ERROR',
                    message: data.message || $t('Unable to process order')
                  }
                });
                globalMessageList.addErrorMessage({
                  message: data.message || $t('Unable to process order.')
                });
                $('body').trigger('processStop');
              }
            },
            error: function (err) {
              console.error('Order creation error:', err);
              resolve({
                transactionState: 'ERROR',
                error: { reason: 'OTHER_ERROR', message: $t('Failed to create order') }
              });
              $('body').trigger('processStop');
            }
          });
        } catch (e) {
          console.error('Error in onPaymentAuthorized:', e);
          resolve({
            transactionState: 'ERROR',
            error: {
              reason: 'OTHER_ERROR',
              message: e.message || $t('An unexpected error occurred')
            }
          });
          $('body').trigger('processStop');
        }
      });
    },

    setGrandTotalAmount: function (value) {
      this.defaults.grandTotal = parseFloat(value).toFixed(2);
    },

    getGrandTotalAmount: function () {
      return parseFloat(this.defaults.grandTotal);
    },

    getRegionId: function (countryCode, regionCode) {
      if (typeof regionCode !== 'string') return null;

      regionCode = regionCode.toLowerCase().replace(/[^A-Z0-9]/ig, '');

      if (typeof this.countryLists === 'object' &&
        this.countryLists !== null &&
        typeof this.countryLists[countryCode] !== 'undefined' &&
        typeof this.countryLists[countryCode][regionCode] !== 'undefined') {
        return this.countryLists[countryCode][regionCode];
      }

      return 0;
    },

    createButton: function () {
      const self = this;
      const button = this.paymentsClient.createButton({
        buttonType: 'buy',
        buttonColor: window.googlepayConfig.button_color,
        onClick: function() {
          if (self.checkForProductOptions() === true) {
            $('body').trigger('processStart');
            self.clearCart()
              .then(() => self.prepareCartAndAddProduct())
              .then(() => self.paymentsClient.loadPaymentData(self.getPaymentRequest()))
              .catch(function (error) {
                $('body').trigger('processStop');
                if (error && error.statusCode === "CANCELED") {
                  console.log("User cancelled Google Pay process");
                  location.reload();
                } else {
                  console.error("Error during Google Pay process:", error);
                }
              });
           }
        }
      });
      $('#google-pay-button-container').html(button);
    },

    clearCart: function () {
      let cartData = customerData.get('cart')();
      if (!cartData || !cartData.items || cartData.items.length === 0) {
        return Promise.resolve();
      }

      return new Promise(function (resolve) {
        $.ajax({
          url: url.build('lloyds/wallet/clear'),
          type: 'POST',
          showLoader: true,
          success: function () {
            customerData.reload(['cart'], true);
            resolve(true);
          },
          error: function () {
            resolve(true);
          }
        });
      });
    },

    prepareCartAndAddProduct: function () {
      return new Promise(function (resolve, reject) {
        var form = $('#product_addtocart_form');
        $.post(form.attr('action'), form.serialize())
          .done(function () {
            customerData.reload(['cart'], true);
            setTimeout(() => resolve(true), 1000);
          })
          .fail(reject);
      });
    },

    checkForProductOptions: function () {
      let form = $('#product_addtocart_form');
      if (form.length === 0) return false;
      if (typeof form.validation === 'function') {
        form.validation();
        if (!form.valid()) return false;
      }
      return true;
    }
  };

  $(document).ready(function () {
    GooglePayProduct.init();
  });

  return GooglePayProduct;
});