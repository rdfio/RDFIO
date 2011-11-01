<?php 

/**
 * 
 * General ARC2Parser class, not be instanced directly, but overloaded.
 * The idea is that child classes overload the constructor, and loads
 * a different parser object there, based upon which input format they
 * support.
 * @author samuel
 *
 */

class RDFIOARC2Parser extends RDFIOParser {
	
	protected $mArc2Parser = null;
	
	public function __construct() {
		parent::__construct();
		$this->mArc2Parser = ARC2::getRDFXMLParser();
	}
	
	public function execute() {
		$this->mArc2Parser->parseData( $this->getInput() );
	}
	
	public function getResults() {
		return $this->mArc2Parser->getTriples();
	}
	
	public function setResults( $results ) {
		// Nothing ... should not be possible to set anything here.
	}
	
}