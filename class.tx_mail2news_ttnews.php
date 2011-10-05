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
 *   90:     function store_news($item)
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
	 *  Check if tt_news category exists. DEPRECATED
	 *  First checks is $category is category-title, if not, checks if it matches uid.
	 *  Input: string or integer $category, can be name or uid
	 *  Output: string category-uid, FALSE if no matching category
	 */
	function category_id($category) {
		global $TYPO3_DB;
		$rows = $TYPO3_DB->exec_SELECTgetRows(
			'uid',
			'tt_news_cat',
			'title LIKE ' . $TYPO3_DB->fullQuoteStr( trim($category) , 'tt_news_cat') . ' AND deleted=0'
		);

		$uid = FALSE;
		if (count($rows) >= 1) {
			$uid = $rows[0]['uid'];
		} elseif (is_numeric($category)) {
			$rows = $TYPO3_DB->exec_SELECTgetRows(
				'uid',
				'tt_news_cat',
				'uid = '. intval($category) . ' AND deleted=0'
			);
			if (count($rows) == 1) {
				$uid = $category;
			}
		}
		return $uid;
	}

	/*
	 *  Check if categories exist and return category id's.
	 *  Input can be category names OR id's (if input is numeric, it's considered an id)
	 *  Output: csv string category_ids, FALSE if no matching category
	 */
	function category_ids($categories) {

		$category_ids = FALSE;
		if(trim($categories!='')) {
			$cat_array = explode( ',' , $categories );
			$orderBy = '0';
			$where = 'deleted=0 AND ( 0=1';
			$i = 0;
			foreach ($cat_array as $category) {
				$category = trim($category);
				if (is_numeric($category)) {
					$condition = 'uid=' . intval($category);
				} elseif ( trim($category)!='' ) {
					$condition = 'title LIKE ' . $TYPO3_DB->fullQuoteStr($category, 'tt_news_cat');
				} else {
					continue;
				}
				$where .= ' OR ' . $condition;
				// Monstruous construction for preserving sort order of categories, but it works!
				$orderBy = 'IF(' . $condition . ', ' . $i++ . ', ' . $orderBy . ')';
			}
			$where .= ' )';
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows( 'uid', 'tt_news_cat', $where, '', $orderBy, '', 'uid' );

			if (count($rows) >= 1) {
				$category_ids = implode ( ',' , array_keys($rows) );
			}
		}
	#	t3lib_div::debug($rows, '$rows');
	#	t3lib_div::debug($category_ids, '$categories');
		return $category_ids;
	}

	/**
	 * Store data in array $item in DB table tt_news
	 *
	 * @param	[array]		$item: ...
	 * @param	[boolean]	$useTceMain: if set, use TCEmain instead of direct DB inserts
	 * (requires properly configured cli BE-user with rights to save news records, experimental!)
	 * @return	VOID
	 */
	function store_record($item, $useTceMain=false, $disableLogging=false) {

		$tt_news = array();

		if (!$item['crdate']) $item['crdate'] = time();
		if (!$item['tstamp']) $item['tstamp'] = time();

		t3lib_div::loadTCA('tt_news');
		// Automatically match fields from input array to matching TCA fields in relevant tables
		foreach ($item as $key => $value) {
			if (isset( $GLOBALS['TCA']['tt_news']['columns'][$key] ))  $tt_news[$key] = $item[$key];
			if (in_array($key, array('pid', 'crdate', 'tstamp', 'cruser_id')))  $tt_news[$key] = $item[$key];
		}

		// Set category for this record?
		$addCat = isset($item['tx_mail2news_categories']);
		// tt_news field category in table tt_news contains no of categories
		if ($addCat) {
			$categories = explode(',' ,$item['tx_mail2news_categories']);
			$tt_news['category'] = count($categories);
		} else {
			$tt_news['category'] = 0;
		}

		if ($useTceMain) {

			global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
			//	$item['bodytext'] = substr($item['bodytext'],0,20);
			$uid = 'NEW' . uniqid('');
			// Datamap for page and content
			$datamap = array(
				'tt_news' => array(
					$uid => $tt_news
				)
			);
			if ($addCat) {
				$sort = 64;
				#t3lib_div::debug($categories, '$categories');
				$cat_mm = array();
				foreach ($categories as $category) {
					$cat_mm[uniqid('NEW')] = array(
						'uid_local' => $uid,
						'uid_foreign' => $category,
						'sorting' => $sort
					);
					$sort += 64;
				}
				$datamap['tt_news_cat_mm'] = $cat_mm;
			}

			// Create TCEmain instance
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			/* @var $tce t3lib_TCEmain */
			$tce->start($datamap, null);
			$tce->process_datamap();

			#print_r($datamap);

		} else {

			global $TYPO3_DB;
			$TYPO3_DB->exec_INSERTquery('tt_news', $tt_news);
			$uid = $TYPO3_DB->sql_insert_id();

			// Set category in table tt_news_cat_mm with UID of new record
			if ($addCat) {
				$sort = 64;
				foreach ($categories as $category) {
					$cat_mm = array(
						'uid_local' => $uid,
						'uid_foreign' => $category,
						'sorting' => $sort
					);
					$TYPO3_DB->exec_INSERTquery('tt_news_cat_mm', $cat_mm);
					$sort += 64;
				}
			}

			// Update refindex after DB insert
			$ref = t3lib_div::makeInstance('t3lib_refindex');
			/* var $ref t3lib_refindex */
			$ref->updateRefIndexTable('tt_news',$uid);

			if (!$disableLogging) {
				$logmsg = 'News item created: ' . ($addCat ? '[cat: ' . implode(',' , $categories) . '] ' : '') . '"' .
					substr($tt_news['title'], 0, 50) . '", created on page (pid '. $tt_news['pid'] . ')';
			}
			$GLOBALS['BE_USER']->simplelog($logmsg, $this->extKey);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']);
}
?>