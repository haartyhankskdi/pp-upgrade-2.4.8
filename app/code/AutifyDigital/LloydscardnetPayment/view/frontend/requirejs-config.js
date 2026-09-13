var config = {
    map: {
        '*': {
            googlePayApi: 'https://pay.google.com/gp/p/js/pay.js'
        }
    },
    shim: {
        googlePayApi: {
            exports: 'google'
        }
    }
};