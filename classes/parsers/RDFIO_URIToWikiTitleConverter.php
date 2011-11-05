<?php

class RDFIOURIToWikiTitleConverter {

	private static $instance;

	protected $mArc2Store = null;
	protected $mNamespacePrefixesFromParser = null;
	protected $mNaturalLanguagePropertyURIs = null;
	protected $mCurrentURIObject = null;

	public function __construct() {
		# Enable access to global settings variables
		global $rdfiogPropertiesToUseAsWikiTitle;
		
		# Init ARC2 Store Wrapper
		$this->setArc2Store( new RDFIOARC2StoreWrapper() );
		
	    if ( isset( $rdfiogPropertiesToUseAsWikiTitle ) ) {
            $this->setNaturalLanguagePropertyURIs( $rdfiogPropertiesToUseAsWikiTitle );
        } else {
        	# Some defaults
            $this->m_wikititlepropertyuris = array(
            	'http://semantic-mediawiki.org/swivt/1.0#page', // Suggestion for new property
            	'http://www.w3.org/2000/01/rdf-schema#label',
            	'http://purl.org/dc/elements/1.1/title',
            	'http://www.w3.org/2004/02/skos/core#preferredLabel',
            	'http://xmlns.com/foaf/0.1/name'
            );
        }		
	}

	public static function singleton()
	{
		if (!isset(self::$instance)) {
			// echo 'Creating new instance.';
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	public function convert( &$uriObj ) {
		
		$uriStr = $uriObj->getIdentifier();
		$this->setCurrentURIObject( $uriObj ); // Needed in order to access Imported data		
		
		/**
		 * This is how to do it:
		 *
		 * 1. [x] Check if the uri exists as Equiv URI already (Overrides everything)
		 * 2. [ ] Apply facts suitable for naming (such as dc:title)
		 * 3. [x] Shorten the Namespace (even for entities, optionally) into an NS Prefix
		 *        according to mappings from parser (Such as chenInf:Blabla ...)
		 * 4. [ ] The same, but according to mappings from LocalSettings.php
		 * 5. [ ] The same, but according to abbreviation screen
		 *
		 *    (In all the above, keep properties and normal entities separately)
		 *
		 */

		if ( !$this->isURIResolverURI( $uriStr ) )
			$wikiTitle = $this->tryToGetExistingWikiTitleForURI( $uriStr );

		if ( empty( $wikiTitle ) )
			$wikiTitle = $this->getWikiTitleByNaturalLanguageProperty( $uriStr );
			
		if ( empty( $wikiTitle ) )
			$wikiTitle = $this->abbreviateWithNamespacePrefixesFromParser( $uriStr );

		$this->setResults( $wikiTitle );
	}

	public function tryToGetExistingWikiTitleForURI( $uri ) {
		$wikititle = $this->getArc2Store()->getWikiTitleByOriginalURI( $uri );
		return $wikititle;
	}

	public static function startsWithUnderscore( $str ) {
		return substr( $str, 0, 1 ) == '_';
	}
	public static function startsWithHttpOrHttps( $str ) {
		return ( substr( $str, 0, 7 ) == 'http://' || substr( $str, 0, 8 ) == 'https://' );
	}
	public static function endsWithColon( $str ) {
		return substr( $str, -1 ) == ':';
	}

	public function abbreviateWithNamespacePrefixesFromParser( $uri ) {
		$nsPrefixesFromParser = $this->getNamespacePrefixesFromParser();
		foreach ( $nsPrefixesFromParser as $namespace => $prefix ) {
			$nslength = strlen( $namespace );
			$basepart = '';
			$localpart = '';
			$uriContainsNamepace = substr( $uri, 0, $nslength ) === $namespace;
			if ( $uriContainsNamepace ) {
				$localpart = substr( $uri, $nslength );
				$basepart = $prefix;
			}
		}

		# ----------------------------------------------------
		# Take care of some special cases:
		# ----------------------------------------------------
		
		if ( $basepart == '' &&  $localpart == '' ) {
			$uriParts = $this->splitURIIntoBaseAndLocalPart( $uri );
			$basepart = $uriParts[0];
			$localpart = $uriParts[1];
		}

		if ( $localpart == '' ) {
			$abbreviatedUri = $basepart;
		} elseif ( RDFIOURIToWikiTitleConverter::startsWithUnderscore( $basepart ) ) {
			// FIXME: Shouldn't the above check the local part instead??

			// Change ARC:s default "random string", to indicate more clearly that
			// it lacks title
			$abbreviatedUri = str_replace( 'arc', 'untitled', $localpart );

		} elseif ( RDFIOURIToWikiTitleConverter::startsWithHttpOrHttps( $basepart ) ) {
			// If the abbreviation does not seem to have succeeded,
			// fall back to use only the local part
			$abbreviatedUri = $localpart;

		} elseif ( RDFIOURIToWikiTitleConverter::endsWithColon( $basepart ) ) {
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
     * Use a "natural language" property, such as dc:title or similar, as wiki title
     * @param string $subject
     * @return string $wikiTitle
     */
    function getWikiTitleByNaturalLanguageProperty( $subjectURI ) {
        // Looks through, in order, the uri:s in $this->m_wikititlepropertyuris
        // to see if any of them is set for $subjectURI. if so, return corresponding
        // value as title.
        // FIXME: Update to work with RDFIO2 Data structures
        $wikiTitle = '';
        $naturalLanguagePropertyURIs = $this->getNaturalLanguagePropertyURIs();
        foreach ( $naturalLanguagePropertyURIs as $naturalLanguagePropertyURI ) {
        	$importedDataAggregate = $this->getCurrentURIObject()->getOwningDataAggregate();
        	$subjectData = $importedDataAggregate->getSubjectDataFromURI( $subjectURI );
        	$fact = $subjectData->getFactFromPropertyURI( $naturalLanguagePropertyURI );

        	if ( isset( $fact ) )
        		$wikiTitle = $fact->getObject()->getAsText();
            if ( !empty( $wikiTitle ) ) {
                // When we have found a "$naturalLanguagePropertyURI" that matches,
                // return the value immediately
                return $wikiTitle;
            }
        }
        return $wikiTitle;
    }

	/**
	 * Customized version of the splitURI($uri) of the ARC2 library (http://arc.semsol.org)
	 * Splits a URI into its base part and local part, and returns them as an
	 * array of two strings
	 * @param string $uri
	 * @return array
	 */
	public function splitURIIntoBaseAndLocalPart( $uri ) {
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

	# Getters and setters

	public function getNamespacePrefixesFromParser() {
		return $this->mNamespacePrefixesFromParser;
	}
	public function setNamespacePrefixesFromParser( $namespacePrefixesFromParser ) {
		$this->mNamespacePrefixesFromParser = $namespacePrefixesFromParser;
	}
	public function getArc2Store() {
		return $this->mArc2Store;
	}
	public function setArc2Store( $arc2Store ) {
		$this->mArc2Store = $arc2Store;
	}
	public function getNaturalLanguagePropertyURIs() { 
	    return $this->mNaturalLanguagePropertyURIs;
	}
	public function setNaturalLanguagePropertyURIs( $naturalLanguagePropertyURIs ) { 
	    $this->mNaturalLanguagePropertyURIs = $naturalLanguagePropertyURIs;
	}
	public function &getCurrentURIObject() { 
	    return $this->mCurrentURIObject;
	}
	public function setCurrentURIObject( &$currentURIObject ) { 
	    $this->mCurrentURIObject = $currentURIObject;
	}
}
