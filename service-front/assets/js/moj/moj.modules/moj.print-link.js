// Fees module for LPA
// Dependencies: moj, jQuery

(function() {
    moj.Modules.PrintLink = {

        init: function () {
            this.hookupPrintLinks();
        },

        hookupPrintLinks: function() {
            $('a.print').on('click', this.handleClick);
        },

        handleClick: function(event) {
            var isInPageLink =
                location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') &&
                location.hostname == this.hostname;

            if (isInPageLink) {
                GOVUK.analytics.trackEvent('Print-page', 'User requested to print page', { transport: 'beacon' });

                window.print();
                return false
            }
        }
    };

})();
