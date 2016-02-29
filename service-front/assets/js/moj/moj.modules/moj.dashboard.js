// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Dashboard = {

    init: function () {
      this.changeMobileActions();
      this.searchFocus();
      this.ie8NthChildFix();
    },

    changeMobileActions: function(){
      // In list view on mobile, disable DETAILS and show all actions
      if (moj.Helpers.isMobileWidth()) {
        $('tr .lpa-actions').each(function(){
          $('.lpa-manage details', this).before($('.lpa-manage details ul', this));
          $('.lpa-manage details').addClass('hidden');
        })
      }
    },

    searchFocus:function(){
      if ($('.js-search-focus').val() != '') {
        $('.js-search-focus').addClass('focus');
      }
      $('.js-search-focus').on('focus', function(){
        if (!$(this).hasClass('focus')){
          $(this).addClass('focus');
        }
      });
      $('.js-search-focus').on('blur', function(){
        if ($(this).val() == '') {
          $(this).removeClass('focus');
        }
      });
    },

    ie8NthChildFix: function(){
      if ($('html').hasClass('lte-ie8')) {
        $('.lpa-cards tr:nth-child(2n-1)').addClass('odd');
      }
    }
  };
})();
