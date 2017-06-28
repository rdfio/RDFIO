<?php

/**
 * SpecialRDFIOAdmin is a Special page for setting up the database tables for an ARC2 RDF Store
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOAdmin extends RDFIOSpecialPage {

	function __construct() {
		parent::__construct( 'RDFIOAdmin' );
	}

	/**
	 * @param string $par (unused)
	 */
	function execute( $par ) {
		unset( $par ); // Needed to suppress warning about unused variable which we include just for consistency.
		global $smwgARC2StoreConfig, $wgScriptPath, $wgServer;
		$wUser = $this->getUser();
		$wRequest = $this->getRequest();
		$wOut = $this->getOutput();

		$this->setHeaders();

		$rdfioAction = $wRequest->getText( 'rdfio_action', '' );

		$wOut->addHTML("<h3>RDF Store Setup</h3>" );

		$store = ARC2::getStore( $smwgARC2StoreConfig );
		if ( $store->isSetUp() ) {
			$this->infoMsg( 'Store is already set up.' );
		} else {
			if ( $rdfioAction === 'setup' ) {
				$this->setUpStore( $store, $wUser, $wRequest );
			} else {
				$this->infoMsg( 'Store is <b>not</b> set up' );
				$setupStoreForm = '
				<form method="get"
					action="' . $wgServer . $wgScriptPath . '/index.php/Special:RDFIOAdmin"
					name="createEditQuery">
					<input
						type="submit"
						name="rdfio_action"
						value="setup">' .
					Html::Hidden( 'token', $wUser->getEditToken() ) .
				'</form>';
				$wOut->addHTML( $setupStoreForm );
			}
		}

		$wOut->addWikiText( "\n===Data Sources===\n" );
		$wOut->addWikiText( "\n{{#ask: [[Category:RDFIO Data Source]]
					|?Equivalent URI
					|?RDFIO Import Type
					|format=table
					|mainlabel=Data Source
					|limit=10
					}}\n" );

		$wOut->addWikiText( "\n===Pages and Templates===\n" );
		$wOut->addWikiText( "To associate a template with a category, add <nowiki>[[Has template::Template:Name]]</nowiki> to the Category page" );
		$wOut->addWikiText( "{{#ask:  [[:Category:+]]
					|?Equivalent URI
					|?Has template
					|format=table
					|mainlabel=Category
					|limit=10
					}}" );
	}

	/**
	 * Check permissions and set up the ARC2 store if allowed.
	 * @param $store
	 * @param $wUser
	 * @param $wRequest
	 */
	private function setUpStore( $store, $wUser, $wRequest ) {
		if ( !$this->editTokenOk( $wUser, $wRequest ) ) {
			$this->errorMsg( 'Cross-site request forgery detected!' );
			return;
		}

		if ( !in_array( 'sysop', $wUser->getGroups() ) ) {
			$this->errorMsg( 'Permission Error: Only sysops can perform this operation!' );
			return;
		}

		$store->setUp();

		if ( $store->getErrors() ) {
			$this->errorMsg( 'Error setting up store: ' . implode( "\n", $store->getErrors() ));
			return;
		}

		if ( !$store->isSetUp() ) {
			$this->errorMsg( 'Failed ot set up store, for unknown reason (no errors reported)' );
			return;
		}

		$this->successMsg( 'Store successfully set up!' );
	}
}
