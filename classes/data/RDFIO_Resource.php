<?php 

/**
 * 
 * @author samuel lampa
 *
 */
class RDFIOResource {
	
	protected $mIdentifier = null;
		
	public function __construct() {
		// TODO: Add code
	}
	
	# Getters and setters

	public function getIdentifier() { 
	    return $this->mIdentifier;
	}
	public function setIdentifier( $identifier ) { 
	    $this->mIdentifier = $identifier;
	}
	
}
	