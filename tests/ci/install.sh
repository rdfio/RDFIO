#!/bin/bash
set -ex

MWDIR=$HOME/w
MWVER_MAJOR="1.34"
MWVER_MINOR=$MWVER_MAJOR".0"
SMWVER="3.1.5"
PHPUNITVER="6.5.5"

# Make sure we don't get stuck on interactive dialogues from tzdata
export TZ=Europe/Stockholm
ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
apt-get -qq update && apt-get -qq install -y wget mysql-server php php-mysql php-mbstring php-gd php-xml php-xdebug composer unzip curl

cd $HOME
wget "http://releases.wikimedia.org/mediawiki/"$MWVER_MAJOR"/mediawiki-"$MWVER_MINOR".tar.gz"
tar -zxf mediawiki-"$MWVER_MINOR".tar.gz
mv mediawiki-"$MWVER_MINOR" w

cd $MWDIR
service mysql start
echo "ALTER USER 'root'@'localhost' IDENTIFIED BY 'changethis';" | mysql
php maintenance/install.php --dbserver=localhost --scriptpath=/w --dbname=circle_test --dbuser=ubuntu --installdbuser=root --installdbpass=changethis --pass=changethis MW admin

echo "=== STARTING TO INSTALL SMW ==="

composer update
composer require mediawiki/semantic-media-wiki $SMWVER --update-no-dev

mkdir -p extensions/PageForms
cd extensions/PageForms
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/PageForms .

cd $MWDIR
echo 'enableSemantics( "localhost:8080" );' >> LocalSettings.php
echo '$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;' >> LocalSettings.php
echo 'include_once "$IP/extensions/PageForms/PageForms.php";' >> LocalSettings.php

# This must come after the enableSemantics(); line is added in LocalSettings.php
php extensions/SemanticMediaWiki/maintenance/setupStore.php

echo "=== STARTING TO INSTALL RDFIO ==="

cd $MWDIR
composer require rdfio/rdfio --update-no-dev
echo '$smwgOWLFullExport = true;' >> LocalSettings.php

rm -rf $MWDIR/extensions/Rdfio
mv ~/project $MWDIR/extensions/Rdfio
ln -s $MWDIR/extensions/Rdfio ~/project

cd $MWDIR/extensions/Rdfio/maintenance
php setupStore.php

cd $HOME
git clone https://github.com/rdfio/rdfio-vagrantbox.git vbox
php $MWDIR/maintenance/importDump.php $HOME/vbox/roles/rdfio/files/wiki_content.xml

# Require specific verison of PHPUnit
cd $MWDIR
composer require phpunit/phpunit $PHPUNITVER
