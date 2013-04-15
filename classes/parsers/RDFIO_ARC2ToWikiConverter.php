<?php

class RDFIOARC2ToWikiConverter extends RDFIOParser {
	protected $mWikiPages = null;
	protected $mPropPages = null;
	
	
	public function __construct() {
		$this->mWikiPages = array();
	}
	
	
	public function convert( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		# Instatiate wiki title converters (converting from URI and related RDF data to Wiki Title)
		$uriToWikiTitleConverter = new RDFIOURIToWikiTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );
		$uriToPropertyTitleConverter = new RDFIOURIToPropertyTitleConverter( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes );
		
		/*
		 * The main loop, doing the convertion of triples into 
		 * a representation of wiki pages instead.
		 */
		foreach ( $arc2Triples as $triple ) {
			
			# Store triple array members as better named variables
			$subjectURI = $triple['s'];
			$propertyURI = $triple['p'];
			$objectUriOrValue = $triple['o'];
			$objectType = $triple['o_type'];
			
			# Convert URI:s to wiki titles
			$wikiPageTitle = $uriToWikiTitleConverter->convert( $subjectURI );

			if ( $propertyURI === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' ) {

				# Add categorization of page
				$categoryPageTitle = $uriToWikiTitleConverter->convert( $objectUriOrValue );
				$categoryPageTitleWithNamespace = 'Category:' . $categoryPageTitle;
				# Add data for the subject page
				$this->addDataToPage( $wikiPageTitle, $subjectURI, $fact = null, $categoryPageTitleWithNamespace ); // TODO: Use i18n:ed NS
				# Add data for the category page
				$this->addDataToPage( $categoryPageTitleWithNamespace, $objectUriOrValue ); // TODO: Use i18n:ed NS
				
			} else if ( $propertyURI === 'http://www.w3.org/2000/01/rdf-schema#subClassOf' ) {

				# Add categorization of page
				$categoryPageTitle = $uriToWikiTitleConverter->convert( $objectUriOrValue );
				$categoryPageTitleWithNamespace = 'Category:' . $categoryPageTitle;
				$wikiPageTitleWithNamespace = 'Category:' . $wikiPageTitle;

				# Add data for the subject page
				$this->addDataToPage( $wikiPageTitleWithNamespace, $subjectURI, $fact = null, $categoryPageTitleWithNamespace ); 
				
				# Add data for the category page
				$this->addDataToPage( $categoryPageTitleWithNamespace, $objectUriOrValue ); 
			
			} else {
				# Separate handling for properties
				$propertyTitle = $uriToPropertyTitleConverter->convert( $propertyURI );
				# Add the property namespace to property title
				$propertyTitleWithNamespace = 'Property:' . $propertyTitle; // TODO: Use i18n:ed NS
					
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
						$this->addDataToPage( $objectTitle, $objectUriOrValue );
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
				
				# Add data to class variables
				$this->addDataToPage( $wikiPageTitle, $subjectURI, $fact  );
				$this->addDataToPage( $propertyTitleWithNamespace, $propertyURI );
			}
		}
		
		return $this->mWikiPages;
	}

	
	private function addDataToPage( $pageTitle, $equivURI, $fact = null, $category = null ) {
		# Create page array if not exists in array
		if ( !array_key_exists( $pageTitle, $this->mWikiPages ) ) {
			$this->mWikiPages[$pageTitle] = new RDFIOWikiPage($pageTitle);
		}
		$this->mWikiPages[$pageTitle]->addEquivalentURI( $equivURI );
		$this->mWikiPages[$pageTitle]->addFact( $fact );
		$this->mWikiPages[$pageTitle]->addCategory( $category );
	}

	
	private function addCategoryPagesToPagesArray( $wikiPageTitle, $subjectURI, $categoryPageTitle, $wikiPages ) {
		// TODO: Implement
		if ( array_key_exists( $pageTitle, $pagesArray ) ) {
			// Nothing
		} else {
			// Nothing
		}
	}
		
}

