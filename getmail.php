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

// Save image to uploads/pics and return the random filename
function saveimage($part,$img,$date,&$imagecounter) {
	$filename = '';
	if ($part->subtype == "JPEG" || $part->subtype == "OCTET-STREAM") {
		$filename = md5($date) . "_$imagecounter.jpg";
		if (! $handle = @fopen(PATH_uploads_pics . $filename, "w"))
			die("No permission to write image, quitting ...");
		fwrite($handle, imap_base64($img));
		fclose($handle);
		//create_thumbnail(md5($date) . "_$imagecounter.jpg");
		$imagecounter++;
	}
	return $filename;
}

function matchemail($match,$email) {
	$allowed_senders = explode(',',$match);
	foreach ($allowed_senders as $part) {
		if (stripos($email,$part) !== FALSE) return TRUE;
	}
	return FALSE;
}

function getmail($extConf) {

	$result = array();
	$resultcount = 1;
	
	if (! $mail = imap_open( "{" . $extConf['mail_server'] . ":110/pop3/notls}INBOX", $extConf['mail_username'], $extConf['mail_password']))
		die("could not connect to mailserver. Quitting...");
	$headerstrings = imap_headers($mail);

	foreach ($headerstrings as $headerstring) {
		$multipart = FALSE;
		$imagecounter = 1;
		preg_match("/[0-9]/", $headerstring, $number);
		// parse message and sender
		$header = imap_fetchheader($mail, $number[0]);
		preg_match("/Date: (.*)?[\+|-]/", $header, $date);
		// Translate $date to unix timestamp
		$date = strtotime(htmlentities($date[1]));
		$headerinfo = imap_headerinfo($mail, $number[0], 256, 256);
		$from_email = $headerinfo->from[0]->mailbox . "@" . $headerinfo->from[0]->host;
		$decode = imap_mime_header_decode($headerinfo->from[0]->personal);
		$from_name = $decode[0]->text;
		$decode = imap_mime_header_decode($headerinfo->fetchsubject);
		$subject = $decode[0]->text;
		$imap = imap_fetchstructure($mail, $number[0]);
		
		//echo "Recent: " . $headerinfo->Recent . "\nUnseen: " . $headerinfo->Unseen . "\nSeen: " . $headerinfo->Seen . "\n";
		//$status = imap_setflag_full($mail, $number[0], "\\Seen \\Recent");
		//echo "Status: " . $status . "\n";
		
		if (! empty($imap->parts)) {
			$filename = '';
			for($i = 0, $j = count($imap->parts); $i < $j; $i++) {
				$part = $imap->parts[$i];
				if ($part->bytes < $extConf['max_image_size']*1024) {
					$msg = imap_fetchbody($mail, $number[0], $i + 1);
					// save image
					if ($part->disposition == ATTACHMENT || $part->type == TYPEIMAGE || ($part->type == TYPEAPPLICATION && $part->subtype <> "SMIL")) {
						// If filename not empty, then first prepend a comma, then add the next filename
						if ($filename!=='') $filename .= ',';
						$filename .= saveimage($part,$msg,$date,$imagecounter);
					}
					elseif (($part->type == TYPETEXT || $part->type == TYPEMULTIPART || $part->disposition == INLINE) && ! $multipart) {
						$body = $msg;
						if (preg_match("#/9j/#", $body)) {
							preg_match("#.*(/9j/.*)--.*#Ums", $body, $buffer);
							// If filename not empty, then first prepend a comma, then add the next filename
							if ($filename!=='') $filename .= ',';
							$filename .= saveimage($part,$buffer[1],$date,$imagecounter);
						}   
						if (preg_match("/#(.*)#/Ums", $body))
							$multipart = TRUE;
						elseif ($part->encoding == 3)	// LH: added check for BASE64 encoding before actually decoding the message body!
							$body = imap_base64($body);
						
					}
					if ($part->subtype == "SMIL" && ! $multipart)
						$body = imap_base64($body);
				}
				else
					$body = '';
			} 
		}
		else
			$body = imap_body($mail, $number[0]);


		// extract message, filter message body to $message[1]
		if (preg_match("/=23/", $body))
			preg_match("/=23(.*)=23/Ums", $body, $message);
		elseif (preg_match("/Ums", $body))
			preg_match("/#(.*)#/Ums", $body, $message);
		else
			// no encoding
			$message[1] = $body;	//preg_match("#(.*)#", $body, $message);
		
		// handle soft and hard CRs
		$message[1] = preg_replace("/ \r\n/", " ", trim($message[1]));
		$message[1] = preg_replace("/\n/", "<br />", trim($message[1]));	

		// decode message according to charset
		if (preg_match("/=C3/", $message[1]))
			$message[1] = utf8_decode(quoted_printable_decode($message[1]));
		else
			$message[1] = quoted_printable_decode($message[1]);
		
		$message[1] = preg_replace("/ = /", " ", $message[1]);
		

		// write message, author and date into appropriate arrays
		
		//if ($message[1] != "" && preg_match("/({$extConf['allowed_senders']})/", $from_email)) {
		if (matchemail($extConf['allowed_senders'], $from_email)) {
			$result[$resultcount]['author'] = $from_name;
			$result[$resultcount]['datetime'] = $date;
			$result[$resultcount]['title'] = $subject;
			$result[$resultcount]['bodytext'] = $message[1];
			$result[$resultcount]['author_email'] = $from_email;
			$result[$resultcount]['image'] = $filename;
			$resultcount++;
			
			// mark emails for deletion from server
			if ($extConf['delete_after_download'])
				imap_delete($mail, $number[0]);			//#
		}
		
		elseif ($extConf['delete_rejected_mail'])
			imap_delete($mail, $number[0]);			//#
	}
	// delete all read emails from server and close connection
	imap_expunge($mail);
	imap_close($mail);
	
	unset($headerstrings);
	return $result;
}


// Initialize database and configuration vars
require_once ("init.php");

$result = getmail($extConf);

/*
// For testing
foreach ($result as $newsitem) {
//$newsitem = $extConf;
	foreach ($newsitem as $key=>$value) {
		echo "newsitem[$key] = $value\n";
	}
}
*/

// Write all correctly received messages to tt_news database records
foreach ($result as $newsitem) {

	// supply additional fields from configuration defaults
	$newsitem['pid'] = $extConf['pid'];
	$newsitem['hidden'] = $extConf['hide_by_default'];
	// Set category for this record?
	$addCat = isset($extConf['category_id']);
	// tt_news field category in table tt_news contains no of categories
	if ($addCat) {
		$newsitem['category'] = 1;
	} else {
		$newsitem['category'] = 0;
	}
	$newsitem['cruser_id'] = $extConf['cruser_id'];
	$newsitem['tstamp'] = $newsitem['crdate'] = time();

	$TYPO3_DB->exec_INSERTquery('tt_news',$newsitem);
	
	// Set category in table tt_news_cat_mm with UID of new record
	if ($addCat) {
		$catmm = array(
			'uid_local' => $TYPO3_DB->sql_insert_id(),
			'uid_foreign' => $extConf['category_id'],
			'sorting' => 1,
			//'tablenames' => ''
		);
		$TYPO3_DB->exec_INSERTquery('tt_news_cat_mm',$catmm);
	}
	// echo actions for logfile
	echo date("Y-m-d H:i:s ") . 'News item created: "' . $newsitem["title"] . '", ' . $newsitem["author"] . "\n";
}

unset($newsitem);
unset($result);
unset($extConf);
unset($TYPO3_CONF_VARS);
unset($TYPO3_DB);
?>