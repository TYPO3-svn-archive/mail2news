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
 *   59: class tx_mail2news_getmail extends t3lib_cli
 *   63:     function __construct($extConf)
 *   72:     function getmail()
 *  185:     function getparameterline(&$text, $label)
 *  204:     function matchemail($match, $email)
 *  217:     function clearpagecache($pid_list)
 *  240:     function storebodyparts($bodyparts)
 *  290:     function saveattachment($filename, $fileext, $savepath, $attachment, $maxsize, &$filelist, &$counter)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// Initialize database and configuration vars
	include('class.tx_mail2news_imap.php');
	include('class.tx_mail2news_ttnews.php');

	class tx_mail2news_getmail extends t3lib_cli {

		var $extconf;

		function __construct($extConf) {
			$this->extconf = $extConf;
		}

		/**
 * Read messages from mail account one by one and import into news records
 *
 * @return	void
 */
		function getmail() {

			$extConf = $this->extconf;
			// new objects
			$imap = t3lib_div::makeInstance('tx_mail2news_imap');
			$news = t3lib_div::makeInstance('tx_mail2news_ttnews');

			// Use default charset if nothing configured
			$this->targetcharset = 'iso-8859-1';
			if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] <> '') {
				$this->targetcharset = strtolower($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
			}
			$imap->set_targetcharset($this->targetcharset);

			$imap->imap_connect($extConf['mail_server'], $extConf['mail_username'], $extConf['mail_password'], $extConf);
			$itemadded = FALSE;
			$count = $imap->imap_count_headers($imap);

			for ($msgno = 1; $msgno <= $count; $msgno++) {

				$header = $imap->imap_get_message_header($msgno);
				if ($this->matchemail($extConf['allowed_senders'], $header['fromemail'])) {

					$bodyparts = $imap->imap_get_message_body($msgno);
					$body = $this->storebodyparts($bodyparts);
					$msg = array_merge($header, $body);

					// Read category selector and subheader, if present, from message body text
					$bodytext = explode("\r\n", $msg['bodytext']);
					$category = FALSE;
					$subheader = FALSE;
					for ($i = 1; $i <= 2; $i++) {
						if (!$category) {
							$category = $this->getparameterline($bodytext, $extConf['category_identifier']);
						}
						if (!$subheader) {
							$subheader = $this->getparameterline($bodytext, $extConf['subheader_identifier']);
						}
					}

					// Map email msg array to newsitem array
					$newsitem = Array();
					// Implode what's left of bodytext, add <br /> at end of lines and
					// wrap the text in p-tags while we're at it
					$newsitem['bodytext'] = '<p>' . implode("<br />", $bodytext) . '</p>';
					// Replace double line breaks with p-tags, and remove empty space in between (spc, nbsp or tab)
					$newsitem['bodytext'] = preg_replace('/<br \/>(\ |\t|&nbsp;)*<br \/>/', '</p><p>', $newsitem['bodytext']);

					$newsitem['short'] = $subheader;
					$newsitem['image'] = $msg['imagefilenames'];
					$newsitem['news_files'] = $msg['attachmentfilenames'];
					$newsitem['author'] = $msg['fromname'];
					$newsitem['title'] = $msg['subject'];

					// newsitem fields below do not need to be encoded
					$newsitem['author_email'] = $msg['fromemail'];
					$newsitem['datetime'] = $msg['date'];

					// FOR TESTING:
					//$newsitem['datetime'] = time();

					// supply additional fields from configuration defaults
					$newsitem['pid'] = $extConf['pid'];
					$newsitem['hidden'] = $extConf['hide_by_default'];
					$newsitem['cruser_id'] = $extConf['news_cruser_id'];

					// Check news category: first check if category from message is valid,
					// if not, check default category from em config.
					if ($category) $category = $news->category_id($category);

					if (!$category) {
						if (isset($extConf['default_category'])) {
							$category = $news->category_id($extConf['default_category']);
						}
					}
					// Set news category only if a valid category has been found
					if ($category) $newsitem['category'] = $category;
					$news->store_news($newsitem);
					// echo actions for cron log file
					echo date('Y-m-d H:i:s ') . 'News item created: [cat ' . $category . '] "' . $newsitem['title'] . '", ' . $newsitem['author'] . "\n";
					$itemadded = TRUE;

					// mark emails for deletion from server
					if ($extConf['delete_after_download']) {
						$imap->imap_delete_message($msgno);
					}
				} elseif ($extConf['delete_rejected_mail']) {
					$imap->imap_delete_message($msgno);
				}

			}

			// delete all read emails from server and close connection
			$imap->imap_disconnect();

			// Clear page cache for pages set in extConf, if new records are not hidden
			if (!$extConf['hide_by_default'] && isset($extConf['clearcachecmd']) && $itemadded) {
				$this->clearpagecache($extConf['clearcachecmd']);
			}

			unset($header, $body, $msg, $newsitem, $TYPO3_CONF_VARS, $TYPO3_DB);

		}

		/**
 * Check if first string in Array of strings &$text starts with $label
 * if yes, return the rest of that line, and remove the first string from &$text
 * else, return false
 *
 * @param	array		$$text: array of strings (typically bodytext of email msg)
 * @param	string		$label: identifier for special first lines
 * @return	string/false:		return the content part of first line if match, otherwise false
 */
		function getparameterline(&$text, $label) {
			if (strlen($label) > 0 && isset($text[0])) {
				if (preg_match('/^' . quotemeta($label) . "(.*)?$/", $text[0], $match)) {
					array_shift($text);
					return trim($match[1]);
				}
			}
			return FALSE;
		}

		/**
 * Check if $email matches one of the allowed email address parts $match
 *
 * 						 matches emails from email.com and from .nl domains
 *
 * @param	string		$match: comma separated parts of emailaddress, e.g. '@email.com, .nl'
 * @param	string		$email: email address to be checked
 * @return	boolean
 */
		function matchemail($match, $email) {
			$allowed_senders = explode(',', $match);
			foreach ($allowed_senders as $part) {
				if (stripos($email, $part) !== FALSE) return TRUE;
			}
			return FALSE;
		}

		/*
		* Clear page cache of $pid_list
		* $pid_list can be a comma separated list of id's
		*/

		function clearpagecache($pid_list) {
			global $TYPO3_DB;
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$pid_array = explode(',', $pid_list);
			foreach ($pid_array as $pid) {
				$tce->clear_cacheCmd($pid);
			}
		}

		/**
 * Sort data from 1 or more parts (multipart) of email message
 * Separate text parts, images and other attachments
 * Store images in PATH_uploads_pics
 * Store other attachments in PATH_uploads_media
 *
 *     bodytext => (optionally concatenated) text of text parts
 *     imagefilenames => comma separated str
 *     attachmentfilenames => comma separated str
 *    )
 *
 * @param	array		$bodyparts:  array of arrays, with properties of each message part
 * @return	array(
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

				if ($part['is_text']) {

					// Takes only first text-part of multipart messages, or optionally concatenate text parts
					$result['bodytext'] .= ($result['bodytext'] == '' || $this->extconf['concatenate_text_parts'] ? $part['content'] : '');

				} elseif($part['is_attachment']) {

					// check file extension
					// store attachment in pics or media
					// add filename to imagefilenames or attachmentfilenames

					// Use 'name' instead of 'filename' because filename gets encoded differently
					$file = pathinfo($part['name']);
					$fileext = strtolower($file['extension']);
					$filename = $file['filename'];
					// Convert special characters in filenames to double-chars version like ae, etc.
					// Otherwise ImageMagick has troubles with them.
					$filename = $GLOBALS['LANG']->csConvObj->specCharsToASCII($this->targetcharset, $filename);

					if ($fileext !== '' && in_array($fileext, $imageextensions)) {
						//$this->saveattachment($filename, $fileext, PATH_uploads_pics, $part['content'], $this->extconf['max_image_size'], $result['imagefilenames'], $imgs);
						$this->saveattachment($filename, $fileext, 'uploads/pics/', $part['content'], $this->extconf['max_image_size'], $result['imagefilenames'], $imgs);
					} elseif ($fileext !== '' && in_array($fileext, $allowedextensions)) {
						//$this->saveattachment($filename, $fileext, PATH_uploads_media, $part['content'], $this->extconf['max_attachment_size'], $result['attachmentfilenames'], $atts);
						$this->saveattachment($filename, $fileext, 'uploads/media/', $part['content'], $this->extconf['max_attachment_size'], $result['attachmentfilenames'], $atts);
					}

				}
			}
			return $result;
		}

		/*
		*  Save attachment in $savepath and append filename to (referenced) $filelist variable, increment ref $counter
		*/
		function saveattachment($filename, $fileext, $savepath, $attachment, $maxsize, &$filelist, &$counter) {

			if (strlen($attachment) <= $maxsize * 1024) {
				// $savedfilename is the filename as it will be saved, including _x counter in case
				// file already exists
				$savedfilename = $filename . '.' . $fileext;
				$absfilename = t3lib_div::getFileAbsFileName($savepath . $savedfilename);
				$i = 1;
				while (file_exists($absfilename)) {
					$savedfilename = $filename . '_' . $i . '.' . $fileext;
					$absfilename = t3lib_div::getFileAbsFileName($savepath . $filename . '_' . $i . '.' . $fileext);
					$i++;
				}
				if (!t3lib_div::writeFile($absfilename,$attachment)) {
					die(date('Y-m-d H:i:s ') . "No permission to write file, quitting ... \n");
					// TODO: more subtle exception here
				}
				// append the savedfilename to $filelist (image- or attachmentfilenames)
				$filelist .= ($filelist == '' ? '' : ',') . $savedfilename;
				$counter++;
			}

		}

	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_getmail.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_getmail.php']);
	}
?>