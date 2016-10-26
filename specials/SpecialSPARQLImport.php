<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
    echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/RDFIO/specials/SpecialSPARQLImport.php" );
EOT;
    exit( 1 );
}

$GLOBALS['wgExtensionCredits']['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SPARQLImport',
	'author' => 'Samuel Lampa',
	'url' => 'http://www.mediawiki.org/wiki/Extension:RDFIO',
	'descriptionmsg' => 'rdfio-sparqlimport-desc',
	'version' => '0.0.0',
);

$dir = dirname( __FILE__ ) . '/';

$GLOBALS['wgAutoloadClasses']['SPARQLImport'] = $dir . 'SpecialSPARQLImport_body.php'; # Tell MediaWiki to load the extension body.
$GLOBALS['wgExtensionMessagesFiles']['SPARQLImport'] = $dir . '../RDFIO.i18n.php';
$GLOBALS['wgExtensionAliasFiles']['SPARQLImport'] = $dir . '../RDFIO.alias.php';
$GLOBALS['wgSpecialPages']['SPARQLImport'] = 'SPARQLImport'; # Let MediaWiki know about your new special page.
