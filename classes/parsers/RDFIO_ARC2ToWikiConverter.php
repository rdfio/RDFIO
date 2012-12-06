<?php

class RDFIOARC2ToWikiConverter extends RDFIOParser {
	
	protected $mWikiPages = null;
	protected $mPropPages = null;
	
	public function __construct() {
		
	}
	
	public function convert( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		
		# Initialize variables that hold pages (normal and properties)
		$wikiPages = array();
		$propPages = array();
		
		# Instatiate wiki title converrters (converting from URI and related RDF data to Wiki Title)
		$uriToWikiTitleConverter = new RDFIOURIToWikiTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );
		$uriToPropertyTitleConverter = new RDFIOURIToPropertyTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );

		# Loop over the triples in the ARC2 triple array structure
		foreach ( $arc2Triples as $triple ) {
			
			# Store triple array members as better named variables
			$subjectURI = $triple['s'];
			$propertyURI = $triple['p'];
			$objectUriOrValue = $triple['o'];
			$objectType = $triple['o_type'];

			# Convert URI:s to wiki titles
			$wikiPageTitle = $uriToWikiTitleConverter->convert( $subjectURI );
			# Separate handling for properties
			$propertyTitle = $uriToPropertyTitleConverter->convert( $propertyURI );

			$propertyTitleWithNamespace = 'Property:' . $propertyTitle; 

			$objectTitle = '';
			switch ( $objectType ) {
				case 'uri':
					// @TODO: $objectType also decide data type of the property like these: 
					//        http://semantic-mediawiki.org/wiki/Help:Properties_and_types#List_of_datatypes 
					//        ?
					$objectTitle = $uriToWikiTitleConverter->convert( $objectUriOrValue );
					$wikiPages = $this->addPagesAndFactsToPagesArray( $objectTitle, $objectUriOrValue, null, $wikiPages );
					break;
				case 'literal':
					$objectTitle = $objectUriOrValue;
					break;
				default:
					die("Unknown type of object in triple! (not 'uri' nor 'literal')!");
			}
			
			$fact = array( 'p' => $propertyTitle, 'o' => $objectTitle );
				
			$wikiPages = $this->addPagesAndFactsToPagesArray( $wikiPageTitle, $subjectURI, $fact, $wikiPages );
			$propPages = $this->addPagesAndFactsToPagesArray( $propertyTitleWithNamespace, $propertyURI, null, $propPages );
			// if o is an URI, also create object page
		}
		
		// Store in class variable
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
	
	
	private function addPagesAndFactsToPagesArray( $pageTitle, $equivURI, $fact = null, $pagesArray ) {

		# HELPER FUNCTIONS #################################################

		# Avoid redeclaring helper functions
		if ( !is_callable('helperFunctionsDefined') ) {
			# Use as a check whether the helper functions are 
			# defined or not, to avoid redeclarations
			function helperFunctionsDefined() { }

			function pagesArrayHasPageWithTitle( $pageTitle, $pagesArray ) {
				return array_key_exists( $pageTitle, $pagesArray );
			}
			function pageWithTitleInPagesArrayHasEquivURI( $equivURI, $pageTitle, $pagesArray ) {
				return in_array($equivURI, $pagesArray[$pageTitle]['equivuris']);
			}
			function addFactToPageWithTitleInArray( $fact, $pageTitle, $pagesArray ) {
				$pagesArray[$pageTitle]['facts'][] = $fact;
				return $pagesArray;
			}
			function addEquivURIToPageWithTitleInPagesArray( $equivURI, $pageTitle, $pagesArray ){
				$pagesArray[$pageTitle]['equivuris'][] = $equivURI;
				return $pagesArray;
			}
			function createNewPageEntryWithFactAndEquivURI( $equivURI, $fact, $pageTitle ){
				$page = array();
				$page['equivuris'] = array( $equivURI );
				if ( !is_null( $fact ) ) {
					$page['facts'] = array( $fact );
				} else {
					$page['facts'] = array();
				}
				return $page;
			}
			function addPageToPagesArray( $page, $pageTitle, $pagesArray ) {
				$pagesArray[$pageTitle] = $page;
				return $pagesArray;
			}
		}

		# MAIN CODE START #################################################

		if ( pagesArrayHasPageWithTitle( $pageTitle, $pagesArray ) ) {
			if ( !pageWithTitleInPagesArrayHasEquivURI( $equivURI, $pageTitle, $pagesArray ) ) {
				$pagesArray = addEquivURIToPageWithTitleInPagesArray( $equivURI, $pageTitle, $pagesArray );
			}
			if ( !is_null( $fact ) ) {
				$pagesArray = addFactToPageWithTitleInArray( $fact, $pageTitle, $pagesArray );
			}
		} else {
			# Create new entry for the page in the array
			$page = createNewPageEntryWithFactAndEquivURI( $equivURI, $fact, $pageTitle );
			$pagesArray = addPageToPagesArray( $page, $pageTitle, $pagesArray );
		}

		# MAIN CODE END ###################################################

		return $pagesArray;
	}
	
}

