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
		global $smwgARC2StoreConfig;

		# Get ARC2 Store
		$store = ARC2::getStore( $smwgARC2StoreConfig );

		if ( !$store->isSetUp() ) {
			echo( "ARC2 Store is NOT setup, so setting up now ... " );
			$store->setUp();
			if ( $store->isSetUp() ) {
				echo( "Done!\n" );
			} else {
				echo( "Setup failed with the following errors reported by the ARC2 library:\n" );
				echo( $store->getErrors() );
				exit( 1 );
			}
		} else {
			echo( "Store is already set up, so not doing anything.\n" );
		}
	}
}

$maintClass = 'SetupArc2Store';

require_once RUN_MAINTENANCE_IF_MAIN;
