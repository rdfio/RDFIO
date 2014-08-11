<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/RDFIO/specials/SpecialRDFIOAdmin.php" );
EOT;
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'RDFIOAdmin',
	'author' => array('Samuel Lampa','Ali King'),
	'url' => 'http://www.mediawiki.org/wiki/Extension:RDFIO',
	'descriptionmsg' => 'rdfio-arc2admin-desc',
	'version' => '0.0.0',
);

$dir = dirname( __FILE__ ) . '/';

$wgAutoloadClasses['RDFIOAdmin'] = $dir . 'SpecialRDFIOAdmin_body.php'; # Tell MediaWiki to load the extension body.
$wgExtensionMessagesFiles['RDFIOAdmin'] = $dir . '../RDFIO.i18n.php';
$wgExtensionAliasFiles['RDFIOAdmin'] = $dir . '../RDFIO.alias.php';
$wgSpecialPages['RDFIOAdmin'] = 'RDFIOAdmin'; # Let MediaWiki know about your new special page.
