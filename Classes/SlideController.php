<?php
namespace Jpmschuler\TvplusContentslide;

/***************************************************************
*  Copyright notice
*
*  (c) 2004-2014 Bernhard Kraft (kraftb@think-open.at)
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
/**
 * *************************************************************
 * Update of "kb_tv_cont_slide" to work with templavoilaplus
 * Original author:
 *
 * 'author' => 'Bernhard Kraft',
 * 'author_email' => 'kraftb@think-open.at',
 * *************************************************************
 */

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Backend\Utility\BackendUtility;

class SlideController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
	var $prefixId = 'SlideController';		// Same as class name
	var $scriptRelPath = 'Classes/SlideController.php';	// Path to this script relative to the extension dir.
	var $extKey = 'tvplus_contentslide';	// The extension key.
	var $pi_checkCHash = TRUE;


	/**
	 * The main method getting called as pre/postUserFunc from the 'source' property of the RECORDS TS cObject
	 * rendering the Content Elements for a TV Column. Should return the tt_content entries of the first page
	 * which has this value set.
	 *
	 * @param string $content: The already set content
	 * @param array $conf: The configuration of the plugin
	 * @return string The content elements as comma separated list as required by RECORDS
	 */
	function main($content,$conf) {
		if ($conf['overridePage'] || $conf['overridePage.']) {
			$overridePage = $this->cObj->stdWrap($conf['overridePage'], $conf['overridePage.']);
			$rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($overridePage, $GLOBALS['TSFE']->MP);
		} else {
			$rootLine = $GLOBALS['TSFE']->rootLine;
		}
		$recordsFromTable = trim($this->cObj->stdWrap($conf['table'], $conf['table.']));
		$reverse = intval($this->cObj->stdWrap($conf['reverse'], $conf['reverse.']));
		$innerReverse = intval($this->cObj->stdWrap($conf['innerReverse'], $conf['innerReverse.']));
		$field = $this->cObj->stdWrap($conf['field'], $conf['field.']);
		$collect = intval($this->cObj->stdWrap($conf['collect'], $conf['collect.']));
		$slide = intval($this->cObj->stdWrap($conf['slide'], $conf['slide.']));
		if (!$slide) {
			$slide = -1;
		}
		$languageFallback = $this->cObj->stdWrap($conf['languageFallback'], $conf['languageFallback.']);
		if (strlen($languageFallback)) {
			$this->languageFallback = GeneralUtility::intExplode(',', $languageFallback);
		} else {
			$this->languageFallback = array();
		}
		while ($page = array_shift($rootLine)) {
			$page = $GLOBALS['TSFE']->sys_page->getPage($page['uid']);
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
				$content = $value.(strlen($content)&&strlen($value)?',':'').$content;
			} else {
				$content .= (strlen($content)&&strlen($value)?',':'').$value;
			}
			if ($collect) {
				$collect--;
			}
			if ($slide) {
				$slide--;
			}
			if (strlen($content)&&!$collect) {
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
	 * @param string $value: A list of content element uids to check
	 * @return string A list of remaining valid content elements
	 */
	protected function removeHiddenRecords($value, $recordTable) {
		$uids = GeneralUtility::intExplode(',', $value);
		$uidList = implode(',', $uids);
		$result = '';

		$loadDB = GeneralUtility::makeInstance('TYPO3\CMS\Core\Database\RelationHandler');
		$loadDB->setFetchAllFields(TRUE);
		$loadDB->start($uidList, $recordTable);
		foreach ($loadDB->tableArray as $table => $tableData) {
			if (is_array($GLOBALS['TCA'][$table])) {
				$loadDB->additionalWhere[$table] = $this->cObj->enableFields($table);
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
	 * @param array $page: The page row from which to retrieve the flex field value
	 * @param string $field: The field name in the flex XML
	 * @return string The contents of the field
	 */
	protected function getPageFlexValue($page, $field) {
		$xml = GeneralUtility::xml2array($page['tx_templavoilaplus_flex']);
		$ds = BackendUtility::getFlexFormDS($GLOBALS['TCA']['pages']['columns']['tx_templavoilaplus_flex']['config'], $page, 'pages', 'tx_templavoilaplus_flex');
		if (is_array($ds)&&is_array($ds['meta'])) {
			$langChildren = intval($ds['meta']['langChildren']);
			$langDisable = intval($ds['meta']['langDisable']);
		} else {
			$langChildren = 0;
			$langDisable = 0;
		}
		$translatedLanguagesArr = $this->getAvailableLanguages($pageUid);
		$tryLang = $GLOBALS['TSFE']->sys_language_content;
		$tryLangArr = $this->languageFallback;
		do {
			if ($langArr = $translatedLanguagesArr[$tryLang]) {
				$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$langArr['ISOcode']);
				$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v'.$langArr['ISOcode']: 'vDEF');
			} else {
				$lKey = 'lDEF';
				$vKey = 'vDEF';
			}
			$value = '';
			if (is_array($xml)&&is_array($xml['data'])&&is_array($xml['data']['sDEF'])&&is_array($xml['data']['sDEF'][$lKey])) {
				$value = $this->getSubKey($xml['data']['sDEF'][$lKey], GeneralUtility::trimExplode(',', $field, 1), $vKey);
			}
		} while ((!strlen($value))&&strlen($tryLang = array_shift($tryLangArr)));
		return $value;
	}


	/**
	 * Returns a value of a flex field and if necessary calls itself recursively
	 *
	 * @param array $arr: The flex XML data array from which to return the requested value
	 * @param array $keys: Contains the key/path into the flex XML data array which to return
	 * @param string $vKey: The language value which should get returned (i.e. vDEF, vDE, vPT, etc.)
	 * @return string The contents of the field
	 */
	function getSubKey($arr, $keys, $vKey) {
		if (!is_array($arr)) {
			return '';
		}
		if (!count($keys)) {
			return $arr[$vKey];
		} else {
			$sKey = array_shift($keys);
			return $this->getSubKey($arr[$sKey], $keys, $vKey);
		}
	}


	/**
	 * Generates an array of available languages
	 *
	 * @param integer $id: The page for which to return available languages. If passed only languages for available translations will get returned.
	 * @param boolean $onlyIsoCoded: Will only return a language if it has its "ISOcode" field set
	 * @param boolean $setDefault: When TRUE the default language "0" (lDEF/vDEF) will get included in the result
	 * @param boolean $setMulti: When TRUE the multiple languages config "-1" (lDEF/vDEF) will get included in the result
	 * @return array All available languages (on the passed page id)
	 */
	function getAvailableLanguages($id=0, $onlyIsoCoded = TRUE, $setDefault = TRUE, $setMulti = FALSE) {
		global $LANG, $TYPO3_DB, $BE_USER, $TCA, $BACK_PATH;

		$flagAbsPath = GeneralUtility::getFileAbsFileName($TCA['sys_language']['columns']['flag']['config']['fileFolder']);
		$flagIconPath = $BACK_PATH.'../'.substr($flagAbsPath, strlen(PATH_site));

		$output = array();
		//$excludeHidden = $BE_USER->isAdmin() ? '1' : 'sys_language.hidden=0';

		if ($id) {
			$excludeHidden .= ' AND pages_language_overlay.deleted=0';
			$res = $TYPO3_DB->exec_SELECTquery(
				'DISTINCT sys_language.*',
				'pages_language_overlay,sys_language',
				'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid='.intval($id).' AND '.$excludeHidden,
				'',
				'sys_language.title'
			);
		} else {
			$res = $TYPO3_DB->exec_SELECTquery(
				'sys_language.*',
				'sys_language',
				$excludeHidden,
				'',
				'sys_language.title'
			);
		}

		if ($setDefault) {
			$output[0]=array(
				'uid' => 0,
				'ISOcode' => 'DEF',
			);
		}

		if ($setMulti) {
			$output[-1]=array(
				'uid' => -1,
				'ISOcode' => 'DEF',
			);
		}

		while(is_array($row = $TYPO3_DB->sql_fetch_assoc($res))) {
			$GLOBALS['TSFE']->sys_page->versionOL('sys_language', $row);
			$output[$row['uid']]=$row;

			if ($staticLanguageUid = intval($row['static_lang_isocode'])) {
				$staticLangRow = $GLOBALS['TSFE']->sys_page->getRawRecord('static_languages', $staticLanguageUid, 'lg_iso_2');
				if ($staticLangRow['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
				}
			}
			if (strlen($row['flag'])) {
				$output[$row['uid']]['flagIcon'] = @is_file($flagAbsPath.$row['flag']) ? $flagIconPath.$row['flag'] : '';
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}

		return $output;
	}

}

