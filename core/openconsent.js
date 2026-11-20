/**
 * OpenConsent banner script (shared by WordPress, Joomla and the universal snippet)
 *
 * - Renders the cookie consent banner (opt-in, Accept / Decline) and the re-open widget
 * - Google Consent Mode v2 updates; loads GTM after consent in standalone mode
 * - Removes Google cookies when consent is declined or withdrawn
 * - No dependencies. Reads window.openconsentConfig (see openconsent-head.js)
 *
 * Events on document, for integrations (e.g. the WP Consent API bridge):
 * - "openconsent:ready"  detail: { status }  stored choice on page load ('granted' | 'denied' | null)
 * - "openconsent:choice" detail: { status, previous, waitUntil(promise) }
 *   a new choice; the page reloads after the promises passed to waitUntil() settle (max. 5 s)
 */
(function (w, d) {
    'use strict';

    var DEFAULTS = {
        banner_text: '<p>We use cookies and similar technologies for analytics and marketing, including services provided by Google. ' +
            'They are only activated if you click “Accept”. You can decline, and you can change or withdraw your choice at any time. ' +
            '<a href="https://business.safety.google/privacy/" target="_blank" rel="noopener noreferrer">How Google uses data</a></p>',
        accept_text: 'Accept',
        decline_text: 'Decline',
        minimized_text: 'Cookie Settings',
        banner_label: 'Cookie consent',
        enable_minimized_widget: true,
        cookie_name: 'openconsent_status',
        cookie_duration_days: 180,
        banner_position: 'bottom-full',
        banner_bg_color: '#222222',
        banner_text_color: '#ffffff',
        link_color: '#00a0d2',
        accept_button_bg_color: '#0073aa',
        accept_button_text_color: '#ffffff',
        decline_button_bg_color: '#757575',
        decline_button_text_color: '#ffffff',
        widget_bg_color: '#333333',
        widget_text_color: '#ffffff'
    };
    var POSITIONS = ['bottom-full', 'bottom-left', 'bottom-center', 'bottom-right', 'top-full', 'top-left',
        'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right'];
    var COLOR_PATTERN = /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i;
    var GTM_ID_PATTERN = /^GTM-[A-Z0-9]{4,12}$/;
    var RELOAD_TIMEOUT_MS = 5000;

    var cfg = w.openconsentConfig || {};
    var debug = !!cfg.debug;

    function log() {
        if (debug && w.console) console.log.apply(console, arguments);
    }

    function warn() {
        if (debug && w.console) console.warn.apply(console, arguments);
    }

    /** Configured value, or the default when it is missing or empty. */
    function setting(key) {
        var value = cfg[key];
        if (value === undefined || value === null || (typeof value === 'string' && value.trim() === '')) {
            return DEFAULTS[key];
        }
        return value;
    }

    function color(key) {
        var value = setting(key);
        return COLOR_PATTERN.test(value) ? value : DEFAULTS[key];
    }

    // --- Cookies ---
    function setCookie(name, value, days) {
        try {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            var secure = w.location.protocol === 'https:' ? '; Secure' : '';
            d.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + date.toUTCString() + '; path=/; SameSite=Lax' + secure;
            log('OpenConsent: Cookie "' + name + '" set to ' + value);
        } catch (e) {
            console.error('OpenConsent: Error setting cookie:', e);
        }
    }

    function getCookie(name) {
        try {
            var nameEQ = name + '=';
            var ca = d.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i].trim();
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length));
            }
        } catch (e) {
            console.error('OpenConsent: Error reading cookie:', e);
        }
        return null;
    }

    /** Delete a cookie on the current host and all parent domains (Google sets its cookies on the top-level domain). */
    function deleteCookie(name) {
        var expired = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        d.cookie = expired;
        var parts = w.location.hostname.split('.');
        while (parts.length > 1) {
            var domain = parts.join('.');
            d.cookie = expired + '; domain=' + domain;
            d.cookie = expired + '; domain=.' + domain;
            parts.shift();
        }
    }

    /**
     * Remove Google Analytics / Ads cookies after consent was declined or withdrawn.
     * Also removes the gclid/wbraid cookies written by OpenConsent for WordPress up to v1.1.1.
     */
    function removeTrackingCookies() {
        try {
            var pattern = /^(_ga$|_ga_|_gid$|_gat|_gcl_|_gac_|_dc_gtm_|gclid$|wbraid$)/;
            var ca = d.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var name = ca[i].split('=')[0].trim();
                if (pattern.test(name)) deleteCookie(name);
            }
        } catch (e) {
            console.error('OpenConsent: Error removing tracking cookies:', e);
        }
    }

    // --- Google ---
    function loadGtmScript(gtmId) {
        if (!cfg.should_load_own_gtm || !gtmId || !GTM_ID_PATTERN.test(gtmId) || w.ocGtmLoaded) return;
        try {
            log('OpenConsent: Loading GTM with ID:', gtmId);
            w.ocGtmLoaded = true;
            w.dataLayer = w.dataLayer || [];
            w.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var f = d.getElementsByTagName('script')[0];
            var j = d.createElement('script');
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtmId);
            f.parentNode.insertBefore(j, f);
        } catch (e) {
            console.error('OpenConsent: Error loading GTM:', e);
            w.ocGtmLoaded = false;
        }
    }

    function updateGoogleConsentMode(status) {
        if (typeof w.gtag !== 'function') {
            warn('OpenConsent: gtag function not available. Google Analytics/Ads may not work.');
            return;
        }
        try {
            var value = status === 'granted' ? 'granted' : 'denied';
            log('OpenConsent: Updating Google Consent Mode to ' + value.toUpperCase());
            w.gtag('consent', 'update', {
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

    // --- Events ---
    function emit(name, detail) {
        try {
            d.dispatchEvent(new CustomEvent(name, { detail: detail }));
        } catch (e) {
            console.error('OpenConsent: Error in a "' + name + '" listener:', e);
        }
    }

    // --- Banner markup ---
    function el(tag, attrs, styles) {
        var node = d.createElement(tag);
        for (var key in attrs) {
            if (Object.prototype.hasOwnProperty.call(attrs, key)) node.setAttribute(key, attrs[key]);
        }
        for (var prop in styles) {
            if (Object.prototype.hasOwnProperty.call(styles, prop)) node.style[prop] = styles[prop];
        }
        return node;
    }

    /** Creates banner and widget unless the page already contains them. */
    function renderElements() {
        if (d.getElementById('oc-cookie-banner')) return;

        var position = setting('banner_position');
        if (POSITIONS.indexOf(position) === -1) position = DEFAULTS.banner_position;

        var banner = el('div', {
            id: 'oc-cookie-banner',
            role: 'dialog',
            'aria-modal': 'false',
            'aria-label': setting('banner_label'),
            tabindex: '-1',
            'class': 'oc-banner-position-' + position
        }, { display: 'none', backgroundColor: color('banner_bg_color'), color: color('banner_text_color') });

        var content = el('div', { 'class': 'oc-banner-content' });
        // Site owner's own text (sanitized by the CMS integration where there is one).
        content.innerHTML = setting('banner_text');

        var actions = el('div', { 'class': 'oc-banner-actions' });
        var accept = el('button', { type: 'button', id: 'oc-accept-btn' },
            { backgroundColor: color('accept_button_bg_color'), color: color('accept_button_text_color') });
        accept.textContent = setting('accept_text');
        var decline = el('button', { type: 'button', id: 'oc-decline-btn' },
            { backgroundColor: color('decline_button_bg_color'), color: color('decline_button_text_color'), marginLeft: '10px' });
        decline.textContent = setting('decline_text');
        actions.appendChild(accept);
        actions.appendChild(decline);
        banner.appendChild(content);
        banner.appendChild(actions);

        var widget = el('button', { type: 'button', id: 'oc-cookie-widget' },
            { display: 'none', backgroundColor: color('widget_bg_color'), color: color('widget_text_color') });
        widget.textContent = setting('minimized_text');

        var style = d.createElement('style');
        style.textContent =
            '#oc-cookie-banner .oc-banner-content a, #oc-cookie-banner .oc-banner-content a:visited { color: ' + color('link_color') + ' !important; }' +
            '#oc-cookie-banner .oc-banner-content a:hover, #oc-cookie-banner .oc-banner-content a:focus { text-decoration: underline; }';

        d.body.appendChild(banner);
        d.body.appendChild(widget);
        d.body.appendChild(style);
    }

    // --- Main logic ---
    function init() {
        renderElements();

        var banner = d.getElementById('oc-cookie-banner');
        var acceptBtn = d.getElementById('oc-accept-btn');
        var declineBtn = d.getElementById('oc-decline-btn');
        var widget = d.getElementById('oc-cookie-widget');

        var cookieName = setting('cookie_name');
        var cookieDuration = parseInt(setting('cookie_duration_days'), 10);
        if (!(cookieDuration >= 1 && cookieDuration <= 390)) cookieDuration = DEFAULTS.cookie_duration_days;
        var enableWidget = setting('enable_minimized_widget') !== false && setting('enable_minimized_widget') !== '0';

        function showBanner(moveFocus) {
            if (banner) {
                banner.style.display = '';
                if (moveFocus) banner.focus();
            }
            if (widget) widget.style.display = 'none';
        }

        function showWidget() {
            if (banner) banner.style.display = 'none';
            if (widget) widget.style.display = enableWidget ? '' : 'none';
        }

        var consent = getCookie(cookieName);
        if (consent === 'granted') {
            log('OpenConsent: Found existing consent cookie - GRANTED');
            // In standalone mode the head script has already sent the update and loaded GTM.
            if (!w.ocGtmLoaded) {
                updateGoogleConsentMode('granted');
                loadGtmScript(cfg.gtm_id);
            }
            showWidget();
        } else if (consent === 'denied') {
            log('OpenConsent: Found existing consent cookie - DENIED');
            updateGoogleConsentMode('denied');
            showWidget();
        } else {
            consent = null;
            log('OpenConsent: No existing consent cookie - showing banner');
            showBanner(false);
        }
        emit('openconsent:ready', { status: consent });

        function handleChoice(newStatus) {
            var currentStatus = getCookie(cookieName);
            log('OpenConsent: Choice made. Current status:', currentStatus, 'New status:', newStatus);

            updateGoogleConsentMode(newStatus);
            setCookie(cookieName, newStatus, cookieDuration);
            if (newStatus === 'denied') removeTrackingCookies();

            var pending = [];
            emit('openconsent:choice', {
                status: newStatus,
                previous: currentStatus,
                waitUntil: function (promise) {
                    if (promise && typeof promise.then === 'function') pending.push(promise);
                }
            });

            function finish() {
                if (currentStatus !== newStatus) {
                    // Reload so that tags run (or stop running) with the new consent state.
                    log('OpenConsent: Consent status changed - reloading page');
                    setTimeout(function () { w.location.reload(); }, 100);
                } else {
                    showWidget();
                    if (enableWidget && widget) widget.focus();
                }
            }

            if (!pending.length || typeof Promise === 'undefined') {
                finish();
                return;
            }
            var settled = pending.map(function (p) {
                return p.then(null, function (e) { warn('OpenConsent: Integration failed:', e); });
            });
            var timeout = new Promise(function (resolve) { setTimeout(resolve, RELOAD_TIMEOUT_MS); });
            Promise.race([Promise.all(settled), timeout]).then(finish);
        }

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function (e) {
                e.preventDefault();
                handleChoice('granted');
            });
        }
        if (declineBtn) {
            declineBtn.addEventListener('click', function (e) {
                e.preventDefault();
                handleChoice('denied');
            });
        }

        // The widget, links to "#openconsent" and elements with the class "oc-open-settings" re-open the banner.
        d.addEventListener('click', function (e) {
            var target = e.target && e.target.closest ? e.target.closest('#oc-cookie-widget, .oc-open-settings, a[href="#openconsent"]') : null;
            if (!target) return;
            e.preventDefault();
            showBanner(true);
        });
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
