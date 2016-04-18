define('utils/global', function() {
    window.global = window.global || {};
    window.global.data = window.global.data || {};
    window.global.get = function($var) {
        return window.global.data[$var];
    };
});
