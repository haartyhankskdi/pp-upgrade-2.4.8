define([
    'Magento_Ui/js/grid/listing',
    'underscore',
    'jquery',
    'Magento_Ui/js/modal/modal'
], function (Listing, _, $, modal) {
    'use strict';
    
    return Listing.extend({
        defaults: {
            imports: {
                currentLabel: '${ $.provider }:data.currentLabel',
                prevLabel:    '${ $.provider }:data.prevLabel',
                nextLabel:    '${ $.provider }:data.nextLabel',
                prev:         '${ $.provider }:data.prev',
                next:         '${ $.provider }:data.next'
            }
        },
    
        initialize: function () {
            _.bindAll(this, 'onClick');
        
            this._super();
        
            return this;
        },
        
        initObservable: function () {
            this._super()
                .track({
                    currentLabel: '',
                    prevLabel:    '',
                    nextLabel:    '',
                    prev:         0,
                    next:         0
                }).observe(['point']);
            
            return this;
        },
        
        getOffset: function (point) {
            var len = 14 * 60 * 60;
            var percent = (point.time - 8 * 60 * 60) / len * 100;
            if (percent < 0) {
                percent = 0
            } else if (percent > 100) {
                percent = 100;
            }
            return percent + '%';
        },
        
        goPrev: function () {
            this.reload(this.prev);
        },
        
        goNext: function () {
            this.reload(this.next);
        },
        
        reload: function (offset) {
            this.source.params['offset'] = offset;
            this.source.params['rand'] = Math.random();
            
            this.source.reload();
        },
        
        onClick: function (point) {
            this.point(point);
            return;
            var modalInstance = modal({
                autoOpen:         true,
                modalClass:       'activity-modal',
                responsive:       true,
                clickableOverlay: true,
                title:            point.title,
                type:             'popup',
                buttons:          [
                    {
                        text:    $.mage.__('Open'),
                        'class': 'action-primary',
                        click:   function () {
                            window.location.href = point.link;
                        }
                    }
                ]
            });
            
            // close handler
            $(modalInstance.options.modalCloseBtn).on('click', function () {
                modalInstance.closeModal();
            });
            
            $('.activity-modal .modal-content').html(point.content);
        }
    });
});
