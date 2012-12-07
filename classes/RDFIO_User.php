<?php

class RDFIOUser {
	protected $m_mwuser;

	function __construct() {
		global $wgUser;
		$this->m_mwuser = $wgUser;
	}

    public function hasWriteAccess() {
        $userrights = $this->m_mwuser->getRights();
        return (in_array( 'edit', $userrights ) && in_array( 'createpage', $userrights ));
    }
    public function hasDeleteAccess() {
    	$userrights = $this->m_mwuser->getRights();
        return ( in_array( 'edit', $userrights ) && in_array( 'delete', $userrights ) );
    }
    public function editTokenIsCorrect( $edittoken ) {
    	return $this->m_mwuser->matchEditToken( $edittoken );
    }
}