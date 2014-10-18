<?php
/**
 * SpecialRDFIOAdmin is a Special page for setting up the database tables for an ARC2 RDF Store
 * @author samuel.lampa@gmail.com
 * @package RDFIO
 */
class RDFIOAdmin extends SpecialPage {

	protected $isSysop;

	function __construct() {
		global $wgUser;

		$userGroups = $wgUser->getGroups();
		if ( in_array( 'sysop', $userGroups ) ) {
			$this->isSysop = true;
		} else {
			$this->isSysop = false;
		}

		parent::__construct( 'RDFIOAdmin', 'editinterface' );
	}

	function execute( $par ) {
		global $wgRequest, $wgOut, $smwgARC2StoreConfig,
			$wgServer, $wgScriptPath, $wgUser;
		
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		};
		
		$this->setHeaders();
		$output = "";

		# Get request data from, e.g.
		$rdfioAction = $wgRequest->getText( 'rdfio_action' );

		# instantiation
		$store = ARC2::getStore( $smwgARC2StoreConfig );

		$output .= "\n===RDF Store Setup===\n'''Status:'''\n\n";

		if ( !$store->isSetUp() ) {
			$output .= "* Store is '''not''' set up\n";
			if ( $rdfioAction == "setup" ) {
				if ( !$wgUser->matchEditToken( $wgRequest->getText( 'token' ) ) ) {
					die( 'Cross-site request forgery detected!' );
				} else {
					if ( $this->isSysop ) {
						$output .= "* Setting up now ...\n";
						$store->setUp();
						if ( $store->isSetUp() ) {
							$output .= "* Done!\n";
						} else {
							$output .= "Setup failed:\n";
							$store->getErrors();	
						}
					} else {
						$errorMessage = "Only sysops can perform this operation!";
						$wgOut->addHTML( "<pre>Permission Error: " . $errorMessage . "</pre>" );
					}
				}
			}
		} else {
			$output .= "* Store is already set up.\n";
		}

		$wgOut->addWikiText( $output );

		$htmlOutput = '<form method="get" action="' . $wgServer . $wgScriptPath . '/index.php/Special:RDFIOAdmin"
			name="createEditQuery">
			<input type="submit" name="rdfio_action" value="setup">' .
			Html::Hidden( 'token', $wgUser->getEditToken() ) . '
			</form>';

		$wgOut->addHTML( $htmlOutput );

		$wgOut->addWikiText("\n===Data Sources===\n");
		$wgOut->addWikiText("\n{{#ask: [[Category:RDFIO Data Source]]
					|?Equivalent URI
					|?RDFIO Import Type
					|format=table
					|mainlabel=Data Source
					|limit=10
					}}\n");
		$wgOut->addWikiText("\n===Pages and Templates===\n");
		
		$wgOut->addWikiText("{{#ask:  [[:Category:+]]
					|?Equivalent URI
					|?Has template
					|format=table
					|mainlabel=Category
					|limit=10
					}}");

	}
}
