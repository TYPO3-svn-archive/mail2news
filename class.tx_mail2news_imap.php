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

class tx_mail2news_imap {

	protected $mail;
	var $targetcharset;

	// message body data
	protected $parts;
	protected $partno;
	
	/*
	*	Construct new IMAP object
	*/
	
	function __construct() {
	}

	/*
	*	Initialize IMAP connection
	* 	Input:	mailserver data + options array:
	* 		IMAP => boolean
	* 		SSL  => boolean
	* 		portno => integer, uses defaults if 0, empty or not set
	* 	Output:	email inbox headerstrings (array)
	*/
	
	function imap_connect($mail_server, $mail_username, $mail_password, $options) {
		
		$portno = $options['portno'];
		if ( !isset($portno) || $portno == '' || $portno == 0 ) {
			if ($options['IMAP']) $portno = ($options['SSL'] ? 993 : 143);
			else $portno = ($options['SSL'] ? 995 : 110);
		}
		
		$mailboxoptions = ':' . $portno . ($options['IMAP'] ? '/imap' : '/pop3') . ($options['SSL'] ? '/ssl' : '') . ($options['self_signed_certificate'] ? '/novalidate-cert' : '') . '/notls';
		
		if (! $this->mail = imap_open( '{' . $mail_server . $mailboxoptions . '}INBOX', $mail_username, $mail_password)) {
			die('Could not connect to mailserver. Quitting...' . "\n");
		}
	}
	
	/*
	 * 	Returns the number of messages in inbox
	 */
	function imap_count_headers() {
		return count(imap_headers($this->mail));
	}

	/*
	 * 	Delete message $msgno
	 */
	function imap_delete_message($msgno) {
		imap_delete($this->mail, $msgno);
	}

	function imap_disconnect () {
		// really remove messages marked as deleted
		imap_expunge($this->mail);
		// close connection
		imap_close($this->mail);
	}

	/*
	 * 	Sets the target character set to which appropriate text and header fields will be converted
	 */
	function set_targetcharset($charset) {
		$this->targetcharset = $charset;
	}

	/*
	 * 	Checks if charset is different from target charset, if so, convert
	 * 	windows-1252 is treated as ISO-8859-1, default as US-ASCII
	 */
	function convert_to_targetcharset($string, $currentcharset) {
		if (strcasecmp($currentcharset, 'windows-1252') == 0) {
			$currentcharset = 'ISO-8859-1';
		}
		if (strcasecmp($currentcharset, 'default') == 0 || $currentcharset == '') {
			$currentcharset = 'US-ASCII';
		}
		if (strcasecmp($currentcharset, $this->targetcharset) <> 0) {
			$string = mb_convert_encoding($string, $this->targetcharset, $currentcharset);
		}
		return $string;
	}


	/*
	 * 	Fetch header of message $msgno and return header fields:
	 * 	If charset is defined in header, the fields from name and subject are
	 * 	converted to target charset.
	 * 	Input:	$msgno
	 * 	Output:	$header Array
	 * 		fromemail => from: email address,
	 * 		fromname => from: full name, 
	 * 		date	=> unix timestamp,
	 * 		subject	=> message subject
	 * 	
	 */
	function imap_get_message_header($msgno) {

		$header = Array();

		// parse message and sender
		$headertext = imap_fetchheader($this->mail, $msgno);
		$headerinfo = imap_headerinfo($this->mail, $msgno, 256, 256);
		
		// Extract from_email
		$from = $headerinfo->from[0];
		$header['fromemail'] = $from->mailbox . '@' . $from->host;
		// Extract from_name
		$decode = imap_mime_header_decode($from->personal);
		$header['fromname'] = $this->convert_to_targetcharset($decode[0]->text, $decode[0]->charset);
		//$header['charset'] = $decode[0]->charset;

		// Extract message date and translate to unix timestamp
		$decode = imap_mime_header_decode($headerinfo->udate);
		$header['date'] = $decode[0]->text;

		$decode = imap_mime_header_decode($headerinfo->subject);
		if (isset($decode[1])) {
			$header['subject'] = $this->convert_to_targetcharset($decode[1]->text, $decode[1]->charset);
		} else {
			// Not sure why [1] is not always defined, but it works this way
			//$header['subject'] = $decode[0]->text;
			$header['subject'] = $this->convert_to_targetcharset($decode[0]->text, $decode[0]->charset);
		}
		
		return $header;
				
	}

	function imap_get_message_body($msgno) {
		
		$structure = imap_fetchstructure($this->mail, $msgno);
	
		$this->parts = array();
		$this->partno = 0;
		
		$this->imap_get_message_part($structure, $msgno);
		
		return $this->parts;
	}
	
	function imap_get_message_part($structure, $msgno, $imappart='') {
		
		if(isset($structure->parts)) {		//$structure->type == TYPEMULTIPART || strcasecmp($structure->subtype, 'ALTERNATIVE') &&
			$i = 1; 
			foreach ($structure->parts as $partstructure) {
				// recurse message parts for multipart messages
				$makepartno = $imappart . ($imappart=='' ? '' : '.') . strval($i);
				$this->imap_get_message_part($partstructure, $msgno, $makepartno);
				$i++;
			}
		}
		else {
			
			if($imappart=='') $imappart = '1';
			$partbody = imap_fetchbody($this->mail, $msgno, $imappart);	//	$this->partno+1);
			
			$part = array(
				'is_text' => false,
				'is_attachment' => false,
				'filename' => '',
				'name' => '',
				'content' => '',
				'charset' => ''
			);
			
			if($structure->ifdparameters) {
				foreach($structure->dparameters as $object) {
					if(strcasecmp($object->attribute, 'filename') == 0) {
						$part['is_attachment'] = true;
						$part['filename'] = $object->value;
					}
				}
			}
			
			if($structure->ifparameters) {
				foreach($structure->parameters as $object) {
					if(strcasecmp($object->attribute, 'name') == 0) {
						$part['is_attachment'] = true;
						$part['name'] = $object->value;
					}
					if(strcasecmp($object->attribute, 'charset') == 0) {
						$part['charset'] = $object->value;
					}
					
				}
			}
			if($structure->encoding == ENCBASE64) { // 3 = BASE64
				$part['content'] = base64_decode($partbody);
			}
			elseif($structure->encoding == ENCQUOTEDPRINTABLE) { // 4 = QUOTED-PRINTABLE
				$part['content'] = quoted_printable_decode($partbody);
			}
			else {
				$part['content'] = $partbody;
			}
	
			if($structure->ifsubtype && strcasecmp($structure->subtype, 'HTML')==0 ) {
				// No support for html-mail (yet?)
			}	
			
			if($structure->ifsubtype && strcasecmp($structure->subtype, 'PLAIN')==0 ) {
				$part['is_text'] = true;

				// Remove soft CR-LFs and replace hard ones with <br />
				$part['content'] = preg_replace("/ \r\n/", " ", trim($part['content']));
            	$part['content'] = preg_replace("/\n/", "<br />", trim($part['content']));	
								
				if ($part['charset']!=='') {
					$part['content'] = $this->convert_to_targetcharset($part['content'], $part['charset']);
				}
			}
			
			// Skip unknown parts
			if ($part['is_text'] || $part['is_attachment']) {
				$this->parts[$this->partno] = $part;
				$this->partno++;
			}
			unset($part);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_imap.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mail2news/class.tx_mail2news_imap.php']);
}
?>