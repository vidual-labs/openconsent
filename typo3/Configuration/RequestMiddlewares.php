<?php

/*
 * OpenConsent - (C) 2026 vidual - GNU General Public License version 2 or later
 */

// Wraps page rendering in the frontend; needs the resolved site language for the texts.
return [
    'frontend' => [
        'vidual/openconsent/consent-banner' => [
            'target' => \Vidual\OpenConsent\Middleware\ConsentBanner::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
