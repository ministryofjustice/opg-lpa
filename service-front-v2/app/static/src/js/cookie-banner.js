(function () {
    function getCookie(name) {
      // Split cookie string and get all individual name=value pairs in an array
      var cookies = document.cookie.split(";");
  
      // Loop through the array elements
      for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].split("=");
  
        /* Removing whitespace at the beginning of the cookie name
                and compare it with the given string */
        if (name == cookie[0].trim()) {
          // Decode the cookie value and return
          return decodeURIComponent(cookie[1]);
        }
      }
  
      // Return null if not found
      return null;
    }
  
    var cookieBanner = document.getElementById("cookie-banner");
  
    // Dont display if cookies policy already set
    if (getCookie("cookies_policy")) {
      cookieBanner.hidden = true;
    } else {
      var defaultMessage = document.getElementById("default-message");
      var acceptedMessage = document.getElementById("accepted-message");
      var rejectedMessage = document.getElementById("rejected-message");
  
      // Accept additional cookies
      document.getElementById("accept-cookies").addEventListener("click", function () {
        document.cookie = 'cookies_policy={"analytics": "yes", "functional": "yes"}; max-age=31557600; path=/; secure';
        defaultMessage.hidden = true;
        acceptedMessage.hidden = false;
      });
  
      // Reject additional cookies
      document.getElementById("reject-cookies").addEventListener("click", function () {
        document.cookie = 'cookies_policy={"analytics": "no", "functional": "no"}; max-age=31557600; path=/; secure';
        defaultMessage.hidden = true;
        rejectedMessage.hidden = false;
      });
  
      // Hide accepted message
      document.getElementById("accepted-hide").addEventListener("click", function () {
        acceptedMessage.hidden = true;
        cookieBanner.hidden = true;
      });
  
      // Hide rejected message
      document.getElementById("rejected-hide").addEventListener("click", function () {
        rejectedMessage.hidden = true;
        cookieBanner.hidden = true;
      });
    }
})();
  