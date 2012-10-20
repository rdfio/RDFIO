<?php

class RDFIOARC2ToWikiConverter extends RDFIOParser {
	
	protected $mARC2ResourceIndex = null;
	protected $mWikiPages = null;
	protected $mPropPages = null;
	
	public function __construct() {
		// ...
	}
	
	public function parseData( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		
		$this->mARC2ResourceIndex = $arc2ResourceIndex;
		
		$wikiPages = array();
		$propPages = array();
		
		foreach ( $arc2Triples as $triple ) {
			
			$subjURI = $triple['s'];
			$propURI = $triple['p'];
			$objURI = $triple['o'];

			# Convert URI:s to wiki titles
			$wikiTitle = $this->getWikiTitleFromURI($subjURI);
			$propTitle = $this->getPropertyWikiTitleFromURI($triple['p']);
			$propTitleWithNS = 'Property:' . $propTitle; 
			$objTitle = $this->getWikiTitleFromURI($triple['o']);
			
			$fact = array( 'p' => $propTitle, 'o' => $objTitle );
				
			$wikiPages = $this->mergeIntoPagesArray( $wikiTitle, $subjURI, $fact, $wikiPages );
			$propPages = $this->mergeIntoPagesArray( $propTitleWithNS, $propURI, null, $propPages );
			# if o is an URI, also create object page
			if ( $triple['o_type'] == "uri" ) {
				// @TODO: Should the o_type also decide data type of the property (i.e. page, or value?)
				$wikiPages = $this->mergeIntoPagesArray( $objTitle, $objURI, null, $wikiPages );
			} 
			
		}
		# Store in class variable
		$this->mWikiPages = $wikiPages;
		$this->mPropPages = $propPages;
	}
	
	public function getWikiPages() {
		return $this->mWikiPages;
	}

	public function getPropertyPages() {
		return $this->mPropPages;
	}

	// PRIVATE FUNCTIONS
	
	private function mergeIntoPagesArray( $pageTitle, $equivURI, $fact = null, $pagesArray ) {
		if ( !array_key_exists($pageTitle, $pagesArray) ) {
			$page = array();
			$page['equivuris'] = array( $equivURI );
			if ( $fact != null ) {
				$page['facts'] = array( $fact );
			} else {
				$page['facts'] = array();
			}
			$pagesArray[$pageTitle] = $page;
		} else {
			# Just merge data into existing page
			$page = $pagesArray[$pageTitle];
			$page['equivuris'][] = $equivURI;
			if ( $fact != null ) {
				$page['facts'][] = $fact;
			}
		}
		return $pagesArray;
	}
	
	private function getWikiTitleFromURI( $uri ) {
		# @TODO: Create some "conversion index", from URI:s to wiki titles?
		$wikiTitle = "";
		$wikiTitle = preg_replace("/http.*\//", "", $uri); // @FIXME Dummy method for testing
		return $wikiTitle;
	}
	
	private function getPropertyWikiTitleFromURI( $uri ) {
		$propWikiTitle = $this->getWikiTitleFromURI($uri);
		return $propWikiTitle;
	}
	
}

