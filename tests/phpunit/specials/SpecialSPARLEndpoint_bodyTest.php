<?php

class RDFIOSPARQLEndpointTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testExtractQueryInfosAndTypeSelect() {
		$ep = new SPARQLEndpoint();

		$query = 'SELECT * WHERE { ?s ?p ?o }';

		list( $qInfo, $qType ) = $this->invokeMethod( $ep, 'extractQueryInfosAndType', array( $query ) );

		$this->assertEquals( $qType, 'select' );

		// Check some basic stuff that should be included in the parsed result
		$this->assertType( 'array', $qInfo );
		$this->assertEquals( 's', $qInfo['vars'][0] );
		$this->assertEquals( 'p', $qInfo['vars'][1] );
		$this->assertEquals( 'o', $qInfo['vars'][2] );
	}

	public function testExtractQueryInfosAndTypeConstruct() {
		$ep = new SPARQLEndpoint();

		$query = 'CONSTRUCT { ?s ?p ?o } WHERE { ?s ?p ?o }';

		list( $qInfo, $qType ) = $this->invokeMethod( $ep, 'extractQueryInfosAndType', array( $query ) );

		$this->assertEquals( $qType, 'construct' );

		// Check some basic stuff that should be included in the parsed result
		$this->assertType( 'array', $qInfo );
		$this->assertEquals( 's', $qInfo['vars'][0] );
		$this->assertEquals( 'p', $qInfo['vars'][1] );
		$this->assertEquals( 'o', $qInfo['vars'][2] );
	}

	public function testExtendQueryPatternsWithEquivUriLinks() {
		$ep = new SPARQLEndpoint();

		$query = 'SELECT * WHERE { <http://ex.org/Sweden> ?p ?o }';

		$patBefore = array(
			array(
				 'type' => 'triple',
				 's'  => 'http://example.org/onto/Sweden',
				 'p'  => 'p',
				 'o'  => 'o',
				 's_type' => 'uri',
				 'p_type' => 'var',
				 'o_type' => 'var',
				 'o_datatype' => '',
				 'o_lang' => ''
			)
		);

		$patExpected = array(
			array(
				'type' => 'triple',
				's'  => 'rdfio_var_0_s',
				'p'  => 'p',
				'o'  => 'o',
				's_type' => 'var',
				'p_type' => 'var',
				'o_type' => 'var',
				'o_datatype' => '',
				'o_lang' => ''
			),
			array(
				'type' => 'triple',
				's'  => 'rdfio_var_0_s',
				'p'  => 'http://www.w3.org/2002/07/owl#sameAs',
				'o'  => 'http://example.org/onto/Sweden',
				's_type' => 'var',
				'p_type' => 'uri',
				'o_type' => 'uri',
				'o_datatype' => '',
				'o_lang' => ''
			)
		);
		$patAfter = $this->invokeMethod( $ep, 'extendQueryPatternsWithEquivUriLinks', array( $patBefore ));

		$this->assertArrayEquals( $patExpected, $patAfter );
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