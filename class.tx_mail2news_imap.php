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
	*   58: class tx_mail2news_imap
	*   71:     function __construct()
	*   83:     function imap_connect($mail_server, $mail_username, $mail_password, $options)
	*  102:     function imap_count_headers()
	*  109:     function imap_delete_message($msgno)
	*  118:     function imap_disconnect ()
	*  128:     function set_targetcharset($charset)
	*  136:     function convert_to_targetcharset($string, $currentcharset)
	*  152:     function decode_header_item($item)
	*  174:     function imap_get_message_header($msgno)
	*  205:     function imap_get_message_body($msgno)
	*  225:     function imap_get_message_part($structure, $msgno, $imappart='')
	*
	* TOTAL FUNCTIONS: 11
	* (This index is automatically created/updated by the extension "extdeveval")
	*
	*/
	class tx_mail2news_imap {
		 
		protected $mail;
		var $targetcharset;
		 
		// message body data
		protected $parts;
		protected $partno;
		 
		/*
		* Construct new IMAP object
		*/
		 
		function __construct() {
		}
		 
		/**
		* Initialize IMAP connection
		*
		* @param string $mail_server: mailserver address
		* @param string $mail_username: username of mailaccount
		* @param string $mail_password: password
		* @param array $options:
		*   IMAP => boolean
		*   SSL  => boolean
		*   portno => integer, uses defaults if 0, empty or not set
		* @return void
		*/
		 
		function imap_connect($mail_server, $mail_username, $mail_password, $options) {
			 
			$portno = $options['portno'];
			if (!isset($portno) || $portno == '' || $portno == 0 ) {
				if ($options['IMAP']) $portno = ($options['SSL'] ? 993 : 143);
					else $portno = ($options['SSL'] ? 995 : 110);
			}
			 
			$mailboxoptions = ':' . $portno . ($options['IMAP'] ? '/imap' : '/pop3') . ($options['SSL'] ? '/ssl' : '') . ($options['self_signed_certificate'] ? '/novalidate-cert' : '') . '/notls';
			 
			$this->mail = imap_open('{' . $mail_server . $mailboxoptions . '}INBOX', $mail_username, $mail_password);
			if (!$this->mail) {
				die(date('Y-m-d H:i:s ') . 'Could not connect to mailserver. Quitting...' . "\n");
			}
		}
		 
		/**
		* Returns the number of messages in inbox
		*
		* @return int: no. of messages
		*/
		function imap_count_headers() {
			return count(imap_headers($this->mail));
		}
		 
		/**
		* Delete message $msgno
		*
		* @param int $msgno: Number of the message to be deleted from mail account
		*/
		function imap_delete_message($msgno) {
			imap_delete($this->mail, $msgno);
		}
		 
		/**
		* Disconnect from POP3/IMAP mailbox
		*
		* @return void
		*/
		function imap_disconnect () {
			// really remove messages marked as deleted
			imap_expunge($this->mail);
			// close connection
			imap_close($this->mail);
		}
		 
		/**
		* Sets the target character set to which appropriate text and header fields will be converted
		* @param string $charset:	characterset of the current TYPO3 installation,
		*				to which the incoming messages should be converted
		*/
		function set_targetcharset($charset) {
			$this->targetcharset = strtolower($charset);
		}
		 
		/**
		*  Checks if charset is different from target charset, if so, convert
		*  windows-1252 is treated as ISO-8859-1, default as US-ASCII
		*/
		function convert_to_targetcharset($string, $currentcharset) {
			if (strcasecmp($currentcharset, 'windows-1252') == 0) {
				$currentcharset = 'iso-8859-1';
			}
			if (strcasecmp($currentcharset, 'default') == 0 || $currentcharset == '') {
				$currentcharset = 'us-ascii';
			}
			if (strcasecmp($currentcharset, $this->targetcharset) <> 0) {
				$string = mb_convert_encoding($string, $this->targetcharset, $currentcharset);
			}
			return $string;
		}
		 
		/**
		*  Decode multi-line mime-header item, get charset and convert if necessary
		*/
		function decode_header_item($item) {
			$result = '';
			$decode = imap_mime_header_decode($item);
			foreach($decode as $line) {
				$result .= $this->convert_to_targetcharset($line->text, $line->charset);
			}
			// remove tabs from multi-line format
			return preg_replace("/\t/", '', $result);
		}
		 
		/**
		* Fetch header of message $msgno and return header fields:
		* If charset is defined in header, the fields from name and subject are
		* converted to target charset.
		*
		* @param int $msgno:	Number of the message in current inbox
		* @return array: Array of header lines from the mail message:
		*   fromemail => from: email address,
		*   fromname => from: full name,
		*   date => unix timestamp,
		*   subject => message subject
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
			$header['fromname'] = $this->decode_header_item($from->personal);
			 
			// Extract message date and translate to unix timestamp
			$decode = imap_mime_header_decode($headerinfo->udate);
			$header['date'] = $decode[0]->text;
			 
			// decode multi-line message subject
			$header['subject'] = $this->decode_header_item($headerinfo->subject);
			 
			return $header;
			 
		}
		 
		/**
		* [Describe function...]
		*
		* @param [type]  $msgno: ...
		* @return [type]  ...
		*/
		function imap_get_message_body($msgno) {
			 
			$structure = imap_fetchstructure($this->mail, $msgno);
			 
			$this->parts = array();
			$this->partno = 0;
			 
			$this->imap_get_message_part($structure, $msgno);
			 
			return $this->parts;
		}
		 
		/**
		* [Describe function...]
		*
		* @param [type]  $structure: ...
		* @param [type]  $msgno: ...
		* @param [type]  $imappart: ...
		* @return [type]  ...
		*/
		function imap_get_message_part($structure, $msgno, $imappart = '') {
			 
			if (isset($structure->parts)) {
				//$structure->type == TYPEMULTIPART || strcasecmp($structure->subtype, 'ALTERNATIVE') &&
				$i = 1;
				foreach ($structure->parts as $partstructure) {
					// recurse message parts for multipart messages
					$makepartno = $imappart . ($imappart == '' ? '' : '.') . strval($i);
					$this->imap_get_message_part($partstructure, $msgno, $makepartno);
					$i++;
				}
			} else {
				 
				if ($imappart == '') $imappart = '1';
				$partbody = imap_fetchbody($this->mail, $msgno, $imappart); // $this->partno+1);
				 
				$part = array(
					'is_text' => false,
					'is_attachment' => false,
					'filename' => '',
					'name' => '',
					'content' => '',
					'charset' => ''
					);

				/*
				 * get dparameters, if they exist
				 * filename inside 'dparameters' gets encoded differently for more exotic charactersets,
				 * therefor use name from 'parameters' to retrieve filename.
				 */
				if ($structure->ifdparameters) {
					foreach($structure->dparameters as $object) {
						if (strtolower(substr($object->attribute,0,8)) == 'filename') {
							$part['is_attachment'] = true;
							$part['filename'] = $this->decode_header_item($object->value);
						}
						/*
						echo "------------ifdparameters:---------------IFD----------\n";
						print_r($object); echo "\n";
 						echo "is_attachment: " . $part['is_attachment'] . "\n";
						echo "filename: " . $part['filename'] . "\n";
						 */
					}
				}
				 
				if ($structure->ifparameters) {
					foreach($structure->parameters as $object) {
						if (strtolower($object->attribute) == 'name') {
							$part['is_attachment'] = true;
							// This will contain the filename of the image or attachment
							$part['name'] = $this->decode_header_item($object->value);
						}
						if (strtolower($object->attribute) == 'charset') {
							$part['charset'] = $object->value;
						}
						/*
						echo "------------ifparameters:----------------IF-----------\n";
						print_r($object); echo "\n";
						 */
					}
				}
				if ($structure->encoding == ENCBASE64) {
					// 3 = BASE64
					$part['content'] = base64_decode($partbody);
				} elseif($structure->encoding == ENCQUOTEDPRINTABLE) {
					// 4 = QUOTED-PRINTABLE
					$part['content'] = quoted_printable_decode($partbody);
				} else {
					$part['content'] = $partbody;
				}
				 
				if ($structure->ifsubtype && strcasecmp($structure->subtype, 'HTML') == 0 ) {
					// No support for html-mail (yet?)
				}
				 
				if ($structure->ifsubtype && strcasecmp($structure->subtype, 'PLAIN') == 0 ) {
					$part['is_text'] = true;
					// Remove soft CR-LFs (preg_replace)
					$part['content'] = preg_replace("/ \r\n/", ' ', trim($part['content']));
					$part['content'] = $this->convert_to_targetcharset($part['content'], $part['charset']);
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