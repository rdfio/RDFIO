<?php

class RDFIOARC2ToWikiConverterTest extends RDFIOTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * @covers RDFIOARC2ToWikiConverter::convert
	 */
	public function testConvert() {
		$testData = new RDFIOTestData();

		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$arc2rdfxmlparser->parseData( $testData->getTestImportData() );

		$triples = $arc2rdfxmlparser->triples;
		$tripleIndex = $arc2rdfxmlparser->getSimpleIndex();
		$namespaces = $arc2rdfxmlparser->nsp;

		$arc2ToWikiConv = new RDFIOARC2ToWikiConverter();
		$wikiPages = $arc2ToWikiConv->convert( $triples, $tripleIndex, $namespaces );

		$this->assertEquals( 12, count( $wikiPages ), "No wiki pages converted from triples!" );
	}

	public function testAddEquivUriToPage() {
		$arc2ToWiki = new RDFIOARC2ToWikiConverter();

		$equivUri = 'http://example.org/example_uri';
		$pageTitle = 'ExamplePage';

		$this->invokeMethod( $arc2ToWiki, 'addEquivUriToPage', array( $equivUri, $pageTitle ) );
		$page = $this->invokeMethod( $arc2ToWiki, 'getPage', array( $pageTitle ) );

		$equivUriExists = $this->invokeMethod( $page, 'equivalentURIExists', array( $equivUri ) );
		$this->assertTrue( $equivUriExists );
	}

	public function testAddEquivUriToPageDontAddBNode() {
		$arc2ToWiki = new RDFIOARC2ToWikiConverter();

		$equivUri = '_:arc123abc';
		$pageTitle = 'ExamplePage';

		$this->invokeMethod( $arc2ToWiki, 'addEquivUriToPage', array( $equivUri, $pageTitle ) );
		$page = $this->invokeMethod( $arc2ToWiki, 'getPage', array( $pageTitle ) );

		$equivUriExists = $this->invokeMethod( $page, 'equivalentURIExists', array( $equivUri ) );
		$this->assertFalse( $equivUriExists );
	}

	public function testAddFactToPage() {
		$arc2ToWiki = new RDFIOARC2ToWikiConverter();

		$fact = array( 'p' => 'example_property', 'o' => 'example_object' );
		$pageTitle = 'ExamplePage';

		$this->invokeMethod( $arc2ToWiki, 'addFactToPage', array( $fact, $pageTitle ) );
		$page = $this->invokeMethod( $arc2ToWiki, 'getPage', array( $pageTitle ) );

		$facts = $this->invokeMethod( $page, 'getFacts' );
		$this->assertArrayEquals( $fact, $facts[0] );
	}

	public function testAddCategoryToPage() {
		$arc2ToWiki = new RDFIOARC2ToWikiConverter();

		$category = 'ExampleCategory';
		$pageTitle = 'ExamplePage';

		$this->invokeMethod( $arc2ToWiki, 'addCategoryToPage', array( $category, $pageTitle ) );
		$page = $this->invokeMethod( $arc2ToWiki, 'getPage', array( $pageTitle ) );

	}

	public function testAddDataTypeToPage() {
		$arc2ToWiki = new RDFIOARC2ToWikiConverter();

		$dataType = 'Telephone number';
		$pageTitle = 'ExamplePage';

		$this->invokeMethod( $arc2ToWiki, 'addDataTypeToPage', array( $dataType, $pageTitle ) );
		$page = $this->invokeMethod( $arc2ToWiki, 'getPage', array( $pageTitle ) );

		$facts = $this->invokeMethod( $page, 'getFacts' );
		$this->assertArrayEquals( array( 'p' => 'Has type', 'o' => $dataType ), $facts[0] );
	}
}