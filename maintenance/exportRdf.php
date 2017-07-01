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
		// Serialize to selected output format
		$format = $this->getOption( 'format', 'rdfxml' );

		// Validate format flag
		if ( !in_array( $format, array( 'rdfxml', 'turtle', 'ntriples' ) ) ) {
			$this->error( "Invalid format supplied: $format. Must be one of: rdfxml, turtle or ntriples", 1 );
		}

		$outFile = fopen( $outPath, 'w' );
		$store = new SMWARC2Store();

		$offset = 0;
		$limit = 250;

		$this->output( "Starting RDF export to file $outPath ...\n" );
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

			if ( $ser->getErrors() ) {
				$this->error("Exited RDF Export script due to previous errors:\n" . implode("\n", $ser->getErrors() ), 1 );
			}

			fputs( $outFile, $rdf );
			$offset += $limit;
		}

		fclose( $outFile );
	}
}

$maintClass = 'BatchExportRDF';

require_once RUN_MAINTENANCE_IF_MAIN;
