/*
 * @author Chris Moreton
 * @author Jianzhong Yu
 * @author Tim Paul
 * @author Mat Harden
 * @author Dom Smith
 */


window.lpa = window.lpa || {};


// ====================================================================================
// SCROLL TO CURRENT SECTION OF LPA
// This happens before anything else, but it pauses slightly before scrolling (200ms)

(function() {
  'use strict';
  if ( $('section.current').offset() !== undefined ) {
    setTimeout(function() {
      if (window.location.href.substring(window.location.href.lastIndexOf('/') + 1) !== 'lpa-type') {
        window.scrollTo(0, $('section.current').offset().top - 107);
      }
    }, 200);
  }
})();

$(document).ready(function () {
  'use strict';

  // ====================================================================================
  // COMMON VARIABLES

  var body = $('body');


  // ====================================================================================
  // FORM VALIDATION
  // NOTE: Only on the older pages. This is older validation and will be replaced.

  body.on('click', 'form [role="alert"] a', function() {
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


  // ====================================================================================
  // WHO IS APPLYING TO REGISTER?
  // NOTE: This is on the 'applicant' page (Registration #1)

  // Only do the following if .js-attorney-list exists
  if ($('.js-attorney-list')[0]) {

    // Toggle all checkboxes under Attorneys
    $('[name="whoIsRegistering"]').change(function(){
      if($(this).val() === 'donor' ){
        $('.attorney-applicant input:checkbox').prop('checked', false);
      } else {
        $('.attorney-applicant input:checkbox').prop('checked', true);
      }
    });

    // Revert to Donor if no Attorneys are checked
    $('.attorney-applicant input').change(function(){
      if($('.attorney-applicant input').is(':checked')){
        $('input[name="whoIsRegistering"][value!="donor"]').prop('checked', true);
      } else {
        $('input[name="whoIsRegistering"][value="donor"]').prop('checked', true);
      }
    });

  }


  // ====================================================================================
  // JQUERY UI DATEPICKER SETUP
  // NOTE: Only on the 'date-check' page.

  $('.date-field input').datepicker(
    {
      dateFormat: 'dd/mm/yy',
      altFormat: 'dd/mm/yy',
      firstDay: 1,
      autoSize:true,
      changeMonth:true,
      changeYear:true
    }
  );


  // ====================================================================================
  // FEE REDUCTION CHOICES
  // NOTE: This is only on the 'fee-reduction' page (Registration #4)
  // This could be some sort of a label subtext pattern

  $('input[name=reductionOptions]').change(function(){
      $('.revised-fee').addClass('hidden');
      if ($('#reducedFeeReceivesBenefits').is(':checked')) {
          $('#revised-fee-0').removeClass('hidden');
      } else if ($('#reducedFeeUniversalCredit').is(':checked')) {
          $('#revised-fee-uc').removeClass('hidden');
      }
  });


  // ====================================================================================
  // DELETE PERSON (ACTOR) CONFIRMATION AND REDIRECT
  // NOTE: Only on older pages.
  // TO DO: Would like to get away from generic alerts.

  body.on('click', 'a.delete-confirmation', function(event){
    moj.log('Delete person?');
    event.preventDefault();
    var url=$(this).attr('href');
    if(confirm('Do you want to remove this person?')) {
      window.location.href=url;
    }
  });


  // ====================================================================================
  // EMPHASISED CHECKBOX AND RADIO BUTTON LABEL STYLES
  // NOTE: Only on older pages. This won't be needed when new styles come in

  var $emphasised = $('.emphasised input');
  $emphasised.filter(':checked').parent().addClass('checked');
  $emphasised.change(function() {
      $emphasised.parent().removeClass('checked');
      $emphasised.filter(':checked').parent().addClass('checked');
  });

});

