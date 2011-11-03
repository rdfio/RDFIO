<?php 

/**
 * 
 * Data object analogous to a literal in RDF, and an SMWDataItem in SMW 
 * (except resources, which have their own data object, RDFIOResource). 
 * 
 * @author samuel lampa
 *
 */
class RDFIOLiteral extends RDFIOResource {
	
	public function __construct() {
		// TODO: Add code
	}
	
	public function getAsText() {
		return $this->getIdentifier();
	}
	
	public static function newFromString( $identifier ) {
		$newResource = new RDFIOLiteral();
		$newResource->setIdentifier( $identifier );
		return $newResource;
	}
}
	