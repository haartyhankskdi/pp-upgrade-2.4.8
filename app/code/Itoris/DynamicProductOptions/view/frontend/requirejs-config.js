/**
* Copyright © 2016 ITORIS INC. All rights reserved.
* See license agreement for details
*/
var config = {
    map: {
        '*': {
            'itoris_options'  : 'Itoris_DynamicProductOptions/js/options'
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Itoris_DynamicProductOptions/js/catalog-add-to-cart-dpo': true
            }
        }
    }
};