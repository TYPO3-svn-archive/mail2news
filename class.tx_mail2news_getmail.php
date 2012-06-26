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
*   63:     function __construct()
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
include('class.tx_mail2news_t3blog.php');

class tx_mail2news_getmail extends t3lib_cli {

	var $conf;
	private $extKey = 'mail2news';

	function __construct() {
	}

	/**
	 * Read messages from mail account one by one and import into news records
	 *
	 * @param	array	configuration (from record and/or EM) for 1 importer
	 * @return	void
	 */
	function process_all_importers($emConf) {

		/**
		 * Modify configuration keys for backwards compatibility
		 * Change case to lower, and adjust 2 keynames
		 *
		 * $emConf is the configuration array as read from the extension manager (EM, stored in localconf.php)
		 * $importerConf are these settings merged with overrides from the mail2news importer records
		 */
		$emConf = array_change_key_case($emConf);
		// 'ssl' is not allowed as mysql fieldname!
		$emConf['use_ssl'] = $emConf['ssl'];
		$emConf['news_cruser_id'] = $emConf['cruser_id'];
		unset ($emConf['ssl'],$emConf['cruser_id']);

		$table = 'tx_mail2news_importer';
		// select all active records
		$where = '1=1' . t3lib_BEfunc::BEenableFields($table) . t3lib_BEfunc::deleteClause($table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where, '', 'sorting');

		if($GLOBALS['TYPO3_DB']->sql_num_rows($res)> 0) {
			// mail2news importer records found, execute import script for each record
			while (false !== ($ar = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$obligatory_parameters = array_intersect_key($ar, array_flip(array('pid', 'allowed_senders')));
				$mailbox_parameters = array_intersect_key($ar, array_flip(array('mail_server', 'mail_username', 'mail_password',
					'imap', 'usessl', 'self_signed_certificate', 'portno', 'delete_after_download', 'delete_rejected_mail')));
				$processing_parameters = array_intersect_key($ar, array_flip(array('concatenate_text_parts',
					'max_image_size', 'max_attachment_size', 'imageextensions', 'allowedextensions')));
				$record_parameters = array_intersect_key($ar, array_flip(array('record_type', 'default_category',
					'default_t3blog_category', 'cruser_id', 'hide_by_default', 'clearCacheCmd')));

				$importerConf = $this->override_parameters($emConf, $obligatory_parameters);
				if(($ar['override_sections']&1)==0) {
					$importerConf = $this->override_parameters($importerConf, $mailbox_parameters);
				}
				if(($ar['override_sections']&2)==0) {
					$importerConf = $this->override_parameters($importerConf, $processing_parameters);
				}
				if(($ar['override_sections']&4)==0) {
					$importerConf = $this->override_parameters($importerConf, $record_parameters);
				}

				$this->getmail($importerConf);
			}

		} else {
			// No active importer records found, just execute script once with EM configuration
			// (this is also how the extension worked until version 1.9.6)
			$this->getmail($emConf);
		}
		
	}

	function override_parameters($emConf, $override) {
		foreach ($override as $key=>$value) {
			if($value!=='') {
				$emConf[$key] = $value;
			}
		}
		return $emConf;
	}


	/**
	 * Read messages from mail account one by one and import into news records
	 *
	 * @param	array	configuration (from record and/or EM) for 1 importer
	 * @return	void
	 */
	function getmail($conf) {

		// new objects
		$imap = t3lib_div::makeInstance('tx_mail2news_imap');

		// instantiate record-object for the selected record_type, default to tt_news
		switch ($conf['record_type']) {
			// t3blog
			case 't3blog':
				if (t3lib_extMgm::isLoaded('t3blog')) {
					$record = t3lib_div::makeInstance('tx_mail2news_t3blog');
					// Override ttnews categories with t3blog cats, would prefer a generic solution, but it's
					// difficult to change TCA dynamically (field for selecting both news and blog categories)
					if (isset($conf['default_t3blog_category'])) {
						$conf['default_category'] = $conf['default_t3blog_category'];
					}
				} else {
					// throw exception
				}
				break;
			case 'tt_news':
			default:
				if ( t3lib_extMgm::isLoaded('tt_news') ) {
					$record = t3lib_div::makeInstance('tx_mail2news_ttnews');
				}
		}

		$this->conf = $conf;

		// Get the markers that define which values from the first lines of the email should go into which database fields
		$pageTSC = $this->getTSConfig($conf['pid']);

		// Use default charset if nothing configured
		$this->targetcharset = 'iso-8859-1';
		if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] <> '') {
			$this->targetcharset = strtolower($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
		}
		$imap->set_targetcharset($this->targetcharset);

		if ($imap->imap_connect($conf['mail_server'], $conf['mail_username'], $conf['mail_password'], $conf) !== FALSE) {
			$itemadded = FALSE;
			$count = $imap->imap_count_headers($imap);

			for ($msgno = 1; $msgno <= $count; $msgno++) {

				$header = $imap->imap_get_message_header($msgno);
				if ($this->matchemail($conf['allowed_senders'], $header['fromemail'])) {

					$bodyparts = $imap->imap_get_message_body($msgno);
					$body = $this->storebodyparts($bodyparts);
					$msg = array_merge($header, $body);

					// Read category selector and subheader, if present, from message body text
					$bodytext_array = explode("\r\n", $msg['bodytext']);

					// Get the marker values from the first lines of the email message
					// Parse $bodytext while it's still an array of message lines
					$marker_values = $this->get_marker_values($bodytext_array, $pageTSC['fieldMarkers.']);

					// Implode what's left of bodytext
					$bodytext = implode(chr(10), $bodytext_array);

					// Replace URLs and emails with <a href...> elements
					$bodytext = $this->link_plain_text_urls($bodytext);

					// add <br /> at end of lines and wrap the text in p-tags while we're at it
					$bodytext = '<p>' . str_replace(chr(10), '<br />', $bodytext) . '</p>';
					// Replace double line breaks with p-tags, and remove empty space in between (spc, nbsp or tab)
					$bodytext = preg_replace('/<br \/>(\ |\t|&nbsp;)*<br \/>/', '</p><p>', $bodytext);
					// Map email msg array to newsitem array
					$item = array(

						'bodytext' => $bodytext,
						'image' => $msg['imagefilenames'],
						'news_files' => $msg['attachmentfilenames'],
						'author' => $msg['fromname'],
						'title' => $msg['subject'],

						// newsitem fields below do not need to be encoded
						'author_email' => $msg['fromemail'],
						'datetime' => $msg['date'],

					// FOR TESTING:
					// 'datetime' => time(),

						// supply additional fields from configuration defaults
						'pid' => $conf['pid'],
						'hidden' => $conf['hide_by_default'],
						'cruser_id' => $conf['cruser_id']

					);

					// Add preset values  and values from custom marker/field combinations from TSConfig to record data
					// First merge with presets so they can also be used as default, then override with marker values
					$item = array_merge( $item, $pageTSC['fieldPresetValues.'] );
					$item = array_merge( $item, $marker_values );

					// Categories is a special case, they need to be processed
					$categories = $item['categories'];
					unset($item['categories']);

					// Check categories: first check if categories from message are valid,
					// if not, check configured default category (from EM config or importer record)
					if ($categories) $categories = $record->category_ids($categories);

					if (!$categories) {
						if (isset($conf['default_category'])) {
							$categories = $record->category_ids($conf['default_category']);
						}
					}
					// Set categories in item only if a valid category has been found
					if ($categories) $item['tx_mail2news_categories'] = $categories;

					$record->store_record($item, $conf['usetcemain']);

					// Set flag so that cache will be cleared when ready
					$itemadded = TRUE;

					// mark emails for deletion from server
					if ($conf['delete_after_download']) {
						$imap->imap_delete_message($msgno);
					}
				} elseif ($conf['delete_rejected_mail']) {
					$imap->imap_delete_message($msgno);
				}

			}

			// delete all read emails from server and close connection
			$imap->imap_disconnect();

			// Clear page cache for pages set in extConf, if new records are not hidden
			if (!$conf['hide_by_default'] && isset($conf['clearcachecmd']) && $itemadded) {
				$this->clearpagecache($conf['clearcachecmd']);
			}

			unset($header, $body, $msg, $item);
		}
	}


	/**
	 * Get Page TS-Config array for this extension for the given page id.
	 *
	 * @param	int		$id: current page id
	 * @return	array:		TSConfig array from mod.tx_{extKey} from pageTSC
	 */
	function getTSConfig($id) {
		$rootLineStruct = t3lib_BEfunc::BEgetRootLine($id);
		// get TSconfig
		$pagesTSC = t3lib_BEfunc::getPagesTSconfig($id, $rootLineStruct);
		return $pagesTSC['mod.']['tx_' . $this->extKey . '.'];
	}


	/**
	 * Collect the values for the fields configured in TSConfig from the first lines
	 * of the email message and store them in $marker_values[fieldname] = $value
	 * $bodytext will be stripped of all the lines until the last matching marker found
	 *
	 * @param	string		&$bodytext: text of the email message
	 * @param	array		$field_markers: array of fieldname => marker
	 * @return	array		$marker_values: array of fieldnames with the values that they should be filled with
	 */
	function get_marker_values(&$bodytext, $field_markers) {

		$marker_values = array();

		for ($i=0; $i < count($field_markers); $i++) {
			$parse_text = $bodytext;
			// Check if first line exists
			if (isset($parse_text[0])) {
				$current_line = $parse_text[0];
				array_shift($parse_text);
				foreach ($field_markers as $fieldname => $marker) {
					// Skip arrays (because they contain special properties)
					if (!is_array($marker)) {
						// Check if the current line matches one of the markers
						if (preg_match('/^' . quotemeta($marker) . "(.*)?$/", $current_line, $match)) {
							// If yes, add value to $marker_values
							$marker_value = trim($match[1]);
							$marker_values[$fieldname] = $marker_value;
							if (is_array($field_markers[$fieldname.'.']) && $field_markers[$fieldname.'.']['parseDate']) {
								$timestamp = strtotime($marker_value);
								// If recognized as a date format, assign the timestamp
								if ($timestamp)	$marker_values[$fieldname] = $timestamp;
							}
							// And strip all lines up until this one from $bodytext
							$bodytext = $parse_text;
						}
					}
				}
			}
		}
		return $marker_values;
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
	 * e.g. '@email.com, .nl' matches emails from email.com and from .nl domains
	 *
	 * @param	string		$match: comma separated parts of emailaddress
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
		//global $TYPO3_DB;

		// Initialize TCE
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->start('', '');

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

		$imageextensions = explode(',', strtolower($this->conf['imageextensions']));
		$allowedextensions = explode(',', strtolower($this->conf['allowedextensions']));

		foreach($bodyparts as $part) {

			if ($part['is_text']) {

				// Takes only first text-part of multipart messages, or optionally concatenate text parts
				$result['bodytext'] .= ($result['bodytext'] == '' || $this->conf['concatenate_text_parts'] ? $part['content'] : '');

			} elseif($part['is_attachment']) {

				// check file extension
				// store attachment in pics or media
				// add filename to imagefilenames or attachmentfilenames
				
				// Replace not supported characters from filenames: comma's
				$part['name'] = str_replace(',', '_', $part['name']);

				// Use 'name' instead of 'filename' because filename gets encoded differently
				$file = pathinfo($part['name']);
				$fileext = strtolower($file['extension']);
				$filename = $file['filename'];
				// Convert special characters in filenames to double-chars version like ae, etc.
				// Otherwise ImageMagick has troubles with them.
				$filename = $GLOBALS['LANG']->csConvObj->specCharsToASCII($this->targetcharset, $filename);

				if ($fileext !== '' && in_array($fileext, $imageextensions)) {
					//$this->saveattachment($filename, $fileext, PATH_uploads_pics, $part['content'], $this->conf['max_image_size'], $result['imagefilenames'], $imgs);
					$this->saveattachment($filename, $fileext, 'uploads/pics/', $part['content'], $this->conf['max_image_size'], $result['imagefilenames'], $imgs);
				} elseif ($fileext !== '' && in_array($fileext, $allowedextensions)) {
					//$this->saveattachment($filename, $fileext, PATH_uploads_media, $part['content'], $this->conf['max_attachment_size'], $result['attachmentfilenames'], $atts);
					$this->saveattachment($filename, $fileext, 'uploads/media/', $part['content'], $this->conf['max_attachment_size'], $result['attachmentfilenames'], $atts);
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
				$absfilename = t3lib_div::getFileAbsFileName($savepath . $savedfilename);
				$i++;
			}
			if (!t3lib_div::writeFile($absfilename,$attachment)) {
				// If attachments can't be saved, log error in syslog, store news without file, and continue
				$logmsg = 'No permission to write file attachment, path: ' . $savepath . ', filename: ' . $savedfilename;
				$error = 2;	// system error
				$GLOBALS['BE_USER']->simplelog($logmsg, $this->extKey, $error);
			} else {
				// append the savedfilename to $filelist (image- or attachmentfilenames)
				$filelist .= ($filelist == '' ? '' : ',') . $savedfilename;
				$counter++;
			}
		}
	}

	/**
	 * link_plain_text_urls: Parse plain text input, recognize urls and emailaddresses and replace them with links
	 * Based on: http://snippets.dzone.com/posts/show/6642 by Sean Murphy
	 *
	 * @param	[string]	$text: plain text with urls etc.
	 * @return	[string]	The same text, but with all urls replaced by proper links
	 */
	function link_plain_text_urls($text) {
		// Start off with a regex
		preg_match_all('#\b(?:(?:https?|ftps?)://[^.\s]+\.[^\s]+|(?:[^.\s/]+\.)+(?:museum|travel|[a-z]{2,4})(?:[:/][^\s]*)?)\b#i', $text, $matches);

		// Then clean up what the regex left behind
		$offset = 0;
		foreach($matches[0] as $url) {
			$url = htmlspecialchars_decode($url);

			// Remove trailing punctuation
			$url = rtrim($url, '.?!,;:\'"`');

			// Remove surrounding parens and the like
			preg_match('/[)\]>]+$/', $url, $trailing);
			if (isset($trailing[0])) {
				preg_match_all('/[(\[<]/', $url, $opened);
				preg_match_all('/[)\]>]/', $url, $closed);
				$unopened = count($closed[0]) - count($opened[0]);

				// Make sure not to take off more closing parens than there are at the end
				$unopened = ($unopened > strlen($trailing[0])) ? strlen($trailing[0]):$unopened;

				$url = ($unopened > 0) ? substr($url, 0, $unopened * -1):$url;
			}

			// Remove trailing punctuation again (in case there were some inside parens)
			$url = rtrim($url, '.?!,;:\'"`');

			// Make sure we didn't capture part of the next sentence
			preg_match('#((?:[^.\s/]+\.)+)(museum|travel|[a-z]{2,4})\b#i', $url, $url_parts);

			// Were the parts capitalized any?
			$last_part = (strtolower($url_parts[2]) !== $url_parts[2]) ? true:false;
			$prev_part = (strtolower($url_parts[1]) !== $url_parts[1]) ? true:false;

			// If the first part wasn't cap'd but the last part was, we captured too much
			if ((!$prev_part && $last_part)) {
				$url = substr_replace($url, '', strpos($url, '.'.$url_parts[2], 0));
			}

			// Capture the new TLD
			preg_match('#((?:[^.\s/]+\.)+)(museum|travel|[a-z]{2,4})#i', $url, $url_parts);

			$tlds = array('ac', 'ad', 'ae', 'aero', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'arpa', 'as', 'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'biz', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv', 'bw', 'by', 'bz', 'ca', 'cat', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'com', 'coop', 'cr', 'cu', 'cv', 'cx', 'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'edu', 'ee', 'eg', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gov', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'info', 'int', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jm', 'jo', 'jobs', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mil', 'mk', 'ml', 'mm', 'mn', 'mo', 'mobi', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'museum', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'name', 'nc', 'ne', 'net', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'org', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'pro', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'st', 'su', 'sv', 'sy', 'sz', 'tc', 'td', 'tel', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr', 'travel', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'yu', 'za', 'zm', 'zw');

			if (!in_array($url_parts[2], $tlds)) continue;

			// Call user specified func
			$modified_url = $this->linkify($url);

			// Replace it!
			$start = strpos($text, $url, $offset);
			$text = substr_replace($text, $modified_url, $start, strlen($url));
			$offset = $start + strlen($modified_url);
		}

		return $text;
	}

	function linkify($url) {
		$atag = $url;
		if (!preg_match('#^[a-z]+://#i', $url)) {
			if (strpos($url, '@')) {
				$atag = 'mailto://' . $url . '" class="mailtolink';
			} else {
				$atag = 'http://' . $url . '" class="extlink" target="_blank';
			}
		} else {
			$atag = $url . '" class="extlink" target="_blank';
		}
		return '<a href="' . $atag . '">' . $url . '</a>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_getmail.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_getmail.php']);
}
?>