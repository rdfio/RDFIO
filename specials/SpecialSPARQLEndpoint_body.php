<?php
class SPARQLEndpoint extends SpecialPage {

    protected $sparqlendpointconfig;
    protected $sparqlendpoint;
    protected $sparqlparser;
    protected $store;

    protected $user;

    // TODO: Really keep the below ones as class variables?    

    function __construct() {
        parent::__construct( 'SPARQLEndpoint' );
        # Set up some stuff
        $this->sparqlendpointconfig = $this->getSPARQLEndpointConfig();
        $this->sparqlendpoint = ARC2::getStoreEndpoint( $this->sparqlendpointconfig );
        $this->sparqlparser = ARC2::getSPARQLPlusParser();
        $this->store = new RDFIOARC2StoreWrapper();
        $this->user = new RDFIOUser();
        $this->requestdata = null;
    }

    /**
     * The main function
     */
    function execute() {
        global $wgOut;

        $this->setHeaders();
        $this->requestdata = $this->handleRequestData();

        if ( $this->hasSparqlQuery() ) {

            $this->ensureSparqlEndpointInstalled();

            if ( $this->requestdata->querybyequivuri ) {
                $this->urisToEquivURIsInQuery();
            }

            switch ( $this->requestdata->querytype ) {
                case 'insert':
                    $this->importTriplesInQuery();
                    $this->printHTMLForm();
                    break;
                case 'delete':
                    if ( $this->checkAllowDelete() ) {
                        $this->deleteTriplesInQuery();
                    }
                    // TODO Add a "successfully inserted/deleted" message here
                    $this->printHTMLForm();
                    break;
                default:
                    switch ( $this->requestdata->outputtype ) {
                        case 'htmltab':
                            $this->printHTMLForm();
                            if ( $this->shouldShowQuery() ) {
                                $this->printQueryStructure();
                            } else {
                                $this->executeNonEditSparqlQuery();
                            }
                            break;
                        case 'rdfxml':
                            if ( $this->requestdata->querytype != 'construct' ) {
                                $wgOut->addHTML( $this->formatErrorHTML( "Invalid choice", "RDF/XML can only be used with CONSTRUCT, if constructing triples" ) );
                                $this->printHTMLForm();
                            } else {
                               $this->prepareCreatingDownloadableFile();
                               $this->executeNonEditSparqlQuery();
                            }
                            break;
                        case 'xml':
                           $this->prepareCreatingDownloadableFile();
                           $this->executeNonEditSparqlQuery();
                           break;
                    }
            }
        } else { // SPARQL query is empty
            $this->printHTMLForm();
        }
    }

    /**
     * Execute method for SPARQL queries that only queries and returns results, but
     * does not modify, add or delete triples.
     */ 
    function executeNonEditSparqlQuery() {
        global $wgOut; 

        $output = $this->passSparqlToARC2AndGetAsPhpSerialization();
        $outputtype = $this->determineOutputType();

        if ( $outputtype == 'rdfxml' ) {
            # Here the results should be RDF/XML triples, 
            # not just plain XML SPARQL result set
            $output_structure = unserialize( $output );
            $tripleindex = $output_structure['result'];
            $triples = ARC2::getTriplesFromIndex( $tripleindex );
            
            // TODO: Merge functionality from "Orig URI:s" to "Equiv URI:s"
            if ( $this->requestdata->outputequivuris ) {
                // FIXME: Why is this uncommented???
                # $triples = $this->complementTriplesWithEquivURIsForProperties( $triples );
                $triples = $this->complementTriplesWithEquivURIs( $triples );
            }
            $output = $this->triplesToRDFXML( $triples );
            # Using echo instead of $wgOut->addHTML() here, since output format is not HTML                
            echo $output;
        } else {
            // TODO: Add some kind of check that the output is really an object
            if ( count($output) > 0 ) {
                $output_structure = unserialize( $output );
                    if ( $this->requestdata->outputequivuris ) {
                    $output_structure = $this->urisToEquivURIsInSparqlResults( $output_structure );
                }

                if ( $outputtype == 'htmltab' ) {
                    $output = $this->sparqlResultToHTML( $output_structure );
                    $wgOut->addHTML( $output );
                } else {
                    # Using echo instead of $wgOut->addHTML() here, since output format is not HTML                
                    $output = $this->sparqlendpoint->getSPARQLXMLSelectResultDoc( $output_structure );
                    echo $output;
                }
            } else {
                $wgOut->setHTML("ERROR: No results from SPARQL query!");
            }
        }
    }

    function shouldShowQuery() {
        global $wgRequest;
        return $wgRequest->getBool( 'showquery', false );
    }

    function passSparqlToARC2AndGetAsPhpSerialization() {
        # Make sure ARC2 returns a PHP serialization, so that we 
        # can do stuff with it programmatically
        $this->setOutputTypeInPost( 'php_ser' );
        $this->sparqlendpoint->handleRequest();
        $this->handleSPARQLErrors();
        $output = $this->sparqlendpoint->getResult();
        return $output;
    }

    /**
     * Determine the output type of the SPARQL query
     */
    function determineOutputType() {
        $outputtype = $this->requestdata->outputtype;
        if ( $outputtype == '' && $this->requestdata->querytype == 'construct' ) {
            $outputtype = 'rdfxml';
        }
        return $outputtype;
    }

    function hasSparqlQuery() {
        return ( $this->requestdata->query != '' );
    }

    /**
     * Take care of data from the request object and store
     * in class variables
     */
    function handleRequestData() {
        global $wgRequest,
               $rdfiogQueryByEquivURI,
               $rdfiogOutputEquivURIs;

        $requestData = new RDFIOSPARQLRequestData();

        $requestData->query = $wgRequest->getText( 'query' );

        if ( $rdfiogQueryByEquivURI != '' ) {
          $requestData->querybyequivuri = $rdfiogQueryByEquivURI;
        } else {
          $requestData->querybyequivuri = $wgRequest->getBool( 'equivuri_q' );
        }

        if ( $rdfiogOutputEquivURIs != '' ) {
            $requestData->outputequivuris = $rdfiogOutputEquivURIs;
        } else {
            $requestData->outputequivuris = $wgRequest->getBool( 'equivuri_o' );
        }

        $requestData->outputtype = $wgRequest->getText( 'output' );
        if ( $requestData->query !== '' ) {
            $this->sparqlparser->parse( $requestData->query, '' );
            $requestData->query_parsed = $this->sparqlparser->getQueryInfos();
            if ( array_key_exists( 'query', $requestData->query_parsed ) ) {
                $requestData->querytype = $requestData->query_parsed['query']['type'];
            }
        }
        return $requestData;
    }

    function ensureSparqlEndpointInstalled() {
        if ( !$this->sparqlendpoint->isSetUp() ) {
            $this->sparqlendpoint->setUp(); /* create MySQL tables */
        }
    }

    /**
     * Modify the SPARQL pattern to allow querying using the original URI
     */
    function urisToEquivURIsInQuery() {
        $query_structure = $this->requestdata->query_parsed;
        //print_r($query_structure);
        $triple = $query_structure['query']['pattern']['patterns'][0]['patterns'][0];
        $s = $triple['s'];
        $p = $triple['p'];
        $o = $triple['o'];
        $s_type = $triple['s_type'];
        $p_type = $triple['p_type'];
        $o_type = $triple['o_type'];
        if ( $s_type === 'uri' ) {
            $triple['s'] = 's';
            $triple['s_type'] = 'var';
            $newtriple = $this->createEquivURITriple( $s, 's' );
            $query_structure['query']['pattern']['patterns'][0]['patterns'][] = $newtriple;
        }
        if ( $p_type === 'uri' ) {
            $triple['p'] = 'p';
            $triple['p_type'] = 'var';
            $newtriple = $this->createEquivURITriple( $p, 'p', true );
            $query_structure['query']['pattern']['patterns'][0]['patterns'][] = $newtriple;
        }
        if ( $o_type === 'uri' ) {
            $triple['o'] = 'o';
            $triple['o_type'] = 'var';
            $newtriple = $this->createEquivURITriple( $o, 'o' );
            $query_structure['query']['pattern']['patterns'][0]['patterns'][] = $newtriple;
        }
        // restore the first triple into its original location
        $query_structure['query']['pattern']['patterns'][0]['patterns'][0] = $triple;
        //print_r($query_structure);
        require_once( __DIR__ . "/../bundle/ARC2_SPARQLSerializerPlugin.php" );
        $sparqlserializer = new ARC2_SPARQLSerializerPlugin( "<>", $this );
        $query = $sparqlserializer->toString( $query_structure );

        $this->setQueryInPost( $query );
    }

    /**
     * Create an RDF triple that links a wiki page to its corresponding
     * equivalent URI
     * @param string $uri
     * @param string $varname
     * @param boolean $isproperty
     * @return array $equivuritriple
     */
    function createEquivURITriple( $uri, $varname, $isproperty = false ) {
        if ( $isproperty ) {
            $equivuriuri = $this->store->getEquivURIURIForProperty();
        } else {
            $equivuriuri = $this->store->getEquivURIURI();
        }
        $equivuritriple = array(
        	'type' => 'triple',
        	's' => $varname,
        	'p' => $equivuriuri,
        	'o' => $uri,
        	's_type' => 'var',
        	'p_type' => 'uri',
        	'o_type' => 'uri',
        	'o_datatype' => '',
        	'o_lang' => ''
        );
        return $equivuritriple;
    }

    /**
     * Check if writing to wiki is allowed, and handle a number
     * of exceptions to that, by showing error messages etc
     */
    function checkAllowInsert() {
        global $wgOut;

        if ( $this->wrongEditTokenDetected() ) {
            $wgOut->addHTML( $this->formatErrorHTML( "Error", "Cross-site request forgery detected!" ) );
            return false;
        } else {
            if ( $this->user->hasWriteAccess() ) {
                return true;
            } else {
                $wgOut->addHTML( $this->formatErrorHTML( "Permission error", "The current user lacks access either to edit or create pages (or both) in this wiki." ) );
                return false;
            }
        }
    }

    /**
     * Detect whether the edit token is not correct, even though remote editing is not permitted 
     * (in which case this check will not be done).
     */
    function wrongEditTokenDetected() {
        global $rdfiogAllowRemoteEdit, $wgRequest;
        if ( $rdfiogAllowRemoteEdit == '' ) {
            $rdfiogAllowRemoteEdit = false;
        }
        return ( ! $rdfiogAllowRemoteEdit && 
                 ! $this->user->editTokenIsCorrect( $wgRequest->getText( 'token' ) ) );
    }

    /**
     * Check if deleting from wiki is allowed, and handle a number
     * of exceptions to that, by showing error messages etc
     */
    function checkAllowDelete() {
        global $wgRequest, $wgUser, $wgOut, $rdfiogAllowRemoteEdit;
        if ( !$wgUser->matchEditToken( $wgRequest->getText( 'token' ) ) &&
             !$rdfiogAllowRemoteEdit ) {
            die( 'Cross-site request forgery detected!' );
        } else {
            if ( $this->user->hasDeleteAccess() || $rdfiogAllowRemoteEdit ) {
                return true;
            } else {
                $errortitle = "Permission error";
                $errormessage = "The current user lacks access either to edit or delete pages (or both) in this wiki.";
                $wgOut->addHTML( $this->formatErrorHTML( $errortitle, $errormessage ) );
                return false;
            }
        }
    }

    /**
     * Print out the datastructure of the query in preformatted text
     */
    function printQueryStructure() {
        global $wgOut;
        $wgOut->addHTML( "<h3>Query structure</h3><pre>" . print_r( $this->requestdata->query_parsed, true ) . "</pre>" );
    }

    /**
     * Do preparations for getting outputted data as a downloadable file
     * rather than written to the current page
     */
    function prepareCreatingDownloadableFile() {
        global $wgOut;
        // Disable MediaWikis theming
        $wgOut->disable();
        // Enables downloading as a stream, which is important for large dumps
        wfResetOutputBuffers();
        // Send headers telling that this is a special content type
        // and potentially is to be downloaded as a file
        $this->sendHeadersForOutputType( $this->requestdata->outputtype );
    }

    /**
     * Print out the HTML Form
     */
    function printHTMLForm() {
        global $wgOut;
        $wgOut->addScript( $this->getHTMLFormScript() );
        $wgOut->addHTML( $this->getHTMLForm( $this->requestdata->query ) );
    }

    /**
     * Extract the main content from ARC:s SPARQL result HTML
     * and do some enhancing (wikify tables)
     * @param string $output
     * @return string $html
     */
    function sparqlResultToHTML( $result_structure ) {
        $html = "";
        $html = "<h3>Result:</h3><div style='font-size: 11px;'>" . $html . "</div>";
        $html .= "<table class=\"wikitable sortable\">";
        $result = $result_structure['result'];
        $variables = $result['variables'];

        $html .= "<tr>";
        foreach ( $variables as $variable ) {
            $html .= "<th width='34%''>$variable</th>";    
        }
        $html .= "</tr>";

        $rows = $result['rows'];
        foreach ( $rows as $row ) {
            $html .= "<tr>";
            foreach ( $variables as $variable ) {
                $value = $row[$variable];
                $valueType = $row[$variable . ' type'];
                $html .= "<td style=\"font-size:9px!important;white-space:nowrap!important;\">" . $value . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "</table>";
        return $html;
    }

    /**
     * After a query is parsed, import the parsed data to the wiki
     */
    function importTriplesInQuery() {
        if ( $this->checkAllowInsert() ) {
            $triples = $this->requestdata->query_parsed['query']['construct_triples'];
            
            # Parse data from ARC2 triples to custom data structure holding wiki pages
            $arc2towikiconverter = new RDFIOARC2ToWikiConverter();
            $arc2towikiconverter->convert( $triples, $tripleindex="", $namespaces="" );
            
            # Get data from parser
            $wikipages = $arc2towikiconverter->getWikiPages();
            $proppages = $arc2towikiconverter->getPropertyPages();
            
            # Import pages into wiki
            $smwDataImporter = new RDFIOSMWDataImporter();
            $smwDataImporter->import( $wikipages );
            $smwDataImporter->import( $proppages );
        }
    }

    /**
     * After a query is parsed, delete the parsed data from the wiki
     */
    function deleteTriplesInQuery() {
        $triples = $this->requestdata->query_parsed['query']['construct_triples'];
        $rdfImporter = new RDFIOSMWBatchWriter( $triples, 'triples_array' );
        $rdfImporter->executeDelete();
    }

    /**
     * Die and display current errors
     */
    function handleSPARQLErrors() {
        global $wgOut;
        $sparqlEndpointErrors = $this->sparqlendpoint->getErrors();
        if ( count( $sparqlEndpointErrors ) > 0 ) {
            $errormessage = '';
            if ( is_array( $sparqlEndpointErrors ) ) {
                foreach ( $sparqlEndpointErrors as $sparqlEndpointError ) {
                    $errormessage .= "<p>$sparqlEndpointError</p>";
                }
            } else {
                $errormessage = "<p>$sparqlEndpointErrors</p>";
            }
            $this->showErrorMessage( "SPARQL Error", $errormessage );
        }
    }

    /**
     * For all property URIs and all subject and objects which have URIs,
     * add triples using equivalent uris for these URIs (in all combinations
     * thereof). If $p_uris_filter is set, allow only triples with properties
     * included in this filter array
     * @param array $triples
     * @param array $p_uris_filter
     * @return array $triples
     */
    function complementTriplesWithEquivURIs( $triples, $p_uris_filter = '' ) {
        $variables = array( 's', 'p', 'o' );
        $newtriples = array();
        foreach ( $triples as $tripleid => $triple ) {
            // Subject
            $s_equivuris = array( $triple['s'] );
            if ( $triple['s_type'] === 'uri' ) {
                $s_uri = $triple['s'];
                $s_equivuris_temp = $this->store->getEquivURIsForURI( $s_uri );
                if ( count( $s_equivuris_temp ) > 0 ) {
                    $s_equivuris = $s_equivuris_temp;
                }
            }

            // Property
            $propertyuri = $triple['p'];
            $p_equivuris = array( $triple['p'] );
            $p_equivuris_temp = $this->store->getEquivURIsForURI( $propertyuri, true );
            if ( count( $p_equivuris_temp ) > 0 ) {
                if ( $p_uris_filter != '' ) {
                    // Only include URIs that occur in the filter
                    $p_equivuris_temp = array_intersect( $p_equivuris_temp, $p_uris_filter );
                }
                if ( $p_equivuris_temp != '' ) {
                    $p_equivuris = $p_equivuris_temp;
                }
            }

            // Object
            $o_equivuris = array( $triple['o'] );
            if ( $triple['o_type'] === 'uri' ) {
                $o_uri = $triple['o'];
                $o_equivuris_temp = $this->store->getEquivURIsForURI( $o_uri );
                if ( count( $o_equivuris_temp ) > 0 ) {
                    $o_equivuris = $o_equivuris_temp;
                }
            }

            // Generate triples
            foreach ( $s_equivuris as $s_equivuri ) {
                foreach ( $p_equivuris as $p_equivuri ) {
                    foreach ( $o_equivuris as $o_equivuri ) {
                        $newtriple = array(
                    		's' => $s_equivuri,
                			'p' => $p_equivuri,
                    		'o' => $o_equivuri
                        );
                        $newtriples[] = $newtriple;
                    }
                }
            }
        }
        return $newtriples;
    }

    /**
     * Replace URI:s with an accompanying "Equivalent URI" with that one. If 
     * there are móre than one Equivalent URI for a given URI, the others than 
     * the first one will be ignored.
     * @param array $sparql_resultstructure
     * @param string $p_uris_filter
     * @return array $sparql_resultstructure
     */
    function urisToEquivURIsInSparqlResults( $sparql_resultstructure ) {
        $rows = $sparql_resultstructure['result']['rows'];
        $sparql_varnames = $sparql_resultstructure['result']['variables'];
        foreach ( $rows as $rowid => $row ) {
            foreach ( $sparql_varnames as $sparql_varname ) {
                $typekey = "$sparql_varname type";
                $type = $row[$typekey];
                $uri = $row[$sparql_varname];
                if ( $type === 'uri' ) {
                    $equivuris = $this->store->getEquivURIsForURI( $uri );
                    if ( !$this->arrayEmpty( $equivuris ) ) {
                        $equivuri = $equivuris[0];
                        # Replace the URI with the Equivalent URI
                        $rows[$rowid][$sparql_varname] = $equivuri;
                    }
                }
            }
        }
        # Put back the modified rows into the results structure
        $sparql_resultstructure['result']['rows'] = $rows;
        return $sparql_resultstructure;
    }

    /**
     * Convert an ARC triple index array structure into RDF/XML
     * @param array $tripleindex
     * @return string $rdfxml
     */
    function tripleIndexToRDFXML( $tripleindex ) {
        $ser = ARC2::getRDFXMLSerializer(); // TODO: Choose format depending on user choice
        // Serialize into RDF/XML, since it will contain
        // all URIs in un-abbreviated form, so that they
        // can easily be replaced by search-and-replace
        $rdfxml = $ser->getSerializedIndex( $tripleindex );
        if ( $ser->getErrors() ) {
            die( "ARC Serializer Error: " . $ser->getErrors() );
        }
        return $rdfxml;
    }

    /**
     * Convert an ARC triples array into RDF/XML
     * @param array $triples
     * @return string $rdfxml
     */
    function triplesToRDFXML( $triples ) {
        $ser = ARC2::getRDFXMLSerializer(); // TODO: Choose format depending on user choice
        // Serialize into RDF/XML, since it will contain
        // all URIs in un-abbreviated form, so that they
        // can easily be replaced by search-and-replace
        $rdfxml = $ser->getSerializedTriples( $triples );
        if ( $ser->getErrors() ) {
            die( "ARC Serializer Error: " . $ser->getErrors() );
        }
        return $rdfxml;
    }

    function getPredicateVariableName() {
        $pred_varname = $this->requestdata->query_parsed['vars'][1];
        return $pred_varname;
    }

    /**
     * Get a configuration array for initializing the ARCs
     * SPARQL endpoint
     */
    private function getSPARQLEndpointConfig() {
        global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $smwgARC2StoreConfig;
        $epconfig = array(
            'db_host' => $wgDBserver, /* optional, default is localhost */
            'db_name' => $wgDBname,
            'db_user' => $wgDBuser,
            'db_pwd' =>  $wgDBpassword,
            'store_name' => $smwgARC2StoreConfig['store_name'],
            'endpoint_features' =>
        array(
            'select',
            'construct',
            'ask',
            'describe',
        # 'load',
        # 'insert', // This is not needed, since it is done via SMWWriter instead
        # 'delete', // This is not needed, since it is done via SMWWriter instead
        # 'dump' /* dump is a special command for streaming SPOG export */
        ),
            'endpoint_timeout' => 60, /* not implemented in ARC2 preview */
        # 'endpoint_read_key' => '', /* optional */
        # 'endpoint_write_key' => 'somekey', /* optional */
        # 'endpoint_max_limit' => 250, /* optional */
        );
        return $epconfig;
    }

    /**
     * Set headers appropriate to the filetype specified in $outputtype
     * @param string $outputtype
     */
    private function sendHeadersForOutputType( $outputtype ) {
        global $wgRequest;
        // Provide a sane filename suggestion
        $basefilename = 'SPARQLOutput_';
        switch( $outputtype )
        {
            case 'xml':
                $wgRequest->response()->header( "Content-type: application/xml; charset=utf-8" );
                $filename = urlencode( $basefilename . wfTimestampNow() . '.xml' );
                $wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
                break;
            case 'rdfxml':
                $wgRequest->response()->header( "Content-type: application/xml; charset=utf-8" );
                $filename = urlencode( $basefilename . wfTimestampNow() . '.rdf.xml' );
                $wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
                break;
            case 'json':
                $wgRequest->response()->header( "Content-type: text/html; charset=utf-8" );
                $filename = urlencode( $basefilename . wfTimestampNow() . '.json.txt' );
                $wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
                break;
            case 'turtle':
                $wgRequest->response()->header( "Content-type: text/html; charset=utf-8" );
                $filename = urlencode( $basefilename . wfTimestampNow() . '.turtle.txt' );
                $wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
                break;
            case 'htmltab':
                // For HTML table we are taking care of the output earlier
                # $wgRequest->response()->header( "Content-type: text/html; charset=utf-8" );
                # $filename = urlencode( $basefilename . wfTimestampNow() . '.html' );
                break;
            case 'tsv':
                $wgRequest->response()->header( "Content-type: text/html; charset=utf-8" );
                $filename = urlencode( $basefilename . wfTimestampNow() . '.tsv.txt' );
                $wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
                break;
            default:
                $wgRequest->response()->header( "Content-type: application/xml; charset=utf-8" );
                $filename = urlencode( $basefilename . wfTimestampNow() . '.xml' );
                $wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
        }

    }

    /**
     * Get the HTML for the main SPARQL querying form. If $query is set, use it to prefill the main textarea
     * @param string $query
     * @return string $htmlForm
     */
    private function getHTMLForm( $query = '' ) {
        global $wgArticlePath, $wgUser, $wgRequest;

        $uriResolverURI = $this->store->getURIResolverURI();

        $defaultQuery = "@PREFIX w : <$uriResolverURI> .\n\nSELECT *\nWHERE { ?s ?p ?o }\nLIMIT 25";

        if ( $query == '' ) {
            $query = $defaultQuery;
        }

        $checked_equivuri_q = $wgRequest->getBool( 'equivuri_q', false ) == 1 ? ' checked="true" ' : '';
        $checked_equivuri_o = $wgRequest->getBool( 'equivuri_o', false ) == 1 ? ' checked="true" ' : '';
        $checked_filtervocab = $wgRequest->getBool( 'filtervocab', false ) == 1 ? ' checked="true" ' : '';
        $checked_allowwrite = $wgRequest->getBool( 'allowwrite', false ) == 1 ? ' checked="true" ' : '';
        $checked_showquery = $wgRequest->getBool( 'showquery', false ) == 1 ? ' checked="true" ' : '';

        $selected_output_html = $wgRequest->getText( 'output', '' ) == 'htmltab' ? ' selected="selected" ' : '';
        $selected_output_rdfxml = $wgRequest->getText( 'output', '' ) == 'rdfxml' ? ' selected="selected" ' : '';

        // Make the HTML format selected by default
        if ( $selected_output_rdfxml == '' ) {
            $selected_output_html = ' selected="selected" ';
        }

        $htmlForm = '<form method="post" action="' . str_replace( '/$1', '', $wgArticlePath ) . '/Special:SPARQLEndpoint"
            name="createEditQuery">
            <div style="font-size: 10px">

            <table border="0"><tbody>
            <tr><td colspan="3">Enter SPARQL query:</td><tr>
            <tr><td colspan="3"><textarea cols="80" rows="9" name="query">' . $query . '</textarea></td></tr>
            <tr>
            <td style="vertical-align: top; border-right: 1px solid #ccc;">

            <table border="0" style="background: transparent; font-size: 11px;">
            <tr><td style="text-align: right">Query by Equivalent URIs:</td>
            <td>
			<input type="checkbox" name="equivuri_q" value="1" ' . $checked_equivuri_q . '/>
            </td></tr>
            </table>

            </td>
            <td width="170" style="vertical-align: top; border-right: 1px solid #ccc;">

            <table border="0" style="font-size: 11px; background: transparent;">
            <tr><td style="text-align: right">Output Equivalent URIs:</td>
            <td>
			<input type="checkbox" name="equivuri_o" id="outputequivuri" value="1" ' . $checked_equivuri_o /* . ' onChange="toggleDisplay(\'byontology\');" */ . '/>
            </td></tr>
            </table>

            </td>
            <td width="260" style="vertical-align: top;">
Output Equivalent
            <table border="0" style="font-size: 11px; background: transparent;" >
            <tr><td style="text-align: right" width="180">Output format:</td>
            <td style="vertical-align: top">
            <select id="output" name="output" onChange="toggleDisplay(\'byontology\');" >
              <!-- <option value="" >default</option> -->
              <!-- <option value="json" >JSON</option> -->
              <!-- <option value="plain" >Plain</option> -->
              <!-- <option value="php_ser" >Serialized PHP</option> -->
              <!-- <option value="turtle" >Turtle</option> -->
              <option value="htmltab" ' . $selected_output_html . '>HTML</option>
              <option value="xml" >XML Resultset</option>
              <option value="rdfxml" ' . $selected_output_rdfxml . '>RDF/XML</option>
              <!-- <option value="infos" >Query Structure</option> -->
              <!-- <option value="tsv" >TSV</option> -->
            </select>
            </td></tr>
            <tr>
            <td colspan="2">
            <span style="font-family: arial, helvetica, sans-serif; font-size: 10px; color: #777;">(RDF/XML requires creating triples using <a href="http://www.w3.org/TR/rdf-sparql-query/#construct">CONSTRUCT</a>)</span>
            </td>
            </table>

            </td>
            </tr>
            <tr>
            <td colspan="3">

            <div id="byontology" style="display: none; background: #ffd; border: 1px solid #ee7;">
            <table border="0" style="font-size: 11px; background: transparent;" >
            <tr><td style="text-align: right;">Filter by vocabulary:</td>
            <td>
			<input type="checkbox" name="filtervocab" value="1" ' . $checked_filtervocab . '/>
            </td>
            <td style="text-align: right">Vocabulary URL:</td>
            <td>
			<input type="text" name="filtervocaburl" size="48" />
            </td></tr>
            <tr>
            <td>&#160;</td>
            <td>&#160;</td>
            <td>&#160;</td>
            <td>
            <span style="font-family: arial, helvetica, sans-serif; font-size: 10px; color: #777">Example: http://xmlns.com/foaf/spec/index.rdf</span>
            </td></tr>
			</table>
			</div>

            </td>
            </table>
			</div>

            <input type="submit" value="Submit">' . Html::Hidden( 'token', $wgUser->editToken() ) . '
            </form>';
        return $htmlForm;
    }

    /**
     * Get the javascript used for some functionality in the main SPARQL
     * querying HTML form
     * @return string $htmlFormScript
     */
    private function getHTMLFormScript() {
        $htmlFormScript = "<script type=\"text/javascript\">
        function toggleDisplay(id1) {
        	var bostyle = document.getElementById(id1).style.display;
        	var fmtsel = document.getElementById('output');
        	var fmt = fmtsel.options[fmtsel.selectedIndex].value;
        	var outsel = document.getElementById('outputequivuri');
        	if ( outsel.checked && fmt.match('rdfxml') ) {
				document.getElementById(id1).style.display = 'block';
			} else {
				document.getElementById(id1).style.display = 'none';
			}
    	}
	 	</script>";
        return $htmlFormScript;
    }

    /**
     * Get the query parameter from the request object
     * @return string $query
     */
    function getQuery() {
        $query = $wgRequest->getText( 'query' );
        return $query;
    }

    /**
     * Update the query variable in the $_POST object.
     * Useful for passing on parsing to ARC, since $_POST is what ARC reads
     * @param string $query
     */
    function setQueryInPost( $query ) {
        // Set the query in $_POST, so that ARC will grab the modified query
        $_POST['query'] = $query;
    }

    /**
     * Update the output (type) variable in the $_POST object.
     * Useful for passing on parsing to ARC, since $_POST is what ARC reads
     * @param string $type
     */
    function setOutputTypeInPost( $type ) {
        $_POST['output'] = $type;
    }

    function stringContains( $needle, $haystack ) {
        return strpos( $needle, $haystack ) != false;
    }

    function arrayEmpty( $array ) {
        return ( count( $array ) < 1 );
    }

}

class RDFIOSPARQLRequestData {
    function __construct() {
        $this->query = '';
        $this->querybyequivuri = false;
        $this->outputequivuris = false;
        $this->outputtype = '';
        $this->query_parsed = array();
    }
}