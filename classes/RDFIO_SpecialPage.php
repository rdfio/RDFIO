<?php

/**
 * Class RDFIOSpecialPage contains common functionality to be used
 * by multiple special pages in the RDFIO extension.
 */
class RDFIOSpecialPage extends SpecialPage {

	function __construct( $pageName, $restriction ) {
		parent::__construct( $pageName, $restriction );
	}

    /**
     * {@inheritDoc}
     */
    protected function getGroupName() {
        return 'rdfio_group';
    }

	/**
	 * Do preparations for getting outputted data as a downloadable file
	 * rather than written to the current page
	 */
	protected function prepareCreatingDownloadableFile( $options ) {
		$wOut = $this->getOutput();
		// Disable MediaWikis theming
		$wOut->disable();
		// Enables downloading as a stream, which is important for large dumps
		wfResetOutputBuffers();
		// Send headers telling that this is a special content type
		// and potentially is to be downloaded as a file
		$this->setHeadersForOutputType( $options->outputType );
	}


	/**
	 * Set headers appropriate to the filetype specified in $outputtype
	 * @param string $outputType
	 */
	private function setHeadersForOutputType( $outputType ) {
		$wRequest = $this->getRequest();

		$contentTypeMap = array(
			'sparqlresult' => 'application/sparql-results+xml',
			'rdfxml'       => 'application/rdf+xml',
			'json'         => 'application/json',
			'turtle'       => 'text/turtle',
			'htmltab'      => '', // Not applicable
			'tsv'          => 'text/html'
		);

		$extensionMap = array(
			'sparqlresult' => '.xml',
			'rdfxml'       => '_rdf.xml',
			'json'         => '.json',
			'turtle'       => '.ttl',
			'htmltab'      => '', // Not applicable
			'tsv'          => '.tsv'
		);

		if ( $outputType != 'htmltab' ) { // For HTML table we are taking care of the output earlier
			$wRequest->response()->header( 'Content-type: ' . $contentTypeMap[$outputType] . '; charset=utf-8' );

			$fileName = urlencode('sparql_result_' . wfTimestampNow() . $extensionMap[$outputType] );
			$wRequest->response()->header( 'Content-disposition: attachment;filename=' . $fileName );
		}
	}

	/**
	 * Check if writing to wiki is allowed, and handle a number
	 * of exceptions to that, by showing error messages etc
	 * @return bool
	 */
	protected function allowInsert( $user, $request ) {
		if ( !$this->editTokenOk( $user, $request ) ) {
			return false;
		}

		if ( in_array( 'edit', $user->getRights() ) && in_array( 'createpage', $user->getRights() ) ) {
			return true;
		}

		return false;
	}

	protected function editTokenOk( $user, $request ) {
		if ( $user->matchEditToken( $request->getText( 'token' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if deleting from wiki is allowed, and handle a number
	 * of exceptions to that, by showing error messages etc
	 */
	protected function allowDelete( $user ) {
		if ( in_array( 'edit', $user->getRights() ) && in_array( 'delete', $user->getRights() ) ) {
			return true;
		}
		$this->errorMsg( 'The current user lacks delete access' );
		return false;
	}

	/**
	 * Add a formatted success message to the HTML output, with $message as message.
	 * @param $message
	 */
	protected function infoMsg( $message ) {
		$infoHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #66AAFF; background-color: #AADDFF;">
				<h3>Information</h3>
				<p>' . $message . '</p>
								</div>';
		$wOut = $this->getOutput();
		$wOut->addHTML( $infoHtml );
	}

	/**
	 * Add a formatted success message to the HTML output, with $message as message.
	 * @param $message
	 */
	protected function successMsg( $message ) {
		$successHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #99FF99; background-color: #DDFFDD;">
				<h3>Success</h3>
				<p>' . $message . '</p>
								</div>';
		$wOut = $this->getOutput();
		$wOut->addHTML( $successHtml );
	}

	/**
	 * Add a formatted error message to the HTML output, with $message as message.
	 * @param $message
	 */
	protected function errorMsg( $message ) {
		$errorHtml = '<div style="margin: .4em 0; padding: .4em .7em; border: 1px solid #FF9999; background-color: #FFDDDD;">
				<h3>Error</h3>
				<p>' . $message . '</p>
								</div>';
		$wOut = $this->getOutput();
		$wOut->addHTML( $errorHtml );
	}

	/**
	 * Add a formatted error message to the HTML output, taking an array of messages
	 * @param $messages array
	 */
	protected function errorMsgArr( $messages ) {
		$allMsgs = '';
		foreach ( $messages as $msg ) {
			$allMsgs .= '<p>' . $msg . '</p>';
		}
		$this->errorMsg( $allMsgs );
	}
}
