<?php
/**
 * Aliases for special pages
 *
 * @file
 * @ingroup Extensions
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'SPARQLEndpoint' => array( 'SPARQLEndpoint' ),
	'RDFIOAdmin' => array( 'RDFIOAdmin' ),
	'RDFImport' => array( 'RDFImport' ),
	'SPARQLImport' => array( 'SPARQLImport' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'SPARQLEndpoint' => array( 'سباركل_إن_دي_بوينت' ),
	'RDFIOAdmin' => array( 'أرك_تو_أدمن' ),
	'RDFImport' => array( 'استيراد_آر_دي_إف' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'SPARQLEndpoint' => array( 'SPARQL-Endpoint' ),
	'RDFIOAdmin' => array( 'RDFIO-Administration' ),
	'RDFImport' => array( 'RDF_importieren' ),
);

/** Macedonian (Македонски) */
$specialPageAliases['mk'] = array(
	'SPARQLEndpoint' => array( 'SPARQLЗавршеток' ),
	'RDFIOAdmin' => array( 'RDFIOАдмин' ),
	'RDFImport' => array( 'RDFУвоз' ),
);

/** Nedersaksisch (Nedersaksisch) */
$specialPageAliases['nds-nl'] = array(
	'SPARQLEndpoint' => array( 'SPARQL-eindpunt' ),
	'RDFIOAdmin' => array( 'RDFIO-beheer' ),
	'RDFImport' => array( 'RDF_invoeren' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'SPARQLEndpoint' => array( 'SPARQLEindpunt' ),
	'RDFIOAdmin' => array( 'RDFIOBeheer' ),
	'RDFImport' => array( 'RDFImporteren' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'RDFIOAdmin' => array( 'Quản_lý_RDFIO', 'Quản_lí_RDFIO' ),
	'RDFImport' => array( 'Nhập_RDF' ),
);

/**
 * For backwards compatibility with MediaWiki 1.15 and earlier.
 */
$aliases =& $specialPageAliases;
