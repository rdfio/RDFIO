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
define( 'RDFIO_VERSION', 'v2.3.1' );

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'RDFIO',
	'version' => RDFIO_VERSION,
	'author' => array(
		'[http://bionics.it Samuel Lampa]',
		'[http://koshatnik.com Ali King]'
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:RDFIO',
	'descriptionmsg' => 'rdfio-desc',
	'license-name' => 'GPL-2.0'
);

// -------------------------------------------------------------
// internationalization
// -------------------------------------------------------------
$GLOBALS['wgExtensionMessagesFiles']['RDFIO'] = __DIR__ . '/RDFIO.i18n.php';
$GLOBALS['wgExtensionMessagesFiles']['RDFIOAliases'] = __DIR__ . '/RDFIO.alias.php';

// -------------------------------------------------------------
// Load RDFIO Components
// -------------------------------------------------------------
$GLOBALS['wgAutoloadClasses']['SMWARC2Store'] = __DIR__ . '/stores/SMW_ARC2Store.php';
$GLOBALS['wgAutoloadClasses']['RDFIOARC2StoreException'] = __DIR__ . '/stores/SMW_ARC2Store.php';

// Misc
$GLOBALS['wgAutoloadClasses']['RDFIOARC2StoreWrapper'] = __DIR__ . '/classes/RDFIO_ARC2StoreWrapper.php';
$GLOBALS['wgAutoloadClasses']['RDFIOSMWPageWriter'] = __DIR__ . '/classes/RDFIO_SMWPageWriter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOTestCase'] = __DIR__ . '/tests/phpunit/RDFIOTestCase.php';
$GLOBALS['wgAutoloadClasses']['RDFIOWikiWriter'] = __DIR__ . '/classes/RDFIO_WikiWriter.php';

// Parsers
$GLOBALS['wgAutoloadClasses']['ARC2_SPARQLSerializerPlugin'] = __DIR__ . '/vendor/ARC2_SPARQLSerializerPlugin.php';
$GLOBALS['wgAutoloadClasses']['RDFIOARC2ToWikiConverter'] = __DIR__ . '/classes/parsers/RDFIO_ARC2ToWikiConverter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOException'] = __DIR__ . '/classes/RDFIO_Exception.php';
$GLOBALS['wgAutoloadClasses']['RDFIOParser'] = __DIR__ . '/classes/parsers/RDFIO_Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIORDFImporter'] = __DIR__ . '/classes/RDFIO_RDFImporter.php';
$GLOBALS['wgAutoloadClasses']['RDFIORDFXMLToARC2Parser'] = __DIR__ . '/classes/parsers/RDFIO_RDFXMLToARC2Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIOTurtleToARC2Parser'] = __DIR__ . '/classes/parsers/RDFIO_TurtleToARC2Parser.php';
$GLOBALS['wgAutoloadClasses']['RDFIOURIToWikiTitleConverter'] = __DIR__ . '/classes/parsers/RDFIO_URIToWikiTitleConverter.php';
$GLOBALS['wgAutoloadClasses']['RDFIOWikiPage'] = __DIR__ . '/classes/RDFIO_WikiPage.php';

// Special pages
$GLOBALS['wgAutoloadClasses']['RDFIOSpecialPage'] = __DIR__ . '/classes/RDFIO_SpecialPage.php';
$GLOBALS['wgAutoloadClasses']['RDFIOAdmin'] = __DIR__ . '/specials/SpecialRDFIOAdmin.php';
$GLOBALS['wgAutoloadClasses']['RDFImport'] = __DIR__ . '/specials/SpecialRDFImport.php';
$GLOBALS['wgAutoloadClasses']['SPARQLEndpoint'] = __DIR__ . '/specials/SpecialSPARQLEndpoint.php';
$GLOBALS['wgAutoloadClasses']['SPARQLImport'] = __DIR__ . '/specials/SpecialSPARQLImport.php';

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
include_once __DIR__ . '/RDFIO.hooks.php';
$wgHooks['UnitTestsList'][] = 'RDFIOHooks::onUnitTestsList';

// -------------------------------------------------------------
// Create metadata pages
// -------------------------------------------------------------
$wgHooks['loadExtensionSchemaUpdate'][] = 'RDFIOCreatePagesOnInstall::create';

