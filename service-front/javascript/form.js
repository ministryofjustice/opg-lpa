/*
 * @author Chris Moreton
 * @author Jianzhong Yu
 * @author Tim Paul
 * @author Mat Harden
 * @author Dom Smith
 */


window.lpa = window.lpa || {}

// Select the given value of a select box where present
lpa.updateSelectbox = function (el, value) {
  var found = false,
      field = el.attr('name'),
      form = el.closest('form');

  // Check if value is in list
  el.children('option').each(function () {
    if ($(this).prop('value') === value) {
      found = true;
    }
  });

  // If not an available option, change field to text box
  if (!found) {
    el.val('Other...').change();
  }

  // Apply the correct value
  // As the field changes to a text box we lose it's reference,
  // so we need to reselect the element.
  form.find('[name=' + field + ']').val(value); // use the name attr as it's unique & will always exist
};

(function() {
    if ( !$('#lpa-type').hasClass('current') && $('section.current').offset() != undefined ) {
        setTimeout(function() {
            if (window.location.href.substring(window.location.href.lastIndexOf('/') + 1) != 'lpa-type') {
                window.scrollTo(0, $('section.current').offset().top - 107);
            }
        }, 200);
    }
})();

$(document).ready(function () {

  // ====================================================================================
  // COMMON VARIABLES

  var body = $('body');

  // ====================================================================================
  // DETAILS TAG SUPPORT

  $('html').addClass($.fn.details.support ? 'details' : 'no-details');
  $('details').details();

  // ====================================================================================
  // FORM VALIDATION

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
  // TOGGLEABLE FORMS



  // Donor cannot sign LPA
  $(document).delegate('#donor_cannot_sign', 'change', function (evt) {
    var donorCannotSign = $(this).is(':checked');
    if (donorCannotSign) {
      $('#donorsignprompt').show();
    } else {
      $('#donorsignprompt').hide();
    }
  });

  // Cancel pop-up
  body.on('click', 'button#form-cancel', function (e) {
    e.preventDefault();
    $('#lightboxclose').click();
  });


  // RADIOS WITH CONDITIONAL CONTENT
  //
  // A jQuery function for toggling content based on selected radio buttons
  //
  // Usage:
  //
  // $(radio).hasConditionalContent();
  //
  // Where radio is one or more of the radios in the group.
  // The elements to be toggled must have an ID made from a concatenation of 'toggle', the radio name and value.
  // For example, a radio with name="radios" and a value of "1" will toggle an element with id="toggle-radios-1".


  jQuery.fn.hasConditionalContent = function() {
      var name = $(this).attr('name');
      $("[id^='toggle-"+name+"']").hide();

      $("[name="+name+"]").change(function(){
          if($(this).is(':checked')){
              $("[id^='toggle-"+name+"']").hide();
              $("#toggle-"+name+"-"+$(this).val()).show();
          }
      }).change();
  }

  $('[name="certificateProviderStatementType"]').hasConditionalContent();
  $('[name="how"]').hasConditionalContent();
  $('[name="when"]').hasConditionalContent();


  // Who is applying to register?

  $("[name='whoIsMakingThisApplication']").change(function(){
    if($(this).val() == 'attorneys' ){
        $('.attorney-applicant input:checkbox').prop('checked', true);
    } else {
        $('.attorney-applicant input:checkbox').prop('checked', false);
    }
  })

  $(".attorney-applicant input").change(function(){
    if($(".attorney-applicant input").is(':checked')){
      $("input[name='whoIsMakingThisApplication'][value='attorneys']").prop('checked', true);
    } else {
      $("input[name='whoIsMakingThisApplication'][value='donor']").prop('checked', true);
    }
  });


  // Calendar control for date fields

  $('.date-field input').datepicker(
    {
      dateFormat: "dd/mm/yy",
      altFormat: "dd/mm/yy",
      firstDay: 1,
      autoSize:true,
      changeMonth:true,
      changeYear:true,
      beforeShow: function(input, inst) {
          inst.dpDiv.css({marginTop: -input.offsetHeight + 'px', marginLeft: input.offsetWidth + 'px'});
      }
    }
  );


  // Any previous LPAs?

  $('#previousLpa').change(function(){
    if($('#toggle-previousLpa textarea').val() != ''){
        $('#previousLpa').prop('checked', true);
    }
    if($(this).is(':checked')) {
        $('#toggle-previousLpa').show();
    } else {
        $('#toggle-previousLpa textarea').val('');
        $('#toggle-previousLpa').hide();
    }
  }).change();


  // Any other info?

  $('#otherInfo').change(function(){
    if($('#toggle-otherInfo textarea').val() != ''){
        $('#otherInfo').prop('checked', true);
    }
    if($(this).is(':checked')) {
        $('#toggle-otherInfo').show();
    } else {
        $('#toggle-otherInfo textarea').val('');
        $('#toggle-otherInfo').hide();
    }
  }).change();


  // Fee remissions

    $allRevisedFees = $('.revised-fee').hide();

    $("input[name=reductionOptions]").change(function(){

        $allRevisedFees.hide();

        if ($('#reducedFeeReceivesBenefits').is(':checked')) {
            $revisedFee = $('#revised-fee-0').show();
        } else if ($('#reducedFeeUniversalCredit').is(':checked')) {
            $revisedFee = $('#revised-fee-uc').show();
        }
    }).change();


  // Make button text reflect chosen payment option

  $('#claimBenefits, #payByCheque, #receiveUniversalCredit').change(function(){
      if($('#claimBenefits, #payByCheque, #receiveUniversalCredit').is(':checked')) {
          $('#form-submit').val('Proceed');
          $('#contact-email').hide();
      } else {
          $('#form-submit').val('Proceed to payment');
          $('#contact-email').show();
      }
  }).change();


  $('#load-pf').click(function(){
    $.get('/service/loadpf');
  });

  $('#load-hw').click(function(){
    $.get('/service/loadhw');
  });


  // Delete user account?

  body.on('click', '#delete-account', function(event){
    event.preventDefault();
    var deleteUrl = $(this).attr('href');
    if(confirm('Are you sure you want to delete this account?')) {
      $.ajax({
        url: deleteUrl,
        type: 'DELETE',
        success: function(resp){
          window.location.href="/";
        }
      });
    }
  });


  // Delete LPA?

  body.on('click', '.delete-lpa', function(event){
    event.preventDefault();
    if(confirm('Are you sure you want to delete this LPA?')) {
      var url = $(this).attr('href');
      window.location.href = url;
    }
  });


  // Delete person?

  body.on('click', 'a.delete-confirmation', function(event){
    event.preventDefault();
    var url=$(this).attr('href');
    if(confirm("Do you want to remove this person?")) {
      window.location.href=url;
    }
  });


  // Watch for changes to lightbox forms

  $('body').on('change', '#form-lightbox input, #form-lightbox select:not(#reusables)', function () {
    $(this).closest('form').data('dirty', true);
  });


  // ====================================================================================
  // POPULATE WITH TEST DATA SCRIPTS

  function populateDate(id, date) {
        if ($(id).length > 0) $(id).val(date);
    }

  function getDateString() {
      var currentTime = new Date();
      var day = currentTime.getDate();
      var month = currentTime.getMonth() + 1;
      var year = currentTime.getFullYear();
      if (day < 10) {
          day = "0" + day;
      }
      if (month < 10) {
          month = "0" + month;
      }
      return day + "/" + month + "/" + year;
  }

  $('#populatetestdates').click(function (event) {
    event.preventDefault();
      var dateString = getDateString();

      populateDate('input#donor', dateString);
      populateDate('input#lifesustaining', dateString);
      for (i=0; i<5; i++) {
          populateDate('input#attorney_' + i, dateString);
          populateDate('input#certificateProvider_' + i, dateString);
          populateDate('input#replacementAttorney_' + i, dateString);
          populateDate('input#trustCorporation', dateString);
      }
  });

  $('#populatenotifiedtestdates').click(function (event) {
    event.preventDefault();
      var dateString = getDateString();

      for (i=0; i<5; i++) {
          populateDate('input#notifiedPerson_' + i, dateString);
      }
  });


  // ====================================================================================
  // Emphasised checkbox and radio button label styles

  var $emphasised = $('.emphasised input');
  $emphasised.filter(':checked').parent().addClass('checked');
  $emphasised.change(function() {
      $emphasised.parent().removeClass('checked');
      $emphasised.filter(':checked').parent().addClass('checked');
  });

});

