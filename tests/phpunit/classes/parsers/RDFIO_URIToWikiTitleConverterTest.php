<?php

/**
 * Exception used in the RDFIOURIToTitleConverter class
 */
// class WikiTitleNotFoundException extends MWException { }

/**
 * Converter that takes an RDF URI and returns a suitable Wiki title for that URI
 * based on various strategies, which are tried one at a time, until a usable title 
 * is found.
 * @author samuel
 *
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
	 * The main method, converting from URI:s to wiki titles.
	 * NOTE: Properties are taken care of py a special method below!
	 * @param string $uriToConvert
	 * @return string $wikiTitle
	 */
// 	public function convert( $uriToConvert ) {
// 		global $wgOut;

// 		// Define the conversion functions to try, in 
// 		// specified order (the first one first).
// 		// You'll find them defined further below in this file.
// 		$uriToWikiTitleConversionStrategies = array(
// 			'getExistingTitleForURI',
// 			'applyGlobalSettingForPropertiesToUseAsWikiTitle',
// 			'shortenURINamespaceToAliasInSourceRDF',
// 			'extractLocalPartFromURI'
// 		);

// 		$wikiPageTitle = '';

// 		foreach ($uriToWikiTitleConversionStrategies as $currentStrategy ) {
// 			$wikiPageTitle = $this->$currentStrategy( $uriToConvert );	
// 			if ($wikiPageTitle != null) {
// 				return $wikiPageTitle;
// 			}
// 		}
// 	}

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
	 * Strategy 3: URI to WikiTitle
	 */
// 	function shortenURINamespaceToAliasInSourceRDF( $uriToConvert ) {
// 		global $rdfiogBaseURIs;

// 		// Shorten the Namespace (even for entities, optionally) into an NS Prefix
// 		// according to mappings from parser (Such as chemInf:Blabla ...)
// 		$nsPrefixes = $this->arc2NSPrefixes;
// 		$wikiPageTitle = '';

// 		// The same, but according to mappings from LocalSettings.php
// 		if ( is_array( $rdfiogBaseURIs ) ) {
// 			$nsPrefixes = array_merge( $nsPrefixes, $rdfiogBaseURIs );
// 		}
		
// 		// Collect all the inputs for abbreviation, and apply:
// 		if ( is_array( $nsPrefixes ) ) {
// 			$abbreviatedUri = $this->abbreviateParserNSPrefixes( $uriToConvert, $nsPrefixes );
// 			$wikiPageTitle = $abbreviatedUri;
// 		}

// 		if ( $wikiPageTitle != '' ) {
// 			return $wikiPageTitle;
// 		} else {
// 			return null;
// 		}	
// 	}

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
	 * @covers RDFIOURIToTitleConverter::removeInvalidChars
	 */
	public function testRemoveInvalidChars() {
	    $uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
	    $cleanedTitle = $uriToWikiTitleConverter->removeInvalidChars( '[Some] words in the title' );
	    $this->assertEquals('Some words in the title', $cleanedTitle);
	}

	/**
	 * Use the namespaces from the RDF / SPARQL source, to shorten the URIs.
	 * @param string $uri
	 * @param array $nsPrefixes
	 * @return string
	 */
// 	function abbreviateParserNSPrefixes( $uri, $nsPrefixes ) {
// 		foreach ( $nsPrefixes as $namespace => $prefix ) {
// 			$nslength = strlen( $namespace );
// 			$basepart = '';
// 			$localpart = '';
// 			$uriContainsNamepace = substr( $uri, 0, $nslength ) === $namespace;
// 			if ( $uriContainsNamepace ) {
// 				$localpart = substr( $uri, $nslength );
// 				$basepart = $prefix;
// 			}
// 		}

// 		/*
// 		 * Take care of some special cases:
// 		 */
// 		if ( $basepart === '' &&  $localpart === '' ) {
// 			$uriParts = $this->splitURI( $uri );
// 			$basepart = $uriParts[0];
// 			$localpart = $uriParts[1];
// 		}

// 		if ( $localpart === '' ) {
// 			$abbreviatedUri = $basepart;
// 		} elseif ( $this->startsWithUnderscore( $basepart ) ) {
// 			// FIXME: Shouldn't the above check the local part instead??

// 			// Change ARC:s default "random string", to indicate more clearly that
// 			// it lacks title
// 			$abbreviatedUri = str_replace( 'arc', 'untitled', $localpart );

// 		} elseif ( $this->startsWithHttpOrHttps( $basepart ) ) {
// 			// If the abbreviation does not seem to have succeeded,
// 			// fall back to use only the local part
// 			$abbreviatedUri = $localpart;

// 		} elseif ( $this->endsWithColon( $basepart ) ) {
// 			// Don't add another colon
// 			$abbreviatedUri = $basepart . $localpart;

// 		} elseif ( $basepart == false || $basepart == '' ) {
// 			$abbreviatedUri = $localpart;

// 		} else {
// 			$abbreviatedUri = $basepart . ':' . $localpart;

// 		}

// 		return $abbreviatedUri;
// 	}



	/**
	 * @covers RDFIOURIToTitleConverter::startsWithUnderscore
	 */
	public function testStartsWithUnderscore() {
	    $uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
	    $this->assertTrue( $uriToWikiTitleConverter->startsWithUnderscore( '_blabla' ) );
	    $this->assertFalse( $uriToWikiTitleConverter->startsWithUnderscore( 'blabla' ) );
	}

	/**
	 * @covers RDFIOURIToTitleConverter::startsWithUnderscore
	 */
	public function testStartsWithHttpOrHttps() {
	    $uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
	    $this->assertTrue( $uriToWikiTitleConverter->startsWithHttpOrHttps('http://example.com') );
	    $this->assertTrue( $uriToWikiTitleConverter->startsWithHttpOrHttps('https://example.com') );
	    $this->assertFalse( $uriToWikiTitleConverter->startsWithHttpOrHttps('ftp://example.com') );
	}
	

	/**
	 * @covers RDFIOURIToTitleConverter::endsWithColon
	 */
	public function testEndsWithColon() {
	    $uriToWikiTitleConverter = new RDFIOURIToTitleConverter(array(), array(), array());
	    $this->assertTrue( $uriToWikiTitleConverter->endsWithColon('http:') );
	    $this->assertFalse( $uriToWikiTitleConverter->endsWithColon('https://') );
	}

}


/**
 * Subclass of the more general RDFIOURIToTitleConverter
 * For property pages (those where titles start with "Property:")  
 */
// class RDFIOURIToPropertyTitleConverter extends RDFIOURIToTitleConverter {

	/**
	 * The main method, which need some special handling.
	 * @param string $propertyURI
	 * @return string $propertyTitle
	 */
// 	function convert( $propertyURI ) {
// 		$propertyTitle = '';
// 		$existingPropTitle = $this->arc2Store->getWikiTitleByEquivalentURI($propertyURI, $isProperty=true);
// 		if ( $existingPropTitle != "" ) {
// 			// If the URI had an existing title, use that
// 			$propertyTitle = $existingPropTitle;
// 		} else {
// 			$uriToWikiTitleConverter = new RDFIOURIToWikiTitleConverter( $this->arc2Triples, $this->arc2ResourceIndex, $this->arc2NSPrefixes );
// 			$propertyTitle = $uriToWikiTitleConverter->convert( $propertyURI );
// 		}
// 		$propertyTitle = $this->removeInvalidChars( $propertyTitle );
// 		return $propertyTitle;
// 	}
//}	


