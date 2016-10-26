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

define( 'RDFIO_VERSION', '2.0.0' );

$GLOBALS['wgExtensionCredits']['other'][] = array(
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
$GLOBALS['wgExtensionMessagesFiles']['RDFIO'] = $dir . 'RDFIO.i18n.php';
$GLOBALS['wgExtensionMessagesFiles']['RDFIOAliases'] = $dir . 'RDFIO.alias.php';

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
require_once( "$IP/extensions/RDFIO/specials/SpecialRDFIOAdmin.php" );

/**************************
 *    RDFIO Components    *
 **************************/

$rdfioDir = dirname( __FILE__ );

include_once $rdfioDir . '/specials/SpecialRDFImport.php';
include_once $rdfioDir . '/specials/SpecialSPARQLImport.php';
include_once $rdfioDir . '/specials/SpecialSPARQLEndpoint.php'; 

# Misc
$GLOBALS['wgAutoloadClasses']['RDFIOUser'] = $rdfioDir . '/classes/RDFIO_User.php'; 
$GLOBALS['wgAutoloadClasses']['RDFIOUtils'] = $rdfioDir . '/classes/RDFIO_Utils.php'; 
$GLOBALS['wgAutoloadClasses']['RDFIOSMWPageWriter'] = $rdfioDir . '/classes/RDFIO_SMWPageWriter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOWikiWriter'] = $rdfioDir . '/classes/RDFIO_WikiWriter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOARC2StoreWrapper'] = $rdfioDir . '/classes/RDFIO_ARC2StoreWrapper.php';

# Parsers
$GLOBALS['wgAutoloadClasses']['RDFIOParser'] = $rdfioDir . '/classes/parsers/RDFIO_Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIORDFXMLToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_RDFXMLToARC2Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIOTurtleToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_TurtleToARC2Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIORDFImporter'] = $rdfioDir . '/classes/RDFIO_RDFImporter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOARC2ToWikiConverter'] = $rdfioDir . '/classes/parsers/RDFIO_ARC2ToWikiConverter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOURIToWikiTitleConverter'] = $rdfioDir . '/classes/parsers/RDFIO_URIToWikiTitleConverter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOWikiPage'] = $rdfioDir . '/classes/RDFIO_WikiPage.php';
$GLOBALS['wgAutoloadClasses']['RDFIOException'] = $rdfioDir . '/classes/RDFIO_Exception.php';

/**************************
 *     Register hooks     *
 **************************/

include_once $rdfioDir . '/RDFIO.hooks.php';
$wgHooks['UnitTestsList'][] = 'RDFIOHooks::onUnitTestsList';
