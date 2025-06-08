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

    // --- GTM Loader Function (for Standalone Mode only) ---
    function loadGtmScript(gtmId) {
        if (!settings.should_load_own_gtm || !gtmId || window.gtmScriptLoaded) {
            if (settings.should_load_own_gtm && !gtmId) console.log('OpenConsent Standalone: GTM ID not provided.');
            return;
        }
        console.log('OpenConsent Standalone: Loading GTM with ID:', gtmId);
        window.gtmScriptLoaded = true;

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
    var cookieWidget = $('#oc-cookie-widget');

    var settings = window.oc_js_vars || {};
    var cookieName = settings.cookie_name || 'openconsent_status';
    var cookieDuration = parseInt(settings.cookie_duration_days, 10) || 365;
    var gtmId = settings.gtm_id || null;
    var minimizedText = settings.minimized_text || 'Cookie Settings';
    var enableWidget = settings.enable_minimized_widget;
    var ajaxUrl = settings.ajax_url;
    var ajaxNonce = settings.ajax_nonce;

    // Populate texts from settings
    if (banner.length) banner.find('.oc-banner-content').html(settings.banner_text);
    if (acceptBtn.length) acceptBtn.text(settings.accept_text);
    if (declineBtn.length) declineBtn.text(settings.decline_text);
    if (cookieWidget.length) cookieWidget.text(minimizedText);

    function showBanner() {
        if (banner.length) banner.show();
        if (cookieWidget.length) cookieWidget.hide();
    }

    function showWidget() {
        if (!enableWidget) return;
        if (banner.length) banner.hide();
        if (cookieWidget.length) cookieWidget.show();
    }

    function updateWPConsentAPI(status) {
        if (!ajaxUrl) { return $.Deferred().resolve().promise(); }
        return $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: 'openconsent_update_consent', nonce: ajaxNonce, status: status }
        }).done(function(response) {
            console.log('OpenConsent: WP Consent API AJAX call successful.', response);
        }).fail(function(xhr) {
            console.error('OpenConsent: WP Consent API AJAX request failed.', xhr.statusText);
        });
    }

    // --- Initial Page Load Check ---
    var consentCookie = getCookie(cookieName);
    if (consentCookie === 'granted') {
        // **KEY CHANGE**: Always send the 'update' command on page load if consent was granted.
        // This overrides Site Kit's safe 'denied' default for the current page view.
        if (typeof gtag === 'function') {
            console.log("OpenConsent: Consent is 'granted'. Updating GCM state to granted.");
            gtag('consent', 'update', {'ad_storage': 'granted', 'ad_user_data': 'granted', 'ad_personalization': 'granted', 'analytics_storage': 'granted'});
        }

        // In standalone mode, we must also load our own GTM script
        if (settings.should_load_own_gtm) {
            loadGtmScript(gtmId);
        }
        showWidget();
    } else if (consentCookie === 'denied') {
        showWidget();
    } else {
        showBanner();
    }

    // --- Button Click Handlers ---
    acceptBtn.on('click', function() {
        var newStatus = 'granted';
        var anUpdateOccurred = getCookie(cookieName) !== newStatus;

        // The gtag update on click is still useful for single-page applications,
        // but the reload is what makes it work for Site Kit.
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {'ad_storage': 'granted', 'ad_user_data': 'granted', 'ad_personalization': 'granted', 'analytics_storage': 'granted'});
        }
        
        setCookie(cookieName, newStatus, cookieDuration);
        
        updateWPConsentAPI(newStatus).always(function() {
            if (anUpdateOccurred) {
                window.location.reload();
            } else {
                showWidget();
            }
        });
    });

    declineBtn.on('click', function() {
        var newStatus = 'denied';
        var anUpdateOccurred = getCookie(cookieName) !== newStatus;
        
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {'ad_storage': 'denied', 'ad_user_data': 'denied', 'ad_personalization': 'denied', 'analytics_storage': 'denied'});
        }

        setCookie(cookieName, newStatus, cookieDuration);

        updateWPConsentAPI(newStatus).always(function() {
            if (anUpdateOccurred) {
                 window.location.reload();
            } else {
                showWidget();
            }
        });
    });

    cookieWidget.on('click', function() {
        showBanner();
    });
});