// Cache-busting ajax requests, based on the value of window.MAKE_ENV.cacheBust;
// this value is set in the env-vars.js file, generated from env-vars.template.js.

(function (window) {

    var cacheBustingUrl = function (url) {
        // defined in env-vars JS file
        var cacheBust = window.getMakeEnvVar('cacheBust');

        if (cacheBust === undefined) {
            return url;
        }

        var url = window.URI(url);
        url.addQuery({cacheBust: cacheBust});
        return url.toString();
    };

    window.cacheBusting = {
        url: cacheBustingUrl,
    };

})(window);
