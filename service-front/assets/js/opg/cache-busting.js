// Cache-busting ajax requests, based on the value of window.MAKE_ENV.cacheBust;
// this value is set in the env-vars.js file, generated from env-vars.template.js.

(function (window) {

    var cacheBustingUrl = function (url) {
        // defined in env-vars JS file
        var revision = window.getBuildRevision();

        if (revision === undefined) {
            return url;
        }

        var url = window.URI(url);
        url.addQuery({revision: revision});
        return url.toString();
    };

    window.cacheBusting = {
        url: cacheBustingUrl,
    };

})(window);
