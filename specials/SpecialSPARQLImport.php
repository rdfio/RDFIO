<?php

class SPARQLImport extends RDFIOSpecialPage {

	private $wOut;

	function __construct() {
		parent::__construct( 'SPARQLImport', 'rdfio-import' );
	}

	/**
	 * The main code goes here
	 * @param string $par (unused)
	 */
	function execute( $par ) {
		unset( $par ); // Needed to suppress warning about unused variable which we include just for consistency.

		// Require rdfio-import permission for the current user
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			throw new PermissionsError( 'rdfio-import', array( 'rdfio-specialpage-access-permission-missing' ) );
		}

		$this->wOut = $this->getOutput();
		$wRequest = $this->getRequest();
		$wUser = $this->getUser();

		$this->setHeaders();
		$submitButtonText = wfMessage( 'rdfio-start-import' )->parse();

		$offset = $wRequest->getVal( 'offset', 0 );
		$limit = $wRequest->getVal( 'limit', 25 );

		if ( $wRequest->getText( 'action' ) === 'import' ) {

			if ( !$this->allowInsert( $wUser, $wRequest ) ) {
				$this->errorMsg( wfMessage( 'rdfio-error-no-write-access' )->parse() );
				return;
			}

			$submitButtonText = wfMessage( 'rdfio-import-next-batch-of-triples' )->parse();
			$this->addHTML( $this->getHTMLForm( $submitButtonText, $limit, $offset + $limit ) );

			try {
				$importInfo = $this->import( $limit, $offset );
				$externalSparqlUrl = $importInfo['externalSparqlUrl'];
				$dataSourceImporter = new RDFIORDFImporter();
				$dataSourceImporter->addDataSource( $externalSparqlUrl, 'SPARQL' );
			} catch ( RDFIOException $e ) {
				$this->errorMsg( $e->getMessage() );
				return;
			}

			return;
		}

		$this->addHTML( $this->getHTMLForm( $submitButtonText, $limit, $offset ) );
		$this->addHTML( '<div id=sources style="display:none">' );
		$this->addWikiText( '{{#ask: [[Category:RDFIO Data Source]] [[RDFIO Import Type::SPARQL]] |format=list }}' );
		$this->addHTML( '</div>' );
	}

	/**
	 * Add wiki text to output. Requires that $this->wOut is already
	 * initialized to $this->getOutput();
	 * @param $text The wiki text to add.
	 */
	private function addWikiText( $text ) {
		if ( method_exists( $this->wOut, 'addWikiTextAsInterface' ) ) {
			$this->wOut->addWikiTextAsInterface( $text );
		} else {
			$this->wOut->addWikiText( $text );
		}
	}

	/**
	 * Add HTML content to output. Requires that $this->wOut is already
	 * initialized to $this->getOutput();
	 * @param $text The HTML content to add.
	 */
	private function addHTML( $html ) {
		$this->wOut->addHTML( $html );
	}

	function resourceType( $resourceStr ) {
		if ( substr( $resourceStr, 0, 4 ) === 'http' ) {
			return 'uri';
		}
		return 'literal';
	}

	protected function import( $limit = 25, $offset = 0 ) {
		$wOut = $this->getOutput();
		$wRequest = $this->getRequest();

		$externalSparqlUrl = $wRequest->getText( 'extsparqlurl' );

		if ( $externalSparqlUrl === '' ) {
			throw new RDFIOException( wfMessage( 'rdfio-error-empty-sparql-url' )->parse() );
		}

		if ( substr( $externalSparqlUrl, 0, 4 ) !== 'http' ) {
			throw new RDFIOException( wfMessage( 'rdfio-error-invalid-sparql-url' )->parse() );
		}

		$sparqlQuery = urlencode( "SELECT DISTINCT * WHERE { ?s ?p ?o } OFFSET $offset LIMIT $limit" );
		$sparqlQueryUrl = $externalSparqlUrl . '/' . '?query=' . $sparqlQuery;
		$sparqlResultXml = file_get_contents( $sparqlQueryUrl );

		$sparqlResultXmlObj = simplexml_load_string( $sparqlResultXml );

		$triples = array();

		if ( !is_object( $sparqlResultXmlObj ) ) {
			$this->errorMsg( wfMessage( 'rdfio-error-not-sparql-endpoint' )->parse() );
			return;
		}

		foreach ( $sparqlResultXmlObj->results->children() as $result ) {
			$triple = array();

			foreach ( $result as $binding ) {
				$str = $this->extractStringFromBinding( $binding );
				if ( $binding['name'] == 's' ) {
					$triple['s'] = $str;
					$triple['s_type'] = $this->resourceType( $triple['s'] );
				} else if ( $binding['name'] == 'p' ) {
					$triple['p'] = $str;
					$triple['p_type'] = $this->resourceType( $triple['p'] );
				} else if ( $binding['name'] == 'o' ) {
					$triple['o'] = $str;
					$triple['o_type'] = $this->resourceType( $triple['o'] );
					$triple['o_datatype'] = '';
				}
			}

			$triples[] = $triple;
		}

		$rdfImporter = new RDFIORDFImporter();
		$rdfImporter->importTriples( $triples );
		$this->addHTML( $rdfImporter->showImportedTriples( $triples ) );

		return array( 'externalSparqlUrl' => $externalSparqlUrl );
	}

	protected function extractStringFromBinding( $binding ) {
		$str = (string)$binding->uri[0];
		if ( $str == '' ) {
			throw new Exception( 'Could not extract object from empty string (' . $binding->uri . '), in SPARQLImport' );
		}
		return $str;
	}

	protected function getHTMLForm( $buttonText, $limit, $offset ) {
		global $wgArticlePath;
		$wRequest = $this->getRequest();
		$wUser = $this->getUser();

		$thisPageUrl = str_replace( '/$1', '', $wgArticlePath ) . "/Special:SPARQLImport";
		$extSparqlUrl = $wRequest->getText( 'extsparqlurl', '' );

		$htmlForm = '
		<form method="post" action="" style="clear: none;">
				' . wfMessage( 'rdfio-remote-sparql-endpoint-url' )->parse() . ':<br>
				<input type="hidden" name="action" value="import">
				<div id="urlfields">
				<input type="text" name="extsparqlurl" id="extsparqlurl" size="60" value="' . $extSparqlUrl . '"></input>
				<a href="#" onClick="addSources();">' . wfMessage( 'rdfio-use-previous-source' )->parse() . '</a>
				</div>
				<p><span style="font-style: italic; font-size: 11px">' . wfMessage( 'rdfio-example' )->parse() . ': http://www.semantic-systems-biology.org/biogateway/endpoint</span></p>
				<p>' . wfMessage( 'rdfio-batching-parameters-instructions' )->parse() . ':</p>
				<table style="margin-bottom: 1em;">
					<tr>
						<th style="text-align: right;">' . wfMessage( 'rdfio-limit' )->parse() . ':</th>
						<td><input type="text" name="limit" size="3" value="' . $limit . '"></td>
					</tr>
					<tr>
						<th style="text-align: right;">' . wfMessage( 'rdfio-offset' )->parse() . ':</th>
						<td><input type="text" name="offset" size="3" value="' . $offset . '"></td>
					</tr>
				</table>
				<input type="hidden" name="token" value="' . $wUser->getEditToken() . '">
				<input type="submit" value="' . $buttonText . '"> <a href="' . $thisPageUrl . '">' . wfMessage( 'rdfio-clear-form' ) . '</a></form>';
		$htmlForm .= $this->getJs();
		return $htmlForm;
	}

	public function getJs() {
		$jsCode = '<script type="text/javascript">
		function addSources() {
			var sourceList = document.getElementById("sources").getElementsByTagName("p")[0];
			var sources = sourceList.getElementsByTagName("a");
			var urlForm = document.getElementById("urlfields");
			var urlTextField = document.getElementById("extsparqlurl");
			var selectList = document.createElement("select");
			selectList.id = "sourceSelect";
			urlForm.appendChild(selectList);
			for (var i = 0; i < sources.length; i++) {
				var option = document.createElement("option");
				option.value = sources[i].innerHTML;
				option.text = sources[i].innerHTML;
				selectList.appendChild(option);
			}
			selectList.onchange = function() {selectedUrl = selectList.options[selectList.selectedIndex].value; selectedUrl1 = selectedUrl.substring(0,1).toLowerCase(); selectedUrl2 = selectedUrl.substring(1); selectedUrl = selectedUrl1.concat(selectedUrl2); urlTextField.value = selectedUrl};
		}
		</script>';
		return $jsCode;
	}

}
