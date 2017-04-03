<?php

class RDFIOUtilsTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * @covers RDFIOUtils::startsWithUnderscore
	 */
	public function testStartsWithUnderscore() {
		$this->assertTrue( RDFIOUtils::startsWithUnderscore( '_blabla' ) );
		$this->assertFalse( RDFIOUtils::startsWithUnderscore( 'blabla' ) );
	}

	/**
	 * @covers RDFIOUtils::isURI
	 */
	public function testIsURI() {
		$this->assertTrue( RDFIOUtils::isURI( 'http://example.com' ) );
		$this->assertTrue( RDFIOUtils::isURI( 'https://example.com' ) );
		$this->assertFalse( RDFIOUtils::isURI( 'ftp://example.com' ) );
	}


	/**
	 * @covers RDFIOUtils::endsWithColon
	 */
	public function testEndsWithColon() {
		$this->assertTrue( RDFIOUtils::endsWithColon( 'http:' ) );
		$this->assertFalse( RDFIOUtils::endsWithColon( 'https://' ) );
	}

	/**
	 * @covers RDFIOUtils::cleanWikiTitle
	 */
	public function testClearWikiTitle() {
		$cleanedTitle = RDFIOUtils::cleanWikiTitle( '[Some] words in the title' );
		$this->assertEquals( 'Some words in the title', $cleanedTitle );
	}
} 
