// Validation module for LPA
// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Validation = {
    selector: '.validation-summary[role=alert]',

    init: function () {
      _.bindAll(this, 'render');
      this.bindEvents();
      this.render(null, {wrap: 'body'});
      this.oldValidation();
    },

    bindEvents: function () {
      moj.Events.on('Validation.render', this.render);
    },

    render: function (e, params) {
      var $el = $(this.selector, $(params.wrap));

      if ($el.length > 0) {
        $el.focus();
      }
    },

    // TO DO: replace with newer validation
    oldValidation: function(){
      $('body').on('click', 'form [role="alert"] a', function() {
        var $target = $($(this).attr('href'));
        $('html, body')
          .animate({
            scrollTop: $target.offset().top
          }, 300)
          .promise()
          .done(function() {
            $target.closest('.group').find('input,select').first().focus();
          });
      });
    }
  };

})();