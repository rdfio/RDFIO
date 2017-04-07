#! /bin/bash
set -ex

cd /home/ubuntu/w/extensions/Rdfio/tests/phpunit/
php ../../../../tests/phpunit/phpunit.php --configuration=suite.rdfio.xml --coverage-clover=coverage.xml --log-junit $CIRCLE_TEST_REPORTS/phpunit/junit.xml
mv coverage.xml $HOME/RDFIO/
