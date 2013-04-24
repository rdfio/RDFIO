<?php 

/**
 * Wrapper for ARC2:s RDF/XML parser
 * @author samuel.lampa@gmail.com
 *
 */
class RDFIORDFXMLToARC2Parser extends RDFIOParser { 
	
	public function __construct() {
		parent::__construct();
		$this->mArc2Parser = ARC2::getRDFXMLParser();
	}
	
}