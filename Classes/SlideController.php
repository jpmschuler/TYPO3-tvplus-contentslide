<?php

/***************************************************************
 *  Copyright notice
 *
 *  Update of "kb_tv_cont_slide" to work with templavoilaplus
 *  Original author:
 *  (c) 2004-2014 Bernhard Kraft (kraftb@think-open.at)
 *
 *  Current maintainer:
 *  (c) 2016-2021 J. Peter M. Schuler (j.peter.m.schuler@uni-due.de)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Jpmschuler\TvplusContentslide;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

class SlideController extends AbstractPlugin
{
    /**
     * @var array
     */
    public $languageFallback = [];
    public $prefixId = 'SlideController';
    public $scriptRelPath = 'Classes/SlideController.php';
    public $extKey = 'tvplus_contentslide';

    /**
     * @var bool true
     */
    public $pi_checkCHash = true;

    /**
     * The main method getting called as pre/postUserFunc from the 'source' property of the RECORDS TS cObject
     * rendering the Content Elements for a TV Column. Should return the tt_content entries of the first page
     * which has this value set.
     *
     * @param ?string $content : The already set content
     * @param array   $conf    : The configuration of the plugin
     *
     * @return string The content elements as comma separated list as required by RECORDS
     */
    public function main(?string $content, array $conf): string
    {
        if ($conf['overridePage'] || $conf['overridePage.']) {
            $overridePage = $this->cObj->stdWrap($conf['overridePage'], $conf['overridePage.']);
            $rootLineUtility = GeneralUtility::makeInstance(
                RootlineUtility::class,
                $overridePage,
                $GLOBALS['TSFE']->MP
            );
            $rootLine = $rootLineUtility->get();
        } else {
            $rootLine = $GLOBALS['TSFE']->rootLine;
        }
        $recordsFromTable = trim($this->cObj->stdWrap($conf['table'], $conf['table.']));
        $reverse = (int)$this->cObj->stdWrap($conf['reverse'], $conf['reverse.']);
        $innerReverse = (int)$this->cObj->stdWrap($conf['innerReverse'], $conf['innerReverse.']);
        $field = $this->cObj->stdWrap($conf['field'], $conf['field.']);
        $collect = (int)$this->cObj->stdWrap($conf['collect'], $conf['collect.']);
        $slide = ((int)$this->cObj->stdWrap($conf['slide'], $conf['slide.'])) ?: -1;
        $tempLanguageFallback = $this->cObj->stdWrap($conf['languageFallback'], $conf['languageFallback.']);
        $this->languageFallback = $tempLanguageFallback !== ''
            ? GeneralUtility::intExplode(',', $tempLanguageFallback)
            : [];
        foreach ($rootLine as $page) {
            /** @var PageRepository $pageRepository */
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $page = $pageRepository->getPage($page['uid']);
            $value = $this->getPageFlexValue($page, $field);
            if ($value && $recordsFromTable) {
                $value = $this->removeHiddenRecords($value, $recordsFromTable);
            }
            if ($innerReverse) {
                $parts = GeneralUtility::trimExplode(',', $value, 1);
                $parts = array_reverse($parts);
                $value = implode(',', $parts);
            }
            if ($reverse) {
                $content = $value . (strlen($content) && strlen($value) ? ',' : '') . $content;
            } else {
                $content .= (strlen($content) && strlen($value) ? ',' : '') . $value;
            }
            if ($collect) {
                $collect--;
            }
            if ($slide) {
                $slide--;
            }
            if (strlen($content) && !$collect) {
                break;
            }
            if (!$slide) {
                break;
            }
        }
        return $content;
    }

    /**
     * This method removes hidden or disabled content elements from the list.
     *
     * @param string $value A csv list of content element uids to check
     *
     * @return string A csv list of remaining valid content elements
     */
    protected function removeHiddenRecords(string $value, $recordTable): string
    {
        $uids = GeneralUtility::intExplode(',', $value);
        $uidList = implode(',', $uids);
        $result = '';

        $loadDB = GeneralUtility::makeInstance(RelationHandler::class);
        $loadDB->setFetchAllFields(true);
        $loadDB->start($uidList, $recordTable);
        foreach ($loadDB->tableArray as $table => $tableData) {
            if (is_array($GLOBALS['TCA'][$table])) {
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $loadDB->additionalWhere[$table] = $pageRepository->enableFields($table);
            }
        }
        $loadDB->getFromDB();

        if (is_array($loadDB->results[$recordTable])) {
            $result = array_keys($loadDB->results[$recordTable]);
            $result = implode(',', array_intersect($uids, $result));
        }
        return $result;
    }

    /**
     * This method returns the contents of the flex-field given.
     *
     * @param array  $page  The page row from which to retrieve the flex field value
     * @param string $field The field name in the flex XML
     *
     * @return string The contents of the field
     */
    protected function getPageFlexValue($page, $field): string
    {
        $xml = GeneralUtility::xml2array($page['tx_templavoilaplus_flex']);
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $ds = $flexFormTools->parseDataStructureByIdentifier(
            $flexFormTools->getDataStructureIdentifier(
                $GLOBALS['TCA']['pages']['columns']['tx_templavoilaplus_flex'],
                'pages',
                'tx_templavoilaplus_flex',
                $page
            )
        );

        if (is_array($ds) && is_array($ds['meta'])) {
            $langChildren = (int)$ds['meta']['langChildren'];
            $langDisable = (int)$ds['meta']['langDisable'];
        } else {
            $langChildren = 0;
            $langDisable = 0;
        }
        $translatedLanguagesArr = $this->getAvailableLanguages($page['uid']);
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        $tryLang = $languageAspect->getContentId();
        $tryLangArr = $this->languageFallback;
        array_unshift($tryLangArr, $tryLang);
        foreach ($tryLangArr as $tryLang) {
            $langArr = $translatedLanguagesArr[$tryLang];
            if ($langArr) {
                $lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l' . $langArr['ISOcode']);
                $vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v' . $langArr['ISOcode'] : 'vDEF');
            } else {
                $lKey = 'lDEF';
                $vKey = 'vDEF';
            }
            if (
                is_array($xml)
                && is_array($xml['data'])
                && is_array($xml['data']['sDEF'])
                && is_array($xml['data']['sDEF'][$lKey])
            ) {
                return $this->getSubKey(
                    $xml['data']['sDEF'][$lKey],
                    GeneralUtility::trimExplode(',', $field, 1),
                    $vKey
                );
            }
        }

        return '';
    }

    /**
     * Returns a value of a flex field and if necessary calls itself recursively
     *
     * @param ?array $arr  The flex XML data array from which to return the requested value
     * @param array  $keys Contains the key/path into the flex XML data array which to return
     * @param string $vKey The language value which should get returned (i.e. vDEF, vDE, vPT, etc.)
     *
     * @return string The contents of the field
     */
    public function getSubKey(?array $arr, array $keys, string $vKey): string
    {
        if (!is_array($arr)) {
            return '';
        }
        if (!count($keys)) {
            return $arr[$vKey];
        }
        $sKey = array_shift($keys);
        return $this->getSubKey($arr[$sKey], $keys, $vKey);
    }

    /**
     * Generates an array of available languages
     *
     * @param int  $id           : The page for which to return available languages. If passed only languages for
     *                           available translations will get returned.
     * @param bool $onlyIsoCoded : Will only return a language if it has its "ISOcode" field set
     * @param bool $setDefault   : When TRUE the default language "0" (lDEF/vDEF) will get included in the result
     * @param bool $setMulti     : When TRUE the multiple languages config "-1" (lDEF/vDEF) will get included in the
     *                           result
     *
     * @return array All available languages (on the passed page id)
     */
    public function getAvailableLanguages(
        ?int $id = 0,
        bool $onlyIsoCoded = true,
        bool $setDefault = true,
        bool $setMulti = true
    ): array {
        if ($id === null) {
            $id = 0;
        }
        // TODO: rector and refactor
        $flagAbsPath = GeneralUtility::getFileAbsFileName(
            $GLOBALS['TCA']['sys_language']['columns']['flag']['config']['fileFolder']
        );
        $flagIconPath = $GLOBALS['BACK_PATH'] . '../' . substr($flagAbsPath, strlen(Environment::getPublicPath()));

        $output = [];
        $excludeHidden = 'sys_language.hidden=0';

        if ($id) {
            $excludeHidden .= ' AND pages.deleted=0';
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'DISTINCT sys_language.*',
                'pages,sys_language',
                'pages.sys_language_uid=sys_language.uid AND pages.pid=' . ((int)$id) . ' AND ' . $excludeHidden,
                '',
                'sys_language.title'
            );
        } else {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'sys_language.*',
                'sys_language',
                $excludeHidden,
                '',
                'sys_language.title'
            );
        }

        if ($setDefault) {
            $output[0] = [
                'uid' => 0,
                'ISOcode' => 'DEF',
            ];
        }

        if ($setMulti) {
            $output[-1] = [
                'uid' => -1,
                'ISOcode' => 'DEF',
            ];
        }

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $pageRepository->versionOL('sys_language', $row);
            $output[$row['uid']] = $row;

            $staticLanguageUid = (int)$row['static_lang_isocode'];
            if ($staticLanguageUid) {
                $staticLangRow = $pageRepository->getRawRecord('static_languages', $staticLanguageUid, 'lg_iso_2');
                if ($staticLangRow['lg_iso_2']) {
                    $output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
                }
            }
            if ($row['flag'] !== '') {
                $output[$row['uid']]['flagIcon']
                    = is_file($flagAbsPath . $row['flag']) ? $flagIconPath . $row['flag'] : '';
            }

            if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
                unset($output[$row['uid']]);
            }
        }

        return $output;
    }
}
