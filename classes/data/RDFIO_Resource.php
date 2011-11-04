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
	
	# Convenience methods
	
	public function escapeProblemanticCharsInFacts( $wikiTitle ) {
		$wikiTitle = str_replace( '[', '', $wikiTitle );
		$wikiTitle = str_replace( ']', '', $wikiTitle );
		return $wikiTitle;
	}
	
	# Getters and setters

	public function getIdentifier() { 
	    return $this->mIdentifier;
	}
	public function setIdentifier( $identifier ) { 
	    $this->mIdentifier = $identifier;
	}
	
}
	