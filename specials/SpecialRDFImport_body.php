<?php
class RDFImport extends SpecialPage {

	function __construct() {
		parent::__construct( 'RDFImport' );
	}

	/**
	 * The main code goes here
	 */
	function execute( $par ) {
		try {
			# Set HTML headers sent to the browser
			$this->setHeaders();
				
			# The main code
			$requestData = $this->getRequestData();
			if ( $requestData->hasWriteAccess && $requestData->action === 'import' ) {
				$this->importData( $requestData );
			} else if ( !$requestData->hasWriteAccess ) {
				throw new RDFIOException("User does not have write access");
			} 
		} catch (MWException $e) {
			$this->showErrorMessage('Error!', $e->getMessage());
		}
		$this->outputHTMLForm( $requestData );
	}

	/**
	 * Import data into wiki pages
	 */
	function importData( RDFIORequestData $requestData ) {
		$rdfImporter = new RDFIORDFImporter();
		if ( $requestData->importSource === 'url' ) {
			if ( $requestData->externalRdfUrl === '' ) {
			    throw new RDFIOException('URL field is empty!');
			} else if ( !RDFIOUtils::isURI( $requestData->externalRdfUrl ) ) {
			    throw new RDFIOException('Invalid URL provided!');
			}
			$rdfData = file_get_contents( $requestData->externalRdfUrl );
		} else if ( $requestData->importSource === 'textfield' ) {
			if ( $requestData->importData === '' )
				throw new RDFIOException('RDF field is empty!');
			$rdfData = $requestData->importData;
		} else {
			throw new RDFIOException('Import source is not selected!');
		}

	    switch ( $requestData->dataFormat ) {
	        case 'rdfxml':
	            $rdfImporter->importRdfXml( $rdfData );
	            break;
	        case 'turtle':
	            $rdfImporter->importTurtle( $rdfData );
	            break;
	    };
		$this->showSuccessMessage("Success!","Successfully imported the triples");

		global $wgOut;
		$wgOut->addHTML('Tried to import the data ...');
	}

	/**
	 * Get data from the request object and store it in class variables
	 */
	function getRequestData() {
		global $wgRequest, $wgArticlePath;

		$requestData = new RDFIORequestData();
		$requestData->action = $wgRequest->getText( 'action' );
		$requestData->editToken = $wgRequest->getText( 'token' );
		$requestData->importSource = $wgRequest->getText( 'importsrc' );
		$requestData->nsPrefixInWikiTitlesProperties = $wgRequest->getBool( 'nspintitle_prop', false ); // TODO: Remove?
		$requestData->nsPrefixInWikiTitlesEntities = $wgRequest->getBool( 'nspintitle_ent', false ); // TODO: Remove?
		$requestData->externalRdfUrl = $wgRequest->getText( 'extrdfurl' );
		$requestData->importData = $wgRequest->getText( 'importdata' );
		$requestData->dataFormat = $wgRequest->getText( 'dataformat' );
		$requestData->hasWriteAccess = $this->userHasWriteAccess();
		$requestData->articlePath = $wgArticlePath;

		return $requestData;
	}

	/**
	 * Check whether the current user has rights to edit or create pages
	 */
	protected function userHasWriteAccess() {
		global $wgUser;
		$userRights = $wgUser->getRights();
		return ( in_array( 'edit', $userRights ) && in_array( 'createpage', $userRights ) );
	}

	/**
	 * Output the HTML for the form, to the user
	 */
	function outputHTMLForm( $requestData ) {
		global $wgOut;
		$wgOut->addScript( $this->getJsCode() );
		$wgOut->addHTML( $this->getHTMLFormContent( $requestData ) );
	}

	/**
	 * Get RDF/XML stub for for the import form, including namespace definitions
	 * @return string
	 */
	public function getExampleRDFXMLData() {
		return '<rdf:RDF\\n\
				xmlns:rdf=\\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\\"\\n\
				xmlns:cd=\\"http://www.recshop.fake/cd#\\"\\n\
				xmlns:countries=\\"http://www.countries.org/onto/\\"\\n\
				xmlns:rdfs=\\"http://www.w3.org/2000/01/rdf-schema#\\"\\n\
				>\\n\
				\\n\
				<rdf:Description\\n\
				rdf:about=\\"http://www.recshop.fake/cd/Empire Burlesque\\">\\n\
				<cd:artist>Bob Dylan</cd:artist>\\n\
				<cd:country rdf:resource=\\"http://www.countries.org/onto/USA\\"/>\\n\
				<cd:company>Columbia</cd:company>\\n\
				<cd:price>10.90</cd:price>\\n\
				<cd:year>1985</cd:year>\\n\
				</rdf:Description>\\n\
				\\n\
				<rdf:Description\\n\
				rdf:about=\\"http://www.recshop.fake/cd/Hide your heart\\">\\n\
				<cd:artist>Bonnie Tyler</cd:artist>\\n\
				<cd:country>UK</cd:country>\\n\
				<cd:company>CBS Records</cd:company>\\n\
				<cd:price>9.90</cd:price>\\n\
				<cd:year>1988</cd:year>\\n\
				</rdf:Description>\\n\
				\\n\
				<rdf:Description\\n\
				rdf:about=\\"http://www.countries.org/onto/USA\\">\\n\
				<rdfs:label>USA</rdfs:label>\\n\
				</rdf:Description>\\n\
				\\n\
				<rdf:Description rdf:about=\\"http://www.countries.org/onto/Albums\\">\\n\
				<rdfs:subClassOf rdf:resource=\\"http://www.countries.org/onto/MediaCollections\\"/>\\n\
				</rdf:Description>\\n\
				</rdf:RDF>';
	}
	
		/**
	 * Get Turtle stub for for the import form, including namespace definitions
	 * @return string
	 */
	public function getExampleTurtleData() {
		return '@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\\n\
				@prefix cd: <http://www.recshop.fake/cd#> .\\n\
				@prefix countries: <http://www.countries.org/onto/> .\\n\
				@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\\n\
				\\n\
				<http://www.recshop.fake/cd/Empire Burlesque>\\n\
					cd:artist \\"Bob Dylan\\" ;\\n\
					cd:country countries:USA ;\\n\
					cd:company \\"Columbia\\" ;\\n\
					cd:price \\"10.90\\" ;\\n\
					cd:year \\"1985\\" .\\n\
				\\n\
				<http://www.recshop.fake/cd/Hide your heart>\\n\
					cd:artist \\"Bonnie Tyler\\" ;\\n\
					cd:country \\"UK\\" ;\\n\
					cd:company \\"CBS Records\\" ;\\n\
					cd:price \\"9.90\\" ;\\n\
					cd:year \\"1988\\" .\\n\
				\\n\
				countries:USA\\n\
					rdfs:label \\"USA\\" .\\n\
				\\n\
				countries:Albums\\n\
					rdfs:subClassOf countries:MediaCollections .';
	}

	/**
	 * Generate the main HTML form, if the variable $extraFormContent is set, the
	 * content of it will be prepended before the form
	 * @param RDFIORequestData $requestData
	 * @param string $extraFormContent
	 * @return string $htmlFormContent
	 */
	public function getHTMLFormContent( $requestData, $extraFormContent = '' ) {
		$urlChecked = ( $requestData->importSource === 'url' );
		$textfieldChecked = ( $requestData->importSource === 'textfield' );
		
		// Show (and pre-select) the URL field, as default
		if ( !$urlChecked && !$textfieldChecked ) {
			$urlChecked = true;
			$textfieldHiddenContent = 'style="display: none"';
		}

		$urlCheckedContent = $urlChecked ? 'checked="true"' : '';
		$textfieldCheckedContent = $textfieldChecked ? 'checked="true"' : '';
		
		// Create the HTML form for RDF/XML Import
		$htmlFormContent = '<script type="text/javascript">
				function showUrlFields() {
					document.getElementById("urlfields").style.display = "";
					document.getElementById("datafields").style.display = "none";
				}
				function showDataFields() {
					document.getElementById("urlfields").style.display = "none";
					document.getElementById("datafields").style.display = "";
				}
				</script>
				<form method="post" action="' . str_replace( '/$1', '', $requestData->articlePath ) . '/Special:RDFImport"
				name="createEditQuery"><input type="hidden" name="action" value="import">
				' . $extraFormContent . '
					<table border="0">
						<tbody>
							<tr>
								<td colspan="3">
								Action:
								<input type="radio" name="importsrc" value="url" ' . $urlCheckedContent .' onclick="javascript:showUrlFields();" />Import RDF from URL,
								<input type="radio" name="importsrc" value="textfield" ' . $textfieldCheckedContent . ' onclick="javascript:showDataFields();" />Paste RDF
								</td>
							</tr>
						</tbody>
					</table>
						
					<div id="urlfields">
						External URL:
						<input type="text" size="100" name="extrdfurl">
					</div>
						
					<div id="datafields" ' . $textfieldHiddenContent . '>
						<table style="border: none"><tbody>
							<tr>
								<td colspan="3">Data to import:</td>
							</tr>
							<tr>
								<td colspan="3"><textarea cols="80" rows="9" name="importdata" id="importdata">' . $requestData->importData . '</textarea></td>
							</tr>
							<tr>
								<td style="width: 100px;">Data format:</td>
								<td>
									<select id="dataformat" name="dataformat">
    									<option value="rdfxml" selected="selected">RDF/XML</option>
    									<option value="turtle">Turtle</option>
									</select>
								</td>
								<td style="text-align: right; font-size: 10px;">
									[<a href="#" onClick="pasteExampleRDFXMLData(\'importdata\');">RDFXML example data</a>]
									[<a href="#" onClick="pasteExampleTurtleData(\'importdata\');">Turtle example data</a>]
									[<a href="#" onClick="document.getElementById(\'importdata\').value = \'\';">Clear</a>]
								</td>
							</tr>
						</tbody></table>
					</div>
					<input type="submit" value="Submit">' . Html::Hidden( 'token', $requestData->editToken ) . '
				</form>';

		return $htmlFormContent;
	}

	/**
	 * Generate the javascriptcode used in the main HTML form for
	 * loading example data into the main textarea
	 * also set the dataformat to the correct one
	 * @return string $exampleDataJs
	 */
	public function getJsCode() {
		$jsCode = '
<script type="text/javascript">
function pasteExampleRDFXMLData(textFieldId) {
	var textfield = document.getElementById(textFieldId);
	var exampledata = "' . $this->getExampleRDFXMLData() . '";
	textfield.value = exampledata;
	document.getElementById("dataformat").options[0].selected = true;
	
}	
function pasteExampleTurtleData(textFieldId) {
	var textfield = document.getElementById(textFieldId);
	var exampledata = "' . $this->getExampleTurtleData() . '";
	textfield.value = exampledata;
	document.getElementById("dataformat").options[1].selected = true;
}
</script>
						';
		return $jsCode;
	}

	function showErrorMessage( $title, $message ) {
		global $wgOut;
		$errorHtml = RDFIOUtils::formatErrorHTML( $title, $message );
		$wgOut->addHTML( $errorHtml );
	}
}

class RDFIORequestData {
	public $action;
	public $editToken;
	public $importSource;
	public $nsPrefixInWikiTitlesProperties;
	public $nsPrefixInWikiTitlesEntities;
	public $externalRdfUrl;
	public $importData;
	public $dataFormat;
	public $hasWriteAccess;
	public $articlePath;

	public function __construct() {
	}
}
