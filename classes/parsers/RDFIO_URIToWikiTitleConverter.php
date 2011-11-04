<?php 

class RDFIOURIToWikiTitleConverter extends RDFIOParser {
	
    private static $instance;
	
    protected $mNamespaces = null;
    	
	public function __construct() {
		// ...
	}
	
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            echo 'Creating new instance.';
            $className = __CLASS__;
            self::$instance = new $className;
        }
        return self::$instance;
    }

    public function execute() {
    	$uri = $this->getInput();
    	$wikiTitle = $this->abbreviateNSFromURI( $uri );
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
    public function abbreviateNSFromURI( $uri ) {
        $prefixes = $this->getNamespaces();

        foreach ( $prefixes as $ns => $prefix ) {
            $nslength = strlen( $ns );
            $uricontainsns = substr( $uri, 0, $nslength ) === $ns;
            if ( $uricontainsns ) {
                $basepart = $prefix;
                $localpart = substr( $uri, $nslength );
            }
        }

        if ( $basepart == '' &&  $localpart == '' ) {
            $uriParts = $this->splitURI( $uri );
            $basepart = $uriParts[0];
            $localpart = $uriParts[1];
        }

        if ( $localpart == '' ) {
            $uri = $basepart;
        } elseif ( substr( $basepart, 0, 1 ) == '_' ) {
            // Change ARC:s default "random string", to indicate more clearly that
            // it lacks title
            $uri = str_replace( 'arc', 'untitled', $localpart );
        } elseif ( substr( $basepart, 0, 7 ) == 'http://' ) {
            // If the abbreviation does not seem to have succeeded,
            // fall back to use only the local part
            $uri = $localpart;
        } elseif ( substr( $basepart, -1 ) == ':' ) {
            // Don't add another colon
            $uri = $basepart . $localpart;
        } elseif ( $basepart == false || $basepart == '' ) {
            $uri = $localpart;
        } else {
            $uri = $basepart . ':' . $localpart;
        }

        return $uri;
    }
    
    /**
     * Customized version of the splitURI($uri) of the ARC2 library (http://arc.semsol.org)
     * Splits a URI into its base part and local part, and returns them as an
     * array of two strings
     * @param string $uri
     * @return array
     */
    static function splitURI( $uri ) {
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
    
	public function getNamespaces() { 
	    return $this->mNamespaces;
	}
	public function setNamespaces( $namespaces ) { 
	    $this->mNamespaces = $namespaces;
	}    
}
