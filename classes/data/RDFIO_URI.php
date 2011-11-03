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
		// FIXME: Call a URI-to-WikiPageName converter here, later on
		$asWikiPageName = $this->getIdentifier();
		$asWikiPageName = ucfirst( $asWikiPageName );
		$asWikiPageName = str_replace( '_', ' ', $asWikiPageName );
		return $asWikiPageName;
	}

	public static function newFromString( $identifier ) {
		$newResource = new RDFIOURI();
		$newResource->setIdentifier( $identifier );
		return $newResource;
	}
	
}
	