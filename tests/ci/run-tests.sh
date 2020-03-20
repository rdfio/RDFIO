#! /bin/bash
set -ex

MWDIR=$HOME/w

cd $MWDIR/extensions/Rdfio/tests/phpunit/
php ../../../../tests/phpunit/phpunit.php --use-normal-tables --configuration=suite.rdfio.xml --coverage-clover=coverage.xml --log-junit $CIRCLE_TEST_REPORTS/phpunit/junit.xml
mv coverage.xml $HOME/project

# ---------------------------------------------
# Inactivated for now. Needs a thorough update:
# ---------------------------------------------
#cd $MWDIR/extensions/Rdfio/tests/systemtest/
#./run_simple_test_circleci.sh
#./run_advanced_system_test_circleci.sh
#./run_roundtrip_test_circleci.sh
