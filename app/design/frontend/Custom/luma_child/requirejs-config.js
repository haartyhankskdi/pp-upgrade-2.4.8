var config = {
    shim: {
        'jquery/jquery-migrate': ['jquery']
    },
    deps: [
        'jquery/jquery-migrate'
    ],
    config: {
        mixins: {
            'mage/gallery/gallery': {
                'mage/gallery/gallery-mixin': true
            }
        }
    }
};
