<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Loek Hilgersom <typo3extensions@netcoop.nl>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
* Get mail from IMAP and store in tt_news
* ext: mail2news
*
* $Id$
*
* @author Loek Hilgersom <typo3extensions@netcoop.nl>
*/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   50: class tx_mail2news_ttnews
 *   58:     function __construct()
 *   68:     function category_id($category)
 *   90:     function store_news($newsitem)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib . 'class.t3lib_tcemain.php');

class tx_mail2news_ttnews {

	public $extconf;
	private $extKey = 'mail2news';

	/*
	* Construct new object
	*/
	/*
	function __construct() {
	}
	*/

	/*
	 *  Check if tt_news category exists.
	 *  First checks is $category is category-title, if not, checks if it matches uid.
	 *  Input: string or integer $category, can be name or uid
	 *  Output: string category-uid, FALSE if no matching category
	 */
	function category_id($category) {
		global $TYPO3_DB;
		$rows = $TYPO3_DB->exec_SELECTgetRows('uid,title', 'tt_news_cat', 'title = "'.$category. '"');
		if (count($rows) == 1) {
			$uid = $rows[0]['uid'];
		} else {
			$rows = $TYPO3_DB->exec_SELECTgetRows('uid', 'tt_news_cat', 'uid = '.$category);
			if (count($rows) == 1) {
				$uid = $category;
			} else {
				$uid = FALSE;
			}
		}
		return $uid;
	}

	/**
	 * Store data in array $newsitem in DB table tt_news
	 *
	 * @param	[array]		$newsitem: ...
	 * @param	[boolean]	$useTceMain: if set, use TCEmain instead of direct DB inserts
	 * (requires properly configured cli BE-user with rights to save news records)
	 * @return	VOID
	 */
	function store_news($newsitem,$useTceMain=0) {

		// Set category for this record?
		$addCat = isset($newsitem['category']);
		// tt_news field category in table tt_news contains no of categories
		if ($addCat) {
			$category_id = $newsitem['category'];
			$newsitem['category'] = 1;
		} else {
			$newsitem['category'] = 0;
		}
		$newsitem['tstamp'] = $newsitem['crdate'] = time();

		if ($useTceMain) {

			global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
			//	$newsitem['bodytext'] = substr($newsitem['bodytext'],0,20);
			$uid = 'NEW' . uniqid('');
			// Datamap for page and content
			$datamap = array(
				'tt_news' => array(
					$uid => $newsitem
				)
			);
			if ($addCat) {
				$datamap['tt_news_cat_mm'] = array(
					'uid_local' => $uid,
					'uid_foreign' => $category_id,
					'sorting' => 1
				);
			}

			// Create TCEmain instance
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			/* @var $tce t3lib_TCEmain */
			$tce->start($datamap, null);
			$tce->process_datamap();

			//print_r($datamap);

		} else {

			global $TYPO3_DB;
			$TYPO3_DB->exec_INSERTquery('tt_news', $newsitem);
			$uid = $TYPO3_DB->sql_insert_id();

			// Set category in table tt_news_cat_mm with UID of new record
			if ($addCat) {
				$catmm = array(
					'uid_local' => $uid,
					'uid_foreign' => $category_id,
					'sorting' => 1,
					//'tablenames' => ''
				);
				$TYPO3_DB->exec_INSERTquery('tt_news_cat_mm', $catmm);
			}

			// Update refindex after DB insert
			$ref = t3lib_div::makeInstance('t3lib_refindex');
			/* var $ref t3lib_refindex */
			$ref->updateRefIndexTable('tt_news',$uid);

			$logmsg = 'News item created: ' . ($category ? '[cat: ' . $category . '] ' : '') . '"' . 
				substr($newsitem['title'], 0, 50) . '", created on page (pid '. $newsitem['pid'] . ')';
			$GLOBALS['BE_USER']->simplelog($logmsg, $this->extKey);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']);
}
?>