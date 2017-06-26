<?php

class RDFIOARC2StoreWrapperTest extends RDFIOTestCase {

	protected function setUp() {
		$this->arc2rdfxmlparser = ARC2::getRDFXMLParser();
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetEquivURIsForURI() {
		$wrapper = new RDFIOARC2StoreWrapper(new FakeTripleStore() );

		$inputUri =	'http://localhost:8080/w/index.php/Special:URIResolver/USA';

		$expectedUris = array( 'http://www.countries.org/onto/USA' );
		$actualUris = $this->invokeMethod( $wrapper, 'getEquivURIsForURI', array( $inputUri ) );

		$this->assertArrayEquals( $expectedUris, $actualUris );
	}

	public function testGetURIForEquivURI() {
		$wrapper = new RDFIOARC2StoreWrapper(new FakeTripleStore() );

		$inputUri =	'http://www.countries.org/onto/USA';

		$expectedUri = 'http://localhost:8080/w/index.php/Special:URIResolver/USA';
		$actualUri = $this->invokeMethod( $wrapper, 'getURIForEquivURI', array( $inputUri ) );

		$this->assertEquals( $expectedUri, $actualUri );
	}

}

class FakeTripleStore {
	public function query( $query ) {
		if ( $query == 'SELECT ?equivUri WHERE { <http://localhost:8080/w/index.php/Special:URIResolver/USA> <http://www.w3.org/2002/07/owl#sameAs> ?equivUri }' ) {
			$fakeResult = array(
				'query_type' => 'select',
				'result' => array(
					'variables' => array(
						'equivUri'
					),
					'rows' => array(
						array(
							'equivUri' => 'http://www.countries.org/onto/USA',
							'equivUri type' => 'uri',
							)
						)
					),
				'query_time' => 0.0081660747528076
			);
		} else if ( $query == 'SELECT ?uri WHERE { $uri <http://www.w3.org/2002/07/owl#sameAs> <http://localhost:8080/w/index.php/Special:URIResolver/USA> }' ) {
			$fakeResult = array(
				'query_type' => 'select',
				'result' => array(
					'variables' => array(
						'uri'
					),
					'rows' => array(
						array(
							'uri' => 'http://localhost:8080/w/index.php/Special:URIResolver/USA',
							'uri type' => 'uri',
						)
					)
				),
				'query_time' => 0.0081660747528076
			);
		}
		return $fakeResult;
	}
}