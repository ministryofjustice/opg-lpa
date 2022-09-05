window.fetchLpaStatuses = function () {
  const moj = window.moj

  // Get an array of the LPA IDs to check for a status update
  const ids = []
  document.querySelectorAll('li[data-refresh-id]').forEach(function (elt) {
    ids.push(elt.getAttribute('data-refresh-id'))
  })

  if (ids.length > 0) {
    moj.Helpers.ajax({
      url: '/user/dashboard/statuses/' + ids.join(','),
      isJson: true,
      headers: {
        'X-SessionReadOnly': 'true'
      },
      success: function (results) {
        // Loop through all the ids that were requested and update the text css style to the new status
        ids.forEach(function (lpaId) {
          const lpaResult = results[lpaId]
          const li = document.querySelector('li[data-refresh-id="' + lpaId + '"]')
          const statusIndicator = li.querySelector('.opg-status')

          if (lpaResult.found) {
            // Get the status from the returned data
            const newStatus = lpaResult.status.toLowerCase()

            // Store current status then update style on the status indicator
            const currentStatus = statusIndicator.textContent.toLowerCase()

            statusIndicator.innerHTML = newStatus

            statusIndicator.classList.remove('opg-lozenge-status--' + currentStatus)
            statusIndicator.classList.add('opg-lozenge-status--' + newStatus)

            // Update style on status container
            li.classList.remove('status-container--' + currentStatus)
            li.classList.add('status-container--' + newStatus)

            // Update the status link with the new status
            const link = document.querySelector('a[id="status-description-link-' + lpaId + '"]')
            link.setAttribute('href', '/lpa/' + lpaId + '/status')
            link.setAttribute('data-journey-click', 'page:link:View ' + newStatus + ' message')
            link.textContent = 'View ' + newStatus + ' message'
          }

          // mark the status as "refreshed", whether the status update was successful or not
          statusIndicator.setAttribute('data-refreshed', 'true')
        })
      },
      error: function (textStatus, errorThrown) {
        console.error(textStatus + ' ' + errorThrown)
      }
    })
  }
}
