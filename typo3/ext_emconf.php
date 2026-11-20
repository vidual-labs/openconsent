<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'OpenConsent',
    'description' => 'Cookie banner for Google Consent Mode v2 (Basic): one decision, Accept or Decline. Loads Google Tag Manager only after consent.',
    'category' => 'fe',
    'author' => 'vidual',
    'author_company' => 'vidual',
    'state' => 'stable',
    'version' => '1.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Vidual\\OpenConsent\\' => 'Classes/',
        ],
    ],
];
