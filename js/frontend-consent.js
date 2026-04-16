/**
 * OpenConsent Frontend JavaScript
 * Version: 1.1.0
 *
 * Features:
 * - GDPR/CCPA compliant cookie consent banner
 * - Google Consent Mode API integration (for Site Kit and standalone GTM)
 * - WordPress Consent API support
 * - Conversion tracking with GCLID/WBRAID
 * - Secure cookie handling with SameSite attribute
 * - Accessible UI with keyboard navigation support
 * - Detailed console logging for debugging
 *
 * Refactoring improvements (v1.1.0):
 * - Better error handling throughout
 * - Improved Google Consent Mode integration
 * - Support for conversion tracking parameters
 * - More robust AJAX requests with timeout
 * - Better logging for troubleshooting
 * - WCAG 2.1 accessibility compliance
 * - Support for prefers-reduced-motion
 */

jQuery(document).ready(function($) {

    // --- Cookie Helper Functions ---
    /**
     * Set a cookie with secure SameSite attribute
     * @param {string} name - Cookie name
     * @param {string} value - Cookie value
     * @param {number} days - Days until expiration
     */
    function setCookie(name, value, days) {
        try {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            // Use Secure flag for HTTPS sites
            var secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = name + "=" + encodeURIComponent(value || "") + expires + "; path=/; SameSite=Lax" + secure;
            console.log('OpenConsent: Cookie "' + name + '" set successfully.');
        } catch (e) {
            console.error('OpenConsent: Error setting cookie:', e);
        }
    }

    /**
     * Get a cookie value by name
     * @param {string} name - Cookie name
     * @returns {string|null} Cookie value or null
     */
    function getCookie(name) {
        try {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i = 0; i < ca.length; i++) {
                var c = ca[i].trim();
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length));
            }
            return null;
        } catch (e) {
            console.error('OpenConsent: Error reading cookie:', e);
            return null;
        }
    }

    // --- GTM Loader Function (for Standalone Mode only) ---
    /**
     * Load GTM script for standalone mode (when Google Site Kit is not active)
     * @param {string} gtmId - Google Tag Manager ID (GTM-XXXXX)
     */
    function loadGtmScript(gtmId) {
        if (!settings.should_load_own_gtm || !gtmId || window.gtmScriptLoaded) {
            if (settings.should_load_own_gtm && !gtmId) {
                console.warn('OpenConsent Standalone: GTM ID not provided in settings.');
            }
            return;
        }

        try {
            console.log('OpenConsent Standalone: Loading GTM with ID:', gtmId);
            window.gtmScriptLoaded = true;

            // Initialize data layer
            (function(w,d,s,l,i){
                w[l]=w[l]||[];
                w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});
                var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),
                dl=l!='dataLayer'?'&l='+l:'';
                j.async=true;
                j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
                f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer',gtmId);

            // Insert noscript fallback
            var noscriptTag = document.createElement('noscript');
            var iframeTag = document.createElement('iframe');
            iframeTag.src = 'https://www.googletagmanager.com/ns.html?id=' + gtmId;
            iframeTag.height = '0';
            iframeTag.width = '0';
            iframeTag.style.display = 'none';
            iframeTag.style.visibility = 'hidden';
            noscriptTag.appendChild(iframeTag);
            document.body.insertBefore(noscriptTag, document.body.firstChild);
        } catch (e) {
            console.error('OpenConsent: Error loading GTM:', e);
            window.gtmScriptLoaded = false;
        }
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

    /**
     * Update WordPress Consent API via AJAX
     * @param {string} status - Consent status ('granted' or 'denied')
     * @returns {object} jQuery promise
     */
    function updateWPConsentAPI(status) {
        if (!ajaxUrl) {
            console.warn('OpenConsent: AJAX URL not configured.');
            return $.Deferred().resolve().promise();
        }

        if (!ajaxNonce) {
            console.warn('OpenConsent: Nonce not available.');
            return $.Deferred().resolve().promise();
        }

        return $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            timeout: 5000,
            data: {
                action: 'openconsent_update_consent',
                nonce: ajaxNonce,
                status: status
            }
        }).done(function(response) {
            if (response.success) {
                console.log('OpenConsent: WP Consent API updated successfully.', response.data);
            } else {
                console.warn('OpenConsent: WP Consent API returned error:', response.data);
            }
        }).fail(function(xhr, textStatus, errorThrown) {
            console.error('OpenConsent: WP Consent API AJAX request failed.', textStatus, errorThrown);
        });
    }

    // --- Helper function for updating consent in Google Consent Mode ---
    /**
     * Update Google Consent Mode with appropriate status
     * Supports both standard consent and conversion tracking
     * @param {string} status - 'granted' or 'denied'
     */
    function updateGoogleConsentMode(status) {
        if (typeof gtag !== 'function') {
            console.warn('OpenConsent: gtag function not available. Google Analytics/Ads may not work.');
            return;
        }

        try {
            var consentData = {
                'ad_storage': status === 'granted' ? 'granted' : 'denied',
                'ad_user_data': status === 'granted' ? 'granted' : 'denied',
                'ad_personalization': status === 'granted' ? 'granted' : 'denied',
                'analytics_storage': status === 'granted' ? 'granted' : 'denied'
            };

            // Add conversion tracking support for Google Ads
            if (status === 'granted') {
                consentData['conversion_linker'] = 'granted';
                console.log('OpenConsent: Updating Google Consent Mode to GRANTED (with conversion tracking)');
            } else {
                console.log('OpenConsent: Updating Google Consent Mode to DENIED');
            }

            gtag('consent', 'update', consentData);

            // Handle GCLID and WBRAID parameters for conversion tracking
            if (status === 'granted') {
                handleConversionTrackingParameters();
            }
        } catch (e) {
            console.error('OpenConsent: Error updating Google Consent Mode:', e);
        }
    }

    /**
     * Handle Google Ads conversion tracking parameters
     * Allows Google Ads to track conversions properly when consent is granted
     */
    function handleConversionTrackingParameters() {
        try {
            // Check for GCLID (Google Click ID) in URL
            var gclid = getUrlParameter('gclid');
            if (gclid) {
                console.log('OpenConsent: GCLID detected - conversion tracking enabled');
                setCookie('gclid', gclid, 90);
            }

            // Check for WBRAID (Web Attribution ID) in URL
            var wbraid = getUrlParameter('wbraid');
            if (wbraid) {
                console.log('OpenConsent: WBRAID detected - conversion tracking enabled');
                setCookie('wbraid', wbraid, 90);
            }
        } catch (e) {
            console.error('OpenConsent: Error handling conversion tracking parameters:', e);
        }
    }

    /**
     * Get URL parameter value
     * @param {string} name - Parameter name
     * @returns {string|null} Parameter value or null
     */
    function getUrlParameter(name) {
        try {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        } catch (e) {
            // Fallback for older browsers without URLSearchParams
            var regexS = "[\\?&]" + name + "=([^&#]*)";
            var regex = new RegExp(regexS);
            var results = regex.exec(window.location.href);
            if (results === null) return null;
            return decodeURIComponent(results[1].replace(/\+/g, " "));
        }
    }

    // --- Initial Page Load Check ---
    var consentCookie = getCookie(cookieName);
    if (consentCookie === 'granted') {
        console.log('OpenConsent: Found existing consent cookie - GRANTED');
        updateGoogleConsentMode('granted');

        // In standalone mode, load GTM script
        if (settings.should_load_own_gtm) {
            loadGtmScript(gtmId);
        }
        showWidget();
    } else if (consentCookie === 'denied') {
        console.log('OpenConsent: Found existing consent cookie - DENIED');
        updateGoogleConsentMode('denied');
        showWidget();
    } else {
        console.log('OpenConsent: No existing consent cookie - showing banner');
        showBanner();
    }

    // --- Button Click Handlers ---
    acceptBtn.on('click', function(e) {
        e.preventDefault();
        var currentStatus = getCookie(cookieName);
        var newStatus = 'granted';

        console.log('OpenConsent: Accept button clicked. Current status:', currentStatus, 'New status:', newStatus);

        // Update Google Consent Mode
        updateGoogleConsentMode(newStatus);

        // Set cookie
        setCookie(cookieName, newStatus, cookieDuration);

        // Update WordPress Consent API
        updateWPConsentAPI(newStatus).always(function() {
            // Reload page only if consent status changed
            if (currentStatus !== newStatus) {
                console.log('OpenConsent: Consent status changed - reloading page');
                setTimeout(function() {
                    window.location.reload();
                }, 100);
            } else {
                console.log('OpenConsent: Consent status unchanged - showing widget');
                showWidget();
            }
        });
    });

    declineBtn.on('click', function(e) {
        e.preventDefault();
        var currentStatus = getCookie(cookieName);
        var newStatus = 'denied';

        console.log('OpenConsent: Decline button clicked. Current status:', currentStatus, 'New status:', newStatus);

        // Update Google Consent Mode
        updateGoogleConsentMode(newStatus);

        // Set cookie
        setCookie(cookieName, newStatus, cookieDuration);

        // Update WordPress Consent API
        updateWPConsentAPI(newStatus).always(function() {
            // Reload page only if consent status changed
            if (currentStatus !== newStatus) {
                console.log('OpenConsent: Consent status changed - reloading page');
                setTimeout(function() {
                    window.location.reload();
                }, 100);
            } else {
                console.log('OpenConsent: Consent status unchanged - showing widget');
                showWidget();
            }
        });
    });

    cookieWidget.on('click', function(e) {
        e.preventDefault();
        console.log('OpenConsent: Cookie widget clicked - showing banner');
        showBanner();
    });
});