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

class BatchImportRDF extends Maintenance {
	public function __construct() {
		parent::__construct();
		// NTriples is required in order to split lines into chunks. Splitting RDF/XML or Turtle much harder.
		$this->addOption( 'in', 'A file in with RDF data in NTriples format, with one triple per line.', true, true );
		$this->addOption( 'chunksize', 'How many lines (triples) to import at a time. 0 means no chunking.', false, true );
		$this->addOption( 'chunksleep', 'How many seconds (float value) to sleep after each chunk has been imported.', false, true );
		$this->addOption( 'offset', 'Skip this many triples before starting import', false, true );
		$this->addOption( 'verbose', 'Show verbose output', false, false, 'v' );
	}

	public function execute() {
		$inFile = $this->getOption( 'in', '' );
		$chunksize = intval( $this->getOption( 'chunksize', 0 ) );
		$chunksleep = floatval( $this->getOption( 'chunksleep', 0.0 ) );
		$offset = intval( $this->getOption( 'offset', 0 ) );
		$verbose = $this->getOption( 'verbose', false );

		$this->output( "Starting import from file: $inFile\n" );
		if ( $offset > 0 ) {
			$this->output( "Starting with offset $offset ...\n" );
		}

		$rdfImporter = new RDFIORDFImporter();
		$inFileHandle = fopen( $inFile, 'r' );

		$lineinchunk = 1;
		$chunkindex = 1;
		$lineindex = 0;
		$totalimported = 0;
		$importdata = '';
		while ( $line = fgets( $inFileHandle ) ) {
			if ( $lineindex >= $offset ) {
				if ( $chunksize > 0 && $lineinchunk == 1 ) {
					if ( $verbose ) {
						$this->output( "Starting chunk $chunkindex ...\n" );
					}
				}

				$importdata .= $line;

				if ( $verbose ) {
					$this->output( "Appended line $lineinchunk in chunk $chunkindex, to indata ...\n" );
				}

				$totalimported++;

				if ( $chunksize != 0 && $lineinchunk == $chunksize ) {
					$rdfImporter->importTurtle( $importdata );
					$totalwithoffset = $totalimported + $offset;
					$this->output( "Imported $chunksize triples in chunk $chunkindex ($totalimported triples imported in total, and $totalwithoffset including offset)!\n" );

					// Reset variables
					$lineinchunk = 0;
					$importdata = '';

					// Bump chunk index
					$chunkindex++;

					if ( $verbose ) {
						$this->output( 'Now sleeping for ' . strval( $chunksleep ) . ' seconds before continuing with next chunk ...' );
					}
					sleep( $chunksleep );
				}
				$lineinchunk++;
			}
			$lineindex++;
		}
		// Import any remaining stuff, or all the stuff, if chunksize = 0
		$rdfImporter->importTurtle( $importdata );
		fclose( $inFileHandle );
		$this->output( "Finished importing everything ($totalimported triples in total)!\n" );
	}
}

$maintClass = 'BatchImportRDF';

require_once RUN_MAINTENANCE_IF_MAIN;
