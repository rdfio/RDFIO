RDF IO Extension for Semantic MediaWiki
=======================================

[![Build Status](https://img.shields.io/circleci/project/github/rdfio/RDFIO.svg)](https://circleci.com/gh/rdfio/RDFIO)
[![Latest Stable Version](https://poser.pugx.org/rdfio/rdfio/version.png)](https://packagist.org/packages/rdfio/rdfio)
[![Packagist download count](https://poser.pugx.org/rdfio/rdfio/d/total.png)](https://packagist.org/packages/rdfio/rdfio)
[![Code coverage in master](https://img.shields.io/codecov/c/github/rdfio/RDFIO/master.svg)](https://codecov.io/gh/rdfio/RDFIO/branch/master)
<sup>Develop branch:</sup> [![Code coverage in develop](https://img.shields.io/codecov/c/github/rdfio/RDFIO/develop.svg)](https://codecov.io/gh/rdfio/RDFIO/branch/develop)


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

This extensions was initially developed as part of a
[Google Summer of Code 2010 project](http://www.mediawiki.org/wiki/User:SHL/GSoC2010),
and further extended as part of a [FOSS OPW 2014 project](https://www.mediawiki.org/wiki/Extension:RDFIO/Template_matching_for_RDFIO).

- Caution! *This extension is not yet ready for production use! Use it on your own risk!*

Installation
------------

**Please note:** RDFIO is not yet updated to work with SMW 2.5.x, so you have to use 2.4.x at the moment! 

### Vagrant box

The absolutely simplest way, is to use the [RDFIO Vagrant
box](https://github.com/rdfio/rdfio-vagrantbox), which will automatically set
up MediaWiki, SemanticMediaWiki and RDFIO in a virtual machine in under 20
minutes.

### Install semi-manually using composer

1. Provided you have the PHP package manager
   [Composer](https://getcomposer.org/) installed (See [this page](https://getcomposer.org/doc/00-intro.md)
   for install instructions), you should now be able to install RDFIO via
   packagist.org, like so:

   ```bash
   cd <wiki_folder>
   composer require rdfio/rdfio --update-no-dev
   ```

2.  After installing RDFIO using composer, only one manual step is required,
   namely to go to the `Special:RDFIOAdmin` page on your wiki, and hit the "setup"
   button, to initialize the MySQL tables needed by the ARC2 library that RDFIO
   builds upon.

Dependencies
------------

- [Semantic MediaWiki Extension](http://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki) 2.4.x
- [The ARC2 RDF library for PHP](https://github.com/semsol/arc2)

Bugs, new feature request and contact information
-------------------------------------------------

Please reports bugs and feature requests in the
[issue tracker](https://github.com/rdfio/RDFIO/issues) here on Github.
