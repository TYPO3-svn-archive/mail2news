<?php

class tx_mail2news {

	var $extconf;

	function __construct($extconf) {
		$this->extconf = $extconf;
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
			
//print_r ($part);
			
			if($part['is_text']) {
				// Takes only first text-part of multipart messages, or optionally concatenate text parts
            	$result['bodytext'] .= ($result['bodytext'] == '' || $this->extconf['concatenate_text_parts'] ? $part['content'] : '');

				$fileext = 'txt';
				$filename = 'textpart' . '_' . rand(0,9999);

				$txtfilenames = '';
				$this->saveattachment($filename, $fileext, PATH_uploads_media, $part['content'], $this->extconf['max_image_size'], $txtfilenames, $imgs);
			
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
?>