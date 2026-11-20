<?php

/**
 * @package     OpenConsent
 * @copyright   (C) 2026 vidual
 * @license     GNU General Public License version 2 or later
 */

namespace Vidual\Plugin\System\OpenConsent\Extension;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * OpenConsent for Joomla: cookie banner for Google Consent Mode v2 (Basic).
 *
 * The banner itself is the shared script in media/plg_system_openconsent/js/openconsent.js;
 * this plugin only passes the settings to it and adds the head script.
 */
final class OpenConsent extends CMSPlugin implements SubscriberInterface
{
    private const MEDIA = 'plg_system_openconsent';
    private const COOKIE_NAME = 'openconsent_status';
    private const GTM_ID_PATTERN = '/^GTM-[A-Z0-9]{4,12}$/';
    private const COLOR_PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';
    private const DEFAULT_COOKIE_DURATION_DAYS = 180;
    private const MAX_COOKIE_DURATION_DAYS = 390;
    private const POSITIONS = [
        'bottom-full', 'bottom-left', 'bottom-center', 'bottom-right', 'top-full', 'top-left',
        'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right',
    ];
    private const COLORS = [
        'banner_bg_color'           => '#222222',
        'banner_text_color'         => '#ffffff',
        'link_color'                => '#00a0d2',
        'accept_button_bg_color'    => '#0073aa',
        'accept_button_text_color'  => '#ffffff',
        'decline_button_bg_color'   => '#757575',
        'decline_button_text_color' => '#ffffff',
        'widget_bg_color'           => '#333333',
        'widget_text_color'         => '#ffffff',
    ];

    /** @var bool */
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'onBeforeCompileHead',
            'onAfterRender'       => 'onAfterRender',
        ];
    }

    /**
     * Adds the banner script and styles to frontend HTML pages.
     */
    public function onBeforeCompileHead(): void
    {
        if (!$this->isFrontendHtml()) {
            return;
        }

        $wa = $this->getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle(self::MEDIA, self::MEDIA . '/openconsent.css', ['version' => 'auto']);
        $wa->registerAndUseScript(self::MEDIA, self::MEDIA . '/openconsent.js', ['version' => 'auto'], ['defer' => true]);
    }

    /**
     * Puts the config and the head script (Consent Mode defaults) right after <head>,
     * before any other script on the page.
     */
    public function onAfterRender(): void
    {
        if (!$this->isFrontendHtml()) {
            return;
        }

        $headScript = JPATH_ROOT . '/media/' . self::MEDIA . '/js/openconsent-head.js';
        if (!is_readable($headScript)) {
            return;
        }

        $app  = $this->getApplication();
        $body = $app->getBody();
        if (!\is_string($body) || !preg_match('/<head\b[^>]*>/i', $body, $match, PREG_OFFSET_CAPTURE)) {
            return;
        }

        $config = json_encode(
            $this->getFrontendConfig(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );

        $script = "\n<script id=\"openconsent-consent-default\" data-cfasync=\"false\">\n"
            . 'window.openconsentConfig = ' . $config . ";\n"
            . file_get_contents($headScript)
            . "</script>\n";

        $insertAt = $match[0][1] + \strlen($match[0][0]);
        $app->setBody(substr($body, 0, $insertAt) . $script . substr($body, $insertAt));
    }

    private function isFrontendHtml(): bool
    {
        $app = $this->getApplication();
        if (!$app->isClient('site')) {
            return false;
        }

        $document = $app->getDocument();

        return $document !== null && $document->getType() === 'html';
    }

    /**
     * Settings for the shared banner script (window.openconsentConfig).
     */
    private function getFrontendConfig(): array
    {
        $gtmId = strtoupper(trim((string) $this->params->get('gtm_id', '')));
        if (!preg_match(self::GTM_ID_PATTERN, $gtmId)) {
            $gtmId = '';
        }

        $duration = (int) $this->params->get('cookie_duration_days', self::DEFAULT_COOKIE_DURATION_DAYS);
        if ($duration < 1) {
            $duration = self::DEFAULT_COOKIE_DURATION_DAYS;
        }

        $position = (string) $this->params->get('banner_position', 'bottom-full');
        if (!\in_array($position, self::POSITIONS, true)) {
            $position = 'bottom-full';
        }

        $bannerText = trim((string) $this->params->get('banner_text', ''));
        $bannerText = $bannerText !== '' ? InputFilter::getInstance([], [], 1, 1)->clean($bannerText, 'html') : $this->getDefaultBannerText();

        $config = [
            'gtm_id'                  => $gtmId,
            'should_load_own_gtm'     => $gtmId !== '',
            'banner_text'             => $bannerText,
            'accept_text'             => $this->textParam('accept_text', 'PLG_SYSTEM_OPENCONSENT_ACCEPT'),
            'decline_text'            => $this->textParam('decline_text', 'PLG_SYSTEM_OPENCONSENT_DECLINE'),
            'minimized_text'          => $this->textParam('minimized_text', 'PLG_SYSTEM_OPENCONSENT_COOKIE_SETTINGS'),
            'banner_label'            => Text::_('PLG_SYSTEM_OPENCONSENT_BANNER_LABEL'),
            'enable_minimized_widget' => (bool) $this->params->get('enable_minimized_widget', 1),
            'cookie_name'             => self::COOKIE_NAME,
            'cookie_duration_days'    => min(self::MAX_COOKIE_DURATION_DAYS, $duration),
            'banner_position'         => $position,
            'debug'                   => \defined('JDEBUG') && JDEBUG,
        ];

        foreach (self::COLORS as $key => $default) {
            $value        = (string) $this->params->get($key, $default);
            $config[$key] = preg_match(self::COLOR_PATTERN, $value) ? $value : $default;
        }

        return $config;
    }

    private function textParam(string $key, string $defaultKey): string
    {
        $value = trim((string) $this->params->get($key, ''));

        return $value !== '' ? $value : Text::_($defaultKey);
    }

    /**
     * Default banner message: names the purposes, Google as recipient and the right to withdraw.
     */
    private function getDefaultBannerText(): string
    {
        return '<p>' . htmlspecialchars(Text::_('PLG_SYSTEM_OPENCONSENT_DEFAULT_BANNER_TEXT'), ENT_QUOTES, 'UTF-8')
            . ' <a href="https://business.safety.google/privacy/" target="_blank" rel="noopener noreferrer">'
            . htmlspecialchars(Text::_('PLG_SYSTEM_OPENCONSENT_GOOGLE_LINK'), ENT_QUOTES, 'UTF-8')
            . '</a></p>';
    }
}
