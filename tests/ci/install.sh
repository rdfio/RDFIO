#!/bin/bash
set -ex

BASE_PATH=$(pwd)
MW_DIR=$HOME/w
MWVER_MAJOR="1.29"
MWVER_MINOR=$MWVER_MAJOR".0"
SMWVER="2.5.4"
PHPUNITVER="4.7.6"

cd $HOME
wget "http://releases.wikimedia.org/mediawiki/"$MWVER_MAJOR"/mediawiki-"$MWVER_MINOR".tar.gz"
tar -zxf mediawiki-"$MWVER_MINOR".tar.gz
mv mediawiki-"$MWVER_MINOR" w

cd $MW_DIR
php maintenance/install.php --dbserver=127.0.0.1 --scriptpath=/w --dbname=circle_test --dbuser=ubuntu --installdbuser=ubuntu --pass=changethis MW admin

echo "=== STARTING TO INSTALL SMW ==="

sudo chown -R ubuntu:ubuntu .

composer require mediawiki/semantic-media-wiki $SMWVER --update-no-dev
php maintenance/update.php

mkdir -p extensions/PageForms
cd extensions/PageForms
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/PageForms .

cd $MW_DIR
echo 'enableSemantics( "localhost:8080" );' >> LocalSettings.php
echo '$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;' >> LocalSettings.php
echo 'include_once "$IP/extensions/PageForms/PageForms.php";' >> LocalSettings.php

echo "=== STARTING TO INSTALL RDFIO ==="

cd $MW_DIR
composer require rdfio/rdfio >=2.0.11 --update-no-dev
echo '$smwgOWLFullExport = true;' >> LocalSettings.php

rm -rf $MW_DIR/extensions/Rdfio
mv ~/RDFIO $MW_DIR/extensions/Rdfio
ln -s $MW_DIR/extensions/Rdfio ~/RDFIO

cd $MW_DIR/extensions/Rdfio/maintenance
php setupStore.php

cd
git clone https://github.com/rdfio/rdfio-vagrantbox.git vbox
cd $MW_DIR
php maintenance/importDump.php ~/vbox/roles/rdfio/files/wiki_content.xml

# Require specific verison of PHPUnit
cd $MW_DIR
composer require phpunit/phpunit $PHPUNITVER
