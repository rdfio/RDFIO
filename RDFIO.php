<?php
/**
 * Initializing file for SMW RDFIO extension.
 *
 * @file
 * @ingroup RDFIO
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'RDFIO_VERSION', '0.9.0 alpha' );

global $wgExtensionCredits;

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'RDFIO',
	'version' => RDFIO_VERSION,
	'author' => '[http://saml.rilspace.org Samuel Lampa]',
	'url' => 'http://www.mediawiki.org/wiki/Extension:RDFIO',
	'descriptionmsg' => 'rdfio-desc',
);

/****************************
 * i18n
 ****************************/
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['RDFIO'] = $dir . 'RDFIO.i18n.php';
$wgExtensionAliasesFiles['RDFIO'] = $dir . 'RDFIO.alias.php';

/****************************
 * ARC2 RDF library for PHP *
 ****************************/

#$smwgARC2Path = $smwgIP . '/libs/arc/';
#require_once( $smwgARC2Path . '/ARC2.php' );

/**************************
 *  ARC2 RDF Store config *
 **************************/

#$smwgARC2StoreConfig = array(
#              /* Customize these details if you   *
#               * want to use an external database */
#                'db_host' => $wgDBserver,
#                'db_name' => $wgDBname,
#                'db_user' => $wgDBuser,
#                'db_pwd' =>  $wgDBpassword,
#                'store_name' => $wgDBprefix . 'arc2store',
#);
#$smwgDefaultStore = 'SMWARC2Store'; // Determines database table prefix
#
#require_once( "$IP/extensions/RDFIO/stores/SMW_ARC2Store.php" );
#require_once( "$IP/extensions/RDFIO/specials/SpecialARC2Admin.php" );

/**************************
 *    RDFIO Components    *
 **************************/

$rdfioDir = dirname( __FILE__ );

include_once $rdfioDir . '/specials/SpecialRDFImport.php';
#include_once $rdfioDir . '/specials/SpecialSPARQLEndpoint.php';

#$wgAutoloadClasses['RDFIOUtils'] = $rdfioDir . '/classes/Utils.php';
#$wgAutoloadClasses['RDFIOStore'] = $rdfioDir . '/classes/RDFStore.php'; // TODO: This has to be activated I think
#$wgAutoloadClasses['RDFIOSMWBatchWriter'] = $rdfioDir . '/classes/SMWBatchWriter.php';
#$wgAutoloadClasses['RDFIOPageHandler'] = $rdfioDir . '/classes/PageHandler.php';
$wgAutoloadClasses['RDFIOSMWDataImporter'] = $rdfioDir . '/classes/RDFIO_SMWDataImporter.php';
$wgAutoloadClasses['RDFIOWikiWriter'] = $rdfioDir . '/classes/RDFIO_WikiWriter.php';

# Parsers
$wgAutoloadClasses['RDFIOIOService'] = $rdfioDir . '/classes/parsers/RDFIO_IOService.php';
$wgAutoloadClasses['RDFIOParser'] = $rdfioDir . '/classes/parsers/RDFIO_Parser.php';
$wgAutoloadClasses['RDFIOARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_ARC2Parser.php';
$wgAutoloadClasses['RDFIOARC2ToRDFIOParser'] = $rdfioDir . '/classes/parsers/RDFIO_ARC2ToRDFIOParser.php';
$wgAutoloadClasses['RDFIORDFXMLToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_RDFXMLToARC2Parser.php';
$wgAutoloadClasses['RDFIOTurtleToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_TurtleToARC2Parser.php';

# Data objects
$wgAutoloadClasses['RDFIORawData'] = $rdfioDir . '/classes/data/RDFIO_RawData.php';
$wgAutoloadClasses['RDFIODataAggregate'] = $rdfioDir . '/classes/data/RDFIO_DataAggregate.php';
$wgAutoloadClasses['RDFIOSubjectData'] = $rdfioDir . '/classes/data/RDFIO_SubjectData.php';
$wgAutoloadClasses['RDFIOTriple'] = $rdfioDir . '/classes/data/RDFIO_Triple.php';
$wgAutoloadClasses['RDFIOResource'] = $rdfioDir . '/classes/data/RDFIO_Resource.php';
$wgAutoloadClasses['RDFIOURI'] = $rdfioDir . '/classes/data/RDFIO_URI.php';
$wgAutoloadClasses['RDFIOLiteral'] = $rdfioDir . '/classes/data/RDFIO_Literal.php';
