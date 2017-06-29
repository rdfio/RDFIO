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

	static function inString( $needle, $haystack ) {
		return strpos( $needle, $haystack ) != false;
	}

	static function currentUserHasWriteAccess() {
		global $wgUser;
		$userRights = $wgUser->getRights();
		return ( in_array( 'edit', $userRights ) && in_array( 'createpage', $userRights ) );
	}

	static function showErrorMessage( $title, $message ) {
		global $wgOut;
		$errorHtml = self::fmtErrorMsgHTML( $title, $message );
		$wgOut->addHTML( $errorHtml );
	}

	static function showSuccessMessage( $title, $message ) {
		global $wgOut;
		$successMsgHtml = self::fmtSuccessMsgHTML( $title, $message );
		$wgOut->addHTML( $successMsgHtml );
	}

	static function showInfoMessage( $title, $message ) {
		global $wgOut;
		$successMsgHtml = self::fmtInfoMsgHTML( $title, $message );
		$wgOut->addHTML( $successMsgHtml );
	}
}
