<?php 

class RDFIOURIToWikiTitleConverter extends RDFIOParser {
	
	protected $mNamespaces = null;
	
	public function __construct() {
		// ...
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
            $uriParts = RDFIOUtils::splitURI( $uri );
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

    # Getters and setters
    
	public function getNamespaces() { 
	    return $this->mNamespaces;
	}
	public function setNamespaces( $namespaces ) { 
	    $this->mNamespaces = $namespaces;
	}    
}