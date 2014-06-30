<?php

/**
 * @covers RDFIOURIToTitleConverter
 */
class RDFIOURIToTitleConverterTest extends MediaWikiTestCase { 

	protected function setUp() {
		parent::setUp();
		$testData = new RDFIOTestData();
		
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$arc2rdfxmlparser->parseData( $testData->getTestImportData() );
		
		$triples = $arc2rdfxmlparser->triples;
		$tripleIndex = $arc2rdfxmlparser->getSimpleIndex();
		$namespaces = $arc2rdfxmlparser->nsp;
		
		$this->uriToWikiTitleConverter = new RDFIOURIToWikiTitleConverter( $triples, $tripleIndex, $namespaces );
		$this->uriToPropertyTitleConverter = new RDFIOURIToPropertyTitleConverter( $triples, $tripleIndex, $namespaces );	
	}
	
	protected function tearDown() {}

	/**
	 * @covers RDFIOURIToTitleConverter::convert
	 * @covers RDFIOURIToPropertyTitleConverter::convert
	 */
	public function testConvertWithABunchOfExampleURLs() {
	    $uri1 = 'http://www.recshop.fake/cd/Empire Burlesque';
	    $title1 = $this->uriToWikiTitleConverter->convert( $uri1 );
	    $this->assertEquals('Empire Burlesque', $title1);

	    $uri2 = 'http://www.recshop.fake/cd#artist';
	    $title2 = $this->uriToPropertyTitleConverter->convert( $uri2 );
	    $this->assertEquals('cd:artist', $title2);
	     
 	    $uri3 = 'http://www.countries.org/onto/USA';
 	    $title3 = $this->uriToWikiTitleConverter->convert( $uri3 );
 	    $this->assertEquals('countries:USA', $title3);
	     
 	    $uri4 = 'http://something.totally.unrelated.to/its/label';
 	    $title4 = $this->uriToWikiTitleConverter->convert( $uri4 );
 	    $this->assertEquals('SomeTotallyUnrelatedLabel', $title4);
	}

	/**
	 * @covers RDFIOURIToTitleConverter::applyGlobalSettingForPropertiesToUseAsWikiTitle
	 */
	public function testApplyGlobalSettingForPropertiesToUseAsWikiTitleWorksWithCorrectSettings() {
	    $GLOBALS['rdfiogPropertiesToUseAsWikiTitle'] = array(
	            'http://semantic-mediawiki.org/swivt/1.0#page',
	            'http://www.w3.org/2000/01/rdf-schema#label',
	            'http://purl.org/dc/elements/1.1/title',
	            'http://www.w3.org/2004/02/skos/core#preferredLabel',
	            'http://xmlns.com/foaf/0.1/name',
	            'http://www.nmrshiftdb.org/onto#spectrumId'
	    );
	     
	    $uri = 'http://something.totally.unrelated.to/its/label';
	    $wikiTitle = $this->uriToWikiTitleConverter->convert($uri);
	    $this->assertEquals('SomeTotallyUnrelatedLabel', $wikiTitle);
	}	

	public function testApplyGlobalSettingForPropertiesToUseAsWikiTitleDoesNotWorkWithWrongSetting() {
	    $GLOBALS['rdfiogPropertiesToUseAsWikiTitle'] = array(
	            'http://semantic-mediawiki.org/swivt/1.0#page',
	            'http://purl.org/dc/elements/1.1/title',
	            'http://www.w3.org/2004/02/skos/core#preferredLabel',
	            'http://xmlns.com/foaf/0.1/name',
	            'http://www.nmrshiftdb.org/onto#spectrumId'
	    );
	     
	    $uri = 'http://something.totally.unrelated.to/its/label';
	    $wikiTitle = $this->uriToWikiTitleConverter->convert($uri);
	    $this->assertNotEquals('SomeTotallyUnrelatedLabel', $wikiTitle);
	}

	/**
	 * @covers RDFIOURIToTitleConverter::shortenURINamespaceToAliasInSourceRDF
	 * @covers RDFIOURIToTitleConverter::abbreviateParserNSPrefixes
	 */
	public function testShortenURINamespaceToAliasInSourceRDF() {
	    global $rdfiogBaseURIs;

	    $title = $this->uriToWikiTitleConverter->shortenURINamespaceToAliasInSourceRDF('http://www.countries.org/onto/Canada');
	    $this->assertEquals('countries:Canada', $title);
	}

	/** 
	 * @covers RDFIOURIToTitleConverter::extractLocalPartFromURI
	 * @covers RDFIOURIToTitleConverter::splitURI
	 */
	public function testExtractLocalPartFromURIWorks() {
	    $uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());

	    $newUri1 = $uriToWikiTitleConverter->extractLocalPartFromURI('http://some.url.with.a/localpart');
	    $this->assertEquals('localpart', $newUri1);

	    $newUri2 = $uriToWikiTitleConverter->extractLocalPartFromURI('https://some.url.with.a/localpart');
	    $this->assertEquals('localpart', $newUri2);
	     
	    $newUri3 = $uriToWikiTitleConverter->extractLocalPartFromURI('http://some.url/with.a#localpart');
	    $this->assertEquals('localpart', $newUri3);
	     
	    $newUri4 = $uriToWikiTitleConverter->extractLocalPartFromURI('http://some.url/with.a/localpart');
	    $this->assertEquals('localpart', $newUri4);
	     
	    $newUri4 = $uriToWikiTitleConverter->extractLocalPartFromURI('http://some.com/url/with.a#localpart');
	    $this->assertEquals('localpart', $newUri4);
	}

	/////// HELPER METHODS ///////

	/**
	 * @covers RDFIOURIToTitleConverter::globalSettingForPropertiesToUseAsWikiTitleExists
	 */
	public function testGlobalSettingForPropertiesToUseAsWikiTitleExistsReturnsTrueOnExists() {
	    global $rdfiogPropertiesToUseAsWikiTitle;
		$uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
		$rdfiogPropertiesToUseAsWikiTitle = array();
		$this->assertTrue($uriToWikiTitleConverter->globalSettingForPropertiesToUseAsWikiTitleExists());

		// A normal unset only destroys the local variable binding, so we have to do like
		// this inseta
		$GLOBALS['rdfiogPropertiesToUseAsWikiTitle'] = null;
		$this->assertFalse($uriToWikiTitleConverter->globalSettingForPropertiesToUseAsWikiTitleExists());
	}
	
	/**
	 * @covers RDFIOURIToTitleConverter::globalSettingForPropertiesToUseAsWikiTitleExists
	 */
	public function testGlobalSettingForPropertiesToUseAsWikiTitleExistsReturnsFalseOnDOesntExist() {
	    global $rdfiogPropertiesToUseAsWikiTitle;
		$uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
	    // A normal unset only destroys the local variable binding, so we have to do like
		// this inseta
		$GLOBALS['rdfiogPropertiesToUseAsWikiTitle'] = null;
		$this->assertFalse($uriToWikiTitleConverter->globalSettingForPropertiesToUseAsWikiTitleExists());
	}	
	
	/**
	 * @covers RDFIOURIToTitleConverter::cleanWikiTitle
	 */
	public function testRemoveInvalidChars() {
	    $uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
	    $cleanedTitle = $uriToWikiTitleConverter->cleanWikiTitle( '[Some] words in the title' );
	    $this->assertEquals('Some words in the title', $cleanedTitle);
	}

}
