<?php

class RDFIOARC2ToWikiConverter extends RDFIOParser {
	
	protected $mARC2ResourceIndex = null;
	protected $mArc2NSPrefixes = null;
	protected $mWikiPages = null;
	protected $mPropPages = null;
	protected $mArc2Store = null;
	
	public function __construct() {
		$this->mArc2Store = new RDFIOARC2StoreWrapper();
	}
	
	public function parseData( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		
		# Store paramters as class variables
		$this->mARC2ResourceIndex = $arc2ResourceIndex;
		$this->mArc2NSPrefixes = $arc2NSPrefixes;
		
		# Initialize variables that hold pages (normal and properties)
		$wikiPages = array();
		$propPages = array();
		
		# Loop over the triples in the ARC2 triple array structure
		foreach ( $arc2Triples as $triple ) {
			
			# Store triple array members as better named variables
			$subjectURI = $triple['s'];
			$propertyURI = $triple['p'];
			$objectUriOrValue = $triple['o'];
			$objectType = $triple['o_type'];

			# Convert URI:s to wiki titles
			$wikiPageTitle = $this->convertURIToWikiTitle( $subjectURI );
			# Separate handling for properties
			$propertyTitle = $this->convertURIToPropertyTitle( $propertyURI );


			$propertyTitleWithNamespace = 'Property:' . $propertyTitle; 

			$objectTitle = '';
			switch ( $objectType ) {
				case 'uri':
					// @TODO: $objectType also decide data type of the property like these: 
					//        http://semantic-mediawiki.org/wiki/Help:Properties_and_types#List_of_datatypes 
					//        ?
					$objectTitle = $this->convertURIToWikiTitle( $objectUriOrValue );
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
	
	private function removeInvalidChars( $title ) {
		$title = str_replace('[', '', $title);
		$title = str_replace(']', '', $title);
		// TODO: Add more here later ...
		return $title;
	}
	
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
				addEquivURIToPageWithTitleInPagesArray(  );
			}
			if ( !is_null( $fact ) ) {
				addFactToPageWithTitleInArray( $fact, $pageTitle, $pagesArray );
			}
		} else {
			# Create new entry for the page in the array
			$page = createNewPageEntryWithFactAndEquivURI( $equivURI, $fact, $pageTitle );
			$pagesArray = addPageToPagesArray( $page, $pageTitle, $pagesArray );
		}

		# MAIN CODE END ###################################################

		return $pagesArray;
	}
	
	private function convertURIToPropertyTitle( $propertyURI ) {
		$propertyTitle = '';
		$existingPropTitle = $this->mArc2Store->getWikiTitleByEquivalentURI($propertyURI, $is_property=true);
		if ( $existingPropTitle != "" ) {
			// If the URI had an existing title, use that
			$propertyTitle = $existingPropTitle;
		} else {
			// As default, use the last part of the URI
			$propertyTitle = preg_replace("/http.*\//", "", $propertyURI); 
		}
		$propertyTitle = $this->removeInvalidChars( $propertyTitle );
		return $propertyTitle;
	}

	
	private function convertURIToWikiTitle( $uri_to_convert ) {
		global $rdfiogPropertiesToUseAsWikiTitle;

		$wikiPageTitle = "";
		# @TODO: Create some "conversion index", from URI:s to wiki titles?
		
		 // 1. [x] Check if the uri exists as Equiv URI already (Overrides everything)
		$existingWikiTitle = $this->mArc2Store->getWikiTitleByEquivalentURI( $uri_to_convert );
		if ( $existingWikiTitle != "" ) {
			return $existingWikiTitle;
		} 

		// 2. [ ] Apply facts suitable for naming (such as dc:title, rdfs:label, skos:prefLabel etc...)
		if ( !isset( $rdfiogPropertiesToUseAsWikiTitle ) ) {
			// Some defaults
			$rdfiogPropertiesToUseAsWikiTitle = array(
				'http://semantic-mediawiki.org/swivt/1.0#page', // Suggestion for new property
            	'http://www.w3.org/2000/01/rdf-schema#label',
		        'http://purl.org/dc/elements/1.1/title',
		        'http://www.w3.org/2004/02/skos/core#preferredLabel',
		        'http://xmlns.com/foaf/0.1/name'
            );
		}
		$index = $this->mARC2ResourceIndex;
		if ( is_array($index) ) {
			foreach ( $index as $subject => $properties ) {
				if ( $subject === $uri_to_convert ) {
					foreach ( $properties as $property => $object ) {
						if ( in_array( $property, $rdfiogPropertiesToUseAsWikiTitle ) ) {
							$wikiPageTitle = $object[0];
						}
					}
				}
			}
		}
		if ( $wikiPageTitle != "" ) {
			$wikiPageTitle = $this->removeInvalidChars( $wikiPageTitle );
			return $wikiPageTitle;
		}
		
		// 3. [x] Shorten the Namespace (even for entities, optionally) into an NS Prefix
		//        according to mappings from parser (Such as chemInf:Blabla ...)
		$nsPrefixes = $this->mArc2NSPrefixes;
		// 4. [x] The same, but according to mappings from LocalSettings.php
		global $rdfiogBaseURIs;
		if ( is_array( $rdfiogBaseURIs ) ) {
			$nsPrefixes = array_merge( $nsPrefixes, $rdfiogBaseURIs );
		}
		// 5. [ ] The same, but according to abbreviation screen
		
		// Collect all the inputs for abbreviation, and apply:
		if ( is_array( $nsPrefixes ) ) {
			$abbreviatedUri = $this->abbreviateParserNSPrefixes( $uri_to_convert, $nsPrefixes );
			if ( $abbreviatedUri != "" ) {
				return $abbreviatedUri;
			}
		}
		
		 // 6. [x] As a default, just try to get the local part of the URL
		if ( $wikiPageTitle === '' ) {
			$parts = $this->splitURI( $uri_to_convert );
			if ( $parts[1] != "" ) {
				return $parts[1];
			}
		}
		 //
		 //    (In all the above, keep properties and normal entities separately)
		
		return $wikiPageTitle;
	}
	
	//
	// ---------- SOME JUNK THAT MIGHT BE USED OR NOT ----------------
	//
	
	function abbreviateParserNSPrefixes( $uri, $nsPrefixes ) {
		foreach ( $nsPrefixes as $namespace => $prefix ) {
			$nslength = strlen( $namespace );
			$basepart = '';
			$localpart = '';
			$uriContainsNamepace = substr( $uri, 0, $nslength ) === $namespace;
			if ( $uriContainsNamepace ) {
				$localpart = substr( $uri, $nslength );
				$basepart = $prefix;
			}
		}

		// ----------------------------------------------------
		// Take care of some special cases:
		// ----------------------------------------------------
		
		if ( $basepart === '' &&  $localpart === '' ) {
			$uriParts = $this->splitURI( $uri );
			$basepart = $uriParts[0];
			$localpart = $uriParts[1];
		}

		if ( $localpart === '' ) {
			$abbreviatedUri = $basepart;
		} elseif ( $this->startsWithUnderscore( $basepart ) ) {
			// FIXME: Shouldn't the above check the local part instead??

			// Change ARC:s default "random string", to indicate more clearly that
			// it lacks title
			$abbreviatedUri = str_replace( 'arc', 'untitled', $localpart );

		} elseif ( $this->startsWithHttpOrHttps( $basepart ) ) {
			// If the abbreviation does not seem to have succeeded,
			// fall back to use only the local part
			$abbreviatedUri = $localpart;

		} elseif ( $this->endsWithColon( $basepart ) ) {
			// Don't add another colon
			$abbreviatedUri = $basepart . $localpart;

		} elseif ( $basepart == false || $basepart == '' ) {
			$abbreviatedUri = $localpart;

		} else {
			$abbreviatedUri = $basepart . ':' . $localpart;

		}

		return $abbreviatedUri;
	}

	function startsWithUnderscore( $str ) {
		return substr( $str, 0, 1 ) == '_';
	}
	function startsWithHttpOrHttps( $str ) {
		return ( substr( $str, 0, 7 ) == 'http://' || substr( $str, 0, 8 ) == 'https://' );
	}
	function endsWithColon( $str ) {
		return substr( $str, -1 ) == ':';
	}
	
	/**
	 * Customized version of the splitURI($uri) of the ARC2 library (http://arc.semsol.org)
	 * Splits a URI into its base part and local part, and returns them as an
	 * array of two strings
	 * @param string $uri
	 * @return array
	 */
	public function splitURI( $uri ) {
		global $rdfiogBaseURIs;
		/* ADAPTED FROM ARC2 WITH SOME MODIFICATIONS
		 * the following namespaces may lead to conflated URIs,
		 * we have to set the split position manually
		 */
		if ( strpos( $uri, 'www.w3.org' ) ) {
			$specials = array(
		        'http://www.w3.org/XML/1998/namespace',
		        'http://www.w3.org/2005/Atom',
		        'http://www.w3.org/1999/xhtml',
			);
			if ( $rdfiogBaseURIs != '' ) {
				$specials = array_merge( $specials, $rdfiogBaseURIs );
			}
			foreach ( $specials as $ns ) {
				if ( strpos( $uri, $ns ) === 0 ) {
					$local_part = substr( $uri, strlen( $ns ) );
					if ( !preg_match( '/^[\/\#]/', $local_part ) ) {
						return array( $ns, $local_part );
					}
				}
			}
		}
		/* auto-splitting on / or # */
		// $re = '^(.*?)([A-Z_a-z][-A-Z_a-z0-9.]*)$';
		if ( preg_match( '/^(.*[\#])([^\#]+)$/', $uri, $matches ) ) {
			return array( $matches[1], $matches[2] );
		}
		if ( preg_match( '/^(.*[\:])([^\:\/]+)$/', $uri, $matches ) ) {
			return array( $matches[1], $matches[2] );
		}
		if ( preg_match( '/^(.*[\/])([^\/]+)$/', $uri, $matches ) ) {
			return array( $matches[1], $matches[2] );
		}        /* auto-splitting on last special char, e.g. urn:foo:bar */
		return array( $uri, '' );

	}

	# Convenience methods

	public function isURIResolverURI( $uri ) {
		return ( preg_match( '/Special:URIResolver/', $uri ) > 0 );
	}
	
}

