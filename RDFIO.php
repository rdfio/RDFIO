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

// -------------------------------------------------------------
// Extension meta data
// -------------------------------------------------------------
define( 'RDFIO_VERSION', 'v2.1.1' );

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'RDFIO',
	'version' => RDFIO_VERSION,
	'author' => array('[http://bionics.it Samuel Lampa]','[http://koshatnik.com Ali King]'),
	'url' => 'http://www.mediawiki.org/wiki/Extension:RDFIO',
	'descriptionmsg' => 'rdfio-desc',
);

// -------------------------------------------------------------
// internationalization
// -------------------------------------------------------------
$dir = dirname( __FILE__ ) . '/';
$GLOBALS['wgExtensionMessagesFiles']['RDFIO'] = $dir . 'RDFIO.i18n.php';
$GLOBALS['wgExtensionMessagesFiles']['RDFIOAliases'] = $dir . 'RDFIO.alias.php';

// -------------------------------------------------------------
// Load RDFIO Components
// -------------------------------------------------------------
$rdfioDir = dirname( __FILE__ );

$GLOBALS['wgAutoloadClasses']['SMWARC2Store'] = $rdfioDir . '/stores/SMW_ARC2Store.php';
$GLOBALS['wgAutoloadClasses']['RDFIOARC2StoreException'] = $rdfioDir . '/stores/SMW_ARC2Store.php';

// Misc
$GLOBALS['wgAutoloadClasses']['RDFIOARC2StoreWrapper'] = $rdfioDir . '/classes/RDFIO_ARC2StoreWrapper.php';
$GLOBALS['wgAutoloadClasses']['RDFIOSMWPageWriter'] = $rdfioDir . '/classes/RDFIO_SMWPageWriter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOTestCase'] = $rdfioDir . '/tests/phpunit/RDFIOTestCase.php';
$GLOBALS['wgAutoloadClasses']['RDFIOWikiWriter'] = $rdfioDir . '/classes/RDFIO_WikiWriter.php';

// Parsers
$GLOBALS['wgAutoloadClasses']['ARC2_SPARQLSerializerPlugin'] = $rdfioDir . '/vendor/ARC2_SPARQLSerializerPlugin.php';
$GLOBALS['wgAutoloadClasses']['RDFIOARC2ToWikiConverter'] = $rdfioDir . '/classes/parsers/RDFIO_ARC2ToWikiConverter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOException'] = $rdfioDir . '/classes/RDFIO_Exception.php';
$GLOBALS['wgAutoloadClasses']['RDFIOParser'] = $rdfioDir . '/classes/parsers/RDFIO_Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIORDFImporter'] = $rdfioDir . '/classes/RDFIO_RDFImporter.php';
$GLOBALS['wgAutoloadClasses']['RDFIORDFXMLToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_RDFXMLToARC2Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIOTurtleToARC2Parser'] = $rdfioDir . '/classes/parsers/RDFIO_TurtleToARC2Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIOURIToWikiTitleConverter'] = $rdfioDir . '/classes/parsers/RDFIO_URIToWikiTitleConverter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOWikiPage'] = $rdfioDir . '/classes/RDFIO_WikiPage.php';

// Special pages
$GLOBALS['wgAutoloadClasses']['RDFIOSpecialPage'] = $rdfioDir . '/classes/RDFIO_SpecialPage.php';
$GLOBALS['wgAutoloadClasses']['RDFIOAdmin'] = $rdfioDir . '/specials/SpecialRDFIOAdmin.php';
$GLOBALS['wgAutoloadClasses']['RDFImport'] = $rdfioDir . '/specials/SpecialRDFImport.php';
$GLOBALS['wgAutoloadClasses']['SPARQLEndpoint'] = $rdfioDir . '/specials/SpecialSPARQLEndpoint.php';
$GLOBALS['wgAutoloadClasses']['SPARQLImport'] = $rdfioDir . '/specials/SpecialSPARQLImport.php';

$GLOBALS['wgSpecialPages']['RDFIOAdmin'] = 'RDFIOAdmin';
$GLOBALS['wgSpecialPages']['RDFImport'] = 'RDFImport';
$GLOBALS['wgSpecialPages']['SPARQLEndpoint'] = 'SPARQLEndpoint';
$GLOBALS['wgSpecialPages']['SPARQLImport'] = 'SPARQLImport';

// -------------------------------------------------------------
// ARC2 RDF Store config
// -------------------------------------------------------------
// Has to be made as an wgExtensionFunction so as to get access to
// LocalSettings variables, as suggested by @mwjames in
// https://github.com/rdfio/RDFIO/issues/13#issuecomment-256414481
$GLOBALS['wgExtensionFunctions'][] = function() {
	global $wgDBtype, $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBprefix;
	global $smwgARC2StoreConfig;

	// Customize these details if you
	// want to use an external database
	$smwgARC2StoreConfig = array(
		'db_host' => $wgDBserver,
		'db_name' => $wgDBname,
		'db_user' => $wgDBuser,
		'db_pwd' => $wgDBpassword,
		'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
	);

};

// This has to be set outside of the wgExtensionFunctions array above
global $smwgDefaultStore;
$smwgDefaultStore = 'SMWARC2Store';

// -------------------------------------------------------------
// Register hooks
// -------------------------------------------------------------
include_once $rdfioDir . '/RDFIO.hooks.php';
$wgHooks['UnitTestsList'][] = 'RDFIOHooks::onUnitTestsList';

// -------------------------------------------------------------
// Create metadata pages
// -------------------------------------------------------------
$wgHooks['loadExtensionSchemaUpdate'][] = 'RDFIOCreatePagesOnInstall::create';

