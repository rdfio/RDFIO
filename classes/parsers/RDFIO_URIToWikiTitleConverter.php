<?php 

class RDFIOURIToWikiTitleConverter extends RDFIOParser {
	
    private static $instance;
	
    protected $mNamespacePrefixesFromParser = null;
    	
	public function __construct() {
		// ...
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

    public function execute() {
    	$uri = $this->getInput();
    	$wikiTitle = $this->abbreviateNamespaceForURI( $uri );
    	$this->setResults( $wikiTitle );
    }
	
	# Convenience method, for clearer code
	
	public function convert( $uri ) {
		return $this->executeForData( $uri );
	}
	
    /**
     * Abbreviate the base URI into a "pseudo-wiki-title-namespace"
     * @param string $uri
     * @return string $uri
     */
    public function abbreviateNamespaceForURI( $uri ) {
        $namespacePrefixesFromParser = $this->getNamespacePrefixesFromParser();

		$prefixAndLocalPart = $this->applyNamespacePrefixesFromParser( $uri, $namespacePrefixesFromParser );
		$basepart = $prefixAndLocalPart['basepart']; 
		$localpart = $prefixAndLocalPart['localpart'];

        if ( $basepart == '' &&  $localpart == '' ) {
            $uriParts = $this->splitURI( $uri );
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
    
    public static function startsWithUnderscore( $str ) {
    	return substr( $str, 0, 1 ) == '_';
    }
    public static function startsWithHttpOrHttps( $str ) {
    	return ( substr( $str, 0, 7 ) == 'http://' || substr( $str, 0, 8 ) == 'https://' );
    }
    public static function endsWithColon( $str ) {
    	return substr( $str, -1 ) == ':';
    }
    
    public function applyNamespacePrefixesFromParser( $uri, $prefixesFromParser ) {
        foreach ( $prefixesFromParser as $namespace => $prefix ) {
            $nslength = strlen( $namespace );
            $basepart = '';
            $localpart = '';
            $uriContainsNamepace = substr( $uri, 0, $nslength ) === $namespace;
            if ( $uriContainsNamepace ) {
                $localpart = substr( $uri, $nslength );
                $prefixAndLocalPart = array( 'basepart' => $prefix, 'localpart' => $localpart );
                return $prefixAndLocalPart;
            }
        }
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

    # Getters and setters
    
	public function getNamespacePrefixesFromParser() { 
	    return $this->mNamespacePrefixesFromParser;
	}
	public function setNamespacePrefixesFromParser( $namespacePrefixesFromParser ) { 
	    $this->mNamespacePrefixesFromParser = $namespacePrefixesFromParser;
	}    
}
