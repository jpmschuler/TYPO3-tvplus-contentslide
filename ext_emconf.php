<?php

/**
 * *************************************************************
 * Update of "kb_tv_cont_slide" to work with templavoilaplus
 * Original author:
 *
 * 'author' => 'Bernhard Kraft',
 * 'author_email' => 'kraftb@think-open.at',
 * *************************************************************
 */
$EM_CONF[$_EXTKEY] = array(
    'title' => 'TemplaVoila Plus: Content Slide',
    'description' => 'This extension allows you to inherit the content of a TemplaVoilaPlus content element column to its child pages - Adaption of EXT:kb_tv_cont_slide to work with templavoilaplus',
    'category' => 'plugin',
    'version' => '1.0.4-0',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => false,
    'author' => 'Schuler, J. Peter M.',
    'author_email' => 'j.peter.m.schuler@uni-due.de',
    'author_company' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '5.2.0-0.0.0',
            'typo3' => '6.2.0-7.6.99'
        ),
        'conflicts' => array(),
        'suggests' => array()
    )
);

