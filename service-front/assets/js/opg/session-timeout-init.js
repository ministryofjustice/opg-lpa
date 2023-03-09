if (typeof window.timeoutWarningDialog === 'undefined') {
  // instantiate object and attach events on page load ready event
  $(
    (window.timeoutWarningDialog = new window.SessionTimeoutDialog({
      element: $('#timeoutPopup'),
      warningPeriodMs: 300000,
    })),
  );
}
