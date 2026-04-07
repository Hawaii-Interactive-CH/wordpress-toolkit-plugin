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
    var additionalData = localStorage.getItem('cookieConsentAdditionalData');
    var rawAllowedHosts = localStorage.getItem('cookieConsentAllowedScriptHosts');
    var allowedHosts = [];

    if (rawAllowedHosts) {
      try {
        allowedHosts = JSON.parse(rawAllowedHosts);
      } catch (error) {
        allowedHosts = [];
      }
    }

    if (additionalData) {
      var parser = new DOMParser();
      var parsed = parser.parseFromString(additionalData, 'text/html');
      var scripts = parsed.querySelectorAll('script[src]');

      scripts.forEach(function (script) {
        var src = script.getAttribute('src');

        if (!src) {
          return;
        }

        var url;
        try {
          url = new URL(src, window.location.origin);
        } catch (error) {
          return;
        }

        var isHttp = url.protocol === 'https:' || url.protocol === 'http:';
        var isAllowedHost = allowedHosts.indexOf(url.hostname.toLowerCase()) !== -1;

        if (!isHttp || !isAllowedHost) {
          return;
        }

        var injectedScript = document.createElement('script');
        injectedScript.src = url.href;

        if (script.hasAttribute('async')) {
          injectedScript.async = true;
        }
        if (script.hasAttribute('defer')) {
          injectedScript.defer = true;
        }
        if (script.hasAttribute('type')) {
          injectedScript.type = script.getAttribute('type');
        }
        if (script.hasAttribute('id')) {
          injectedScript.id = script.getAttribute('id');
        }

        document.head.appendChild(injectedScript);
      });
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