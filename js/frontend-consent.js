/**
 * OpenConsent Frontend JavaScript
 * Version: 1.1.2
 *
 * - Cookie consent banner (opt-in, Accept / Decline)
 * - Google Consent Mode v2 updates (standalone GTM and Google Site Kit)
 * - WP Consent API integration (client-side, AJAX fallback)
 * - Removes Google cookies when consent is declined or withdrawn
 */

jQuery(document).ready(function($) {

    var settings = window.oc_js_vars || {};
    var debug = !!settings.debug;

    function log() {
        if (debug && window.console) console.log.apply(console, arguments);
    }

    function warn() {
        if (debug && window.console) console.warn.apply(console, arguments);
    }

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
            log('OpenConsent: Cookie "' + name + '" set successfully.');
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

    /**
     * Delete a cookie on the current host and all parent domains
     * (Google sets its cookies on the top-level domain).
     * @param {string} name - Cookie name
     */
    function deleteCookie(name) {
        var expired = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = expired;
        var parts = window.location.hostname.split('.');
        while (parts.length > 1) {
            var domain = parts.join('.');
            document.cookie = expired + '; domain=' + domain;
            document.cookie = expired + '; domain=.' + domain;
            parts.shift();
        }
    }

    /**
     * Remove Google Analytics / Ads cookies after consent was declined or withdrawn.
     * Also removes the gclid/wbraid cookies written by OpenConsent up to v1.1.1.
     */
    function removeTrackingCookies() {
        try {
            var pattern = /^(_ga$|_ga_|_gid$|_gat|_gcl_|_gac_|_dc_gtm_|gclid$|wbraid$)/;
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var name = ca[i].split('=')[0].trim();
                if (pattern.test(name)) deleteCookie(name);
            }
        } catch (e) {
            console.error('OpenConsent: Error removing tracking cookies:', e);
        }
    }

    // --- GTM Loader Function (for Standalone Mode only) ---
    /**
     * Load GTM script for standalone mode (when Google Site Kit is not active)
     * @param {string} gtmId - Google Tag Manager ID (GTM-XXXXX)
     */
    function loadGtmScript(gtmId) {
        if (!settings.should_load_own_gtm || !gtmId || window.ocGtmLoaded) {
            return;
        }

        try {
            log('OpenConsent Standalone: Loading GTM with ID:', gtmId);
            window.ocGtmLoaded = true;

            (function(w,d,s,l,i){
                w[l]=w[l]||[];
                w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});
                var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s);
                j.async=true;
                j.src='https://www.googletagmanager.com/gtm.js?id='+encodeURIComponent(i);
                f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer',gtmId);
        } catch (e) {
            console.error('OpenConsent: Error loading GTM:', e);
            window.ocGtmLoaded = false;
        }
    }

    // --- Main Consent Logic ---
    var banner = $('#oc-cookie-banner');
    var acceptBtn = $('#oc-accept-btn');
    var declineBtn = $('#oc-decline-btn');
    var cookieWidget = $('#oc-cookie-widget');

    var cookieName = settings.cookie_name || 'openconsent_status';
    var cookieDuration = parseInt(settings.cookie_duration_days, 10) || 180;
    var gtmId = settings.gtm_id || null;
    var enableWidget = settings.enable_minimized_widget;
    var ajaxUrl = settings.ajax_url;
    var ajaxNonce = settings.ajax_nonce;
    var consentCategories = settings.consent_categories || ['statistics', 'statistics-anonymous', 'marketing', 'preferences'];

    // WP Consent API: announce that this site uses opt-in consent.
    window.wp_consent_type = 'optin';
    try {
        document.dispatchEvent(new CustomEvent('wp_consent_type_defined'));
    } catch (e) { /* very old browsers */ }

    function showBanner(moveFocus) {
        if (banner.length) {
            banner.show();
            if (moveFocus) banner.trigger('focus');
        }
        if (cookieWidget.length) cookieWidget.hide();
    }

    function showWidget() {
        if (banner.length) banner.hide();
        if (enableWidget && cookieWidget.length) cookieWidget.show();
    }

    /**
     * Update the WP Consent API.
     * Uses the API's own JavaScript function when available (this also notifies
     * Site Kit and other listeners), otherwise falls back to the AJAX endpoint.
     * @param {string} status - Consent status ('granted' or 'denied')
     * @returns {object} jQuery promise
     */
    function updateWPConsentAPI(status) {
        var done = $.Deferred().resolve().promise();
        var value = status === 'granted' ? 'allow' : 'deny';

        if (typeof window.wp_set_consent === 'function') {
            try {
                window.wp_set_consent('functional', 'allow');
                for (var i = 0; i < consentCategories.length; i++) {
                    window.wp_set_consent(consentCategories[i], value);
                }
                log('OpenConsent: WP Consent API updated to:', value);
            } catch (e) {
                console.error('OpenConsent: Error updating WP Consent API:', e);
            }
            return done;
        }

        if (!settings.wp_consent_api_active || !ajaxUrl || !ajaxNonce) {
            return done;
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
                log('OpenConsent: WP Consent API updated successfully.', response.data);
            } else {
                warn('OpenConsent: WP Consent API returned error:', response.data);
            }
        }).fail(function(xhr, textStatus, errorThrown) {
            warn('OpenConsent: WP Consent API AJAX request failed.', textStatus, errorThrown);
        });
    }

    /**
     * Keep the WP Consent API cookies in line with the stored choice
     * (they can expire earlier than the OpenConsent cookie).
     * @param {string} status - 'granted' or 'denied'
     */
    function syncWPConsentAPI(status) {
        if (typeof window.wp_set_consent !== 'function' || typeof window.wp_has_consent !== 'function') return;
        try {
            var expected = status === 'granted';
            for (var i = 0; i < consentCategories.length; i++) {
                if (window.wp_has_consent(consentCategories[i]) !== expected) {
                    updateWPConsentAPI(status);
                    return;
                }
            }
        } catch (e) {
            console.error('OpenConsent: Error syncing WP Consent API:', e);
        }
    }

    /**
     * Update Google Consent Mode v2
     * @param {string} status - 'granted' or 'denied'
     */
    function updateGoogleConsentMode(status) {
        if (typeof gtag !== 'function') {
            warn('OpenConsent: gtag function not available. Google Analytics/Ads may not work.');
            return;
        }

        try {
            var value = status === 'granted' ? 'granted' : 'denied';
            log('OpenConsent: Updating Google Consent Mode to ' + value.toUpperCase());
            gtag('consent', 'update', {
                'ad_storage': value,
                'ad_user_data': value,
                'ad_personalization': value,
                'analytics_storage': value,
                'functionality_storage': value,
                'personalization_storage': value
            });
        } catch (e) {
            console.error('OpenConsent: Error updating Google Consent Mode:', e);
        }
    }

    // --- Initial Page Load Check ---
    var consentCookie = getCookie(cookieName);
    if (consentCookie === 'granted') {
        log('OpenConsent: Found existing consent cookie - GRANTED');
        // In standalone mode the inline head script has already sent the update and loaded GTM.
        if (!window.ocGtmLoaded) {
            updateGoogleConsentMode('granted');
            loadGtmScript(gtmId);
        }
        syncWPConsentAPI('granted');
        showWidget();
    } else if (consentCookie === 'denied') {
        log('OpenConsent: Found existing consent cookie - DENIED');
        updateGoogleConsentMode('denied');
        syncWPConsentAPI('denied');
        showWidget();
    } else {
        log('OpenConsent: No existing consent cookie - showing banner');
        showBanner(false);
    }

    // --- Button Click Handlers ---
    function handleChoice(newStatus) {
        var currentStatus = getCookie(cookieName);

        log('OpenConsent: Choice made. Current status:', currentStatus, 'New status:', newStatus);

        updateGoogleConsentMode(newStatus);
        setCookie(cookieName, newStatus, cookieDuration);

        if (newStatus === 'denied') {
            removeTrackingCookies();
        }

        updateWPConsentAPI(newStatus).always(function() {
            // Reload page only if consent status changed
            if (currentStatus !== newStatus) {
                log('OpenConsent: Consent status changed - reloading page');
                setTimeout(function() {
                    window.location.reload();
                }, 100);
            } else {
                showWidget();
                if (enableWidget) cookieWidget.trigger('focus');
            }
        });
    }

    acceptBtn.on('click', function(e) {
        e.preventDefault();
        handleChoice('granted');
    });

    declineBtn.on('click', function(e) {
        e.preventDefault();
        handleChoice('denied');
    });

    // The widget, the [openconsent_settings_link] shortcode and any element with the
    // class "oc-open-settings" or a link to "#openconsent" re-open the banner.
    $(document).on('click', '#oc-cookie-widget, .oc-open-settings, a[href="#openconsent"]', function(e) {
        e.preventDefault();
        showBanner(true);
    });
});
