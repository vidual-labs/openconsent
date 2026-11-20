<?php

declare(strict_types=1);

/*
 * OpenConsent - (C) 2026 vidual - GNU General Public License version 2 or later
 */

namespace Vidual\OpenConsent\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Html\DefaultSanitizerBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * OpenConsent for TYPO3: cookie banner for Google Consent Mode v2 (Basic).
 *
 * The banner itself is the shared script in Resources/Public/JavaScript/openconsent.js.
 * This middleware only adds the settings, the head script and the banner assets to
 * frontend HTML pages. It runs on every request, so it also works for cached pages.
 */
final class ConsentBanner implements MiddlewareInterface
{
    private const EXT_KEY = 'openconsent';
    private const LLL = 'LLL:EXT:openconsent/Resources/Private/Language/locallang.xlf:';
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

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $settings = $this->getSettings();
        if (!(bool)($settings['enable_banner'] ?? true)
            || stripos($response->getHeaderLine('Content-Type'), 'text/html') !== 0
        ) {
            return $response;
        }

        $body = (string)$response->getBody();
        if (!preg_match('/<head\b[^>]*>/i', $body, $match, PREG_OFFSET_CAPTURE)) {
            return $response;
        }
        $headEnd = stripos($body, '</head>');
        if ($headEnd === false) {
            return $response;
        }

        $headScript = GeneralUtility::getFileAbsFileName('EXT:openconsent/Resources/Public/JavaScript/openconsent-head.js');
        if ($headScript === '' || !is_readable($headScript)) {
            return $response;
        }

        $config = json_encode(
            $this->getFrontendConfig($settings, $request),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );

        // Config + head script (Consent Mode defaults) go right after <head>, before any other script.
        $headStart = "\n<script id=\"openconsent-consent-default\" data-cfasync=\"false\">\n"
            . 'window.openconsentConfig = ' . $config . ";\n"
            . file_get_contents($headScript)
            . "</script>\n";

        // Styles and banner script go at the end of <head>.
        $assets = '<link rel="stylesheet" href="' . $this->assetUrl('Css/openconsent.css') . '">' . "\n"
            . '<script src="' . $this->assetUrl('JavaScript/openconsent.js') . '" defer></script>' . "\n";

        $insertAt = $match[0][1] + \strlen($match[0][0]);
        $body = substr($body, 0, $insertAt) . $headStart
            . substr($body, $insertAt, $headEnd - $insertAt) . $assets
            . substr($body, $headEnd);

        $response = $response->withBody($this->streamFactory->createStream($body));
        if ($response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string)\strlen($body));
        }

        return $response;
    }

    /**
     * Global settings from Admin Tools > Settings > Extension Configuration.
     */
    private function getSettings(): array
    {
        try {
            $settings = $this->extensionConfiguration->get(self::EXT_KEY);
        } catch (\Throwable) {
            $settings = [];
        }

        return \is_array($settings) ? $settings : [];
    }

    /**
     * Settings for the shared banner script (window.openconsentConfig).
     */
    private function getFrontendConfig(array $settings, ServerRequestInterface $request): array
    {
        $languageService = $this->getLanguageService($request);

        $gtmId = strtoupper(trim((string)($settings['gtm_id'] ?? '')));
        if (!preg_match(self::GTM_ID_PATTERN, $gtmId)) {
            $gtmId = '';
        }

        $duration = (int)($settings['cookie_duration_days'] ?? self::DEFAULT_COOKIE_DURATION_DAYS);
        if ($duration < 1) {
            $duration = self::DEFAULT_COOKIE_DURATION_DAYS;
        }

        $position = (string)($settings['banner_position'] ?? 'bottom-full');
        if (!\in_array($position, self::POSITIONS, true)) {
            $position = 'bottom-full';
        }

        $bannerText = trim((string)($settings['banner_text'] ?? ''));
        $bannerText = $bannerText !== ''
            ? (new DefaultSanitizerBuilder())->build()->sanitize($bannerText)
            : $this->getDefaultBannerText($languageService);

        $config = [
            'gtm_id'                  => $gtmId,
            'should_load_own_gtm'     => $gtmId !== '',
            'banner_text'             => $bannerText,
            'accept_text'             => $this->text($settings, 'accept_text', 'accept', $languageService),
            'decline_text'            => $this->text($settings, 'decline_text', 'decline', $languageService),
            'minimized_text'          => $this->text($settings, 'minimized_text', 'cookie_settings', $languageService),
            'banner_label'            => $languageService->sL(self::LLL . 'banner_label'),
            'enable_minimized_widget' => (bool)($settings['enable_minimized_widget'] ?? true),
            'cookie_name'             => self::COOKIE_NAME,
            'cookie_duration_days'    => min(self::MAX_COOKIE_DURATION_DAYS, $duration),
            'banner_position'         => $position,
            'debug'                   => (bool)($GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] ?? false),
        ];

        foreach (self::COLORS as $key => $default) {
            $value = trim((string)($settings[$key] ?? $default));
            $config[$key] = preg_match(self::COLOR_PATTERN, $value) ? $value : $default;
        }

        return $config;
    }

    /**
     * Texts follow the language of the current page unless they are set in the extension configuration.
     */
    private function getLanguageService(ServerRequestInterface $request): LanguageService
    {
        $siteLanguage = $request->getAttribute('language');

        return $siteLanguage instanceof SiteLanguage
            ? $this->languageServiceFactory->createFromSiteLanguage($siteLanguage)
            : $this->languageServiceFactory->create('default');
    }

    private function text(array $settings, string $key, string $label, LanguageService $languageService): string
    {
        $value = trim((string)($settings[$key] ?? ''));

        return $value !== '' ? $value : $languageService->sL(self::LLL . $label);
    }

    /**
     * Default banner message: names the purposes, Google as recipient and the right to withdraw.
     */
    private function getDefaultBannerText(LanguageService $languageService): string
    {
        return '<p>' . htmlspecialchars($languageService->sL(self::LLL . 'default_banner_text'), ENT_QUOTES, 'UTF-8')
            . ' <a href="https://business.safety.google/privacy/" target="_blank" rel="noopener noreferrer">'
            . htmlspecialchars($languageService->sL(self::LLL . 'google_link'), ENT_QUOTES, 'UTF-8')
            . '</a></p>';
    }

    private function assetUrl(string $path): string
    {
        $resource = 'EXT:openconsent/Resources/Public/' . $path;
        $file = GeneralUtility::getFileAbsFileName($resource);
        $version = $file !== '' && is_file($file) ? '?v=' . filemtime($file) : '';

        return htmlspecialchars(PathUtility::getPublicResourceWebPath($resource) . $version, ENT_QUOTES, 'UTF-8');
    }
}
