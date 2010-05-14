<?php
	//#!/usr/bin/php
	 
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
	*/
	 
	include('init.php');
	include('class.tx_mail2news_getmail.php');

	function override_parameters($extConf, $override) {
		foreach ($override as $key=>$value) {
			if($value!=='') {
				$extConf[$key] = $value;
			}
		}
		return $extConf;
	}
	 
	$class = t3lib_div::makeInstanceClassName('tx_mail2news_getmail');

	/** Modify configuration keys for backwards compatibility
	 *  Change case to lower, and adjust 2 keynames
	 */
	$extConf = array_change_key_case(unserialize($TYPO3_CONF_VARS['EXT']['extConf']['mail2news']), CASE_LOWER);
	// 'ssl' is not allowed as mysql fieldname!
	$extConf['use_ssl'] = $extConf['ssl'];
	$extConf['news_cruser_id'] = $extConf['cruser_id'];
	unset ($extConf['ssl'],$extConf['cruser_id']);

	$table = 'tx_mail2news_importer';
	// select all active records
	$where = '1=1' . t3lib_BEfunc::BEenableFields($table) . t3lib_BEfunc::deleteClause($table);
//	echo $where . chr(10);
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where, '', 'sorting');
	if($res !== false) {
		// mail2news importer records found, execute import script for each record
		while (false !== ($ar = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$obligatory_parameters = array_intersect_key($ar, array_flip(array('pid', 'allowed_senders')));
			$mailbox_parameters = array_intersect_key($ar, array_flip(array('mail_server', 'mail_username', 'mail_password',
				'imap', 'usessl', 'self_signed_certificate', 'portno', 'delete_after_download', 'delete_rejected_mail')));
			$processing_parameters = array_intersect_key($ar, array_flip(array('concatenate_text_parts',
				'[max_image_size', 'max_attachment_size', 'imageextensions', 'allowedextensions')));
			$newsrecord_parameters = array_intersect_key($ar, array_flip(array('category_identifier', 'subheader_identifier',
				'default_category', 'news_cruser_id', 'hide_by_default', 'clearCacheCmd')));

			$importerConf = override_parameters($extConf, $obligatory_parameters);
			if(($ar['override_sections']&1)==0) {
				$importerConf = override_parameters($importerConf, $mailbox_parameters);
			}
			if(($ar['override_sections']&2)==0) {
				$importerConf = override_parameters($importerConf, $processing_parameters);
			}
			if(($ar['override_sections']&4)==0) {
				$importerConf = override_parameters($importerConf, $newsrecord_parameters);
			}


		//	print_r($importerConf);

			$main = new $class($importerConf);
			$main->getmail();
		}

	} else {
		// No active importer records found, just execute script once with EM configuration
		// (this is also how the extension worked until version 1.9.6)
//		echo 'Execute with EM configuration' . chr(10);
//		print_r($extConf);
		$main = new $class($extConf);
		$main->getmail();
	}


	

	
	 
?>