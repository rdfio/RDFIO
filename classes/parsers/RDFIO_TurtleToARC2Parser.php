<?php 

class RDFIORDFXMLToARC2Parser extends RDFIOARC2Parser { // TODO: RDFIOARC2Parser does not exist
	
	public function __construct() {
		parent::__construct();
		$this->mArc2Parser = ARC2::getTurtleParser();
	}
	
}