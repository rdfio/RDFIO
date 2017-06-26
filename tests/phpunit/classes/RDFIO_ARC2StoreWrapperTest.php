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

	}

}

class FakeTripleStore {
	// ...
}