<?php

class RDFIOCreatePagesOnInstall {

	public function __construct() {}

	/**
	 * Creates pages from an array on install if thishas not been done already
	 */

	public function create() {

		$wikiPageData = array(
							'titletext' => array( 'title' => 'titletext', 'content' => 'page content [[Category:Stuff]]', 'summary' => 'Created by RDFIO', 'namespace' => 'NS_MAIN' )
						);

		foreach ( $wikiPageData as $pageTitle => $page ) {
			$pageTitleObj => Title::newFromText ($page['title'], $namespace=$page['namespace'];
			$pageObj = new Article( $pageTitleObj );
			$pageObj->doEdit( $page['content'], $page['summary'] );
		}

}
