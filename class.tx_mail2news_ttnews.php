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
 * @author	Loek Hilgersom <typo3extensions@netcoop.nl>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

class tx_mail2news_ttnews {

	public $extconf;

	/*
	*	Construct new object
	*/
	/*
	function __construct() {
	}
	*/

	function store_news($newsitem, $folderpid, $hide) {
	
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

		$TYPO3_DB->exec_INSERTquery('tt_news',$newsitem);
		
		// Set category in table tt_news_cat_mm with UID of new record
		if ($addCat) {
			$catmm = array(
				'uid_local' => $TYPO3_DB->sql_insert_id(),
				'uid_foreign' => $category_id,
				'sorting' => 1,
				//'tablenames' => ''
			);
			$TYPO3_DB->exec_INSERTquery('tt_news_cat_mm',$catmm);
		}
		
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_ttnews.php']);
}
?>