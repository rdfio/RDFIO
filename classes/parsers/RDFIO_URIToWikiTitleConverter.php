<?php

/**
 * Exception used in the RDFIOURIToTitleConverter class
 */
class WikiTitleNotFoundException extends MWException { }

/**
 * Converter that takes an RDF URI and returns a suitable Wiki title for that URI
 * based on various strategies, which are tried one at a time, until a usable title 
 * is found.
 * @author samuel
 *
 */
class RDFIOURIToTitleConverter { 

	protected $arc2Triples = null;
	protected $arc2ResourceIndex = null;
	protected $arc2NSPrefixes = null;
	protected $arc2Store = null;

	function __construct( $arc2Triples, $arc2ResourceIndex, $arc2NSPrefixes ) {
		$this->arc2Store = new RDFIOARC2StoreWrapper();

		// Store paramters as class variables
		$this->arc2Triples = $arc2Triples;
		$this->arc2ResourceIndex = $arc2ResourceIndex;
		$this->arc2NSPrefixes = $arc2NSPrefixes;
	}

	/**
	 * The main method, converting from URI:s to wiki titles.
	 * NOTE: Properties are taken care of py a special method below!
	 * @param string $uriToConvert
	 * @return string $wikiTitle
	 */
	public function convert( $uriToConvert ) {
		global $wgOut;

		// Define the conversion functions to try, in 
		// specified order (the first one first).
		// You'll find them defined further below in this file.
		$convStrategies = array(
			'getExistingTitleForURI',
			'applyGlobalSettingForPropertiesToUseAsWikiTitle',
			'shortenURINamespaceToAliasInSourceRDF',
			'extractLocalPartFromURI'
		);

		$wikiPageTitle = '';

		foreach ($convStrategies as $currStrategy ) {
			$wikiPageTitle = $this->$currStrategy( $uriToConvert );
			if ($wikiPageTitle != null) {
				return $wikiPageTitle;
			}
		}
	}

	/////// CONVERSION STRATEGIES ///////

	/**
	 * Strategy 1: Use existing title for URI
	 */
	function getExistingTitleForURI( $uri ) {
		$wikiTitle = $this->arc2Store->getWikiTitleByEquivalentURI( $uri );
		if ( $wikiTitle != '' ) {
			return $wikiTitle;
		} else {
			return null;
		}
	}

	/**
	 * Strategy 2: Use configured properties to get the title
	 */
	function applyGlobalSettingForPropertiesToUseAsWikiTitle( $uri ) {
		global $rogTitleProperties;

		$wikiPageTitle = '';

		if ( !$this->globalSettingForPropertiesToUseAsWikiTitleExists() ) {
			$this->setglobalSettingForPropertiesToUseAsWikiTitleToDefault();
		}

		$index = $this->arc2ResourceIndex;
		if ( is_array($index) ) {
			foreach ( $index as $subject => $properties ) {
				if ( $subject === $uri ) {
					foreach ( $properties as $property => $object ) {
						if ( in_array( $property, $rogTitleProperties ) ) {
							$wikiPageTitle = $object[0];
						}
					}
				}
			}
		}

		if ( $wikiPageTitle != '' ) {
			$wikiPageTitle = RDFIOUtils::cleanWikiTitle( $wikiPageTitle );
		}
		if ( $wikiPageTitle != '' ) {
			return $wikiPageTitle;
		} else {
			return null;
		}
	}	

	/**
	 * Strategy 3: Abbreviate the namespace to its NS prefix as configured in
	 * mappings in the parser (default ones, or provided as part of the
	 * imported data)
	 */
	function shortenURINamespaceToAliasInSourceRDF( $uriToConvert ) {
		global $rogBaseURIs;
		
		$nsPrefixes = $this->arc2NSPrefixes;
		$wikiPageTitle = '';

		// The same, but according to mappings from LocalSettings.php
		if ( is_array( $rogBaseURIs ) ) {
			$nsPrefixes = array_merge( $nsPrefixes, $rogBaseURIs );
		}

		// Collect all the inputs for abbreviation, and apply:
		if ( is_array( $nsPrefixes ) ) {
			$abbreviatedUri = $this->abbreviateParserNSPrefixes( $uriToConvert, $nsPrefixes );
			$wikiPageTitle = $abbreviatedUri;
		}

		if ( $wikiPageTitle != '' ) {
			return $wikiPageTitle;
		} else {
			return null;
		}	
	}

	/**
	 * Strategy 4: As a default, just try to get the local part of the URL
	 */
	function extractLocalPartFromURI( $uriToConvert ) {
		$parts = $this->splitURI( $uriToConvert );
		if ( $parts[1] != '' ) {
			$wikiPageTitle = $parts[1];
		}

		if ( $wikiPageTitle != '' ) {
			return $wikiPageTitle;
		} else {
			return null;
		}	
	}

	/////// HELPER METHODS ///////

	/**
	 * Just tell if $rogTitleProperties is set or not.
	 */
	function globalSettingForPropertiesToUseAsWikiTitleExists() {
		global $rogTitleProperties;
		return isset( $rogTitleProperties );
	}
	
	/**
	 * Default settings for which RDF properties to use for getting
	 * possible candidates for wiki page title names.
	 */
	function setglobalSettingForPropertiesToUseAsWikiTitleToDefault() {
		global $rogTitleProperties;
		$rogTitleProperties = array(
			'http://semantic-mediawiki.org/swivt/1.0#page', // Suggestion for new property
			'http://www.w3.org/2000/01/rdf-schema#label',
			'http://purl.org/dc/terms/title',
			'http://purl.org/dc/elements/1.1/title',
			'http://www.w3.org/2004/02/skos/core#preferredLabel',
			'http://xmlns.com/foaf/0.1/name'
		);
	}

	/**
	 * Use the namespaces from the RDF / SPARQL source, to shorten the URIs.
	 * @param string $uri
	 * @param array $nsPrefixes
	 * @return string
	 */
	function abbreviateParserNSPrefixes( $uri, $nsPrefixes ) {
		foreach ( $nsPrefixes as $namespace => $prefix ) {
			$nslength = strlen( $namespace );
			$basepart = '';
			$localpart = '';
			$uriContainsNamepace = substr( $uri, 0, $nslength ) === $namespace;
			if ( $uriContainsNamepace ) {
				$localpart = substr( $uri, $nslength );
				$basepart = $prefix;
				break;
			}
		}

		/*
		 * Take care of some special cases:
		 */
		if ( $basepart === '' &&  $localpart === '' ) {
			$uriParts = $this->splitURI( $uri );
			$basepart = $uriParts[0];
			$localpart = $uriParts[1];
		}

		if ( $localpart === '' ) {
			$abbreviatedUri = $basepart;
		} elseif ( RDFIOUtils::isURI( $basepart ) ) {
			// FIXME: Shouldn't the above check the local part instead??

			// Change ARC:s default "random string", to indicate more clearly that
			// it lacks title
			$abbreviatedUri = str_replace( 'arc', 'untitled', $localpart );

		} elseif ( RDFIOUtils::isURI( $basepart ) ) {
			// If the abbreviation does not seem to have succeeded,
			// fall back to use only the local part
			$abbreviatedUri = $localpart;

		} elseif ( RDFIOUtils::endsWithColon( $basepart ) ) {
			// Don't add another colon
			$abbreviatedUri = $basepart . $localpart;

		} elseif ( $basepart == false || $basepart == '' ) {
			$abbreviatedUri = $localpart;

		} else {
			$abbreviatedUri = $basepart . ':' . $localpart;

		}

		return $abbreviatedUri;
	}


	/**
	 * Customized version of the splitURI($uri) of the ARC2 library (http://arc.semsol.org)
	 * Splits a URI into its base part and local part, and returns them as an
	 * array of two strings
	 * @param string $uri
	 * @return array
	 */
	public function splitURI( $uri ) {
		global $rogBaseURIs;
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
			if ( $rogBaseURIs != '' ) {
				$specials = array_merge( $specials, $rogBaseURIs );
			}
			foreach ( $specials as $ns ) {
				if ( strpos( $uri, $ns ) === 0 ) {
					$localPart = substr( $uri, strlen( $ns ) );
					if ( !preg_match( '/^[\/\#]/', $localPart ) ) {
						return array( $ns, $localPart );
					}
				}
			}
		}
		// auto-splitting on / or #
		if ( preg_match( '/^(.*[\#])([^\#]+)$/', $uri, $matches ) ) {
			return array( $matches[1], $matches[2] );
		}
		if ( preg_match( '/^(.*[\:])([^\:\/]+)$/', $uri, $matches ) ) {
			return array( $matches[1], $matches[2] );
		}
		// auto-splitting on last special char, e.g. urn:foo:bar
		if ( preg_match( '/^(.*[\/])([^\/]+)$/', $uri, $matches ) ) {
			return array( $matches[1], $matches[2] );
		} 
		return array( $uri, '' );
	}

}

/**
 * Subclass of the more general RDFIOURIToTitleConverter.
 * For normal wiki pages. 
 */
class RDFIOURIToWikiTitleConverter extends RDFIOURIToTitleConverter {}

/**
 * Subclass of the more general RDFIOURIToTitleConverter
 * For property pages (those where titles start with "Property:")  
 */
class RDFIOURIToPropertyTitleConverter extends RDFIOURIToTitleConverter {

	/**
	 * The main method, which need some special handling.
	 * @param string $propertyURI
	 * @return string $propertyTitle
	 */
	function convert( $propertyURI ) {
		$propertyTitle = '';
		$existingPropTitle = $this->arc2Store->getWikiTitleByEquivalentURI($propertyURI, $isProperty=true);
		if ( $existingPropTitle != "" ) {
			// If the URI had an existing title, use that
			$propertyTitle = $existingPropTitle;
		} else {
			$uriToTitleConv = new RDFIOURIToWikiTitleConverter( $this->arc2Triples, $this->arc2ResourceIndex, $this->arc2NSPrefixes );
			$propertyTitle = $uriToTitleConv->convert( $propertyURI );
		}
		$propertyTitle = RDFIOUtils::cleanWikiTitle( $propertyTitle );
		return $propertyTitle;
	}

}	

