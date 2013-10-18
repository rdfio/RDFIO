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

define( 'RDFIO_VERSION', '1.9.5 beta' ); // TODO: UPdate

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

$smwgARC2Path = $smwgIP . '/libs/arc/';
$smwgARC2MainFile = $smwgARC2Path . '/ARC2.php';
if ( ! file_exists( $smwgARC2MainFile )) {
    throw new MWException("ARC2 library is not installed in Semantic Mediawiki libs folder! Please refer to the installation instructions, to fix that: http://www.mediawiki.org/wiki/Extension:RDFIO#Installation");
} else {
    require_once( $smwgARC2MainFile );
}

/**************************
 *  ARC2 RDF Store config *
 **************************/

/* Customize these details if you   *
 * want to use an external database */
$smwgARC2StoreConfig = array(
		'db_host' => $wgDBserver,
        'db_name' => $wgDBname,
        'db_user' => $wgDBuser,
        'db_pwd' =>  $wgDBpassword,
        'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
);

$smwgDefaultStore = 'SMWARC2Store'; 

require_once( "$IP/extensions/RDFIO/stores/SMW_ARC2Store.php" );
require_once( "$IP/extensions/RDFIO/specials/SpecialARC2Admin.php" );

/**************************
 *    RDFIO Components    *
 **************************/

$rdfioDir = dirname( __FILE__ );

include_once $rdfioDir . '/specials/SpecialRDFImport.php';
include_once $rdfioDir . '/specials/SpecialSPARQLImport.php';
include_once $rdfioDir . '/specials/SpecialSPARQLEndpoint.php'; 

# Misc
$wgAutoloadClasses['RDFIOUser'] = $rdfioDir . '/classes/RDFIO_User.php'; 
$wgAutoloadClasses['RDFIOUtils'] = $rdfioDir . '/classes/RDFIO_Utils.php'; 
$wgAutoloadClasses['RDFIOSMWPageWriter'] = $rdfioDir . '/classes/RDFIO_SMWPageWriter.php';
$wgAutoloadClasses['RDFIOWikiWriter'] = $rdfioDir . '/classes/RDFIO_WikiWriter.php';
$wgAutoloadClasses['RDFIOARC2StoreWrapper'] = $rdfioDir . '/classes/RDFIO_ARC2StoreWrapper.php';

# Parsers
$wgAutoloadClasses['RDFIOParser'] = $rdfioDir . '/classes/parsers/RDFIO_Parser.php';
$wgAutoloadClasses['RDFIORDFXMLToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_RDFXMLToARC2Parser.php';
$wgAutoloadClasses['RDFIOTurtleToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_TurtleToARC2Parser.php';
$wgAutoloadClasses['RDFIORDFImporter'] = $rdfioDir . '/classes/RDFIO_RDFImporter.php';
$wgAutoloadClasses['RDFIOARC2ToWikiConverter'] = $rdfioDir . '/classes/parsers/RDFIO_ARC2ToWikiConverter.php';
$wgAutoloadClasses['RDFIOURIToWikiTitleConverter'] = $rdfioDir . '/classes/parsers/RDFIO_URIToWikiTitleConverter.php';
$wgAutoloadClasses['RDFIOWikiPage'] = $rdfioDir . '/classes/RDFIO_WikiPage.php';
$wgAutoloadClasses['RDFIOException'] = $rdfioDir . '/classes/RDFIO_Exception.php';

/**************************
 *     Register hooks     *
 **************************/

include_once $rdfioDir . '/RDFIO.hooks.php';
$wgHooks['UnitTestsList'][] = 'RDFIOHooks::onUnitTestsList';
