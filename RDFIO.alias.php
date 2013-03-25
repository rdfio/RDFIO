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
	'ARC2Admin' => array( 'ARC2Admin' ),
	'RDFImport' => array( 'RDFImport' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'SPARQLEndpoint' => array( 'سباركل_إن_دي_بوينت' ),
	'ARC2Admin' => array( 'أرك_تو_أدمن' ),
	'RDFImport' => array( 'استيراد_آر_دي_إف' ),
);

/** German (Deutsch) */
$specialPageAliases['de'] = array(
	'SPARQLEndpoint' => array( 'SPARQL-Endpoint' ),
	'ARC2Admin' => array( 'ARC2-Administration' ),
	'RDFImport' => array( 'RDF_importieren' ),
);

/** Macedonian (Македонски) */
$specialPageAliases['mk'] = array(
	'SPARQLEndpoint' => array( 'SPARQLЗавршеток' ),
	'ARC2Admin' => array( 'ARC2Админ' ),
	'RDFImport' => array( 'RDFУвоз' ),
);

/** Nedersaksisch (Nedersaksisch) */
$specialPageAliases['nds-nl'] = array(
	'SPARQLEndpoint' => array( 'SPARQL-eindpunt' ),
	'ARC2Admin' => array( 'ARC2-beheer' ),
	'RDFImport' => array( 'RDF_invoeren' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'SPARQLEndpoint' => array( 'SPARQLEindpunt' ),
	'ARC2Admin' => array( 'ARC2Beheer' ),
	'RDFImport' => array( 'RDFImporteren' ),
);

/** Vietnamese (Tiếng Việt) */
$specialPageAliases['vi'] = array(
	'ARC2Admin' => array( 'Quản_lý_ARC2', 'Quản_lí_ARC2' ),
	'RDFImport' => array( 'Nhập_RDF' ),
);

/**
 * For backwards compatibility with MediaWiki 1.15 and earlier.
 */
$aliases =& $specialPageAliases;
