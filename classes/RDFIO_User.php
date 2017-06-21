<?php

/**
 * Convenience class for getting information about the current user, such as
 * checking user rights and similar.
 * @author samuel lampa
 */
class RDFIOUser {
	protected $mwUser;

	function __construct( $user ) {
		$this->mwUser = $user;
	}

	public function hasWriteAccess() {
	    $userrights = $this->mwUser->getRights();
	    return (in_array( 'edit', $userrights ) && in_array( 'createpage', $userrights ));
	}

	public function hasDeleteAccess() {
		$userrights = $this->mwUser->getRights();
	    return ( in_array( 'edit', $userrights ) && in_array( 'delete', $userrights ) );
	}

	public function editTokenIsCorrect( $edittoken ) {
		return $this->mwUser->matchEditToken( $edittoken );
	}
}