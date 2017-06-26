<?php

class RDFIOARC2StoreWrapperTest extends RDFIOTestCase {

	protected function setUp() {
		$this->arc2rdfxmlparser = ARC2::getRDFXMLParser();
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function getEquivURIsForURITest() {
		$wrapper = new RDFIOARC2StoreWrapper(new FakeTripleStore() );

		$expectedUri = 'http://www.countries.org/onto/USA';
		$actualUri = $this->invokeMethod( $wrapper, 'getEquivURIsForURI', array( 'http://localhost:8080/w/index.php/Special:URIResolver/USA' ) );

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
		}
		return $fakeResult;
	}
}