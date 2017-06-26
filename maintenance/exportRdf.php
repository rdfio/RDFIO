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

class BatchExportRDF extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'out', 'A file name for writing the output.', true, true );
		$this->addOption( 'format', 'Serialization format for the exported RDF. (one of rdfxml, turtle or ntriples)', true, true );
		$this->addOption( 'origuris', 'Output the original URIs (set with "Equivalent URI" property in the wiki) for pages', false, false );
	}

	public function execute() {
		$outPath = $this->getOption( 'out', '' );
		$outFile = fopen( $outPath, 'w' );
		$store = new SMWARC2Store();

		$offset = 0;
		$limit = 100;

		while ( true ) {
			$query = 'CONSTRUCT { ?s ?p ?o } WHERE { ?s ?p ?o } OFFSET ' . $offset . ' LIMIT ' . $limit;
			$resultSet = $store->executeArc2Query( $query );
			$index = $resultSet['result'];

			if ( count( $index ) == 0 ) {
				break;
			}

			$triples = ARC2::getTriplesFromIndex( $index );

			// Optionally convert to original URIs
			if ( $this->getOption( 'origuris', false ) ) {
				$arc2storeWrapper = new RDFIOARC2StoreWrapper();
				$triples = $arc2storeWrapper->toEquivUrisInTriples( $triples );
			}

			// Serialize to selected output format
			$format = $this->getOption( 'format', 'rdfxml' );
			switch ( $format ) {
				case 'rdfxml':
					$ser = ARC2::getRDFXMLSerializer();
					break;
				case 'ntriples':
					$ser = ARC2::getNTriplesSerializer();
					break;
				case 'turtle':
					$ser = ARC2::getTurtleSerializer();
					break;
			}

			$rdf = $ser->getSerializedTriples( $triples );
			print_r( $rdf );

			if ( !$ser->getErrors() ) {
				fputs( $outFile, $rdf );
			} else {
				foreach( $ser->getErrors() as $err ) {
					echo( 'ARC2 serializer error: ' . $err );
				}
				die('Exited RDF Export script due to previous errors.');
			}

			$offset += $limit;
		}


		fclose( $outFile );
	}
}

$maintClass = 'BatchExportRDF';

require_once RUN_MAINTENANCE_IF_MAIN;
