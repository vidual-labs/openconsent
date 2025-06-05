jQuery(document).ready(function($) {
    // --- Cookie Helper Functions ---
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/; SameSite=Lax";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    // --- GTM Loader Function ---
    function loadGtmScript(gtmId) {
        if (!gtmId || window.gtmScriptLoaded) { // Check if GTM ID exists and script not already loaded
            if (!gtmId) console.log('OpenConsent: GTM ID not provided.');
            return;
        }
        console.log('OpenConsent: Loading GTM with ID:', gtmId);
        window.gtmScriptLoaded = true; // Flag to prevent multiple loads

        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',gtmId);

        var noscriptTag = document.createElement('noscript');
        var iframeTag = document.createElement('iframe');
        iframeTag.src = 'https://www.googletagmanager.com/ns.html?id=' + gtmId;
        iframeTag.height = '0'; iframeTag.width = '0';
        iframeTag.style.display = 'none'; iframeTag.style.visibility = 'hidden';
        noscriptTag.appendChild(iframeTag);
        document.body.insertBefore(noscriptTag, document.body.firstChild);
    }

    // --- Main Consent Logic ---
    var banner = $('#oc-cookie-banner');
    var acceptBtn = $('#oc-accept-btn');
    var declineBtn = $('#oc-decline-btn');
    var cookieWidget = $('#oc-cookie-widget'); // New widget element

    var settings = window.oc_js_vars || {};
    var cookieName = settings.cookie_name || 'openconsent_status';
    var cookieDuration = parseInt(settings.cookie_duration_days, 10) || 365;
    var gtmId = settings.gtm_id || null;
    var minimizedText = settings.minimized_text || 'Cookie Settings';

    // Populate initial texts
    if (settings.banner_text && banner.length) {
        banner.find('.oc-banner-content').html(settings.banner_text);
    }
    if (settings.accept_text && acceptBtn.length) {
        acceptBtn.text(settings.accept_text);
    }
    if (settings.decline_text && declineBtn.length) {
        declineBtn.text(settings.decline_text);
    }
    if (cookieWidget.length) {
        cookieWidget.text(minimizedText);
    }

    function showBanner() {
        if (banner.length) banner.show();
        if (cookieWidget.length) cookieWidget.hide();
    }

    function showWidget() {
        if (banner.length) banner.hide();
        if (cookieWidget.length) cookieWidget.show();
    }

    function hideAll() {
        if (banner.length) banner.hide();
        if (cookieWidget.length) cookieWidget.hide();
    }

    var consentCookie = getCookie(cookieName);

    if (consentCookie === 'granted') {
        console.log('OpenConsent: Consent previously granted.');
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'granted', 'ad_user_data': 'granted',
                'ad_personalization': 'granted', 'analytics_storage': 'granted'
            });
        }
        loadGtmScript(gtmId);
        showWidget(); // Show widget instead of hiding everything
    } else if (consentCookie === 'denied') {
        console.log('OpenConsent: Consent previously denied.');
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'denied', 'ad_user_data': 'denied',
                'ad_personalization': 'denied', 'analytics_storage': 'denied'
            });
        }
        showWidget(); // Show widget instead of hiding everything
    } else {
        console.log('OpenConsent: No consent status found. Displaying banner.');
        showBanner();
    }

    acceptBtn.on('click', function() {
        console.log('OpenConsent: Accept clicked.');
        var anUpdateOccurred = getCookie(cookieName) !== 'granted'; // Check if state is actually changing to granted

        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'granted', 'ad_user_data': 'granted',
                'ad_personalization': 'granted', 'analytics_storage': 'granted'
            });
            console.log('OpenConsent: GCM V2 updated to granted.');
        }
        setCookie(cookieName, 'granted', cookieDuration);
        showWidget();

        // Reload only if GTM hasn't been loaded yet (e.g. first time accepting)
        // or if the consent state genuinely changed to 'granted' from something else
        if (!window.gtmScriptLoaded || anUpdateOccurred) {
            console.log('OpenConsent: Reloading page to apply consent and load GTM.');
            window.location.reload();
        } else {
            // If GTM is already loaded and consent was already 'granted', no need to reload.
            // Just ensure widget is shown.
            loadGtmScript(gtmId); // Call again to ensure it runs if it hasn't due to some edge case
        }
    });

    declineBtn.on('click', function() {
        console.log('OpenConsent: Decline clicked.');
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'denied', 'ad_user_data': 'denied',
                'ad_personalization': 'denied', 'analytics_storage': 'denied'
            });
            console.log('OpenConsent: GCM V2 updated to denied.');
        }
        setCookie(cookieName, 'denied', cookieDuration);
        showWidget();
        // No reload needed for decline
    });

    cookieWidget.on('click', function() {
        console.log('OpenConsent: Cookie widget clicked.');
        showBanner();
    });
});