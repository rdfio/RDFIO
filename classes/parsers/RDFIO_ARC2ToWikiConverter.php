<?php

class RDFIOARC2ToWikiConverter extends RDFIOParser {
	
	protected $mARC2ResourceIndex = null;
	
	public function __construct() {
		// ...
	}
	
	public function parseData( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		
		$this->mARC2ResourceIndex = $arc2ResourceIndex;
		
		$wikiPages = array();
		
		foreach ( $arc2Triples as $triple ) {
			
			# Create a wiki page for the subject
			# If o is a literal, add, only as fact to subject
			# else if o is an URI, also create property and object pages
			# All the time, add the EquivURI fact to all pages
			
		}
	}
	
	private function getWikiTitleFromURI( $uri ) {
		$wikiTitle = "";
		$wikiTitle = preg_replace("/http.*\//", "", $uri); // @FIXME Dummy method for testing
		return $wikiTitle;
	}
	
	private function getPropertyWikiTitleFromURI( $uri ) {
		$propWikiTitle = "Property:" . $this->getWikiTitleFromURI($uri);
		return $propWikiTitle;
	}
	
}

