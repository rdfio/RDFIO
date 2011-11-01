<?php 

/**
 * 
 * Data object analogous to a resource in RDF, and an SMWDataItem in SMW
 * (except literals, which have their own data object, RDFIOLiteral). 
 * 
 * @author samuel lampa
 *
 */
class RDFIOURI extends RDFIOResource {

	protected $mURI = null;
	
	public function __construct() {
		// TODO: Add code
	}
	
	# Getters and setters
	
	public function getURI() {
		return $this->mURI;
	}
	public function setURI( $uri ) {
		$this->mURI = $uri;
	}
	
}
	