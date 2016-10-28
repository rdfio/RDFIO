<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/RDFIO/specials/SpecialSPARQLEndpoint.php" );
EOT;
	exit( 1 );
}

$GLOBALS['wgExtensionCredits']['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SPARQLEndpoint',
	'author' => array('Samuel Lampa', 'Ali King'),
	'url' => 'http://www.mediawiki.org/wiki/Extension:SMWRDFConnector',
	'descriptionmsg' => 'rdfio-sparqlendpoint-desc',
	'version' => '0.0.0',
);

$dir = dirname( __FILE__ ) . '/';

$GLOBALS['wgAutoloadClasses']['SPARQLEndpoint'] = $dir . 'SpecialSPARQLEndpoint_body.php'; # Tell MediaWiki to load the extension body.
$GLOBALS['wgExtensionMessagesFiles']['SPARQLEndpoint'] = $dir . '../RDFIO.i18n.php';
$GLOBALS['wgExtensionAliasesFiles']['SPARQLEndpoint'] = $dir . '../RDFIO.alias.php';
$GLOBALS['wgSpecialPages']['SPARQLEndpoint'] = 'SPARQLEndpoint'; # Let MediaWiki know about your new special page.
