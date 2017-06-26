<?php

class RDFIOARC2StoreWrapperTest extends RDFIOTestCase {

	protected function setUp() {
		$this->arc2rdfxmlparser = ARC2::getRDFXMLParser();
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testToEquivUrisInTriples() {
		$wrapper = new RDFIOARC2StoreWrapper( new FakeTripleStore() );

		$inputTriples = array(
			array(
				's' => 'http://localhost:8080/w/index.php/Special:URIResolver/Empire_Burlesque',
				'p' => 'http://localhost:8080/w/index.php/Special:URIResolver/Property-3ACompany',
				'o' => 'http://localhost:8080/w/index.php/Special:URIResolver/Columbia',
				's_type' => 'uri',
				'o_type' => 'var',
				'o_datatype' => '',
				'o_lang' => '',
			),
			array(
				's' => 'http://localhost:8080/w/index.php/Special:URIResolver/Empire_Burlesque',
				'p' => 'http://localhost:8080/w/index.php/Special:URIResolver/Property-3ACountry',
				'o' => 'http://localhost:8080/w/index.php/Special:URIResolver/USA',
				's_type' => 'uri',
				'o_type' => 'var',
				'o_datatype' => '',
				'o_lang' => '',
			)
		);

		$expectedTriples = array(
			array(
				's' => 'http://www.recshop.fake/cd/Empire%20Burlesque',
				'p' => 'http://www.recshop.fake/cd#company',
				'o' => 'http://localhost:8080/w/index.php/Special:URIResolver/Columbia',
			),
			array(
				's' => 'http://www.recshop.fake/cd/Empire%20Burlesque',
				'p' => 'http://localhost:8080/w/index.php/Special:URIResolver/Property-3ACountry',
				'o' => 'http://localhost:8080/w/index.php/Special:URIResolver/USA',
			),
		);

		$actualTriples = $this->invokeMethod( $wrapper, 'toEquivUrisInTriples', array( $inputTriples ) );

		$this->assertArrayEquals( $expectedTriples, $actualTriples );
	}

	public function testGetEquivURIsForURI() {
		$wrapper = new RDFIOARC2StoreWrapper( new FakeTripleStore() );

		$inputUri =	'http://localhost:8080/w/index.php/Special:URIResolver/Empire_Burlesque';

		$expectedUris = array( 'http://www.recshop.fake/cd/Empire%20Burlesque' );
		$actualUris = $this->invokeMethod( $wrapper, 'getEquivURIsForURI', array( $inputUri ) );

		$this->assertArrayEquals( $expectedUris, $actualUris );
	}

	public function testGetURIForEquivURI() {
		$wrapper = new RDFIOARC2StoreWrapper( new FakeTripleStore() );

		$inputUri =	'http://www.recshop.fake/cd/Empire%20Burlesque';

		$expectedUri = 'http://localhost:8080/w/index.php/Special:URIResolver/Empire_Burlesque';
		$actualUri = $this->invokeMethod( $wrapper, 'getURIForEquivURI', array( $inputUri ) );

		$this->assertEquals( $expectedUri, $actualUri );
	}

	public function testGetWikiTitleByEquivalentURI() {
		$wrapper = new RDFIOARC2StoreWrapper( new FakeTripleStore() );

		$inputUri = 'http://www.recshop.fake/cd/Empire%20Burlesque';
		$expectedTitle = 'Empire Burlesque';
		$actualTitle = $this->invokeMethod( $wrapper, 'getWikiTitleByEquivalentURI', array( $inputUri ) );

		$this->assertEquals( $expectedTitle, $actualTitle );
	}
}

// ============ HELPER STUFF ============

class FakeTripleStore {
	public function query( $query ) {
		$fakeResult = null;

		$query_getEquivURIsForURI = 'SELECT ?equivUri WHERE { <http://localhost:8080/w/index.php/Special:URIResolver/Empire_Burlesque> <http://www.w3.org/2002/07/owl#sameAs> ?equivUri }';
		$query_getURIForEquivURI = 'SELECT ?uri WHERE { ?uri <http://www.w3.org/2002/07/owl#sameAs> <http://www.recshop.fake/cd/Empire%20Burlesque> }';
		$query_complementTriplesWithEquivURIs = 'SELECT ?equivUri WHERE { <http://localhost:8080/w/index.php/Special:URIResolver/Property-3ACompany> <http://www.w3.org/2002/07/owl#equivalentProperty> ?equivUri }';

		if ( $query == $query_getEquivURIsForURI ) {
			$fakeResult = array(
				'query_type' => 'select',
				'result' => array(
					'variables' => array(
						'equivUri'
					),
					'rows' => array(
						array(
							'equivUri' => 'http://www.recshop.fake/cd/Empire%20Burlesque',
							'equivUri type' => 'uri',
							)
						)
					),
				'query_time' => 0.0081660747528076
			);
		} else if ( $query == $query_getURIForEquivURI ) {
			$fakeResult = array(
				'query_type' => 'select',
				'result' => array(
					'variables' => array(
						'uri'
					),
					'rows' => array(
						array(
							'uri' => 'http://localhost:8080/w/index.php/Special:URIResolver/Empire_Burlesque',
							'uri type' => 'uri',
						)
					)
				),
				'query_time' => 0.0081660747528076
			);
		} else if ( $query == $query_complementTriplesWithEquivURIs ) {
			$fakeResult = array(
				'query_type' => 'select',
				'result' => array(
					'variables' => array(
						'equivUri'
					),
					'rows' => array(
						array(
							'equivUri' => 'http://www.recshop.fake/cd#company',
							'equivUri type' => 'uri',
						)
					)
				),
				'query_time' => 0.0081660747528076
			);
		}
		return $fakeResult;
	}

	public function getErrors() {
		return null;
	}
}