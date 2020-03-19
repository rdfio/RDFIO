<?php

/**
 * To the extent possible under law,  I, Samuel Lampa, have waived all copyright and
 * related or neighboring rights to Hello World. This work is published from Sweden.
 *
 * @copyright CC0 http://creativecommons.org/publicdomain/zero/1.0/
 * @author Samuel Lampa <samuel.lampa@gmail.com>
 * @ingroup Maintenance
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class SetupArc2Store extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBprefix;
		$arc2StoreConfig = array(
			'db_host' => $wgDBserver,
			'db_name' => $wgDBname,
			'db_user' => $wgDBuser,
			'db_pwd' => $wgDBpassword,
			'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
		);
		$store = ARC2::getStore( $arc2StoreConfig );
		$store->createDBCon();

		if ( $store->isSetUp() ) {
			$this->output( "Store is already set up, so not doing anything.\n" );
			return;
		}

		$this->output( 'ARC2 Store is NOT setup, so setting up now ... ' );
		$store->setUp();

		if ( $store->getErrors() ) {
			$this->error( "Setup failed with the following errors reported by the ARC2 library:\n" . implode( "\n", $store->getErrors() ) . "\n" );
			return;
		}

		if ( $store->isSetUp() ) {
			$this->output( "Store successfully set up!\n" );
		}
	}
}

$maintClass = 'SetupArc2Store';

require_once RUN_MAINTENANCE_IF_MAIN;
