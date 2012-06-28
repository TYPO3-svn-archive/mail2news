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
* Class for storing imported content into t3blog format
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
 *   50: class tx_mail2news_t3blog
 *   58:     function __construct()
 *   68:     function category_id($category)
 *   90:     function store_news($newsitem)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib . 'class.t3lib_tcemain.php');

class tx_mail2news_t3blog {

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
	 *  Check if categories exist and return category id's.
	 *  Input can be category names OR id's (if input is numeric, it's considered an id)
	 * @param	string	$categories: csv list of category id's or names
	 * @return	string/boolean	csv string category_ids, FALSE if no matching category
	 */
	function category_ids($categories) {
		global $TYPO3_DB;
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
					$condition = 'title LIKE ' . $TYPO3_DB->fullQuoteStr($category, 'tx_t3blog_cat');
				} else {
					continue;
				}
				$where .= ' OR ' . $condition;
				// Monstruous construction for preserving sort order of categories, but it works!
				$orderBy = 'IF(' . $condition . ', ' . $i++ . ', ' . $orderBy . ')';
			}
			$where .= ' )';
			$rows = $TYPO3_DB->exec_SELECTgetRows( 'uid', 'tx_t3blog_cat', $where, '', $orderBy, '', 'uid' );

			if (count($rows) >= 1) {
				$category_ids = implode ( ',' , array_keys($rows) );
			}
		}
		return $category_ids;
	}

	/**
	 * Checks if comma separated list of integers contains only integers
	 * Non-integers are stripped
	 * @param string $list	comma separated list of values
	 * @return string	comma separated list containing only integers
	 */
	function cleanCsvList ($list) {
		$list ? $array = explode(',' , $list) : $array=array();
		foreach ($array as $key=>$value) {
			if (is_numeric($value)) {
				$array[$key] = intval($value);
			} else {
				unset($array[$key]);
			}
		}
		return implode(',' , $array);
	}

	/**
	 * Store data in array $blogpost in DB table tt_news
	 *
	 * @param	[array]		$blogpost: ...
	 * @param	[boolean]	$useTceMain: if set, use TCEmain instead of direct DB inserts
	 * (requires properly configured cli BE-user with rights to save news records, experimental!)
	 * @return	VOID
	 */
	function store_record($item, $useTceMain=false, $disableLogging=false) {

		$t3blog_post = array(
			'date' => $item['datetime'],
			'content' => 1,
			'cat' => 0,
			'author' => $item['cruser_id'],
		);

		$tt_content = array(
			'sorting' => 1,
			'CType' => $item['image']!='' ? 'textpic' : 'text',
			'irre_parenttable' => 'tx_t3blog_post',
			'cruser_id' => $item['cruser_id'],
			'image_link' => '',
			'list_type' => '',
			'image' => NULL,
		);

		if (!$item['crdate']) $item['crdate'] = time();
		if (!$item['tstamp']) $item['tstamp'] = time();

		// authorfield in tx_t3blog_post is be_user id, so only allow author to be set from custom field if it's numeric
		if (!is_numeric($item['author'])) unset($item['author']);

		t3lib_div::loadTCA('tx_t3blog_post');
		t3lib_div::loadTCA('tt_content');
		// Automatically match fields from input array to matching TCA fields in relevant tables
		foreach ($item as $key => $value) {
			if (isset( $GLOBALS['TCA']['tx_t3blog_post']['columns'][$key] ))  $t3blog_post[$key] = $item[$key];
			if (isset( $GLOBALS['TCA']['tt_content']['columns'][$key] ))  $tt_content[$key] = $item[$key];
			if (in_array($key, array('pid', 'crdate', 'tstamp')))  $t3blog_post[$key] = $tt_content[$key] = $item[$key];
		}

		// Set category for this record?
		$addCat = isset($item['tx_mail2news_categories']);
		// tt_news field category in table tt_news contains no of categories
		if ($addCat) {
			$categories = explode ( ',' , $this->cleanCsvList( $item['tx_mail2news_categories'] ) );
			$t3blog_post['cat'] = count($categories);
		}

		if ($useTceMain) {

			global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
			$t3blog_uid = 'NEW_' . uniqid('');
			// Datamap for page and content
			$tt_content['irre_parentid'] = $t3blog_uid;
			$datamap = array(
				'tx_t3blog_post' => array(
					$t3blog_uid => $t3blog_post
				),
				'tt_content' => array(
					'NEW_' . uniqid('') => $tt_content
				),
			);
			if ($addCat) {
				$sort = 64;
				#t3lib_div::debug($categories, '$categories');
				$cat_mm = array();
				foreach ($categories as $category) {
					$cat_mm[uniqid('NEW')] = array(
						'uid_local' => $t3blog_uid,
						'uid_foreign' => $category,
						'sorting' => $sort
					);
					$sort += 64;
				}
				$datamap['tx_t3blog_post_cat_mm'] = $cat_mm;
			}

			// Create TCEmain instance
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			/* @var $tce t3lib_TCEmain */
			$tce->stripslashes_values = 0;
			$tce->start($datamap, array() );
			$tce->process_datamap();

			t3lib_div::debug($datamap, 'datamap');

		} else {

			global $TYPO3_DB;
			$TYPO3_DB->exec_INSERTquery('tx_t3blog_post', $t3blog_post);
			$t3blog_uid = $TYPO3_DB->sql_insert_id();

		#	$tt_content['bodytext'] = $TYPO3_DB->fullQuoteStr($tt_content['bodytext'], 'tx_t3blog_cat');
			$tt_content['irre_parentid'] = $t3blog_uid;
			$TYPO3_DB->exec_INSERTquery('tt_content', $tt_content);
			$tt_content_uid = $TYPO3_DB->sql_insert_id();

			// Set category in table tx_t3blog_post_cat_mm with UID of new record
			if ($addCat) {
				$sort = 1;
	#			$cat_mm_array = array();
				foreach ($categories as $category) {
					$cat_mm = array(
						'uid_local' => $t3blog_uid,
						'uid_foreign' => $category,
						'sorting' => $sort
					);
					$TYPO3_DB->exec_INSERTquery('tx_t3blog_post_cat_mm', $cat_mm);
					$sort++;

	#				$cat_mm_array[] = $cat_mm;
				}
			}

 			// Update refindex after DB insert
			$ref = t3lib_div::makeInstance('t3lib_refindex');
			/* var $ref t3lib_refindex */
			$ref->updateRefIndexTable('tx_t3blog_post',$t3blog_uid);
			$ref->updateRefIndexTable('tt_content',$tt_content_uid);


	#		$t3blog_post['uid'] = $t3blog_uid;
	#		t3lib_div::debug($t3blog_post,'post');
	#		$tt_content['uid'] = $tt_content_uid;
	#		t3lib_div::debug($tt_content,'content');
	#		t3lib_div::debug($categories,'categories');
	#		t3lib_div::debug($cat_mm_array,'cat_mm');

			if (!$disableLogging) {
				$logmsg = 'T3Blog item created: ' . ($addCat ? '[cat: ' . implode(',' , $categories) . '] ' : '') . '"' .
							substr($item['title'], 0, 50) . '", created on page (pid '. $item['pid'] . ')';
			}
			$GLOBALS['BE_USER']->simplelog($logmsg, $this->extKey);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_t3blog.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_t3blog.php']);
}
?>