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

define( 'RDFIO_VERSION', 'v2.0.11' );

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'RDFIO',
	'version' => RDFIO_VERSION,
	'author' => array( '[http://saml.rilspace.org Samuel Lampa]', '[http://koshatnik.com Ali King]' ),
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
 *    RDFIO Components    *
 **************************/

$rdfioDir = dirname( __FILE__ );

require_once $rdfioDir . '/stores/SMW_ARC2Store.php';
require_once $rdfioDir . '/specials/SpecialRDFIOAdmin.php';
require_once $rdfioDir . '/specials/SpecialRDFImport.php';
require_once $rdfioDir . '/specials/SpecialSPARQLImport.php';
require_once $rdfioDir . '/specials/SpecialSPARQLEndpoint.php';

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
 *  ARC2 RDF Store config *
 **************************/

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

/**************************
 *     Register hooks     *
 **************************/

include_once $rdfioDir . '/RDFIO.hooks.php';
$wgHooks['UnitTestsList'][] = 'RDFIOHooks::onUnitTestsList';

/**************************
 *    Create metadata pages *
 *************************/

$wgHooks['loadExtensionSchemaUpdate'][] = 'RDFIOCreatePagesOnInstall::create';

