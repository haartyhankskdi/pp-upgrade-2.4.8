define([
    'jquery',
    'underscore',
    'moment',
    'Magezon_PopupBuilder/js/dialogs-manager'
], function ($, _, moment) {
    'use strict';

    var dialogManager = new DialogsManager.Instance({
        classPrefix: 'popupbuilder'
    });

    $.widget('popupbuilder.popup', {

        _create: function () {
            var self = this;
            this.modal = '';
            this.clicksCount = 0;
            this.popupElem = this.element.children('.popupbuilder-poup-content-inner');
            this.initModal();
            this.setViews();
            this.initListeners();
            this.initTriggers();
            this.popupElem.find('a').click(function(event) {
                self.addReport('click');
            });
        },

        setViews: function() {
            var pageViews = localStorage.getItem('popupbuilder_pageViews') || 0;
            localStorage.setItem('popupbuilder_pageViews', parseInt(pageViews) + 1);
        },

        isJSON: function(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        },

        getKey: function (key) {
            return 'popup_' + this.options.id + '_' + key;
        },

        setStorage: function (key, options) {
            var items = (_.isArray(options) || _.isObject(options)) ? JSON.stringify(options) : options;
            localStorage.setItem(this.getKey(key), items)
        },

        getStorage: function (key) {
            var value = localStorage.getItem(this.getKey(key));
            return this.isJSON(value) ? JSON.parse(value) : value;
        },

        getTriggerSetting: function (key) {
            return this.options['trigger'][key];
        },

        getDisplaySetting: function (key) {
            return this.options['display'][key];
        },

        getStyleSetting: function (key) {
            return this.options['style'][key];
        },

        initListeners: function() {
            var self = this;
            this.popupElem.find('.mgz-element-popup_action').each(function(index, el) {
                var actionType = $(this).data('action-type');
                if (actionType) {
                    $(this).find('a').click(function(e) {
                        switch(actionType) {
                            case 'leave':
                                e.preventDefault();
                                window.history.back();
                                break;

                            case 'close-popup':
                                e.preventDefault();
                                self.modal.hide();
                                break;

                            case 'close-constantly':
                                e.preventDefault();
                                self.setStorage('disabled', true);
                                self.modal.hide();
                                break;

                            case 'close-all-constantly':
                                e.preventDefault();
                                window.popupbuilder.disabled = true;
                                $(document).trigger('closeAllPopups');
                                break;
                        }
                    });
                }
            });

            var width = self.getDisplaySetting('width') ? parseFloat(self.getDisplaySetting('width')) : 700;
            $(window).resize(function () {
                if ($(window).width() <= width)  {
                    self.popupElem.parents('.popupbuilder-widget-content').css({
                        left: 0,
                        right: 0,
                        width: 'initial'
                    });
                } else {
                    self.popupElem.parents('.popupbuilder-widget-content').css({
                        left: '',
                        right: '',
                        width: ''
                    });
                }
            }).resize();

            $(document).on('closeAllPopups', function() {
                self.modal.hide();
            });

            this.element.on('showPopup', function() {
                self.showPopup(true);
            })
        },

        initTriggers: function () {
            var self = this;
            if (self.options.previewMode) {
                self.showPopup(true);
                window.popupbuilder.disabled = true;
            } else {
                if (this.isValid() && this.options.isValid) {
                    this.initPageLoadTrigger();
                    this.initScrollingTrigger();
                    this.initScrollingToTrigger();
                    this.initClickTrigger();
                    this.initHoverTrigger();
                    this.initInactivityTrigger();
                    this.initExitIntentTrigger();
                }
            }
        },

        isValid: function() {
            var self = this;
            if (self.getDisplaySetting('avoid_multiple_popups') && window.popupbuilder.showed) {
                return false;
            }
            return self.isValidPageViews() && self.isValidMinutes() && self.isValidTimes() && self.isValidUrl() && self.isValidSource() && !window.popupbuilder.disabled && !self.getStorage('disabled') && !self.showed;
        },

        isValidPageViews: function() {
            var self = this;
            if (!self.getTriggerSetting('page_views')) return true;
            var pageViewsViews = parseInt(self.getTriggerSetting('page_views_views'));
            var pageViews = parseInt(localStorage.getItem('popupbuilder_pageViews'));
            var initialPageViews = self.getStorage('initialPageViews');
            if (!initialPageViews) {
                initialPageViews = pageViews;
                self.setStorage('initialPageViews', initialPageViews);
            }
            return pageViews - initialPageViews >= pageViewsViews;
        },

        isValidMinutes: function() {
            var self = this;
            if (!self.getTriggerSetting('minutes')) return true;
            var minutes = parseFloat(self.getTriggerSetting('minutes_minutes'));
            var opendedAt = self.getStorage('opended');
            if (self.getStorage('opended')) {
                var opendeded = moment(new Date(self.getStorage('opended')));
                var now       = moment(new Date());
                var duration  = moment.duration(now.diff(opendeded));
                var range     = Math.floor(duration.asMinutes());
                return range >= minutes;
            }
            return true;
        },

        isValidTimes: function() {
            var self = this;
            if (!self.getTriggerSetting('times')) return true;
            var timesTimes = parseFloat(self.getTriggerSetting('times_times'));
            var type = self.getTriggerSetting('times_type');
            var times = self.getStorage(type + '_times') || 0;
            return times < timesTimes; 
        },

        isValidUrl: function() {
            var self = this;
            if (!self.getTriggerSetting('url')) return true;
            var action = self.getTriggerSetting('url_action');
            var url = self.getTriggerSetting('url_url');
            var referrer = document.referrer;
            
            if ('regex' !== action) {
                return 'hide' === action ^ -1 !== referrer.indexOf(url);
            }

            var regexp;

            try {
                regexp = new RegExp(url);
            } catch (e) {
                return false;
            }

            return regexp.test(referrer);
        },

        isValidSource: function() {
            var self = this;
            if (!self.getTriggerSetting('sources')) return true;
            var sources = self.getTriggerSetting('sources_sources');

            if (3 === sources.length) {
                return true;
            }

            var referrer = document.referrer.replace(/https?:\/\/(?:www\.)?/, ''),
                isInternal = 0 === referrer.indexOf(location.host);

            if (isInternal) {
                return -1 !== sources.indexOf('internal');
            }

            if (-1 !== sources.indexOf('external')) {
                return true;
            }

            if (-1 !== sources.indexOf('search')) {
                return (/\.(google|yahoo|bing|yandex|baidu)\./.test(referrer));
            }

            return false;
        },

        initPageLoadTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('page_load')) return;
            var pageLoadDelay = parseFloat(self.getTriggerSetting('page_load_delay'));
            setTimeout(function () {
                self.showPopup();
            }, pageLoadDelay * 1000);
        },

        initScrollingTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('scrolling')) return;
            var lastScrollOffset = window.scrollY;
            $(window).on('scroll', function() {
                var scrollDirection = scrollY > lastScrollOffset ? 'down' : 'up';
                var direction = self.getTriggerSetting('scrolling_direction');
                var offset = parseFloat(self.getTriggerSetting('scrolling_offset'));
                lastScrollOffset = scrollY;
                if (scrollDirection !== direction) return;
                var fullScroll = $(document).height() - innerHeight,
                scrollPercent = scrollY / fullScroll * 100;

                if (('up' === scrollDirection && scrollPercent <= offset)
                    || ('down' === scrollDirection && scrollPercent >= offset)
                    ) {
                    self.showPopup();
                }
            });
        },

        initScrollingToTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('scrolling_to')) return;
            var targetElement = $(self.getTriggerSetting('scrolling_to_selector'));
            if (targetElement.length) {
                targetElement.waypoint(function() {
                    self.showPopup();
                });
            }
        },

        initClickTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('click')) return;
            var clickTimes = parseInt(self.getTriggerSetting('click_times'));
            $('body').on('click', function() {
                self.clicksCount++;
                if (self.clicksCount === clickTimes) {
                    self.showPopup();
                }
            });
        },

        initHoverTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('hover_on')) return;
            var targetElement = $(self.getTriggerSetting('hover_selector'));
            if (targetElement.length) {
                targetElement.on('hover', function() {
                    self.showPopup();
                });
            }
        },

        initInactivityTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('inactivity')) return;
            var inactivityTime = parseFloat(self.getTriggerSetting('inactivity_time'));
            var timeOut;

            var startTimer = function() {
                timeOut = setTimeout(function() {
                    self.showPopup();
                }, inactivityTime * 1000);
            }

            var clearTimer = function() {
                clearTimeout(timeOut);
            }

            var restartTimer = function() {
                clearTimer();
                startTimer();
            }

            $(document).on('keypress mousemove scroll', restartTimer);
        },

        initExitIntentTrigger: function() {
            var self = this;
            if (!self.getTriggerSetting('exit_intent')) return;
            $(window).on('mouseleave', function(e) {
                if (e.clientY <= 0) {
                    self.showPopup();
                }
            });
        },

        showPopup: function(force) {
            var self = this;
            if (force || self.isValid()) {
                self.modal.show();
            }
        },

        initModal: function() {
            var self               = this;
            var closeAutomatically = self.getDisplaySetting('close_automatically') ? parseFloat(self.getDisplaySetting('close_automatically')) : 0;
            var entranceAnimation  = self.getDisplaySetting('entrance_animation');
            var position           = self.getDisplaySetting('position');
            var preventScroll      = self.getDisplaySetting('prevent_scroll');
            var exitAnimation      = self.getDisplaySetting('exit_animation');
            var animationDuration  = self.getDisplaySetting('animation_duration') ? parseFloat(self.getDisplaySetting('animation_duration')) : 0;
            var preventCloseOnBackgroundClick = self.getDisplaySetting('prevent_close_on_background_click');
            if (!self.getDisplaySetting('overlay')) preventCloseOnBackgroundClick = true;

            var classes = self.options.customClass;
            if (self.getTriggerSetting('devices')) {
                var devices = ['xl', 'lg', 'md', 'sm', 'xs'];
                var devicesDevices = self.getTriggerSetting('devices_devices');
                var difference = _.difference(devices, devicesDevices);
                _.each(difference, function(_device) {
                    classes += ' mgz-hidden-' + _device;
                });
            }
            if (position) classes += ' mgz-flex mgz-flex-position-' + position;

            var modal;

            var settings = {
                id: 'popupbuilder-popup' + self.options.id,
                className: 'popupbuilder-popup-modal' + (classes ? ' ' + classes : ''),
                closeButton: self.getDisplaySetting('close_button'),
                closeButtonClass: 'mgz-icon mgz-icon-close',
                preventScroll: self.getDisplaySetting('prevent_scroll'),
                effects: {
                    hide: function hide() {
                        if (entranceAnimation) modal.getElements('widgetContent').removeClass(entranceAnimation);
                        if (exitAnimation) {
                            modal.getElements('widgetContent').addClass(exitAnimation);
                            setTimeout(function () {
                                modal.getElements('widget').hide();
                                modal.getElements('widgetContent').removeClass(exitAnimation);
                            }, animationDuration * 1000);
                        } else {
                            modal.getElements('widget').hide();
                        }
                    }
                },
                hide: {
                    auto: !!closeAutomatically,
                    autoDelay: closeAutomatically * 1000,
                    onBackgroundClick: !preventCloseOnBackgroundClick,
                    onOutsideClick: !preventCloseOnBackgroundClick,
                    onEscKeyPress: !self.getDisplaySetting('prevent_close_on_esc_key')
                },
                position: {
                    enable: false
                },

                onShow: function() {
                    this.getElements('widgetContent').addClass('mgz-flex animated ' + entranceAnimation);
                    self.closeButtonDelay(this);
                    var now = new Date();
                    self.setStorage('opended', now.getTime());
                    var openTimes = self.getStorage('open_times') || 0;
                    self.setStorage('open_times', openTimes + 1);
                    self.addReport('open');
                    self.showed = true;
                    window.popupbuilder.showed = true;
                    $(document).trigger('mgz:init');
                },

                onHide: function() {
                    var closeTimes = self.getStorage('close_times') || 0;
                    self.setStorage('close_times', closeTimes + 1);
                    self.addReport('close');
                }
            };
            modal = dialogManager.createWidget('lightbox', settings).setMessage(self.popupElem);
            modal.getElements('widgetContent').addClass('popupbuilder-poup-content');
            self.modal = modal;
        },

        closeButtonDelay: function(modal) {
            var self = this;
            if (self.getDisplaySetting('close_button')) {
                var animationDuration   = self.getDisplaySetting('animation_duration') ? parseFloat(self.getDisplaySetting('animation_duration')) : 0;
                var closeButtonDelay    = (self.getDisplaySetting('close_button_delay') ? parseFloat(self.getDisplaySetting('close_button_delay')) : 0);
                var closeButtonPosition = self.getStyleSetting('closebutton')['position'];
                var closeButton         = modal.getElements('closeButton');
                if (closeButtonDelay) closeButton.hide();
                closeButton.addClass('popupbuilder-popup-close');
                clearTimeout(closeButtonTimeout);
                var closeButtonTimeout = setTimeout(function () {
                    closeButton.appendTo(modal.getElements('outside' === closeButtonPosition ? 'widget' : 'widgetContent'));
                    closeButton.css('display', '');
                }, closeButtonDelay * 1000);
            }
        },

        closeAutomatically: function() {
            var self = this;
            var closeAutomatically = parseFloat(self.getDisplaySetting('close_automatically'));
            if (closeAutomatically) {
                var timerElem = self.popupElem.find('.popupbuilder-timer');
                var currentTime = closeAutomatically, myTimer;
                timerElem.html(closeAutomatically);
                var stopTimer = function() {
                    clearInterval(myTimer);
                }
                var resetTimer = function() {
                    stopTimer();
                    currentTime = closeAutomatically;
                    timerElem.html(currentTime);
                }
                var startTimer = function() {
                    if (currentTime<=0) {
                        resetTimer();
                        startTimer();
                    } else {
                        myTimer = setInterval(function() {
                            currentTime--;
                            timerElem.html(currentTime);
                            if (currentTime == 0) {
                                $.magnificPopup.close();
                                stopTimer();
                            }
                        }, 1000);
                    }
                }
                startTimer();
            }
        },

        getCloseBtnHtml: function() {
            return '<div class="popupbuilder-popup-close"><i class="mgz-icon mgz-icon-close"></i></div>';
        },

        addReport: function(type) {
            var self = this;
            $.ajax({
                url: self.options.reportUrl,
                data: {
                    id: self.options.id,
                    type: type
                },
                type: 'post',
                dataType: 'json',
                headers: {'X-Alt-Referer': 'https://www.google.com' },
                success: function(res) {

                }
            });
        }
    });

    return $.popupbuilder.popup;
});