#! /bin/bash
set -ex

cd /home/ubuntu/w/extensions/Rdfio/tests/phpunit/
php ../../../../tests/phpunit/phpunit.php --use-normal-tables --configuration=suite.rdfio.xml --coverage-clover=coverage.xml --log-junit $CIRCLE_TEST_REPORTS/phpunit/junit.xml
mv coverage.xml $HOME/RDFIO/

cd /home/ubuntu/w/extensions/Rdfio/tests/systemtest/
./run_system_test_circleci.sh
./run_advanced_system_test_circleci.sh
./run_roundtrip_test_circleci.sh
