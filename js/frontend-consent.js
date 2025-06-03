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
        if (!gtmId) {
            console.log('OpenConsent: GTM ID not provided. GTM will not be loaded.');
            return;
        }
        console.log('OpenConsent: Loading GTM with ID:', gtmId);

        // GTM Head Script
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',gtmId);

        // GTM Noscript (for body) - create and prepend to body
        var noscriptTag = document.createElement('noscript');
        var iframeTag = document.createElement('iframe');
        iframeTag.src = 'https://www.googletagmanager.com/ns.html?id=' + gtmId;
        iframeTag.height = '0';
        iframeTag.width = '0';
        iframeTag.style.display = 'none';
        iframeTag.style.visibility = 'hidden';
        noscriptTag.appendChild(iframeTag);
        document.body.insertBefore(noscriptTag, document.body.firstChild);
    }

    // --- Main Consent Logic ---
    var banner = $('#oc-cookie-banner');
    var acceptBtn = $('#oc-accept-btn');
    var declineBtn = $('#oc-decline-btn');

    // Get settings passed from PHP
    // oc_js_vars is defined by wp_localize_script in openconsent.php
    var settings = window.oc_js_vars || {};
    var cookieName = settings.cookie_name || 'openconsent_status';
    var cookieDuration = parseInt(settings.cookie_duration_days, 10) || 365;
    var gtmId = settings.gtm_id || null;

    var consentCookie = getCookie(cookieName);

    if (consentCookie === 'granted') {
        // Consent already granted
        console.log('OpenConsent: Consent previously granted.');
        // Ensure GTM is loaded if consent was granted on a previous page/session
        // The default consent state is already set by PHP on every page load.
        // We update it here again to be sure, then load GTM if needed.
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'granted',
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'analytics_storage': 'granted'
            });
        }
        loadGtmScript(gtmId); // Load GTM if ID exists and consent is granted
        banner.hide(); // Should already be hidden or not even in DOM if handled by PHP
    } else if (consentCookie === 'denied') {
        // Consent previously denied
        console.log('OpenConsent: Consent previously denied.');
        // Default consent is 'denied', so no GTM load, no specific gtag update needed here unless changing defaults
         if (typeof gtag === 'function') {
            gtag('consent', 'update', { // Ensure it's still denied
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied'
            });
        }
        banner.hide();
    } else {
        // No consent cookie found, or cookie exists but isn't 'granted' or 'denied'
        // Display the banner
        console.log('OpenConsent: No consent cookie found or status unknown. Displaying banner.');
        if (banner.length) { // Check if banner element exists
            banner.show();
        } else {
            console.error('OpenConsent: Banner element #oc-cookie-banner not found.');
        }
    }

    acceptBtn.on('click', function() {
        console.log('OpenConsent: Accept clicked.');
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'granted',
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'analytics_storage': 'granted'
            });
            console.log('OpenConsent: GCM V2 updated to granted.');
        } else {
            console.warn('OpenConsent: gtag function not found. GCM V2 might not be configured correctly.');
        }
        setCookie(cookieName, 'granted', cookieDuration);
        banner.hide();
        // For Basic Consent Mode, GTM should load *after* consent.
        // Reloading the page is a simple way to ensure GTM picks up the new consent state.
        // The GTM script will then be loaded on the next page load by the logic above (consentCookie === 'granted').
        // Alternatively, call loadGtmScript(gtmId); here and avoid reload if your setup handles it.
        // For simplicity with Basic Mode, reload is often more robust.
        console.log('OpenConsent: Reloading page to apply consent.');
        window.location.reload();
    });

    declineBtn.on('click', function() {
        console.log('OpenConsent: Decline clicked.');
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied'
            });
            console.log('OpenConsent: GCM V2 updated to denied.');
        } else {
            console.warn('OpenConsent: gtag function not found. GCM V2 might not be configured correctly.');
        }
        setCookie(cookieName, 'denied', cookieDuration);
        banner.hide();
        // No need to reload here, as GTM shouldn't load.
    });

    // Populate banner text from localized variable (supports HTML)
    if (settings.banner_text && banner.length) {
        banner.find('.oc-banner-content').html(settings.banner_text);
    }
    // Populate button texts
    if (settings.accept_text && acceptBtn.length) {
        acceptBtn.text(settings.accept_text);
    }
    if (settings.decline_text && declineBtn.length) {
        declineBtn.text(settings.decline_text);
    }
});