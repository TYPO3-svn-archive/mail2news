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

// Initialize database and configuration vars
include('class.tx_mail2news_imap.php');
include('class.tx_mail2news_ttnews.php');

class tx_mail2news_getmail extends t3lib_cli {

	var $extconf;

	function __construct($extConf) {
		$this->extconf = $extConf;
	}

	function getmail() {
		
		$extConf = $this->extconf;
		// new objects
		$imap = t3lib_div::makeInstance('tx_mail2news_imap');
		$news = t3lib_div::makeInstance('tx_mail2news_ttnews');

		if(isset($TYPO3_CONF_VARS['BE']['forceCharset']) && $TYPO3_CONF_VARS['BE']['forceCharset'] <> '') {
			$imap->set_targetcharset(strtoupper($TYPO3_CONF_VARS['BE']['forceCharset']));
		} else {
			// if not set, use default TYPO3 charset
			$imap->set_targetcharset('ISO-8859-1');
		}
		
		$imap->imap_connect($extConf['mail_server'], $extConf['mail_username'], $extConf['mail_password'], $extConf);
		$itemadded = FALSE;
		$count = $imap->imap_count_headers($imap);
		
		for ($msgno = 1; $msgno <= $count; $msgno++) {
		
			$header = $imap->imap_get_message_header($msgno);
			if ($this->matchemail($extConf['allowed_senders'], $header['fromemail'])) {
		
				$bodyparts = $imap->imap_get_message_body($msgno);
				$body = $this->storebodyparts($bodyparts);
				$msg = array_merge($header, $body);
		
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

				$news->store_news($newsitem, $extConf['pid'], $extConf['hide_by_default']);
				// echo actions for cron log file
				echo date("Y-m-d H:i:s ") . 'News item created: "' . $newsitem["title"] . '", ' . $newsitem["author"] . "\n";
				$itemadded = TRUE;
				
				// mark emails for deletion from server
				if ($extConf['delete_after_download']) {
					$imap->imap_delete_message($msgno);
				}
			}
			elseif ($extConf['delete_rejected_mail']) {
				$imap->imap_delete_message($msgno);
			}
		
		}
		
		// delete all read emails from server and close connection
		$imap->imap_disconnect();
		
		// Clear page cache for pages set in extConf, if new records are not hidden
		if(!$extConf['hide_by_default'] && isset($extConf['clearCacheCmd']) && $itemadded) {
			$this->clearpagecache($extConf['clearCacheCmd']);
		}
		
		unset($header,$body,$msg,$newsitem,$TYPO3_CONF_VARS,$TYPO3_DB);
		
	}
	/*
	 * 	Check if $email matches one of the allowed email address parts $match
	 * 	Input:	$match (str)	comma separated parts of emailaddress,
	 * 			e.g. '@email.com, .nl' matches emails from email.com and from .nl domains
	 * 			$email (str)	email address to be checked
	 *	Output:	boolean
	 */
	
	function matchemail($match,$email) {
		$allowed_senders = explode(',',$match);
		foreach ($allowed_senders as $part) {
			if (stripos($email,$part) !== FALSE) return TRUE;
		}
		return FALSE;
	}
	
	/*
	 *	Clear page cache of $pid_list
	 *	$pid_list can be a comma separated list of id's
	 */
	
	function clearpagecache($pid_list) {
		global $TYPO3_DB;
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$pid_array = explode(',',$pid_list);
		foreach ($pid_array as $pid) {
			$tce->clear_cacheCmd($pid);
		}
	}
	
	/*
	 * Sort data from 1 or more parts (multipart) of email message
	 * Separate text parts, images and other attachments
	 * Store images in PATH_uploads_pics
	 * Store other attachments in PATH_uploads_media
	 * Input: $bodyparts array of arrays, with properties of each message part
	 * Output: array(
	 * 				bodytext => (optionally concatenated) text of text parts
	 * 				imagefilenames => comma separated str
	 * 				attachmentfilenames => comma separated str
	 * 			)
	 */
		
	function storebodyparts($bodyparts) {
		
		$result = array(
			'bodytext' => '',
			'imagefilenames' => '',
			'attachmentfilenames' => ''
		);
		$imgs = 0;
		$atts = 0;
		
		$imageextensions = explode(',', strtolower($this->extconf['imageextensions']));
		$allowedextensions = explode(',', strtolower($this->extconf['allowedextensions']));
		
		foreach($bodyparts as $part) {
			
			if($part['is_text']) {

				// Takes only first text-part of multipart messages, or optionally concatenate text parts
            	$result['bodytext'] .= ($result['bodytext'] == '' || $this->extconf['concatenate_text_parts'] ? $part['content'] : '');

			}
			elseif($part['is_attachment']) {
	
				// check file extension
				// store attachment in pics or media
				// add filename to imagefilenames or attachmentfilenames
				
				$file = pathinfo($part['filename']);
				$fileext = strtolower($file['extension']);
				$filename = $file['filename'] . '_' . substr(md5(time()),0,4);
				
				if ($fileext !== '' && in_array($fileext, $imageextensions)) {
					$this->saveattachment($filename, $fileext, PATH_uploads_pics, $part['content'], $this->extconf['max_image_size'], $result['imagefilenames'], $imgs);
				}
				elseif ($fileext !== '' && in_array($fileext, $allowedextensions)) {
					$this->saveattachment($filename, $fileext, PATH_uploads_media, $part['content'], $this->extconf['max_attachment_size'], $result['attachmentfilenames'], $atts);
				}
				
			}
		}
		return $result;
	}
	
	/*
	 * 	Save attachment in $savepath and append filename to (referenced) $filelist variable, increment ref $counter
	 */
	function saveattachment($filename, $fileext, $savepath, $attachment, $maxsize, &$filelist, &$counter) {
		
		if(strlen($attachment)<=$maxsize*1024) {
			$filename .= '_' . strval($counter+1) . '.' . $fileext;
			if (! $handle = @fopen($savepath . $filename, "w")) {
				die("No permission to write file, quitting ... \n");		// TODO: more subtle exception here
			}
			fwrite($handle, $attachment);
			fclose($handle);
			// append filename to $filelist (image- or attachmentfilenames)
			$filelist .= ($filelist=='' ? '' : ',') . $filename;
			$counter++;
		}
	
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_getmail.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_getmail.php']);
}
?>