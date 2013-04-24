<?php 

/**
 * Wrapper for ARC2:s turtle parser
 * @author samuel.lampa@gmail.com
 *
 */
class RDFIOTurtleToARC2Parser extends RDFIOARC2Parser { // TODO: RDFIOARC2Parser does not exist
	
	public function __construct() {
		parent::__construct();
		$this->mArc2Parser = ARC2::getTurtleParser();
	}
	
}