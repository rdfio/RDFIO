<?php

class RDFIOCreatePagesOnInstall {

	public function __construct() {
	}

	/**
	 * Creates pages from an array on install if this has not been done already
	 */

	public static function create() {

		$wikiPageData = array(
			'Category:RDFIO Data Source' => array(
				'title' => 'RDFIO Data Source',
				'content' => 'URL containing RDF data or a SPARQL endpoint which has been used to import triples into the wiki through the RDFIO extension<br />[[Has type::URL]]',
				'summary' => 'Created by RDFIO',
				'namespace' => 'NS_CATEGORY'
			),
			'Property:RDFIO Import Type' => array(
				'title' => 'RDFIO Import Type',
				'content' => 'RDF or SPARQL import<br />[[Allows value::RDF]]<br />[[Allows value::SPARQL]]',
				'summary' => 'Created by RDFIO',
				'namespace' => 'NS_PROPERTY'
			),
			'Property:Has template' => array(
				'title' => 'Has template',
				'content' => 'Template used for pages in this category on creation<br />[[Has type::Page]]',
				'summary' => 'Created by RDFIO',
				'namespace' => 'NS_PROPERTY'
			),
			'RDF' => array(
				'title' => 'RDF',
				'content' => 'Resource Description Framwork (RDF)',
				'summary' => 'Created by RDFIO',
				'namespace' => 'NS_MAIN'
			),
			'SPARQL' => array(
				'title' => 'SPARQL',
				'content' => 'SPARQL endpoint',
				'summary' => 'Created by RDFIO',
				'namespace' => 'NS_MAIN'
			)
		);

		foreach ( $wikiPageData as $pageTitle => $page ) {
			$pageTitleObj = Title::newFromText( $pageTitle );
			$pageObj = WikiPage::factory( $pageTitleObj );
			$content = $page['content'];
			$summary = $page['summary'];
			$pageObj->doEdit( $content, $summary );
		}
	}
}
