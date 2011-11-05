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
		$wikiPageName = $this->getIdentifier();
		$wikiPageName = $this->escapeProblemanticCharsInFacts( $wikiPageName );
		return $wikiPageName;
	}
	
	public static function newFromString( $identifier, $owningDataAggregate ) {
		$newResource = new RDFIOLiteral();
		$newResource->setIdentifier( $identifier );
		$newResource->setOwningDataAggregate( $owningDataAggregate );
		return $newResource;
	}
}
	