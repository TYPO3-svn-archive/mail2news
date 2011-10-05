<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (t3lib_extMgm::extPath('mail2news').'class.tx_mail2news_ttnews.php');

/**
 * Description of class
 *
 * @author loek
 */
class tx_mail2news_ttnewsTest extends Tx_Phpunit_TestCase {

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

	public function setUp() {
		$this->databaseBackup = $GLOBALS['TYPO3_DB'];
		$this->backEndUserBackup = $GLOBALS['BE_USER'];

		$this->fixture = new tx_mail2news_ttnews();
	}

	public function tearDown() {
		unset($this->fixture);

		t3lib_div::purgeInstances();

		$GLOBALS['TYPO3_DB'] = $this->databaseBackup;
		$GLOBALS['BE_USER'] = $this->backEndUserBackup;
	}

	/**
	 * @test
	 */
	public function category_idsReturnsFalseOnEmptyInput() {
		$categories = $this->fixture->category_ids('');
		$this->assertSame(FALSE, $categories);
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

		
		// Prepare item array as input for the method to test
		$item = array(
		    'bodytext' => 'Bodytext',
		    'image' => 'imagefilenames.jpg',
		    'author' => 'fromname',
		    'title' => 'subject',
		    // newsitem fields below do not need to be encoded
		    'author_email' => 'fromname@email.com',
		    'datetime' => time(),
		    // supply additional fields from configuration defaults
		    'pid' => 10,
		    'hidden' => 0,
		    'cruser_id' => 1
		);

		// Create the expected array that should go into the database
		$expectedItem = array(
		    'crdate' => time(),
		    'tstamp' => time(),
		    'category' => 0
		);

		$expectedItem = array_merge($expectedItem, $item);

		// Create mock object for INSERTquery
		$databaseMock->expects($this->once())
			->method('exec_INSERTquery')
			->with('tt_news', $expectedItem)
			->will($this->returnValue(TRUE));
			
		// Expected message to go into simplelog
		$expectedlogmsg = 'News item created: "subject", created on page (pid 10)';

		$backEndUserMock->expects($this->once())
			->method('simplelog')
			->with($expectedlogmsg, 'mail2news');
			
		// Check if reference index gets updated
		$databaseMock->expects($this->once())
			->method('sql_insert_id')
			->will($this->returnValue('42'));
		
		$referenceIndexMock->expects($this->once())
			->method('updateRefIndexTable')
			->with('tt_news', 42);
		
		$this->fixture->store_record($item);
	}

}

?>
