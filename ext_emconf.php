<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà! Plus: Content Slide',
    'description' => 'This extension allows you to inherit the content of a TemplaVoilaPlus content element column to its child pages - Adaption of EXT:kb_tv_cont_slide to work with templavoilaplus',
    'category' => 'plugin',
    'version' => '12.0.3',
    'state' => 'stable',
    'author' => 'Schuler, J. Peter M.',
    'author_email' => 'j.peter.m.schuler@uni-due.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'templavoilaplus' => '8.0.3-8.99.99',
            'typo3' => '12.4.26-13.4.99'
        ]
    ],
    'autoload' => [
    'psr-4' => [
        'Jpmschuler\\TvplusContentslide\\' => 'Classes/',
    ],
],
];
