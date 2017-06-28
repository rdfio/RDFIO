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

	/**
	 * Format an error message with HTML, based on a message title and the message
	 * @param string $title
	 * @param string $message
	 * @return string $errorhtml
	 */
	static function fmtErrorMsgHTML( $title, $message ) {
		$errorHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #FF9999; background-color: #FFDDDD;">
				<h3>' . $title . '</h3>
				<p>' . $message . '</p>
								</div>';
		return $errorHtml;
	}

	static function fmtSuccessMsgHTML( $title, $message ) {
		$successHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #99FF99; background-color: #DDFFDD;">
				<h3>' . $title . '</h3>
				<p>' . $message . '</p>
								</div>';
		return $successHtml;
	}

	static function fmtInfoMsgHTML( $title, $message ) {
		$successHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #9999FF; background-color: #DDDDFF;">
				<h3>' . $title . '</h3>
				<p>' . $message . '</p>
								</div>';
		return $successHtml;
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

	/**
	 * Remove some characters that are not allowed in Wiki titles.
	 * @param string $title
	 * @return string $title
	 */
	static function cleanWikiTitle( $title ) {
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
