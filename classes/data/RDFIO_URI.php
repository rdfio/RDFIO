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

	public function __construct() {
		// TODO: Add code
	}
	
	public function getAsText() {
		return $this->getAsWikiPageName();
	}
	
	public function getAsWikiPageName() {
		$uriToTitleConverter = RDFIOURIToWikiTitleConverter::singleton();
		$asWikiPageName = $uriToTitleConverter->convert( $this );
		$asWikiPageName = ucfirst( $asWikiPageName );
		$asWikiPageName = str_replace( '_', ' ', $asWikiPageName );
		$asWikiPageName = $this->escapeProblemanticCharsInFacts( $asWikiPageName );
		return $asWikiPageName;
	}

	public static function newFromString( $identifier, &$owningDataAggregate ) {
		$newResource = new RDFIOURI();
		$newResource->setIdentifier( $identifier );
		$newResource->setOwningDataAggregate( $owningDataAggregate );
		return $newResource;
	}
	
}
	