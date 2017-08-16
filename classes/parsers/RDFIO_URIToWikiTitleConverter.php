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
	 * @param string $uri
	 * @return string $wikiTitle
	 */
	public function convert( $uri ) {
		// Define the conversion functions to try, in
		// specified order (the first one first).
		// You'll find them defined further below in this file.
		$convStrategies = array(
			'getExistingTitleForURI',
			'applyGlobalSettingForPropertiesToUseAsWikiTitle',
			'parseBNode',
			'shortenURINamespaceToAliasInSourceRDF',
			'extractLocalPartFromURI',
			'useValueAsIs'
		);

		foreach ($convStrategies as $currStrategy ) {
			$title = $this->$currStrategy( $uri );

			$title = urldecode( $title ); // If a part of the URL was used
			$title = $this->cleanPageTitle( $title );

			if ($title != '') {
				return $title;
			}
		}
	}

	/////// CONVERSION STRATEGIES ///////

	/**
	 * Strategy 1: Use existing title for URI
	 */
	function getExistingTitleForURI( $uri ) {
		return $this->arc2Store->getWikiTitleByEquivalentURI( $uri );
	}

	/**
	 * Strategy 2: Use configured properties to get the title
	 */
	function applyGlobalSettingForPropertiesToUseAsWikiTitle( $uri ) {
		global $rdfiogTitleProperties;

		$title = '';

		if ( !$this->globalSettingForPropertiesToUseAsWikiTitleExists() ) {
			$this->setglobalSettingForPropertiesToUseAsWikiTitleToDefault();
		}

		$index = $this->arc2ResourceIndex;
		if ( is_array($index) ) {
			foreach ( $index as $subject => $properties ) {
				if ( $subject === $uri ) {
					foreach ( $properties as $prop => $obj ) {
						if ( in_array( $prop, $rdfiogTitleProperties ) ) {
							$title = $obj[0];
						}
					}
				}
			}
		}

		return $title;
	}

	/**
	 * Strategy 3: Check if $uri is a blank node, and if so, add 'BNode_' to the wiki title.
	 * @param $uri
	 * @return string
	 */
	function parseBNode( $uri ) {
		$title = '';

		if ( substr( $uri, 0, 2 ) == '_:' ) {
			$bnodeId = explode( ':', $uri )[1];
			$title = 'Blank_node_' . substr( $bnodeId, 3);
		}

		return $title;
	}

	/**
	 * Strategy 4: Abbreviate the namespace to its NS prefix as configured in
	 * mappings in the parser (default ones, or provided as part of the
	 * imported data)
	 */
	function shortenURINamespaceToAliasInSourceRDF( $uri ) {
		global $rdfiogBaseURIs;
		
		$nsPrefixes = $this->arc2NSPrefixes;
		$title = '';

		// The same, but according to mappings from LocalSettings.php
		if ( is_array( $rdfiogBaseURIs ) ) {
			$nsPrefixes = array_merge( $nsPrefixes, $rdfiogBaseURIs );
		}

		// Collect all the inputs for abbreviation, and apply:
		if ( is_array( $nsPrefixes ) ) {
			$abbreviatedUri = $this->abbreviateParserNSPrefixes( $uri, $nsPrefixes );
			$title = $abbreviatedUri;
		}

		return $title;
	}

	/**
	 * Strategy 5: As a default, just try to get the local part of the URL
	 */
	function extractLocalPartFromURI( $uri ) {
		$title = '';

		$parts = $this->splitURI( $uri );
		if ( $parts[1] != '' ) {
			$title = $parts[1];
		}

		return $title;
	}

	/**
	 * Strategy 6: Just use the value as is, as if it was a literal value
	 */
	function useValueAsIs( $uri ) {
		return $uri;
	}

	/////// HELPER METHODS ///////

	/**
	 * Just tell if $rdfiogTitleProperties is set or not.
	 */
	function globalSettingForPropertiesToUseAsWikiTitleExists() {
		global $rdfiogTitleProperties;
		return isset( $rdfiogTitleProperties );
	}
	
	/**
	 * Default settings for which RDF properties to use for getting
	 * possible candidates for wiki page title names.
	 */
	function setglobalSettingForPropertiesToUseAsWikiTitleToDefault() {
		global $rdfiogTitleProperties;
		$rdfiogTitleProperties = array(
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

		// Make sure both basepart and localpart contains anything before proceeding
		if ( $basepart === '' ||  $localpart === '' ) {
			return '';
		}

		$abbreviatedUri = $basepart . ':' . $localpart;
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

	/**
	 * Remove some characters that are not allowed in Wiki titles.
	 * @param string $title
	 * @return string $title
	 */
	public function cleanPageTitle( $title ) {
		$replacements = array(
			'[' => '',
			']' => '',
			'{{' => '',
			'}}' => '',
			'#' => ':',
		);
		foreach( $replacements as $search => $replace ) {
			$title = str_replace( $search, $replace, $title );
		}
		return $title;
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
		$existingPropTitle = $this->arc2Store->getWikiTitleByEquivalentURI($propertyURI, true);
		if ( $existingPropTitle != "" ) {
			// If the URI had an existing title, use that
			$propertyTitle = $existingPropTitle;
		} else {
			$uriToTitleConv = new RDFIOURIToWikiTitleConverter( $this->arc2Triples, $this->arc2ResourceIndex, $this->arc2NSPrefixes );
			$propertyTitle = $uriToTitleConv->convert( $propertyURI );
		}
		$propertyTitle = $this->cleanPageTitle( $propertyTitle );

		return $propertyTitle;
	}
}	

