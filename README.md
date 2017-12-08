RDFIO Extension for Semantic MediaWiki
======================================

[![Build Status](https://img.shields.io/circleci/project/github/rdfio/RDFIO.svg)](https://circleci.com/gh/rdfio/RDFIO)
[![Test Coverage](https://img.shields.io/codecov/c/github/rdfio/RDFIO/master.svg)](https://codecov.io/gh/rdfio/RDFIO)
[![Code Climate Rating](https://img.shields.io/codeclimate/github/rdfio/RDFIO.svg)](https://codeclimate.com/github/rdfio/RDFIO)
[![Code Climate Issues](https://img.shields.io/codeclimate/issues/github/rdfio/RDFIO.svg)](https://codeclimate.com/github/rdfio/RDFIO/issues)
[![Codacy Grade](https://api.codacy.com/project/badge/Grade/60604793d171476e92e997b71aca20a2)](https://www.codacy.com/app/samuel-lampa/RDFIO)
[![Latest Stable Version](https://img.shields.io/packagist/v/rdfio/rdfio.svg)](https://packagist.org/packages/rdfio/rdfio)
[![Licence](https://img.shields.io/packagist/l/rdfio/rdfio.svg)]()

![Screenshot of the SPARQL Endpoint shipped with RDFIO](http://i.imgur.com/PMMIHZ4.png)

Updates
-------

**Sep 4, 2017:** Our paper on RDFIO was just published! If you use RDFIO in scientific work, please cite:<br>
Lampa S, Willighagen E, Kohonen P, King A, Vrandečić D, Grafström R, Spjuth O<br> 
[RDFIO: extending Semantic MediaWiki for interoperable biomedical data management](https://jbiomedsem.biomedcentral.com/articles/10.1186/s13326-017-0136-y)<br>
*Journal of Biomedical Semantics*. **8**:35 (2017). DOI: [10.1186/s13326-017-0136-y](https://dx.doi.org/10.1186/s13326-017-0136-y).

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

Installation
------------

### Easiest: Use the ready-made Virtual Machine

The absolute easiest way to try out RDFIO is to import the [Ready-made Virtual Machine with RDFIO 3.0.2 (with MW 1.29 and SMW 2.5)](https://doi.org/10.6084/m9.figshare.5383966) into VirtualBox or VMWare, and just start browsing the local wiki installation. 

**Steps:**

1. Download the `.ova` file from [doi.org/10.6084/m9.figshare.5383966.v1](https://doi.org/10.6084/m9.figshare.5383966.v1)
2. In VirtualBox (should be similar in VMWare), select _"File > Import appliance"_
3. Click the folder icon
4. Locate the `.ova` file you downloaded
5. Click _"Next"_, _"Agree"_ to the license, and finally _"Import"_, to start the import
6. Start the virtual machine
7. Click log in (No password required)
8. Click the icon on the desktop
9. You will now see a local wiki installation with an RDFIO enabled wiki, in a browser!
10. Enjoy!

### Easy: Vagrant box

Another quite easy way, is to use the [RDFIO Vagrant
box](https://github.com/rdfio/rdfio-vagrantbox), which will automatically set
up MediaWiki, SemanticMediaWiki and RDFIO in a virtual machine in under 20
minutes.

### Medium-hard: Install semi-manually using composer

#### Install dependencies

- [Composer](https://getcomposer.org/)
  - See [this page](https://www.mediawiki.org/wiki/Composer) for installation instructions.
- [MediaWiki](https://www.mediawiki.org)
  - See [this page](https://www.mediawiki.org/wiki/Installation) for installation instructions.
- [Semantic MediaWiki](https://www.semantic-mediawiki.org)
  - See [this page](http://semantic-mediawiki.org/wiki/Help:Installation) for installation instructions.
  - To show the "Semantic factbox" on all pages, make sure to include this in your LocalSettings.php file:

```php
$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;
```

#### Installation steps

Assuming you have followed the steps above to install the dependencies for RDFIO:

1. Install RDFIO by executing the following commands in a terminal:

   ```bash
   cd <wiki_folder>
   composer require rdfio/rdfio --update-no-dev
   ```

2. Log in to your wiki as a super user
3. Browse to `http://[your-domain]/wiki/Special:RDFIOAdmin`
4. Click the "Setup" button to set up ARC2 database tables.
5. If you already have semantic annotations in your wiki, you need to go to the article "Special:SMWAdmin" in your wiki, and click "Start updating data", and let it complete, in order for the data to be available in the SPARQL endpoint.
   
#### Optional but recommended steps

* Edit the MediaWiki:Sidebar page and add the following wiki snippet, as an extra menu (I use to place it before just the "* SEARCH" line), which will give you links to the main functionality with RDFIO from the main links in the left sidebar on the wiki:

   ```
   * Semantic Tools
   ** Special:RDFIOAdmin|RDFIO Admin
   ** Special:RDFImport|RDF Import
   ** Special:SPARQLEndpoint|SPARQL Endpoint
   ** Special:SPARQLImport|SPARQL Import
   ```

* Create the article "MediaWiki:Smw_uri_blacklist" and make sure it is empty (you might need to add some nonsense content like `{{{<!--empty-->}}}`).

#### Test that it works

* Access the **SPARQL endpoint** at `http://[url-to-your-wiki]/Special:SPARQLEndpoint`
* Access the **RDF Import page** at `http://[url-to-your-wiki]/Special:RDFImport`
* Access the **SPARQL Import page** at `http://[url-to-your-wiki]/Special:SPARQLImport`
* Optionally, if you want to really see that it works, try adding some semantic data to wiki pages, and then check the database (using phpMyAdmin e.g.) to see if you get some triples in the table named `arc2store_triple`.

### Additional configuration

These are some configuration options that you might want to adjust to your specific use case. That goes into your `LocalSettings.php` file. Find below a template with the default options, which you can start from, add to your `LocalSettings.php` file and modify to your liking:

```php
# ---------------------------------------------------------------
#  RDFIO Configuration
# ---------------------------------------------------------------
# An associative array with base uris as keys and corresponding 
# prefixes as the items. Example:
# array( 
#       "http://example.org/someOntology#" => "ont1",
#       "http://example.org/anotherOntology#" => "ont2"
#      );
# $rdfiogBaseURIs = array();
# ---------------------------------------------------------------
# Query by /Output Equivalent URIs SPARQL Endpoint 
# (overrides settings in HTML Form)
# 
# $rdfiogQueryByEquivURI = false;
# $rdfiogOutputEquivURIs = false;
#
# $rdfiogTitleProperties = array(
#  'http://semantic-mediawiki.org/swivt/1.0#page',
#  'http://www.w3.org/2000/01/rdf-schema#label',
#  'http://purl.org/dc/elements/1.1/title',
#  'http://www.w3.org/2004/02/skos/core#preferredLabel',
#  'http://xmlns.com/foaf/0.1/name',
#  'http://www.nmrshiftdb.org/onto#spectrumId'
# );
# ---------------------------------------------------------------
# Allow edit operations via SPARQL from remote services
#
# $rdfiogAllowRemoteEdit = false;
# ---------------------------------------------------------------
```

Dependencies
------------

- PHP 5.3 - latest (SMW [might have more strict deps](https://www.semantic-mediawiki.org/wiki/Help:Compatibility))
- MySQL (MariaDB unfortunatly not supported yet. See [#48](https://github.com/rdfio/RDFIO/issues/48))
- [MediaWiki](mediawiki.org) - Tested with 1.27 - 1.29
- [Semantic MediaWiki Extension](http://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki) - Tested with 2.4 - 3.0-alpha
- [The ARC2 RDF library for PHP](https://github.com/semsol/arc2) - Latest version on github should work

Known limitations
-----------------

- RDFIO does not yet support all the features of [SMW's vocabulary import](https://www.semantic-mediawiki.org/wiki/Help:Import_vocabulary).

Bugs, new feature request and contact information
-------------------------------------------------

Please reports bugs and feature requests in the
[issue tracker](https://github.com/rdfio/RDFIO/issues) here on Github.

Links
-----

- [RDFIO's page on MediaWiki.org](https://www.mediawiki.org/wiki/Extension:RDFIO)
- [RDFIO project page on pharmb.io (where it is partly developed)](https://pharmb.io/project/rdfio/)

Related work
------------

- [FresnelForms](http://is.cs.ou.nl/OWF/index.php5/Fresnel_Forms)
- [KnowledgeWiki](http://knoesis.org/node/2696)
- [LinkedData Extension](https://www.mediawiki.org/wiki/Extension:LinkedWiki)
