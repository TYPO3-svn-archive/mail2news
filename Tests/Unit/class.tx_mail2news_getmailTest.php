<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include('/home/loek/www/javinto/html/typo3conf/ext/mail2news/class.tx_mail2news_getmail.php');

/**
 * Description of class
 *
 * @author loek
 */
class tx_mail2news_getmailTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_mail2news_getmail
	 */
	private $fixture = NULL;
	
	/**
	 * @var t3lib_DB
	 */
	private $databaseBackup = NULL;
	
	/**
	 * @var t3lib_beUserAuth 
	 */
	private $backEndUserBackup = NULL;
	
	public function setUp () {
		$this->databaseBackup = $GLOBALS['TYPO3_DB'];
		$this->backEndUserBackup = $GLOBALS['BE_USER'];
		
		$this->fixture = new tx_mail2news_getmail();
	}
	
	public function tearDown() {
		unset ($this->fixture);
		
		t3lib_div::purgeInstances();
		
		 $GLOBALS['TYPO3_DB'] = $this->databaseBackup;
		$GLOBALS['BE_USER'] = $this->backEndUserBackup;
	}

	
	/**
	 * @test
	 */
	public function linkTheWwwlinkInPlainTextInput() {
		$plaintext = 'This is some plain text with a link to this website www.google.com. Isn\'t that cool?';
		$linkedplaintext = 'This is some plain text with a link to this website <a href="http://www.google.com" class="extlink" target="_blank">www.google.com</a>. Isn\'t that cool?';
		
		
		$this->assertSame(
			$linkedplaintext, 
			$this->fixture->link_plain_text_urls($plaintext)
		);
	}
	

	/**
	 * @test
	 */
	public function happy() {
		$title = 'TestTitle';
		$this->assertSame(1,1);
	}

	/**
	 * @test
	 */
	public function storeRecordInsertsDataIntoDatabase() {
		$backEndUserMock = $this->getMock('t3lib_beUserAuth');
		$GLOBALS['BE_USER'] = $backEndUserMock;

		$databaseMock = $this->getMock('t3lib_DB');
		$GLOBALS['TYPO3_DB'] = $databaseMock;
		
		$referenceIndexMock = $this->getMock('t3lib_refindex');
		t3lib_div::addInstance('t3lib_refindex', $referenceIndexMock);
		
		$this->fixture->store_record(array());
	}
}
?>
