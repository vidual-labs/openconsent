/**
 * OpenConsent head script (shared by WordPress, Joomla and the universal snippet)
 *
 * Must run in <head> before any Google tag. Reads window.openconsentConfig.
 * Standalone mode only (a GTM ID is set and no other plugin manages Google tags):
 * - sets the Google Consent Mode v2 defaults (everything denied)
 * - returning visitors who accepted get the consent update and GTM right here,
 *   so tags do not have to wait for the banner script
 */
(function (w, d) {
    var cfg = w.openconsentConfig = w.openconsentConfig || {};
    var gtmId = typeof cfg.gtm_id === 'string' ? cfg.gtm_id.trim().toUpperCase() : '';
    if (!/^GTM-[A-Z0-9]{4,12}$/.test(gtmId)) gtmId = '';
    cfg.gtm_id = gtmId;
    if (typeof cfg.should_load_own_gtm !== 'boolean') cfg.should_load_own_gtm = gtmId !== '';
    if (!cfg.should_load_own_gtm || !gtmId) {
        cfg.should_load_own_gtm = false;
        return;
    }

    w.dataLayer = w.dataLayer || [];
    if (typeof w.gtag !== 'function') {
        w.gtag = function () { w.dataLayer.push(arguments); };
    }
    w.gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted',
        'wait_for_update': 500
    });
    w.gtag('set', 'ads_data_redaction', true);

    var name = (cfg.cookie_name || 'openconsent_status') + '=';
    var parts = d.cookie.split(';');
    var status = null;
    for (var i = 0; i < parts.length; i++) {
        var c = parts[i].replace(/^\s+/, '');
        if (c.indexOf(name) === 0) status = c.substring(name.length);
    }
    if (status !== 'granted') return;

    w.gtag('consent', 'update', {
        'ad_storage': 'granted',
        'ad_user_data': 'granted',
        'ad_personalization': 'granted',
        'analytics_storage': 'granted',
        'functionality_storage': 'granted',
        'personalization_storage': 'granted'
    });
    w.ocGtmLoaded = true;
    w.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
    var f = d.getElementsByTagName('script')[0];
    var j = d.createElement('script');
    j.async = true;
    j.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtmId);
    if (f && f.parentNode) {
        f.parentNode.insertBefore(j, f);
    } else {
        (d.head || d.documentElement).appendChild(j);
    }
})(window, document);
