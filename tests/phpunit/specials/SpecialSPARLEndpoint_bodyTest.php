<?php

class RDFIOSPARQLEndpointTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testExtractQueryInfosAndTypeSelect() {
		$endpoint = new SPARQLEndpoint();

		$query = 'SELECT * WHERE { ?s ?p ?o }';

		list( $qInfo, $qType ) = $this->invokeMethod( $endpoint, 'extractQueryInfosAndType', array( $query ) );

		$this->assertEquals( $qType, 'select' );

		// Check some basic stuff that should be included in the parsed result
		$this->assertType( 'array', $qInfo );
		$this->assertEquals( 's', $qInfo['vars'][0] );
		$this->assertEquals( 'p', $qInfo['vars'][1] );
		$this->assertEquals( 'o', $qInfo['vars'][2] );
	}

	public function testExtractQueryInfosAndTypeConstruct() {
		$endpoint = new SPARQLEndpoint();

		$query = 'CONSTRUCT { ?s ?p ?o } WHERE { ?s ?p ?o }';

		list( $qInfo, $qType ) = $this->invokeMethod( $endpoint, 'extractQueryInfosAndType', array( $query ) );

		$this->assertEquals( $qType, 'construct' );

		// Check some basic stuff that should be included in the parsed result
		$this->assertType( 'array', $qInfo );
		$this->assertEquals( 's', $qInfo['vars'][0] );
		$this->assertEquals( 'p', $qInfo['vars'][1] );
		$this->assertEquals( 'o', $qInfo['vars'][2] );
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	public function invokeMethod(&$object, $methodName, array $parameters = array())
	{
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}
}