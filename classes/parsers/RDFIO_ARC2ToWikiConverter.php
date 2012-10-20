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
			
			$s_uri = $triple['s'];
			$p_uri = $triple['p'];
			$o_uri = $triple['o'];
			
			$wikiTitle = $this->getWikiTitleFromURI($s_uri);
			$propTitle = $this->getPropertyWikiTitleFromURI($triple['p']);
			$objTitle = $this->getWikiTitleFromURI($triple['o']);
			
			$fact = array( 'p' => $propTitle, 'o' => $objTitle );
				
			$wikiPages = $this->mergeIntoPagesArray( $wikiTitle, $s_uri, $fact, $wikiPages );
			$propPages = $this->mergeIntoPagesArray( $propTitle, $p_uri, null, $propPages );
			# if o is an URI, also create object page
			if ( $triple['o_type'] == "uri" ) {
				// @TODO: Should the o_type also decide data type of the property (i.e. page, or value?)
				$wikiPages = $this->mergeIntoPagesArray( $objTitle, $o_uri, null, $wikiPages );
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
		if ( !array_key_exists($pageTitle, $pageTitle) ) {
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
			$pagesArray[$pageTitle]['equivuris'][] = $equivURI;
			if ( $fact != null ) {
				$pagesArray[$pageTitle]['facts'][] = $fact;
			}
		}
		return $pagesArray;
	}
	
	private function getWikiTitleFromURI( $uri ) {
		$wikiTitle = "";
		$wikiTitle = preg_replace("/http.*\//", "", $uri); // @FIXME Dummy method for testing
		return $wikiTitle;
	}
	
	private function getPropertyWikiTitleFromURI( $uri ) {
		$propWikiTitle = $this->getWikiTitleFromURI($uri);
		return $propWikiTitle;
	}
	
}

