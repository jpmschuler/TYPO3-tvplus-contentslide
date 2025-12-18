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

use Tvp\TemplaVoilaPlus\Domain\Model\Configuration\DataConfiguration;
use Tvp\TemplaVoilaPlus\Service\ApiService;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SlideController
{
    /**
     * @var array
     */
    public $languageFallback = [];
    public $prefixId = 'SlideController';
    public $scriptRelPath = 'Classes/SlideController.php';
    public $extKey = 'tvplus_contentslide';
    protected ?ContentObjectRenderer $cObj = null;

    /**
     * Property for accessing TypoScriptFrontendController centrally
     *
     * @var TypoScriptFrontendController
     */
    protected $frontendController;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;
    /**
     * Class Constructor (true constructor)
     * Initializes $this->piVars if $this->prefixId is set to any value
     *
     * @param null $_ unused,
     */
    public function __construct($_ = null, ?TypoScriptFrontendController $frontendController = null)
    {
        $this->frontendController = $frontendController ?: $GLOBALS['TSFE'];
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    /**
     * @var bool true
     */
    public $pi_checkCHash = true;

    /**
     * This setter is called when the plugin is called from UserContentObject (USER)
     * via ContentObjectRenderer->callUserFunction().
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * The main method getting called as pre/postUserFunc from the 'source' property of the RECORDS TS cObject
     * rendering the Content Elements for a TV Column. Should return the tt_content entries of the first page
     * which has this value set.
     *
     * @param ?string $content The already set content
     * @param array $conf The configuration of the plugin
     *
     * @return string The content elements as comma separated list as required by RECORDS
     */
    // phpcs:disable Generic.Metrics.CyclomaticComplexity
    public function main(?string $content, array $conf): string
    {
        // phpcs:enable
        if (
            (isset($conf['overridePage']) && $conf['overridePage'])
            || (isset($conf['overridePage.']) && $conf['overridePage.'])
        ) {
            $overridePage = $this->cObj->stdWrap($conf['overridePage'], $conf['overridePage.']);
            $rootLineUtility = GeneralUtility::makeInstance(
                RootlineUtility::class,
                $overridePage,
                $GLOBALS['TSFE']->MP
            );
        } else {
            $rootLineUtility = GeneralUtility::makeInstance(
                RootlineUtility::class,
                $GLOBALS['TSFE']->id
            );
        }
        $rootLine = $rootLineUtility->get();
        $recordsFromTable = trim($this->cObj->stdWrap($conf['table'] ?? '', $conf['table.'] ?? []));
        $reverse = (int)$this->cObj->stdWrap($conf['reverse'] ?? '', $conf['reverse.'] ?? []);
        $innerReverse = (int)$this->cObj->stdWrap($conf['innerReverse'] ?? '', $conf['innerReverse.'] ?? []);
        $field = $this->cObj->stdWrap($conf['field'] ?? '', $conf['field.'] ?? []);
        $collect = (int)$this->cObj->stdWrap($conf['collect'] ?? '', $conf['collect.'] ?? []);
        $slide = ((int)$this->cObj->stdWrap($conf['slide'] ?? '', $conf['slide.'] ?? [])) ?: -1;
        $tempLanguageFallback = $this->cObj->stdWrap($conf['languageFallback'] ?? '', $conf['languageFallback.'] ?? []);
        $this->languageFallback = GeneralUtility::intExplode(',', $tempLanguageFallback ?? '');
        foreach ($rootLine as $page) {
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $page = $pageRepository->getPage($page['uid']);
            $value = $this->getPageFlexValue($page, $field);
            if ($value && $recordsFromTable) {
                $value = $this->removeHiddenRecords($value, $recordsFromTable);
            }
            if ($innerReverse) {
                $parts = GeneralUtility::trimExplode(',', $value, true);
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
        $loadDB->start($uidList, $recordTable);
        foreach ($loadDB->tableArray as $table => $tableData) {
            if (is_array($GLOBALS['TCA'][$table] ?? null)) {
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
     * @param array $page The page row from which to retrieve the flex field value
     * @param string $field The field name in the flex XML
     *
     * @return string The contents of the field
     */
    protected function getPageFlexValue(array $page, string $field): string
    {
        $flexData = $page['tx_templavoilaplus_flex'] ?? '';
        $mapData = $page['tx_templavoilaplus_map'] ?? '';
        $xml = GeneralUtility::xml2array($flexData);
        $apiService = GeneralUtility::makeInstance(ApiService::class, 'pages');
        $combinedMappingConfigurationIdentifier = $mapData;
        // Find DS and Template in root line IF there is no Data Structure set for the current page:
        if (!$combinedMappingConfigurationIdentifier) {
            $rootLine = $apiService->getBackendRootline($page['uid'] ?? 0);
            $combinedMappingConfigurationIdentifier = $apiService->getMapIdentifierFromRootline($rootLine);
            if (!$combinedMappingConfigurationIdentifier) {
                return '';
            }
        }
        $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($combinedMappingConfigurationIdentifier);
        $combinedDataStructureIdentifier = $mappingConfiguration->getCombinedDataStructureIdentifier();

        /** @var DataConfiguration $dsModel */
        $dsModel = ApiHelperUtility::getDataStructure($combinedDataStructureIdentifier);
        $ds = $dsModel->getDataStructure();

        if (is_array($ds['meta'] ?? null)) {
            $langChildren = (int)$ds['meta']['langChildren'];
            $langDisable = (int)$ds['meta']['langDisable'];
        } else {
            $langChildren = 0;
            $langDisable = 0;
        }
        $translatedLanguagesArr = $this->getAvailableLanguages($page['uid'] ?? null);
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        $tryLang = $languageAspect->getContentId();
        $tryLangArr = $this->languageFallback;
        array_unshift($tryLangArr, $tryLang);
        foreach ($tryLangArr as $tryLang) {
            $langArr = $translatedLanguagesArr[$tryLang] ?? null;
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
                    GeneralUtility::trimExplode(',', $field, true),
                    $vKey
                );
            }
        }

        return '';
    }

    /**
     * Returns a value of a flex field and if necessary calls itself recursively
     *
     * @param ?array $arr The flex XML data array from which to return the requested value
     * @param array $keys Contains the key/path into the flex XML data array which to return
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
            return $arr[$vKey] ?? '';
        }
        $sKey = array_shift($keys);
        return $this->getSubKey($arr[$sKey] ?? [], $keys ?? [], $vKey);
    }

    /**
     * Generates an array of available languages
     *
     * @param ?int $id The page for which to return available languages. If passed only languages for
     *                 available translations will get returned.
     *
     * @return array All available languages (on the passed page id)
     */
    public function getAvailableLanguages(?int $id = 0): array
    {
        if ($id === null) {
            $id = 0;
        }
        $output = [];
        try {
            $currentSite = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($id);
        } catch (SiteNotFoundException $e) {
            return $output;
        }
        $availableLanguages = $currentSite->getLanguages();

        $output[0] = [
            'uid' => 0,
            'ISOcode' => 'DEF',
        ];
        $output[-1] = [
            'uid' => -1,
            'ISOcode' => 'DEF',
        ];

        foreach ($availableLanguages as $language) {
            $languageId = $language->getLanguageId();
            if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.4', '>=')) {
                $isoCode = $language->getLocale()->getLanguageCode();
            } else {
                $isoCode = $language->getTwoLetterIsoCode();
            }
            if ($languageId > 0 && static::checkIfPageHasTranslation($id, $languageId)) {
                $output[$languageId]['ISOcode'] = $isoCode;
            }
        }

        return $output;
    }

    public static function checkIfPageHasTranslation(int $pid, int $lid): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $queryBuilder->getRestrictions()->removeByType(FrontendGroupRestriction::class);
        $result = $queryBuilder
            ->count('uid')
            ->from('pages')->where($queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                $queryBuilder->createNamedParameter($pid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)
            ), $queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                $queryBuilder->createNamedParameter($lid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)
            ))->executeQuery()
            ->fetchOne();
        return $result > 0;
    }
}
