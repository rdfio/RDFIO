<?php
class RDFImport extends SpecialPage {

	function __construct() {
		parent::__construct( 'RDFImport' );
	}

	/**
	 * The main code goes here
	 */
	function execute( $par ) {
		global $wgOut, $wgUser, $wgRequest;

		# Set HTML headers sent to the browser
		$this->setHeaders();

		# The main code
		$requestData = $this->getRequestData();
		if ( $requestData->mHasWriteAccess && $requestData->mAction == 'import' ) {
			$this->importData( $requestData );
		} else if ( !$requestData->mHasWriteAccess ) {
			$wgOut->addHTML("<b>User does not have write access!</b>");
		} else {
			$this->outputHTMLForm( $requestData );
		}
	}

	/**
	 * Import data into wiki pages
	 */
	function importData( $requestData ) {
		global $wgOut;

		# Parse RDF/XML to triples
		$arc2rdfxmlparser = ARC2::getRDFXMLParser();
		$arc2rdfxmlparser->parseData( $requestData->mImportData );

		# Receive the data
		$triples = $arc2rdfxmlparser->triples;
		$tripleindex = $arc2rdfxmlparser->getSimpleIndex();
		$namespaces = $arc2rdfxmlparser->nsp;
		
		# Parse data from ARC2 triples to custom data structure holding wiki pages
		$arc2towikiconverter = new RDFIOARC2ToWikiConverter();
		$arc2towikiconverter->convert( $triples, $tripleindex, $namespaces );
		
		# Get data from parser
		$wikipages = $arc2towikiconverter->getWikiPages();
		$proppages = $arc2towikiconverter->getPropertyPages();
		
		# Import pages into wiki
		$smwDataImporter = new RDFIOSMWDataImporter();
		$smwDataImporter->import( $wikipages );
		$smwDataImporter->import( $proppages );
		
		$wgOut->addHTML('Tried to import the stuff ...');
	}


	/**
	 * Get data from the request object and store it in class variables
	 */
	function getRequestData() {
		global $wgRequest, $wgArticlePath;

		$requestData = new RDFIORequestData();
		$requestData->mAction = $wgRequest->getText( 'action' );
		$requestData->mEditToken = $wgRequest->getText( 'token' );
		$requestData->mNSPrefixInWikiTitlesProperties = $wgRequest->getBool( 'nspintitle_prop', false ); // TODO: Remove?
		$requestData->mShowAbbrScreenProperties = $wgRequest->getBool( 'abbrscr_prop', false ); // TODO: Remove?
		$requestData->mNSPrefixInWikiTitlesEntities = $wgRequest->getBool( 'nspintitle_ent', false ); // TODO: Remove?
		$requestData->mShowAbbrScreenEntities = $wgRequest->getBool( 'abbrscr_ent', false ); // TODO: Remove?
		$requestData->mImportData = $wgRequest->getText( 'importdata' );
		$requestData->mDataFormat = $wgRequest->getText( 'dataformat' );
		$requestData->mHasWriteAccess = $this->userHasWriteAccess();
		$requestData->mArticlePath = $wgArticlePath;
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
		$wgOut->addScript( $this->getExampleDataJs() );
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
</rdf:RDF>';
	}

	/**
	 * Generate the main HTML form, if the variable $extraFormContent is set, the
	 * content of it will be prepended before the form
	 * @param RDFIORequestData $requestData
	 * @param string $extraFormContent
	 * @return string $htmlFormContent
	 */
	public function getHTMLFormContent( $requestData, $extraFormContent = '' ) {

		// NOT IMPLEMENTED AT THE MOMENT // TODO: Remove all together?
		// # Abbreviation (and screen) options for properties
		// $checked_nspintitle_properties = $requestData->mNSPrefixInWikiTitlesProperties == 1 ? ' checked="true" ' : '';
		// $checked_abbrscr_properties = $requestData->mShowAbbrScreenProperties == 1 ? ' checked="true" ' : '';
        // 
		// # Abbreviation (and screen) options for entities
		// $checked_nspintitle_entities = $requestData->mNSPrefixInWikiTitlesEntities == 1 ? ' checked="true" ' : '';
		// $checked_abbrscr_entities = $requestData->mShowAbbrScreenEntities == 1 ? ' checked="true" ' : '';

		# Create the HTML form for RDF/XML Import
		$htmlFormContent = '<form method="post" action="' . str_replace( '/$1', '', $requestData->mArticlePath ) . '/Special:RDFImport"
			name="createEditQuery"><input type="hidden" name="action" value="import">
			' . $extraFormContent . '
			<table border="0"><tbody>
			<tr><td colspan="3">RDF/XML data to import:</td><tr>
			<tr><td colspan="3"><textarea cols="80" rows="9" name="importdata" id="importdata">' . $requestData->mImportData . '</textarea>
			</td></tr>
			<tr><td width="100">Data format:</td>
			<td>
			<select id="dataformat" name="dataformat">
			  <option value="rdfxml" selected="selected">RDF/XML</option>
			  <!-- option value="turtle" >Turtle</option -->
			</select>
			</td>
			<td style="text-align: right; font-size: 10px;">
			[<a href="#" onClick="pasteExampleRDFXMLData(\'importdata\');">Paste example data</a>]
			[<a href="#" onClick="document.getElementById(\'importdata\').value = \'\';">Clear</a>]
			</td>
			</tr>
			</tbody></table>
			<input type="submit" value="Submit">' . Html::Hidden( 'token', $requestData->mEditToken ) . '
			</form>';

		return $htmlFormContent;
	}

	/**
	 * Generate the javascriptcode used in the main HTML form for
	 * loading example data into the main textarea
	 * @return string $exampleDataJs
	 */
	public function getExampleDataJs() {
		$exampleDataJs = '
			<script type="text/javascript">
			function pasteExampleRDFXMLData(textFieldId) {
			var textfield = document.getElementById(textFieldId);
			var exampledata = "' . $this->getExampleRDFXMLData() . '";
			textfield.value = exampledata;
			}
			</script>
			';
		return $exampleDataJs;
	}

}

class RDFIORequestData {
	public $mAction = "";
	public $mEditToken = "";
	public $mNSPrefixInWikiTitlesProperties = "";
	public $mShowAbbrScreenProperties = "";
	public $mNSPrefixInWikiTitlesEntities = "";
	public $mShowAbbrScreenEntities = "";

	public function __construct() {
	   // Nothing here so far ...	
	}
}