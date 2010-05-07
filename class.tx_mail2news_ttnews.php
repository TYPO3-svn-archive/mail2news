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
	class tx_mail2news_ttnews {
		 
		public $extconf;
		 
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
		* [Describe function...]
		*
		* @param [type]  $newsitem: ...
		* @return [type]  ...
		*/
		function store_news($newsitem) {
			 
			global $TYPO3_DB;
			 
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
			 
			$TYPO3_DB->exec_INSERTquery('tt_news', $newsitem);
			 
			// Set category in table tt_news_cat_mm with UID of new record
			if ($addCat) {
				$catmm = array(
				'uid_local' => $TYPO3_DB->sql_insert_id(),
					'uid_foreign' => $category_id,
					'sorting' => 1,
					//'tablenames' => ''
				);
				$TYPO3_DB->exec_INSERTquery('tt_news_cat_mm', $catmm);
			}
			 
		}
	}
	 
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']);
	}
?>