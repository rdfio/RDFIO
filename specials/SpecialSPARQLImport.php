<?php

class SPARQLImport extends RDFIOSpecialPage {

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
			throw new PermissionsError( 'rdfio-import', array( 'rdfio-import-permission-missing' ) );
		}

		$wOut = $this->getOutput();
		$wRequest = $this->getRequest();
		$wUser = $this->getUser();

		$this->setHeaders();
		$submitButtonText = "Start import";

		$offset = $wRequest->getVal( 'offset', 0 );
		$limit = $wRequest->getVal( 'limit', 25 );

		if ( $wRequest->getText( 'action' ) === 'import' ) {

			if ( !$this->allowInsert( $wUser, $wRequest ) ) {
				$this->errorMsg( 'The current logged in user does not have write access' );
				return;
			}

			$submitButtonText = "Import next batch of triples...";
			$wOut->addHTML( $this->getHTMLForm( $submitButtonText, $limit, $offset + $limit ) );

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

		$wOut->addHTML( $this->getHTMLForm( $submitButtonText, $limit, $offset ) );
		$wOut->addHTML( '<div id=sources style="display:none">' );
		$wOut->addWikiText( '{{#ask: [[Category:RDFIO Data Source]] [[RDFIO Import Type::SPARQL]] |format=list }}' );
		$wOut->addHTML( '</div>' );
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
			throw new RDFIOException( 'Empty SPARQL Url provided!' );
		}

		if ( substr( $externalSparqlUrl, 0, 4 ) !== 'http' ) {
			throw new RDFIOException( 'Invalid SPARQL Endpoint URL provided! (Must start with \'http\')' );
		}

		$sparqlQuery = urlencode( "SELECT DISTINCT * WHERE { ?s ?p ?o } OFFSET $offset LIMIT $limit" );
		$sparqlQueryUrl = $externalSparqlUrl . '/' . '?query=' . $sparqlQuery;
		$sparqlResultXml = file_get_contents( $sparqlQueryUrl );

		$sparqlResultXmlObj = simplexml_load_string( $sparqlResultXml );

		$triples = array();

		if ( !is_object( $sparqlResultXmlObj ) ) {
			$this->errorMsg( 'There was a problem importing from the endpoint. Are you sure that the given URL is a valid SPARQL endpoint?' );
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
		$wOut->addHTML( $rdfImporter->showImportedTriples( $triples ) );

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
		<form method="get" action="" style="clear: none;">
				URL of SPARQL endpoint:<br>
				<input type="hidden" name="action" value="import">
				<div id="urlfields">
				<input type="text" name="extsparqlurl" id="extsparqlurl" size="60" value="' . $extSparqlUrl . '"></input>
				<a href="#" onClick="addSources();">Use previous source</a>
				</div>
				<p><span style="font-style: italic; font-size: 11px">Example: http://www.semantic-systems-biology.org/biogateway/endpoint</span></p>
				<p>Batching parameters (Automatically updated - change manually only if you know you know you need it!):</p>
				<table style="margin-bottom: 1em;">
					<tr>
						<th style="text-align: right;">Limit:</th>
						<td><input type="text" name="limit" size="3" value="' . $limit . '"></td>
					</tr>
					<tr>
						<th style="text-align: right;">Offset:</th>
						<td><input type="text" name="offset" size="3" value="' . $offset . '"></td>
					</tr>
				</table>
				<input type="hidden" name="token" value="' . $wUser->getEditToken() . '">
				<input type="submit" value="' . $buttonText . '"> <a href="' . $thisPageUrl . '">Reset form</a></form>';
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
