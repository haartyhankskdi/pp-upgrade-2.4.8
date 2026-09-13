/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
define(
  [
      'ko',
      'jquery',
      'Magento_Checkout/js/view/payment/default',
      'Magento_Checkout/js/model/quote',
      'mage/url',
      'Magento_Customer/js/customer-data',
      'Magento_Checkout/js/model/error-processor',
      'Magento_Checkout/js/model/full-screen-loader',
      'Magento_Ui/js/model/messageList',
      'Magento_Checkout/js/model/payment/additional-validators',
      'Magento_Checkout/js/action/redirect-on-success',
      'AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder',
      'Magento_Customer/js/model/customer',
      'AutifyDigital_LloydscardnetPayment/js/model/paymentjs-error'
  ],
  function (
      ko,
      $,
      Component,
      quote,
      url,
      customerData,
      errorProcessor,
      fullScreenLoader,
      globalMessageList,
      additionalValidators,
      redirectOnSuccessAction,
      formBuilder,
      customer,
      getPaymentJsErrorMessage
  ) {
      'use strict';

      return Component.extend({
          redirectAfterPlaceOrder: false,
          isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
          saveCards: window.checkoutConfig.payment.lcnetpaymentjs.saveCards,
          selectedCard: ko.observable(''),
          paymentClientToken: null,
          paymentIPGForm: null,
          saveCard: ko.observable(false),  // Observable for save card checkbox
          isCustomerLoggedIn: ko.observable(customer.isLoggedIn()), // Observable for customer login status
          defaults: {
              template: 'AutifyDigital_LloydscardnetPayment/payment/lcnetpaymentjs'
          },
          getCode: function() {
              return 'lcnetpaymentjs';
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

          initialize: function (data, event) {
              var self = this;
              this._super();
              setTimeout(function(){
                  const DomUtils = {
                      getEl: (selector) => window.document.querySelector(selector),

                      hasClass: (el, cssClass) => {
                          if (el && el.classList) {
                              return el.classList.contains(cssClass);
                          }
                          return false;
                      },

                      removeClass: (el, cssClass) => {
                          if (el && el.classList) {
                              el.classList.remove(cssClass);
                          } else if (el && DomUtils.hasClass(el, cssClass)) {
                              const reg = new RegExp(`(\\s|^)${cssClass}(\\s|$)`);
                              el.className = el.className.replace(reg, ' ');
                          }
                      },
                  };
                  const customCSS = window.checkoutConfig.payment.lcnetpaymentjs.custom_css;

                  const allowedBrands = window.checkoutConfig.payment.lcnetpaymentjs.allowed_brands || [];

                  const config = {
                      fields: {
                          card: {
                              selector: '[data-cc-card]',
                              placeholder: window.checkoutConfig.payment.lcnetpaymentjs.placeholder_card
                          },
                          cvv: {
                              selector: '[data-cc-cvv]',
                              placeholder: window.checkoutConfig.payment.lcnetpaymentjs.placeholder_cvv
                          },
                          exp: {
                              selector: '[data-cc-exp]',
                          },
                          name: {
                              selector: '[data-cc-name]',
                              placeholder: window.checkoutConfig.payment.lcnetpaymentjs.placeholder_name
                          },
                      },
                      classes: {
                          empty: 'empty',
                          focus: 'focus',
                          invalid: 'invalid',
                          valid: 'valid',
                      },
                  };

                  if (allowedBrands.length > 0) {
                      config.fields.card.allowedBrands = allowedBrands;
                  }

                  if (customCSS && typeof customCSS === 'object' && Object.keys(customCSS).length > 0) {
                      config.styles = {
                          customCSS: customCSS
                      };
                  } else if (customCSS && typeof customCSS === 'string' && customCSS.trim() !== '') {
                      config.styles = {
                          customCSS: {
                              base: customCSS
                          }
                      };
                  }

                  const hooks = {
                      preFlowHook: function (callback) {
                          let request = new XMLHttpRequest();
                          request.onload = () => {
                              if (request.status >= 200 && request.status < 300) {
                                  // values come from authorize-session endpoint
                                  callback(JSON.parse(request.responseText));
                              } else {
                                  throw new Error("error response: " + request.responseText);
                              }
                              request = null;
                          };
                          fullScreenLoader.startLoader();
                          request.open("POST", url.build('lloyds/paymentjs/authJsData'), true);
                          request.send();
                      },
                  };

                  window.onCreate = (paymentForm) => {
                      window.paymentIPGForm = paymentForm;
                      window.onSuccessForm = (clientToken) => {
                          window.paymentClientToken = clientToken;
                          console.log(window.paymentClientToken);
                          fullScreenLoader.startLoader();
                          console.log("submit success; clientToken=\""+clientToken+"\"");
                          self.placeOrder();
                      };

                      window.onErrorForm = (error) => {
                          globalMessageList.addErrorMessage({
                              message: getPaymentJsErrorMessage(error, allowedBrands)
                          });
                          console.log("Tokenize Error: " + error.message);
                          fullScreenLoader.stopLoader();
                      };

                      const ccFields = window.document.getElementsByClassName('payment-fields');
                      for (let i = 0; i < ccFields.length; i++) {
                          DomUtils.removeClass(ccFields[i], 'disabled');
                      }
                      //ENABLEBUTTON
                  };

                  if (typeof window.firstdata !== "undefined") {
                      window.firstdata.createPaymentForm(config, hooks, window.onCreate);
                  }

              }, 2000);
          },

          /**
           * Function to find the card object by token_id
           *
           * @param tokenId
           * @returns
           */
          findTokenById: function (tokenId) {
              return this.saveCards.find(function (card) {
                  return card.token_id === tokenId;
              });
          },

          /**
           *
           * @param data
           * @param event
           * @returns {boolean}
           */
          placeOrder: function (data, event) {
              fullScreenLoader.stopLoader();
              if(window.paymentClientToken || this.selectedCard()) {
                  var self = this;

                  if (event) {
                      event.preventDefault();
                  }

                  if (this.validate() &&
                      additionalValidators.validate() &&
                      this.isPlaceOrderActionAllowed() === true
                  ) {
                      fullScreenLoader.startLoader();
                      this.isPlaceOrderActionAllowed(false);

                      this.getPlaceOrderDeferredObject()
                          .done(
                              function () {
                                  self.afterPlaceOrder();
                              }
                          ).always(
                          function () {
                              self.isPlaceOrderActionAllowed(true);
                          }
                      );
                      fullScreenLoader.stopLoader();
                      return true;
                  }
                  fullScreenLoader.stopLoader();
                  return false;
              } else {
                  fullScreenLoader.startLoader();
                  window.paymentIPGForm.onSubmit(window.onSuccessForm, window.onErrorForm);
              }
          },

          /**
           * After Place Order Function
           */
          afterPlaceOrder: function () {
              fullScreenLoader.startLoader();
              var self = this;
              var selectedTokenId = this.selectedCard();
              var saveCard = this.saveCard();  // Get save card checkbox value
              var paymentAction = window.checkoutConfig.payment.lcnetpaymentjs.payment_action;
              var baseUrl = '';
              var requestData = {
                screen_height: window.innerHeight || 912,
                screen_width: window.innerWidth || 1920,
                save_card: saveCard
              };

               // For authorize_capture, use existing ProcessJsData flow
                if (selectedTokenId) {
                    var selectedCard = this.findTokenById(selectedTokenId);
                    if (selectedCard) {
                        var URL = url.build('lloyds/paymentjs/processJsData?token_id=' + encodeURIComponent(selectedTokenId));
                    } else {
                        var URL = url.build('lloyds/paymentjs/processJsData');
                    }

                } else {
                    var URL = url.build('lloyds/paymentjs/processJsData');
                }
              var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
              var browser_height = window.innerHeight || document.documentElement.clientHeight|| document.body.clientHeight;

              $.ajax({
                  showLoader: true,
                  url: URL,
                  data: requestData,
                  dataType: 'json',
                  type: 'GET'
              }).done(function (response) {
                  if(response) {
                      if(response['url']) {
                          if (self.isValidRedirectUrl(response['url'])) {
                              $('body').trigger('processStart');
                              window.location = response['url'];
                              return true;
                          } else {
                              errorProcessor.process({
                                  message: 'Invalid redirect URL received from payment gateway'
                              }, this.messageContainer);
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
                          errorProcessor.process(response, this.messageContainer);
                          fullScreenLoader.stopLoader();
                          return false;
                      }
                      return false;
                  } else {
                      errorProcessor.process(response, this.messageContainer);
                      fullScreenLoader.stopLoader();
                      return false;
                  }
              }).fail(function (response) {
                  errorProcessor.process(response, this.messageContainer);
                  fullScreenLoader.stopLoader();
              });

              return false;
          },
      });
  }
);
