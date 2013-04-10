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
		
		# Instatiate wiki title converters (converting from URI and related RDF data to Wiki Title)
		$uriToWikiTitleConverter = new RDFIOURIToWikiTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );
		$uriToPropertyTitleConverter = new RDFIOURIToPropertyTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );

		# ========================================================
		# THE MAIN LOOP
		# ========================================================
		# Loop over the triples in the ARC2 triple array structure
		# and perform the actual conversion.
		# ========================================================
		foreach ( $arc2Triples as $triple ) {
			
			# Store triple array members as better named variables
			$subjectURI = $triple['s'];
			$propertyURI = $triple['p'];
			$objectUriOrValue = $triple['o'];
			$objectType = $triple['o_type'];

			/*
			 * TODO: Add detection of category properties here!
			 * 
			 *  These properties should be handled in a special way:
			 *  - rdf:type
			 *  - rdfs:subClassOf
			 *  
			 */
			
			# Convert URI:s to wiki titles
			$wikiPageTitle = $uriToWikiTitleConverter->convert( $subjectURI );

			if ( $propertyURI === 'rdf:type' ) {
				$categoryPageTitle = $uriToWikiTitleConverter->convert( $subjectURI );
				$categoryPageTitleWithNamespace = 'Category:' . $categoryPageTitle;
				$wikiPages = $this->addCategoryPagesToPagesArray( $wikiPageTitle, $subjectURI, $categoryPageTitle, $wikiPages );
			} else {
				# Separate handling for properties
				$propertyTitle = $uriToPropertyTitleConverter->convert( $propertyURI );
				# Add the property namespace to property title
				$propertyTitleWithNamespace = 'Property:' . $propertyTitle;
					
				/*
				 * Decide whether to create a page for the linked "object" or not,
				* depending on object datatype (uri or literal)
				*/
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
						// TODO: Handle error more gracefully!
				}
					
				# Create a fact array
				$fact = array( 'p' => $propertyTitle, 'o' => $objectTitle );
				
				$wikiPages = $this->addPagesAndFactsToPagesArray( $wikiPageTitle, $subjectURI, $fact, $wikiPages );
				// TODO: Why is $propertyTitleWithNamespace used here, and not $propertyTitle ?
				$propPages = $this->addPagesAndFactsToPagesArray( $propertyTitleWithNamespace, $propertyURI, null, $propPages );
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

	/* 
	 * ----------------- PRIVATE FUNCTIONS -------------------
	 */ 

	/**
	 * Collect into custom array structure, representing the wiki pages.
     * The array structure looks like this:
     *
     * Array
     * (
     *    [A wiki page title] => Array
     *        (
     *            [categories] => Array
     *                (
     *                    [0] => <category name>
     *                )
     *            [equivuris] => Array
     *                (
     *                    [0] => http://www.some-equiv-uri.org
     *                )
     *
     *            [facts] => Array
     *                (
     *                    [0] => Array
     *                        (
     *                            [p] => <predicate name>
     *                            [o] => <object name>
     *                        )
	 * 
	 * @param string $pageTitle
	 * @param string $equivURI
	 * @param string $fact
	 * @param array $pagesArray
	 * @return array
	 */
	private function addPagesAndFactsToPagesArray( $pageTitle, $equivURI, $fact = null, $pagesArray ) {
		$pagesArray = $this->ensurePageExistsInPagesArray( $pageTitle, $pagesArray );
		$pagesArray = $this->addEquivalentURIToPageInPagesArray( $equivURI, $pageTitle, $pagesArray );
		$pagesArray = $this->addFactToPageInPagesArray( $fact, $pageTitle, $pagesArray );
		return $pagesArray;
	}
	
	private function addCategoryPagesToPagesArray( $wikiPageTitle, $subjectURI, $categoryPageTitle, $wikiPages ) {
		// TODO: Implement
		if ( array_key_exists( $pageTitle, $pagesArray ) ) {
			// Nothing
		} else {
			// Nothing
		}
	}
	
	/*
	 * Helper functions
	 */
	
	private function ensurePageExistsInPagesArray( $pageTitle, $pagesArray ) {
		# Create page array if not exists
		if ( !array_key_exists( $pageTitle, $pagesArray ) ) {
			$page = $this->createNewPageArray();
			$pagesArray[$pageTitle] = $page;
		}
		return $pagesArray;
	}
	
	private function addEquivalentURIToPageInPagesArray( $equivURI, $pageTitle, $pagesArray ) {
		# Add Equivalent URI, if not exists
		if ( !in_array($equivURI, $pagesArray[$pageTitle]['equivuris']) ) {
			$pagesArray[$pageTitle]['equivuris'][] = $equivURI;
		}
		return $pagesArray;
	}
	
	private function addFactToPageInPagesArray( $fact, $pageTitle, $pagesArray ) {
		# Add fact (property and object)
		if ( !is_null( $fact ) ) {
			// TODO: Detect duplicates?
			$pagesArray[$pageTitle]['facts'][] = $fact;
		}
		return $pagesArray;
	}
	
	private function createNewPageArray() {
		$page = array();
		$page['equivuris'] = array();
		$page['facts'] = array();
		$page['categories'] = array();
		return $page;
	}
		
}

