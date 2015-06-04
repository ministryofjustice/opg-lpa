/*jshint maxparams: 5, unused: false */
function DateHelper() {
  'use strict';

  function _parseDate(input) {
    var parts = input.match(/(\d+)/g);
    return new Date(parts[2], parts[1] - 1, parts[0]);
  }

  function _configureDateBeforeShow(input, inst, datepicker, minDate, maxDate) {
    $(datepicker).datepicker('option', 'minDate', minDate);
    $(datepicker).datepicker('option', 'maxDate', maxDate);
    setTimeout(function () {
      inst.dpDiv.css({marginTop: 0 + 'px', marginLeft: 0 + 'px'});
    }, 1);
  }

  var _dateDefaults = {
    changeMonth: true,
    changeYear: true,
    monthNames: [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ],
    // showButtonPanel: true,
    // yearRange: '2002:2012',
    dateFormat: 'dd-mm-yy',
    closeText: 'Close calendar'
  };

  return { parseDate: _parseDate,
           configureDateBeforeShow: _configureDateBeforeShow,
           dateDefaults: _dateDefaults};

}