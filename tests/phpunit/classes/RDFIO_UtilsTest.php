<?php

class RDFIOUtilsTest extends MediaWikiTestCase {

    protected function setUp() {
        parent::setUp();
    }

    protected function tearDown() {}
    
    /**
     * @covers RDFIOURIToTitleConverter::startsWithUnderscore
     */
    public function testStartsWithUnderscore() {
        $this->assertTrue( RDFIOUtils::startsWithUnderscore( '_blabla' ) );
        $this->assertFalse( RDFIOUtils::startsWithUnderscore( 'blabla' ) );
    }
    
    /**
     * @covers RDFIOURIToTitleConverter::startsWithUnderscore
     */
    public function testStartsWithHttpOrHttps() {
        $this->assertTrue( RDFIOUtils::isURI('http://example.com') );
        $this->assertTrue( RDFIOUtils::isURI('https://example.com') );
        $this->assertFalse( RDFIOUtils::isURI('ftp://example.com') );
    }
    
    
    /**
     * @covers RDFIOURIToTitleConverter::endsWithColon
     */
    public function testEndsWithColon() {
        $this->assertTrue( RDFIOUtils::endsWithColon('http:') );
        $this->assertFalse( RDFIOUtils::endsWithColon('https://') );
    }

} 
