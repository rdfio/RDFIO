<?php

class RDFIOARC2Parser extends RDFIOParser {
	
	protected $mInputType = null;

	public function __construct() {
		parent::__construct();
	}
	
	public function execute() {
		switch ( $this->getInput()->getDataType() ) {
			case ( 'rdfxml' ): 
				$this->mExternalParser = ARC2::getRDFXMLParser();
				break;
			case ( 'turtle' ):
				$this->mExternalParser = ARC2::getTurtleParser();
			default:
				// TODO: Add some error message!
		}
		
		# Execute the external parser
		$this->mExternalParser->parseData( $this->getInput()->getData() );
		
		# Collect results
		$resultData = new RDFIORawData();
		$resultData->setData( $this->mExternalParser->getTriples() );
		$resultData->setDataType( 'arc2triples' );
		$this->setResults( $resultData ); 
	}
}
