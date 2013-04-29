<?php
class SPARQLImport extends SpecialPage {

	function __construct() {
		parent::__construct( 'SPARQLImport' );
	}

	/**
	 * The main code goes here
	 */
	function execute( $par ) {
		global $wgOut;
		try {
			$this->setHeaders();
			$wgOut->addWikiText("The new Special page works!");
		} catch (RDFIOUIException $e) {
			$this->showErrorMessage('Error!', $e->getMessage());
		}
	}

	/**
	 * Check whether the current user has rights to edit or create pages
	 */
	protected function userHasWriteAccess() {
		global $wgUser;
		$userRights = $wgUser->getRights();
		return ( in_array( 'edit', $userRights ) && in_array( 'createpage', $userRights ) );
	}

	function showErrorMessage( $title, $message ) {
		global $wgOut;
		$errorHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #FF9999; background-color: #FFDDDD;">
                	 <h3>' . $title . '</h3>
                	 <p>' . $message . '</p>
                	 </div>';
		$wgOut->addHTML( $errorHtml );
	}
	

}
