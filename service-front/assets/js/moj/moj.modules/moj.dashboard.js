// Dependencies: moj, _, jQuery

(function () {
  'use strict';

  moj.Modules.Dashboard = {

    init: function () {
      this.changeMobileActions();
      this.searchFocus();
    },
    changeMobileActions: function(){
      // In list view on mobile, disable DETAILS and show all actions
      if (moj.Helpers.isMobileWidth()) {
        $('tr .lpa-actions').each(function(){
          $('.lpa-manage details', this).before($('.lpa-manage details ul', this));
          $('.lpa-manage details').addClass('hidden');
        });
      }
    },
    searchFocus:function(){
      // Add .focus class if there's text already there (on load)
      if ($('.js-search-focus').val() !== '') {
        $('.js-search-focus').addClass('focus');
      }
      // Add .focus class when input gets focus
      $('.js-search-focus').on('focus', function(){
        if (!$(this).hasClass('focus')){
          $(this).addClass('focus');
        }
      });
      // Remove .focus class if input is blurred
      $('.js-search-focus').on('blur', function(){
        if ($(this).val() === '') {
          $(this).removeClass('focus');
        }
      });
    }
  };
  
})();
