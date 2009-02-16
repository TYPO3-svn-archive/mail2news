#!/usr/bin/php
<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Loek Hilgersom <typo3extensions@netcoop.nl>
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



// Initialize database and configuration vars
include('init.php');
include('class.tx_mail2news_imap.php');
include('class.tx_mail2news_ttnews.php');
include('class.tx_mail2news.php');

//require_once(t3lib_extMgm::extPath('mail2news').'class.tx_mail2news_imap.php');

// new objects
$imap = new tx_mail2news_imap();
$news = new tx_mail2news_ttnews($extConf);
$main = new tx_mail2news($extConf);

if(isset($TYPO3_CONF_VARS['BE']['forceCharset']) && $TYPO3_CONF_VARS['BE']['forceCharset'] <> '') {
	$imap->set_targetcharset(strtoupper($TYPO3_CONF_VARS['BE']['forceCharset']));
} else {
	// if not set, use default TYPO3 charset
	$imap->set_targetcharset('ISO-8859-1');
}
/*
$options = Array(
	'IMAP' => $extConf['IMAP'],
	'SSL' => $extConf['SSL'],
	'portno' => $extConf['portno'],
	'self_signed_certificate' => $extConf['self_signed_certificate']
);
*/
$imap->imap_connect($extConf['mail_server'], $extConf['mail_username'], $extConf['mail_password'], $extConf);

$itemadded = FALSE;

$count = $imap->imap_count_headers($imap);

for ($msgno = 1; $msgno <= $count; $msgno++) {

	$header = $imap->imap_get_message_header($msgno);

	if ($main->matchemail($extConf['allowed_senders'], $header['fromemail'])) {

		/*
		echo "--------------------------------------------------------------------------\n";
		foreach($header as $key=>$item) {
			echo '$header['. $key . '] = ' . $item . "\n";
		}
		echo "SUBJECT: " . $header['subject'];
		echo "\n";
		*/

		$bodyparts = $imap->imap_get_message_body($msgno);
		
		$body = $main->storebodyparts($bodyparts);
		
		//print_r($body);
		
		$msg = array_merge($header, $body);

		/*
		foreach($body as $key=>$item) {
			if($key!='bodytext') echo '$body['. $key . '] = ' . $item . "\n";
		}
		echo "\n";
		foreach($msg as $key=>$item) {
			echo '$msg['. $key . '] = ' . $item . "\n";
		}
		echo "\n";
		*/

		// Map email array to newsitem array
		$newsitem = Array();
		$newsitem['author'] = $msg['fromname'];
		$newsitem['datetime'] = $msg['date'];
		
		// FOR TESTING:
		//$newsitem['datetime'] = time();
		
		$newsitem['title'] = $msg['subject'];
		//$newsitem['subheader'] = $msg['subheader'];
		$newsitem['bodytext'] = $msg['bodytext'];
		$newsitem['author_email'] = $msg['fromemail'];
		$newsitem['image'] = $msg['imagefilenames'];
		$newsitem['news_files'] = $msg['attachmentfilenames'];

		if (isset($extConf['category_id'])) {
			$newsitem['category'] = $extConf['category_id'];
		}

		//$msg['category'];

		$news->store_news($newsitem);
		// echo actions for logfile
		echo date("Y-m-d H:i:s ") . 'News item created: "' . $newsitem["title"] . '", ' . $newsitem["author"] . "\n";
		$itemadded = TRUE;
		
		// mark emails for deletion from server
		if ($extConf['delete_after_download']) {
			$imap->imap_delete_current_message();
		}
	}
	elseif ($extConf['delete_rejected_mail']) {
		$imap->imap_delete_current_message();
	}
	else {
		echo "email doesnt match enzo.\n";
	}
}

// delete all read emails from server and close connection
$imap->imap_disconnect();

// Clear page cache for pages set in extConf, if new records are not hidden
if(!$extConf['hide_by_default'] && isset($extConf['clearCacheCmd']) && $itemadded) {
	$main->clearpagecache($extConf['clearCacheCmd']);
	
	//echo "cache cleared for " . $extConf['clearCacheCmd'] . "\n";
}

unset($header,$body,$msg,$newsitem,$TYPO3_CONF_VARS,$TYPO3_DB);

?>