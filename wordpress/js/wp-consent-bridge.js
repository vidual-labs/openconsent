/**
 * OpenConsent for WordPress: WP Consent API bridge
 *
 * Passes the visitor's choice from the shared banner script (openconsent.js) to the
 * WP Consent API. Uses the API's own JavaScript function when available (this also
 * notifies Site Kit and other listeners), otherwise the AJAX endpoint.
 * Must load before openconsent.js.
 */
(function (w, d) {
    'use strict';

    var cfg = w.openconsentConfig || {};
    var debug = !!cfg.debug;
    var categories = cfg.consent_categories || ['statistics', 'statistics-anonymous', 'marketing', 'preferences'];

    function log() {
        if (debug && w.console) console.log.apply(console, arguments);
    }

    function warn() {
        if (debug && w.console) console.warn.apply(console, arguments);
    }

    // WP Consent API: announce that this site uses opt-in consent.
    w.wp_consent_type = 'optin';
    try {
        d.dispatchEvent(new CustomEvent('wp_consent_type_defined'));
    } catch (e) { /* very old browsers */ }

    /**
     * @param {string} status 'granted' or 'denied'
     * @returns {Promise|undefined} pending AJAX request, if one was sent
     */
    function update(status) {
        var value = status === 'granted' ? 'allow' : 'deny';

        if (typeof w.wp_set_consent === 'function') {
            try {
                w.wp_set_consent('functional', 'allow');
                for (var i = 0; i < categories.length; i++) {
                    w.wp_set_consent(categories[i], value);
                }
                log('OpenConsent: WP Consent API updated to:', value);
            } catch (e) {
                console.error('OpenConsent: Error updating WP Consent API:', e);
            }
            return undefined;
        }

        if (!cfg.wp_consent_api_active || !cfg.ajax_url || !cfg.ajax_nonce || typeof w.fetch !== 'function') {
            return undefined;
        }

        var body = new URLSearchParams();
        body.append('action', 'openconsent_update_consent');
        body.append('nonce', cfg.ajax_nonce);
        body.append('status', status);

        return w.fetch(cfg.ajax_url, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response && response.success) {
                    log('OpenConsent: WP Consent API updated successfully.', response.data);
                } else {
                    warn('OpenConsent: WP Consent API returned error:', response && response.data);
                }
            }, function (e) {
                warn('OpenConsent: WP Consent API AJAX request failed.', e);
            });
    }

    /** Keep the WP Consent API cookies in line with the stored choice (they can expire earlier). */
    function sync(status) {
        if (!status || typeof w.wp_set_consent !== 'function' || typeof w.wp_has_consent !== 'function') return;
        try {
            var expected = status === 'granted';
            for (var i = 0; i < categories.length; i++) {
                if (w.wp_has_consent(categories[i]) !== expected) {
                    update(status);
                    return;
                }
            }
        } catch (e) {
            console.error('OpenConsent: Error syncing WP Consent API:', e);
        }
    }

    d.addEventListener('openconsent:ready', function (e) {
        sync(e.detail && e.detail.status);
    });

    d.addEventListener('openconsent:choice', function (e) {
        e.detail.waitUntil(update(e.detail.status));
    });
})(window, document);
