// Load code after DOM is loaded
// document.addEventListener('DOMContentLoaded', function() {
  // Check if browser supports local storage

  // Check if user has already consented to cookies
  // If local storage is empty, show the cookie consent banner
  if (localStorage.getItem('cookieConsent') === null ) {

    setTimeout(() => {
      showCookieConsent();
    }, 200);
  }

  // Check if user has already consented to cookies
  if (localStorage.getItem('cookieConsent') === 'true') {
    handleCookieConsent();
    hideCookieConsent()
  }

  // Check if user has already refused cookies
  if (localStorage.getItem('cookieConsent') === 'false') {
    hideCookieConsent()
  }

  // Function to handle cookie consent
  function handleCookieConsent() {
    // Add your logic to inject the desired content into the head tag
    var additionalData = localStorage.getItem('cookieConsentAdditionalData');

    if (additionalData) {
      // Inject additional data at the end of head tag without creating a new script tag
      // var head = document.getElementsByTagName('head')[0];
      // head.insertAdjacentHTML('beforeend', additionalData);
      // Remove <script> and </script> tag from additionalData
      additionalData = additionalData.replace('<script>', '');
      additionalData = additionalData.replace('</script>', '');
      // Execute the injected script
      eval(additionalData);
    }
  }
  
  // Function to hide the cookie consent banner
  function hideCookieConsent() {
    const cookie_banner = document.querySelector(".cookie-banner");
    cookie_banner.classList.remove("active");
    cookie_banner.classList.add("hidden");
  }

  // Function to show the cookie consent banner
  function showCookieConsent() {
    const cookie_banner = document.querySelector(".cookie-banner");
    cookie_banner.classList.add("active");
  }

  // Function to handle accept button click
  function acceptCookies() {
    localStorage.setItem('cookieConsent', 'true');
    handleCookieConsent();
    hideCookieConsent();
  }

  // Function to handle refuse button click
  function refuseCookies() {
    localStorage.setItem('cookieConsent', 'false');
    hideCookieConsent();
  }

  // Attach event listeners to buttons
  document.getElementById('acceptButton').addEventListener('click', acceptCookies);
  document.getElementById('refuseButton').addEventListener('click', refuseCookies);
// });