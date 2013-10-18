<?php

class RDFIOUtils {
    
    /**
     * Check whether the string starts with 'http://' or 'https://'
     * @param string $str
     * @return boolean
     */
    static function isURI( $str ) {
        return ( substr( $str, 0, 7 ) === 'http://' || substr( $str, 0, 8 ) == 'https://' );
    }
    
    /**
     * Check whether the string ends with a ':'
     * @param string $str
     * @return boolean
     */
    static function endsWithColon( $str ) {
        return ( substr( $str, -1 ) === ':' );
    }

    /**
     * Check whether the string starts with an '_'
     * @param string $str
     * @return boolean
     */
    static function startsWithUnderscore( $str ) {
        return substr( $str, 0, 1 ) === '_';
    }
    
}