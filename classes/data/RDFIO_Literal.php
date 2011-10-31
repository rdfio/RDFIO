<?php 

/**
 * 
 * Data object analogous to a literal in RDF, and an SMWDataItem in SMW 
 * (except resources, which have their own data object, RDFIOResource). 
 * 
 * @author samuel lampa
 *
 */
class RDFIOLiteral extends RDFIODataItem {
	
	protected $mAsString = null;
		
	public function __construct() {
		// TODO: Add code
	}
	
	# Getters and setters
	
	public function getAsString() { 
	    return $this->mAsString;
	}
	public function setAsString( $asString ) { 
	    $this->mAsString = $asString;
	}

}
	