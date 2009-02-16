<?php

class tx_mail2news_imap {

	protected $mail;
	var $targetcharset;

	// message header data
	//protected $msgno;
	protected $date;
	//protected $charset;
	//protected $multipart;
	
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
		
		//echo 'Opening IMAP, using $mailboxoptions = ' . $mailboxoptions . "\n";
		if (! $this->mail = imap_open( '{' . $mail_server . $mailboxoptions . '}INBOX', $mail_username, $mail_password)) {
			die('Could not connect to mailserver. Quitting...' . "\n");
		}
	}
	
	function imap_count_headers() {
		return count(imap_headers($this->mail));
	}

	function imap_delete_current_message($msgno) {
		imap_delete($this->mail, $msgno);

		//echo "IMAP deleted nr: " . $msgno . "\n";
	}

	function imap_disconnect () {
		// really remove messages marked as deleted
		imap_expunge($this->mail);
		// close connection
		imap_close($this->mail);
		
		//echo "IMAP closed\n";
	}

	function set_targetcharset($charset) {
		$this->targetcharset = $charset;

		//echo "IMAP target charset set to $charset \n";
	}

	function convert_to_targetcharset($string, $currentcharset) {
		if (strcasecmp($currentcharset, 'windows-1252') == 0) {
			$currentcharset = 'ISO-8859-1';
		}
		if (strcasecmp($currentcharset, 'default') == 0) {
			$currentcharset = 'US-ASCII';
		}
		if (strcasecmp($currentcharset, $this->targetcharset) <> 0) {
			$string = mb_convert_encoding($string, $this->targetcharset, $currentcharset);
		}
		return $string;
	}


	function imap_get_message_header($msgno) {

		$header = Array();
	/*
		preg_match("/[0-9]/", $headerstring, $number);
		$this->msgno = $number[0];
	*/
		// parse message and sender
		$headertext = imap_fetchheader($this->mail, $msgno);
		
	//echo "$headertext\n\n";
		/*
		$header['charset'] = $this->charset = '';
		//$header['multipart'] = $this->multipart = FALSE;
		if (preg_match("/Content-Type: text\/plain; charset=(.*)?;/", $headertext, $charset)) {
			$header['charset'] = $this->charset = $charset[1];
		} elseif (preg_match("/Content-Type: multipart\/(.*);/", $headertext)) {
			$header['multipart'] = $this->multipart = TRUE;
		}
		*/
		$headerinfo = imap_headerinfo($this->mail, $msgno, 256, 256);
	//var_dump(get_object_vars($headerinfo));
		
		// Extract message from_email
		$from = $headerinfo->from[0];
		$header['fromemail'] = $from->mailbox . '@' . $from->host;
		// Extract message from_name
		$decode = imap_mime_header_decode($from->personal);
		$header['fromname'] = $this->convert_to_targetcharset($decode[0]->text, $decode[0]->charset);

		// Extract message date and translate to unix timestamp
		/*
		preg_match("/Date: (.*)?[\+|-]/", $headertext, $date);
		$this->date = strtotime(htmlentities($date[1]));
		$header['date'] = $this->date;
		*/
		// Alt:
		$decode = imap_mime_header_decode($headerinfo->udate);
		$header['date'] = $decode[0]->text;

		/*
		// LH: remove following old lines, as it takes only part of the subject until first non-ascii char
		$decode = imap_mime_header_decode($headerinfo->fetchsubject);
		$infofetchsubject = $decode[0]->text;
		*/
		$decode = imap_mime_header_decode($headerinfo->subject);
		if (isset($decode[1])) {
			$header['subject'] = $this->convert_to_targetcharset($decode[1]->text, $decode[1]->charset);
		} else {
			// No charset defined in header
			$header['subject'] = $decode[0]->text;
			// Sets charset to 'default'.....
			$header['charset'] = $decode[0]->charset;
		}
		
		$header['unseen'] = $headerinfo->Unseen;
		$header['recent'] = $headerinfo->Recent;
		//$header['charset'] = $decode[1]->charset;
		
		// Extract mail subject
		//preg_match("/Subject: (.*)\n/", $headertext, $subject_array);
		//$header['subject'] = $subject_array[1];
		//echo "INFOSUBJECT: " . $infosubject . "\n";
		
		//$decode = imap_mime_header_decode($headerinfo->from[0]->personal);
		/*
		//echo $headertext;
		//echo "\n" . "CHARSET[0]: " . $charset[0] . "\n";
		echo "\n" . "CHARSET[1]: " . $header['charset'] . "\n";
		echo "INFOSUBJECT: " . $infosubject . "\n";
		echo "SUBJECT: " . $this->subject . "\n";
		echo "CONV_SUBJECT: " . mb_convert_encoding($header['subject'], $this->targetcharset, $header['charset']) . "\n";
		echo "AUTO_SUBJECT: " . mb_convert_encoding($header['subject'], $this->targetcharset, auto) . "\n\n";
		echo "FROM_NAME: " . $from_name . "\n";
		echo "CONV_FROM: " . mb_convert_encoding($header['fromname'], $this->targetcharset, $header['charset']) . "\n";
		echo "AUTO_FROM: " . mb_convert_encoding($header['fromname'], $this->targetcharset, auto) . "\n\n";
		echo "DATE: " . $header['date'] . "\n";
		echo "UDATE: " . $udate . "\n\n";

		foreach($header as $key=>$item) {
			echo '$header['. $key . '] = ' . $item . "\n";
		}
		*/
		return $header;
		
	}

	function imap_get_message_body($msgno) {
		
		$structure = imap_fetchstructure($this->mail, $msgno);
	
//	print_r(get_object_vars($structure));
//	reset($structure);
	
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
			
			/*
			echo "################### Part no: ".$imappart." ##################\n";
			print_r(get_object_vars($structure));
			reset($structure);
			*/
			
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
				//echo "\nThere you have it! HTML! is_text = " . ($part['is_text']?'true':'false') . " | is_attach = " .($part['is_attachment']?'true':'false'). "\n\n";
			}	
			
			if($structure->ifsubtype && strcasecmp($structure->subtype, 'PLAIN')==0 ) {
				$part['is_text'] = true;

				// Remove soft CR-LFs and replace hard ones with <br />
				$part['content'] = preg_replace("/ \r\n/", " ", trim($part['content']));
            	$part['content'] = preg_replace("/\n/", "<br />", trim($part['content']));	
								
				if ($part['charset']!=='') {
					$part['content'] = $this->convert_to_targetcharset($part['content'], $part['charset']);
				}
//				echo $part['content'] . "\n\n";
			}
			
			// TEST!!!
			//$part['content'] = substr($part['content'], 0, 20);
			
			// Skip unknown parts
			if ($part['is_text'] || $part['is_attachment']) {
				$this->parts[$this->partno] = $part;
				$this->partno++;
			}
			unset($part);
		}
	}
}

?>