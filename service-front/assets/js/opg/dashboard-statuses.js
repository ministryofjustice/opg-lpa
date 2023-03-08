$(document).ready(function () {
  // Get an array of the LPA IDs to check for a status update
  var ids = $('li[data-refresh-id]')
    .map(function () {
      return this.getAttribute('data-refresh-id');
    })
    .get();

  if (ids.length > 0) {
    $.ajax({
      url: '/user/dashboard/statuses/' + ids.join(),
      dataType: 'json',
      headers: {
        'X-SessionReadOnly': 'true',
        Accept: 'application/json',
        'Accept-Language': 'en',
      },
      success: function (results) {
        // Loop through all the ids that were requested and update the text css style to the new status
        ids.forEach(function (lpaId) {
          var lpaResult = results[lpaId];
          var li = $('li[data-refresh-id=' + lpaId + ']');
          var statusIndicator = li.find('.opg-status');

          if (lpaResult['found']) {
            // Get the status from the returned data
            var newStatus = lpaResult['status'].toLowerCase();

            // Store current status then update style on the status indicator
            var currentStatus = statusIndicator.html().toLowerCase();

            statusIndicator.html(newStatus);

            statusIndicator.removeClass('opg-lozenge-status--' + currentStatus);
            statusIndicator.addClass('opg-lozenge-status--' + newStatus);

            // Update style on status container
            li.removeClass('status-container--' + currentStatus);
            li.addClass('status-container--' + newStatus);

            // Update the status link with the new status
            var link = $('a[id=status-description-link-' + lpaId + ']');
            link.attr('href', '/lpa/' + lpaId + '/status');
            link.attr(
              'data-journey-click',
              'page:link:View ' + newStatus + ' message',
            );
            link.text('View ' + newStatus + ' message');
          }

          // mark the status as "refreshed", whether the status update was successful or not
          statusIndicator.attr('data-refreshed', 'true');
        });
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(textStatus + ' ' + errorThrown);
      },
    });
  }
});
