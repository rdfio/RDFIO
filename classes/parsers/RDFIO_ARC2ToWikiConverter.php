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
		
		$this->mARC2ResourceIndex = $arc2ResourceIndex;
		$this->mArc2NSPrefixes = $arc2NSPrefixes;
		
		$wikiPages = array();
		$propPages = array();
		
		foreach ( $arc2Triples as $triple ) {
			
			$subjURI = $triple['s'];
			$propURI = $triple['p'];
			$objURI = $triple['o'];

			// Convert URI:s to wiki titles
			$wikiTitle = $this->convertURIToWikiTitle( $subjURI );
			$propTitle = $this->convertURIToPropertyTitle( $propURI );
			$propTitleWithNS = 'Property:' . $propTitle; 
			$objTitle = "";
			if ( $triple['o_type'] == "uri" ) {
				// @TODO: Should the o_type also decide data type of the property like these: 
				//        http://semantic-mediawiki.org/wiki/Help:Properties_and_types#List_of_datatypes 
				//        ?
				$objTitle = $this->convertURIToWikiTitle( $objURI );
				$wikiPages = $this->mergeIntoPagesArray( $objTitle, $objURI, null, $wikiPages );
			} else {
				$objTitle = $objURI;
			}
			
			$fact = array( 'p' => $propTitle, 'o' => $objTitle );
				
			$wikiPages = $this->mergeIntoPagesArray( $wikiTitle, $subjURI, $fact, $wikiPages );
			$propPages = $this->mergeIntoPagesArray( $propTitleWithNS, $propURI, null, $propPages );
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
	
	private function mergeIntoPagesArray( $pageTitle, $equivURI, $fact = null, $pagesArray ) {
		if ( !array_key_exists( $pageTitle, $pagesArray ) ) {
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
			if ( !in_array($equivURI, $pagesArray[$pageTitle]['equivuris']) ) {
				$pagesArray[$pageTitle]['equivuris'][] = $equivURI;
			}
			if ( $fact != null ) {
				$pagesArray[$pageTitle]['facts'][] = $fact;
			}
		}
		return $pagesArray;
	}
	
	private function convertURIToPropertyTitle( $propURI ) {
		$propertyTitle = '';
		$existingPropTitle = $this->mArc2Store->getWikiTitleByEquivalentURI($propURI, $is_property=true);
		if ( $existingPropTitle != "" ) {
			// If the URI had an existing title, use that
			$propertyTitle = $existingPropTitle;
		} else {
			// As default, use the last part of the URI
			$propertyTitle = preg_replace("/http.*\//", "", $propURI); 
		}
		$propertyTitle = $this->removeInvalidChars( $propertyTitle );
		return $propertyTitle;
	}

	
	private function convertURIToWikiTitle( $uri ) {
		$wikiTitle = "";
		# @TODO: Create some "conversion index", from URI:s to wiki titles?
		global $rdfiogPropertiesToUseAsWikiTitle;
		
		 // 1. [x] Check if the uri exists as Equiv URI already (Overrides everything)
		$existingWikiTitle = $this->mArc2Store->getWikiTitleByEquivalentURI( $uri );
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
				if ( $subject == $uri ) {
					foreach ( $properties as $property => $object ) {
						if ( in_array( $property, $rdfiogPropertiesToUseAsWikiTitle ) ) {
							$wikiTitle = $object[0];
						}
					}
				}
			}
		}
		if ( $wikiTitle != "" ) {
			$wikiTitle = $this->removeInvalidChars( $wikiTitle );
			return $wikiTitle;
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
			$abbreviatedUri = $this->abbreviateParserNSPrefixes( $uri, $nsPrefixes );
			if ( $abbreviatedUri != "" ) {
				return $abbreviatedUri;
			}
		}
		
		 // 6. [x] As a default, just try to get the local part of the URL
		if ( $wikiTitle == "" ) {
			$parts = $this->splitURI( $uri );
			if ( $parts[1] != "" ) {
				return $parts[1];
			}
		}
		 //
		 //    (In all the above, keep properties and normal entities separately)
		
		return $wikiTitle;
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
		
		if ( $basepart == '' &&  $localpart == '' ) {
			$uriParts = $this->splitURI( $uri );
			$basepart = $uriParts[0];
			$localpart = $uriParts[1];
		}

		if ( $localpart == '' ) {
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

