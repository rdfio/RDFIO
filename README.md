RDF IO Extension for Semantic MediaWiki
=======================================

[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/samuell/RDFIO?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

- See also [RDFIO's page on MediaWiki.org](https://www.mediawiki.org/wiki/Extension:RDFIO)

![Screenshot of the SPARQL Endpoint shipped with RDFIO](http://i.imgur.com/PMMIHZ4.png)

Introduction
------------

This extension extends the RDF import and export functionality in Semantic
MediaWiki by providing import of arbitrary RDF triples (not only OWL
ontologies, as before (see about [Ontology import](http://semantic-mediawiki.org/wiki/Help:Ontology_import),
and a SPARQL endpoint that allows write operations.

Technically, RDFIO implements the PHP/MySQL based triple store (and its
accompanying SPARQL Endpoint) provided by the [ARC2](http://arc.semsol.org/)
library. For updating wiki pages with new triples on import/sparql update, the
WOM extension is used.

The RDF import stores the original URI of all imported RDF entities (in
a special property), which can later be used by the SPARQL endpoint,
instead of SMW's internal URIs, which thus allows to expose the imported
RDF data "in its original formats", with its original URIs. This allows
to use SMW as a collaborative RDF editor, in workflows together with
other semantic tools, from which it is then possible to "export,
collaboratively edit, and import again", to/from SMW.

This extensions was developed as part of a
[Google Summer of Code 2010 project](http://www.mediawiki.org/wiki/User:SHL/GSoC2010),
and further extended as part of a [FOSS OPW 2014 project](https://www.mediawiki.org/wiki/Extension:RDFIO/Template_matching_for_RDFIO).

- Caution! *This extension is not yet ready for production use! Use it on your own risk!*

Installation
------------

- See the [installation section](http://www.mediawiki.org/wiki/Extension:RDFIO#Installation) on MediaWiki.org.

Dependencies
------------

- [Semantic MediaWiki Extension](http://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki)
- [Wiki Object Model SMW Extension](https://www.mediawiki.org/wiki/Extension:Wiki_Object_Model)
- [The ARC2 RDF library for PHP](https://github.com/semsol/arc2)

Bugs, new feature request and contact information
-------------------------------------------------

Please reports bugs and feature requests in the
[issue tracker](https://github.com/rdfio/RDFIO/issues) here on Github.
